<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    /**
     * Display admin dashboard
     */
    public function index(Request $request)
    {
        $selectedPeriod = in_array($request->query('period'), ['today', 'week', 'month', 'year'], true)
            ? $request->query('period')
            : 'week';

        // Get statistics (period-aware)
        $amountColumn = $this->orderAmountColumn();
        $periodStats = $this->periodStats($amountColumn);
        $selectedPeriodStat = collect($periodStats)->firstWhere('key', $selectedPeriod) ?? $periodStats[1] ?? null;

        // Determine the current period range (used to compute KPI values for the selected period)
        [$currentFrom, $currentTo, $previousFrom, $previousTo] = $this->periodComparisonRange($selectedPeriod);

        // KPI values should reflect the selected period
        $totalRevenue = $this->revenueFor($currentFrom, $currentTo, $amountColumn);
        $totalOrders = $this->orderCountFor($currentFrom, $currentTo);
        $totalUsers = $this->newUsersBetween($currentFrom, $currentTo);
        $totalProducts = $this->productsCountUntil($currentTo);

        $cardTrends = $this->cardTrends($selectedPeriod, $amountColumn);
        $comparisonLabel = $this->comparisonLabel($selectedPeriod);
        $chartDatasets = [
            'revenue' => [
                'title' => 'Phân tích doanh thu',
                'description' => 'Thống kê doanh thu theo kỳ đang chọn',
                'bars' => $this->chartBarsForMetric($selectedPeriod, 'revenue', $amountColumn),
            ],
            'orders' => [
                'title' => 'Phân tích đơn hàng',
                'description' => 'Thống kê số lượng đơn hàng theo kỳ đang chọn',
                'bars' => $this->chartBarsForMetric($selectedPeriod, 'orders', $amountColumn),
            ],
            'users' => [
                'title' => 'Phân tích người dùng mới',
                'description' => 'Thống kê tài khoản khách hàng mới theo kỳ đang chọn',
                'bars' => $this->chartBarsForMetric($selectedPeriod, 'users', $amountColumn),
            ],
        ];
        $chartBars = $chartDatasets['revenue']['bars'];
        $topProducts = $this->topProducts();

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
            'selectedPeriod',
            'selectedPeriodStat',
            'cardTrends',
            'comparisonLabel',
            'chartBars',
            'chartDatasets',
            'topProducts',
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

    private function revenueBetween(Carbon $from, Carbon $to, ?string $amountColumn): float
    {
        if (! $amountColumn || ! Schema::hasColumn('orders', 'created_at')) {
            return 0;
        }

        $query = Order::query()->whereBetween('created_at', [$from, $to]);

        if (Schema::hasColumn('orders', 'status')) {
            $query->where('status', 'completed');
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
            [
                'key' => 'year',
                'label' => 'Năm nay',
                'icon' => 'bi-calendar-range',
                'from' => $now->copy()->startOfYear(),
                'to' => $now->copy()->endOfYear(),
            ],
        ];

        return collect($periods)->map(function (array $period) use ($amountColumn) {
            return [
                ...$period,
                'range' => $period['from']->isSameDay($period['to'])
                    ? $period['from']->format('d/m/Y')
                    : $period['from']->format('d/m/Y') . ' - ' . $period['to']->format('d/m/Y'),
                'orders' => $this->orderCountFor($period['from'], $period['to']),
                'revenue' => $this->revenueFor($period['from'], $period['to'], $amountColumn),
            ];
        })->all();
    }

    private function cardTrends(string $period, ?string $amountColumn): array
    {
        [$currentFrom, $currentTo, $previousFrom, $previousTo] = $this->periodComparisonRange($period);

        $currentRevenue = $this->revenueFor($currentFrom, $currentTo, $amountColumn);
        $previousRevenue = $this->revenueFor($previousFrom, $previousTo, $amountColumn);

        $currentOrders = $this->orderCountFor($currentFrom, $currentTo);
        $previousOrders = $this->orderCountFor($previousFrom, $previousTo);

        $currentUsers = $this->newUsersBetween($currentFrom, $currentTo);
        $previousUsers = $this->newUsersBetween($previousFrom, $previousTo);

        $currentProducts = $this->productsCountUntil($currentTo);
        $previousProducts = $this->productsCountUntil($previousTo);

        return [
            'revenue' => $this->trendData($currentRevenue, $previousRevenue),
            'orders' => $this->trendData($currentOrders, $previousOrders),
            'users' => $this->trendData($currentUsers, $previousUsers),
            'products' => $this->trendData($currentProducts, $previousProducts),
        ];
    }

    private function periodComparisonRange(string $period): array
    {
        $now = Carbon::now();

        if ($period === 'today') {
            $currentFrom = $now->copy()->startOfDay();
            $currentTo = $now->copy()->endOfDay();
            $previousFrom = $currentFrom->copy()->subDay()->startOfDay();
            $previousTo = $currentFrom->copy()->subDay()->endOfDay();

            return [$currentFrom, $currentTo, $previousFrom, $previousTo];
        }

        if ($period === 'month') {
            $currentFrom = $now->copy()->startOfMonth();
            $currentTo = $now->copy()->endOfMonth();
            $previousFrom = $currentFrom->copy()->subMonthNoOverflow()->startOfMonth();
            $previousTo = $previousFrom->copy()->endOfMonth();

            return [$currentFrom, $currentTo, $previousFrom, $previousTo];
        }

        if ($period === 'year') {
            $currentFrom = $now->copy()->startOfYear();
            $currentTo = $now->copy()->endOfYear();
            $previousFrom = $currentFrom->copy()->subYear()->startOfYear();
            $previousTo = $previousFrom->copy()->endOfYear();

            return [$currentFrom, $currentTo, $previousFrom, $previousTo];
        }

        $currentFrom = $now->copy()->startOfWeek(Carbon::MONDAY);
        $currentTo = $now->copy()->endOfWeek(Carbon::SUNDAY);
        $previousFrom = $currentFrom->copy()->subWeek()->startOfWeek(Carbon::MONDAY);
        $previousTo = $previousFrom->copy()->endOfWeek(Carbon::SUNDAY);

        return [$currentFrom, $currentTo, $previousFrom, $previousTo];
    }

    private function trendData(float|int $current, float|int $previous): array
    {
        $currentValue = (float) $current;
        $previousValue = (float) $previous;

        if ($previousValue <= 0.0) {
            if ($currentValue <= 0.0) {
                return [
                    'direction' => 'flat',
                    'icon' => 'bi-dash',
                    'value' => '0,0%',
                ];
            }

            return [
                'direction' => 'up',
                'icon' => 'bi-arrow-up-short',
                'value' => '100,0%',
            ];
        }

        $delta = $currentValue - $previousValue;
        $percent = abs(($delta / $previousValue) * 100);
        $formattedPercent = number_format($percent, 1, ',', '.') . '%';

        if (abs($delta) < 0.00001) {
            return [
                'direction' => 'flat',
                'icon' => 'bi-dash',
                'value' => '0,0%',
            ];
        }

        return [
            'direction' => $delta > 0 ? 'up' : 'down',
            'icon' => $delta > 0 ? 'bi-arrow-up-short' : 'bi-arrow-down-short',
            'value' => $formattedPercent,
        ];
    }

    private function comparisonLabel(string $period): string
    {
        return match ($period) {
            'today' => 'So với hôm qua',
            'month' => 'So với tháng trước',
            'year' => 'So với năm trước',
            default => 'So với tuần trước',
        };
    }

    private function chartBarsForMetric(string $period, string $metric, ?string $amountColumn): array
    {
        if (! Schema::hasColumn('orders', 'created_at')) {
            return [];
        }
        if ($metric === 'revenue' && ! $amountColumn) {
            return [];
        }

        $now = Carbon::now();
        $slots = [];

        if ($period === 'today') {
            $start = $now->copy()->startOfDay();
            for ($i = 0; $i < 12; $i++) {
                $slotStart = $start->copy()->addHours($i * 2);
                $slotEnd = $slotStart->copy()->addHours(2)->subSecond();
                $slots[] = ['label' => $slotStart->format('H:i'), 'from' => $slotStart, 'to' => $slotEnd];
            }
        } elseif ($period === 'week') {
            $start = $now->copy()->startOfWeek(Carbon::MONDAY);
            for ($i = 0; $i < 7; $i++) {
                $slotStart = $start->copy()->addDays($i)->startOfDay();
                $slotEnd = $slotStart->copy()->endOfDay();
                $slots[] = ['label' => 'T' . ($i + 2), 'from' => $slotStart, 'to' => $slotEnd];
            }
        } elseif ($period === 'month') {
            $cursor = $now->copy()->startOfMonth();
            $monthEnd = $now->copy()->endOfMonth();

            while ($cursor->lessThanOrEqualTo($monthEnd)) {
                $slotStart = $cursor->copy()->startOfDay();
                $slotEnd = $cursor->copy()->endOfWeek(Carbon::SUNDAY);
                if ($slotEnd->greaterThan($monthEnd)) {
                    $slotEnd = $monthEnd->copy();
                }

                $slots[] = ['label' => $slotStart->format('d/m'), 'from' => $slotStart, 'to' => $slotEnd];

                $cursor = $slotEnd->copy()->addDay()->startOfDay();
            }
        } else {
            for ($m = 1; $m <= 12; $m++) {
                $slotStart = $now->copy()->startOfYear()->month($m)->startOfMonth();
                $slotEnd = $slotStart->copy()->endOfMonth();

                $slots[] = ['label' => 'T' . $m, 'from' => $slotStart, 'to' => $slotEnd];
            }
        }

        $bars = collect($slots)->map(function (array $slot) use ($amountColumn, $metric) {
            $value = 0;
            $tooltipValue = '0';

            if ($metric === 'revenue') {
                $value = $this->revenueBetween($slot['from'], $slot['to'], $amountColumn);
                $tooltipValue = number_format($value, 0, ',', '.') . 'đ';
            } elseif ($metric === 'orders') {
                $value = $this->orderCountFor($slot['from'], $slot['to']);
                $tooltipValue = number_format($value, 0, ',', '.') . ' đơn';
            } elseif ($metric === 'users') {
                $value = $this->newUsersBetween($slot['from'], $slot['to']);
                $tooltipValue = number_format($value, 0, ',', '.') . ' tài khoản';
            }

            return [
                ...$slot,
                'value' => (float) $value,
                'tooltip_value' => $tooltipValue,
            ];
        })->all();

        $max = max(1, (float) collect($bars)->max('value'));

        return collect($bars)->map(function (array $bar) use ($max) {
            $height = (int) round(($bar['value'] / $max) * 100);

            return [
                ...$bar,
                'height' => max(10, $height),
            ];
        })->all();
    }

    private function newUsersBetween(Carbon $from, Carbon $to): int
    {
        if (! Schema::hasColumn('users', 'created_at')) {
            return 0;
        }

        return User::customers()
            ->whereBetween('created_at', [$from, $to])
            ->count();
    }

    private function productsCountUntil(Carbon $to): int
    {
        if (! Schema::hasColumn('products', 'created_at')) {
            return Product::count();
        }

        return Product::query()
            ->where('created_at', '<=', $to)
            ->count();
    }

    private function topProducts(int $limit = 4): array
    {
        if (! Schema::hasTable('order_items') || ! Schema::hasColumn('order_items', 'product_id')) {
            return [];
        }

        $quantityColumn = Schema::hasColumn('order_items', 'quantity') ? 'quantity' : null;
        if (! $quantityColumn) {
            return [];
        }

        $salesQuery = DB::table('order_items')
            ->select('product_id', DB::raw('SUM(' . $quantityColumn . ') as sold_qty'))
            ->whereNotNull('product_id')
            ->groupBy('product_id')
            ->orderByDesc('sold_qty')
            ->limit($limit);

        if (
            Schema::hasTable('orders')
            && Schema::hasColumn('orders', 'status')
            && Schema::hasColumn('order_items', 'order_id')
        ) {
            $salesQuery
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->where('orders.status', 'completed');
        }

        $sales = $salesQuery->get();
        if ($sales->isEmpty()) {
            return [];
        }

        $products = Product::query()
            ->with('category')
            ->whereIn('id', $sales->pluck('product_id')->all())
            ->get()
            ->keyBy('id');

        return $sales
            ->map(function ($row) use ($products) {
                $product = $products->get((int) $row->product_id);
                if (! $product) {
                    return null;
                }

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku ?? ('#' . $product->id),
                    'image_url' => $product->image_url,
                    'sold_qty' => (int) $row->sold_qty,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
