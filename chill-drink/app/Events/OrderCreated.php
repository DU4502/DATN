<?php

namespace App\Events;

use App\Models\Order;
use App\Support\OrderStatus;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Order $order)
    {
        $this->order->loadMissing('user', 'branch');
    }

    public function broadcastOn(): array
    {
        $branchId = is_numeric($this->order->branch_id)
            ? (int) $this->order->branch_id
            : null;

        $channels = $branchId
            ? [new PrivateChannel('admin-notifications.'.$branchId)]
            : [new PrivateChannel('admin-notifications')];

        return $channels;
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
        $orderCode = method_exists($this->order, 'displayCode')
            ? $this->order->displayCode()
            : ($this->order->order_code ?? ('#'.$this->order->id));
        $branchId = is_numeric($this->order->branch_id)
            ? (int) $this->order->branch_id
            : null;
        $branchName = $this->order->branch?->name ?? 'Chi nhánh';

        return [
            'order_id' => $this->order->id,
            'order_code' => $orderCode,
            'branch_id' => $branchId,
            'branch_name' => $branchName,
            'customer_name' => $customerName,
            'customer_email' => $this->order->user->email ?? '',
            'total' => $total,
            'total_formatted' => number_format($total, 0, ',', '.').'đ',
            'payment_method' => $this->order->payment_method,
            'payment_status' => $this->order->payment_status,
            'status' => OrderStatus::normalize((string) $this->order->status),
            'status_label' => OrderStatus::label((string) $this->order->status),
            'created_at' => $this->order->created_at?->toDateTimeString(),
            'url' => route('admin.orders.index', ['q' => $orderCode]),
            'message' => "Đơn hàng mới {$orderCode} từ {$customerName}",
        ];
    }
}
