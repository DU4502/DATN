<?php

namespace App\Support;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Order;
use App\Models\OrderIssueReport;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ChatHelper
{
    /** Đưa yêu cầu hỗ trợ đơn vào đúng cuộc trò chuyện của khách với chi nhánh. */
    public static function notifyOrderIssue(OrderIssueReport $issue): void
    {
        $issue->loadMissing(['order.branch', 'user']);
        $order = $issue->order;
        $customer = $issue->user;

        if (! $order?->branch_id || ! $customer) {
            return;
        }

        $conversation = Conversation::query()
            ->where('user_id', $customer->id)
            ->where('branch_id', $order->branch_id)
            ->where('status', 'open')
            ->latest('last_message_at')
            ->first();

        if (! $conversation) {
            $conversation = Conversation::create([
                'user_id' => $customer->id,
                'branch_id' => $order->branch_id,
                'order_id' => $order->id,
                'subject' => 'Hỗ trợ đơn '.$order->displayCode(),
                'status' => 'open',
            ]);
        } else {
            $conversation->update([
                'order_id' => $order->id,
                'subject' => 'Hỗ trợ đơn '.$order->displayCode(),
            ]);
        }

        $typeLabels = [
            'missing_item' => 'Thiếu món',
            'wrong_item' => 'Sai món',
            'quality_issue' => 'Chất lượng đồ uống',
            'other' => 'Vấn đề khác',
        ];
        $content = "[YÊU CẦU HỖ TRỢ ĐƠN {$order->displayCode()}]"
            ."\nVấn đề: ".($typeLabels[$issue->type] ?? 'Vấn đề khác')
            ."\nNội dung: ".trim($issue->description)
            .($issue->evidence_path ? "\nKhách đã gửi ảnh bằng chứng trong mục Yêu cầu hỗ trợ." : '');

        $message = $conversation->messages()->create([
            'sender_id' => $customer->id,
            'content' => $content,
            'is_read' => false,
        ]);

        $conversation->update(['last_message_at' => now()]);
        $message->load(['sender', 'displayAsSender']);

        try {
            broadcast(new MessageSent($message))->toOthers();
        } catch (\Throwable) {
        }
    }

    /** Gửi cập nhật phương án hỗ trợ từ nhân viên vào chính cuộc trò chuyện của khách. */
    public static function notifyOrderIssueStatus(OrderIssueReport $issue, User $staff): void
    {
        $issue->loadMissing('order');
        $order = $issue->order;
        if (! $order?->branch_id) {
            return;
        }

        $conversation = Conversation::query()
            ->where('user_id', $issue->user_id)
            ->where('branch_id', $order->branch_id)
            ->where('status', 'open')
            ->latest('last_message_at')
            ->first();
        if (! $conversation) {
            return;
        }

        $labels = ['open' => 'Đang chờ xử lý', 'processing' => 'Đang xử lý', 'awaiting_confirmation' => 'Chờ khách xác nhận', 'resolved' => 'Hoàn tất', 'rejected' => 'Không được chấp nhận'];
        $content = "[CẬP NHẬT HỖ TRỢ ĐƠN {$order->displayCode()}]"
            ."\nTrạng thái: ".($labels[$issue->status] ?? $issue->status)
            .($issue->resolution_value ? "\nPhương án: {$issue->resolution_value}" : '')
            .($issue->admin_note ? "\nPhản hồi: {$issue->admin_note}" : '');
        $message = $conversation->messages()->create([
            'sender_id' => $staff->id,
            'content' => $content,
            'is_read' => false,
        ]);
        $conversation->update(['last_message_at' => now()]);
        $message->load(['sender', 'displayAsSender']);
        try {
            broadcast(new MessageSent($message))->toOthers();
        } catch (\Throwable) {
        }
    }

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
            'subject'   => "Đơn hàng {$order->displayCode()}",
            'status'    => 'open',
            'branch_id' => $branch->id,
        ]);

        // Tìm nhân viên thuộc chi nhánh để gửi system message
        $staffUser = User::whereIn('role_id', [2, 3, 4])
            ->where('branch_id', $branch->id)
            ->where('is_active', true)
            ->first()
            ?? User::whereIn('role_id', [2, 3, 4])
                ->where('is_active', true)
                ->first();

        // Không có tài khoản staff thì bỏ qua system message, tuyệt đối
        // không dùng tài khoản khách hàng làm người gửi cho chính họ.
        if (! $staffUser) {
            return;
        }

        $message = $conversation->messages()->create([
            'sender_id' => $staffUser->id,
            'content'   => "Xin chào! Bạn vừa đặt đơn hàng {$order->displayCode()} tại Chi nhánh {$branch->name}.\nNhân viên sẽ hỗ trợ bạn trong giây lát.",
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
