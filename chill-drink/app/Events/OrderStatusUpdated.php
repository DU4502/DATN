<?php

namespace App\Events;

use App\Models\Order;
use App\Support\OrderStatus;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Order $order)
    {
        $this->order->loadMissing('user');
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.'.$this->order->user_id),
        ];
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
            'url' => route('orders.index', ['order' => $this->order->id]),
        ];
    }
}
