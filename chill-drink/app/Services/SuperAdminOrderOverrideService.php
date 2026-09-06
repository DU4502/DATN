<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Shipper;
use App\Models\User;
use App\Support\OrderStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Cơ chế thực thi trạng thái dành RIÊNG cho Super Admin.
 *
 * Super Admin được vượt giới hạn vai trò/chi nhánh để thực hiện cả bước của
 * Admin/Staff/Shipper, nhưng trạng thái đầu vào đã bị giới hạn theo đúng bước
 * kế tiếp của state machine. Service này chịu trách nhiệm đồng bộ assignment,
 * shipment, bundle và returning sau mỗi thao tác đặc quyền đó.
 */
class SuperAdminOrderOverrideService
{
    private const ACTIVE_DELIVERY_STATUSES = [
        OrderStatus::CONFIRMED,
        OrderStatus::PREPARING,
        OrderStatus::READY_FOR_DELIVERY,
        OrderStatus::SHIPPER_PICKED_UP,
        OrderStatus::DELIVERING,
    ];

    private const PRE_DISPATCH_STATUSES = [
        OrderStatus::PENDING,
    ];

    public function __construct(
        private readonly OrderCancellationService $cancellations,
        private readonly ShipperBundleService $bundles,
        private readonly ShipperReturnService $returns,
        private readonly ShipperDispatchService $dispatch,
    ) {
    }

    /**
     * @return array{order:Order,old_status:string,new_status:string,dispatch:?array,warning:?string}
     */
    public function override(
        Order $order,
        string $newStatus,
        User $actor,
        ?string $cancellationReason = null,
    ): array {
        if (! $actor->isSuperAdmin()) {
            throw new RuntimeException('Chỉ Super Admin được dùng cơ chế override trạng thái đơn.');
        }

        $newStatus = OrderStatus::normalize($newStatus);
        $fulfillmentType = $order->fulfillment_type ?? 'delivery';
        $oldStatus = OrderStatus::normalize((string) $order->status);

        $allowed = $newStatus === OrderStatus::CANCELLED
            ? OrderStatus::canSuperAdminCancelFrom($oldStatus)
            : OrderStatus::canSuperAdminAdvanceTo($oldStatus, $newStatus, $fulfillmentType);

        if (! $allowed) {
            throw new RuntimeException('Super Admin chỉ được thực hiện đúng bước kế tiếp của đơn; không thể nhảy cóc hoặc quay lùi.');
        }

        if ($newStatus === $oldStatus) {
            return [
                'order' => $order->fresh(['branch', 'shipper.user']),
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'dispatch' => null,
                'warning' => null,
            ];
        }

        // Hủy chỉ từ trạng thái mà state machine cho phép. OrderCancellationService chịu trách nhiệm
        // hoàn kho, voucher, shipment, bundle và giải phóng shipper.
        if ($newStatus === OrderStatus::CANCELLED) {
            $reason = trim((string) $cancellationReason);
            if ($reason === '') {
                $reason = 'Super Admin hủy đơn theo luồng vận hành toàn hệ thống.';
            }

            $cancelled = $this->cancellations->cancel($order, $reason, $actor, force: true);

            return [
                'order' => $cancelled['order']->fresh(['branch', 'shipper.user']),
                'old_status' => $oldStatus,
                'new_status' => OrderStatus::CANCELLED,
                'dispatch' => null,
                'warning' => null,
            ];
        }

        $releasedShipper = null;
        $shouldReturnReleasedShipper = false;
        $warning = null;

        $updated = DB::transaction(function () use (
            $order,
            $actor,
            $newStatus,
            $oldStatus,
            $fulfillmentType,
            &$releasedShipper,
            &$shouldReturnReleasedShipper,
            &$warning,
        ) {
            /** @var Order $locked */
            $locked = Order::query()
                ->with(['orderItems.product', 'branch', 'shipper.user'])
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            $currentStatus = OrderStatus::normalize((string) $locked->status);

            $shipper = $locked->shipper;

            // Pending hoặc đơn tự lấy không được giữ assignment giao hàng.
            $mustReleaseShipper = $shipper
                && ($fulfillmentType === 'pickup' || in_array($newStatus, self::PRE_DISPATCH_STATUSES, true));

            if ($mustReleaseShipper) {
                $this->bundles->dissolveTripForOrder(
                    $locked,
                    'Super Admin override trạng thái khiến đơn không còn thuộc chuyến giao hiện tại.'
                );

                $this->markShipmentReleased($locked, $shipper, $actor);
                $locked->shipper_id = null;
                $releasedShipper = $shipper;
            }

            $locked->status = $newStatus;
            $locked->status_changed_at = now();
            $locked->status_changed_by = $actor->id;

            // Chỉ DELIVERED/COMPLETED mới giữ mốc giao; các bước trước chưa có mốc auto-complete.
            if (! in_array($newStatus, [OrderStatus::DELIVERED, OrderStatus::COMPLETED], true)) {
                $locked->delivered_at = null;
            } elseif ($locked->delivered_at === null) {
                $locked->delivered_at = now();
            }

            $locked->save();

            if ($releasedShipper) {
                $shouldReturnReleasedShipper = ! $this->hasOtherActiveOrder($releasedShipper, (int) $locked->id)
                    && $releasedShipper->status !== 'offline';
            } elseif ($shipper) {
                $this->syncAssignedShipper($locked, $shipper, $newStatus, $actor);
            }

            // Giao thành công đồng nghĩa COD đã được thu và doanh thu được ghi nhận ngay.
            if (in_array($newStatus, [OrderStatus::DELIVERED, OrderStatus::COMPLETED], true)
                && strtolower((string) $locked->payment_method) === 'cod') {
                if (strtolower((string) $locked->payment_status) !== 'paid') {
                    $locked->forceFill(['payment_status' => 'paid'])->save();
                }
            }

            if ($newStatus === OrderStatus::DELIVERED) {
                try {
                    app(\App\Support\AddressLearning::class)->markOrderDelivered($locked->fresh());
                } catch (\Throwable $exception) {
                    $warning = 'Đã override trạng thái, nhưng Address Learning không ghi nhận được điểm giao.';
                }
            }

            $this->appendOverrideAudit($locked, $actor, $currentStatus, $newStatus);

            return $locked->fresh(['branch', 'shipper.user']);
        }, 3);

        if ($releasedShipper && $shouldReturnReleasedShipper) {
            $this->returns->startAfterDelivery(
                $releasedShipper->fresh(),
                $updated->fresh(['branch'])
            );
        }

        $dispatchResult = null;
        if (($updated->fulfillment_type ?? 'delivery') === 'delivery'
            && $newStatus === OrderStatus::READY_FOR_DELIVERY
            && ! $updated->shipper_id) {
            $dispatchResult = $this->dispatch->dispatchConfirmedOrder($updated->fresh(['branch']));
            $updated->refresh();
        }

        // Nếu override sang trạng thái kết thúc và shipper không còn đơn active khác,
        // đưa shipper vào luồng RETURNING thay vì để BUSY treo.
        if (in_array($newStatus, [OrderStatus::DELIVERED, OrderStatus::COMPLETED], true)
            && $updated->shipper_id) {
            $shipper = Shipper::query()->find($updated->shipper_id);
            if ($shipper && ! $this->hasOtherActiveOrder($shipper, (int) $updated->id) && $shipper->status !== 'offline') {
                $this->bundles->completeTripIfFinished($shipper);
                $this->returns->startAfterDelivery($shipper->fresh(), $updated->fresh(['branch']));
            }
        }

        if ($newStatus === OrderStatus::COMPLETED && $oldStatus !== OrderStatus::COMPLETED) {
            $updated->awardLoyaltyPoints();
        }

        return [
            'order' => $updated->fresh(['branch', 'shipper.user']),
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'dispatch' => $dispatchResult,
            'warning' => $warning,
        ];
    }

    private function syncAssignedShipper(Order $order, Shipper $shipper, string $newStatus, User $actor): void
    {
        if (in_array($newStatus, self::ACTIVE_DELIVERY_STATUSES, true)) {
            $values = ['status' => 'busy'];
            if (Schema::hasColumn('shippers', 'station_branch_id')) {
                $values['station_branch_id'] = null;
            }
            if (Schema::hasColumn('shippers', 'returning_to_branch_id')) {
                $values['returning_to_branch_id'] = null;
                $values['returning_started_at'] = null;
            }
            $shipper->forceFill($values)->save();
        }

        if (! Schema::hasTable('shipments')) {
            return;
        }

        $shipment = DB::table('shipments')
            ->where('order_id', $order->id)
            ->where('shipper_id', $shipper->id)
            ->latest('id')
            ->first();

        if (! $shipment) {
            $shipmentId = (int) DB::table('shipments')->insertGetId([
                'order_id' => $order->id,
                'shipper_id' => $shipper->id,
                'status' => 'assigned',
                'assigned_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $shipment = DB::table('shipments')->where('id', $shipmentId)->first();
        }

        if (! $shipment) {
            return;
        }

        $historyAccepted = Schema::hasTable('shipment_history')
            && DB::table('shipment_history')->where('shipment_id', $shipment->id)->where('status', 'accepted')->exists();

        $shipmentStatus = match ($newStatus) {
            OrderStatus::CONFIRMED, OrderStatus::PREPARING, OrderStatus::READY_FOR_DELIVERY => $historyAccepted ? 'accepted' : 'assigned',
            OrderStatus::SHIPPER_PICKED_UP => 'picked_up',
            OrderStatus::DELIVERING => 'delivering',
            OrderStatus::DELIVERED => 'delivered',
            OrderStatus::COMPLETED => 'completed',
            default => (string) $shipment->status,
        };

        $values = [
            'status' => $shipmentStatus,
            'updated_at' => now(),
        ];

        if (in_array($newStatus, [OrderStatus::CONFIRMED, OrderStatus::PREPARING, OrderStatus::READY_FOR_DELIVERY], true)) {
            $values['picked_up_at'] = null;
            $values['delivered_at'] = null;
        } elseif (in_array($newStatus, [OrderStatus::SHIPPER_PICKED_UP, OrderStatus::DELIVERING], true)) {
            $values['picked_up_at'] = $shipment->picked_up_at ?: now();
            $values['delivered_at'] = null;
        } elseif (in_array($newStatus, [OrderStatus::DELIVERED, OrderStatus::COMPLETED], true)) {
            $values['picked_up_at'] = $shipment->picked_up_at ?: now();
            $values['delivered_at'] = $shipment->delivered_at ?: now();
        }

        DB::table('shipments')->where('id', $shipment->id)->update($values);

        if (Schema::hasTable('shipment_history')) {
            DB::table('shipment_history')->insert([
                'shipment_id' => $shipment->id,
                'status' => 'super_admin_override',
                'description' => sprintf(
                    'Super Admin %s override đơn sang %s; shipment đồng bộ thành %s.',
                    $actor->name ?: '#'.$actor->id,
                    OrderStatus::label($newStatus),
                    $shipmentStatus,
                ),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function markShipmentReleased(Order $order, Shipper $shipper, User $actor): void
    {
        if (! Schema::hasTable('shipments')) {
            return;
        }

        $shipment = DB::table('shipments')
            ->where('order_id', $order->id)
            ->where('shipper_id', $shipper->id)
            ->latest('id')
            ->first();

        if (! $shipment) {
            return;
        }

        DB::table('shipments')->where('id', $shipment->id)->update([
            'status' => 'released',
            'note' => 'Assignment được giải phóng do Super Admin override trạng thái đơn.',
            'updated_at' => now(),
        ]);

        if (Schema::hasTable('shipment_history')) {
            DB::table('shipment_history')->insert([
                'shipment_id' => $shipment->id,
                'status' => 'super_admin_released',
                'description' => 'Super Admin '.$actor->name.' đưa đơn về trạng thái không giữ shipper; assignment được giải phóng.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function appendOverrideAudit(Order $order, User $actor, string $from, string $to): void
    {
        // shipment_history là audit tốt nhất hiện có cho đơn giao hàng. Nếu chưa có
        // shipment thì status_changed_by/status_changed_at trên orders vẫn là nguồn audit.
        if (! $order->shipper_id || ! Schema::hasTable('shipments') || ! Schema::hasTable('shipment_history')) {
            return;
        }

        $shipmentId = DB::table('shipments')
            ->where('order_id', $order->id)
            ->where('shipper_id', $order->shipper_id)
            ->latest('id')
            ->value('id');

        if (! $shipmentId) {
            return;
        }

        $alreadyLogged = DB::table('shipment_history')
            ->where('shipment_id', $shipmentId)
            ->where('status', 'super_admin_override')
            ->where('created_at', '>=', now()->subSecond())
            ->exists();

        if ($alreadyLogged) {
            return;
        }

        DB::table('shipment_history')->insert([
            'shipment_id' => $shipmentId,
            'status' => 'super_admin_override',
            'description' => sprintf(
                'Super Admin %s override trạng thái đơn: %s → %s.',
                $actor->name ?: '#'.$actor->id,
                OrderStatus::label($from),
                OrderStatus::label($to),
            ),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function hasOtherActiveOrder(Shipper $shipper, int $excludeOrderId): bool
    {
        return Order::query()
            ->where('shipper_id', $shipper->id)
            ->where('id', '!=', $excludeOrderId)
            ->whereIn('status', self::ACTIVE_DELIVERY_STATUSES)
            ->exists();
    }
}
