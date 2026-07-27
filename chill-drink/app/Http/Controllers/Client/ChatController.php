<?php

namespace App\Http\Controllers\Client;

use App\Events\ConversationClosed;
use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Http\Resources\MessageResource;
use App\Models\Branch;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    // ─── GPS / Branch listing ────────────────────────────────────────────────

    public function nearestBranches(Request $request)
    {
        $lat = $request->input('lat');
        $lng = $request->input('lng');

        if (is_numeric($lat) && is_numeric($lng)) {
            $lat = (float) $lat;
            $lng = (float) $lng;
            session(['user_lat' => $lat, 'user_lng' => $lng]);

            $branches = Branch::availableForLocation()
                ->get()
                ->map(function (Branch $branch) use ($lat, $lng) {
                    $distanceKm = $branch->distanceTo($lat, $lng);
                    return [
                        'id'            => $branch->id,
                        'name'          => $branch->name,
                        'address'       => $branch->address,
                        'phone'         => $branch->phone,
                        'distance'      => round($distanceKm, 2),
                        'distance_text' => round($distanceKm, 1) . ' km',
                    ];
                })
                ->sortBy('distance')
                ->values()
                ->take(3);
        } else {
            $branches = Branch::availableForLocation()
                ->take(3)
                ->get()
                ->map(function (Branch $branch) {
                    return [
                        'id'            => $branch->id,
                        'name'          => $branch->name,
                        'address'       => $branch->address,
                        'phone'         => $branch->phone,
                        'distance'      => 0,
                        'distance_text' => '',
                    ];
                });
        }

        return response()->json([
            'success'  => true,
            'branches' => $branches,
        ]);
    }

    // ─── Guest Init ──────────────────────────────────────────────────────────

    /**
     * Khởi tạo conversation cho khách vãng lai (chưa đăng nhập).
     * POST /chat/guest-init
     */
    public function guestInit(Request $request)
    {
        // Nếu đã đăng nhập, dùng luồng user bình thường
        if (auth()->check()) {
            return $this->getOrCreateConversation($request);
        }

        $request->validate([
            'guest_name'  => 'required|string|max:100',
            'guest_email' => 'required|email|max:200',
        ]);

        $guestName  = trim($request->input('guest_name'));
        $guestEmail = strtolower(trim($request->input('guest_email')));
        $guestToken = $request->input('guest_token');

        // Nếu có token hiện có → khôi phục conversation cũ
        if ($guestToken) {
            $conversation = Conversation::where('guest_token', $guestToken)
                ->where('status', 'open')
                ->first();

            if ($conversation) {
                $conversation->load('branch');
                return response()->json([
                    'success'         => true,
                    'conversation_id' => $conversation->id,
                    'branch_id'       => $conversation->branch_id,
                    'branch_name'     => $conversation->branch?->name ?? '',
                    'status'          => $conversation->status,
                    'guest_token'     => $conversation->guest_token,
                    'guest_name'      => $conversation->guest_name,
                ]);
            }
        }

        // Tạo conversation mới cho guest
        $newToken     = Str::uuid()->toString();
        $conversation = Conversation::create([
            'user_id'     => null,
            'guest_name'  => $guestName,
            'guest_email' => $guestEmail,
            'guest_token' => $newToken,
            'subject'     => 'Hỗ trợ khách hàng (Guest)',
            'status'      => 'open',
            'branch_id'   => null,
        ]);

        return response()->json([
            'success'         => true,
            'conversation_id' => $conversation->id,
            'branch_id'       => null,
            'branch_name'     => '',
            'status'          => 'open',
            'guest_token'     => $newToken,
            'guest_name'      => $guestName,
        ]);
    }

    // ─── Get or Create Conversation (Authenticated user) ────────────────────

    /**
     * GET /chat — dành cho user đã đăng nhập hoặc guest có token
     */
    public function getOrCreateConversation(Request $request)
    {
        // Guest with token
        if (!auth()->check()) {
            $guestToken = $request->input('guest_token');
            if (!$guestToken) {
                return response()->json([
                    'success'      => false,
                    'requires_guest_init' => true,
                    'message'      => 'Vui lòng nhập thông tin để bắt đầu chat.',
                ], 200);
            }

            $conversation = Conversation::where('guest_token', $guestToken)
                ->where('status', 'open')
                ->first();

            if (!$conversation) {
                return response()->json([
                    'success'      => false,
                    'requires_guest_init' => true,
                    'message'      => 'Phiên chat đã kết thúc. Vui lòng bắt đầu phiên mới.',
                ], 200);
            }

            $conversation->load('branch');
            return response()->json([
                'success'         => true,
                'conversation_id' => $conversation->id,
                'branch_id'       => $conversation->branch_id,
                'branch_name'     => $conversation->branch?->name ?? '',
                'status'          => $conversation->status,
                'guest_token'     => $conversation->guest_token,
                'guest_name'      => $conversation->guest_name,
            ]);
        }

        // Authenticated user
        $this->ensureCustomer();
        $user = auth()->user();

        $conversation = $user->conversations()
            ->where('status', 'open')
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->first();

        if (!$conversation) {
            $conversation = $user->conversations()->create([
                'subject'   => 'Hỗ trợ khách hàng',
                'status'    => 'open',
                'branch_id' => null,
            ]);
        }

        $conversation->load('branch');

        return response()->json([
            'success'         => true,
            'conversation_id' => $conversation->id,
            'branch_id'       => $conversation->branch_id,
            'branch_name'     => $conversation->branch?->name ?? '',
            'status'          => $conversation->status,
            'guest_token'     => null,
            'is_logged_in'    => true,
        ]);
    }

    // ─── Select Branch ───────────────────────────────────────────────────────

    public function selectBranch(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'branch_id'       => 'required|exists:branches,id',
        ]);

        $conversation = Conversation::findOrFail($request->conversation_id);
        $this->authorizeConversation($conversation, $request);

        $branch      = Branch::findOrFail($request->branch_id);
        $isNewBranch = ($conversation->branch_id !== $branch->id);

        $conversation->update(['branch_id' => $branch->id]);
        session(['nearest_branch_id' => $branch->id]);

        $systemMessage = null;

        if ($isNewBranch || $conversation->messages()->count() === 0) {
            // Tìm staff user để gán làm sender cho tin nhắn Bot
            $staffUser = User::whereIn('role_id', [2, 3, 4])
                ->where('branch_id', $branch->id)
                ->first()
                ?? User::whereIn('role_id', [2, 3, 4])->first();

            if ($staffUser) {
                $branchNameFormatted = Str::startsWith($branch->name, 'Chi nhánh')
                    ? $branch->name
                    : 'Chi nhánh ' . $branch->name;

                $systemContent   = "🤖 Hệ thống\nXin chào!\nBạn đã được kết nối với " . $branchNameFormatted . ".\n\nNhân viên sẽ phản hồi bạn trong giây lát.\nBạn có thể gửi trước câu hỏi hoặc yêu cầu của mình.";
                $systemMessage   = $conversation->messages()->create([
                    'sender_id' => $staffUser->id,
                    'content'   => $systemContent,
                ]);

                DB::table('conversations')
                    ->where('id', $conversation->id)
                    ->update(['last_message_at' => now()]);

                $systemMessage->load(['sender', 'displayAsSender']);

                try {
                    broadcast(new MessageSent($systemMessage))->toOthers();
                } catch (\Throwable) {}
            }
        }

        return response()->json([
            'success'     => true,
            'branch_id'   => $branch->id,
            'branch_name' => $branch->name,
            'message'     => $systemMessage ? MessageResource::toPublicArray($systemMessage) : null,
        ]);
    }

    // ─── End Session ─────────────────────────────────────────────────────────

    public function endSession(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
        ]);

        $conversation = Conversation::findOrFail($request->conversation_id);
        $this->authorizeConversation($conversation, $request);

        $conversation->update(['status' => 'closed']);

        try {
            broadcast(new ConversationClosed($conversation, 'client'))->toOthers();
        } catch (\Throwable) {}

        return response()->json([
            'success' => true,
            'message' => 'Đã kết thúc phiên tư vấn thành công.',
        ]);
    }

    // ─── Messages ────────────────────────────────────────────────────────────

    public function messages(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
        ]);

        $conversation = Conversation::findOrFail($request->conversation_id);
        $this->authorizeConversation($conversation, $request);

        // Tải toàn bộ lịch sử tin nhắn tại chi nhánh
        if ($conversation->branch_id) {
            if ($conversation->user_id) {
                // Authenticated user: lấy tất cả conversation tại branch đó
                $conversationIds = Conversation::where('user_id', $conversation->user_id)
                    ->where('branch_id', $conversation->branch_id)
                    ->pluck('id');
            } else {
                // Guest: chỉ lấy conversation hiện tại (guest chỉ có 1 conversation)
                $conversationIds = collect([$conversation->id]);
            }

            $messages = Message::whereIn('conversation_id', $conversationIds)
                ->with(['sender', 'displayAsSender'])
                ->orderBy('created_at', 'asc')
                ->get();
        } else {
            $messages = $conversation->messages()
                ->with(['sender', 'displayAsSender'])
                ->get();
        }

        if ($request->input('mark_as_read') && auth()->check()) {
            $conversation->messages()
                ->where('is_read', false)
                ->where('sender_id', '!=', auth()->id())
                ->update(['is_read' => true]);
        }

        return response()->json([
            'success'             => true,
            'conversation_status' => $conversation->status,
            'messages'            => $messages->map(
                fn (Message $message) => MessageResource::toPublicArray($message)
            ),
        ]);
    }

    // ─── Send Message ────────────────────────────────────────────────────────

    public function send(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'content'         => 'nullable|string|max:5000',
            'attachment'      => 'nullable|file|max:10240',
        ]);

        if (empty($request->input('content')) && !$request->hasFile('attachment')) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng nhập nội dung hoặc đính kèm file.',
            ], 422);
        }

        $conversation = Conversation::findOrFail($request->conversation_id);
        $this->authorizeConversation($conversation, $request);

        if ($conversation->status === 'closed') {
            return response()->json([
                'success' => false,
                'message' => 'Phiên chat đã kết thúc.',
            ], 403);
        }

        $attachmentPath = null;
        $attachmentName = null;

        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('chat-attachments', 'public');
            $attachmentName = $request->file('attachment')->getClientOriginalName();
        }

        $messageData = [
            'content'         => $request->input('content'),
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
        ];

        if (auth()->check()) {
            // Authenticated user: gán sender_id = user's id
            $messageData['sender_id'] = auth()->id();
        } else {
            // Guest: sender_id = null, guest_sender_name = tên guest từ conversation
            $messageData['sender_id']         = null;
            $messageData['guest_sender_name'] = $conversation->guest_name ?? 'Khách vãng lai';
        }

        $message = $conversation->messages()->create($messageData);

        DB::table('conversations')
            ->where('id', $conversation->id)
            ->update(['last_message_at' => now()]);

        $message->load(['sender', 'displayAsSender']);

        try {
            broadcast(new MessageSent($message))->toOthers();
        } catch (\Throwable) {}

        return response()->json([
            'success' => true,
            'message' => MessageResource::toPublicArray($message),
        ]);
    }

    // ─── Authorization Helper ────────────────────────────────────────────────

    /**
     * Kiểm tra xem request có quyền truy cập conversation này không.
     * Hỗ trợ cả user đã đăng nhập và guest dùng guest_token.
     */
    protected function authorizeConversation(Conversation $conversation, Request $request): void
    {
        // User đã đăng nhập
        if (auth()->check()) {
            if ($conversation->user_id !== auth()->id()) {
                abort(403, 'Bạn không có quyền truy cập cuộc trò chuyện này.');
            }
            return;
        }

        // Guest: kiểm tra guest_token
        $guestToken = $request->input('guest_token');
        if (!$guestToken || $conversation->guest_token !== $guestToken) {
            abort(403, 'Token không hợp lệ. Vui lòng bắt đầu lại phiên chat.');
        }
    }

    protected function ensureCustomer(): void
    {
        abort_unless(auth()->user()?->isCustomer(), 403);
    }
}
