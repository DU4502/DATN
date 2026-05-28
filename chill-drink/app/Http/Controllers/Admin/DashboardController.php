<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use Carbon\Carbon;
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
        $amountColumn = $this->orderAmountColumn();
        $totalRevenue = $this->revenueFor(null, null, $amountColumn);
        $periodStats = $this->periodStats($amountColumn);

        // Get recent orders
        $recentOrdersQuery = Order::with('user');

        if (Schema::hasColumn('orders', 'created_at')) {
            $recentOrdersQuery->latest();
        } else {
            $recentOrdersQuery->orderByDesc('id');
        }

        $recentOrders = $recentOrdersQuery->take(5)->get();

        // Đảm bảo tất cả các biến đã được định nghĩa đầy đủ ở trên
        return view('admin.dashboard', compact(
            'totalUsers',
            'totalProducts',
            'totalOrders',
            'totalRevenue',
            'periodStats',
            'recentOrders'
        ));
    }

    private function orderAmountColumn(): ?string
    {
        foreach (['total_price', 'total', 'subtotal'] as $column) {
            if (Schema::hasColumn('orders', $column)) {
                return $column;
            }
        }

        return null;
    }

    private function revenueFor(?Carbon $from, ?Carbon $to, ?string $amountColumn): float
    {
        if (! $amountColumn) {
            return 0;
        }

        $query = Order::query();

        if (Schema::hasColumn('orders', 'status')) {
            $query->where('status', 'completed');
        }

        if ($from && $to && Schema::hasColumn('orders', 'created_at')) {
            $query->whereBetween('created_at', [$from, $to]);
        }

        return (float) $query->sum($amountColumn);
    }

    private function orderCountFor(?Carbon $from, ?Carbon $to): int
    {
        $query = Order::query();

        if ($from && $to && Schema::hasColumn('orders', 'created_at')) {
            $query->whereBetween('created_at', [$from, $to]);
        }

        return $query->count();
    }

    private function periodStats(?string $amountColumn): array
    {
        $now = Carbon::now();
        $periods = [
            [
                'key' => 'today',
                'label' => 'Hôm nay',
                'icon' => 'bi-calendar-day',
                'from' => $now->copy()->startOfDay(),
                'to' => $now->copy()->endOfDay(),
            ],
            [
                'key' => 'week',
                'label' => 'Tuần này',
                'icon' => 'bi-calendar-week',
                'from' => $now->copy()->startOfWeek(Carbon::MONDAY),
                'to' => $now->copy()->endOfWeek(Carbon::SUNDAY),
            ],
            [
                'key' => 'month',
                'label' => 'Tháng này',
                'icon' => 'bi-calendar3',
                'from' => $now->copy()->startOfMonth(),
                'to' => $now->copy()->endOfMonth(),
            ],
        ];

        return collect($periods)->map(function (array $period) use ($amountColumn) {
            return [
                ...$period,
                'range' => $period['from']->isSameDay($period['to'])
                    ? $period['from']->format('d/m/Y')
                    : $period['from']->format('d/m/Y').' - '.$period['to']->format('d/m/Y'),
                'orders' => $this->orderCountFor($period['from'], $period['to']),
                'revenue' => $this->revenueFor($period['from'], $period['to'], $amountColumn),
            ];
        })->all();
    }
}
