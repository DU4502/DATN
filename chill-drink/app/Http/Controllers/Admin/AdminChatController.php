<?php

namespace App\Http\Controllers\Admin;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminChatController extends Controller
{
    public function index()
    {
        $conversations = $this->conversationQuery()
            ->orderBy('last_message_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.chat.index', compact('conversations'));
    }

    /**
     * API: trả về số tin nhắn chưa đọc của admin hiện tại.
     */
    public function unreadCount()
    {
        return response()->json([
            'count' => auth()->user()->unreadConversationMessagesCount(),
        ]);
    }
    /**
     * API: trả về danh sách conversation dạng JSON cho frontend polling nhẹ.
     */
    public function conversationList()
    {
        $user    = auth()->user();
        $search  = trim((string) request()->query('q', ''));

        $query = $this->conversationQuery()
            ->withCount([
                'messages as unread_count' => fn ($q) => $q
                    ->where('is_read', false)
                    ->whereHas('sender', fn ($u) => $u->where('role_id', 1)),
            ]);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                // Thành viên đã đăng ký
                $q->whereHas('user', fn ($u) => $u
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                )
                // Khách vãng lai
                ->orWhere('guest_name', 'like', "%{$search}%")
                ->orWhere('guest_email', 'like', "%{$search}%");
            });
        }

        $conversations = $query
            ->orderBy('last_message_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'conversations' => $conversations->map(fn ($c) => [
                'id'          => $c->id,
                'user_id'     => $c->user_id,
                'is_guest'    => is_null($c->user_id) && !is_null($c->guest_token),
                'user_name'   => is_null($c->user_id) ? ($c->guest_name ?? 'Khách vãng lai') : ($c->user?->name ?? '—'),
                'user_email'  => is_null($c->user_id) ? ($c->guest_email ?? '') : ($c->user?->email ?? ''),
                'guest_name'  => $c->guest_name,
                'guest_email' => $c->guest_email,
                'cskh_name'   => $c->cskh?->name,
                'unread'      => (int) $c->unread_count,
                'can_reply'   => $user->isSuperAdmin() || $user->isAdmin() || !$c->cskh_id || $c->cskh_id === $user->id,
                'last_at'     => $c->latestMessage?->created_at?->format('H:i'),
            ]),
            'total_unread' => $conversations->sum('unread_count'),
        ]);
    }

    public function show(Conversation $conversation)
    {
        $this->authorizeView($conversation);

        $conversation->load([
            'user',
            'cskh',
            'messages.sender',
            'messages.displayAsSender',
        ]);

        $conversations = $this->conversationQuery()
            ->orderBy('last_message_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $this->markMessagesAsRead($conversation);

        $canReply = $this->canReply($conversation);

        return view('admin.chat.index', compact('conversations', 'conversation', 'canReply'));
    }

    public function messages(Conversation $conversation)
    {
        $this->authorizeView($conversation);

        $this->markMessagesAsRead($conversation);

        if ($conversation->branch_id) {
            if ($conversation->user_id) {
                // User conversation: lấy tất cả tin nhắn tại chi nhánh đó từ mọi conversation của user
                $conversationIds = Conversation::where('user_id', $conversation->user_id)
                    ->where('branch_id', $conversation->branch_id)
                    ->pluck('id');
            } else {
                // Guest conversation: chỉ lấy conversation hiện tại
                $conversationIds = collect([$conversation->id]);
            }

            $messages = Message::whereIn('conversation_id', $conversationIds)
                ->with(['sender', 'displayAsSender'])
                ->orderBy('created_at', 'asc')
                ->get();
        } else {
            $messages = Message::where('conversation_id', $conversation->id)
                ->with(['sender', 'displayAsSender'])
                ->orderBy('created_at', 'asc')
                ->get();
        }

        return response()->json([
            'success'   => true,
            'can_reply' => $this->canReply($conversation),
            'messages'  => $messages->map(
                fn (Message $message) => MessageResource::toStaffArray($message, auth()->user())
            ),
        ]);
    }

    public function reply(Request $request, Conversation $conversation)
    {
        $this->authorizeView($conversation);

        $request->validate([
            'content' => 'required|string|max:5000',
        ]);

        $user = auth()->user();

        $message = DB::transaction(function () use ($conversation, $user, $request) {
            $lockedConversation = Conversation::query()
                ->lockForUpdate()
                ->findOrFail($conversation->id);

            // Re-check quyền sau khi lấy lock để request đến sau không thể
            // ghi đè người phụ trách đã được request trước đó nhận.
            abort_unless($this->canReply($lockedConversation), 403);

            if (! $lockedConversation->cskh_id && ! $user->isSuperAdmin()) {
                $lockedConversation->update(['cskh_id' => $user->id]);
            }

            if ($lockedConversation->status === 'closed') {
                $lockedConversation->update(['status' => 'open']);
            }

            return $this->createMessage($lockedConversation, [
                'sender_id' => $user->id,
                'content' => $request->content,
            ]);
        });

        return $this->jsonMessageResponse($message);
    }

    public function close(Conversation $conversation)
    {
        $this->authorizeView($conversation);

        $conversation->update(['status' => 'closed']);

        try {
            broadcast(new \App\Events\ConversationClosed($conversation, 'cskh'))->toOthers();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('ConversationClosed Broadcast Error: ' . $e->getMessage(), [
                'conversation_id' => $conversation->id,
            ]);
        }

        \App\Models\SystemLog::record(
            auth()->user(),
            "Đã đóng cuộc trò chuyện #{$conversation->id} của " . ($conversation->user?->name ?? $conversation->guest_name ?? 'Khách vãng lai'),
            'chat',
            'info'
        );

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Cuộc trò chuyện đã được đóng!');
    }

    protected function conversationQuery()
    {
        $user = auth()->user();

        // Lấy tất cả conversation (cả user đã đăng nhập và khách vãng lai)
        // Điều kiện: (user_id có giá trị và là customer) HOẶC (user_id null và có guest_token)
        $query = Conversation::with(['user', 'cskh', 'latestMessage', 'branch'])
            ->where(function ($q) {
                $q->where(function ($inner) {
                    // Conversation của user đăng nhập với role customer
                    $inner->whereNotNull('user_id')
                        ->whereHas('user', fn ($u) => $u->customers());
                })->orWhere(function ($inner) {
                    // Conversation của khách vãng lai
                    $inner->whereNull('user_id')
                        ->whereNotNull('guest_token');
                });
            })
            ->where('status', 'open'); // Chỉ hiện các chat đang mở trên sidebar

        if ($user->isSuperAdmin()) {
            if (request()->has('branch_id') && request('branch_id')) {
                $query->where('branch_id', request('branch_id'));
            }
        } elseif ($user->isAdmin() || $user->isCskh() || $user->isStaffOnly()) {
            if (!$user->branch_id) {
                return $query->whereRaw('1 = 0');
            }

            $query->where('branch_id', $user->branch_id);

            // Also filter by assigned cskh if it's CSKH (role 4) or Staff (role 5)
            if (($user->isCskh() || $user->isStaffOnly()) && !$user->isAdmin()) {
                $query->where(function ($inner) use ($user) {
                    $inner->whereNull('cskh_id')
                        ->orWhere('cskh_id', $user->id);
                });
            }
        }

        return $query;
    }

    protected function authorizeView(Conversation $conversation): void
    {
        $user = auth()->user();

        if (!$user->isSuperAdmin() && $conversation->branch_id
            && (!$user->branch_id || (int) $conversation->branch_id !== (int) $user->branch_id)) {
            abort(403, 'Báº¡n khÃ´ng cÃ³ quyá»n xem cuá»™c trÃ² chuyá»‡n cÃ»a chi nhÃ¡nh khÃ¡c.');
        }

        // Guest conversation (user_id = null, guest_token có giá trị) — cho phép admin/cskh xem
        $isGuestConversation = is_null($conversation->user_id) && !is_null($conversation->guest_token);

        if (!$isGuestConversation) {
            // User conversation: kiểm tra có phải customer không
            $conversation->loadMissing('user');
            if (!$conversation->user?->isCustomer()) {
                abort(403);
            }
        }

        if ($user->isAdmin() || $user->isSuperAdmin()) {
            return;
        }

        // CSKH và Nhân viên (role 4, 5): được xem nếu chưa assign hoặc assign cho mình
        if ($user->isCskh() || $user->isStaffOnly()) {
            if ($conversation->cskh_id === $user->id || !$conversation->cskh_id) {
                return;
            }
            abort(403, 'Cuộc trò chuyện này đã được phân công cho nhân viên khác.');
        }

        abort(403);
    }

    protected function canReply(Conversation $conversation): bool
    {
        $user = auth()->user();

        // Admin và Super Admin luôn có quyền trả lời
        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return true;
        }

        // CSKH chỉ được trả lời nếu được assign hoặc chưa có ai assign
        return $conversation->cskh_id === $user->id || !$conversation->cskh_id;
    }

    protected function createMessage(Conversation $conversation, array $data): Message
    {
        $message = $conversation->messages()->create($data);

        DB::table('conversations')
            ->where('id', $conversation->id)
            ->update(['last_message_at' => now()]);

        $message->load(['sender', 'displayAsSender']);

        try {
            broadcast(new MessageSent($message))->toOthers();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Chat Broadcast Error: ' . $e->getMessage(), [
                'message_id' => $message->id,
                'conversation_id' => $message->conversation_id,
            ]);
        }

        return $message;
    }

    protected function jsonMessageResponse(Message $message)
    {
        return response()->json([
            'success' => true,
            'message' => MessageResource::toStaffArray($message, auth()->user()),
        ]);
    }

    protected function markMessagesAsRead(Conversation $conversation): void
    {
        $adminId = auth()->id();

        if ($conversation->branch_id && $conversation->user_id) {
            $conversationIds = Conversation::where('user_id', $conversation->user_id)
                ->where('branch_id', $conversation->branch_id)
                ->pluck('id');
        } else {
            $conversationIds = collect([$conversation->id]);
        }

        if ($conversation->user_id) {
            // User conversation: đánh dấu đã đọc tin nhắn từ customer
            $customerIds = \App\Models\User::where('role_id', 1)
                ->whereIn('id',
                    Message::whereIn('conversation_id', $conversationIds)
                        ->where('is_read', false)
                        ->where('sender_id', '!=', $adminId)
                        ->whereNotNull('sender_id')
                        ->pluck('sender_id')
                )
                ->pluck('id');

            if ($customerIds->isEmpty()) {
                return;
            }

            Message::whereIn('conversation_id', $conversationIds)
                ->where('is_read', false)
                ->whereIn('sender_id', $customerIds)
                ->update(['is_read' => true]);
        } else {
            // Guest conversation: đánh dấu đọc tin nhắn guest (sender_id = null, có guest_sender_name)
            Message::whereIn('conversation_id', $conversationIds)
                ->where('is_read', false)
                ->whereNull('sender_id')
                ->whereNotNull('guest_sender_name')
                ->update(['is_read' => true]);
        }
    }
}
