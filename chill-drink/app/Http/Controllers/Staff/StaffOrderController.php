<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\ShipperDispatchService;
use App\Services\OrderCancellationService;
use App\Support\OrderStatus;
use App\Support\RealtimeOrderNotifier;
use App\Support\ScheduledDelivery;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StaffOrderController extends Controller
{
    /**
     * Nhân viên chỉ xem được đơn hàng của chi nhánh mình.
     */
    private function applyBranchScope($query)
    {
        $user = auth()->user();

        if ($user->branch_id) {
            return $query->where('branch_id', $user->branch_id);
        }

        // Nhân viên chưa được gán chi nhánh: không thấy đơn nào
        return $query->whereRaw('1 = 0');
    }

    public function index(Request $request)
    {
        $filters = [
            'q'              => trim((string) $request->query('q', '')),
            'status'         => trim((string) $request->query('status', '')),
            'payment_status' => trim((string) $request->query('payment_status', '')),
            'date_from'      => trim((string) $request->query('date_from', '')),
            'date_to'        => trim((string) $request->query('date_to', '')),
            'scope'          => trim((string) $request->query('scope', '')),
            'order_type'     => trim((string) $request->query('order_type', '')),
        ];

        $orderTypeOptions = [
            '' => 'Tất cả loại đơn',
            'regular' => 'Đơn thường',
            'scheduled' => 'Đơn giao sau',
            'group' => 'Đơn nhóm',
        ];
        if (! array_key_exists($filters['order_type'], $orderTypeOptions)) {
            $filters['order_type'] = '';
        }

        $dashboardScopes = [
            'work' => 'Công việc hiện tại',
            'new' => 'Đơn mới',
            'preparing' => 'Đang chuẩn bị',
            'ready_delivery' => 'Chờ bàn giao',
            'ready_pickup' => 'Chờ khách lấy',
            'today' => 'Đơn trong ngày',
        ];
        if (! isset($dashboardScopes[$filters['scope']])) {
            $filters['scope'] = '';
        }

        $statusOptions = OrderStatus::filterOptions();

        $orders = Order::query()
            ->with([
                'user',
                'branch',
                'address',
                'orderItems.product',
                'orderItems.productSize.size',
                'orderItems.toppingLines.topping',
                'groupOrder',
                'statusChangedBy',
                'statusHistories.actor',
            ])
            ->where('status', '!=', OrderStatus::AWAITING_EMAIL_CONFIRMATION)
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $keyword       = $filters['q'];
                $cleanKeyword  = ltrim($keyword, '#');
                $query->where(function ($sub) use ($keyword, $cleanKeyword) {
                    $sub->where('order_code', 'like', '%' . $cleanKeyword . '%');
                    if (is_numeric($cleanKeyword)) {
                        $sub->orWhere('id', (int) $cleanKeyword);
                        return;
                    }
                    $sub->orWhereHas('user', fn ($u) => $u->where('name', 'like', '%' . $keyword . '%')
                        ->orWhere('email', 'like', '%' . $keyword . '%'));
                    if (Schema::hasColumn('orders', 'guest_name')) {
                        $sub->orWhere('guest_name', 'like', '%' . $keyword . '%');
                    }
                });
            })
            ->when(isset($statusOptions[$filters['status']]) && $filters['status'] !== '',
                fn ($q) => $q->where('status', $filters['status']))
            ->when($filters['payment_status'] !== '',
                fn ($q) => $q->where('payment_status', $filters['payment_status']));

        match ($filters['order_type']) {
            'group' => $orders->whereHas('groupOrder'),
            'scheduled' => $orders
                ->whereDoesntHave('groupOrder')
                ->where('delivery_type', 'scheduled'),
            'regular' => $orders
                ->whereDoesntHave('groupOrder')
                ->where(fn ($query) => $query
                    ->whereNull('delivery_type')
                    ->orWhere('delivery_type', '!=', 'scheduled')),
            default => null,
        };

        if ($filters['status'] === '') {
            match ($filters['scope']) {
                'work' => $orders->whereIn('status', [
                    OrderStatus::PENDING,
                    OrderStatus::CONFIRMED,
                    OrderStatus::PREPARING,
                    OrderStatus::READY_FOR_DELIVERY,
                    OrderStatus::READY_FOR_PICKUP,
                ]),
                'new' => $orders->where('status', OrderStatus::PENDING),
                'preparing' => $orders->whereIn('status', [OrderStatus::CONFIRMED, OrderStatus::PREPARING]),
                'ready_delivery' => $orders
                    ->where('fulfillment_type', 'delivery')
                    ->where('status', OrderStatus::READY_FOR_DELIVERY),
                'ready_pickup' => $orders
                    ->where('fulfillment_type', 'pickup')
                    ->where('status', OrderStatus::READY_FOR_PICKUP),
                'today' => $orders->whereDate('created_at', today()),
                default => null,
            };
        }

        if (Schema::hasColumn('orders', 'created_at')) {
            if ($filters['date_from'] !== '' && $filters['date_to'] !== '') {
                $orders->whereBetween('created_at', [$filters['date_from'] . ' 00:00:00', $filters['date_to'] . ' 23:59:59']);
            } elseif ($filters['date_from'] !== '') {
                $orders->where('created_at', '>=', $filters['date_from'] . ' 00:00:00');
            } elseif ($filters['date_to'] !== '') {
                $orders->where('created_at', '<=', $filters['date_to'] . ' 23:59:59');
            }
        }

        $orders = $this->applyBranchScope($orders)->latest()->paginate(12)->withQueryString();

        return view('staff.orders.index', compact('orders', 'filters', 'statusOptions', 'dashboardScopes', 'orderTypeOptions'));
    }

    public function pendingAlerts(): JsonResponse
    {
        $user = auth()->user();
        abort_unless($user?->isStaffOnly() && is_numeric($user->branch_id), 403);
        $mutedOrderIds = collect((array) request()->query('muted_order_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->take(30)
            ->values();

        $baseQuery = Order::query()
            ->where('branch_id', (int) $user->branch_id)
            ->where('status', OrderStatus::PENDING)
            ->where('status', '!=', OrderStatus::AWAITING_EMAIL_CONFIRMATION);

        $orders = (clone $baseQuery)
            ->when($mutedOrderIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $mutedOrderIds->all()))
            ->with([
                'user',
                'branch',
                'address',
                'orderItems.product',
                'orderItems.productSize.size',
                'orderItems.toppingLines.topping',
            ])
            ->oldest('created_at')
            ->limit(1)
            ->get()
            ->map(fn (Order $order) => $this->pendingAlertPayload($order))
            ->values();

        return response()->json([
            'orders' => $orders,
            'pending_count' => (int) $baseQuery->count(),
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    private function pendingAlertPayload(Order $order): array
    {
        $currentStatus = OrderStatus::normalize((string) $order->status);
        $canConfirm = $currentStatus === OrderStatus::PENDING
            && ! ($order->payment_method === 'vnpay' && $order->payment_status !== 'paid');

        return [
            'order_id' => (int) $order->id,
            'order_code' => $order->displayCode(),
            'branch_id' => is_numeric($order->branch_id) ? (int) $order->branch_id : null,
            'branch_name' => $order->branch?->name ?? 'Chưa gán',
            'customer_name' => $order->customerName() ?: 'Khách hàng',
            'customer_email' => $order->customerEmail() ?: '',
            'customer_phone' => $order->customerPhone() ?: '',
            'payment_method' => $order->payment_method,
            'payment_method_label' => match ($order->payment_method) {
                'cod' => 'COD',
                'bank_transfer' => 'Chuyển khoản',
                'vnpay' => 'VNPay',
                'momo' => 'MoMo',
                'card' => 'Thẻ',
                'wallet' => 'Ví điện tử',
                default => ucfirst((string) $order->payment_method),
            },
            'payment_status' => $order->payment_status,
            'payment_status_label' => match ($order->payment_status) {
                'pending' => 'Chưa thanh toán',
                'paid' => 'Đã thanh toán',
                'failed' => 'Thất bại',
                default => ucfirst((string) $order->payment_status),
            },
            'total_formatted' => number_format((int) ($order->total ?? $order->total_price ?? 0), 0, ',', '.').'đ',
            'shipping_address' => $order->getShippingAddress(),
            'note' => $order->note ?: $order->delivery_note,
            'customer_note' => $order->customerNote(),
            'delivery_info_note' => $order->deliveryInfoNote(),
            'created_at' => $order->created_at?->format('d/m/Y H:i'),
            'can_confirm' => $canConfirm,
            'confirm_block_reason' => $canConfirm ? null : 'Đơn VNPay phải thanh toán thành công trước khi xác nhận.',
            'status_update_url' => route('staff.orders.updateStatus', $order->id),
            'url' => route('staff.orders.index', ['q' => $order->displayCode()]),
            'items' => $order->orderItems->map(fn ($item) => [
                'product_name' => $item->product?->name ?? 'Sản phẩm đã xóa',
                'size_name' => $item->productSize?->size?->name ?? 'Chưa chọn',
                'ice_level' => (int) $item->ice_level,
                'sugar_level' => (int) $item->sugar_level,
                'quantity' => (int) $item->quantity,
                'item_note' => $item->item_note,
                'toppings' => $item->toppingLines->map(fn ($line) => $line->topping?->name)->filter()->values()->all(),
                'total_formatted' => number_format((int) $item->getSubtotal(), 0, ',', '.').'đ',
            ])->values()->all(),
        ];
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status'              => ['required', OrderStatus::validationRule()],
            'cancellation_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $order = Order::findOrFail($id);
        $user  = auth()->user();

        // Nhân viên chỉ cập nhật đơn hàng thuộc chi nhánh mình
        if (!$user->branch_id || (int) $order->branch_id !== (int) $user->branch_id) {
            abort(403, 'Bạn không có quyền cập nhật đơn hàng của chi nhánh khác.');
        }

        $newStatus      = OrderStatus::normalize($request->status);
        $fulfillmentType = $order->fulfillment_type ?? 'delivery';

        if ($newStatus === OrderStatus::CANCELLED && empty($request->cancellation_reason)) {
            $message = 'Vui lòng nhập lý do hủy đơn hàng.';
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $message], 422)
                : redirect()->back()->with('error', $message);
        }

        if (!OrderStatus::canStoreAdvanceTo((string) $order->status, $newStatus, $fulfillmentType)) {
            $message = 'Quán chỉ được xử lý đơn giao hàng tới bước Sẵn sàng giao. Các bước sau thuộc tài xế.';
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $message], 422)
                : redirect()->back()->with('error', $message);
        }

        if ($newStatus === OrderStatus::CONFIRMED
            && OrderStatus::normalize((string) $order->status) === OrderStatus::CONFIRMED) {
            $message = 'Đơn hàng đã được nhân viên khác nhận hoặc trạng thái đã thay đổi.';

            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $message], 409)
                : redirect()->back()->with('error', $message);
        }

        if ($newStatus === OrderStatus::CONFIRMED &&
            $order->payment_method === 'vnpay' &&
            $order->payment_status !== 'paid') {
            $message = 'Đơn hàng VNPay phải được thanh toán trước khi xác nhận.';
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $message], 422)
                : redirect()->back()->with('error', $message);
        }

        if ($newStatus === OrderStatus::PREPARING && ! ScheduledDelivery::canStartPreparation($order)) {
            $message = ScheduledDelivery::preparationBlockedMessage($order);
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $message], 422)
                : redirect()->back()->with('error', $message);
        }

        $oldStatus = $order->status;

        if ($newStatus === OrderStatus::CANCELLED && OrderStatus::normalize((string) $oldStatus) !== OrderStatus::CANCELLED) {
            $cancelResult = app(OrderCancellationService::class)->cancel(
                $order,
                (string) $request->cancellation_reason,
                $user
            );
            $order = $cancelResult['order'];
        } elseif ($newStatus === OrderStatus::CONFIRMED) {
            $updated = DB::transaction(function () use ($order, $newStatus, $oldStatus, $user) {
                return Order::query()
                    ->whereKey($order->id)
                    ->where('status', $oldStatus)
                    ->update([
                        'status' => $newStatus,
                        'status_changed_at' => now(),
                        'status_changed_by' => $user->id,
                        'updated_at' => now(),
                    ]);
            });

            if ($updated !== 1) {
                $message = 'Đơn hàng đã được nhân viên khác nhận hoặc trạng thái đã thay đổi.';

                return $request->expectsJson()
                    ? response()->json(['success' => false, 'message' => $message], 409)
                    : redirect()->back()->with('error', $message);
            }

            $order->refresh();
        } else {
            DB::transaction(function () use ($order, $newStatus, $oldStatus, $user) {
                $order->status            = $newStatus;
                $order->status_changed_at = now();
                $order->status_changed_by = $user->id;

                if ($newStatus === OrderStatus::DELIVERED && OrderStatus::normalize((string) $oldStatus) !== OrderStatus::DELIVERED) {
                    $order->delivered_at = now();
                }

                $order->save();
            });
        }


        $dispatchResult = null;
        if ($newStatus === OrderStatus::READY_FOR_DELIVERY
            && OrderStatus::normalize((string) $oldStatus) !== OrderStatus::READY_FOR_DELIVERY
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

        RealtimeOrderNotifier::orderStatusUpdated($order);

        $message = 'Đã cập nhật trạng thái: ' . OrderStatus::label($newStatus) . '.';
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
            $message .= " Đã tự động gán cho {$shipperName}{$modeText}{$scoreText}{$etaText}.";
        } elseif (($dispatchResult['status'] ?? null) === 'waiting') {
            $message .= ' Chưa có shipper rảnh phù hợp, đơn đang chờ hệ thống điều phối.';
        } elseif (($dispatchResult['status'] ?? null) === 'error') {
            $message .= ' Điều phối shipper chưa thành công, vui lòng kiểm tra lại.';
        }

        if ($request->expectsJson()) {
            $freshOrder = $order->fresh();
            $freshStatus = OrderStatus::normalize((string) $freshOrder->status);
            $freshFulfillmentType = $freshOrder->fulfillment_type ?? 'delivery';
            $freshOptions = OrderStatus::storeStepwiseOptions($freshStatus, $freshFulfillmentType);

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'id' => (int) $freshOrder->id,
                    'order_id' => (int) $freshOrder->id,
                    'order_code' => $freshOrder->displayCode(),
                    'customer_name' => $freshOrder->customerName() ?: 'Khách hàng',
                    'created_at' => $freshOrder->created_at?->format('H:i · d/m/Y'),
                    'fulfillment_type' => $freshFulfillmentType,
                    'total_formatted' => number_format((int) ($freshOrder->total ?? $freshOrder->total_price ?? 0), 0, ',', '.').'đ',
                    'status' => $freshStatus,
                    'status_label' => OrderStatus::label($freshStatus),
                    'status_class' => 'status-text-'.$freshStatus,
                    'status_options' => $freshOptions,
                    'next_status' => OrderStatus::storeNextStatus($freshStatus, $freshFulfillmentType),
                    'status_update_url' => route('staff.orders.updateStatus', $freshOrder->id),
                    'can_update' => count($freshOptions) > 1,
                    'updated_at' => $freshOrder->updated_at?->toIso8601String(),
                    'status_changed_at' => $freshOrder->status_changed_at?->format('d/m H:i'),
                    'status_changed_by_name' => $user->name,
                ],
            ]);
        }

        return redirect()->back()->with('success', $message);
    }
}
