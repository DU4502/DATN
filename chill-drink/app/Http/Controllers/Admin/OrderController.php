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

        $orders = Schema::hasColumn('orders', 'created_at')
            ? $orders->latest()
            : $orders->orderByDesc('id');

        $orders = $orders->paginate(12)->withQueryString();
        $latestOrderId = (int) (Order::query()->max('id') ?? 0);

        return view('admin.orders.index', compact('orders', 'filters', 'statusOptions', 'latestOrderId'));
    }

    public function recent(Request $request): JsonResponse
    {
        $afterId = max(0, (int) $request->query('after_id', 0));

        $orders = Order::query()
            ->with(['user', 'branch'])
            ->where('status', '!=', \App\Support\OrderStatus::AWAITING_EMAIL_CONFIRMATION)
            ->when($afterId > 0, fn ($query) => $query->where('id', '>', $afterId))
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(fn (Order $order) => $this->orderBroadcastPayload($order))
            ->values();

        return response()->json([
            'orders' => $orders,
            'latest_id' => (int) (Order::query()->max('id') ?? 0),
        ]);
    }

    private function orderBroadcastPayload(Order $order): array
    {
        $customerName = $order->user->name ?? 'Khách hàng';
        $total = (int) ($order->total ?? $order->total_price ?? 0);

        return [
            'order_id' => $order->id,
            'branch_name' => $order->branch?->name ?? 'Chưa gán',
            'customer_name' => $customerName,
            'customer_email' => $order->user->email ?? '',
            'customer_phone' => $order->user->phone ?? '',
            'note' => $order->note ?? '',
            'total' => $total,
            'total_formatted' => number_format($total, 0, ',', '.').'đ',
            'subtotal_formatted' => number_format((int) ($order->subtotal ?? 0), 0, ',', '.').'đ',
            'shipping_fee_formatted' => number_format((int) ($order->shipping_fee ?? 0), 0, ',', '.').'đ',
            'discount_formatted' => number_format((int) ($order->discount ?? 0), 0, ',', '.').'đ',
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status,
            'payment_method_label' => $this->paymentMethodLabel($order->payment_method),
            'payment_status_label' => $this->paymentStatusLabel($order->payment_status),
            'address_receiver_name' => $order->address?->receiver_name,
            'address_detail' => $order->address?->detail ?? $order->user?->address,
            'status' => $order->status,
            'status_label' => OrderStatus::label((string) $order->status),
            'next_status' => OrderStatus::nextStatus((string) $order->status),
            'can_cancel' => ! in_array(OrderStatus::normalize((string) $order->status), [OrderStatus::COMPLETED, OrderStatus::CANCELLED], true),
            'status_options' => OrderStatus::stepwiseOptions((string) $order->status),
            'created_at' => $order->created_at?->format('d/m/Y H:i'),
            'message' => "Đơn hàng mới #{$order->id} từ {$customerName}",
            'status_update_url' => route('admin.orders.updateStatus', $order->id),
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
        ]);

        $order = Order::findOrFail($id);
        $newStatus = OrderStatus::normalize($request->status);

        if (! OrderStatus::canAdvanceTo((string) $order->status, $newStatus)) {
            return redirect()->back()->with('error', 'Chỉ được chuyển sang bước tiếp theo hoặc hủy đơn.');
        }

        $order->status = $newStatus;
        $order->save();

        RealtimeOrderNotifier::orderStatusUpdated($order);

        return redirect()->back()->with('success', 'Cập nhật trạng thái thành công!');
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
