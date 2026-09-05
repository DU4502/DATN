<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Shipper;
use App\Models\User;
use App\Support\OrderStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ShipperBundleService
{
    public const MAX_ORDERS_PER_TRIP = 3;
    public const MAX_CUPS_PER_TRIP = 20;
    public const FAR_ORDER_KM = 8.0;
    public const MAX_ESTIMATED_TRIP_SECONDS = 75 * 60;
    public const PICKUP_DEPARTURE_LOCK_DISTANCE_M = 200.0;

    private const ACTIVE_ORDER_STATUSES = [
        OrderStatus::CONFIRMED,
        OrderStatus::PREPARING,
        OrderStatus::READY_FOR_DELIVERY,
        OrderStatus::SHIPPER_PICKED_UP,
        OrderStatus::DELIVERING,
    ];

    private const PRE_PICKUP_STATUSES = [
        OrderStatus::CONFIRMED,
        OrderStatus::PREPARING,
        OrderStatus::READY_FOR_DELIVERY,
    ];

    public function __construct(
        private readonly DeliveryRoutingService $routing,
        private readonly ShipperIncidentService $incidents,
        private readonly ShipperDispatchScoringService $scoring,
    ) {
    }

    public function schemaReady(): bool
    {
        return Schema::hasTable('delivery_bundle_trips')
            && Schema::hasTable('delivery_bundle_trip_orders');
    }

    /**
     * Chỉ khóa ghép thêm sau khi shipper đã lấy hàng và thực sự rời quán.
     * Trước thời điểm đó, các đơn đang chờ lấy vẫn có thể ghép theo luật tuyến
     * và giới hạn tải hiện có của chuyến.
     *
     * @param Collection<int,Order> $activeOrders
     */
    public function canAcceptAdditionalBundle(Shipper $shipper, Collection $activeOrders): bool
    {
        foreach ($activeOrders as $activeOrder) {
            $status = OrderStatus::normalize((string) $activeOrder->status);
            if (! in_array($status, [OrderStatus::SHIPPER_PICKED_UP, OrderStatus::DELIVERING], true)) {
                continue;
            }

            $points = $this->orderPointSet($activeOrder);
            if (! $points
                || ! is_numeric($shipper->current_latitude)
                || ! is_numeric($shipper->current_longitude)) {
                // Không đủ GPS để chứng minh shipper còn ở quán thì khóa an toàn.
                return false;
            }

            $distanceM = $this->haversineKm(
                (float) $shipper->current_latitude,
                (float) $shipper->current_longitude,
                (float) $points['branch']['latitude'],
                (float) $points['branch']['longitude'],
            ) * 1000;

            if ($distanceM >= self::PICKUP_DEPARTURE_LOCK_DISTANCE_M) {
                return false;
            }
        }

        return true;
    }

    /**
     * Tìm shipper BUSY đang giữ 1-2 đơn để ghép thêm đơn mới.
     *
     * Quy tắc tuyến mới:
     *  - tối đa 3 đơn / 20 cốc;
     *  - toàn bộ pickup còn lại phải đi trước toàn bộ delivery;
     *  - trong pha pickup: từ vị trí hiện tại chọn quán gần nhất theo ROUTE THẬT,
     *    rồi từ quán đó chọn quán gần tiếp theo;
     *  - sau pickup cuối cùng mới chuyển sang pha giao khách, cũng chọn khách gần nhất
     *    theo route thật theo kiểu greedy.
     *
     * Ví dụ 3 đơn còn đủ pickup: GPS -> Quán 1 -> Quán 2 -> Quán 3
     * -> Khách 1 -> Khách 2 -> Khách 3.
     */
    public function findBestCandidate(Order $newOrder): ?array
    {
        if (! $this->schemaReady()) {
            return null;
        }

        $newOrder->loadMissing(['branch', 'address', 'orderItems']);
        $newPoints = $this->orderPointSet($newOrder);
        if (! $newPoints) {
            return null;
        }

        $maxOrders = max(1, (int) config('shipper_dispatch.bundle.max_orders_per_trip', self::MAX_ORDERS_PER_TRIP));
        $maxCups = max(1, (int) config('shipper_dispatch.bundle.max_cups_per_trip', self::MAX_CUPS_PER_TRIP));
        $newCups = $this->cupCount($newOrder);
        if ($newCups <= 0 || $newCups > $maxCups) {
            return null;
        }

        // Với đơn ghép, shipper có thể đang chạy từ chi nhánh khác. Đây là chủ đích:
        // đơn đầu vẫn được dispatch theo chi nhánh/home branch như cũ, còn các đơn ghép
        // được chọn theo vị trí + tuyến thực tế để có thể tạo chuỗi 3 quán -> 3 khách.
        $shippers = Shipper::query()
            ->with('user')
            ->where('status', 'busy')
            ->when(Schema::hasColumn('shippers', 'last_active_at'), fn ($query) => $query->where('last_active_at', '>=', now()->subMinutes(max(1, (int) config('shipper_dispatch.presence.active_ttl_minutes', 3)))))
            ->whereNotNull('current_latitude')
            ->whereNotNull('current_longitude')
            ->whereHas('user', fn ($query) => $query
                ->where('is_active', 1)
                ->where('role_id', User::SHIPPER_ROLE_ID))
            ->get()
            ->map(fn (Shipper $shipper) => [
                'shipper' => $shipper,
                'air_km_to_new_branch' => $this->haversineKm(
                    (float) $shipper->current_latitude,
                    (float) $shipper->current_longitude,
                    (float) $newPoints['branch']['latitude'],
                    (float) $newPoints['branch']['longitude']
                ),
            ])
            ->sortBy('air_km_to_new_branch')
            ->take(max(1, (int) config('shipper_dispatch.shortlist.bundle_candidates', 6)))
            ->pluck('shipper')
            ->values();

        if ($shippers->isEmpty()) {
            return null;
        }

        $rows = collect();
        foreach ($shippers as $shipper) {
            $activeOrders = Order::query()
                ->with(['branch', 'address', 'orderItems'])
                ->where('shipper_id', $shipper->id)
                ->whereIn('status', self::ACTIVE_ORDER_STATUSES)
                ->oldest('created_at')
                ->get();

            if ($activeOrders->count() < 1 || $activeOrders->count() >= $maxOrders) {
                continue;
            }

            if (! $this->canAcceptAdditionalBundle($shipper, $activeOrders)) {
                continue;
            }

            // Nếu đã có trip, dữ liệu trip phải khớp đúng các đơn đang hoạt động.
            $activeTrip = $this->activeTripForShipper($shipper);
            if ($activeTrip) {
                $tripIds = collect($activeTrip['order_ids'] ?? [])->map(fn ($id) => (int) $id)->sort()->values();
                $activeIds = $activeOrders->pluck('id')->map(fn ($id) => (int) $id)->sort()->values();
                if ($tripIds->all() !== $activeIds->all()) {
                    continue;
                }
            } elseif ($activeOrders->count() > 1) {
                // Hai đơn hoạt động mà không có trip là trạng thái dữ liệu bất thường.
                continue;
            }

            $blocked = false;
            foreach ($activeOrders as $activeOrder) {
                if (! $this->isAccepted($activeOrder, $shipper)
                    || $this->incidents->pendingIncident($activeOrder)
                    || $this->incidents->pendingHandoverForOrder($activeOrder, $shipper)) {
                    $blocked = true;
                    break;
                }
            }
            if ($blocked) {
                continue;
            }

            $currentCups = (int) $activeOrders->sum(fn (Order $order) => $this->cupCount($order));
            $totalCups = $currentCups + $newCups;
            if ($totalCups > $maxCups) {
                continue;
            }

            $evaluation = $this->evaluate($shipper, $activeOrders, $newOrder);
            /** @var Order $scoreReferenceOrder */
            $scoreReferenceOrder = $activeOrders->first();
            if (! $evaluation || ! $this->isWorthBundling($scoreReferenceOrder, $newOrder, $evaluation)) {
                continue;
            }

            $score = $this->scoring->scoreBundle($newOrder, $scoreReferenceOrder, $evaluation, $totalCups);
            $rows->push(array_merge($evaluation, [
                'shipper' => $shipper,
                'current_order' => $scoreReferenceOrder, // tương thích logger/scoring cũ
                'current_orders' => $activeOrders,
                'existing_trip' => $activeTrip,
                'total_cups' => $totalCups,
                'score' => $score['score'],
                'score_breakdown' => $score['breakdown'],
                'order_urgency' => $score['urgency'],
                'reason' => 'BUSY có '.($activeOrders->count()).' đơn: pickup gần nhất trước, lấy hết hàng rồi mới giao khách gần nhất; tối đa '.$maxOrders.' đơn / '.$maxCups.' cốc.',
            ]));
        }

        return $rows
            ->sort(function (array $a, array $b) {
                if (abs($a['score'] - $b['score']) > 0.001) {
                    return $b['score'] <=> $a['score'];
                }

                return $a['merged_duration_s'] <=> $b['merged_duration_s'];
            })
            ->first();
    }

    /**
     * Tạo chuyến ghép mới hoặc nối thêm order thứ 3 vào chuyến đang hoạt động.
     */
    public function attachOrderToTrip(Shipper $shipper, Collection $existingOrders, Order $newOrder, array $evaluation): ?int
    {
        if (! $this->schemaReady()) {
            return null;
        }

        $existingOrders = $existingOrders->values();
        if ($existingOrders->isEmpty()) {
            return null;
        }

        $maxOrders = max(1, (int) config('shipper_dispatch.bundle.max_orders_per_trip', self::MAX_ORDERS_PER_TRIP));
        $allOrderIds = $existingOrders->pluck('id')
            ->push($newOrder->id)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($allOrderIds->count() > $maxOrders) {
            return null;
        }

        $activeTrip = $this->activeTripForShipper($shipper);
        $plan = [
            'primary_order_id' => (int) ($activeTrip['primary_order_id'] ?? $existingOrders->first()->id),
            'merged_order_id' => (int) $newOrder->id,
            'order_ids' => $allOrderIds->all(),
            'far_pair' => (bool) ($evaluation['far_pair'] ?? false),
            'separate_distance_m' => (float) ($evaluation['separate_distance_m'] ?? 0),
            'separate_duration_s' => (float) ($evaluation['separate_duration_s'] ?? 0),
            'routing_policy' => 'all_pickups_nearest_then_all_deliveries_nearest',
            'stops' => $evaluation['stops'] ?? [],
        ];

        $values = [
            'shipper_id' => $shipper->id,
            'status' => 'active',
            'total_cups' => (int) ($evaluation['total_cups'] ?? $allOrderIds->sum(function ($orderId) use ($existingOrders, $newOrder) {
                $order = (int) $orderId === (int) $newOrder->id
                    ? $newOrder
                    : $existingOrders->firstWhere('id', (int) $orderId);
                return $order ? $this->cupCount($order) : 0;
            })),
            'estimated_distance_m' => (int) round($evaluation['merged_distance_m'] ?? 0),
            'estimated_duration_s' => (int) round($evaluation['merged_duration_s'] ?? 0),
            'saved_distance_m' => (int) round($evaluation['saved_distance_m'] ?? 0),
            'plan_json' => json_encode($plan, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'updated_at' => now(),
        ];

        if ($activeTrip) {
            $tripId = (int) $activeTrip['id'];
            DB::table('delivery_bundle_trips')->where('id', $tripId)->update($values);

            DB::table('delivery_bundle_trip_orders')->updateOrInsert(
                ['order_id' => $newOrder->id],
                ['trip_id' => $tripId, 'role' => 'merged', 'created_at' => now(), 'updated_at' => now()]
            );

            return $tripId;
        }

        $tripId = (int) DB::table('delivery_bundle_trips')->insertGetId(array_merge($values, [
            'created_at' => now(),
        ]));

        foreach ($allOrderIds as $index => $orderId) {
            DB::table('delivery_bundle_trip_orders')->updateOrInsert(
                ['order_id' => (int) $orderId],
                [
                    'trip_id' => $tripId,
                    'role' => $index === 0 ? 'primary' : 'merged',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        return $tripId;
    }

    /**
     * Wrapper tương thích với code/patch cũ.
     */
    public function createTrip(Shipper $shipper, Order $primaryOrder, Order $mergedOrder, array $evaluation): ?int
    {
        return $this->attachOrderToTrip($shipper, collect([$primaryOrder]), $mergedOrder, $evaluation);
    }

    public function activeTripForShipper(Shipper $shipper): ?array
    {
        if (! $this->schemaReady()) {
            return null;
        }

        $trip = DB::table('delivery_bundle_trips')
            ->where('shipper_id', $shipper->id)
            ->where('status', 'active')
            ->latest('id')
            ->first();

        if (! $trip) {
            return null;
        }

        return $this->hydrateTrip($trip);
    }

    public function activeTripForOrder(Order $order): ?array
    {
        if (! $this->schemaReady()) {
            return null;
        }

        $tripId = DB::table('delivery_bundle_trip_orders')
            ->where('order_id', $order->id)
            ->value('trip_id');

        if (! $tripId) {
            return null;
        }

        $trip = DB::table('delivery_bundle_trips')
            ->where('id', $tripId)
            ->where('status', 'active')
            ->first();

        return $trip ? $this->hydrateTrip($trip) : null;
    }

    public function nextStopForShipper(Shipper $shipper): ?array
    {
        $trip = $this->activeTripForShipper($shipper);
        if (! $trip) {
            return null;
        }

        $orders = Order::query()
            ->whereIn('id', $trip['order_ids'])
            ->get()
            ->keyBy('id');

        // Cứu chuyến luôn có ưu tiên cao nhất.
        foreach ($trip['order_ids'] as $tripOrderId) {
            $handoverOrder = $orders->get((int) $tripOrderId);
            if (! $handoverOrder) {
                continue;
            }

            $handover = $this->incidents->pendingHandoverForOrder($handoverOrder, $shipper);
            if ($handover) {
                return [
                    'type' => 'handover',
                    'order_id' => (int) $handoverOrder->id,
                    'latitude' => (float) $handover['latitude'],
                    'longitude' => (float) $handover['longitude'],
                    'label' => (string) ($handover['label'] ?? 'Điểm bàn giao với shipper cũ'),
                    'order' => $handoverOrder,
                    'trip' => $trip,
                    'handover' => $handover,
                ];
            }
        }

        // QUAN TRỌNG: đi đúng sequence 1 -> 2 -> 3 -> 4 -> 5 -> 6 đã lưu.
        // Không được bỏ qua quán đang preparing để nhảy sang giao khách, vì như vậy
        // sẽ phá nguyên tắc "lấy hết hàng trước rồi mới giao".
        foreach ($trip['stops'] as $stop) {
            $orderId = (int) ($stop['order_id'] ?? 0);
            $order = $orders->get($orderId);
            if (! $order) {
                continue;
            }

            if (! $this->stopSatisfied($stop, $order)) {
                $status = OrderStatus::normalize((string) $order->status);
                $waitingReady = ($stop['type'] ?? '') === 'pickup'
                    && in_array($status, [OrderStatus::CONFIRMED, OrderStatus::PREPARING], true);

                return array_merge($stop, [
                    'order' => $order,
                    'trip' => $trip,
                    'waiting_ready' => $waitingReady,
                ]);
            }
        }

        $this->completeTripIfFinished($shipper);

        return null;
    }

    /**
     * Điểm vật lý hiện tại của chuyến ghép.
     *
     * Khác với nextStopForShipper() (mỗi order có một pickup stop), hàm này gộp
     * toàn bộ pickup chưa hoàn tất ở CÙNG một chi nhánh thành một điểm đến duy nhất.
     * Nhờ vậy UI và backend cùng hiểu: tới một quán một lần, xử lý toàn bộ đơn của quán.
     */
    public function currentPhysicalStopForShipper(Shipper $shipper): ?array
    {
        $next = $this->nextStopForShipper($shipper);
        if (! $next) {
            return null;
        }

        $type = (string) ($next['type'] ?? '');
        if ($type !== 'pickup') {
            $next['order_ids'] = [(int) ($next['order_id'] ?? 0)];
            return $next;
        }

        $trip = $next['trip'] ?? $this->activeTripForShipper($shipper);
        if (! $trip) {
            $next['order_ids'] = [(int) ($next['order_id'] ?? 0)];
            return $next;
        }

        $orders = Order::query()
            ->whereIn('id', $trip['order_ids'] ?? [])
            ->get()
            ->keyBy('id');

        $currentOrder = $orders->get((int) ($next['order_id'] ?? 0));
        $branchId = (int) ($currentOrder?->branch_id ?? 0);
        if ($branchId <= 0) {
            $next['order_ids'] = [(int) ($next['order_id'] ?? 0)];
            return $next;
        }

        $orderIds = [];
        foreach ($trip['stops'] ?? [] as $stop) {
            if (($stop['type'] ?? '') !== 'pickup') {
                continue;
            }

            $order = $orders->get((int) ($stop['order_id'] ?? 0));
            if (! $order || (int) ($order->branch_id ?? 0) !== $branchId) {
                continue;
            }

            if (! $this->stopSatisfied($stop, $order)) {
                $orderIds[] = (int) $order->id;
            }
        }

        $next['branch_id'] = $branchId;
        $next['order_ids'] = array_values(array_unique($orderIds ?: [(int) ($next['order_id'] ?? 0)]));
        $next['order_count'] = count($next['order_ids']);

        return $next;
    }

    /**
     * Backend guard cho các thao tác thay đổi trạng thái trong chuyến ghép.
     * Tài xế chỉ được thao tác đúng điểm mà hệ thống đang dẫn tới; không thể
     * mở chi tiết một đơn phía sau rồi bấm vượt bước.
     */
    public function isCurrentStopAction(Shipper $shipper, Order $order, string $type): bool
    {
        $trip = $this->activeTripForShipper($shipper);
        if (! $trip || ! in_array((int) $order->id, array_map('intval', $trip['order_ids'] ?? []), true)) {
            return true;
        }

        $current = $this->currentPhysicalStopForShipper($shipper);
        if (! $current || (string) ($current['type'] ?? '') !== $type) {
            return false;
        }

        if ($type === 'pickup') {
            return in_array((int) $order->id, array_map('intval', $current['order_ids'] ?? []), true);
        }

        return (int) ($current['order_id'] ?? 0) === (int) $order->id;
    }

    public function completeTripIfFinished(Shipper $shipper): bool
    {
        $trip = $this->activeTripForShipper($shipper);
        if (! $trip) {
            return false;
        }

        $unfinished = Order::query()
            ->whereIn('id', $trip['order_ids'])
            ->whereNotIn('status', [OrderStatus::DELIVERED, OrderStatus::COMPLETED, OrderStatus::CANCELLED])
            ->exists();

        if ($unfinished) {
            return false;
        }

        DB::table('delivery_bundle_trips')->where('id', $trip['id'])->update([
            'status' => 'completed',
            'updated_at' => now(),
        ]);

        return true;
    }

    public function hasOtherActiveOrder(Shipper $shipper, int $excludeOrderId): bool
    {
        return Order::query()
            ->where('shipper_id', $shipper->id)
            ->where('id', '!=', $excludeOrderId)
            ->whereIn('status', self::ACTIVE_ORDER_STATUSES)
            ->exists();
    }

    public function dissolveTripForOrder(Order $order, string $reason = 'Chuyến ghép được tách do trạng thái đơn thay đổi.'): bool
    {
        if (! $this->schemaReady()) {
            return false;
        }

        $tripId = DB::table('delivery_bundle_trip_orders')->where('order_id', $order->id)->value('trip_id');
        if (! $tripId) {
            return false;
        }

        $updated = DB::table('delivery_bundle_trips')
            ->where('id', $tripId)
            ->where('status', 'active')
            ->update([
                'status' => 'dissolved',
                'updated_at' => now(),
            ]);

        return $updated > 0;
    }

    public function customerNotice(Order $order): ?array
    {
        $trip = $this->activeTripForOrder($order);
        if (! $trip) {
            return null;
        }

        $position = null;
        foreach ($trip['stops'] as $index => $stop) {
            if (($stop['type'] ?? '') === 'delivery' && (int) ($stop['order_id'] ?? 0) === (int) $order->id) {
                $position = $index;
                break;
            }
        }

        if ($position === null) {
            return null;
        }

        $hiddenStopsBefore = collect(array_slice($trip['stops'], 0, $position))
            ->filter(fn ($stop) => (int) ($stop['order_id'] ?? 0) !== (int) $order->id)
            ->count();

        return [
            'shared_trip' => true,
            'hidden_stops_before' => $hiddenStopsBefore,
            'message' => $hiddenStopsBefore > 0
                ? 'Tài xế đang thực hiện chuyến ghép. Hệ thống lấy hết các đơn trên tuyến trước rồi giao theo điểm khách gần nhất; thông tin khách khác được ẩn.'
                : 'Đơn của bạn đang nằm trong chuyến ghép. Thông tin khách khác luôn được ẩn.',
        ];
    }

    public function tripLabel(?array $trip): ?string
    {
        if (! $trip) {
            return null;
        }

        $savedKm = max(0, ((float) $trip['saved_distance_m']) / 1000);

        return 'Chuyến ghép '.count($trip['order_ids']).' đơn · '.$trip['total_cups'].' cốc'
            .($savedKm >= 0.1 ? ' · tiết kiệm ~'.number_format($savedKm, 1, ',', '.').' km' : '');
    }

    /**
     * Đánh giá phương án ghép dựa trên sequence cố định theo nghiệp vụ:
     * pickup gần nhất -> ... -> pickup cuối -> delivery gần nhất -> ...
     */
    private function evaluate(Shipper $shipper, Collection $currentOrders, Order $newOrder): ?array
    {
        $start = [
            'latitude' => (float) $shipper->current_latitude,
            'longitude' => (float) $shipper->current_longitude,
        ];

        $currentStops = [];
        foreach ($currentOrders as $order) {
            $currentStops = array_merge($currentStops, $this->remainingStops($order));
        }
        $newStops = $this->remainingStops($newOrder);
        if (! $currentStops || ! $newStops) {
            return null;
        }

        $currentSequence = $this->pickupThenDeliverySequence($start, $currentStops);
        $mergedSequence = $this->pickupThenDeliverySequence($start, array_merge($currentStops, $newStops));
        if ($currentSequence === null || $mergedSequence === null) {
            return null;
        }

        $currentRoute = $this->routing->routeThrough(array_merge([$start], $this->pointsOnly($currentSequence)));
        $newDirect = $this->routing->routeThrough($this->pointsOnly($newStops));
        $mergedRoute = $this->routing->routeThrough(array_merge([$start], $this->pointsOnly($mergedSequence)));

        // Quyết định ghép bắt buộc dựa trên route thật. Khi OSRM lỗi thì chờ vòng dispatch sau.
        if ((bool) ($currentRoute['fallback'] ?? true)
            || (bool) ($newDirect['fallback'] ?? true)
            || (bool) ($mergedRoute['fallback'] ?? true)) {
            return null;
        }

        $separateDistance = (float) ($currentRoute['distance_m'] ?? 0) + (float) ($newDirect['distance_m'] ?? 0);
        $separateDuration = (float) ($currentRoute['duration_s'] ?? 0) + (float) ($newDirect['duration_s'] ?? 0);
        $mergedDistance = (float) ($mergedRoute['distance_m'] ?? 0);
        $mergedDuration = (float) ($mergedRoute['duration_s'] ?? 0);
        if ($separateDistance <= 0 || $mergedDistance <= 0 || $mergedDuration <= 0) {
            return null;
        }

        $maxTripSeconds = (float) config('shipper_dispatch.bundle.max_trip_minutes', 75.0) * 60;
        if ($mergedDuration > $maxTripSeconds) {
            return null;
        }

        $maxExistingDelay = (float) config('shipper_dispatch.bundle.max_existing_customer_delay_minutes', 12.0) * 60;
        $existingDelays = [];
        $existingEtas = [];
        $currentBaselineEtas = [];
        foreach ($currentOrders as $currentOrder) {
            $baselineEta = $this->etaToDeliveryFromRoute($currentRoute, $currentSequence, (int) $currentOrder->id);
            $mergedEta = $this->etaToDeliveryFromRoute($mergedRoute, $mergedSequence, (int) $currentOrder->id);
            if ($baselineEta <= 0 || $mergedEta <= 0) {
                return null;
            }

            $delay = max(0.0, $mergedEta - $baselineEta);
            if ($delay > $maxExistingDelay) {
                return null;
            }

            $currentBaselineEtas[(int) $currentOrder->id] = $baselineEta;
            $existingEtas[(int) $currentOrder->id] = $mergedEta;
            $existingDelays[(int) $currentOrder->id] = $delay;
        }

        $newEta = $this->etaToDeliveryFromRoute($mergedRoute, $mergedSequence, (int) $newOrder->id);
        if ($newEta <= 0) {
            return null;
        }

        $scheduledLateness = 0.0;
        $scheduled = $newOrder->scheduled_delivery_time ?? $newOrder->scheduled_at;
        if ($scheduled) {
            $predicted = now()->copy()->addSeconds((int) round($newEta));
            if ($predicted->greaterThan($scheduled)) {
                $scheduledLateness = $scheduled->diffInSeconds($predicted);
            }
        }

        $maxScheduledLateness = (float) config('shipper_dispatch.bundle.max_scheduled_lateness_minutes', 15.0) * 60;
        if ($scheduledLateness > $maxScheduledLateness) {
            return null;
        }

        /** @var Order $firstCurrent */
        $firstCurrent = $currentOrders->first();
        $currentDirectKm = $this->orderDirectDistanceKm($firstCurrent);
        $newDirectKm = $this->orderDirectDistanceKm($newOrder);
        $farKm = (float) config('shipper_dispatch.bundle.far_order_km', self::FAR_ORDER_KM);
        $farPair = $currentDirectKm >= $farKm && $newDirectKm >= $farKm;
        $savedDistance = $separateDistance - $mergedDistance;
        $savedRatio = $separateDistance > 0 ? $savedDistance / $separateDistance : 0;
        $maxDelay = $existingDelays ? max($existingDelays) : 0.0;

        return [
            'merged_distance_m' => $mergedDistance,
            'merged_duration_s' => $mergedDuration,
            'separate_distance_m' => $separateDistance,
            'separate_duration_s' => $separateDuration,
            'saved_distance_m' => $savedDistance,
            'saved_ratio' => $savedRatio,
            'far_pair' => $farPair,
            'stops' => $mergedSequence,
            'current_order_distance_km' => $currentDirectKm,
            'new_order_distance_km' => $newDirectKm,
            'current_baseline_eta_s' => (float) ($currentBaselineEtas[(int) $firstCurrent->id] ?? 0),
            'existing_customer_eta_s' => (float) ($existingEtas[(int) $firstCurrent->id] ?? 0),
            'existing_customer_delay_s' => $maxDelay,
            'existing_customer_delays_s' => $existingDelays,
            'new_customer_eta_s' => $newEta,
            'new_scheduled_lateness_s' => $scheduledLateness,
            'routing_policy' => 'all_pickups_nearest_then_all_deliveries_nearest',
        ];
    }

    private function isWorthBundling(Order $currentOrder, Order $newOrder, array $evaluation): bool
    {
        $maxTripSeconds = (float) config('shipper_dispatch.bundle.max_trip_minutes', 75.0) * 60;
        if (($evaluation['merged_duration_s'] ?? PHP_INT_MAX) > $maxTripSeconds) {
            return false;
        }

        $maxExistingDelay = (float) config('shipper_dispatch.bundle.max_existing_customer_delay_minutes', 12.0) * 60;
        if (($evaluation['existing_customer_delay_s'] ?? 0) > $maxExistingDelay) {
            return false;
        }

        $maxScheduledLateness = (float) config('shipper_dispatch.bundle.max_scheduled_lateness_minutes', 15.0) * 60;
        if (($evaluation['new_scheduled_lateness_s'] ?? 0) > $maxScheduledLateness) {
            return false;
        }

        $separate = (float) ($evaluation['separate_distance_m'] ?? 0);
        $merged = (float) ($evaluation['merged_distance_m'] ?? PHP_INT_MAX);
        $saved = (float) ($evaluation['saved_distance_m'] ?? 0);
        $farPair = (bool) ($evaluation['far_pair'] ?? false);
        if ($separate <= 0) {
            return false;
        }

        $currentStatus = OrderStatus::normalize((string) $currentOrder->status);
        $newStatus = OrderStatus::normalize((string) $newOrder->status);
        $prePickupPair = in_array($currentStatus, self::PRE_PICKUP_STATUSES, true)
            && in_array($newStatus, self::PRE_PICKUP_STATUSES, true);

        if ($saved >= (float) config('shipper_dispatch.bundle.normal_min_saved_m', 500.0)) {
            return true;
        }

        $ratio = (float) config('shipper_dispatch.bundle.far_pair_max_distance_ratio', 1.02);
        if ($farPair && $merged <= $separate * $ratio) {
            return true;
        }

        if ($prePickupPair) {
            $extraDistance = $merged - $separate;
            $extraDelay = (float) ($evaluation['existing_customer_delay_s'] ?? 0);

            if ($extraDistance <= 300.0 && $extraDelay <= 180.0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Tạo sequence đúng nghiệp vụ: tất cả pickup trước, tất cả delivery sau.
     * Trong từng phase, chọn stop gần nhất theo quãng đường route thật từ stop trước đó.
     */
    private function pickupThenDeliverySequence(array $start, array $stops): ?array
    {
        $pickups = array_values(array_filter($stops, fn ($stop) => ($stop['type'] ?? '') === 'pickup'));
        $deliveries = array_values(array_filter($stops, fn ($stop) => ($stop['type'] ?? '') === 'delivery'));

        $sequence = [];
        $cursor = $start;

        foreach ([$pickups, $deliveries] as $phaseStops) {
            $remaining = $phaseStops;
            while ($remaining) {
                $bestIndex = null;
                $bestDistance = INF;
                $bestDuration = INF;

                foreach ($remaining as $index => $stop) {
                    $route = $this->routing->route(
                        (float) $cursor['latitude'],
                        (float) $cursor['longitude'],
                        (float) $stop['latitude'],
                        (float) $stop['longitude']
                    );

                    if ((bool) ($route['fallback'] ?? true)) {
                        continue;
                    }

                    $distance = (float) ($route['distance_m'] ?? INF);
                    $duration = (float) ($route['duration_s'] ?? INF);
                    if ($distance < $bestDistance - 1
                        || (abs($distance - $bestDistance) <= 1 && $duration < $bestDuration)) {
                        $bestIndex = $index;
                        $bestDistance = $distance;
                        $bestDuration = $duration;
                    }
                }

                if ($bestIndex === null) {
                    return null;
                }

                $chosen = $remaining[$bestIndex];
                $sequence[] = $chosen;
                $cursor = [
                    'latitude' => (float) $chosen['latitude'],
                    'longitude' => (float) $chosen['longitude'],
                ];
                array_splice($remaining, $bestIndex, 1);
            }
        }

        return $sequence;
    }

    /**
     * routeThrough trả legs tương ứng start->stop1, stop1->stop2...
     */
    private function etaToDeliveryFromRoute(array $route, array $stops, int $orderId): float
    {
        $legs = $route['legs'] ?? [];
        if (! is_array($legs) || count($legs) < count($stops)) {
            return 0.0;
        }

        $elapsed = 0.0;
        foreach ($stops as $index => $stop) {
            $elapsed += (float) ($legs[$index]['duration_s'] ?? 0);
            if (($stop['type'] ?? null) === 'delivery' && (int) ($stop['order_id'] ?? 0) === $orderId) {
                return $elapsed;
            }
        }

        return 0.0;
    }

    private function remainingStops(Order $order): array
    {
        $points = $this->orderPointSet($order);
        if (! $points) {
            return [];
        }

        $status = OrderStatus::normalize((string) $order->status);
        $stops = [];

        if (in_array($status, self::PRE_PICKUP_STATUSES, true)) {
            $stops[] = [
                'type' => 'pickup',
                'order_id' => (int) $order->id,
                'latitude' => $points['branch']['latitude'],
                'longitude' => $points['branch']['longitude'],
                'label' => $order->branch?->name ?: 'Chi nhánh Chill Drink',
            ];
        }

        if (in_array($status, self::ACTIVE_ORDER_STATUSES, true)) {
            $stops[] = [
                'type' => 'delivery',
                'order_id' => (int) $order->id,
                'latitude' => $points['customer']['latitude'],
                'longitude' => $points['customer']['longitude'],
                'label' => 'Khách '.$order->displayCode(),
            ];
        }

        return $stops;
    }

    private function pointsOnly(array $stops): array
    {
        return collect($stops)->map(fn ($stop) => [
            'latitude' => (float) $stop['latitude'],
            'longitude' => (float) $stop['longitude'],
        ])->all();
    }

    private function stopSatisfied(array $stop, Order $order): bool
    {
        $status = OrderStatus::normalize((string) $order->status);
        if ($status === OrderStatus::CANCELLED) {
            return true;
        }

        if (($stop['type'] ?? '') === 'pickup') {
            return in_array($status, [
                OrderStatus::SHIPPER_PICKED_UP,
                OrderStatus::DELIVERING,
                OrderStatus::DELIVERED,
                OrderStatus::COMPLETED,
            ], true);
        }

        if (($stop['type'] ?? '') === 'delivery') {
            return in_array($status, [OrderStatus::DELIVERED, OrderStatus::COMPLETED], true);
        }

        return true;
    }

    private function hydrateTrip(object $trip): array
    {
        $plan = json_decode((string) $trip->plan_json, true) ?: [];
        $orderIds = DB::table('delivery_bundle_trip_orders')
            ->where('trip_id', $trip->id)
            ->orderBy('id')
            ->pluck('order_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return [
            'id' => (int) $trip->id,
            'shipper_id' => (int) $trip->shipper_id,
            'status' => (string) $trip->status,
            'total_cups' => (int) $trip->total_cups,
            'estimated_distance_m' => (int) ($trip->estimated_distance_m ?? 0),
            'estimated_duration_s' => (int) ($trip->estimated_duration_s ?? 0),
            'saved_distance_m' => (int) ($trip->saved_distance_m ?? 0),
            'order_ids' => $orderIds,
            'stops' => $plan['stops'] ?? [],
            'routing_policy' => $plan['routing_policy'] ?? null,
            'far_pair' => (bool) ($plan['far_pair'] ?? false),
            'primary_order_id' => (int) ($plan['primary_order_id'] ?? ($orderIds[0] ?? 0)),
            'merged_order_id' => (int) ($plan['merged_order_id'] ?? ($orderIds[1] ?? 0)),
        ];
    }

    private function orderPointSet(Order $order): ?array
    {
        $order->loadMissing(['branch', 'address']);
        $branch = $order->branch;
        if (! $branch || ! is_numeric($branch->latitude) || ! is_numeric($branch->longitude)) {
            return null;
        }

        $lat = is_numeric($order->shipping_latitude) ? (float) $order->shipping_latitude : null;
        $lng = is_numeric($order->shipping_longitude) ? (float) $order->shipping_longitude : null;
        if ($lat === null || $lng === null) {
            $lat = is_numeric($order->address?->latitude) ? (float) $order->address->latitude : null;
            $lng = is_numeric($order->address?->longitude) ? (float) $order->address?->longitude : null;
        }
        if ($lat === null || $lng === null) {
            return null;
        }

        return [
            'branch' => ['latitude' => (float) $branch->latitude, 'longitude' => (float) $branch->longitude],
            'customer' => ['latitude' => $lat, 'longitude' => $lng],
        ];
    }

    private function orderDirectDistanceKm(Order $order): float
    {
        $points = $this->orderPointSet($order);
        if (! $points) {
            return 0.0;
        }

        $route = $this->routing->route(
            $points['branch']['latitude'],
            $points['branch']['longitude'],
            $points['customer']['latitude'],
            $points['customer']['longitude']
        );

        if ((bool) ($route['fallback'] ?? true)) {
            return 0.0;
        }

        return round(((float) ($route['distance_m'] ?? 0)) / 1000, 3);
    }

    private function cupCount(Order $order): int
    {
        $order->loadMissing('orderItems');

        return max(1, (int) $order->orderItems->sum(fn ($item) => max(0, (int) $item->quantity)));
    }

    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusKm = 6371.0;
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);
        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;

        return $earthRadiusKm * 2 * atan2(sqrt($a), sqrt(max(0, 1 - $a)));
    }

    private function isAccepted(Order $order, Shipper $shipper): bool
    {
        if (! Schema::hasTable('shipments') || ! Schema::hasTable('shipment_history')) {
            return true;
        }

        $shipmentId = DB::table('shipments')
            ->where('order_id', $order->id)
            ->where('shipper_id', $shipper->id)
            ->latest('id')
            ->value('id');

        return $shipmentId && DB::table('shipment_history')
            ->where('shipment_id', $shipmentId)
            ->where('status', 'accepted')
            ->exists();
    }
}
