<?php

namespace App\Http\Controllers\Shipper;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\DeliveryOrderMessage;
use App\Models\Shipper;
use App\Services\DeliveryRoutingService;
use App\Services\NavigationTtsService;
use App\Services\ShipperIncidentService;
use App\Services\ShipperBundleService;
use App\Services\ShipperDispatchService;
use App\Services\ShipperReturnService;
use App\Support\AddressLearning;
use App\Support\OrderStatus;
use App\Support\RealtimeOrderNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ShipController extends Controller
{
    private const ACTIVE_ORDER_STATUSES = [
        OrderStatus::CONFIRMED,
        OrderStatus::PREPARING,
        OrderStatus::READY_FOR_DELIVERY,
        OrderStatus::SHIPPER_PICKED_UP,
        OrderStatus::DELIVERING,
    ];


    private const ISSUE_REASONS = [
        'vehicle_problem' => 'Xe gặp sự cố',
        'emergency' => 'Có việc khẩn cấp / không thể tiếp tục',
        'health_problem' => 'Sức khỏe không đảm bảo',
        'phone_problem' => 'Điện thoại / GPS gặp sự cố',
        'store_problem' => 'Có vấn đề khi nhận hàng tại cửa hàng',
        'customer_unreachable' => 'Không liên hệ được khách',
        'address_problem' => 'Không tìm thấy điểm giao',
        'customer_changed_location' => 'Khách đổi điểm nhận',
        'customer_refused' => 'Khách từ chối nhận',
        'damaged_order' => 'Hàng bị đổ/hỏng',
        'other' => 'Sự cố khác',
    ];

    private const ARRIVAL_ACCURACY_MAX_M = 120.0;
    private const ARRIVAL_RADIUS_M = 50.0;
    private const ARRIVAL_SINGLE_POINT_ACCURACY_M = 50.0;
    private const ARRIVAL_FUZZY_REQUIRED_POINTS = 2;
    private const ARRIVAL_FUZZY_WINDOW_SECONDS = 15;

    private function getShipper(): Shipper
    {
        $user = Auth::user();

        if (! $user) {
            abort(403, 'Bạn chưa đăng nhập.');
        }

        return Shipper::where('user_id', $user->id)->firstOrFail();
    }

    private function deliveryOrderQuery(Shipper $shipper)
    {
        return Order::query()
            ->with(['user', 'address', 'branch'])
            ->where('shipper_id', $shipper->id)
            ->whereIn('status', [
                OrderStatus::CONFIRMED,
                OrderStatus::PREPARING,
                OrderStatus::READY_FOR_DELIVERY,
                OrderStatus::SHIPPER_PICKED_UP,
                OrderStatus::DELIVERING,
                OrderStatus::DELIVERED,
            ])
            ->where(function ($query) {
                $query->whereNull('fulfillment_type')
                    ->orWhere('fulfillment_type', 'delivery');
            });
    }

    private function activeDeliveryOrderQuery(Shipper $shipper)
    {
        // Nhiệm vụ hiện tại chỉ gồm các đơn còn phải xử lý.
        // Đã giao/hoàn thành được xem ở lịch sử, không còn là nhiệm vụ.
        return Order::query()
            ->with(['user', 'address', 'branch'])
            ->where('shipper_id', $shipper->id)
            ->whereIn('status', self::ACTIVE_ORDER_STATUSES)
            ->where(function ($query) {
                $query->whereNull('fulfillment_type')
                    ->orWhere('fulfillment_type', 'delivery');
            });
    }

    /**
     * Không còn bước shipper bấm "Nhận đơn".
     * Đơn được coi là đã tiếp nhận ngay khi engine điều phối gán shipper.
     *
     * @param iterable<Order> $orders
     * @return array<int>
     */
    private function pendingAcceptanceOrderIds(iterable $orders, Shipper $shipper): array
    {
        return [];
    }

    /**
     * Poll nhẹ để shipper đang mở trang nhận được nhiệm vụ hệ thống vừa gán
     * mà không phải tự F5. Không trả về đơn của shipper khác.
     */
    public function assignmentPulse(): JsonResponse
    {
        $shipper = $this->getShipper();
        $ordersQuery = $this->deliveryOrderQuery($shipper)
            ->whereIn('status', self::ACTIVE_ORDER_STATUSES)
            ->limit(10);

        if (Schema::hasTable('shipments')) {
            $latestShipmentAssignments = DB::table('shipments')
                ->select('order_id', DB::raw('MAX(assigned_at) as latest_assigned_at'))
                ->where('shipper_id', $shipper->id)
                ->groupBy('order_id');

            $ordersQuery
                ->leftJoinSub($latestShipmentAssignments, 'latest_shipments', function ($join) {
                    $join->on('latest_shipments.order_id', '=', 'orders.id');
                })
                ->select('orders.*', 'latest_shipments.latest_assigned_at')
                ->orderByDesc(DB::raw('COALESCE(latest_shipments.latest_assigned_at, orders.created_at)'));
        } else {
            $ordersQuery->latest('created_at');
        }

        $orders = $ordersQuery->get();

        $pendingIds = $this->pendingAcceptanceOrderIds($orders, $shipper);
        $latest = $orders->first();
        $assignedAt = $latest?->latest_assigned_at ?? null;

        if ($latest && Schema::hasTable('shipments')) {
            $assignedAt ??= DB::table('shipments')
                ->where('order_id', $latest->id)
                ->where('shipper_id', $shipper->id)
                ->latest('assigned_at')
                ->value('assigned_at');
        }
        $assignmentTimestamp = $assignedAt ? strtotime((string) $assignedAt) : optional($latest?->created_at)->timestamp;

        return response()->json([
            'success' => true,
            'pending_count' => count($pendingIds),
            'order' => $latest ? [
                'id' => (int) $latest->id,
                'assignment_key' => ((int) $latest->id) . ':' . ($assignmentTimestamp ?: 0),
                'assignment_ts' => $assignmentTimestamp,
                'code' => $latest->displayCode(),
                'status' => OrderStatus::normalize((string) $latest->status),
                'status_label' => OrderStatus::label((string) $latest->status),
                'show_url' => route('shipper.orders.show', $latest->id),
                'map_url' => route('shipper.map', ['id' => $latest->id]),
            ] : null,
        ]);
    }

    public function dashboard()
    {
        $user = Auth::user();
        $shipperInfo = Shipper::with(['returningBranch', 'stationBranch', 'user.branch'])->where('user_id', $user->id)->first();

        if (! $shipperInfo) {
            Auth::logout();

            return redirect()->route('login')->withErrors([
                'email' => 'Tài khoản này chưa được tạo hồ sơ shipper.',
            ]);
        }

        $shipperUser = $user;

        $todayOrders = Order::where('shipper_id', $shipperInfo->id)
            ->whereDate('updated_at', today())
            ->count();

        $shippingOrders = Order::where('shipper_id', $shipperInfo->id)
            ->whereIn('status', self::ACTIVE_ORDER_STATUSES)
            ->count();

        $completedOrders = Order::where('shipper_id', $shipperInfo->id)
            ->whereIn('status', [OrderStatus::DELIVERED, OrderStatus::COMPLETED])
            ->count();

        $income = Order::where('shipper_id', $shipperInfo->id)
            ->whereIn('status', [OrderStatus::DELIVERED, OrderStatus::COMPLETED])
            ->sum('shipping_fee');

        $codService = app(\App\Services\ShipperCodService::class);
        $codDue = $codService->pendingAmount($shipperInfo);
        $codDueOrderCount = $codService->pendingCount($shipperInfo);
        $codPendingItems = $codService->pendingItems($shipperInfo, 5);

        $orders = $this->activeDeliveryOrderQuery($shipperInfo)
            ->latest('updated_at')
            ->paginate(10);
        $pendingAcceptanceOrderIds = $this->pendingAcceptanceOrderIds($orders->getCollection(), $shipperInfo);
        $returnPlan = app(ShipperReturnService::class)->currentReturn($shipperInfo);
        $bundleTrip = app(ShipperBundleService::class)->activeTripForShipper($shipperInfo);
        $bundleLabel = app(ShipperBundleService::class)->tripLabel($bundleTrip);

        return view('shipper.dashboard', compact(
            'todayOrders',
            'shippingOrders',
            'completedOrders',
            'income',
            'codDue',
            'codDueOrderCount',
            'codPendingItems',
            'shipperUser',
            'shipperInfo',
            'orders',
            'pendingAcceptanceOrderIds',
            'returnPlan',
            'bundleTrip',
            'bundleLabel'
        ));
    }

    public function orders()
    {
        $shipperInfo = $this->getShipper();
        $orders = $this->activeDeliveryOrderQuery($shipperInfo)
            ->latest('updated_at')
            ->paginate(10);
        $pendingAcceptanceOrderIds = $this->pendingAcceptanceOrderIds($orders->getCollection(), $shipperInfo);
        $bundleTrip = app(ShipperBundleService::class)->activeTripForShipper($shipperInfo);
        $bundleLabel = app(ShipperBundleService::class)->tripLabel($bundleTrip);
        $todayOrders = Order::where('shipper_id', $shipperInfo->id)->whereDate('updated_at', today())->count();
        $activeOrders = Order::where('shipper_id', $shipperInfo->id)->whereIn('status', self::ACTIVE_ORDER_STATUSES)->count();
        $codService = app(\App\Services\ShipperCodService::class);
        $codDue = $codService->pendingAmount($shipperInfo);
        $codDueOrderCount = $codService->pendingCount($shipperInfo);

        return view('shipper.orders.index', compact(
            'orders', 'shipperInfo', 'pendingAcceptanceOrderIds', 'bundleTrip', 'bundleLabel',
            'todayOrders', 'activeOrders', 'codDue', 'codDueOrderCount'
        ));
    }

    public function showOrder($id)
    {
        $shipperInfo = $this->getShipper();

        $order = $this->deliveryOrderQuery($shipperInfo)
            ->where('id', $id)
            ->firstOrFail();

        $pendingAcceptanceOrderIds = $this->pendingAcceptanceOrderIds([$order], $shipperInfo);
        $isAccepted = ! in_array((int) $order->id, $pendingAcceptanceOrderIds, true);
        $incidentService = app(ShipperIncidentService::class);
        $pendingIssue = $incidentService->pendingIncident($order);
        $handoverContext = $incidentService->pendingHandoverForOrder($order, $shipperInfo);
        $bundleService = app(ShipperBundleService::class);
        $bundleTrip = $bundleService->activeTripForOrder($order);
        $bundleLabel = $bundleService->tripLabel($bundleTrip);
        $bundleCurrentStop = $bundleService->currentPhysicalStopForShipper($shipperInfo);

        return view('shipper.orders.show', [
            'order' => $order,
            'shipperInfo' => $shipperInfo,
            'isAccepted' => $isAccepted,
            'issueReasons' => self::ISSUE_REASONS,
            'pendingIssue' => $pendingIssue,
            'handoverContext' => $handoverContext,
            'bundleTrip' => $bundleTrip,
            'bundleLabel' => $bundleLabel,
            'bundleCurrentStop' => $bundleCurrentStop,
        ]);
    }

    /**
     * "Nhận đơn" chỉ là xác nhận đã thấy và tiếp nhận nhiệm vụ.
     * Hệ thống đã gán order.shipper_id ngay khi quán xác nhận đơn.
     */
    public function acceptOrder($id)
    {
        $shipper = $this->getShipper();

        $result = DB::transaction(function () use ($id, $shipper) {
            $lockedShipper = Shipper::whereKey($shipper->id)->lockForUpdate()->firstOrFail();

            $order = Order::whereKey($id)->lockForUpdate()->first();
            if (! $order) {
                return ['error' => 'Không tìm thấy đơn hàng.'];
            }

            if (($order->fulfillment_type ?? 'delivery') !== 'delivery') {
                return ['error' => 'Đây không phải đơn giao tận nơi.'];
            }

            if ((int) $order->shipper_id !== (int) $lockedShipper->id) {
                return ['error' => 'Đơn này chưa được hệ thống giao cho bạn.'];
            }

            $shipmentId = $this->latestShipmentId($order->id, $lockedShipper->id);
            $incidentService = app(ShipperIncidentService::class);
            $handoverContext = $incidentService->pendingHandoverForOrder($order, $lockedShipper);

            $acceptStatuses = [
                OrderStatus::CONFIRMED,
                OrderStatus::PREPARING,
                OrderStatus::READY_FOR_DELIVERY,
            ];
            if ($handoverContext) {
                $acceptStatuses[] = OrderStatus::SHIPPER_PICKED_UP;
                $acceptStatuses[] = OrderStatus::DELIVERING;
            }

            if (! in_array(OrderStatus::normalize((string) $order->status), $acceptStatuses, true)) {
                return ['error' => 'Đơn không còn ở giai đoạn xác nhận nhận nhiệm vụ.'];
            }

            $lockedShipper->forceFill(['status' => 'busy'])->save();

            if (! $shipmentId) {
                // Tương thích đơn cũ trước pickup: nếu order đã gán nhưng chưa có shipment thì tạo bổ sung.
                if ($handoverContext) {
                    return ['error' => 'Không tìm thấy dữ liệu chuyến bàn giao. Vui lòng báo quản lý kiểm tra lại.'];
                }
                $shipmentId = $this->createShipment($order->id, $lockedShipper->id);
            }

            if ($shipmentId && ! $this->hasShipmentHistoryStatus($shipmentId, 'accepted')) {
                if (! $handoverContext) {
                    $this->updateShipment($shipmentId, [
                        'status' => 'accepted',
                        'note' => 'Shipper đã xác nhận tiếp nhận nhiệm vụ được hệ thống điều phối.',
                    ]);
                }
                $this->addShipmentHistory(
                    $shipmentId,
                    'accepted',
                    $handoverContext
                        ? 'Shipper bấm Nhận đơn cứu chuyến và xác nhận sẽ tới điểm bàn giao của shipper cũ.'
                        : 'Shipper bấm Nhận đơn và xác nhận đã tiếp nhận nhiệm vụ.'
                );
            }

            // Nếu đây là chuyến ghép vừa được cứu, một lần bấm Nhận đơn là xác nhận
            // tiếp nhận TOÀN BỘ chuyến. Tránh đơn anh em trong bundle vẫn bị coi là chưa accepted.
            $bundleTrip = app(ShipperBundleService::class)->activeTripForShipper($lockedShipper);
            if ($bundleTrip && in_array((int) $order->id, array_map('intval', $bundleTrip['order_ids'] ?? []), true)) {
                $siblingOrders = Order::query()
                    ->whereIn('id', $bundleTrip['order_ids'])
                    ->where('shipper_id', $lockedShipper->id)
                    ->where('id', '!=', $order->id)
                    ->get();

                foreach ($siblingOrders as $siblingOrder) {
                    $siblingShipmentId = $this->latestShipmentId($siblingOrder->id, $lockedShipper->id);
                    if (! $siblingShipmentId || $this->hasShipmentHistoryStatus($siblingShipmentId, 'accepted')) {
                        continue;
                    }

                    $siblingHandover = $incidentService->pendingHandoverForOrder($siblingOrder, $lockedShipper);
                    if (! $siblingHandover) {
                        $this->updateShipment($siblingShipmentId, [
                            'status' => 'accepted',
                            'note' => 'Shipper đã xác nhận tiếp nhận toàn bộ chuyến ghép được điều phối.',
                        ]);
                    }
                    $this->addShipmentHistory(
                        $siblingShipmentId,
                        'accepted',
                        $siblingHandover
                            ? 'Shipper đã nhận toàn bộ chuyến cứu; đơn này sẽ được nhận bàn giao tại điểm của shipper cũ.'
                            : 'Shipper đã nhận toàn bộ chuyến ghép; đơn này được xác nhận cùng nhiệm vụ chính.'
                    );
                }
            }

            return ['order' => $order, 'handover' => (bool) $handoverContext];
        });

        if (isset($result['error'])) {
            return back()->with('error', $result['error']);
        }

        $order = $result['order'];

        return redirect()->route('shipper.map', ['id' => $order->id])
            ->with(
                'success',
                ! empty($result['handover'])
                    ? 'Đã nhận nhiệm vụ cứu chuyến. Hãy tới điểm bàn giao với shipper cũ trước khi tiếp tục giao khách.'
                    : 'Đã xác nhận nhận đơn. Bạn có thể di chuyển tới cửa hàng trong lúc quán đang pha chế.'
            );
    }

    /**
     * Legacy guard: shipper nội bộ Chill Drink không có quyền hủy/từ chối chuyến.
     * Route cũ được giữ để request/cache cũ cũng không thể tháo shipper khỏi đơn.
     */
    public function cancelOrder(Request $request, $id)
    {
        $shipper = $this->getShipper();

        $order = Order::whereKey($id)
            ->where('shipper_id', $shipper->id)
            ->firstOrFail();

        return back()->with(
            'error',
            'Shipper Chill Drink không được từ chối hoặc hủy chuyến. Nếu không thể tiếp tục, hãy dùng chức năng Báo sự cố để cửa hàng/admin hỗ trợ.'
        );
    }

    /**
     * Shipper đã tới cửa hàng và cầm hàng.
     */
    public function pickedUpOrder(Request $request, $id)
    {
        $validated = $request->validate([
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:5000'],
        ]);

        $shipper = $this->getShipper();
        $bundleService = app(ShipperBundleService::class);

        $requestedOrder = Order::with('branch')
            ->whereKey($id)
            ->where('shipper_id', $shipper->id)
            ->firstOrFail();

        if (! $bundleService->isCurrentStopAction($shipper, $requestedOrder, 'pickup')) {
            return back()->with('error', 'Chưa tới lượt lấy đơn này. Hãy hoàn tất điểm hiện tại theo đúng thứ tự trên bản đồ trước.');
        }

        $pickupOrders = Order::with('branch')
            ->whereKey((int) $requestedOrder->id)
            ->where('shipper_id', $shipper->id)
            ->get();

        if ($pickupOrders->count() !== 1) {
            return back()->with('error', 'Dữ liệu chuyến ghép chưa đồng bộ. Vui lòng tải lại chuyến.');
        }

        $notReady = $pickupOrders->filter(fn (Order $order) => OrderStatus::normalize((string) $order->status) !== OrderStatus::READY_FOR_DELIVERY);
        if ($notReady->isNotEmpty()) {
            return back()->with('error', 'Còn '.$notReady->count().' đơn tại cùng điểm lấy chưa pha chế xong. Hãy chờ đủ hàng để chỉ ghé quán một lần.');
        }

        $evidence = [];
        foreach ($pickupOrders as $pickupOrder) {
            $shipmentId = $this->latestShipmentId($pickupOrder->id, $shipper->id);
            if (! $shipmentId) {
                return back()->with('error', 'Dữ liệu chuyến giao chưa đồng bộ. Hãy tải lại chuyến.');
            }

            if (isset($validated['latitude'], $validated['longitude'])
                && is_numeric($validated['latitude'])
                && is_numeric($validated['longitude'])) {
                $this->recordArrivalFromPoint(
                    $pickupOrder,
                    $shipper,
                    $shipmentId,
                    (float) $validated['latitude'],
                    (float) $validated['longitude'],
                    isset($validated['accuracy']) ? (float) $validated['accuracy'] : 9999.0
                );
            }

            $arrival = $this->arrivalEvidenceState($pickupOrder, $shipper);
            if (! $arrival['verified']) {
                return back()->with('error', 'Bạn chưa tới đúng vị trí cửa hàng. Hãy bật GPS và đi tới cửa hàng để mở thao tác lấy hàng.');
            }

            $evidence[(int) $pickupOrder->id] = [
                'shipment_id' => $shipmentId,
                'arrival' => $arrival,
            ];
        }

        DB::transaction(function () use ($pickupOrders, $evidence) {
            foreach ($pickupOrders as $pickupOrder) {
                $pickupOrder->forceFill([
                    'status' => OrderStatus::SHIPPER_PICKED_UP,
                    'status_changed_at' => now(),
                    'status_changed_by' => auth()->id(),
                ])->save();

                $shipmentId = (int) $evidence[(int) $pickupOrder->id]['shipment_id'];
                $arrival = $evidence[(int) $pickupOrder->id]['arrival'];

                $this->updateShipment($shipmentId, [
                    'status' => 'picked_up',
                    'picked_up_at' => now(),
                ]);
                $verifiedAt = $arrival['verified_at'] ? ' lúc '.$arrival['verified_at'] : '';
                $this->addShipmentHistory(
                    $shipmentId,
                    'picked_up',
                    'Shipper xác nhận đã lấy hàng tại điểm lấy hiện tại. GPS đã ghi nhận shipper tới cửa hàng'.$verifiedAt.'.'
                );
            }
        });

        foreach ($pickupOrders as $pickupOrder) {
            RealtimeOrderNotifier::orderStatusUpdated($pickupOrder->fresh());
        }

        $requestedOrder = $requestedOrder->fresh(['branch', 'address']);
        $autoStartedOrder = $this->autoStartDeliveryWhenDue($shipper, $requestedOrder);
        $bundleNext = $bundleService->nextStopForShipper($shipper);
        $pickedOrder = $pickupOrders->first();
        $branchName = $pickedOrder?->branch?->name ?: 'cửa hàng';
        $message = 'Đã xác nhận lấy đơn '.$pickedOrder?->displayCode().' tại '.$branchName.'.';

        if ($bundleNext) {
            return redirect()->route('shipper.map', ['id' => (int) $bundleNext['order_id']])
                ->with('success', $message.' '.($autoStartedOrder
                    ? 'Hệ thống đang dẫn thẳng sang điểm giao tiếp theo, không cần vuốt Bắt đầu giao.'
                    : 'Hệ thống đã mở đúng điểm tiếp theo của chuyến.'));
        }

        return redirect()->route('shipper.map', ['id' => $requestedOrder->id])
            ->with('success', $message.' '.($autoStartedOrder
                ? 'Đơn đã tự chuyển sang trạng thái đang giao.'
                : ''));
    }

    private function autoStartDeliveryWhenDue(Shipper $shipper, ?Order $fallbackOrder = null): ?Order
    {
        $bundleService = app(ShipperBundleService::class);
        $incidentService = app(ShipperIncidentService::class);
        $candidateOrderId = null;

        $bundleNext = $bundleService->nextStopForShipper($shipper);
        if ($bundleNext && ($bundleNext['type'] ?? null) === 'delivery') {
            $candidateOrderId = (int) ($bundleNext['order_id'] ?? 0);
        } elseif (
            $fallbackOrder
            && (int) ($fallbackOrder->shipper_id ?? 0) === (int) $shipper->id
            && OrderStatus::normalize((string) $fallbackOrder->status) === OrderStatus::SHIPPER_PICKED_UP
        ) {
            $candidateOrderId = (int) $fallbackOrder->id;
        }

        if ($candidateOrderId <= 0) {
            return null;
        }

        $candidateOrder = Order::with(['branch', 'address'])
            ->whereKey($candidateOrderId)
            ->where('shipper_id', $shipper->id)
            ->where('status', OrderStatus::SHIPPER_PICKED_UP)
            ->first();

        if (! $candidateOrder) {
            return null;
        }

        if (! $bundleService->isCurrentStopAction($shipper, $candidateOrder, 'delivery')) {
            return null;
        }

        if ($incidentService->pendingHandoverForOrder($candidateOrder, $shipper)) {
            return null;
        }

        $startedOrder = DB::transaction(function () use ($candidateOrderId, $shipper) {
            $lockedOrder = Order::with(['branch', 'address'])
                ->whereKey($candidateOrderId)
                ->where('shipper_id', $shipper->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedOrder || OrderStatus::normalize((string) $lockedOrder->status) !== OrderStatus::SHIPPER_PICKED_UP) {
                return null;
            }

            $lockedOrder->forceFill([
                'status' => OrderStatus::DELIVERING,
                'status_changed_at' => now(),
                'status_changed_by' => auth()->id(),
            ])->save();

            $shipmentId = $this->latestShipmentId($lockedOrder->id, $shipper->id);
            if ($shipmentId) {
                $this->updateShipment($shipmentId, ['status' => 'delivering']);
                if (! $this->hasShipmentHistoryStatus($shipmentId, 'delivering')) {
                    $this->addShipmentHistory(
                        $shipmentId,
                        'delivering',
                        'Hệ thống tự chuyển sang chặng giao sau khi đã hoàn tất bước lấy hàng của điểm hiện tại.'
                    );
                }
            }

            return $lockedOrder->fresh(['branch', 'address']);
        });

        if ($startedOrder) {
            RealtimeOrderNotifier::orderStatusUpdated($startedOrder);
        }

        return $startedOrder;
    }

    /**
     * Bắt đầu chặng giao tới khách.
     */
    public function startDelivery($id)
    {
        $shipper = $this->getShipper();

        $order = Order::whereKey($id)
            ->where('shipper_id', $shipper->id)
            ->firstOrFail();

        if (OrderStatus::normalize((string) $order->status) === OrderStatus::DELIVERING) {
            return redirect()->route('shipper.map', ['id' => $order->id])
                ->with('success', 'Đơn này đã ở trạng thái đang giao.');
        }

        if (OrderStatus::normalize((string) $order->status) !== OrderStatus::SHIPPER_PICKED_UP) {
            return back()->with('error', 'Đơn này không còn ở bước chờ bắt đầu giao.');
        }

        $bundleService = app(ShipperBundleService::class);
        if (! $bundleService->isCurrentStopAction($shipper, $order, 'delivery')) {
            return back()->with('error', 'Chưa tới lượt giao đơn này. Hãy hoàn tất tất cả điểm lấy và đơn giao phía trước theo thứ tự hệ thống.');
        }

        if (app(ShipperIncidentService::class)->pendingHandoverForOrder($order, $shipper)) {
            return back()->with('error', 'Bạn phải tới điểm bàn giao và bấm Đã nhận bàn giao trước khi bắt đầu giao.');
        }

        $order->forceFill([
            'status' => OrderStatus::DELIVERING,
            'status_changed_at' => now(),
            'status_changed_by' => auth()->id(),
        ])->save();

        $shipmentId = $this->latestShipmentId($order->id, $shipper->id);
        $this->updateShipment($shipmentId, ['status' => 'delivering']);
        $this->addShipmentHistory($shipmentId, 'delivering', 'Shipper bắt đầu giao hàng tới khách.');

        RealtimeOrderNotifier::orderStatusUpdated($order);

        $bundleNext = app(ShipperBundleService::class)->nextStopForShipper($shipper);
        if ($bundleNext && (int) ($bundleNext['order_id'] ?? 0) !== (int) $order->id) {
            return redirect()->route('shipper.map', ['id' => (int) $bundleNext['order_id']])
                ->with('success', 'Đã bắt đầu giao. Chuyến ghép đang dẫn bạn tới điểm tiếp theo theo phương án tối ưu.');
        }

        return redirect()->route('shipper.map', ['id' => $order->id])
            ->with('success', 'Đang giao hàng. Tuyến đường tới khách đã được cập nhật.');
    }

    public function map($id = null)
    {
        $shipper = $this->getShipper();
        $bundleService = app(ShipperBundleService::class);
        $bundleTrip = $bundleService->activeTripForShipper($shipper);
        $bundleNextStop = $bundleService->nextStopForShipper($shipper);

        if ($bundleNextStop && (!$id || (int) $id !== (int) ($bundleNextStop['order_id'] ?? 0))) {
            return redirect()->route('shipper.map', ['id' => (int) $bundleNextStop['order_id']])
                ->with('info', 'Chuyến ghép đang tự chuyển tới điểm tiếp theo theo tuyến đã tối ưu.');
        }

        $query = Order::with(['user', 'address', 'branch'])
            ->where('shipper_id', $shipper->id)
            ->whereIn('status', [
                                OrderStatus::CONFIRMED,
                                OrderStatus::PREPARING,
                                OrderStatus::READY_FOR_DELIVERY,
                                OrderStatus::SHIPPER_PICKED_UP,
                                OrderStatus::DELIVERING,
                            ]);

        if ($id) {
            $query->where('id', $id);
        } else {
            $query->whereIn('status', self::ACTIVE_ORDER_STATUSES);
        }

        $order = $query->latest('updated_at')->first();

        if (! $order) {
            return redirect()->route('shipper.orders')
                ->with('error', 'Không có chuyến đang hoạt động để hiển thị bản đồ.');
        }

        $autoStartedOrder = $this->autoStartDeliveryWhenDue($shipper, $order);
        if ($autoStartedOrder && (int) $autoStartedOrder->id === (int) $order->id) {
            $order = $autoStartedOrder;
        } else {
            $order = $order->fresh(['user', 'address', 'branch']);
        }

        $shipmentId = $this->latestShipmentId($order->id, $shipper->id);
        $isAccepted = (bool) $shipmentId;
        $incidentService = app(ShipperIncidentService::class);
        $pendingIssue = $incidentService->pendingIncident($order);
        $handoverContext = $incidentService->pendingHandoverForOrder($order, $shipper);
        $customerArrivalConfirmed = $shipmentId
            ? $this->hasShipmentHistoryStatus($shipmentId, 'arrived_customer_confirmed')
            : false;
        // Đọc bằng chứng GPS đã lưu ngay khi render trang. Nhờ vậy shipper đã từng
        // vào geofence vẫn bấm được nút sau đó, kể cả lúc này GPS yếu/đã rời điểm.
        $arrivalEvidence = $this->arrivalEvidenceState($order, $shipper);

        $requiresCodCollection = strtolower((string) $order->payment_method) === 'cod'
            && strtolower((string) $order->payment_status) !== 'paid';
        $amountToCollect = max(0, (int) ($order->total ?? $order->total_price ?? 0));

        return view('shipper.map', [
            'order' => $order,
            'shipper' => $shipper,
            'issueReasons' => self::ISSUE_REASONS,
            'isAccepted' => $isAccepted,
            'customerArrivalConfirmed' => $customerArrivalConfirmed,
            'arrivalEvidence' => $arrivalEvidence,
            'requiresCodCollection' => $requiresCodCollection,
            'amountToCollect' => $amountToCollect,
            'pendingIssue' => $pendingIssue,
            'handoverContext' => $handoverContext,
            'bundleTrip' => $bundleTrip,
            'bundleLabel' => $bundleService->tripLabel($bundleTrip),
            'bundleNextStop' => $bundleNextStop,
        ]);
    }

    /**
     * API tuyến đường cho CHÍNH map shipper hiện tại.
     * Client chỉ gửi GPS hiện tại; server tự quyết định đích theo trạng thái đơn.
     */
    public function routeData(Request $request, $id, DeliveryRoutingService $routing): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:5000'],
        ]);

        $shipper = $this->getShipper();
        $order = Order::with(['branch', 'address'])
            ->whereKey($id)
            ->where('shipper_id', $shipper->id)
            ->firstOrFail();

        $bundleNext = app(ShipperBundleService::class)->nextStopForShipper($shipper);
        if ($bundleNext && (int) ($bundleNext['order_id'] ?? 0) !== (int) $order->id) {
            return response()->json([
                'success' => true,
                'redirect_order_id' => (int) $bundleNext['order_id'],
                'redirect_url' => route('shipper.map', ['id' => (int) $bundleNext['order_id']]),
                'message' => 'Chuyến ghép vừa chuyển sang điểm tiếp theo.',
            ]);
        }

        $autoStartedOrder = $this->autoStartDeliveryWhenDue($shipper, $order);
        if ($autoStartedOrder && (int) $autoStartedOrder->id === (int) $order->id) {
            $order = $autoStartedOrder;
        } else {
            $order = $order->fresh(['branch', 'address']);
        }

        $status = OrderStatus::normalize((string) $order->status);
        $target = $this->targetForOrder($order, $status, $shipper);
        $bundleTrip = app(ShipperBundleService::class)->activeTripForShipper($shipper);
        $bundleRoute = $bundleTrip && in_array((int) $order->id, array_map('intval', $bundleTrip['order_ids'] ?? []), true)
            ? $this->bundleRouteForMap($bundleTrip, $shipper, (float) $validated['latitude'], (float) $validated['longitude'], $routing)
            : null;

        if (! $target) {
            return response()->json([
                'success' => false,
                'message' => $status === OrderStatus::READY_FOR_DELIVERY
                    ? 'Chi nhánh chưa có tọa độ để dẫn đường.'
                    : 'Địa chỉ khách chưa có tọa độ để dẫn đường.',
            ], 422);
        }

        $route = isset($bundleRoute['main_route']) && is_array($bundleRoute['main_route'])
            ? $bundleRoute['main_route']
            : $routing->route(
                (float) $validated['latitude'],
                (float) $validated['longitude'],
                (float) $target['latitude'],
                (float) $target['longitude'],
                ['prefer_local_roads' => true]
            );

        return response()->json([
            'success' => true,
            'order_id' => $order->id,
            'status' => $status,
            'target' => $target,
            'current' => [
                'latitude' => (float) $validated['latitude'],
                'longitude' => (float) $validated['longitude'],
                'accuracy' => isset($validated['accuracy']) ? (float) $validated['accuracy'] : null,
            ],
            'arrival' => $this->arrivalState(
                $order,
                $shipper,
                (float) $validated['latitude'],
                (float) $validated['longitude'],
                isset($validated['accuracy']) ? (float) $validated['accuracy'] : 9999.0
            ),
            'route' => $route,
            'bundle_route' => $bundleRoute,
        ]);
    }

    /**
     * Shipper thay thế xác nhận đã nhận hàng từ shipper cũ sau khi GPS đã tới điểm bàn giao.
     * Trạng thái nghiệp vụ của order được giữ nguyên; chỉ kết thúc bước handover của shipment mới.
     */
    public function confirmHandover(Request $request, $id)
    {
        $validated = $request->validate([
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:5000'],
        ]);

        $shipper = $this->getShipper();
        $order = Order::with(['branch', 'address'])
            ->whereKey($id)
            ->where('shipper_id', $shipper->id)
            ->whereIn('status', [OrderStatus::SHIPPER_PICKED_UP, OrderStatus::DELIVERING])
            ->firstOrFail();

        $shipmentId = $this->latestShipmentId($order->id, $shipper->id);
        if (! $shipmentId) {
            return back()->with('error', 'Dữ liệu chuyến bàn giao chưa đồng bộ. Vui lòng tải lại trang.');
        }

        $incidentService = app(ShipperIncidentService::class);
        $handover = $incidentService->pendingHandoverForOrder($order, $shipper);
        if (! $handover) {
            return back()->with('error', 'Chuyến này không còn yêu cầu bàn giao hoặc đã bàn giao xong.');
        }

        if (isset($validated['latitude'], $validated['longitude'])
            && is_numeric($validated['latitude'])
            && is_numeric($validated['longitude'])) {
            $this->recordArrivalFromPoint(
                $order,
                $shipper,
                $shipmentId,
                (float) $validated['latitude'],
                (float) $validated['longitude'],
                isset($validated['accuracy']) ? (float) $validated['accuracy'] : 9999.0
            );
        }

        $arrival = $this->arrivalEvidenceState($order, $shipper);
        if (($arrival['event'] ?? null) !== 'arrived_handover' || ! ($arrival['verified'] ?? false)) {
            return back()->with('error', 'Bạn chưa tới đúng điểm bàn giao. Hãy bật GPS và tới vị trí shipper cũ để mở nút Đã nhận bàn giao.');
        }

        if (! $this->hasShipmentHistoryStatus($shipmentId, 'handover_received')) {
            $this->addShipmentHistory(
                $shipmentId,
                'handover_received',
                'Shipper thay thế xác nhận đã nhận bàn giao hàng từ shipper cũ. GPS đã xác minh điểm bàn giao.'
            );

            $resumeShipmentStatus = OrderStatus::normalize((string) $order->status) === OrderStatus::DELIVERING
                ? 'delivering'
                : 'picked_up';
            $this->updateShipment($shipmentId, [
                'status' => $resumeShipmentStatus,
                'note' => 'Đã nhận bàn giao từ shipper cũ; tiếp tục hành trình giao khách.',
            ]);

            $oldShipmentId = isset($handover['old_shipment_id']) ? (int) $handover['old_shipment_id'] : null;
            if ($oldShipmentId) {
                $this->addShipmentHistory(
                    $oldShipmentId,
                    'handover_completed',
                    'Hàng đã được bàn giao thành công cho shipper thay thế.'
                );
            }
        }

        $order->touch();
        RealtimeOrderNotifier::orderStatusUpdated($order->fresh());

        // Nếu cứu một chuyến ghép và shipper cũ đang cầm hàng của nhiều đơn, đây là
        // một lần bàn giao vật lý cho toàn bộ thùng hàng. Không bắt tài xế đứng cùng
        // một vị trí rồi bấm "Đã nhận bàn giao" lần thứ hai cho đơn anh em.
        $bundleTrip = app(ShipperBundleService::class)->activeTripForShipper($shipper);
        if ($bundleTrip && in_array((int) $order->id, array_map('intval', $bundleTrip['order_ids'] ?? []), true)) {
            $siblings = Order::query()
                ->whereIn('id', $bundleTrip['order_ids'])
                ->where('shipper_id', $shipper->id)
                ->where('id', '!=', $order->id)
                ->whereIn('status', [OrderStatus::SHIPPER_PICKED_UP, OrderStatus::DELIVERING])
                ->get();

            foreach ($siblings as $sibling) {
                $siblingHandover = $incidentService->pendingHandoverForOrder($sibling, $shipper);
                if (! $siblingHandover) {
                    continue;
                }

                $siblingShipmentId = $this->latestShipmentId($sibling->id, $shipper->id);
                if (! $siblingShipmentId || $this->hasShipmentHistoryStatus($siblingShipmentId, 'handover_received')) {
                    continue;
                }

                $this->addShipmentHistory(
                    $siblingShipmentId,
                    'handover_received',
                    'Đã nhận bàn giao cùng toàn bộ chuyến ghép tại điểm GPS đã xác minh.'
                );
                $this->updateShipment($siblingShipmentId, [
                    'status' => OrderStatus::normalize((string) $sibling->status) === OrderStatus::DELIVERING ? 'delivering' : 'picked_up',
                    'note' => 'Đã nhận bàn giao cùng chuyến ghép; tiếp tục hành trình.',
                ]);

                $siblingOldShipmentId = isset($siblingHandover['old_shipment_id']) ? (int) $siblingHandover['old_shipment_id'] : null;
                if ($siblingOldShipmentId) {
                    $this->addShipmentHistory(
                        $siblingOldShipmentId,
                        'handover_completed',
                        'Hàng của đơn ghép đã được bàn giao thành công cho shipper thay thế.'
                    );
                }

                $sibling->touch();
                RealtimeOrderNotifier::orderStatusUpdated($sibling->fresh());
            }
        }

        $autoStartedOrder = $this->autoStartDeliveryWhenDue($shipper, $order);
        $nextBundledStop = app(ShipperBundleService::class)->nextStopForShipper($shipper);
        if ($nextBundledStop) {
            return redirect()->route('shipper.map', ['id' => (int) $nextBundledStop['order_id']])
                ->with('success', $autoStartedOrder
                    ? 'Đã nhận bàn giao toàn bộ hàng của chuyến. Hệ thống đang dẫn thẳng sang điểm giao hiện tại.'
                    : 'Đã nhận bàn giao toàn bộ hàng của chuyến. Map đã chuyển sang điểm tiếp theo.');
        }

        return redirect()->route('shipper.map', ['id' => $order->id])
            ->with('success', $autoStartedOrder
                ? 'Đã nhận bàn giao. Đơn đã tự chuyển sang trạng thái đang giao.'
                : 'Đã nhận bàn giao. Map đã chuyển sang hành trình tiếp tục giao khách.');
    }

    /**
     * Shipper xác nhận thủ công "Đã đến nơi" sau khi hệ thống đã ghi nhận GPS lớp 1.
     * Bước này không đổi trạng thái đơn; nó mở bước gọi khách/thu COD rồi mới được giao xong.
     */
    public function confirmCustomerArrival(Request $request, $id)
    {
        $validated = $request->validate([
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:5000'],
        ]);

        $shipper = $this->getShipper();

        $order = Order::with(['branch', 'address'])
            ->whereKey($id)
            ->where('shipper_id', $shipper->id)
            ->where('status', OrderStatus::DELIVERING)
            ->firstOrFail();

        if (! app(ShipperBundleService::class)->isCurrentStopAction($shipper, $order, 'delivery')) {
            return back()->with('error', 'Đơn này chưa phải điểm giao hiện tại. Hãy giao xong đơn trước rồi hệ thống mới mở đơn tiếp theo.');
        }

        if (app(ShipperIncidentService::class)->pendingHandoverForOrder($order, $shipper)) {
            return back()->with('error', 'Bạn phải hoàn tất bàn giao với shipper cũ trước khi xác nhận tới khách.');
        }

        $shipmentId = $this->latestShipmentId($order->id, $shipper->id);

        // Nếu GPS hiện tại vừa đủ điều kiện thì cho hệ thống ghi lớp 1 trước.
        if (isset($validated['latitude'], $validated['longitude'])
            && is_numeric($validated['latitude'])
            && is_numeric($validated['longitude'])) {
            $this->recordArrivalFromPoint(
                $order,
                $shipper,
                $shipmentId,
                (float) $validated['latitude'],
                (float) $validated['longitude'],
                isset($validated['accuracy']) ? (float) $validated['accuracy'] : 9999.0
            );
        }

        $arrival = $this->arrivalEvidenceState($order, $shipper);
        if (! $arrival['verified']) {
            return back()->with('error', 'Hệ thống chưa ghi nhận bạn đã tới phạm vi khách. Hãy bật GPS và đi tới gần điểm giao trước.');
        }

        if ($shipmentId && ! $this->hasShipmentHistoryStatus($shipmentId, 'arrived_customer_confirmed')) {
            $verifiedAt = $arrival['verified_at'] ? ' lúc '.$arrival['verified_at'] : '';
            $this->addShipmentHistory(
                $shipmentId,
                'arrived_customer_confirmed',
                'Shipper xác nhận đã đến điểm giao. GPS đã ghi nhận shipper tới điểm giao'.$verifiedAt.'.'
            );
        }

        return redirect()->route('shipper.map', ['id' => $order->id])
            ->with('success', 'Đã xác nhận đến nơi. Hãy gọi khách, kiểm tra số tiền cần thu rồi mới xác nhận giao xong.');
    }

    /**
     * Shipper báo đã giao. Đơn chỉ sang DELIVERED, chưa COMPLETED.
     */
    public function completeOrder(Request $request, $id)
    {
        $validated = $request->validate([
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:5000'],
        ]);

        $shipper = $this->getShipper();

        $order = Order::with(['branch', 'address'])
            ->whereKey($id)
            ->where('shipper_id', $shipper->id)
            ->where('status', OrderStatus::DELIVERING)
            ->firstOrFail();

        if (! app(ShipperBundleService::class)->isCurrentStopAction($shipper, $order, 'delivery')) {
            return back()->with('error', 'Đơn này chưa phải điểm giao hiện tại. Hãy giao xong đơn trước rồi hệ thống mới mở đơn tiếp theo.');
        }

        if (app(ShipperIncidentService::class)->pendingHandoverForOrder($order, $shipper)) {
            return back()->with('error', 'Bạn phải hoàn tất bàn giao với shipper cũ trước khi xác nhận giao hàng.');
        }

        $shipmentId = $this->latestShipmentId($order->id, $shipper->id);

        // Nếu shipper đang ở điểm giao ngay lúc bấm thì dùng điểm hiện tại để bổ sung bằng chứng GPS.
        // Nếu đã từng đi vào geofence trước đó thì không cần quay lại.
        if (isset($validated['latitude'], $validated['longitude']) && is_numeric($validated['latitude']) && is_numeric($validated['longitude'])) {
            $this->recordArrivalFromPoint(
                $order,
                $shipper,
                $shipmentId,
                (float) $validated['latitude'],
                (float) $validated['longitude'],
                isset($validated['accuracy']) ? (float) $validated['accuracy'] : 9999.0
            );
        }

        $arrival = $this->arrivalEvidenceState($order, $shipper);
        if (! $arrival['verified']) {
            return back()->with('error', 'Bạn chưa tới đúng vị trí khách. Hãy bật GPS và đi tới điểm giao để mở nút Đã đến nơi.');
        }

        if (! $shipmentId || ! $this->hasShipmentHistoryStatus($shipmentId, 'arrived_customer_confirmed')) {
            return back()->with('error', 'Bạn chưa bấm “Đã đến nơi”. Hãy xác nhận đã đến, gọi khách và kiểm tra tiền cần thu trước khi giao xong.');
        }

        $requiresCodCollection = strtolower((string) $order->payment_method) === 'cod'
            && strtolower((string) $order->payment_status) !== 'paid';
        $amountToCollect = max(0, (int) ($order->total ?? $order->total_price ?? 0));

        $orderValues = [
            'status' => OrderStatus::DELIVERED,
            'delivered_at' => now(),
            'status_changed_at' => now(),
            'status_changed_by' => auth()->id(),
        ];

        // Với COD chưa thanh toán, nút giao xong đồng nghĩa shipper xác nhận đã thu đúng tiền khách.
        if ($requiresCodCollection) {
            $orderValues['payment_status'] = 'paid';
        }

        $order->forceFill($orderValues)->save();

        if ($requiresCodCollection) {
            app(\App\Services\ShipperCodService::class)->recordCollection($order->fresh(), $shipper);
        }

        $this->updateShipment($shipmentId, [
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
        $verifiedAt = $arrival['verified_at'] ? ' lúc '.$arrival['verified_at'] : '';

        if ($requiresCodCollection) {
            $this->addShipmentHistory(
                $shipmentId,
                'cod_collected',
                'Shipper xác nhận đã thu COD '.number_format($amountToCollect, 0, ',', '.').'đ từ khách khi giao hàng. Khoản này đã được ghi nhận vào tiền COD phải nộp về công ty.'
            );
        }

        $this->addShipmentHistory(
            $shipmentId,
            'delivered',
            $requiresCodCollection
                ? 'Shipper xác nhận giao xong và đã thu COD. GPS đã xác nhận tới điểm giao'.$verifiedAt.'.'
                : 'Shipper xác nhận giao xong. Đơn đã thanh toán trước, không thu tiền. GPS đã xác nhận tới điểm giao'.$verifiedAt.'.'
        );

        // P8: nếu shipper còn đơn thứ hai trong chuyến ghép thì KHÔNG RETURNING.
        // Giữ BUSY và chuyển thẳng sang stop tiếp theo; chỉ khi hết toàn bộ đơn mới chạy P7.
        $bundleService = app(ShipperBundleService::class);
        $hasNextBundledOrder = $bundleService->hasOtherActiveOrder($shipper, (int) $order->id);
        $returnPlan = $hasNextBundledOrder
            ? ['status' => 'continue_bundle']
            : app(ShipperReturnService::class)->startAfterDelivery($shipper, $order->fresh(['branch', 'address']));

        if ($hasNextBundledOrder) {
            $shipper->forceFill(['status' => 'busy'])->save();
        } else {
            $bundleService->completeTripIfFinished($shipper);
        }

        // Kho dữ liệu địa chỉ hiện tại được học thêm từ điểm giao thực tế.
        app(AddressLearning::class)->markOrderDelivered($order->fresh());

        // Khi shipper xác nhận muộn ở nơi khác, tuyệt đối không dùng GPS hiện tại để học sai điểm giao.
        // Lấy đúng điểm tracking đã tạo ra bằng chứng tới khách.
        $arrivalPoint = $this->trackingPointForArrival($shipmentId, 'arrived_customer');
        if ($arrivalPoint) {
            app(AddressLearning::class)->recordShipperDeliveryPoint(
                $order->fresh(),
                (float) $arrivalPoint['latitude'],
                (float) $arrivalPoint['longitude'],
                $arrivalPoint['accuracy']
            );
        }

        RealtimeOrderNotifier::orderStatusUpdated($order);

        $deliveryMessage = $requiresCodCollection
            ? 'Đã giao hàng và ghi nhận đã thu COD '.number_format($amountToCollect, 0, ',', '.').'đ. Tiền đã được cộng vào mục phải nộp về công ty.'
            : 'Đã giao hàng. Đơn đã thanh toán trước.';

        if (($returnPlan['status'] ?? null) === 'continue_bundle') {
            $autoStartedOrder = $this->autoStartDeliveryWhenDue($shipper);
            $bundleNext = $bundleService->nextStopForShipper($shipper);
            if ($bundleNext) {
                return redirect()->route('shipper.map', ['id' => (int) $bundleNext['order_id']])
                    ->with('success', $deliveryMessage.' '.($autoStartedOrder
                        ? 'Hệ thống đang dẫn thẳng sang chặng giao tiếp theo.'
                        : 'Còn một đơn trong chuyến ghép; hệ thống đã chuyển sang điểm tiếp theo.'));
            }
        }

        if (($returnPlan['status'] ?? null) === 'returning') {
            return redirect()->route('shipper.returning')
                ->with('success', $deliveryMessage.' Hãy quay về home branch của bạn.');
        }

        // P9: nếu vừa giao xong mà đã AVAILABLE ngay (ví dụ giao sát chi nhánh),
        // tài nguyên mới phải kích hoạt lại hàng chờ chứ không đợi admin thao tác lần nữa.
        app(ShipperDispatchService::class)->dispatchWaitingOrders();
        $nextAssigned = Order::query()
            ->where('shipper_id', $shipper->id)
            ->whereIn('status', self::ACTIVE_ORDER_STATUSES)
            ->latest('updated_at')
            ->first();
        if ($nextAssigned) {
            return redirect()->route('shipper.orders.show', $nextAssigned->id)
                ->with('success', $deliveryMessage.' Hệ thống vừa điều phối cho bạn nhiệm vụ tiếp theo.');
        }

        return redirect()->route('shipper.orders')
            ->with('success', $deliveryMessage.' Bạn đang ở trạng thái sẵn sàng nhận nhiệm vụ tiếp theo.');
    }

    /**
     * Shipper nội bộ không được hủy/từ chối chuyến. Sau khi đã bấm Nhận đơn,
     * nếu có vấn đề ở bất kỳ giai đoạn nào thì chỉ được Báo sự cố để hệ thống lưu vết.
     */
    public function reportIssue(Request $request, $id)
    {
        $validated = $request->validate([
            'reason' => ['required', Rule::in(array_keys(self::ISSUE_REASONS))],
            'reason_detail' => ['nullable', 'string', 'max:1000'],
        ]);

        $shipper = $this->getShipper();
        $order = Order::whereKey($id)
            ->where('shipper_id', $shipper->id)
            ->whereIn('status', [
                OrderStatus::CONFIRMED,
                OrderStatus::PREPARING,
                OrderStatus::READY_FOR_DELIVERY,
                OrderStatus::SHIPPER_PICKED_UP,
                OrderStatus::DELIVERING,
            ])
            ->firstOrFail();

        $shipmentId = $this->latestShipmentId($order->id, $shipper->id);
        if (! $shipmentId) {
            return back()->with('error', 'Dữ liệu chuyến giao chưa đồng bộ. Vui lòng tải lại trang.');
        }

        $incidentService = app(ShipperIncidentService::class);
        if ($incidentService->pendingIncident($order)) {
            return back()->with('error', 'Sự cố trước đó vẫn đang chờ cửa hàng/admin xử lý. Bạn không cần gửi lại.');
        }

        $reasonLabel = self::ISSUE_REASONS[$validated['reason']];
        $detail = trim((string) ($validated['reason_detail'] ?? ''));
        // shipment_history.description hiện là VARCHAR(255); cắt an toàn để báo sự cố
        // không làm vỡ request nếu shipper nhập mô tả quá dài.
        $description = \Illuminate\Support\Str::limit(
            $reasonLabel.($detail !== '' ? ': '.$detail : ''),
            250,
            ''
        );

        $this->addShipmentHistory($shipmentId, 'issue_reported', $description);
        $this->updateShipment($shipmentId, [
            'status' => 'issue_pending',
            // Nếu là chuyến đang chờ bàn giao thì giữ nguyên JSON note để không mất điểm bàn giao.
            'note' => $incidentService->pendingHandoverForOrder($order, $shipper)
                ? (string) (DB::table('shipments')->where('id', $shipmentId)->value('note') ?? '')
                : $description,
        ]);
        $order->touch();
        $freshOrder = $order->fresh(['branch']);
        RealtimeOrderNotifier::orderStatusUpdated($freshOrder);

        // Sự cố thuộc trách nhiệm vận hành của Admin chi nhánh; Super Admin nhận
        // đồng thời để giám sát toàn hệ thống. Database notification + websocket
        // (nếu cấu hình) và polling fallback được xử lý trong notifier này.
        $incident = $incidentService->pendingIncident($freshOrder);
        if ($incident) {
            \App\Support\RealtimeShipperIncidentNotifier::reported($freshOrder, $incident);
        }

        return back()->with('success', 'Đã báo sự cố. Admin chi nhánh và Super Admin đã được cảnh báo; đơn vẫn giữ nguyên cho tới khi quản lý quyết định giữ shipper hoặc điều phối người thay thế.');
    }

    public function profile()
    {
        $user = Auth::user();
        $shipperInfo = Shipper::where('user_id', $user->id)->first();

        return view('shipper.profile', compact('user', 'shipperInfo'))
            ->with('shipperUser', $user);
    }

    public function notifications(Request $request): View
    {
        $user = $request->user();
        $filter = (string) $request->query('filter', 'all');

        $query = $user->notifications()->latest();
        if ($filter === 'unread') {
            $query->whereNull('read_at');
        }

        $notifications = $query->paginate(12);

        return view('shipper.notifications.index', [
            'user' => $user,
            'notifications' => $notifications,
            'unreadCount' => $user->unreadNotifications()->count(),
            'totalCount' => $user->notifications()->count(),
        ]);
    }

    /**
     * Hộp chat riêng của shipper. Chỉ hiển thị chat theo các đơn đã được giao cho
     * chính shipper này; đơn đã kết thúc vẫn xem lại trong cửa sổ 24 giờ.
     */
    public function chats(Request $request): View
    {
        $shipper = $this->getShipper();
        $chatStorageReady = Schema::hasTable('delivery_order_messages');

        $orders = Order::query()
            ->with(['user', 'address', 'branch'])
            ->where('shipper_id', $shipper->id)
            ->where(function ($query) {
                $query->whereIn('status', [
                    OrderStatus::CONFIRMED,
                    OrderStatus::PREPARING,
                    OrderStatus::READY_FOR_DELIVERY,
                    OrderStatus::SHIPPER_PICKED_UP,
                    OrderStatus::DELIVERING,
                ])->orWhere(function ($recent) {
                    $recent->whereIn('status', [OrderStatus::DELIVERED, OrderStatus::COMPLETED])
                        ->where('updated_at', '>=', now()->subHours(24));
                });
            })
            ->latest('updated_at')
            ->limit(30)
            ->get();

        $orderIds = $orders->pluck('id')->map(fn ($id) => (int) $id)->all();
        $latestMessages = collect();
        $unreadByOrder = collect();

        if ($chatStorageReady && $orderIds) {
            $messages = DeliveryOrderMessage::query()
                ->whereIn('order_id', $orderIds)
                ->where('created_at', '>=', now()->subHours(24))
                ->latest('id')
                ->get();

            $latestMessages = $messages->groupBy('order_id')->map->first();
            $unreadByOrder = $messages
                ->where('sender_type', 'customer')
                ->whereNull('read_at')
                ->groupBy('order_id')
                ->map->count();
        }

        $selectedOrder = null;
        $selectedId = (int) $request->query('order', 0);
        if ($selectedId > 0) {
            $selectedOrder = $orders->firstWhere('id', $selectedId);
            abort_unless($selectedOrder, 404);
        }

        return view('shipper.chats.index', [
            'shipper' => $shipper,
            'orders' => $orders,
            'latestMessages' => $latestMessages,
            'unreadByOrder' => $unreadByOrder,
            'selectedOrder' => $selectedOrder,
            'chatStorageReady' => $chatStorageReady,
        ]);
    }

    public function markAllNotificationsRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Đã đánh dấu toàn bộ thông báo là đã đọc.');
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $shipper = Shipper::where('user_id', $user->id)->firstOrFail();

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'vehicle_type' => 'nullable|string|max:50',
            'license_plate' => 'nullable|string|max:50',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $user->name = $request->name;
        $user->save();

        $shipper->phone = $request->phone;
        $shipper->vehicle_type = $request->vehicle_type;
        $shipper->license_plate = $request->license_plate;

        if ($request->hasFile('avatar')) {
            $shipper->avatar = $request->file('avatar')->store('shippers', 'public');
        }

        $shipper->save();

        return redirect()->route('shipper.profile')
            ->with('success', 'Cập nhật thông tin shipper thành công!');
    }

    public function updateStatus(Request $request)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['online', 'offline'])],
        ]);

        $shipper = $this->getShipper();
        $hasActiveOrder = Order::where('shipper_id', $shipper->id)
            ->whereIn('status', self::ACTIVE_ORDER_STATUSES)
            ->exists();

        $isReturning = app(ShipperReturnService::class)->currentReturn($shipper) !== null;

        if (($hasActiveOrder || $isReturning) && $validated['status'] === 'offline') {
            return back()->with('error', $isReturning
                ? 'Bạn đang được điều về chi nhánh nên chưa thể chuyển Offline.'
                : 'Bạn đang có chuyến hoạt động nên chưa thể chuyển Offline.');
        }

        $shipper->forceFill(['status' => $validated['status']])->save();

        $suffix = '';
        if ($validated['status'] === 'online') {
            $summary = app(ShipperDispatchService::class)->dispatchWaitingOrders();
            if (($summary['assigned'] ?? 0) > 0) {
                $suffix = ' Engine điều phối đã xử lý '.(int) $summary['assigned'].' đơn đang chờ.';
            }
        }

        return back()->with('success', 'Đã cập nhật trạng thái shipper.'.$suffix);
    }

    public function updateLocation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:5000'],
            'order_id' => ['nullable', 'integer'],
            'test_mode' => ['nullable', 'boolean'],
        ]);

        $shipper = $this->getShipper();
        $testMode = $request->boolean('test_mode')
            && (app()->environment('local') || in_array($request->getHost(), ['127.0.0.1', 'localhost'], true));
        $latitude = (float) $validated['latitude'];
        $longitude = (float) $validated['longitude'];
        $accuracy = isset($validated['accuracy']) ? (float) $validated['accuracy'] : null;
        $accepted = true;
        $jumpWarning = null;
        $arrival = null;
        $returnState = null;

        $order = null;
        $shipmentId = null;
        if (! empty($validated['order_id'])) {
            $order = Order::with(['branch', 'address'])
                ->whereKey($validated['order_id'])
                ->where('shipper_id', $shipper->id)
                ->whereIn('status', self::ACTIVE_ORDER_STATUSES)
                ->first();

            if ($order) {
                $shipmentId = $this->latestShipmentId($order->id, $shipper->id);
                $last = $this->latestTrackingPoint($shipmentId);
                if ($last && ! $testMode) {
                    $seconds = max(1, now()->diffInSeconds($last['recorded_at']));
                    $meters = $this->distanceMeters($last['latitude'], $last['longitude'], $latitude, $longitude);
                    $speedMps = $meters / $seconds;
                    // Chặn cú nhảy GPS cực đoan; vẫn cho xe máy/ô tô chạy nhanh bình thường.
                    if ($seconds <= 30 && $meters > 300 && $speedMps > 45) {
                        $accepted = false;
                        $jumpWarning = 'GPS vừa nhảy vị trí bất thường nên điểm này không được dùng để xác minh.';
                    }
                }
            }
        }

        if ($accepted) {
            $shipper->forceFill([
                'current_latitude' => $latitude,
                'current_longitude' => $longitude,
            ])->save();

            $returnState = app(ShipperReturnService::class)->recordLocation(
                $shipper->fresh(['returningBranch', 'user']),
                $latitude,
                $longitude,
                $accuracy
            );

            // P9: vừa chạm geofence chi nhánh = vừa xuất hiện một AVAILABLE mới.
            // Quét hàng chờ ngay để đơn từng thiếu shipper được tự cứu.
            if (($returnState['arrived'] ?? false) === true) {
                app(ShipperDispatchService::class)->dispatchWaitingOrders();
            }

            if ($order && $shipmentId && Schema::hasTable('shipment_tracking')) {
                DB::table('shipment_tracking')->insert([
                    'shipment_id' => $shipmentId,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'recorded_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->autoRecordArrival($order, $shipper, $shipmentId, $accuracy);
                $arrival = $this->arrivalState($order, $shipper, $latitude, $longitude, $accuracy ?? 9999.0);
            }
        }

        $newAssignment = null;
        if (($returnState['arrived'] ?? false) === true) {
            $freshShipper = $shipper->fresh();
            $assigned = Order::query()
                ->where('shipper_id', $freshShipper->id)
                ->whereIn('status', self::ACTIVE_ORDER_STATUSES)
                ->latest('updated_at')
                ->first();
            if ($assigned) {
                $newAssignment = [
                    'order_id' => (int) $assigned->id,
                    'code' => $assigned->displayCode(),
                    'show_url' => route('shipper.map', ['id' => $assigned->id]),
                    'map_url' => route('shipper.map', ['id' => $assigned->id]),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'accepted' => $accepted,
            'message' => $jumpWarning ?: ($testMode ? 'Đã cập nhật vị trí TEST GPS.' : 'Đã cập nhật vị trí.'),
            'arrival' => $arrival,
            'return_state' => $returnState,
            'new_assignment' => $newAssignment,
            'test_mode' => $testMode,
        ]);
    }

    public function returning(ShipperReturnService $returnService)
    {
        $shipper = $this->getShipper()->loadMissing(['returningBranch', 'user']);
        $returnPlan = $returnService->currentReturn($shipper);

        if (! $returnPlan) {
            return redirect()->route('shipper.dashboard')
                ->with('success', 'Bạn không có chặng quay về đang hoạt động.');
        }

        return view('shipper.returning', [
            'shipperInfo' => $shipper,
            'branch' => $returnPlan['branch'],
        ]);
    }

    public function returningRoute(DeliveryRoutingService $routing, ShipperReturnService $returnService): JsonResponse
    {
        $shipper = $this->getShipper()->loadMissing(['returningBranch', 'user']);
        $returnPlan = $returnService->currentReturn($shipper);

        if (! $returnPlan) {
            return response()->json([
                'success' => true,
                'active' => false,
                'message' => 'Đã hoàn tất chặng quay về.',
            ]);
        }

        $branch = $returnPlan['branch'];
        if (! is_numeric($shipper->current_latitude) || ! is_numeric($shipper->current_longitude)) {
            return response()->json([
                'success' => true,
                'active' => true,
                'waiting_gps' => true,
                'branch' => [
                    'id' => (int) $branch->id,
                    'name' => $branch->name,
                    'address' => $branch->address,
                    'latitude' => (float) $branch->latitude,
                    'longitude' => (float) $branch->longitude,
                ],
                'message' => 'Đang chờ GPS để tính đường về chi nhánh.',
            ]);
        }

        $route = $routing->route(
            (float) $shipper->current_latitude,
            (float) $shipper->current_longitude,
            (float) $branch->latitude,
            (float) $branch->longitude
        );

        return response()->json([
            'success' => true,
            'active' => true,
            'waiting_gps' => false,
            'branch' => [
                'id' => (int) $branch->id,
                'name' => $branch->name,
                'address' => $branch->address,
                'latitude' => (float) $branch->latitude,
                'longitude' => (float) $branch->longitude,
            ],
            'current' => [
                'latitude' => (float) $shipper->current_latitude,
                'longitude' => (float) $shipper->current_longitude,
            ],
            'distance_m' => (float) ($route['distance_m'] ?? 0),
            'duration_s' => (float) ($route['duration_s'] ?? 0),
            'route' => $route,
        ]);
    }

    /**
     * Tạo audio dẫn đường bằng một voice cố định ở server.
     */
    public function navigationVoice(Request $request, NavigationTtsService $tts)
    {
        $validated = $request->validate([
            'text' => ['required', 'string', 'max:240'],
        ]);

        try {
            $result = $tts->synthesize($validated['text']);

            return response($result['audio'], 200, [
                'Content-Type' => $result['content_type'],
                'Cache-Control' => 'private, max-age=86400',
                'X-Navigation-Voice' => $result['voice'],
                'X-TTS-Cache' => $result['cached'] ? 'HIT' : 'MISS',
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'code' => 'tts_unavailable',
                'message' => 'Hướng dẫn giọng nói tạm thời không khả dụng. Bạn vẫn có thể tiếp tục theo chỉ dẫn trên bản đồ.',
            ], 503);
        }
    }

    private function arrivalState(Order $order, Shipper $shipper, float $latitude, float $longitude, float $accuracy): array
    {
        $status = OrderStatus::normalize((string) $order->status);
        $target = $this->targetForOrder($order, $status, $shipper);
        $event = $this->arrivalEventForOrder($order, $shipper, $status);

        if (! $target || ! $event) {
            return [
                'required' => false,
                'eligible' => true,
                'verified' => true,
                'distance_m' => null,
                'radius_m' => null,
                'accuracy_m' => $accuracy,
                'event' => null,
                'verified_at' => null,
                'message' => 'Giai đoạn này không cần xác minh vị trí.',
            ];
        }

        $distance = $this->distanceMeters($latitude, $longitude, (float) $target['latitude'], (float) $target['longitude']);
        $radius = self::ARRIVAL_RADIUS_M;
        $accuracyOk = $accuracy <= self::ARRIVAL_ACCURACY_MAX_M;
        $inside = $distance <= $radius;
        $shipmentId = $this->latestShipmentId($order->id, $shipper->id);
        $evidence = $shipmentId ? $this->arrivalEvidence($shipmentId, $event) : null;

        // Bằng chứng GPS được lưu theo chuyến. Khi đã ghi nhận tới điểm thì không phụ thuộc
        // vị trí hiện tại nữa; shipper có thể rời điểm rồi mới nhớ bấm nút xác nhận.
        $verified = $evidence !== null;

        if ($verified) {
            $message = match ($event) {
                'arrived_store' => 'Bạn đã tới cửa hàng'.($evidence['time_label'] ? ' lúc '.$evidence['time_label'] : '').'. Nút Đã lấy hàng đã được mở.',
                'arrived_handover' => 'Bạn đã tới điểm bàn giao'.($evidence['time_label'] ? ' lúc '.$evidence['time_label'] : '').'. Nút Đã nhận bàn giao đã được mở.',
                default => 'Bạn đã tới điểm giao'.($evidence['time_label'] ? ' lúc '.$evidence['time_label'] : '').'. Nút Đã đến nơi đã được mở.',
            };
        } elseif (! $accuracyOk) {
            $message = 'GPS hiện chưa đủ chính xác (±'.(int) round($accuracy).' m). Hãy chờ tín hiệu tốt hơn.';
        } elseif ($inside) {
            $message = 'Bạn đã ở rất gần điểm đến. Hệ thống đang kiểm tra vị trí.';
        } else {
            $message = 'Bạn còn cách điểm đến khoảng '.(int) round($distance).' m.';
        }

        return [
            'required' => true,
            'eligible' => $accuracyOk && $inside,
            'verified' => $verified,
            'distance_m' => round($distance, 1),
            'radius_m' => round($radius, 1),
            'accuracy_m' => round($accuracy, 1),
            'event' => $event,
            'target_type' => $target['type'],
            'verified_at' => $evidence['iso'] ?? null,
            'verified_at_label' => $evidence['time_label'] ?? null,
            'message' => $message,
        ];
    }

    /**
     * Trạng thái bằng chứng GPS dùng khi shipper bấm xác nhận thao tác.
     * Không xét vị trí hiện tại nữa.
     */
    private function arrivalEvidenceState(Order $order, Shipper $shipper): array
    {
        $status = OrderStatus::normalize((string) $order->status);
        $event = $this->arrivalEventForOrder($order, $shipper, $status);
        $shipmentId = $this->latestShipmentId($order->id, $shipper->id);
        $evidence = ($shipmentId && $event) ? $this->arrivalEvidence($shipmentId, $event) : null;

        return [
            'verified' => $evidence !== null,
            'event' => $event,
            'verified_at' => $evidence['time_label'] ?? null,
            'verified_at_iso' => $evidence['iso'] ?? null,
        ];
    }

    /**
     * Tự ghi bằng chứng GPS khi vị trí hợp lệ chạm geofence.
     * GPS <= 50m: một điểm đủ. GPS 50-120m: cần 2 điểm gần nhau để tránh nhiễu.
     */
    private function autoRecordArrival(Order $order, Shipper $shipper, ?int $shipmentId, ?float $accuracy): void
    {
        if (! $shipmentId || ! Schema::hasTable('shipment_tracking')) {
            return;
        }

        $latest = $this->latestTrackingPoint($shipmentId);
        if (! $latest) {
            return;
        }

        $this->recordArrivalFromPoint(
            $order,
            $shipper,
            $shipmentId,
            (float) $latest['latitude'],
            (float) $latest['longitude'],
            $accuracy ?? 9999.0
        );
    }

    private function recordArrivalFromPoint(
        Order $order,
        Shipper $shipper,
        ?int $shipmentId,
        float $latitude,
        float $longitude,
        float $accuracy
    ): void {
        if (! $shipmentId || ! Schema::hasTable('shipment_history')) {
            return;
        }

        $status = OrderStatus::normalize((string) $order->status);
        $event = $this->arrivalEventForOrder($order, $shipper, $status);
        $target = $this->targetForOrder($order, $status, $shipper);
        if (! $event || ! $target || $accuracy > self::ARRIVAL_ACCURACY_MAX_M) {
            return;
        }

        if ($this->hasArrivalHistory($shipmentId, $event)) {
            return;
        }

        $radius = self::ARRIVAL_RADIUS_M;
        $distance = $this->distanceMeters(
            $latitude,
            $longitude,
            (float) $target['latitude'],
            (float) $target['longitude']
        );
        if ($distance > $radius) {
            return;
        }

        // GPS có sai số lớn hơn thì cần thêm một điểm gần đây cũng nằm trong geofence.
        if ($accuracy > self::ARRIVAL_SINGLE_POINT_ACCURACY_M && Schema::hasTable('shipment_tracking')) {
            $rows = DB::table('shipment_tracking')
                ->where('shipment_id', $shipmentId)
                ->where('recorded_at', '>=', now()->subSeconds(self::ARRIVAL_FUZZY_WINDOW_SECONDS))
                ->latest('recorded_at')
                ->limit(self::ARRIVAL_FUZZY_REQUIRED_POINTS)
                ->get(['latitude', 'longitude']);

            if ($rows->count() < self::ARRIVAL_FUZZY_REQUIRED_POINTS) {
                return;
            }

            $allInside = $rows->every(function ($point) use ($target, $radius) {
                return $this->distanceMeters(
                    (float) $point->latitude,
                    (float) $point->longitude,
                    (float) $target['latitude'],
                    (float) $target['longitude']
                ) <= $radius;
            });
            if (! $allInside) {
                return;
            }
        }

        $description = match ($event) {
            'arrived_store' => 'GPS tự động ghi nhận shipper đã vào phạm vi cửa hàng (cách '.(int) round($distance).'m, sai số ±'.(int) round($accuracy).'m).',
            'arrived_handover' => 'GPS tự động ghi nhận shipper thay thế đã vào phạm vi điểm bàn giao (cách '.(int) round($distance).'m, sai số ±'.(int) round($accuracy).'m).',
            default => 'GPS tự động ghi nhận shipper đã vào phạm vi điểm giao (cách '.(int) round($distance).'m, sai số ±'.(int) round($accuracy).'m).',
        };
        $this->addShipmentHistory($shipmentId, $event, $description);
    }

    private function arrivalEventForOrder(Order $order, Shipper $shipper, string $status): ?string
    {
        if (app(ShipperIncidentService::class)->pendingHandoverForOrder($order, $shipper)) {
            return 'arrived_handover';
        }

        return $this->arrivalEventForStatus($status);
    }

    private function arrivalEventForStatus(string $status): ?string
    {
        return match ($status) {
            // Điều phối và pha chế chạy song song. Nếu shipper tới quán sớm trong lúc
            // CONFIRMED/PREPARING, hệ thống vẫn lưu bằng chứng GPS. Nút Đã lấy hàng
            // chỉ xuất hiện/mở khi quán chuyển READY_FOR_DELIVERY.
            OrderStatus::CONFIRMED,
            OrderStatus::PREPARING,
            OrderStatus::READY_FOR_DELIVERY => 'arrived_store',
            // Sau khi xác nhận lấy hàng, GPS vẫn có thể ghi bằng chứng khi chạm vùng khách.
            OrderStatus::SHIPPER_PICKED_UP, OrderStatus::DELIVERING => 'arrived_customer',
            default => null,
        };
    }

    private function hasArrivalHistory(int $shipmentId, string $event): bool
    {
        return $this->arrivalEvidence($shipmentId, $event) !== null;
    }

    private function arrivalEvidence(int $shipmentId, string $event): ?array
    {
        if (! Schema::hasTable('shipment_history')) {
            return null;
        }

        $row = DB::table('shipment_history')
            ->where('shipment_id', $shipmentId)
            ->where('status', $event)
            ->oldest('created_at')
            ->first(['created_at', 'description']);

        if (! $row) {
            return null;
        }

        $at = $row->created_at ? \Illuminate\Support\Carbon::parse($row->created_at) : null;

        return [
            'iso' => $at?->toIso8601String(),
            'time_label' => $at?->format('H:i'),
            'description' => $row->description,
            'created_at_raw' => $at,
        ];
    }

    private function trackingPointForArrival(?int $shipmentId, string $event): ?array
    {
        if (! $shipmentId || ! Schema::hasTable('shipment_tracking')) {
            return null;
        }

        $evidence = $this->arrivalEvidence($shipmentId, $event);
        $at = $evidence['created_at_raw'] ?? null;
        if (! $at) {
            return null;
        }

        // Event được ghi ngay sau điểm tracking kích hoạt geofence, nên ưu tiên điểm gần nhất trước event.
        $row = DB::table('shipment_tracking')
            ->where('shipment_id', $shipmentId)
            ->where('recorded_at', '<=', $at)
            ->where('recorded_at', '>=', $at->copy()->subSeconds(30))
            ->latest('recorded_at')
            ->latest('id')
            ->first(['latitude', 'longitude']);

        if (! $row) {
            return null;
        }

        return [
            'latitude' => (float) $row->latitude,
            'longitude' => (float) $row->longitude,
            // shipment_tracking hiện không có cột accuracy; để null thay vì lấy GPS xác nhận muộn.
            'accuracy' => null,
        ];
    }

    private function latestTrackingPoint(?int $shipmentId): ?array
    {
        if (! $shipmentId || ! Schema::hasTable('shipment_tracking')) {
            return null;
        }

        $row = DB::table('shipment_tracking')
            ->where('shipment_id', $shipmentId)
            ->latest('recorded_at')
            ->latest('id')
            ->first(['latitude', 'longitude', 'recorded_at']);

        if (! $row) {
            return null;
        }

        return [
            'latitude' => (float) $row->latitude,
            'longitude' => (float) $row->longitude,
            'recorded_at' => \Illuminate\Support\Carbon::parse($row->recorded_at),
        ];
    }

    private function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);
        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(max(0, 1 - $a)));
    }

    private function targetForOrder(Order $order, string $status, ?Shipper $shipper = null): ?array
    {
        if ($shipper) {
            $handover = app(ShipperIncidentService::class)->pendingHandoverForOrder($order, $shipper);
            if ($handover) {
                return [
                    'type' => 'handover',
                    'label' => $handover['label'] ?? 'Điểm bàn giao với shipper cũ',
                    'address' => $handover['address'] ?? 'Vị trí bàn giao',
                    'latitude' => (float) $handover['latitude'],
                    'longitude' => (float) $handover['longitude'],
                ];
            }
        }

        if (in_array($status, [OrderStatus::CONFIRMED, OrderStatus::PREPARING, OrderStatus::READY_FOR_DELIVERY], true)) {
            $branch = $order->branch;
            if (! $branch || ! is_numeric($branch->latitude) || ! is_numeric($branch->longitude)) {
                return null;
            }

            return [
                'type' => 'branch',
                'label' => $branch->name ?: 'Cửa hàng',
                'address' => $branch->address ?: 'Chưa cập nhật địa chỉ cửa hàng',
                'latitude' => (float) $branch->latitude,
                'longitude' => (float) $branch->longitude,
            ];
        }

        if (! is_numeric($order->shipping_latitude) || ! is_numeric($order->shipping_longitude)) {
            return null;
        }

        return [
            'type' => 'customer',
            'label' => $order->customerName() ?: 'Khách hàng',
            'address' => $order->getShippingAddress(),
            'latitude' => (float) $order->shipping_latitude,
            'longitude' => (float) $order->shipping_longitude,
        ];
    }

    /**
     * Trả dữ liệu tuyến cho toàn bộ chuyến ghép để map có thể vẽ:
     * - tuyến chính màu xanh
     * - tuyến phụ màu cam nét đứt
     * - các stop được đánh số theo thứ tự ghé
     */
    private function bundleRouteForMap(array $bundleTrip, Shipper $shipper, float $latitude, float $longitude, DeliveryRoutingService $routing): ?array
    {
        $orders = Order::query()
            ->with(['branch', 'address'])
            ->whereIn('id', $bundleTrip['order_ids'] ?? [])
            ->get()
            ->keyBy('id');

        if ($orders->isEmpty()) {
            return null;
        }

        $isOrderFinished = function (Order $order): bool {
            $status = OrderStatus::normalize((string) $order->status);
            return in_array($status, [OrderStatus::DELIVERED, OrderStatus::COMPLETED], true);
        };

        $tripStops = collect($bundleTrip['stops'] ?? [])
            ->map(function (array $stop, int $index) use ($orders, $isOrderFinished) {
                $order = $orders->get((int) ($stop['order_id'] ?? 0));
                if (! $order) {
                    return null;
                }

                $isPickup = ($stop['type'] ?? '') === 'pickup';
                $isDelivery = ($stop['type'] ?? '') === 'delivery';
                if (! $isPickup && ! $isDelivery) {
                    return null;
                }

                $label = $isPickup
                    ? ($order->branch?->name ?: 'Cửa hàng')
                    : ($order->customerName() ?: 'Khách hàng');

                $address = $isPickup
                    ? ($order->branch?->address ?: 'Chưa cập nhật địa chỉ cửa hàng')
                    : $order->getShippingAddress();

                $stopLatitude = (float) ($stop['latitude'] ?? 0);
                $stopLongitude = (float) ($stop['longitude'] ?? 0);
                if (! $stopLatitude || ! $stopLongitude) {
                    return null;
                }

                return [
                    'order_id' => (int) $order->id,
                    'type' => $isPickup ? 'pickup' : 'delivery',
                    'branch_id' => $isPickup ? (int) ($order->branch_id ?? 0) : null,
                    'location_key' => $isPickup
                        ? 'branch:'.(int) ($order->branch_id ?? 0)
                        : 'delivery:'.(int) $order->id,
                    'label' => $label,
                    'address' => $address,
                    'latitude' => $stopLatitude,
                    'longitude' => $stopLongitude,
                    'plan_sequence' => $index + 1,
                    'order_status' => OrderStatus::normalize((string) $order->status),
                    'order_finished' => $isOrderFinished($order),
                    'completed' => $isPickup
                        ? in_array(OrderStatus::normalize((string) $order->status), [OrderStatus::SHIPPER_PICKED_UP, OrderStatus::DELIVERING, OrderStatus::DELIVERED, OrderStatus::COMPLETED], true)
                        : in_array(OrderStatus::normalize((string) $order->status), [OrderStatus::DELIVERED, OrderStatus::COMPLETED], true),
                    'waiting_ready' => $isPickup
                        && in_array(OrderStatus::normalize((string) $order->status), [OrderStatus::CONFIRMED, OrderStatus::PREPARING], true),
                ];
            })
            ->filter()
            ->values()
            ->all();

        if (count($tripStops) < 1) {
            return null;
        }

        // Đánh số theo ĐIỂM ĐẾN VẬT LÝ của toàn chuyến trước khi loại các điểm đã xong.
        // Nhiều đơn cùng một chi nhánh dùng chung một location_key nên chỉ chiếm một số.
        // Nhờ vậy sau khi Điểm 1 hoàn tất, UI tiếp tục hiển thị Điểm 2/3/... thay vì đánh số lại từ 1.
        $destinationSequenceByKey = [];
        $destinationSequence = 0;
        foreach ($tripStops as &$tripStop) {
            $locationKey = (string) ($tripStop['location_key'] ?? '');
            if ($locationKey === '') {
                $locationKey = ($tripStop['type'] ?? 'stop').':'.($tripStop['order_id'] ?? uniqid('', true));
            }
            if (! array_key_exists($locationKey, $destinationSequenceByKey)) {
                $destinationSequenceByKey[$locationKey] = ++$destinationSequence;
            }
            $tripStop['destination_sequence'] = $destinationSequenceByKey[$locationKey];
        }
        unset($tripStop);

        $totalDestinations = $destinationSequence;
        $completedDestinations = collect($tripStops)
            ->groupBy('destination_sequence')
            ->filter(fn ($group) => $group->every(fn (array $stop) => (bool) ($stop['completed'] ?? false)))
            ->count();

        $remainingStops = array_values(array_filter($tripStops, fn (array $stop) => !($stop['completed'] ?? false)));
        if (count($remainingStops) < 1) {
            return null;
        }

        // Giữ nguyên tuyệt đối thứ tự stop đã được ShipperBundleService lập.
        // Không đưa quán đang preparing xuống sau khách: chuyến ghép phải đi tuần tự
        // pickup 1 -> pickup 2 -> pickup 3 -> delivery 1 -> delivery 2 -> delivery 3.
        $effectiveStops = $remainingStops;

        foreach ($effectiveStops as $index => &$stop) {
            // Số vật lý đã được gán trên toàn chuyến; không dùng plan_sequence vì hai order-stop
            // cùng một quán phải dùng chung một điểm đến.
            $stop['sequence'] = (int) ($stop['destination_sequence'] ?? ($index + 1));
        }
        unset($stop);

        $currentOrderId = count($effectiveStops) ? (int) ($effectiveStops[0]['order_id'] ?? 0) : null;

        $stages = [];
        $activeStage = null;

        foreach ($effectiveStops as $stop) {
            $point = [
                'latitude' => (float) $stop['latitude'],
                'longitude' => (float) $stop['longitude'],
            ];

            if ($activeStage === null) {
                $activeStage = [
                    'point' => $point,
                    'stops' => [$stop],
                ];
                continue;
            }

            $activeStop = $activeStage['stops'][0] ?? [];
            $sameBranchPickup = ($activeStop['type'] ?? '') === 'pickup'
                && ($stop['type'] ?? '') === 'pickup'
                && (int) ($activeStop['branch_id'] ?? 0) > 0
                && (int) ($activeStop['branch_id'] ?? 0) === (int) ($stop['branch_id'] ?? 0);

            // Một chi nhánh là MỘT điểm đến vật lý. Nếu chuyến có nhiều đơn cùng quán,
            // shipper chỉ tới quán đó một lần rồi xử lý tất cả đơn thuộc quán tại stage này.
            if ($sameBranchPickup) {
                $activeStage['stops'][] = $stop;
                continue;
            }

            $stages[] = $activeStage;
            $activeStage = [
                'point' => $point,
                'stops' => [$stop],
            ];
        }

        if ($activeStage !== null) {
            $stages[] = $activeStage;
        }

        if (count($stages) < 1) {
            return null;
        }

        $startPoint = ['latitude' => $latitude, 'longitude' => $longitude];
        foreach ($stages as $index => &$stage) {
            // Giữ số điểm vật lý của TOÀN chuyến. Không renumber các điểm còn lại.
            // Ví dụ Điểm 1 đã xong thì stage hiện tại vẫn là Điểm 2/5.
            $stage['sequence'] = (int) (($stage['stops'][0]['destination_sequence'] ?? null) ?: ($index + 1));
            $stage['stop_count'] = count($stage['stops'] ?? []);
            $stage['order_count'] = collect($stage['stops'] ?? [])->pluck('order_id')->unique()->count();
            $stage['type'] = (string) (($stage['stops'][0]['type'] ?? '') ?: 'delivery');
            $stage['label'] = (string) (($stage['stops'][0]['label'] ?? '') ?: 'Điểm đến');
            $stage['address'] = (string) (($stage['stops'][0]['address'] ?? '') ?: '');
            foreach ($stage['stops'] as &$stageStop) {
                $stageStop['destination_sequence'] = $stage['sequence'];
            }
            unset($stageStop);

            $stageRoute = $routing->route(
                (float) $startPoint['latitude'],
                (float) $startPoint['longitude'],
                (float) $stage['point']['latitude'],
                (float) $stage['point']['longitude'],
                ['prefer_local_roads' => true]
            );

            if (! is_array($stageRoute) || empty($stageRoute['geometry'])) {
                continue;
            }

            $stage['state'] = $index === 0 ? 'current' : 'future';
            $stage['route'] = [
                'distance_m' => (float) ($stageRoute['distance_m'] ?? 0),
                'duration_s' => (float) ($stageRoute['duration_s'] ?? 0),
                'geometry' => $stageRoute['geometry'] ?? [],
                'steps' => $stageRoute['steps'] ?? [],
                'legs' => $stageRoute['legs'] ?? [],
                'source' => $stageRoute['source'] ?? null,
                'fallback' => (bool) ($stageRoute['fallback'] ?? false),
                'preference_label' => $stageRoute['preference_label'] ?? null,
                'alternatives_count' => (int) ($stageRoute['alternatives_count'] ?? 0),
            ];

            $startPoint = [
                'latitude' => (float) $stage['point']['latitude'],
                'longitude' => (float) $stage['point']['longitude'],
            ];
        }
        unset($stage);

        $stages = array_values(array_filter($stages, fn (array $stage) => !empty($stage['route']['geometry'])));
        if (count($stages) < 1) {
            return null;
        }

        // Nếu có stage không dựng được geometry thì chỉ cập nhật current/future;
        // tuyệt đối không đánh số lại vì số điểm là định danh tiến độ của toàn chuyến.
        foreach ($stages as $index => &$stage) {
            $stage['state'] = $index === 0 ? 'current' : 'future';
        }
        unset($stage);

        $mainRouteGroup = $stages[0];
        $mainRoute = $mainRouteGroup['route'];
        $mainPoints = array_merge([
            ['latitude' => $latitude, 'longitude' => $longitude],
        ], [[
            'latitude' => (float) ($mainRouteGroup['point']['latitude'] ?? 0),
            'longitude' => (float) ($mainRouteGroup['point']['longitude'] ?? 0),
        ]]);

        $altRoute = $this->routeThroughWithOptions($routing, $mainPoints);
        if ($altRoute && (! isset($altRoute['geometry']) || $altRoute['geometry'] === $mainRoute['geometry'])) {
            $altRoute = null;
        }

        return [
            'stops' => $effectiveStops,
            'plan_stops' => $tripStops,
            'stages' => $stages,
            'groups' => $stages,
            'main_route' => $mainRoute,
            'alt_route' => $altRoute ? [
                'distance_m' => (float) ($altRoute['distance_m'] ?? 0),
                'duration_s' => (float) ($altRoute['duration_s'] ?? 0),
                'geometry' => $altRoute['geometry'] ?? [],
                'source' => $altRoute['source'] ?? null,
                'fallback' => (bool) ($altRoute['fallback'] ?? false),
                'preference_label' => $altRoute['preference_label'] ?? null,
            ] : null,
            'display_distance_m' => (float) ($mainRoute['distance_m'] ?? 0),
            'display_duration_s' => (float) ($mainRoute['duration_s'] ?? 0),
            'distance_delta_m' => $altRoute ? ((float) ($altRoute['distance_m'] ?? 0) - (float) ($mainRoute['distance_m'] ?? 0)) : null,
            'duration_delta_s' => $altRoute ? ((float) ($altRoute['duration_s'] ?? 0) - (float) ($mainRoute['duration_s'] ?? 0)) : null,
            'current_order_id' => $currentOrderId,
            'total_destinations' => $totalDestinations,
            'completed_destinations' => $completedDestinations,
            'current_destination_sequence' => (int) ($stages[0]['sequence'] ?? 1),
        ];
    }

    /**
     * Tạo route đa chặng bằng cách ghép các đoạn route đơn.
     * Dùng cho đường phụ/nét đứt khi muốn ưu tiên đường nhỏ.
     *
     * @param array<int,array{latitude:float,longitude:float}> $points
     */
    private function routeThroughWithOptions(DeliveryRoutingService $routing, array $points, array $segmentOptions = []): ?array
    {
        $points = collect($points)
            ->filter(fn ($point) => is_array($point) && isset($point['latitude'], $point['longitude']) && is_numeric($point['latitude']) && is_numeric($point['longitude']))
            ->map(fn ($point) => [
                'latitude' => (float) $point['latitude'],
                'longitude' => (float) $point['longitude'],
            ])
            ->values()
            ->all();

        if (count($points) < 2) {
            return null;
        }

        $distance = 0.0;
        $duration = 0.0;
        $geometry = [];
        $legs = [];
        $fallback = false;
        $source = null;

        for ($i = 0; $i < count($points) - 1; $i++) {
            $segment = $routing->route(
                $points[$i]['latitude'],
                $points[$i]['longitude'],
                $points[$i + 1]['latitude'],
                $points[$i + 1]['longitude'],
                $segmentOptions
            );

            $distance += (float) ($segment['distance_m'] ?? 0);
            $duration += (float) ($segment['duration_s'] ?? 0);
            $legs[] = [
                'distance_m' => (float) ($segment['distance_m'] ?? 0),
                'duration_s' => (float) ($segment['duration_s'] ?? 0),
            ];
            $fallback = $fallback || (bool) ($segment['fallback'] ?? false);
            $source = $source ?? ($segment['source'] ?? null);

            foreach (($segment['geometry'] ?? []) as $point) {
                if (! $geometry || end($geometry) !== $point) {
                    $geometry[] = $point;
                }
            }
        }

        return [
            'source' => $source ?? 'multi_segment',
            'fallback' => $fallback,
            'distance_m' => $distance,
            'duration_s' => $duration,
            'legs' => $legs,
            'geometry' => $geometry,
            'steps' => [],
            'alternatives_count' => 0,
        ];
    }

    private function createShipment(int $orderId, int $shipperId): ?int
    {
        if (! Schema::hasTable('shipments')) {
            return null;
        }

        return (int) DB::table('shipments')->insertGetId([
            'order_id' => $orderId,
            'shipper_id' => $shipperId,
            'status' => 'assigned',
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function latestShipmentId(int $orderId, int $shipperId): ?int
    {
        if (! Schema::hasTable('shipments')) {
            return null;
        }

        $id = DB::table('shipments')
            ->where('order_id', $orderId)
            ->where('shipper_id', $shipperId)
            ->latest('id')
            ->value('id');

        return $id ? (int) $id : null;
    }

    private function updateShipment(?int $shipmentId, array $values): void
    {
        if (! $shipmentId || ! Schema::hasTable('shipments')) {
            return;
        }

        $values['updated_at'] = now();
        DB::table('shipments')->where('id', $shipmentId)->update($values);
    }

    private function hasShipmentHistoryStatus(int $shipmentId, string $status): bool
    {
        if (! Schema::hasTable('shipment_history')) {
            return false;
        }

        return DB::table('shipment_history')
            ->where('shipment_id', $shipmentId)
            ->where('status', $status)
            ->exists();
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
}
