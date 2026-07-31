<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Admin\AdminChatController;
use App\Models\Conversation;

/**
 * StaffChatController — nhân viên (role_id=5) quản lý chat.
 * Kế thừa toàn bộ logic từ AdminChatController,
 * chỉ override lại view paths để dùng staff layout.
 */
class StaffChatController extends AdminChatController
{
    public function index()
    {
        $conversations = $this->conversationQuery()
            ->orderBy('last_message_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('staff.chat.index', compact('conversations'));
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

        return view('staff.chat.index', compact('conversations', 'conversation', 'canReply'));
    }

    /**
     * Override canReply: nhân viên cũng được reply như CSKH
     */
    protected function canReply(Conversation $conversation): bool
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return true;
        }

        // Nhân viên hoặc CSKH: được reply nếu được assign hoặc chưa ai assign
        return $conversation->cskh_id === $user->id || !$conversation->cskh_id;
    }
}
