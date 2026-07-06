<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Order $order)
    {
        $this->order->loadMissing('user');
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin-notifications'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'order.created';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        $customerName = $this->order->user->name ?? 'Khách hàng';
        $total = (int) ($this->order->total ?? $this->order->total_price ?? 0);

        return [
            'order_id' => $this->order->id,
            'customer_name' => $customerName,
            'customer_email' => $this->order->user->email ?? '',
            'total' => $total,
            'total_formatted' => number_format($total, 0, ',', '.').'đ',
            'payment_method' => $this->order->payment_method,
            'payment_status' => $this->order->payment_status,
            'status' => $this->order->status,
            'created_at' => $this->order->created_at?->toDateTimeString(),
            'message' => "Đơn hàng mới #{$this->order->id} từ {$customerName}",
        ];
    }
}
