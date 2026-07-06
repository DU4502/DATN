<?php

namespace App\Notifications;

use App\Models\Order;
use App\Support\OrderStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderStatusUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(public Order $order)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $payload = OrderStatus::notificationPayload($this->order);

        return [
            ...$payload,
            'order_id' => $this->order->id,
            'link' => route('orders.index', ['order' => $this->order->id]),
        ];
    }
}
