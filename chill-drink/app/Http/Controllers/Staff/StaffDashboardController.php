<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\GroupOrder;
use App\Models\Order;
use App\Support\OrderStatus;
use Illuminate\Support\Facades\DB;

class StaffDashboardController extends Controller
{
    public function index()
    {
        $user     = auth()->user();
        $branchId = $user->branch_id;

        $orderQuery = Order::query()
            ->where('status', '!=', OrderStatus::AWAITING_EMAIL_CONFIRMATION);

        if ($branchId) {
            $orderQuery->where('branch_id', $branchId);
        } else {
            $orderQuery->whereRaw('1 = 0');
        }

        $todayOrders   = (clone $orderQuery)->whereDate('created_at', today())->count();
        $pendingOrders = (clone $orderQuery)->whereIn('status', [OrderStatus::PENDING, OrderStatus::CONFIRMED])->count();
        $todayRevenue  = (clone $orderQuery)->whereDate('created_at', today())
            ->where(fn ($q) => $q->where('payment_status', 'paid')->orWhere('status', 'completed'))
            ->sum('total');

        $groupOrderQuery = GroupOrder::query();
        if ($branchId) {
            $groupOrderQuery->where(function ($q) use ($branchId) {
                $q->whereDoesntHave('order')
                  ->orWhereHas('order', fn ($o) => $o->where('branch_id', $branchId));
            });
        } else {
            $groupOrderQuery->whereRaw('1 = 0');
        }

        $openGroups = (clone $groupOrderQuery)->where('status', 'open')->count();

        // 5 đơn hàng gần nhất cần xử lý
        $recentOrders = (clone $orderQuery)
            ->with(['user', 'branch'])
            ->whereIn('status', [OrderStatus::PENDING, OrderStatus::CONFIRMED, OrderStatus::PREPARING])
            ->latest()
            ->take(5)
            ->get();

        // Thống kê trạng thái
        $statusStats = [];
        foreach (OrderStatus::filterOptions() as $status => $label) {
            $statusStats[$label] = (clone $orderQuery)->where('status', $status)->count();
        }

        return view('staff.dashboard', compact(
            'todayOrders',
            'pendingOrders',
            'todayRevenue',
            'openGroups',
            'recentOrders',
            'statusStats',
        ));
    }
}
