<?php

namespace App\Notifications;

use App\Models\OrderIssueReport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderIssueReportCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly OrderIssueReport $report)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $order = $this->report->order;
        $isCustomer = (int) $notifiable->id === (int) $this->report->user_id;

        return [
            'type' => 'order_issue_created',
            'title' => $isCustomer ? 'Đã tiếp nhận yêu cầu hỗ trợ' : 'Có yêu cầu hỗ trợ mới',
            'message' => $isCustomer
                ? 'Yêu cầu cho đơn '.($order->order_code ?? '#'.$order->id).' đang chờ nhân viên xử lý.'
                : 'Khách hàng vừa gửi khiếu nại cho đơn '.($order->order_code ?? '#'.$order->id).'.',
            'order_id' => $order->id,
            'issue_id' => $this->report->id,
            'status' => 'open',
            'status_label' => 'Đang chờ xử lý',
        ];
    }
}
