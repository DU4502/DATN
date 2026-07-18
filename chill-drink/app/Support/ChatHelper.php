<?php

namespace App\Support;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ChatHelper
{
    /**
     * Đảm bảo user có conversation chat với chi nhánh nhận đơn hàng.
     *
     * - Nếu đã có conversation open với đúng chi nhánh → giữ nguyên.
     * - Nếu conversation hiện tại là chi nhánh khác → đóng lại, tạo mới với chi nhánh của đơn.
     * - Nếu chưa có conversation nào → tạo mới.
     */
    public static function ensureChatWithOrderBranch(Order $order): void
    {
        if (! auth()->check() || ! $order->branch_id) {
            return;
        }

        $user = auth()->user();

        $existing = Conversation::where('user_id', $user->id)
            ->where('status', 'open')
            ->latest()
            ->first();

        // Đã có conversation với đúng chi nhánh → không cần làm gì
        if ($existing && (int) $existing->branch_id === (int) $order->branch_id) {
            return;
        }

        // Đóng conversation cũ với chi nhánh khác
        if ($existing && $existing->branch_id && (int) $existing->branch_id !== (int) $order->branch_id) {
            $existing->update(['status' => 'closed']);
        }

        $branch = \App\Models\Branch::find($order->branch_id);
        if (! $branch) {
            return;
        }

        // Tạo conversation mới gắn với đơn hàng và chi nhánh
        $conversation = Conversation::create([
            'user_id'   => $user->id,
            'subject'   => "Đơn hàng #{$order->id}",
            'status'    => 'open',
            'branch_id' => $branch->id,
        ]);

        // Tìm nhân viên thuộc chi nhánh để gửi system message
        $staffUser = User::whereIn('role_id', [2, 3, 4])
            ->where('branch_id', $branch->id)
            ->first()
            ?? User::whereIn('role_id', [2, 3, 4])->first()
            ?? $user;

        $message = $conversation->messages()->create([
            'sender_id' => $staffUser->id,
            'content'   => "Xin chào! Bạn vừa đặt đơn hàng #{$order->id} tại Chi nhánh {$branch->name}.\nNhân viên sẽ hỗ trợ bạn trong giây lát.",
        ]);

        DB::table('conversations')
            ->where('id', $conversation->id)
            ->update(['last_message_at' => now()]);

        $message->load(['sender', 'displayAsSender']);

        try {
            broadcast(new MessageSent($message))->toOthers();
        } catch (\Throwable) {
            // Broadcasting optional
        }
    }
}
