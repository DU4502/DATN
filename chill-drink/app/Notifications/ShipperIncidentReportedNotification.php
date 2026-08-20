<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ShipperIncidentReportedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Order $order,
        private readonly array $incident,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $orderCode = $this->order->displayCode();
        $shipperName = (string) ($this->incident['shipper_name'] ?? 'Shipper');
        $description = (string) ($this->incident['description'] ?? 'Shipper báo sự cố.');

        return [
            'type' => 'shipper_incident_reported',
            'title' => 'Sự cố giao hàng cần xử lý',
            'message' => $orderCode.' · '.$shipperName.' · '.$description,
            'order_id' => (int) $this->order->id,
            'order_code' => $orderCode,
            'branch_id' => is_numeric($this->order->branch_id) ? (int) $this->order->branch_id : null,
            'incident_id' => (int) ($this->incident['incident_id'] ?? 0),
            'shipment_id' => (int) ($this->incident['shipment_id'] ?? 0),
        ];
    }
}
