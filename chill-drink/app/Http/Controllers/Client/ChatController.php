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

class ChatController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function nearestBranches(Request $request)
    {
        $this->ensureCustomer();

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
                        'id' => $branch->id,
                        'name' => $branch->name,
                        'address' => $branch->address,
                        'phone' => $branch->phone,
                        'distance' => round($distanceKm, 2),
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
                        'id' => $branch->id,
                        'name' => $branch->name,
                        'address' => $branch->address,
                        'phone' => $branch->phone,
                        'distance' => 0,
                        'distance_text' => '',
                    ];
                });
        }

        return response()->json([
            'success' => true,
            'branches' => $branches,
        ]);
    }

    public function getOrCreateConversation(Request $request)
    {
        $this->ensureCustomer();

        $user = auth()->user();

        // Tìm conversation 'open' hiện tại
        $conversation = $user->conversations()
            ->where('status', 'open')
            ->latest()
            ->first();

        if (!$conversation) {
            // Tạo conversation mới chưa chọn branch
            $conversation = $user->conversations()->create([
                'subject'   => 'Hỗ trợ khách hàng',
                'status'    => 'open',
                'branch_id' => null,
            ]);
        }

        $conversation->load('branch');

        return response()->json([
            'conversation_id' => $conversation->id,
            'branch_id'       => $conversation->branch_id,
            'branch_name'     => $conversation->branch?->name ?? '',
            'status'          => $conversation->status,
            'success'         => true,
        ]);
    }

    public function selectBranch(Request $request)
    {
        $this->ensureCustomer();

        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'branch_id'       => 'required|exists:branches,id',
        ]);

        $conversation = Conversation::findOrFail($request->conversation_id);

        if ($conversation->user_id !== auth()->id()) {
            abort(403);
        }

        $branch = Branch::findOrFail($request->branch_id);

        $isNewBranch = ($conversation->branch_id !== $branch->id);

        $conversation->update([
            'branch_id' => $branch->id,
        ]);

        session(['nearest_branch_id' => $branch->id]);

        $systemMessage = null;

        // Chỉ gửi tin nhắn chào từ Bot nếu conversation chưa có tin nhắn chào bot hoặc vừa chọn branch mới
        if ($isNewBranch || $conversation->messages()->count() === 0) {
            $staffUser = User::whereIn('role_id', [2, 3, 4])
                ->where('branch_id', $branch->id)
                ->first()
                ?? User::whereIn('role_id', [2, 3, 4])->first()
                ?? auth()->user();

            $branchNameFormatted = \Illuminate\Support\Str::startsWith($branch->name, 'Chi nhánh') ? $branch->name : 'Chi nhánh ' . $branch->name;
            $systemContent = "🤖 Hệ thống\nXin chào!\nBạn đã được kết nối với " . $branchNameFormatted . ".\n\nNhân viên sẽ phản hồi bạn trong giây lát.\nBạn có thể gửi trước câu hỏi hoặc yêu cầu của mình.";

            $systemMessage = $conversation->messages()->create([
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

        return response()->json([
            'success'     => true,
            'branch_id'   => $branch->id,
            'branch_name' => $branch->name,
            'message'     => $systemMessage ? MessageResource::toPublicArray($systemMessage) : null,
        ]);
    }

    public function endSession(Request $request)
    {
        $this->ensureCustomer();

        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
        ]);

        $conversation = Conversation::findOrFail($request->conversation_id);

        if ($conversation->user_id !== auth()->id()) {
            abort(403);
        }

        $conversation->update(['status' => 'closed']);

        try {
            broadcast(new ConversationClosed($conversation, 'client'))->toOthers();
        } catch (\Throwable) {}

        return response()->json([
            'success' => true,
            'message' => 'Đã kết thúc phiên tư vấn thành công.',
        ]);
    }

    public function messages(Request $request)
    {
        $this->ensureCustomer();

        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
        ]);

        $conversation = Conversation::findOrFail($request->conversation_id);

        if ($conversation->user_id !== auth()->id()) {
            abort(403);
        }

        // Nếu conversation đã chọn branch: Tải tất cả tin nhắn từ mọi conversation (cả closed) của user tại chi nhánh này
        if ($conversation->branch_id) {
            $conversationIds = Conversation::where('user_id', auth()->id())
                ->where('branch_id', $conversation->branch_id)
                ->pluck('id');

            $messages = Message::whereIn('conversation_id', $conversationIds)
                ->with(['sender', 'displayAsSender'])
                ->orderBy('created_at', 'asc')
                ->get();
        } else {
            $messages = $conversation->messages()
                ->with(['sender', 'displayAsSender'])
                ->get();
        }

        if ($request->input('mark_as_read')) {
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

    public function send(Request $request)
    {
        $this->ensureCustomer();

        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'content'         => 'nullable|string',
            'attachment'      => 'nullable|file|max:10240',
        ]);

        if (empty($request->input('content')) && !$request->hasFile('attachment')) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng nhập nội dung hoặc đính kèm file.',
            ], 422);
        }

        $conversation = Conversation::findOrFail($request->conversation_id);

        if ($conversation->user_id !== auth()->id()) {
            abort(403);
        }

        $attachmentPath = null;
        $attachmentName = null;

        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('chat-attachments', 'public');
            $attachmentName = $request->file('attachment')->getClientOriginalName();
        }

        $message = $conversation->messages()->create([
            'sender_id'       => auth()->id(),
            'content'         => $request->input('content'),
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
        ]);

        if ($conversation->status === 'closed') {
            $conversation->update(['status' => 'open']);
        }

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

    protected function ensureCustomer(): void
    {
        abort_unless(auth()->user()?->isCustomer(), 403);
    }
}
