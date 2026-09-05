<?php

namespace App\Events;

use App\Models\Order;
use App\Support\OrderStatus;
use App\Support\OrderRealtimeChannel;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Order $order)
    {
        $this->order->loadMissing('user', 'shipper.user');
    }

    public function broadcastOn(): array
    {
        $branchId = is_numeric($this->order->branch_id)
            ? (int) $this->order->branch_id
            : null;
        $channels = [];

        if ($branchId) {
            $channels[] = new PrivateChannel('admin-notifications.'.$branchId);
        }

        if ($this->order->user_id) {
            $channels[] = new PrivateChannel('user.'.$this->order->user_id);
            $channels[] = new PrivateChannel(OrderRealtimeChannel::authenticated($this->order));
        } elseif ($guestChannel = OrderRealtimeChannel::guest($this->order)) {
            // Guest tracking uses the high-entropy guest capability token, never the
            // sequential order id, so another customer's order cannot be guessed.
            $channels[] = new Channel($guestChannel);
        }

        $shipperUserId = $this->order->shipper?->user_id;
        if (is_numeric($shipperUserId)) {
            $channels[] = new PrivateChannel('shipper-orders.'.(int) $shipperUserId);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'order.status.updated';
    }

    public function broadcastWith(): array
    {
        $payload = OrderStatus::notificationPayload($this->order);

        return [
            'order_id' => (int) $this->order->id,
            'order_code' => $this->order->displayCode(),
            'branch_id' => is_numeric($this->order->branch_id) ? (int) $this->order->branch_id : null,
            'status' => $payload['status'],
            'status_label' => $payload['status_label'],
            'status_icon' => OrderStatus::notificationIcon($payload['status']),
            'updated_at' => $this->order->updated_at?->toIso8601String(),
            'message' => $payload['message'],
            'title' => $payload['title'],
            'cancellation_reason' => $this->order->cancellation_reason ?? null,
            'url' => route('orders.index', ['order' => $this->order->id]),
        ];
    }
}
