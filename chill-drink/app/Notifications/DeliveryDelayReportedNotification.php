<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DeliveryDelayReportedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Order $order,
        private readonly string $incidentDescription,
        private readonly ?int $incidentId = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'delivery_delay_reported',
            'title' => 'Đơn hàng có thể giao chậm hơn dự kiến',
            'message' => 'Xin lỗi bạn, chuyến giao '.($this->order->displayCode()).' đang gặp trở ngại. Chill Drink đã tiếp nhận và đang xử lý để giao đến bạn sớm nhất.',
            'detail' => $this->incidentDescription,
            'order_id' => (int) $this->order->id,
            'order_code' => $this->order->displayCode(),
            'incident_id' => $this->incidentId,
            'status' => (string) $this->order->status,
            'status_label' => 'Đang xử lý chậm trễ',
            'link' => route('orders.track', $this->order),
        ];
    }
}
