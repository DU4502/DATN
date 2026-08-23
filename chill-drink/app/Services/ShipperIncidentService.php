<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Shipper;
use App\Models\User;
use App\Support\OrderStatus;
use App\Support\RealtimeOrderNotifier;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ShipperIncidentService
{
    /**
     * Bán kính tối đa để tự cứu một chuyến đang gặp sự cố.
     * Phần 7/9 sẽ biến ngưỡng này thành cấu hình dispatch hoàn chỉnh.
     */
    public const REASSIGN_MAX_RADIUS_KM = 5.0;

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

    private const POST_PICKUP_STATUSES = [
        OrderStatus::SHIPPER_PICKED_UP,
        OrderStatus::DELIVERING,
    ];

    private const INCIDENT_RESOLUTION_STATUSES = [
        'incident_resolved_keep',
        'incident_resolved_reassign',
        'incident_resolved_cancel',
        'reassigned_out',
    ];

    public function __construct(private readonly DeliveryRoutingService $routing)
    {
    }

    /**
     * Trả về sự cố chưa được quản lý xử lý của shipment hiện tại.
     */
    public function pendingIncident(Order $order): ?array
    {
        if (! $order->shipper_id || ! Schema::hasTable('shipments') || ! Schema::hasTable('shipment_history')) {
            return null;
        }

        $shipment = DB::table('shipments')
            ->where('order_id', $order->id)
            ->where('shipper_id', $order->shipper_id)
            ->latest('id')
            ->first();

        if (! $shipment) {
            return null;
        }

        $issue = DB::table('shipment_history')
            ->where('shipment_id', $shipment->id)
            ->where('status', 'issue_reported')
            ->latest('id')
            ->first();

        if (! $issue) {
            return null;
        }

        $resolved = DB::table('shipment_history')
            ->where('shipment_id', $shipment->id)
            ->where('id', '>', $issue->id)
            ->whereIn('status', self::INCIDENT_RESOLUTION_STATUSES)
            ->exists();

        if ($resolved) {
            return null;
        }

        $shipper = Shipper::with('user')->find($shipment->shipper_id);
        $reportedAt = $issue->created_at ? \Illuminate\Support\Carbon::parse($issue->created_at) : null;

        return [
            'incident_id' => (int) $issue->id,
            'order_id' => (int) $order->id,
            'shipment_id' => (int) $shipment->id,
            'shipper_id' => (int) $shipment->shipper_id,
            'shipper_name' => $shipper?->user?->name ?: $shipper?->code ?: 'Shipper',
            'shipper_phone' => $shipper?->phone ?: $shipper?->user?->phone,
            'description' => (string) ($issue->description ?? 'Shipper báo sự cố.'),
            'incident_type' => (string) ($issue->incident_type ?? 'driver_issue'),
            'reported_at' => $reportedAt,
            'reported_at_label' => $reportedAt?->format('H:i · d/m/Y'),
            'shipment_status' => (string) ($shipment->status ?? ''),
        ];
    }

    /**
     * @param iterable<Order> $orders
     * @return array<int,array>
     */
    public function pendingForOrders(iterable $orders): array
    {
        $result = [];

        foreach ($orders as $order) {
            if (! $order instanceof Order) {
                continue;
            }

            $incident = $this->pendingIncident($order);
            if ($incident) {
                $result[(int) $order->id] = $incident;
            }
        }

        return $result;
    }

    /**
     * Shipment hiện tại có đang chờ shipper mới tới điểm bàn giao hay không.
     */
    public function pendingHandoverForOrder(Order $order, Shipper $shipper): ?array
    {
        if (! Schema::hasTable('shipments')) {
            return null;
        }

        $shipment = DB::table('shipments')
            ->where('order_id', $order->id)
            ->where('shipper_id', $shipper->id)
            ->latest('id')
            ->first();

        if (! $shipment || ! in_array((string) $shipment->status, ['handover_required', 'issue_pending'], true)) {
            return null;
        }

        if (Schema::hasTable('shipment_history') && DB::table('shipment_history')
            ->where('shipment_id', $shipment->id)
            ->where('status', 'handover_received')
            ->exists()) {
            return null;
        }

        $payload = $this->decodeHandoverNote($shipment->note ?? null);
        if (! $payload) {
            return null;
        }

        return array_merge($payload, [
            'shipment_id' => (int) $shipment->id,
        ]);
    }

    /**
     * Quản lý xác nhận sự cố đã được hỗ trợ nhưng shipper hiện tại vẫn tiếp tục chuyến.
     */
    public function keepCurrentShipper(Order $order, ?User $actor = null, bool $notifyCustomer = true): array
    {
        $result = DB::transaction(function () use ($order, $actor) {
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $incident = $this->pendingIncident($lockedOrder);

            if (! $incident) {
                return [
                    'status' => 'no_pending_incident',
                    'message' => 'Sự cố này đã được xử lý hoặc không còn tồn tại.',
                ];
            }

            $shipmentId = (int) $incident['shipment_id'];
            $shipper = Shipper::query()->whereKey($incident['shipper_id'])->lockForUpdate()->first();

            if ((int) $lockedOrder->shipper_id !== (int) $incident['shipper_id']) {
                return [
                    'status' => 'changed',
                    'message' => 'Đơn đã được điều phối sang shipper khác.',
                ];
            }

            if ($shipper) {
                $shipper->forceFill(['status' => 'busy'])->save();
            }

            $shipmentRow = DB::table('shipments')->where('id', $shipmentId)->first(['status', 'note']);
            $handoverStillPending = $shipmentRow
                && $this->decodeHandoverNote($shipmentRow->note ?? null)
                && ! DB::table('shipment_history')->where('shipment_id', $shipmentId)->where('status', 'handover_received')->exists();

            DB::table('shipments')->where('id', $shipmentId)->update([
                'status' => $handoverStillPending ? 'handover_required' : $this->shipmentStatusForOrder($lockedOrder),
                'note' => $handoverStillPending
                    ? (string) ($shipmentRow->note ?? '')
                    : 'Sự cố đã được quản lý xử lý. Shipper hiện tại tiếp tục chuyến.',
                'updated_at' => now(),
            ]);

            $this->addHistory(
                $shipmentId,
                'incident_resolved_keep',
                'Quản lý xác nhận shipper hiện tại tiếp tục giao hàng'.($actor ? ' · '.$actor->name : '').'.'
            );

            $lockedOrder->touch();

            return [
                'status' => 'kept',
                'message' => 'Đã xác nhận hỗ trợ xong. Shipper hiện tại tiếp tục chuyến.',
                'order' => $lockedOrder->fresh(),
            ];
        }, 3);

        if ($notifyCustomer && ($result['order'] ?? null) instanceof Order) {
            RealtimeOrderNotifier::orderStatusUpdated($result['order']);
        }

        return $result;
    }

    /**
     * Từ chối yêu cầu hủy của khách và tiếp tục chuyến. Đây vẫn là quyết định
     * nội bộ của quản lý, nên không gửi thông báo "khách yêu cầu hủy" cho khách.
     */
    public function keepCustomerCancelRequest(Order $order, ?User $actor = null): array
    {
        $incident = $this->pendingIncident($order);
        if (! $incident || ($incident['incident_type'] ?? 'driver_issue') !== 'customer_cancel') {
            return [
                'status' => 'invalid_incident_type',
                'message' => 'Đơn này không có yêu cầu hủy đang chờ xử lý.',
            ];
        }

        $result = $this->keepCurrentShipper($order, $actor, notifyCustomer: false);
        if (($result['status'] ?? null) === 'kept') {
            $result['message'] = 'Đã từ chối yêu cầu hủy nội bộ. Đơn tiếp tục được giao.';
        }

        return $result;
    }

    /**
     * Duyệt yêu cầu khách xin hủy. Chỉ controller đã kiểm tra Admin/Super Admin
     * mới gọi được method này; force=true là cần thiết vì đơn có thể đã vào chuyến.
     */
    public function cancelCustomerRequest(Order $order, ?User $actor = null): array
    {
        $incident = $this->pendingIncident($order);
        if (! $incident || ($incident['incident_type'] ?? 'driver_issue') !== 'customer_cancel') {
            return [
                'status' => 'invalid_incident_type',
                'message' => 'Đơn này không có yêu cầu hủy đang chờ xử lý.',
            ];
        }

        try {
            $cancelled = app(OrderCancellationService::class)->cancel(
                $order,
                'Duyệt hủy theo yêu cầu của khách: '.$incident['description'],
                $actor,
                force: true,
            );
        } catch (\Throwable $exception) {
            return [
                'status' => 'cancel_failed',
                'message' => $exception->getMessage() ?: 'Chưa thể hủy đơn lúc này.',
            ];
        }

        $this->addHistory(
            (int) $incident['shipment_id'],
            'incident_resolved_cancel',
            'Quản lý đã duyệt hủy yêu cầu nội bộ của khách'.($actor ? ' · '.$actor->name : '').'.'
        );

        return [
            'status' => 'cancelled',
            'message' => 'Đã duyệt hủy đơn theo yêu cầu nội bộ của khách.',
            'order' => $cancelled['order']->fresh(),
        ];
    }

    /**
     * Quản lý xác nhận shipper cũ không thể tiếp tục. Hệ thống tự tìm người thay thế.
     * Không đổi trạng thái nghiệp vụ của order.
     */
    public function reassign(Order $order, ?User $actor = null): array
    {
        $result = DB::transaction(function () use ($order, $actor) {
            $lockedOrder = Order::query()
                ->with('branch')
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            $orderStatus = OrderStatus::normalize((string) $lockedOrder->status);
            if (! in_array($orderStatus, self::ACTIVE_ORDER_STATUSES, true)) {
                return [
                    'status' => 'invalid_status',
                    'message' => 'Đơn không còn ở trạng thái có thể điều phối lại shipper.',
                ];
            }

            $incident = $this->pendingIncident($lockedOrder);
            if (! $incident) {
                return [
                    'status' => 'no_pending_incident',
                    'message' => 'Sự cố này đã được xử lý hoặc không còn tồn tại.',
                ];
            }

            $oldShipper = Shipper::query()
                ->with('user')
                ->whereKey($incident['shipper_id'])
                ->lockForUpdate()
                ->first();

            if (! $oldShipper || (int) $lockedOrder->shipper_id !== (int) $oldShipper->id) {
                return [
                    'status' => 'changed',
                    'message' => 'Shipper hiện tại của đơn đã thay đổi.',
                ];
            }

            $bundleService = app(ShipperBundleService::class);
            $bundleTrip = $bundleService->activeTripForOrder($lockedOrder);
            $tripOrderIds = $bundleTrip && (int) ($bundleTrip['shipper_id'] ?? 0) === (int) $oldShipper->id
                ? collect($bundleTrip['order_ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->values()->all()
                : [(int) $lockedOrder->id];

            // Một sự cố làm shipper không thể tiếp tục là sự cố của cả chuyến đang cầm.
            // Nếu đang ghép A+B, phải chuyển cả A và B sang cùng người thay thế; tuyệt đối
            // không để đơn còn lại tiếp tục trỏ vào shipper vừa bị chuyển OFFLINE.
            $transferOrders = Order::query()
                ->with('branch')
                ->whereIn('id', $tripOrderIds)
                ->where('shipper_id', $oldShipper->id)
                ->whereIn('status', self::ACTIVE_ORDER_STATUSES)
                ->lockForUpdate()
                ->get();

            if ($transferOrders->isEmpty()) {
                return [
                    'status' => 'changed',
                    'message' => 'Các đơn của chuyến đã được điều phối sang shipper khác.',
                ];
            }

            $incidentTransferOrder = $transferOrders->firstWhere('id', $lockedOrder->id);
            if (! $incidentTransferOrder) {
                return [
                    'status' => 'changed',
                    'message' => 'Đơn báo sự cố không còn thuộc chuyến hiện tại của shipper.',
                ];
            }

            $oldShipmentId = (int) $incident['shipment_id'];
            $takeover = $this->takeoverPointForOrders($transferOrders, $oldShipper, $incidentTransferOrder, $oldShipmentId);
            if (! $takeover) {
                return [
                    'status' => 'missing_takeover_location',
                    'message' => 'Chưa có vị trí đủ tin cậy để điều phối người thay thế. Hãy liên hệ shipper cũ và cập nhật GPS trước.',
                ];
            }

            $transferIds = $transferOrders->pluck('id')->map(fn ($id) => (int) $id)->all();
            $busyIds = Order::query()
                ->whereNotNull('shipper_id')
                ->whereIn('status', self::ACTIVE_ORDER_STATUSES)
                ->whereNotIn('id', $transferIds)
                ->pluck('shipper_id')
                ->filter()
                ->unique()
                ->values();

            $candidateQuery = Shipper::query()
                ->with('user')
                ->where('id', '!=', $oldShipper->id)
                ->where('status', 'online')
                ->whereHas('user', fn ($query) => $query
                    ->where('is_active', 1)
                    ->where('role_id', User::SHIPPER_ROLE_ID)
                    ->where('branch_id', $incidentTransferOrder->branch_id));

            if (Schema::hasColumn('shippers', 'returning_to_branch_id')) {
                $candidateQuery->whereNull('returning_to_branch_id');
            }

            if ($busyIds->isNotEmpty()) {
                $candidateQuery->whereNotIn('id', $busyIds->all());
            }

            // Lock pool để hai sự cố xử lý đồng thời không lấy cùng một shipper.
            $candidates = $candidateQuery->lockForUpdate()->get();
            $candidateResult = $this->chooseReplacement($incidentTransferOrder, $candidates, $takeover);

            if (! $candidateResult) {
                $this->addHistory(
                    $oldShipmentId,
                    'reassign_search_failed',
                    'Hệ thống chưa tìm được shipper rảnh trong bán kính '.self::REASSIGN_MAX_RADIUS_KM.' km quanh điểm tiếp quản.'
                );

                return [
                    'status' => 'waiting',
                    'message' => 'Chưa có shipper rảnh cùng chi nhánh phù hợp trong '.self::REASSIGN_MAX_RADIUS_KM.' km. Đơn vẫn giữ nguyên để có thể thử điều phối lại sau.',
                ];
            }

            /** @var Shipper $newShipper */
            $newShipper = $candidateResult['shipper'];
            $isBundleTransfer = $bundleTrip && count($transferIds) > 1;

            // Giữ nguyên chuyến ghép và plan tuyến. Chỉ đổi người sở hữu chuyến.
            if ($isBundleTransfer && Schema::hasTable('delivery_bundle_trips')) {
                DB::table('delivery_bundle_trips')
                    ->where('id', (int) $bundleTrip['id'])
                    ->where('status', 'active')
                    ->update([
                        'shipper_id' => $newShipper->id,
                        'updated_at' => now(),
                    ]);
            }

            Order::query()->whereIn('id', $transferIds)->update([
                'shipper_id' => $newShipper->id,
                'updated_at' => now(),
            ]);

            // Shipper cũ gặp sự cố không được nhận thêm việc cho tới khi tự bật online lại.
            $oldValues = ['status' => 'offline'];
            $newValues = ['status' => 'busy'];
            if (Schema::hasColumn('shippers', 'returning_to_branch_id')) {
                $oldValues['returning_to_branch_id'] = null;
                $oldValues['returning_started_at'] = null;
                $newValues['returning_to_branch_id'] = null;
                $newValues['returning_started_at'] = null;
            }
            if (Schema::hasColumn('shippers', 'station_branch_id')) {
                $newValues['station_branch_id'] = null;
            }
            $oldShipper->forceFill($oldValues)->save();
            $newShipper->forceFill($newValues)->save();

            foreach ($transferOrders as $transferOrder) {
                $normalizedStatus = OrderStatus::normalize((string) $transferOrder->status);
                $handoverRequired = in_array($normalizedStatus, self::POST_PICKUP_STATUSES, true)
                    && (bool) ($takeover['handover_required'] ?? false);

                $oldShipment = Schema::hasTable('shipments')
                    ? DB::table('shipments')
                        ->where('order_id', $transferOrder->id)
                        ->where('shipper_id', $oldShipper->id)
                        ->latest('id')
                        ->first()
                    : null;
                $transferOldShipmentId = $oldShipment ? (int) $oldShipment->id : null;

                if ($transferOldShipmentId) {
                    DB::table('shipments')->where('id', $transferOldShipmentId)->update([
                        'status' => 'reassigned_out',
                        'note' => $isBundleTransfer
                            ? 'Shipper gặp sự cố. Toàn bộ chuyến ghép đã được chuyển sang shipper thay thế.'
                            : 'Quản lý xác nhận không thể tiếp tục. Chuyến đã được hệ thống điều phối lại.',
                        'updated_at' => now(),
                    ]);

                    if ((int) $transferOrder->id === (int) $incidentTransferOrder->id) {
                        $this->addHistory(
                            $transferOldShipmentId,
                            'incident_resolved_reassign',
                            'Quản lý xác nhận cần đổi shipper'.($actor ? ' · '.$actor->name : '').'.'
                        );
                    } elseif ($isBundleTransfer) {
                        $this->addHistory(
                            $transferOldShipmentId,
                            'bundle_reassigned_out',
                            'Đơn được chuyển cùng toàn bộ chuyến ghép vì shipper cũ gặp sự cố ở một đơn khác.'
                        );
                    }

                    $this->addHistory(
                        $transferOldShipmentId,
                        'reassigned_out',
                        'Hệ thống chuyển chuyến sang '.$this->shipperName($newShipper).'. Trạng thái đơn được giữ nguyên.'
                    );
                }

                $orderTakeover = $takeover;
                $orderTakeover['handover_required'] = $handoverRequired;
                if (! $handoverRequired) {
                    // Đơn chưa lấy hàng vẫn tiếp tục pickup tại chi nhánh theo plan chuyến ghép.
                    $branch = $transferOrder->branch;
                    if ($branch && is_numeric($branch->latitude) && is_numeric($branch->longitude)) {
                        $orderTakeover = [
                            'latitude' => (float) $branch->latitude,
                            'longitude' => (float) $branch->longitude,
                            'label' => $branch->name ?: 'Cửa hàng',
                            'address' => $branch->address ?: 'Điểm lấy hàng',
                            'handover_required' => false,
                            'type' => 'branch',
                        ];
                    }
                }

                $newShipmentId = $this->createReplacementShipment(
                    $transferOrder,
                    $newShipper,
                    $oldShipper,
                    $transferOldShipmentId ?: 0,
                    $orderTakeover
                );

                if ($newShipmentId) {
                    $this->addHistory(
                        $newShipmentId,
                        'assigned',
                        $isBundleTransfer
                            ? 'Hệ thống chuyển toàn bộ chuyến ghép sang '.$this->shipperName($newShipper).'. Chờ shipper bấm Nhận đơn cứu chuyến.'
                            : 'Hệ thống tự động bắn đơn cứu chuyến cho '.$this->shipperName($newShipper).'. Chờ shipper bấm Nhận đơn.'
                    );
                    $this->addHistory(
                        $newShipmentId,
                        'reassigned_in',
                        'Tiếp quản đơn từ '.$this->shipperName($oldShipper).'. Trạng thái nghiệp vụ của đơn không bị quay ngược.'
                    );

                    if ($handoverRequired) {
                        $this->addHistory(
                            $newShipmentId,
                            'handover_required',
                            'Đơn đã được lấy khỏi quán. Shipper mới phải tới điểm bàn giao của shipper cũ trước khi tiếp tục giao khách.'
                        );
                    }
                }
            }

            $freshOrders = Order::query()->whereIn('id', $transferIds)->get();

            return [
                'status' => 'assigned',
                'message' => $isBundleTransfer
                    ? 'Đã chuyển toàn bộ chuyến ghép '.count($transferIds).' đơn sang '.$this->shipperName($newShipper).'. Shipper mới sẽ tới điểm bàn giao trước nếu đang mang hàng.'
                    : 'Đã tự động điều phối sang '.$this->shipperName($newShipper).'.'.((bool) ($takeover['handover_required'] ?? false) ? ' Shipper mới sẽ tới điểm bàn giao trước.' : ''),
                'shipper' => $newShipper,
                'distance_m' => $candidateResult['distance_m'] ?? null,
                'duration_s' => $candidateResult['duration_s'] ?? null,
                'handover_required' => (bool) ($takeover['handover_required'] ?? false),
                'order' => $freshOrders->firstWhere('id', $lockedOrder->id),
                'orders' => $freshOrders,
                'bundle_transferred' => $isBundleTransfer,
            ];
        }, 3);

        $ordersToNotify = $result['orders'] ?? collect([$result['order'] ?? null]);
        foreach ($ordersToNotify as $notifyOrder) {
            if ($notifyOrder instanceof Order) {
                RealtimeOrderNotifier::orderStatusUpdated($notifyOrder);
            }
        }

        return $result;
    }

    /**
     * Với chuyến ghép, nếu bất kỳ đơn nào đã pickup thì toàn chuyến phải cứu tại vị trí
     * thực của shipper cũ. Nếu mọi đơn đều chưa pickup thì dùng cửa hàng của đơn báo sự cố.
     */
    private function takeoverPointForOrders(Collection $orders, Shipper $oldShipper, Order $incidentOrder, int $incidentShipmentId): ?array
    {
        $postPickup = $orders->first(function (Order $candidate) {
            return in_array(OrderStatus::normalize((string) $candidate->status), self::POST_PICKUP_STATUSES, true);
        });

        if ($postPickup instanceof Order) {
            $shipmentId = Schema::hasTable('shipments')
                ? (int) (DB::table('shipments')
                    ->where('order_id', $postPickup->id)
                    ->where('shipper_id', $oldShipper->id)
                    ->latest('id')
                    ->value('id') ?: 0)
                : 0;

            $point = $this->takeoverPoint($postPickup, $oldShipper, $shipmentId ?: $incidentShipmentId);
            if ($point) {
                return $point;
            }
        }

        return $this->takeoverPoint($incidentOrder, $oldShipper, $incidentShipmentId);
    }

    private function takeoverPoint(Order $order, Shipper $oldShipper, int $oldShipmentId): ?array
    {
        $status = OrderStatus::normalize((string) $order->status);

        if (in_array($status, self::PRE_PICKUP_STATUSES, true)) {
            $branch = $order->branch;
            if (! $branch || ! is_numeric($branch->latitude) || ! is_numeric($branch->longitude)) {
                return null;
            }

            return [
                'latitude' => (float) $branch->latitude,
                'longitude' => (float) $branch->longitude,
                'label' => $branch->name ?: 'Cửa hàng',
                'address' => $branch->address ?: 'Điểm lấy hàng',
                'handover_required' => false,
                'type' => 'branch',
            ];
        }

        if (! in_array($status, self::POST_PICKUP_STATUSES, true)) {
            return null;
        }

        $tracking = null;
        if (Schema::hasTable('shipment_tracking')) {
            $tracking = DB::table('shipment_tracking')
                ->where('shipment_id', $oldShipmentId)
                ->latest('recorded_at')
                ->latest('id')
                ->first(['latitude', 'longitude', 'recorded_at']);
        }

        $lat = $tracking && is_numeric($tracking->latitude)
            ? (float) $tracking->latitude
            : (is_numeric($oldShipper->current_latitude) ? (float) $oldShipper->current_latitude : null);
        $lng = $tracking && is_numeric($tracking->longitude)
            ? (float) $tracking->longitude
            : (is_numeric($oldShipper->current_longitude) ? (float) $oldShipper->current_longitude : null);

        if ($lat === null || $lng === null) {
            return null;
        }

        return [
            'latitude' => $lat,
            'longitude' => $lng,
            'label' => 'Điểm bàn giao với shipper cũ',
            'address' => 'Vị trí GPS cuối của '.$this->shipperName($oldShipper),
            'handover_required' => true,
            'type' => 'handover',
        ];
    }

    /**
     * @param Collection<int,Shipper> $candidates
     */
    private function chooseReplacement(Order $order, Collection $candidates, array $takeover): ?array
    {
        if ($candidates->isEmpty()) {
            return null;
        }

        $branchId = is_numeric($order->branch_id) ? (int) $order->branch_id : null;
        $handoverRequired = (bool) ($takeover['handover_required'] ?? false);
        $rows = collect();

        foreach ($candidates as $candidate) {
            $sameBranch = $branchId && (int) ($candidate->user?->branch_id ?? 0) === $branchId;
            if (! $sameBranch) {
                continue;
            }
            $hasGps = is_numeric($candidate->current_latitude) && is_numeric($candidate->current_longitude);

            // Trước pickup, shipper online thuộc đúng chi nhánh nhưng chưa có GPS được xem là đang trực tại quán.
            if (! $hasGps) {
                if (! $handoverRequired && $sameBranch) {
                    $rows->push([
                        'shipper' => $candidate,
                        'distance_m' => 0.0,
                        'duration_s' => 0.0,
                        'same_branch' => true,
                        'source' => 'same_branch_without_gps',
                    ]);
                }
                continue;
            }

            $fromLat = (float) $candidate->current_latitude;
            $fromLng = (float) $candidate->current_longitude;
            $haversineKm = $this->haversineKm(
                $fromLat,
                $fromLng,
                (float) $takeover['latitude'],
                (float) $takeover['longitude']
            );

            if ($haversineKm > self::REASSIGN_MAX_RADIUS_KM) {
                continue;
            }

            // Lọc bằng Haversine trước, xếp hạng cuối cùng bằng ETA đường thật.
            $route = $this->routing->route(
                $fromLat,
                $fromLng,
                (float) $takeover['latitude'],
                (float) $takeover['longitude']
            );

            // Haversine chỉ là prefilter. Xếp hạng cứu chuyến phải có ETA đường thật.
            if ((bool) ($route['fallback'] ?? true)) {
                continue;
            }

            $rows->push([
                'shipper' => $candidate,
                'distance_m' => (float) ($route['distance_m'] ?? ($haversineKm * 1000)),
                'duration_s' => (float) ($route['duration_s'] ?? PHP_FLOAT_MAX),
                'same_branch' => $sameBranch,
                'source' => (string) ($route['source'] ?? 'unknown'),
            ]);
        }

        if ($rows->isEmpty()) {
            return null;
        }

        return $rows
            ->sort(function (array $left, array $right) {
                $durationCompare = ($left['duration_s'] ?? PHP_FLOAT_MAX) <=> ($right['duration_s'] ?? PHP_FLOAT_MAX);
                if ($durationCompare !== 0) {
                    return $durationCompare;
                }

                if (($left['same_branch'] ?? false) !== ($right['same_branch'] ?? false)) {
                    return ($left['same_branch'] ?? false) ? -1 : 1;
                }

                return ($left['distance_m'] ?? PHP_FLOAT_MAX) <=> ($right['distance_m'] ?? PHP_FLOAT_MAX);
            })
            ->first();
    }

    private function createReplacementShipment(
        Order $order,
        Shipper $newShipper,
        Shipper $oldShipper,
        int $oldShipmentId,
        array $takeover
    ): ?int {
        if (! Schema::hasTable('shipments')) {
            return null;
        }

        $handoverRequired = (bool) ($takeover['handover_required'] ?? false);
        $note = $handoverRequired
            ? json_encode([
                'type' => 'incident_handover',
                'handover' => [
                    'latitude' => (float) $takeover['latitude'],
                    'longitude' => (float) $takeover['longitude'],
                    'label' => (string) ($takeover['label'] ?? 'Điểm bàn giao'),
                    'address' => (string) ($takeover['address'] ?? ''),
                    'old_shipper_id' => (int) $oldShipper->id,
                    'old_shipment_id' => $oldShipmentId,
                    'resume_order_status' => OrderStatus::normalize((string) $order->status),
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : 'Chuyến được tự động điều phối lại sau sự cố của shipper cũ.';

        return (int) DB::table('shipments')->insertGetId([
            'order_id' => $order->id,
            'shipper_id' => $newShipper->id,
            'status' => $handoverRequired ? 'handover_required' : 'assigned',
            'assigned_at' => now(),
            'note' => $note,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function decodeHandoverNote(?string $note): ?array
    {
        if (! is_string($note) || trim($note) === '') {
            return null;
        }

        $decoded = json_decode($note, true);
        $handover = is_array($decoded) ? ($decoded['handover'] ?? null) : null;

        if (! is_array($handover)
            || ! is_numeric($handover['latitude'] ?? null)
            || ! is_numeric($handover['longitude'] ?? null)) {
            return null;
        }

        return [
            'latitude' => (float) $handover['latitude'],
            'longitude' => (float) $handover['longitude'],
            'label' => (string) ($handover['label'] ?? 'Điểm bàn giao'),
            'address' => (string) ($handover['address'] ?? 'Vị trí bàn giao'),
            'old_shipper_id' => isset($handover['old_shipper_id']) ? (int) $handover['old_shipper_id'] : null,
            'old_shipment_id' => isset($handover['old_shipment_id']) ? (int) $handover['old_shipment_id'] : null,
            'resume_order_status' => (string) ($handover['resume_order_status'] ?? ''),
            'type' => 'handover',
        ];
    }

    private function shipmentStatusForOrder(Order $order): string
    {
        return match (OrderStatus::normalize((string) $order->status)) {
            OrderStatus::SHIPPER_PICKED_UP => 'picked_up',
            OrderStatus::DELIVERING => 'delivering',
            default => 'accepted',
        };
    }

    private function addHistory(?int $shipmentId, string $status, ?string $description = null): void
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

    private function shipperName(Shipper $shipper): string
    {
        return $shipper->user?->name ?: $shipper->code ?: 'Shipper #'.$shipper->id;
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
}
