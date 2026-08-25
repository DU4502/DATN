<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderArrivingSoonNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Order $order,
        public ?float $distanceMeters = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $orderCode = $this->order->displayCode();
        $distanceText = is_numeric($this->distanceMeters)
            ? 'còn khoảng '.max(0, (int) round($this->distanceMeters)).'m'
            : 'đang ở gần điểm giao';

        return [
            'type' => 'order_arriving_soon',
            'title' => "Đơn hàng {$orderCode} - Tài xế sắp đến",
            'message' => "Tài xế {$distanceText}. Bạn vui lòng chuẩn bị nhận hàng.",
            'status' => 'arriving_soon',
            'status_label' => 'Sắp đến',
            'order_id' => $this->order->id,
            'distance_m' => is_numeric($this->distanceMeters) ? (float) $this->distanceMeters : null,
        ];
    }
}
