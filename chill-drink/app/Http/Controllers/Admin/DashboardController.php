<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    /**
     * Display admin dashboard
     */
    public function index()
    {
        // 1. Thống kê số lượng thành viên (Đổi 'role' thành 'role_id')
        $totalUsers = User::where('role_id', 2)->count();

        $totalProducts = Product::count();
        $totalOrders = Order::count();

        // 2. TÍNH DOANH THU AN TOÀN: Tự động kiểm tra cột tránh bị văng lỗi màn hình
        if (Schema::hasColumn('orders', 'total_amount')) {
            $totalRevenue = Order::where('status', 'completed')->sum('total_amount');
        } elseif (Schema::hasColumn('orders', 'total')) {
            $totalRevenue = Order::where('status', 'completed')->sum('total');
        } elseif (Schema::hasColumn('orders', 'total_price')) {
            $totalRevenue = Order::where('status', 'completed')->sum('total_price');
        } else {
            // Nếu chưa có bất kỳ cột tổng tiền nào trong database, gán tạm bằng 0 để không bị lỗi compact()
            $totalRevenue = 0;
        }

        // 3. Lấy 5 đơn hàng mới nhất
        $recentOrders = Order::with('user')
            ->latest()
            ->take(5)
            ->get();

        // Đảm bảo tất cả các biến đã được định nghĩa đầy đủ ở trên
        return view('admin.dashboard', compact(
            'totalUsers',
            'totalProducts',
            'totalOrders',
            'totalRevenue',
            'recentOrders'
        ));
    }
    
}
