<?php

namespace App\Http\Controllers\Admin;

use App\Support\OrderStatus;
use App\Support\RealtimeOrderNotifier;
use App\Support\AddressLearning;
use App\Services\ShipperDispatchService;
use App\Services\ShipperIncidentService;
use App\Services\OrderCancellationService;
use App\Services\SuperAdminOrderOverrideService;
use App\Http\Controllers\Controller;
use App\Models\Order;
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
            ->with(['user', 'branch', 'address', 'shipper.user', 'codReceivable.settlement', 'orderItems.product', 'orderItems.productSize.size'])
            // Admin không thấy đơn hàng guest chưa xác nhận email
            ->where('status', '!=', \App\Support\OrderStatus::AWAITING_EMAIL_CONFIRMATION)
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
        
        $latestOrderQuery = Order::query();
        $latestOrderQuery = $this->applyBranchScope($latestOrderQuery);
        $latestOrderId = (int) ($latestOrderQuery->max('id') ?? 0);

        return view('admin.orders.index', compact('orders', 'filters', 'statusOptions', 'latestOrderId', 'shipmentIncidents'));
    }

    public function recent(Request $request): JsonResponse
    {
        $afterId = max(0, (int) $request->query('after_id', 0));
        $updatedAfter = $request->query('updated_after');

        $orders = Order::query()
            ->with(['user', 'branch', 'address', 'shipper.user', 'codReceivable.settlement', 'orderItems.product', 'orderItems.productSize.size'])
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
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(fn (Order $order) => $this->orderBroadcastPayload($order))
            ->values();

        $latestOrderQuery = Order::query();
        $latestOrderQuery = $this->applyBranchScope($latestOrderQuery);
        $latestOrderId = (int) ($latestOrderQuery->max('id') ?? 0);

        return response()->json([
            'orders' => $orders,
            'latest_id' => $latestOrderId,
        ]);
    }

    public function pendingAlerts(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->with(['user', 'branch', 'address', 'shipper.user', 'codReceivable.settlement', 'orderItems.product', 'orderItems.productSize.size'])
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

    private function orderBroadcastPayload(Order $order): array
    {
        $customerName = $order->customerName() ?: 'Khách hàng';
        $customerEmail = $order->customerEmail() ?: '';
        $customerPhone = $order->customerPhone() ?: '';
        $total = (int) ($order->total ?? $order->total_price ?? 0);
        $fulfillmentType = $order->fulfillment_type ?? 'delivery';
        $shipmentIncident = app(ShipperIncidentService::class)->pendingIncident($order);
        $shipper = $order->shipper;
        $user = auth()->user();
        $isSuperAdmin = (bool) $user?->isSuperAdmin();
        $isSuperAdminWorkspace = $isSuperAdmin && ! $user->isViewingAdminWorkspace();
        $currentStatus = OrderStatus::normalize((string) $order->status);
        $statusOptionsForActor = $isSuperAdmin
            ? OrderStatus::superAdminOptions($currentStatus, $fulfillmentType)
            : OrderStatus::storeStepwiseOptions($currentStatus, $fulfillmentType);
        $canCancelForActor = $isSuperAdmin
            ? OrderStatus::canSuperAdminCancelFrom($currentStatus)
            : in_array($currentStatus, [OrderStatus::PENDING, OrderStatus::CONFIRMED, OrderStatus::PREPARING], true);
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
            'cod_reconciliation' => $order->payment_method === 'cod' && $order->codReceivable ? [
                'amount' => (int) $order->codReceivable->amount,
                'is_settled' => (bool) $order->codReceivable->settlement_id,
                'settled_at' => $order->codReceivable->settled_at?->format('d/m/Y H:i'),
            ] : null,
            'shipping_address' => $order->getShippingAddress(),
            'shipper' => $shipper ? [
                'id' => (int) $shipper->id,
                'name' => $shipper->user?->name ?: $shipper->code ?: 'Shipper',
                'code' => $shipper->code,
                'phone' => $shipper->phone ?: $shipper->user?->phone,
                'vehicle_type' => $shipper->vehicle_type,
                'license_plate' => $shipper->license_plate,
                'status' => $shipper->status,
            ] : null,
            'delivered_at' => $order->delivered_at?->format('d/m/Y H:i:s'),
            'auto_complete_at' => $order->delivered_at?->copy()->addMinutes(\App\Services\DeliveredOrderCompletionService::AUTO_COMPLETE_AFTER_MINUTES)->format('d/m/Y H:i:s'),
            'status' => $order->status,
            'status_label' => OrderStatus::label((string) $order->status),
            'status_changed_at' => $order->status_changed_at?->format('d/m/Y H:i'),
            'status_changed_by_name' => $order->status_changed_by
                ? (\App\Models\User::find($order->status_changed_by)?->name ?? 'Nhân viên')
                : null,
            'next_status' => $isSuperAdmin
                ? OrderStatus::nextStatus((string) $order->status, $fulfillmentType)
                : OrderStatus::storeNextStatus((string) $order->status, $fulfillmentType),
            'can_cancel' => $canCancelForActor,
            'status_options' => $statusOptionsForActor,
            'super_admin_override' => $isSuperAdmin,
            'created_at' => $order->created_at?->format('d/m/Y H:i'),
            'created_at_iso' => $order->created_at?->toIso8601String(),
            'scheduled_at' => $order->scheduled_at?->format('H:i · d/m/Y'),
            'delivery_type' => $order->delivery_type,
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

        // Super Admin có quyền thao tác mọi bước của đơn, kể cả bước vốn thuộc shipper,
        // nhưng vẫn phải đi ĐÚNG TRÌNH TỰ của state machine. Admin/Staff thường chỉ
        // được thao tác phần trạng thái thuộc cửa hàng.
        $transitionAllowed = $isSuperAdmin
            ? ($newStatus === OrderStatus::CANCELLED
                ? OrderStatus::canSuperAdminCancelFrom($oldStatus)
                : OrderStatus::canSuperAdminAdvanceTo($oldStatus, $newStatus, $fulfillmentType))
            : OrderStatus::canStoreAdvanceTo((string) $order->status, $newStatus, $fulfillmentType);

        if (! $transitionAllowed) {
            $message = $isSuperAdmin
                ? 'Super Admin có quyền thực hiện bước của mọi vai trò, nhưng chỉ được chuyển đúng sang bước kế tiếp của đơn; không được nhảy cóc hoặc quay lùi.'
                : 'Quán chỉ được xử lý đơn giao hàng tới bước Sẵn sàng giao. Các bước lấy hàng/đang giao/đã giao thuộc tài xế.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return redirect()->back()->with('error', $message);
        }

        // Hủy đơn là nghiệp vụ có hậu quả tồn kho/voucher/shipper nên mọi vai trò,
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

        // Super Admin có thể thao tác thay shipper ở đúng bước, nhưng các bước vật lý
        // của giao hàng vẫn cần một shipper thực sự đang được gán cho đơn.
        if ($isSuperAdmin
            && in_array($newStatus, [OrderStatus::SHIPPER_PICKED_UP, OrderStatus::DELIVERING, OrderStatus::DELIVERED], true)
            && ! $order->shipper_id) {
            $message = 'Đơn chưa được gán shipper nên chưa thể thực hiện bước '.OrderStatus::label($newStatus).'.';
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

            return response()->json([
                'success' => true,
                'message' => $message,
                'order_id' => $order->id,
                'status' => $newStatus,
                'status_label' => $statusLabel,
                'super_admin_override' => $isSuperAdmin,
                'status_options' => $isSuperAdmin
                    ? OrderStatus::superAdminOptions($newStatus, $fulfillmentType)
                    : OrderStatus::storeStepwiseOptions($newStatus, $fulfillmentType),
                'data' => $data,
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

    private function statusUpdateData(Order $order): array
    {
        $status = OrderStatus::normalize((string) $order->status);
        $isSuperAdmin = (bool) auth()->user()?->isSuperAdmin();
        $statusOptions = $isSuperAdmin
            ? OrderStatus::superAdminOptions($status, $order->fulfillment_type ?? 'delivery')
            : OrderStatus::storeStepwiseOptions($status, $order->fulfillment_type ?? 'delivery');
        $nextStatus = collect(array_keys($statusOptions))->first(fn (string $option) => $option !== $status);

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
