<?php

namespace App\Notifications;

use App\Models\Order;
use App\Support\OrderStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ShipperOrderAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Order $order,
        private readonly string $mode = 'assigned',
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $orderCode = $this->order->displayCode();
        $statusLabel = OrderStatus::label((string) $this->order->status);
        $isBundle = $this->mode === 'bundle';

        return [
            'type' => 'shipper_order_assigned',
            'title' => $isBundle ? 'Có đơn ghép mới' : 'Có đơn hàng mới',
            'message' => $orderCode.' · '.$statusLabel.'. Mở dẫn đường để tiếp tục chuyến.',
            'order_id' => (int) $this->order->id,
            'order_code' => $orderCode,
            'status' => OrderStatus::normalize((string) $this->order->status),
            'status_label' => $statusLabel,
            'mode' => $this->mode,
            'link' => route('shipper.map', ['id' => $this->order->id]),
        ];
    }
}
