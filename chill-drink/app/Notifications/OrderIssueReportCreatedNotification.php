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

        return [
            'type' => 'order_issue_created',
            'title' => 'Đã tiếp nhận yêu cầu hỗ trợ',
            'message' => 'Yêu cầu cho đơn '.($order->order_code ?? '#'.$order->id).' đang chờ nhân viên xử lý.',
            'order_id' => $order->id,
            'status' => 'open',
            'status_label' => 'Đang chờ xử lý',
        ];
    }
}
