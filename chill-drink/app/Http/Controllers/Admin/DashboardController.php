<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    /**
     * Display admin dashboard
     */
    public function index()
    {
        // Get statistics
        $totalUsers = User::customers()->count();
        $totalProducts = Product::count();
        $totalOrders = Order::count();
        $totalRevenue = 0;

        if (Schema::hasColumn('orders', 'total_price')) {
            $revenueQuery = Order::query();

            if (Schema::hasColumn('orders', 'status')) {
                $revenueQuery->where('status', 'completed');
            }

            $totalRevenue = $revenueQuery->sum('total_price');
        }
        
        // Get recent orders
        $recentOrdersQuery = Order::with('user');

        if (Schema::hasColumn('orders', 'created_at')) {
            $recentOrdersQuery->latest();
        } else {
            $recentOrdersQuery->orderByDesc('id');
        }

        $recentOrders = $recentOrdersQuery->take(5)->get();

        return view('admin.dashboard', compact(
            'totalUsers',
            'totalProducts',
            'totalOrders',
            'totalRevenue',
            'recentOrders'
        ));
    }
}
