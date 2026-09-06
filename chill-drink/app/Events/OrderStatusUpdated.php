<?php

namespace App\Events;

use App\Models\Order;
use App\Services\ShipperBundleService;
use App\Support\OrderDistancePolicy;
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
        $this->order->loadMissing('user', 'shipper.user', 'branch', 'address');
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
        $this->order->loadMissing('orderItems');
        $payload = OrderStatus::notificationPayload($this->order);
        $status = (string) $payload['status'];
        $orderCode = $this->order->displayCode();
        $fulfillmentType = $this->order->fulfillment_type ?? 'delivery';
        $total = (int) ($this->order->total ?? $this->order->total_price ?? 0);
        $bundleTrip = app(ShipperBundleService::class)->activeTripForOrder($this->order);
        $distanceKm = null;
        $destinationLatitude = $this->order->shipping_latitude ?? $this->order->address?->latitude;
        $destinationLongitude = $this->order->shipping_longitude ?? $this->order->address?->longitude;
        foreach ([$this->order->note, $this->order->delivery_note] as $text) {
            if (is_string($text) && preg_match('/khoảng cách\s+([0-9]+(?:[.,][0-9]+)?)\s*km/iu', $text, $matches)) {
                $distanceKm = (float) str_replace(',', '.', $matches[1]);
                break;
            }
        }
        if ($distanceKm === null && $this->order->branch && is_numeric($destinationLatitude) && is_numeric($destinationLongitude)) {
            $distanceKm = OrderDistancePolicy::distanceFromBranch($this->order->branch, $destinationLatitude, $destinationLongitude);
        }
        $shipperOrder = [
            'id' => (int) $this->order->id,
            'assignment_key' => ((int) $this->order->id).':'.(optional($this->order->updated_at)->timestamp ?: time()),
            'assignment_ts' => optional($this->order->updated_at)->timestamp,
            'code' => $orderCode,
            'status' => $status,
            'status_label' => $payload['status_label'],
            'show_url' => route('shipper.orders.show', $this->order->id),
            'map_url' => route('shipper.map', ['id' => $this->order->id]),
            'customer_name' => $this->order->customerName() ?: 'Khách hàng',
            'customer_phone' => $this->order->customerPhone() ?: '',
            'shipping_address' => $this->order->getShippingAddress(),
            'branch_name' => $this->order->branch?->name ?? 'Chi nhánh',
            'total_formatted' => number_format($total, 0, ',', '.').'đ',
            'distance_km' => $distanceKm,
            'details' => [
                'item_lines' => $this->order->orderItems->count(),
                'total_cups' => (int) $this->order->orderItems->sum('quantity'),
                'bundle_order_count' => $bundleTrip ? count($bundleTrip['order_ids'] ?? []) : 1,
                'bundle_total_cups' => $bundleTrip ? (int) ($bundleTrip['total_cups'] ?? 0) : (int) $this->order->orderItems->sum('quantity'),
                'total_formatted' => number_format($total, 0, ',', '.').'đ',
            ],
        ];

        return [
            'order_id' => (int) $this->order->id,
            'order_code' => $orderCode,
            'branch_id' => is_numeric($this->order->branch_id) ? (int) $this->order->branch_id : null,
            'status' => $status,
            'status_label' => $payload['status_label'],
            'status_icon' => OrderStatus::notificationIcon($status),
            'status_class' => 'status-text-'.$status,
            'status_options' => OrderStatus::orderManagementOptions($status, $fulfillmentType),
            'next_status' => OrderStatus::orderManagementNextStatus($status, $fulfillmentType),
            'can_update' => count(OrderStatus::orderManagementOptions($status, $fulfillmentType)) > 1,
            'updated_at' => $this->order->updated_at?->toIso8601String(),
            'status_changed_at' => $this->order->status_changed_at?->format('d/m H:i'),
            'status_changed_by_name' => $this->order->status_changed_by
                ? (\App\Models\User::find($this->order->status_changed_by)?->name ?? 'Nhân viên')
                : null,
            'message' => $payload['message'],
            'title' => $payload['title'],
            'cancellation_reason' => $this->order->cancellation_reason ?? null,
            'url' => route('orders.index', ['order' => $this->order->id]),
            'shipper_order' => $shipperOrder,
        ];
    }
}
