<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShipperIncidentReported implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Order $order,
        public array $incident,
    ) {
        $this->order->loadMissing('branch');
    }

    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel('super-admin-incidents')];

        if (is_numeric($this->order->branch_id)) {
            $channels[] = new PrivateChannel('branch-admin-incidents.'.(int) $this->order->branch_id);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'shipper.incident.reported';
    }

    public function broadcastWith(): array
    {
        return [
            'incident_id' => (int) ($this->incident['incident_id'] ?? 0),
            'order_id' => (int) $this->order->id,
            'order_code' => $this->order->displayCode(),
            'branch_id' => is_numeric($this->order->branch_id) ? (int) $this->order->branch_id : null,
            'branch_name' => $this->order->branch?->name ?: 'Chi nhánh',
            'shipper_name' => (string) ($this->incident['shipper_name'] ?? 'Shipper'),
            'description' => (string) ($this->incident['description'] ?? 'Shipper báo sự cố.'),
            'reported_at_label' => (string) ($this->incident['reported_at_label'] ?? now()->format('H:i · d/m/Y')),
        ];
    }
}
