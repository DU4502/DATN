<?php

namespace App\Events;

use App\Models\Order;
use App\Support\OrderStatus;
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
        $this->order->loadMissing('user');
    }

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('admin-notifications'),
        ];

        if ($this->order->user_id) {
            $channels[] = new PrivateChannel('user.'.$this->order->user_id);
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
            'order_id' => $this->order->id,
            'status' => $payload['status'],
            'status_label' => $payload['status_label'],
            'message' => $payload['message'],
            'title' => $payload['title'],
            'cancellation_reason' => $this->order->cancellation_reason ?? null,
            'url' => route('orders.index', ['order' => $this->order->id]),
        ];
    }
}
