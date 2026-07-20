<?php

namespace App\Http\Controllers\Admin;

use App\Support\OrderStatus;
use App\Support\RealtimeOrderNotifier;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
            ->with(['user', 'branch', 'address', 'orderItems.product', 'orderItems.productSize.size'])
            // Admin không thấy đơn hàng guest chưa xác nhận email
            ->where('status', '!=', \App\Support\OrderStatus::AWAITING_EMAIL_CONFIRMATION)
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $keyword = $filters['q'];

                // Remove # prefix if exists
                $cleanKeyword = ltrim($keyword, '#');

                $query->where(function ($subQuery) use ($keyword, $cleanKeyword) {
                    // Search by order ID (with or without #)
                    if (is_numeric($cleanKeyword)) {
                        $subQuery->where('id', (int) $cleanKeyword);
                    }

                    // Search by customer name or email
                    $subQuery->orWhereHas('user', function ($userQuery) use ($keyword) {
                        $userQuery
                            ->where('name', 'like', '%'.$keyword.'%')
                            ->orWhere('email', 'like', '%'.$keyword.'%');
                    });
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
        
        $latestOrderQuery = Order::query();
        $latestOrderQuery = $this->applyBranchScope($latestOrderQuery);
        $latestOrderId = (int) ($latestOrderQuery->max('id') ?? 0);

        return view('admin.orders.index', compact('orders', 'filters', 'statusOptions', 'latestOrderId'));
    }

    public function recent(Request $request): JsonResponse
    {
        $afterId = max(0, (int) $request->query('after_id', 0));

        $orders = Order::query()
            ->with(['user', 'branch', 'address', 'orderItems.product', 'orderItems.productSize.size'])
            ->where('status', '!=', \App\Support\OrderStatus::AWAITING_EMAIL_CONFIRMATION)
            ->when($afterId > 0, fn ($query) => $query->where('id', '>', $afterId));
        
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

    private function orderBroadcastPayload(Order $order): array
    {
        $customerName = $order->customerName() ?: 'Khách hàng';
        $customerEmail = $order->customerEmail() ?: '';
        $customerPhone = $order->customerPhone() ?: '';
        $total = (int) ($order->total ?? $order->total_price ?? 0);
        $fulfillmentType = $order->fulfillment_type ?? 'delivery';

        return [
            'order_id' => $order->id,
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
            'status' => $order->status,
            'status_label' => OrderStatus::label((string) $order->status),
            'next_status' => OrderStatus::nextStatus((string) $order->status, $fulfillmentType),
            'can_cancel' => ! in_array(OrderStatus::normalize((string) $order->status), [OrderStatus::COMPLETED, OrderStatus::CANCELLED], true),
            'status_options' => OrderStatus::stepwiseOptions((string) $order->status, $fulfillmentType),
            'created_at' => $order->created_at?->format('d/m/Y H:i'),
            'scheduled_at' => $order->scheduled_at?->format('H:i · d/m/Y'),
            'delivery_type' => $order->delivery_type,
            'delivery_note' => $order->delivery_note,
            'scheduled_delivery_time' => $order->scheduled_delivery_time?->format('H:i · d/m/Y'),
            'message' => "Đơn hàng mới #{$order->id} từ {$customerName}",
            'status_update_url' => route('admin.orders.updateStatus', $order->id),
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
        
        // Check if current user (Admin) can access this order's branch
        $user = auth()->user();
        if (!$user->isSuperAdmin() && $order->branch_id !== $user->branch_id) {
            abort(403, 'Không có quyền cập nhật đơn hàng của chi nhánh khác.');
        }

        $newStatus = OrderStatus::normalize($request->status);
        $fulfillmentType = $order->fulfillment_type ?? 'delivery';

        // Kiểm tra yêu cầu lý do hủy
        if ($newStatus === OrderStatus::CANCELLED && empty($request->cancellation_reason)) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vui lòng nhập lý do hủy đơn hàng.',
                ], 422);
            }
            return redirect()->back()->with('error', 'Vui lòng nhập lý do hủy đơn hàng.');
        }

        // Kiểm tra logic chuyển trạng thái
        if (! OrderStatus::canAdvanceTo((string) $order->status, $newStatus, $fulfillmentType)) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể chuyển sang trạng thái này. Chỉ được chuyển sang bước tiếp theo hoặc hủy đơn (nếu được phép).',
                ], 422);
            }
            return redirect()->back()->with('error', 'Không thể chuyển sang trạng thái này. Chỉ được chuyển sang bước tiếp theo hoặc hủy đơn (nếu được phép).');
        }

        // Kiểm tra thanh toán VNPay trước khi xác nhận
        if ($newStatus === OrderStatus::CONFIRMED && 
            $order->payment_method === 'vnpay' && 
            $order->payment_status !== 'paid') {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Đơn hàng VNPay phải được thanh toán trước khi xác nhận.',
                ], 422);
            }
            return redirect()->back()->with('error', 'Đơn hàng VNPay phải được thanh toán trước khi xác nhận.');
        }

        $oldStatus = $order->status;
        $order->status = $newStatus;
        
        // Lưu lý do hủy nếu trạng thái là cancelled
        if ($newStatus === OrderStatus::CANCELLED) {
            $order->cancellation_reason = $request->cancellation_reason;
        }
        
        // Lưu thời gian giao hàng nếu chuyển sang DELIVERED
        if ($newStatus === OrderStatus::DELIVERED && $oldStatus !== OrderStatus::DELIVERED) {
            $order->delivered_at = now();
        }
        
        $order->save();

        // Nếu chuyển sang COMPLETED, cập nhật payment_status cho COD và cộng điểm thưởng
        if ($newStatus === OrderStatus::COMPLETED && $order->payment_method === 'cod') {
            $order->payment_status = 'paid';
            $order->save();
        }
        
        // Award loyalty points when order is completed
        if ($newStatus === OrderStatus::COMPLETED && $oldStatus !== OrderStatus::COMPLETED) {
            $order->awardLoyaltyPoints();
        }

        RealtimeOrderNotifier::orderStatusUpdated($order);

        $statusLabel = OrderStatus::label($newStatus);
        
        // Return JSON for AJAX requests
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Đã cập nhật trạng thái đơn hàng thành: {$statusLabel}",
                'order_id' => $order->id,
                'status' => $newStatus,
                'status_label' => $statusLabel,
            ]);
        }
        
        return redirect()->back()->with('success', "Đã cập nhật trạng thái đơn hàng thành: {$statusLabel}");
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