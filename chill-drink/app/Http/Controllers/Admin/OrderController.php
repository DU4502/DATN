<?php

namespace App\Http\Controllers\Admin;

use App\Support\OrderStatus;
use App\Support\RealtimeOrderNotifier;
use App\Support\AddressLearning;
use App\Support\ScheduledDelivery;
use App\Services\ShipperDispatchService;
use App\Services\ShipperIncidentService;
use App\Services\OrderCancellationService;
use App\Services\SuperAdminOrderOverrideService;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Shipper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OrderController extends Controller
{
    /**
     * Apply branch scope to a query based on current user's role and branch
     * Super Admin sees all orders, Admin sees only their branch's orders
     */
    private function applyBranchScope($query)
    {
        $user = auth()->user();
        
        // Super Admin (role_id = 3) can see all orders
        if ($user->isSuperAdmin()) {
            return $query;
        }
        
        // Regular Admin (role_id = 2) can only see their branch's orders
        return $query->where('branch_id', $user->branch_id);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => trim((string) $request->query('status', '')),
            'payment_status' => trim((string) $request->query('payment_status', '')),
            'payment_method' => trim((string) $request->query('payment_method', '')),
            'date_from' => trim((string) $request->query('date_from', '')),
            'date_to' => trim((string) $request->query('date_to', '')),
            'delivery' => trim((string) $request->query('delivery', '')),
        ];

        $statusOptions = OrderStatus::filterOptions();

        $orders = Order::query()
            ->with(['user', 'branch', 'address', 'shipper.user', 'orderItems.product', 'orderItems.productSize.size', 'reviews.user', 'reviews.product'])
            // Admin không thấy đơn hàng guest chưa xác nhận email
            ->where('status', '!=', \App\Support\OrderStatus::AWAITING_EMAIL_CONFIRMATION)
            // Giao dịch VNPay chưa hoàn tất chưa phải là đơn cần xử lý.
            ->whereNot(function ($query) {
                $query->where('payment_method', 'vnpay')->where('payment_status', '!=', 'paid');
            })
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $keyword = $filters['q'];

                // Remove # prefix if exists
                $cleanKeyword = ltrim($keyword, '#');

                $query->where(function ($subQuery) use ($keyword, $cleanKeyword) {
                    // Tìm theo order_code (VD: CN1-ON-20260728-0002)
                    $subQuery->where('order_code', 'like', '%'.$cleanKeyword.'%');

                    // Nếu nhập số → tìm thêm theo ID (exact match)
                    if (is_numeric($cleanKeyword)) {
                        $subQuery->orWhere('id', (int) $cleanKeyword);
                        return;
                    }

                    // Tìm theo tên/email của user đã đăng nhập
                    $subQuery->orWhereHas('user', function ($userQuery) use ($keyword) {
                        $userQuery
                            ->where('name', 'like', '%'.$keyword.'%')
                            ->orWhere('email', 'like', '%'.$keyword.'%');
                    });

                    // Tìm theo thông tin guest (nếu cột tồn tại)
                    if (Schema::hasColumn('orders', 'guest_name')) {
                        $subQuery->orWhere('guest_name', 'like', '%'.$keyword.'%');
                    }
                    if (Schema::hasColumn('orders', 'guest_email')) {
                        $subQuery->orWhere('guest_email', 'like', '%'.$keyword.'%');
                    }
                    if (Schema::hasColumn('orders', 'guest_phone')) {
                        $subQuery->orWhere('guest_phone', 'like', '%'.$keyword.'%');
                    }
                });
            })
            ->when(isset($statusOptions[$filters['status']]) && $filters['status'] !== '', function ($query) use ($filters) {
                $query->where('status', $filters['status']);
            })
            ->when($filters['payment_status'] !== '', function ($query) use ($filters) {
                $query->where('payment_status', $filters['payment_status']);
            })
            ->when($filters['payment_method'] !== '', function ($query) use ($filters) {
                $query->where('payment_method', $filters['payment_method']);
            })
            ->when($filters['delivery'] !== '', function ($query) use ($filters) {
                $query->where(function ($query) use ($filters) {
                    match ($filters['delivery']) {
                        'now' => $query->where('delivery_type', 'now'),
                        'scheduled' => $query->where('delivery_type', 'scheduled'),
                        'today' => $query->where('delivery_type', 'scheduled')->whereDate('scheduled_delivery_time', today()),
                        'upcoming' => $query->where('delivery_type', 'scheduled')->whereBetween('scheduled_delivery_time', [now(), now()->addHours(2)]),
                        default => $query,
                    };
                });
            });

        if (Schema::hasColumn('orders', 'created_at')) {
            if ($filters['date_from'] !== '' && $filters['date_to'] !== '') {
                $orders->whereBetween('created_at', [
                    $filters['date_from'].' 00:00:00',
                    $filters['date_to'].' 23:59:59',
                ]);
            } elseif ($filters['date_from'] !== '') {
                $orders->where('created_at', '>=', $filters['date_from'].' 00:00:00');
            } elseif ($filters['date_to'] !== '') {
                $orders->where('created_at', '<=', $filters['date_to'].' 23:59:59');
            }
        }

        // Apply branch scope based on user role
        $orders = $this->applyBranchScope($orders);

        $orders = Schema::hasColumn('orders', 'created_at')
            ? $orders->latest()
            : $orders->orderByDesc('id');

        $orders = $orders->paginate(12)->withQueryString();
        $shipmentIncidents = app(ShipperIncidentService::class)->pendingForOrders($orders->getCollection());
        $historicalShippers = $this->historicalShippersForOrders($orders->getCollection());
        
        $latestOrderQuery = Order::query();
        $latestOrderQuery = $this->applyBranchScope($latestOrderQuery);
        $latestOrderId = (int) ($latestOrderQuery->max('id') ?? 0);
        $latestUpdatedQuery = Order::query()
            ->where('status', '!=', \App\Support\OrderStatus::AWAITING_EMAIL_CONFIRMATION);
        $latestUpdatedQuery = $this->applyBranchScope($latestUpdatedQuery);
        $latestOrderUpdatedValue = $latestUpdatedQuery->max('updated_at');
        $latestOrderUpdatedAt = $latestOrderUpdatedValue
            ? \Illuminate\Support\Carbon::parse($latestOrderUpdatedValue)->toIso8601String()
            : null;

        return view('admin.orders.index', compact('orders', 'filters', 'statusOptions', 'latestOrderId', 'latestOrderUpdatedAt', 'shipmentIncidents', 'historicalShippers'));
    }

    public function recent(Request $request): JsonResponse
    {
        $afterId = max(0, (int) $request->query('after_id', 0));
        $updatedAfter = $request->query('updated_after');
        if ($updatedAfter) {
            try {
                $updatedAfter = \Illuminate\Support\Carbon::parse($updatedAfter)->toDateTimeString();
            } catch (\Throwable) {
                $updatedAfter = null;
            }
        }

        $orders = Order::query()
            ->with(['user', 'branch', 'address', 'shipper.user', 'orderItems.product', 'orderItems.productSize.size', 'reviews.user', 'reviews.product'])
            ->where('status', '!=', \App\Support\OrderStatus::AWAITING_EMAIL_CONFIRMATION);

        if ($afterId > 0 || $updatedAfter) {
            $orders->where(function ($query) use ($afterId, $updatedAfter) {
                if ($afterId > 0) {
                    $query->where('id', '>', $afterId);
                }
                if ($updatedAfter && Schema::hasColumn('orders', 'updated_at')) {
                    $query->orWhere('updated_at', '>', $updatedAfter);
                }
            });
        }
        
        // Apply branch scope
        $orders = $this->applyBranchScope($orders);
        
        $orders = $orders
            ->when($updatedAfter, fn ($query) => $query->orderByDesc('updated_at'))
            ->when(! $updatedAfter, fn ($query) => $query->orderByDesc('id'))
            ->limit(20)
            ->get()
            ->map(fn (Order $order) => $this->orderBroadcastPayload($order))
            ->values();

        $latestOrderQuery = Order::query();
        $latestOrderQuery = $this->applyBranchScope($latestOrderQuery);
        $latestOrderId = (int) ($latestOrderQuery->max('id') ?? 0);
        $latestUpdatedQuery = Order::query()
            ->where('status', '!=', \App\Support\OrderStatus::AWAITING_EMAIL_CONFIRMATION);
        $latestUpdatedQuery = $this->applyBranchScope($latestUpdatedQuery);
        $latestUpdatedValue = $latestUpdatedQuery->max('updated_at');
        $latestUpdatedAt = $latestUpdatedValue
            ? \Illuminate\Support\Carbon::parse($latestUpdatedValue)->toIso8601String()
            : null;

        return response()->json([
            'orders' => $orders,
            'latest_id' => $latestOrderId,
            'latest_updated_at' => $latestUpdatedAt,
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    public function pendingAlerts(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->with(['user', 'branch', 'address', 'shipper.user', 'orderItems.product', 'orderItems.productSize.size', 'reviews.user', 'reviews.product'])
            ->where('status', OrderStatus::PENDING)
            ->where('status', '!=', OrderStatus::AWAITING_EMAIL_CONFIRMATION);

        $orders = $this->applyBranchScope($orders);

        $pendingOrders = $orders
            ->orderBy('created_at')
            ->limit(6)
            ->get()
            ->map(fn (Order $order) => $this->orderBroadcastPayload($order))
            ->values();

        $pendingCountQuery = Order::query()
            ->where('status', OrderStatus::PENDING)
            ->where('status', '!=', OrderStatus::AWAITING_EMAIL_CONFIRMATION);

        $pendingCountQuery = $this->applyBranchScope($pendingCountQuery);
        $pendingCount = (int) $pendingCountQuery->count();

        return response()->json([
            'orders' => $pendingOrders,
            'pending_count' => $pendingCount,
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    private function historicalShippersForOrders($orders): array
    {
        if (! Schema::hasTable('shipments')) {
            return [];
        }

        $orderIds = collect($orders)
            ->filter(fn (Order $order) => ! $order->shipper_id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        if ($orderIds->isEmpty()) {
            return [];
        }

        $shipments = DB::table('shipments')
            ->whereIn('order_id', $orderIds)
            ->whereNotNull('shipper_id')
            ->orderByDesc('id')
            ->get(['id', 'order_id', 'shipper_id', 'status']);

        $latestByOrder = [];
        foreach ($shipments as $shipment) {
            $orderId = (int) $shipment->order_id;
            $latestByOrder[$orderId] ??= $shipment;
        }

        if ($latestByOrder === []) {
            return [];
        }

        $shippers = Shipper::with('user')
            ->whereIn('id', collect($latestByOrder)->pluck('shipper_id')->unique()->values())
            ->get()
            ->keyBy('id');

        $result = [];
        foreach ($latestByOrder as $orderId => $shipment) {
            $shipper = $shippers->get((int) $shipment->shipper_id);
            if (! $shipper) {
                continue;
            }

            $payload = $this->shipperPayload($shipper);
            $payload['shipment_status'] = $shipment->status;
            $payload['source_label'] = 'Đã nhận chuyến này';
            $result[$orderId] = $payload;
        }

        return $result;
    }

    private function shipperPayloadForOrder(Order $order): ?array
    {
        if ($order->shipper) {
            return $this->shipperPayload($order->shipper);
        }

        if (! Schema::hasTable('shipments')) {
            return null;
        }

        $shipment = DB::table('shipments')
            ->where('order_id', $order->id)
            ->whereNotNull('shipper_id')
            ->orderByDesc('id')
            ->first(['id', 'shipper_id', 'status']);

        if (! $shipment) {
            return null;
        }

        $shipper = Shipper::with('user')->find((int) $shipment->shipper_id);
        if (! $shipper) {
            return null;
        }

        $payload = $this->shipperPayload($shipper);
        $payload['shipment_status'] = $shipment->status;
        $payload['source_label'] = 'Đã nhận chuyến này';

        return $payload;
    }

    private function shipperPayload(Shipper $shipper): array
    {
        return [
            'id' => (int) $shipper->id,
            'name' => $shipper->user?->name ?: $shipper->code ?: 'Shipper',
            'code' => $shipper->code,
            'phone' => $shipper->phone ?: $shipper->user?->phone,
            'vehicle_type' => $shipper->vehicle_type,
            'license_plate' => $shipper->license_plate,
            'status' => $shipper->status,
            'source_label' => null,
        ];
    }

    private function orderBroadcastPayload(Order $order): array
    {
        $customerName = $order->customerName() ?: 'Khách hàng';
        $customerEmail = $order->customerEmail() ?: '';
        $customerPhone = $order->customerPhone() ?: '';
        $total = (int) ($order->total ?? $order->total_price ?? 0);
        $fulfillmentType = $order->fulfillment_type ?? 'delivery';
        $shipmentIncident = app(ShipperIncidentService::class)->pendingIncident($order);
        $shipperPayload = $this->shipperPayloadForOrder($order);
        $user = auth()->user();
        $isSuperAdmin = (bool) $user?->isSuperAdmin();
        $isSuperAdminWorkspace = $isSuperAdmin && ! $user->isViewingAdminWorkspace();
        $currentStatus = OrderStatus::normalize((string) $order->status);
        $statusOptionsForActor = $this->orderManagementOptionsForOrder($order);
        $canCancelForActor = $isSuperAdmin
            ? OrderStatus::canSuperAdminCancelFrom($currentStatus)
            : $currentStatus === OrderStatus::PENDING;
        $orderCode = $order->displayCode();
        $canConfirm = $currentStatus === OrderStatus::PENDING
            && ! ($order->payment_method === 'vnpay' && $order->payment_status !== 'paid');
        $confirmBlockReason = $canConfirm
            ? null
            : (($currentStatus !== OrderStatus::PENDING)
                ? 'Đơn này không còn ở trạng thái chờ xác nhận.'
                : 'Đơn VNPay phải thanh toán thành công trước khi xác nhận.');

        return [
            'order_id' => $order->id,
            'order_code' => $orderCode,
            'branch_id' => is_numeric($order->branch_id) ? (int) $order->branch_id : null,
            'branch_name' => $order->branch?->name ?? 'Chưa gán',
            'customer_name' => $customerName,
            'customer_email' => $customerEmail,
            'customer_phone' => $customerPhone,
            'note' => $order->note ?? '',
            'cancellation_reason' => $order->cancellation_reason ?? '',
            'total' => $total,
            'total_formatted' => number_format($total, 0, ',', '.').'đ',
            'subtotal_formatted' => number_format((int) ($order->subtotal ?? 0), 0, ',', '.').'đ',
            'shipping_fee_formatted' => number_format((int) ($order->shipping_fee ?? 0), 0, ',', '.').'đ',
            'discount_formatted' => number_format((int) ($order->discount ?? 0), 0, ',', '.').'đ',
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status,
            'payment_method_label' => $this->paymentMethodLabel($order->payment_method),
            'payment_status_label' => $this->paymentStatusLabel($order->payment_status),
            'shipping_address' => $order->getShippingAddress(),
            'shipper' => $shipperPayload,
            'delivered_at' => $order->delivered_at?->format('d/m/Y H:i:s'),
            'auto_complete_at' => $order->delivered_at?->copy()->addMinutes(\App\Services\DeliveredOrderCompletionService::AUTO_COMPLETE_AFTER_MINUTES)->format('d/m/Y H:i:s'),
            'status' => $currentStatus,
            'status_label' => OrderStatus::label($currentStatus),
            'status_changed_at' => $order->status_changed_at?->format('d/m/Y H:i'),
            'status_changed_by_name' => $order->status_changed_by
                ? (\App\Models\User::find($order->status_changed_by)?->name ?? 'Nhân viên')
                : null,
            'next_status' => $this->orderManagementNextStatusForOrder($order),
            'can_cancel' => $canCancelForActor,
            'status_options' => $statusOptionsForActor,
            'super_admin_override' => $isSuperAdmin,
            'created_at' => $order->created_at?->format('d/m/Y H:i'),
            'created_at_iso' => $order->created_at?->toIso8601String(),
            'scheduled_at' => $order->scheduled_at?->format('H:i · d/m/Y'),
            'delivery_type' => $order->delivery_type,
            'is_support_redelivery' => $order->support_issue_id !== null,
            'delivery_note' => $order->delivery_note,
            'scheduled_delivery_time' => $order->scheduled_delivery_time?->format('H:i · d/m/Y'),
            'message' => "Đơn hàng mới {$orderCode} từ {$customerName}",
            'can_confirm' => $canConfirm,
            'confirm_block_reason' => $confirmBlockReason,
            'status_update_url' => $isSuperAdminWorkspace
                ? route('admin.super-admin.manage.orders.updateStatus', $order->id)
                : route('admin.orders.updateStatus', $order->id),
            'url' => $isSuperAdminWorkspace
                ? route('admin.super-admin.manage.orders.index', ['q' => $orderCode])
                : route('admin.orders.index', ['q' => $orderCode]),
            'shipment_incident' => $shipmentIncident ? [
                'shipper_name' => $shipmentIncident['shipper_name'] ?? 'Shipper',
                'description' => $shipmentIncident['description'] ?? 'Shipper báo sự cố.',
                'incident_type' => $shipmentIncident['incident_type'] ?? 'driver_issue',
                'reported_at_label' => $shipmentIncident['reported_at_label'] ?? null,
            ] : null,
            'incident_resolve_url' => $isSuperAdminWorkspace
                ? route('admin.super-admin.manage.orders.shipper-incident.resolve', $order->id)
                : route('admin.orders.shipper-incident.resolve', $order->id),
            'items' => $order->orderItems->map(fn ($item) => [
                'product_name' => $item->product?->name ?? 'Sản phẩm đã xóa',
                'image_url' => $item->product?->image_url,
                'size_name' => $item->productSize?->size?->name ?? 'Chưa chọn',
                'ice_level' => (int) $item->ice_level,
                'sugar_level' => (int) $item->sugar_level,
                'quantity' => (int) $item->quantity,
                'unit_price' => (int) $item->unit_price,
                'unit_price_formatted' => number_format((int) $item->unit_price, 0, ',', '.') . 'đ',
                'total_formatted' => number_format((int) $item->getSubtotal(), 0, ',', '.') . 'đ',
            ])->toArray(),
            'reviews' => $order->reviews->map(fn ($review) => [
                'product_name' => $review->product?->name ?? 'Sản phẩm đã xóa',
                'product_image' => $review->product?->image_url,
                'user_name' => $review->user?->name ?? 'Khách hàng',
                'user_email' => $review->user?->email ?? '',
                'rating' => (int) $review->rating,
                'comment' => $review->comment ?? '',
                'created_at' => $review->created_at?->format('d/m/Y H:i'),
            ])->toArray(),
        ];
    }

    private function paymentMethodLabel(?string $method): string
    {
        return [
            'cod' => 'COD',
            'bank_transfer' => 'Chuyển khoản',
            'vnpay' => 'VNPay',
            'momo' => 'MoMo',
            'card' => 'Thẻ',
            'wallet' => 'Ví điện tử',
        ][$method ?? ''] ?? ucfirst((string) $method);
    }

    private function paymentStatusLabel(?string $status): string
    {
        return [
            'pending' => 'Chưa thanh toán',
            'paid' => 'Đã thanh toán',
            'failed' => 'Thất bại',
        ][$status ?? ''] ?? ucfirst((string) $status);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => ['required', OrderStatus::validationRule()],
            'cancellation_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $order = Order::findOrFail($id);
        $user = auth()->user();
        $isSuperAdmin = (bool) $user?->isSuperAdmin();

        // Admin thường chỉ can thiệp đơn thuộc chi nhánh của mình.
        // Super Admin là quyền cao nhất và không bị branch scope chặn.
        if (! $isSuperAdmin && $order->branch_id !== $user->branch_id) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không có quyền cập nhật đơn hàng của chi nhánh khác.',
                ], 403);
            }

            abort(403, 'Không có quyền cập nhật đơn hàng của chi nhánh khác.');
        }

        $newStatus = OrderStatus::normalize((string) $request->status);
        $oldStatus = OrderStatus::normalize((string) $order->status);
        $fulfillmentType = $order->fulfillment_type ?? 'delivery';

        if ($oldStatus === $newStatus) {
            $data = $this->statusUpdateData($order);
            $message = 'Trạng thái đơn hàng không thay đổi.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'order_id' => $order->id,
                    'status' => $oldStatus,
                    'status_label' => $data['status_label'],
                    'status_options' => $data['status_options'],
                    'data' => $data,
                ]);
            }

            return redirect()->back()->with('success', $message);
        }

        // Trang Đơn hàng chỉ được xử lý tới bước shipper đã lấy hàng.
        // Các bước sau đó chỉ xử lý qua trang Sự cố giao vận khi thật sự có sự cố.
        $transitionAllowed = OrderStatus::canOrderManagementAdvanceTo($oldStatus, $newStatus, $fulfillmentType);

        if (! $transitionAllowed) {
            $message = $newStatus === OrderStatus::CANCELLED
                ? 'Không thể hủy đơn sau khi đã xác nhận. Vui lòng xử lý tại mục Sự cố giao vận.'
                : 'Trang Đơn hàng chỉ được chuyển trạng thái tới bước Sẵn sàng giao. Các bước shipper đã lấy hàng/đang giao/đã giao/hoàn thành chỉ xử lý qua luồng giao vận hoặc trang Sự cố giao vận khi có sự cố.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return redirect()->back()->with('error', $message);
        }

        // Hủy đơn là nghiệp vụ có hậu quả voucher/shipper nên mọi vai trò,
        // kể cả Super Admin, vẫn phải ghi lý do để audit rõ ràng.
        if ($newStatus === OrderStatus::CANCELLED && empty($request->cancellation_reason)) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vui lòng nhập lý do hủy đơn hàng.',
                ], 422);
            }

            return redirect()->back()->with('error', 'Vui lòng nhập lý do hủy đơn hàng.');
        }

        // Quyền cao không đồng nghĩa bỏ qua điều kiện nghiệp vụ. Đơn VNPay vẫn phải
        // thanh toán thành công trước khi bất kỳ ai, kể cả Super Admin, xác nhận.
        if ($newStatus === OrderStatus::CONFIRMED
            && $order->payment_method === 'vnpay'
            && $order->payment_status !== 'paid') {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Đơn hàng VNPay phải được thanh toán trước khi xác nhận.',
                ], 422);
            }
            return redirect()->back()->with('error', 'Đơn hàng VNPay phải được thanh toán trước khi xác nhận.');
        }

        if ($newStatus === OrderStatus::PREPARING && ! ScheduledDelivery::canStartPreparation($order)) {
            $message = ScheduledDelivery::preparationBlockedMessage($order);
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return redirect()->back()->with('error', $message);
        }

        $dispatchResult = null;
        $overrideWarning = null;

        if ($isSuperAdmin) {
            $override = app(SuperAdminOrderOverrideService::class)->override(
                $order,
                $newStatus,
                $user,
                $request->cancellation_reason,
            );

            $order = $override['order'];
            $dispatchResult = $override['dispatch'] ?? null;
            $overrideWarning = $override['warning'] ?? null;
        } else {
            if ($newStatus === OrderStatus::CANCELLED && $oldStatus !== OrderStatus::CANCELLED) {
                $cancelResult = app(OrderCancellationService::class)->cancel(
                    $order,
                    (string) $request->cancellation_reason,
                    $user
                );
                $order = $cancelResult['order'];
            } else {
                DB::transaction(function () use ($order, $newStatus, $oldStatus) {
                    $order->status = $newStatus;
                    $order->status_changed_at = now();
                    $order->status_changed_by = auth()->id();

                    if ($newStatus === OrderStatus::DELIVERED && $oldStatus !== OrderStatus::DELIVERED) {
                        $order->delivered_at = now();
                    }

                    $order->save();

                    if ($newStatus === OrderStatus::DELIVERED && $oldStatus !== OrderStatus::DELIVERED) {
                        app(AddressLearning::class)->markOrderDelivered($order->fresh());
                    }
                });
            }

            if ($newStatus === OrderStatus::CONFIRMED
                && $oldStatus !== OrderStatus::CONFIRMED
                && ($order->fulfillment_type ?? 'delivery') === 'delivery') {
                $dispatchResult = app(ShipperDispatchService::class)
                    ->dispatchConfirmedOrder($order->fresh(['branch']));
                $order->refresh();
            }

            if ($newStatus === OrderStatus::COMPLETED && $order->payment_method === 'cod') {
                $order->payment_status = 'paid';
                $order->save();

            }

            if ($newStatus === OrderStatus::COMPLETED && $oldStatus !== OrderStatus::COMPLETED) {
                $order->awardLoyaltyPoints();
            }
        }

        RealtimeOrderNotifier::orderStatusUpdated($order->fresh());

        $statusLabel = OrderStatus::label($newStatus);
        $dispatchSuffix = '';
        if (($dispatchResult['status'] ?? null) === 'assigned') {
            $shipperName = $dispatchResult['shipper']?->user?->name ?: $dispatchResult['shipper']?->code ?: 'shipper';
            $mode = $dispatchResult['dispatch_mode'] ?? 'available';
            $modeText = match ($mode) {
                'returning' => ' (chuyển hướng shipper đang quay về)',
                'bundle' => ' (ghép chuyến thuận đường)',
                default => '',
            };
            $scoreText = isset($dispatchResult['dispatch_score'])
                ? ' · score '.number_format((float) $dispatchResult['dispatch_score'], 1, ',', '.')
                : '';
            $etaSeconds = (float) ($dispatchResult['dispatch_features']['pickup_eta_s'] ?? 0);
            $etaText = $etaSeconds > 0 ? ' · ETA tới quán ~'.number_format($etaSeconds / 60, 1, ',', '.').' phút' : '';
            $dispatchSuffix = " Đã tự động gán cho {$shipperName}{$modeText}{$scoreText}{$etaText}.";
        } elseif (($dispatchResult['status'] ?? null) === 'waiting') {
            $dispatchSuffix = ' Chưa có shipper rảnh phù hợp, đơn đang chờ hệ thống điều phối.';
        } elseif (($dispatchResult['status'] ?? null) === 'error') {
            $dispatchSuffix = ' Điều phối shipper chưa thành công, vui lòng kiểm tra lại.';
        }

        $prefix = $isSuperAdmin ? 'Super Admin đã cập nhật' : 'Đã cập nhật';
        $message = "{$prefix} trạng thái đơn hàng thành: {$statusLabel}.{$dispatchSuffix}";
        if ($overrideWarning) {
            $message .= ' '.$overrideWarning;
        }

        if ($request->expectsJson() || $request->ajax()) {
            $data = $this->statusUpdateData($order->fresh());
            $responseStatusOptions = $this->orderManagementOptionsForOrder($order->fresh());

            return response()->json([
                'success' => true,
                'message' => $message,
                'order_id' => $order->id,
                'status' => $newStatus,
                'status_label' => $statusLabel,
                'super_admin_override' => $isSuperAdmin,
                'status_options' => $responseStatusOptions,
                'data' => $data,
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

    private function statusUpdateData(Order $order): array
    {
        $status = OrderStatus::normalize((string) $order->status);
        $statusOptions = $this->orderManagementOptionsForOrder($order);
        $nextStatus = $this->orderManagementNextStatusForOrder($order);

        return [
            'id' => (int) $order->id,
            'order_code' => $order->displayCode(),
            'status' => $status,
            'status_label' => OrderStatus::label($status),
            'status_icon' => OrderStatus::notificationIcon($status),
            'status_class' => 'status-text-'.$status,
            'status_options' => $statusOptions,
            'next_status' => $nextStatus,
            'can_update' => count($statusOptions) > 1,
            'updated_at' => $order->updated_at?->toIso8601String(),
        ];
    }

    private function orderManagementOptionsForOrder(Order $order): array
    {
        $status = OrderStatus::normalize((string) $order->status);
        $options = OrderStatus::orderManagementOptions($status, $order->fulfillment_type ?? 'delivery');

        return $options;
    }

    private function orderManagementNextStatusForOrder(Order $order): ?string
    {
        $nextStatus = OrderStatus::orderManagementNextStatus((string) $order->status, $order->fulfillment_type ?? 'delivery');

        return $nextStatus;
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
