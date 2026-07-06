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
            'date_from' => trim((string) $request->query('date_from', '')),
            'date_to' => trim((string) $request->query('date_to', '')),
        ];

        $statusOptions = OrderStatus::filterOptions();

        $orders = Order::query()
            ->with(['user', 'orderItems'])
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
            ->with('user')
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
        $customerName = $order->user->name ?? 'Khách hàng';
        $total = (int) ($order->total ?? $order->total_price ?? 0);

        return [
            'order_id' => $order->id,
            'customer_name' => $customerName,
            'customer_email' => $order->user->email ?? '',
            'total' => $total,
            'total_formatted' => number_format($total, 0, ',', '.').'đ',
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status,
            'status' => $order->status,
            'status_label' => OrderStatus::label((string) $order->status),
            'status_options' => OrderStatus::selectableOptions((string) $order->status),
            'created_at' => $order->created_at?->format('d/m/Y H:i'),
            'message' => "Đơn hàng mới #{$order->id} từ {$customerName}",
            'status_update_url' => route('admin.orders.updateStatus', $order->id),
        ];
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
        
        // Check if current user (Admin) can access this order's branch
        $user = auth()->user();
        if (!$user->isSuperAdmin() && $order->branch_id !== $user->branch_id) {
            abort(403, 'Không có quyền cập nhật đơn hàng của chi nhánh khác.');
        }

        $newStatus = OrderStatus::normalize($request->status);

        if (! OrderStatus::canTransition((string) $order->status, $newStatus)) {
            return redirect()->back()->with('error', 'Không thể quay lại trạng thái trước.');
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
