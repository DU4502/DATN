<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\GroupOrder;
use App\Models\Order;
use App\Support\OrderStatus;

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

        $todayOrders = (clone $orderQuery)->whereDate('created_at', today())->count();
        $newOrders = (clone $orderQuery)->where('status', OrderStatus::PENDING)->count();
        $preparingOrders = (clone $orderQuery)
            ->whereIn('status', [OrderStatus::CONFIRMED, OrderStatus::PREPARING])
            ->count();
        $readyForDeliveryOrders = (clone $orderQuery)
            ->where('fulfillment_type', 'delivery')
            ->where('status', OrderStatus::READY_FOR_DELIVERY)
            ->count();
        $readyForPickupOrders = (clone $orderQuery)
            ->where('fulfillment_type', 'pickup')
            ->where('status', OrderStatus::READY_FOR_PICKUP)
            ->count();

        $groupOrderQuery = GroupOrder::query();
        if ($branchId) {
            $groupOrderQuery->where('branch_id', $branchId);
        } else {
            $groupOrderQuery->whereRaw('1 = 0');
        }

        $groupOrdersToHandle = (clone $groupOrderQuery)
            ->whereIn('status', ['open', 'closed'])
            ->count();

        // Ưu tiên các đơn cũ nhất còn thuộc phạm vi xử lý tại quán.
        $workStatuses = [
            OrderStatus::PENDING,
            OrderStatus::CONFIRMED,
            OrderStatus::PREPARING,
            OrderStatus::READY_FOR_DELIVERY,
            OrderStatus::READY_FOR_PICKUP,
        ];

        $recentOrders = (clone $orderQuery)
            ->with(['user', 'branch'])
            ->whereIn('status', $workStatuses)
            ->oldest()
            ->take(8)
            ->get();

        $totalWork = $newOrders
            + $preparingOrders
            + $readyForDeliveryOrders
            + $readyForPickupOrders
            + $groupOrdersToHandle;

        return view('staff.dashboard', compact(
            'todayOrders',
            'newOrders',
            'preparingOrders',
            'readyForDeliveryOrders',
            'readyForPickupOrders',
            'groupOrdersToHandle',
            'totalWork',
            'recentOrders',
        ));
    }
}
