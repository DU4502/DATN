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

        $messages = $conversation->messages()
            ->with(['sender', 'displayAsSender'])
            ->get();

        return response()->json([
            'success' => true,
            'can_reply' => $this->canReply($conversation),
            'messages' => $messages->map(
                fn (Message $message) => MessageResource::toStaffArray($message, auth()->user())
            ),
        ]);
    }

    public function reply(Request $request, Conversation $conversation)
    {
        $request->validate([
            'content' => 'required|string|max:5000',
        ]);

        abort_unless($this->canReply($conversation), 403);

        $user = auth()->user();

        if (!$conversation->cskh_id && !$user->isSuperAdmin()) {
            $conversation->update(['cskh_id' => $user->id]);
        }

        if ($conversation->status === 'closed') {
            $conversation->update(['status' => 'open']);
        }

        $message = $this->createMessage($conversation, [
            'sender_id' => $user->id,
            'content' => $request->content,
        ]);

        return $this->jsonMessageResponse($message);
    }

    public function close(Conversation $conversation)
    {
        $this->authorizeView($conversation);

        $conversation->update(['status' => 'closed']);

        return back()->with('success', 'Cuộc trò chuyện đã được đóng!');
    }

    protected function conversationQuery()
    {
        $user = auth()->user();

        $query = Conversation::with(['user', 'cskh', 'latestMessage', 'branch'])
            ->whereHas('user', fn ($customer) => $customer->customers())
            ->where('status', 'open'); // Chỉ hiện các chat đang mở trên sidebar

        if ($user->isSuperAdmin()) {
            if (request()->has('branch_id') && request('branch_id')) {
                $query->where('branch_id', request('branch_id'));
            }
        } elseif ($user->isAdmin() || $user->isCskh()) {
            if ($user->branch_id) {
                $query->where('branch_id', $user->branch_id);
            }
            
            // Also filter by assigned cskh if it's just CSKH (role 4)
            if ($user->isCskh() && !$user->isAdmin()) {
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
        $conversation->loadMissing('user');
        $user = auth()->user();

        if (! $conversation->user?->isCustomer()) {
            abort(403);
        }

        if ($user->isAdmin()) {
            return;
        }

        if ($conversation->cskh_id === $user->id || !$conversation->cskh_id) {
            return;
        }

        abort(403);
    }

    protected function canReply(Conversation $conversation): bool
    {
        $user = auth()->user();

        // Admin và Super Admin luôn có quyền trả lời
        if ($user->isAdmin()) {
            return true;
        }

        // CSKH chỉ được trả lời nếu được assign hoặc chưa có ai assign
        return $conversation->cskh_id === $user->id || !$conversation->cskh_id;
    }

    protected function createMessage(Conversation $conversation, array $data): Message
    {
        $message = $conversation->messages()->create($data);

        // Cập nhật last_message_at nhanh nhất bằng raw SQL (bỏ qua Eloquent overhead)
        DB::table('conversations')
            ->where('id', $conversation->id)
            ->update(['last_message_at' => now()]);

        $message->load(['sender', 'displayAsSender']);

        try {
            broadcast(new MessageSent($message))->toOthers();
        } catch (\Throwable) {
            // Broadcasting optional when Reverb/queue is not configured.
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
        $conversation->messages()
            ->where('is_read', false)
            ->where('sender_id', $conversation->user_id)
            ->update(['is_read' => true]);
    }
}
