<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Shipper;
use App\Models\User;
use App\Models\Voucher;
use App\Support\OrderStatus;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class OrderCancellationService
{
    public function __construct(
        private readonly ShipperBundleService $bundles,
        private readonly ShipperReturnService $returns,
    ) {
    }

    /**
     * Hủy đơn tập trung để không để lại shipper BUSY/assignment/shipment mồ côi.
     *
     * $force chỉ dành cho cơ chế hệ thống/Super Admin. UI thông thường vẫn phải
     * tuân theo state machine của OrderStatus.
     *
     * @return array{order:Order,old_status:string,shipper:?Shipper,shipper_has_other_order:bool,return_plan:?array}
     */
    public function cancel(
        Order $order,
        string $reason,
        ?User $actor = null,
        bool $force = false,
    ): array {
        $result = $this->cancelWithGuard($order, $reason, $actor, $force);

        if ($result === null) {
            throw new RuntimeException('Đơn hàng không còn thỏa điều kiện để hủy.');
        }

        return $result;
    }

    /**
     * Hủy có điều kiện nguyên tử cho scheduler timeout.
     *
     * Re-check được thực hiện SAU lockForUpdate nên nếu trạng thái vừa thay đổi
     * đúng lúc scheduler chạy thì đơn sẽ được bỏ qua, không bị hủy nhầm.
     *
     * $clock:
     * - created_at: dùng cho luật Chờ xác nhận quá 30 phút.
     * - status_changed_at: dùng mốc đổi trạng thái, fallback created_at.
     * - status_or_schedule: như trên nhưng với đơn hẹn giờ sẽ không bắt đầu đếm
     *   trước scheduled_at/scheduled_delivery_time.
     *
     * @return array{order:Order,old_status:string,shipper:?Shipper,shipper_has_other_order:bool,return_plan:?array}|null
     */
    public function cancelIfStale(
        Order $order,
        string $reason,
        string $expectedStatus,
        CarbonInterface $staleThreshold,
        string $clock = 'status_changed_at',
    ): ?array {
        $expectedStatus = OrderStatus::normalize($expectedStatus);

        $guard = static function (Order $locked, string $oldStatus) use ($expectedStatus, $staleThreshold, $clock): bool {
            if ($oldStatus !== $expectedStatus) {
                return false;
            }

            if ($clock === 'created_at') {
                $timestamp = $locked->created_at;
            } else {
                $timestamp = $locked->status_changed_at ?? $locked->created_at;

                if ($clock === 'status_or_schedule') {
                    foreach ([$locked->scheduled_at, $locked->scheduled_delivery_time] as $scheduledAt) {
                        if ($scheduledAt !== null && ($timestamp === null || $scheduledAt->gt($timestamp))) {
                            $timestamp = $scheduledAt;
                        }
                    }
                }
            }

            return $timestamp !== null && $timestamp->lte($staleThreshold);
        };

        return $this->cancelWithGuard($order, $reason, null, true, $guard);
    }

    /**
     * @param Closure(Order,string):bool|null $guard
     * @return array{order:Order,old_status:string,shipper:?Shipper,shipper_has_other_order:bool,return_plan:?array}|null
     */
    private function cancelWithGuard(
        Order $order,
        string $reason,
        ?User $actor,
        bool $force,
        ?Closure $guard = null,
    ): ?array {
        $reason = trim($reason);
        if ($reason === '') {
            throw new RuntimeException('Vui lòng nhập lý do hủy đơn hàng.');
        }

        $result = DB::transaction(function () use ($order, $reason, $actor, $force, $guard) {
            /** @var Order $locked */
            $locked = Order::query()
                ->with(['orderItems.product', 'branch'])
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            $oldStatus = OrderStatus::normalize((string) $locked->status);

            if ($guard !== null && ! $guard($locked, $oldStatus)) {
                return null;
            }

            if ($oldStatus === OrderStatus::CANCELLED) {
                return [
                    'order' => $locked,
                    'old_status' => $oldStatus,
                    'shipper' => null,
                    'shipper_has_other_order' => false,
                    'should_return' => false,
                ];
            }

            if (! $force && ! OrderStatus::canTransition($oldStatus, OrderStatus::CANCELLED, $locked->fulfillment_type ?? 'delivery')) {
                throw new RuntimeException('Đơn ở trạng thái hiện tại không còn được phép hủy.');
            }

            $shipper = null;
            if ($locked->shipper_id) {
                $shipper = Shipper::query()->whereKey($locked->shipper_id)->lockForUpdate()->first();
            }

            // Tách chuyến ghép trước khi hủy một đơn. Đơn còn lại vẫn giữ shipper hiện tại.
            if ($shipper) {
                $this->bundles->dissolveTripForOrder(
                    $locked,
                    $force
                        ? 'Tách chuyến ghép vì đơn bị hệ thống/Super Admin hủy.'
                        : 'Tách chuyến ghép vì một đơn đã bị quán/admin hủy.'
                );
            }

            $locked->forceFill([
                'status' => OrderStatus::CANCELLED,
                'cancellation_reason' => $reason,
                'status_changed_at' => now(),
                'status_changed_by' => $actor?->id,
                'shipper_id' => null,
            ])->save();

            // Đơn bị hủy thì trả lại lượt sử dụng voucher, bất kể hủy thủ công hay timeout.
            if ($locked->coupon_id) {
                Voucher::query()
                    ->whereKey($locked->coupon_id)
                    ->where('used_count', '>', 0)
                    ->decrement('used_count');
            }

            if ($oldStatus === OrderStatus::COMPLETED) {
                $locked->revokeLoyaltyPoints();
            }

            // Đóng mọi shipment còn treo của order và ghi lịch sử để audit.
            if (Schema::hasTable('shipments')) {
                $shipmentIds = DB::table('shipments')
                    ->where('order_id', $locked->id)
                    ->whereNotIn('status', ['cancelled', 'delivered', 'completed'])
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                if ($shipmentIds !== []) {
                    DB::table('shipments')->whereIn('id', $shipmentIds)->update([
                        'status' => 'cancelled',
                        'note' => ($force ? 'Đơn bị hủy bởi hệ thống/Super Admin: ' : 'Đơn bị hủy bởi cửa hàng/quản lý: ').$reason,
                        'updated_at' => now(),
                    ]);

                    if (Schema::hasTable('shipment_history')) {
                        $rows = array_map(fn (int $shipmentId) => [
                            'shipment_id' => $shipmentId,
                            'status' => $force ? 'order_auto_cancelled' : 'order_cancelled',
                            'description' => 'Đơn bị hủy. Assignment của shipper đã được giải phóng. Lý do: '.$reason,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ], $shipmentIds);
                        DB::table('shipment_history')->insert($rows);
                    }
                }
            }

            $hasOther = false;
            $shouldReturn = false;
            if ($shipper) {
                $hasOther = $this->bundles->hasOtherActiveOrder($shipper, (int) $locked->id);

                if ($hasOther) {
                    // Vẫn còn đơn khác (ví dụ đơn còn lại của chuyến ghép): không được nhả BUSY.
                    $shipper->forceFill(['status' => 'busy'])->save();
                } elseif ($shipper->status !== 'offline') {
                    $shouldReturn = is_numeric($shipper->current_latitude)
                        && is_numeric($shipper->current_longitude);

                    if (! $shouldReturn) {
                        $values = ['status' => 'online'];
                        if (Schema::hasColumn('shippers', 'station_branch_id')) {
                            $values['station_branch_id'] = $shipper->homeBranchId() ?: $locked->branch_id;
                        }
                        if (Schema::hasColumn('shippers', 'returning_to_branch_id')) {
                            $values['returning_to_branch_id'] = null;
                            $values['returning_started_at'] = null;
                        }
                        $shipper->forceFill($values)->save();
                    }
                }
            }

            return [
                'order' => $locked->fresh(),
                'old_status' => $oldStatus,
                'shipper' => $shipper?->fresh(),
                'shipper_has_other_order' => $hasOther,
                'should_return' => $shouldReturn,
            ];
        }, 3);

        if ($result === null) {
            return null;
        }

        $returnPlan = null;
        if (($result['should_return'] ?? false) && ($result['shipper'] ?? null) instanceof Shipper) {
            $returnPlan = $this->returns->startAfterDelivery(
                $result['shipper']->fresh(),
                $result['order']->fresh(['branch'])
            );
        }

        $result['return_plan'] = $returnPlan;
        unset($result['should_return']);

        // Hủy đơn có thể vừa giải phóng một shipper. Kích hoạt lại hàng chờ P9 ngay
        // thay vì bắt đơn khác đợi scheduler phút kế tiếp.
        if (($result['shipper'] ?? null) instanceof Shipper && ! ($result['shipper_has_other_order'] ?? false)) {
            app(ShipperDispatchService::class)->dispatchWaitingOrders();
        }

        return $result;
    }
}
