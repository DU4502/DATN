<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Shipper;
use App\Models\User;
use App\Support\OrderStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class ShipperRoleChangeGuard
{
    private const TERMINAL_ORDER_STATUSES = [
        OrderStatus::COMPLETED,
        OrderStatus::CANCELLED,
    ];

    private const TERMINAL_SHIPMENT_STATUSES = [
        'delivered',
        'cancelled',
    ];

    public function assertCanLeaveRole(User $user): void
    {
        $shipper = Shipper::query()
            ->where('user_id', $user->id)
            ->lockForUpdate()
            ->first();

        if (! $shipper) {
            return;
        }

        $activeOrders = Order::query()
            ->where('shipper_id', $shipper->id)
            ->whereNotIn('status', self::TERMINAL_ORDER_STATUSES)
            ->count();

        $activeShipments = Schema::hasTable('shipments')
            ? DB::table('shipments')
                ->where('shipper_id', $shipper->id)
                ->whereNotIn('status', self::TERMINAL_SHIPMENT_STATUSES)
                ->count()
            : 0;

        $activeTrips = Schema::hasTable('delivery_bundle_trips')
            ? DB::table('delivery_bundle_trips')
                ->where('shipper_id', $shipper->id)
                ->where('status', 'active')
                ->count()
            : 0;

        $busy = $shipper->status === 'busy';

        if (! $busy && $activeOrders === 0 && $activeShipments === 0 && $activeTrips === 0) {
            return;
        }

        $reasons = [];
        if ($busy) {
            $reasons[] = 'Shipper đang ở trạng thái bận';
        }
        if ($activeOrders > 0) {
            $reasons[] = $activeOrders.' đơn chưa hoàn tất';
        }
        if ($activeShipments > 0) {
            $reasons[] = $activeShipments.' shipment chưa hoàn tất';
        }
        if ($activeTrips > 0) {
            $reasons[] = $activeTrips.' chuyến ghép đang hoạt động';
        }
        throw new RuntimeException(
            'Không thể thay đổi vai trò vì Shipper vẫn còn nhiệm vụ giao hàng chưa hoàn tất: '
            .implode(', ', $reasons).'.'
        );
    }
}
