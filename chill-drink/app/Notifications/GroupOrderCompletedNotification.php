<?php

namespace App\Notifications;

use App\Models\GroupOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class GroupOrderCompletedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly GroupOrder $groupOrder,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'group_order_completed',
            'title' => 'Đơn nhóm đã được đặt',
            'message' => "Chủ nhóm đã hoàn tất đặt đơn \"{$this->groupOrder->name}\". Phòng đã đóng.",
            'status' => 'ordered',
            'status_label' => 'Đã đặt',
            'group_order_id' => $this->groupOrder->id,
        ];
    }
}
