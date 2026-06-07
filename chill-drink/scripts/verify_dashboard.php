<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use App\Models\Order;
use Carbon\Carbon;

$amountColumn = null;
foreach (['total_price', 'total', 'subtotal'] as $c) {
    if (Schema::hasColumn('orders', $c)) {
        $amountColumn = $c;
        break;
    }
}

$now = Carbon::now();

$totalRevenue = 0.0;
$revenueToday = 0.0;
$revenueMonth = 0.0;
if ($amountColumn) {
    $baseQuery = Order::query();
    if (Schema::hasColumn('orders', 'status')) {
        $baseQuery = $baseQuery->where('status', 'completed');
    }
    $totalRevenue = (float) $baseQuery->sum($amountColumn);

    $todayQuery = Order::query();
    if (Schema::hasColumn('orders', 'status')) {
        $todayQuery = $todayQuery->where('status', 'completed');
    }
    if (Schema::hasColumn('orders', 'created_at')) {
        $revenueToday = (float) $todayQuery->whereBetween('created_at', [$now->copy()->startOfDay(), $now->copy()->endOfDay()])->sum($amountColumn);
        $revenueMonth = (float) $todayQuery->whereBetween('created_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])->sum($amountColumn);
    }
}

$totalOrders = Order::count();

$statusCounts = [
    'pending' => 0,
    'processing' => 0,
    'shipping' => 0,
    'completed' => 0,
    'cancelled' => 0,
];
if (Schema::hasColumn('orders', 'status')) {
    foreach (array_keys($statusCounts) as $status) {
        $statusCounts[$status] = Order::where('status', $status)->count();
    }
}

echo "=== Revenue Dashboard Verification ===\n";
echo "Total Revenue: " . number_format($totalRevenue, 0, ',', '.') . "đ\n";
echo "Revenue Today: " . number_format($revenueToday, 0, ',', '.') . "đ\n";
echo "Revenue This Month: " . number_format($revenueMonth, 0, ',', '.') . "đ\n";
echo "Total Orders: " . number_format($totalOrders, 0, ',', '.') . "\n";
echo "Order Status Counts:\n";
foreach ($statusCounts as $k => $v) {
    echo " - " . ucfirst($k) . ": " . number_format($v, 0, ',', '.') . "\n";
}

echo "======================================\n";
