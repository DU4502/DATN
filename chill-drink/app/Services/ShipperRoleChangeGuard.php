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

    public function __construct(private readonly ShipperCodService $cod)
    {
    }

    public function assertCanLeaveRole(User $user): void
    {
        $shipper = Shipper::query()
            ->where('user_id', $user->id)
            ->lockForUpdate()
            ->first();

        if (! $shipper) {
            return;
        }

        $this->cod->syncHistoricalReceivablesForShipper($shipper);

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

        $pendingCod = $this->pendingCodCount($shipper);
        $busy = $shipper->status === 'busy';

        if (! $busy && $activeOrders === 0 && $activeShipments === 0 && $activeTrips === 0 && $pendingCod === 0) {
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
        if ($pendingCod > 0) {
            $reasons[] = $pendingCod.' khoản COD chưa đối soát';
        }

        throw new RuntimeException(
            'Không thể thay đổi vai trò vì Shipper vẫn còn nhiệm vụ giao hàng hoặc nghĩa vụ COD chưa hoàn tất: '
            .implode(', ', $reasons).'.'
        );
    }

    private function pendingCodCount(Shipper $shipper): int
    {
        if (! Schema::hasTable('shipper_cod_receivables')) {
            return 0;
        }

        $query = DB::table('shipper_cod_receivables as receivables')
            ->where('receivables.shipper_id', $shipper->id)
            ->where('receivables.amount', '>', 0);

        if (! Schema::hasTable('shipper_cod_settlements')) {
            return $query->whereNull('receivables.settlement_id')->count();
        }

        return $query
            ->leftJoin('shipper_cod_settlements as settlements', 'settlements.id', '=', 'receivables.settlement_id')
            ->where(function ($builder) {
                $builder->whereNull('receivables.settlement_id')
                    ->orWhereNull('settlements.confirmed_at');
            })
            ->count();
    }
}
