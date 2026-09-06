<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Shipper;
use App\Models\User;
use App\Notifications\ShipperOrderAssignedNotification;
use App\Support\OrderStatus;
use App\Support\ScheduledDelivery;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ShipperDispatchService
{
    private const ACTIVE_ORDER_STATUSES = [
        OrderStatus::CONFIRMED,
        OrderStatus::PREPARING,
        OrderStatus::READY_FOR_DELIVERY,
        OrderStatus::SHIPPER_PICKED_UP,
        OrderStatus::DELIVERING,
    ];

    private const DISPATCHABLE_UNASSIGNED_STATUSES = [
        OrderStatus::READY_FOR_DELIVERY,
    ];

    public function __construct(
        private readonly DeliveryRoutingService $routing,
        private readonly ShipperBundleService $bundles,
        private readonly ShipperDispatchScoringService $scoring,
    ) {
    }

    /**
     * P9 vẫn giữ ưu tiên nghiệp vụ:
     * AVAILABLE -> RETURNING -> BUSY có thể ghép.
     * Khác P8 ở chỗ mỗi tầng không còn chọn bằng rule đơn lẻ mà xếp hạng bằng score giải thích được.
     */
    public function dispatchConfirmedOrder(Order $order, ?string $batchUuid = null): array
    {
        if (($order->fulfillment_type ?? 'delivery') !== 'delivery') {
            return [
                'status' => 'skipped',
                'shipper' => null,
                'message' => 'Đơn tự lấy không cần điều phối shipper.',
            ];
        }

        $batchUuid ??= $this->scoring->beginBatch();

        try {
            $available = $this->assignAvailable($order, $batchUuid);
            if (($available['status'] ?? null) !== 'waiting') {
                return $available;
            }

            $returning = $this->assignReturning($order, $batchUuid);
            if (($returning['status'] ?? null) === 'assigned') {
                return $returning;
            }

            $bundle = $this->assignBundle($order, $batchUuid);
            if (($bundle['status'] ?? null) === 'assigned') {
                return $bundle;
            }

            return [
                'status' => 'waiting',
                'shipper' => null,
                'dispatch_batch' => $batchUuid,
                'message' => 'Chưa có AVAILABLE/RETURNING phù hợp và chưa có chuyến BUSY đủ điều kiện ghép. Đơn tiếp tục nằm trong hàng chờ điều phối tự động.',
            ];
        } catch (\Throwable $exception) {
            Log::error('Không thể tự động điều phối shipper.', [
                'order_id' => $order->id,
                'batch_uuid' => $batchUuid,
                'message' => $exception->getMessage(),
            ]);

            return [
                'status' => 'error',
                'shipper' => null,
                'dispatch_batch' => $batchUuid,
                'message' => 'Đơn vẫn được giữ nguyên nhưng engine điều phối chưa chọn được shipper.',
            ];
        }
    }

    /**
     * Khi có tài nguyên mới (shipper Online, vừa về chi nhánh, vừa giao xong),
     * tự quét các đơn chưa có shipper. Đây là phần còn thiếu ở P1-P8: đơn từng "waiting"
     * không còn phải chờ admin bấm lại.
     */
    public function dispatchWaitingOrders(?int $limit = null): array
    {
        $scanLimit = max(1, (int) config('shipper_dispatch.waiting_orders.scan_limit', 20));
        $dispatchLimit = max(1, $limit ?? (int) config('shipper_dispatch.waiting_orders.dispatch_limit_per_trigger', 5));

        $orders = Order::query()
            ->with(['branch', 'address', 'orderItems'])
            ->whereNull('shipper_id')
            ->where(function ($query) {
                $query->whereNull('fulfillment_type')->orWhere('fulfillment_type', 'delivery');
            })
            ->whereIn('status', self::DISPATCHABLE_UNASSIGNED_STATUSES)
            ->where(function ($query) {
                $dispatchBefore = now()->addMinutes(ScheduledDelivery::DELIVERY_OPERATION_MINUTES);
                $query->where('delivery_type', '!=', 'scheduled')
                    ->orWhereNull('delivery_type')
                    ->orWhere('scheduled_delivery_time', '<=', $dispatchBefore)
                    ->orWhere(function ($legacy) use ($dispatchBefore) {
                        $legacy->whereNull('scheduled_delivery_time')
                            ->where('scheduled_at', '<=', $dispatchBefore);
                    });
            })
            ->oldest('created_at')
            ->limit($scanLimit)
            ->get()
            ->sort(function (Order $a, Order $b) {
                $ua = $this->scoring->orderUrgencyScore($a);
                $ub = $this->scoring->orderUrgencyScore($b);
                if (abs($ua - $ub) > 0.001) {
                    return $ub <=> $ua;
                }

                return ($a->created_at?->getTimestamp() ?? 0) <=> ($b->created_at?->getTimestamp() ?? 0);
            })
            ->take($dispatchLimit)
            ->values();

        $summary = ['scanned' => $orders->count(), 'assigned' => 0, 'waiting' => 0, 'errors' => 0, 'results' => []];
        foreach ($orders as $order) {
            $result = $this->dispatchConfirmedOrder($order);
            $summary['results'][$order->id] = $result['status'] ?? 'unknown';
            if (($result['status'] ?? null) === 'assigned') {
                $summary['assigned']++;
            } elseif (($result['status'] ?? null) === 'waiting') {
                $summary['waiting']++;
            } elseif (($result['status'] ?? null) === 'error') {
                $summary['errors']++;
            }
        }

        return $summary;
    }

    private function assignAvailable(Order $order, string $batchUuid): array
    {
        $snapshot = Order::query()->with('branch')->whereKey($order->id)->first();
        if (! $snapshot) {
            return ['status' => 'skipped', 'shipper' => null, 'message' => 'Đơn không tồn tại.'];
        }
        if ($guard = $this->guardOrderForDispatch($snapshot)) {
            return $guard;
        }

        $busyShipperIds = $this->busyShipperIds();
        $candidateQuery = Shipper::query()
            ->with(['user.branch', 'stationBranch', 'returningBranch'])
            ->where('status', 'online')
            ->when(Schema::hasColumn('shippers', 'last_active_at'), fn ($query) => $query->where('last_active_at', '>=', $this->activePresenceCutoff()))
            ->whereHas('user', fn ($query) => $query
                ->where('role_id', User::SHIPPER_ROLE_ID)
                ->where('is_active', 1)
                ->where('branch_id', $snapshot->branch_id));

        if (Schema::hasColumn('shippers', 'returning_to_branch_id')) {
            $candidateQuery->whereNull('returning_to_branch_id');
        }
        if ($busyShipperIds->isNotEmpty()) {
            $candidateQuery->whereNotIn('id', $busyShipperIds->all());
        }

        $context = $this->scoring->context($snapshot);
        $ranked = $this->scoring->rankAvailable($snapshot, $candidateQuery->get(), $context);
        $this->scoring->logRankedRows($batchUuid, $snapshot, 'available', $ranked);

        foreach ($ranked as $row) {
            $result = DB::transaction(function () use ($order, $row) {
                $lockedOrder = Order::query()->with('branch')->whereKey($order->id)->lockForUpdate()->firstOrFail();
                if ($guard = $this->guardOrderForDispatch($lockedOrder)) {
                    return $guard;
                }

                /** @var Shipper|null $shipper */
                $shipper = Shipper::query()->with(['user.branch', 'stationBranch', 'returningBranch'])
                    ->whereKey($row['shipper']->id)->lockForUpdate()->first();
                if (! $this->isEligibleLockedShipper($shipper) || $shipper->status !== 'online' || ! $this->hasFreshPresence($shipper)) {
                    return ['status' => 'candidate_changed'];
                }
                if ((int) ($shipper->user?->branch_id ?? 0) !== (int) $lockedOrder->branch_id) {
                    return ['status' => 'candidate_changed'];
                }
                if (Schema::hasColumn('shippers', 'returning_to_branch_id') && $shipper->returning_to_branch_id) {
                    return ['status' => 'candidate_changed'];
                }
                if ($this->hasActiveOrder((int) $shipper->id)) {
                    return ['status' => 'candidate_changed'];
                }

                $description = 'P9 auto-dispatch AVAILABLE · score '.number_format((float) $row['score'], 1, '.', '')
                    .' · ETA tới quán '.number_format(((float) $row['pickup_eta_s']) / 60, 1, '.', '').' phút.';
                $this->assignStandard($lockedOrder, $shipper, 'assigned', $description);

                return [
                    'status' => 'assigned',
                    'dispatch_mode' => 'available',
                    'shipper' => $shipper->loadMissing('user'),
                    'dispatch_score' => $row['score'],
                    'dispatch_features' => $this->publicFeatures($row),
                    'message' => 'Đã tự động gán shipper AVAILABLE có điểm điều phối tốt nhất.',
                ];
            }, 3);

            if (($result['status'] ?? null) === 'assigned') {
                $this->scoring->markSelected($batchUuid, (int) $order->id, (int) $result['shipper']->id, 'available');
                $result['dispatch_batch'] = $batchUuid;
                return $result;
            }
            if (in_array($result['status'] ?? null, ['already_assigned', 'skipped'], true)) {
                return $result;
            }
        }

        return ['status' => 'waiting', 'shipper' => null, 'message' => 'Không có shipper AVAILABLE thuộc đúng chi nhánh của đơn và đạt điều kiện ETA.'];
    }

    private function assignReturning(Order $order, string $batchUuid): array
    {
        if (! Schema::hasColumn('shippers', 'returning_to_branch_id')) {
            return ['status' => 'waiting', 'shipper' => null, 'message' => 'Chưa có dữ liệu RETURNING.'];
        }

        $snapshot = Order::query()->with('branch')->whereKey($order->id)->first();
        if (! $snapshot || ($guard = $this->guardOrderForDispatch($snapshot))) {
            return $guard ?? ['status' => 'skipped', 'shipper' => null, 'message' => 'Đơn không tồn tại.'];
        }

        $busyIds = $this->busyShipperIds();
        $query = Shipper::query()
            ->with(['user.branch', 'stationBranch', 'returningBranch'])
            ->where('status', 'online')
            ->when(Schema::hasColumn('shippers', 'last_active_at'), fn ($q) => $q->where('last_active_at', '>=', $this->activePresenceCutoff()))
            ->where('returning_to_branch_id', $snapshot->branch_id)
            ->whereNotNull('current_latitude')
            ->whereNotNull('current_longitude')
            ->whereHas('user', fn ($q) => $q
                ->where('role_id', User::SHIPPER_ROLE_ID)
                ->where('is_active', 1)
                ->where('branch_id', $snapshot->branch_id));
        if ($busyIds->isNotEmpty()) {
            $query->whereNotIn('id', $busyIds->all());
        }

        $context = $this->scoring->context($snapshot);
        $ranked = $this->scoring->rankReturning($snapshot, $query->get(), $context);
        $this->scoring->logRankedRows($batchUuid, $snapshot, 'returning', $ranked);

        foreach ($ranked as $row) {
            $result = DB::transaction(function () use ($order, $row) {
                $lockedOrder = Order::query()->with('branch')->whereKey($order->id)->lockForUpdate()->firstOrFail();
                if ($guard = $this->guardOrderForDispatch($lockedOrder)) {
                    return $guard;
                }

                /** @var Shipper|null $shipper */
                $shipper = Shipper::query()->with('user')->whereKey($row['shipper']->id)->lockForUpdate()->first();
                if (! $this->isEligibleLockedShipper($shipper) || $shipper->status !== 'online' || ! $this->hasFreshPresence($shipper) || ! $shipper->returning_to_branch_id) {
                    return ['status' => 'candidate_changed'];
                }
                if ((int) ($shipper->user?->branch_id ?? 0) !== (int) $lockedOrder->branch_id
                    || (int) $shipper->returning_to_branch_id !== (int) $lockedOrder->branch_id) {
                    return ['status' => 'candidate_changed'];
                }
                if ($this->hasActiveOrder((int) $shipper->id)) {
                    return ['status' => 'candidate_changed'];
                }

                $description = 'P9 chuyển hướng RETURNING · score '.number_format((float) $row['score'], 1, '.', '')
                    .' · ETA tới quán '.number_format(((float) $row['pickup_eta_s']) / 60, 1, '.', '')
                    .' phút · lệch tuyến quay về ~'.number_format((float) ($row['return_detour_min'] ?? 0), 1, '.', '').' phút.';
                $this->assignStandard($lockedOrder, $shipper, 'assigned_returning', $description);

                return [
                    'status' => 'assigned',
                    'dispatch_mode' => 'returning',
                    'shipper' => $shipper->loadMissing('user'),
                    'dispatch_score' => $row['score'],
                    'dispatch_features' => $this->publicFeatures($row),
                    'message' => 'Đã chuyển hướng shipper RETURNING có score tốt nhất.',
                ];
            }, 3);

            if (($result['status'] ?? null) === 'assigned') {
                $this->scoring->markSelected($batchUuid, (int) $order->id, (int) $result['shipper']->id, 'returning');
                $result['dispatch_batch'] = $batchUuid;
                return $result;
            }
            if (in_array($result['status'] ?? null, ['already_assigned', 'skipped'], true)) {
                return $result;
            }
        }

        return ['status' => 'waiting', 'shipper' => null, 'message' => 'Không có shipper RETURNING của chính chi nhánh đủ tốt theo ETA/độ lệch tuyến.'];
    }

    private function assignBundle(Order $order, string $batchUuid): array
    {
        $evaluation = $this->bundles->findBestCandidate($order);
        if (! $evaluation) {
            return ['status' => 'waiting', 'shipper' => null, 'message' => 'Không có chuyến BUSY đủ điều kiện ghép theo SLA/tải/tuyến pickup-trước-delivery.'];
        }

        $this->scoring->logRankedRows($batchUuid, $order, 'bundle', collect([$evaluation]));

        $result = DB::transaction(function () use ($order, $evaluation) {
            $lockedOrder = Order::query()
                ->with(['branch', 'address', 'orderItems'])
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();
            if ($guard = $this->guardOrderForDispatch($lockedOrder)) {
                return $guard;
            }

            /** @var Shipper|null $shipper */
            $shipper = Shipper::query()->with('user')->whereKey($evaluation['shipper']->id)->lockForUpdate()->first();
            if (! $this->isEligibleLockedShipper($shipper) || $shipper->status !== 'busy') {
                return ['status' => 'waiting', 'shipper' => null, 'message' => 'Chuyến ghép vừa thay đổi trạng thái.'];
            }

            $maxOrders = max(1, (int) config('shipper_dispatch.bundle.max_orders_per_trip', ShipperBundleService::MAX_ORDERS_PER_TRIP));
            $currentOrders = Order::query()
                ->with(['branch', 'address', 'orderItems'])
                ->where('shipper_id', $shipper->id)
                ->whereIn('status', self::ACTIVE_ORDER_STATUSES)
                ->oldest('created_at')
                ->lockForUpdate()
                ->get();

            if ($currentOrders->count() < 1 || $currentOrders->count() >= $maxOrders) {
                return ['status' => 'waiting', 'shipper' => null, 'message' => 'Shipper không còn đủ chỗ để ghép thêm đơn.'];
            }

            if (! $this->bundles->canAcceptAdditionalBundle($shipper, $currentOrders)) {
                return [
                    'status' => 'waiting',
                    'shipper' => null,
                    'message' => 'Shipper đã rời quán quá 200m sau khi lấy hàng; cần giao/thu tiền đơn hiện tại trước khi ghép thêm.',
                ];
            }

            // Candidate được tính từ snapshot trước transaction; khóa lại và xác nhận
            // đúng tập đơn hoạt động để tránh gắn nhầm khi hai dispatch chạy đồng thời.
            $expectedIds = collect($evaluation['current_orders'] ?? [])
                ->map(fn ($item) => $item instanceof Order ? (int) $item->id : (int) ($item['id'] ?? 0))
                ->filter()
                ->sort()
                ->values();
            $actualIds = $currentOrders->pluck('id')->map(fn ($id) => (int) $id)->sort()->values();
            if ($expectedIds->isNotEmpty() && $expectedIds->all() !== $actualIds->all()) {
                return ['status' => 'waiting', 'shipper' => null, 'message' => 'Tập đơn trên chuyến vừa thay đổi, hệ thống sẽ tính lại ở lượt sau.'];
            }

            $activeTrip = $this->bundles->activeTripForShipper($shipper);
            if ($currentOrders->count() > 1 && ! $activeTrip) {
                return ['status' => 'waiting', 'shipper' => null, 'message' => 'Dữ liệu chuyến ghép hiện tại không còn đồng bộ.'];
            }
            if ($activeTrip) {
                $tripIds = collect($activeTrip['order_ids'] ?? [])->map(fn ($id) => (int) $id)->sort()->values();
                if ($tripIds->all() !== $actualIds->all()) {
                    return ['status' => 'waiting', 'shipper' => null, 'message' => 'Chuyến ghép vừa thay đổi order, hệ thống sẽ tính lại ở lượt sau.'];
                }
            }

            // Khác luồng cũ: đơn ghép thứ 2/3 có thể thuộc chi nhánh khác.
            // Đơn đầu vẫn theo home branch; phần ghép được quyết định bằng vị trí và route thực tế.
            foreach ($currentOrders as $currentOrder) {
                if ((int) $currentOrder->shipper_id !== (int) $shipper->id
                    || ! in_array(OrderStatus::normalize((string) $currentOrder->status), self::ACTIVE_ORDER_STATUSES, true)) {
                    return ['status' => 'waiting', 'shipper' => null, 'message' => 'Một đơn đang chạy không còn phù hợp để ghép.'];
                }
            }

            $lockedOrder->forceFill(['shipper_id' => $shipper->id])->save();
            $shipmentId = $this->createShipment($lockedOrder->id, $shipper->id);
            $tripId = $this->bundles->attachOrderToTrip($shipper, $currentOrders, $lockedOrder, $evaluation);
            if (! $tripId) {
                $lockedOrder->forceFill(['shipper_id' => null])->save();
                if ($shipmentId && Schema::hasTable('shipments')) {
                    DB::table('shipments')->where('id', $shipmentId)->delete();
                }
                return ['status' => 'waiting', 'shipper' => null, 'message' => 'Không thể cập nhật chuyến ghép, đơn được trả lại hàng chờ.'];
            }

            $savedKm = max(0, ((float) ($evaluation['saved_distance_m'] ?? 0)) / 1000);
            $delayMin = max(0, ((float) ($evaluation['existing_customer_delay_s'] ?? 0)) / 60);
            $description = 'Ghép chuyến '.($currentOrders->count() + 1).' đơn'
                .' · pickup tất cả quán gần nhất trước rồi mới giao khách gần nhất'
                .' · score '.number_format((float) ($evaluation['score'] ?? 0), 1, '.', '')
                .' · tổng tải '.(int) ($evaluation['total_cups'] ?? 0).' cốc'
                .' · tiết kiệm ~'.number_format($savedKm, 1, '.', '').' km'
                .' · tăng ETA khách cũ tối đa ~'.number_format($delayMin, 1, '.', '').' phút.';

            $this->addShipmentHistory($shipmentId, 'bundle_assigned', $description);
            $this->addShipmentHistory($shipmentId, 'assigned', $description);
            $this->addShipmentHistory($shipmentId, 'accepted', 'Hệ thống tự thêm đơn ghép vào chuyến; shipper không cần xác nhận thủ công.');
            $this->notifyAssignedOrder($lockedOrder, $shipper, 'bundle');

            foreach ($currentOrders as $currentOrder) {
                $currentShipmentId = DB::table('shipments')
                    ->where('order_id', $currentOrder->id)
                    ->where('shipper_id', $shipper->id)
                    ->latest('id')
                    ->value('id');
                $this->addShipmentHistory(
                    $currentShipmentId ? (int) $currentShipmentId : null,
                    'bundle_attached',
                    'Hệ thống ghép thêm đơn '.$lockedOrder->displayCode().' vào chuyến #'.$tripId
                        .'. Thứ tự mới: lấy hết các quán theo điểm gần nhất, sau đó giao hết khách theo điểm gần nhất.'
                );
                $this->addShipmentHistory(
                    $currentShipmentId ? (int) $currentShipmentId : null,
                    'accepted',
                    'Chuyến đang chạy được cập nhật tự động; shipper không cần xác nhận lại.'
                );
            }

            return [
                'status' => 'assigned',
                'dispatch_mode' => 'bundle',
                'shipper' => $shipper->loadMissing('user'),
                'trip_id' => $tripId,
                'dispatch_score' => $evaluation['score'] ?? null,
                'dispatch_features' => $this->publicFeatures($evaluation),
                'message' => 'Đã ghép đơn vào chuyến '.($currentOrders->count() + 1).' đơn theo tuyến lấy hết quán trước rồi mới giao khách.',
            ];
        }, 3);

        if (($result['status'] ?? null) === 'assigned') {
            $this->scoring->markSelected($batchUuid, (int) $order->id, (int) $result['shipper']->id, 'bundle');
            $result['dispatch_batch'] = $batchUuid;
        }

        return $result;
    }

    private function guardOrderForDispatch(Order $order): ?array
    {
        $status = OrderStatus::normalize((string) $order->status);
        if (! in_array($status, self::DISPATCHABLE_UNASSIGNED_STATUSES, true)) {
            return ['status' => 'skipped', 'shipper' => null, 'message' => 'Đơn không còn ở giai đoạn có thể điều phối shipper.'];
        }
        if ($order->shipper_id) {
            return [
                'status' => 'already_assigned',
                'shipper' => Shipper::with('user')->find($order->shipper_id),
                'message' => 'Đơn đã được gán shipper trước đó.',
            ];
        }


        if ($order->delivery_type === 'scheduled') {
            $scheduledAt = $order->scheduled_delivery_time ?? $order->scheduled_at;
            if (! $scheduledAt || $scheduledAt->gt(now()->addMinutes(ScheduledDelivery::DELIVERY_OPERATION_MINUTES))) {
                return [
                    'status' => 'deferred',
                    'shipper' => null,
                    'message' => 'Đơn giao sau chưa đến thời điểm điều phối shipper.',
                ];
            }
        }

        return null;
    }

    private function isEligibleLockedShipper(?Shipper $shipper): bool
    {
        return $shipper !== null
            && $shipper->user?->isShipper()
            && (bool) $shipper->user->is_active;
    }

    private function hasFreshPresence(?Shipper $shipper): bool
    {
        if (! $shipper || ! Schema::hasColumn('shippers', 'last_active_at')) {
            return true;
        }

        return $shipper->last_active_at && $shipper->last_active_at->gte($this->activePresenceCutoff());
    }

    private function activePresenceCutoff()
    {
        $ttlMinutes = max(1, (int) config('shipper_dispatch.presence.active_ttl_minutes', 3));

        return now()->subMinutes($ttlMinutes);
    }

    private function assignStandard(Order $order, Shipper $shipper, string $historyStatus, string $description): void
    {
        $order->forceFill(['shipper_id' => $shipper->id])->save();

        $values = ['status' => 'busy'];
        if (Schema::hasColumn('shippers', 'station_branch_id')) {
            $values['station_branch_id'] = null;
        }
        if (Schema::hasColumn('shippers', 'returning_to_branch_id')) {
            $values['returning_to_branch_id'] = null;
            $values['returning_started_at'] = null;
        }
        $shipper->forceFill($values)->save();

        $shipmentId = $this->createShipment($order->id, $shipper->id);
        $this->addShipmentHistory($shipmentId, $historyStatus, $description);
        if ($historyStatus !== 'assigned') {
            $this->addShipmentHistory($shipmentId, 'assigned', $description);
        }
        $this->addShipmentHistory($shipmentId, 'accepted', 'Hệ thống tự giao đơn vào chuyến; shipper không cần bấm Nhận đơn.');
        $this->notifyAssignedOrder($order, $shipper, $historyStatus === 'assigned_returning' ? 'returning' : 'assigned');
    }

    private function busyShipperIds(): Collection
    {
        return Order::query()
            ->whereNotNull('shipper_id')
            ->whereIn('status', self::ACTIVE_ORDER_STATUSES)
            ->pluck('shipper_id')
            ->filter()
            ->unique()
            ->values();
    }

    private function hasActiveOrder(int $shipperId): bool
    {
        return Order::query()->where('shipper_id', $shipperId)->whereIn('status', self::ACTIVE_ORDER_STATUSES)->exists();
    }

    private function createShipment(int $orderId, int $shipperId): ?int
    {
        if (! Schema::hasTable('shipments')) {
            return null;
        }

        return (int) DB::table('shipments')->insertGetId([
            'order_id' => $orderId,
            'shipper_id' => $shipperId,
            'status' => 'accepted',
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function addShipmentHistory(?int $shipmentId, string $status, ?string $description = null): void
    {
        if (! $shipmentId || ! Schema::hasTable('shipment_history')) {
            return;
        }

        DB::table('shipment_history')->insert([
            'shipment_id' => $shipmentId,
            'status' => $status,
            'description' => $description,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function notifyAssignedOrder(Order $order, Shipper $shipper, string $mode): void
    {
        $user = $shipper->user;
        if (! $user) {
            return;
        }

        $user->notify(new ShipperOrderAssignedNotification($order->fresh() ?: $order, $mode));
    }

    private function publicFeatures(array $row): array
    {
        unset($row['shipper'], $row['current_order'], $row['stops'], $row['score_breakdown']);

        return $row;
    }
}
