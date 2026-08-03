<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\SimpleXlsxWriter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    private bool $dashboardUseBranchScope = false;

    private ?int $dashboardBranchId = null;

    /**
     * Display admin dashboard
     */
    public function index(Request $request)
    {
        $this->resolveDashboardScope($request);
        $periodContext = $this->resolveDashboardPeriodContext($request);
        $data = $this->gatherDashboardData($request, $periodContext);
        $dashboardBranch = $this->dashboardBranch();

        extract($data);
        $comparisonLabel = $this->comparisonLabel($selectedPeriod);
        $chartBars = $chartDatasets['revenue']['bars'] ?? [];
        $topProducts = $topProducts ?? $this->topProducts();

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
            'recentOrders',
            'dashboardBranch',
            'timeComparison',
        ));
    }

    /**
     * Return dashboard data as JSON for AJAX clients.
     */
    public function data(Request $request)
    {
        $this->resolveDashboardScope($request);
        $periodContext = $this->resolveDashboardPeriodContext($request);
        $data = $this->gatherDashboardData($request, $periodContext);

        // Convert Eloquent collections/models to arrays for JSON
        $data['topProducts'] = array_values($data['topProducts']);
        $data['recentOrders'] = $data['recentOrders']->map(function ($o) {
            return [
                'id' => $o->id,
                'user' => ['name' => $o->user->name ?? null],
                'created_at' => optional($o->created_at)->format('d/m/Y H:i'),
                'payment_method' => $o->payment_method ?? null,
                'status' => $o->status ?? null,
                'total' => (float) ($o->total_price ?? $o->total ?? 0),
            ];
        })->all();

        return response()->json($data);
    }

    /**
     * Export the time comparison table as XLSX.
     */
    public function exportTimeComparison(Request $request)
    {
        $this->resolveDashboardScope($request);
        $periodContext = $this->resolveDashboardPeriodContext($request);
        $data = $this->gatherDashboardData($request, $periodContext);

        $timeComparison = $data['timeComparison'] ?? [];
        $branch = $this->dashboardBranch();
        $exportLabel = $branch?->code ?: ($branch?->name ?: 'dashboard');
        $exportLabel = Str::slug((string) $exportLabel, '_');
        $exportLabel = $exportLabel !== '' ? strtoupper($exportLabel) : 'DASHBOARD';
        $exportDate = $periodContext['currentTo'] instanceof Carbon
            ? $periodContext['currentTo']->format('d-m-Y')
            : now()->format('d-m-Y');

        $fileName = sprintf(
            'so-sanh-thoi-gian_%s_%s.xlsx',
            $exportLabel,
            $exportDate
        );

        $sheets = [
            [
                'name' => 'So sánh theo thời gian',
                'rows' => $this->buildTimeComparisonSheetRows($timeComparison),
            ],
            [
                'name' => 'Điều kiện báo cáo',
                'rows' => $this->buildTimeComparisonConditionRows($branch, $periodContext, $timeComparison),
            ],
        ];

        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.Str::random(24).'.xlsx';
        $writer = new SimpleXlsxWriter();
        $writer->write($path, $sheets);

        return response()->download($path, $fileName)->deleteFileAfterSend(true);
    }

    /**
     * Gather dashboard data array for a given period key.
     * Used by both `index` (view) and `data` (API JSON) methods.
     */
    private function gatherDashboardData(
        Request $request,
        array $periodContext
    ): array
    {
        $selectedPeriod = (string) ($periodContext['period'] ?? 'week');
        $currentFrom = $periodContext['currentFrom'] instanceof Carbon ? $periodContext['currentFrom']->copy() : Carbon::now()->startOfWeek(Carbon::MONDAY);
        $currentTo = $periodContext['currentTo'] instanceof Carbon ? $periodContext['currentTo']->copy() : Carbon::now()->endOfWeek(Carbon::SUNDAY);
        $selectedPeriodLabel = (string) ($periodContext['label'] ?? $this->comparisonLabel($selectedPeriod));
        $periodDays = max(1, (int) ($periodContext['days'] ?? $currentFrom->diffInDays($currentTo) + 1));
        [$previousFrom, $previousTo] = $this->comparisonPeriodRange($selectedPeriod, $currentFrom, $currentTo, $periodDays);

        $amountColumn = $this->orderAmountColumn();
        $periodStats = $this->periodStats($amountColumn);
        $selectedPeriodStat = [
            'key' => $selectedPeriod,
            'label' => $selectedPeriodLabel,
            'start' => $currentFrom->format('Y-m-d'),
            'end' => $currentTo->format('Y-m-d'),
            'orders' => 0,
            'revenue' => 0,
        ];

        $totalRevenue = $this->revenueFor($currentFrom, $currentTo, $amountColumn);
        $totalOrders = $this->orderCountFor($currentFrom, $currentTo);
        $totalUsers = $this->newUsersBetween($currentFrom, $currentTo);
        $totalProducts = $this->productsCountUntil($currentTo);
        $selectedPeriodStat['orders'] = $totalOrders;
        $selectedPeriodStat['revenue'] = $totalRevenue;

        $cardTrends = $this->cardTrends($currentFrom, $currentTo, $previousFrom, $previousTo, $amountColumn);
        $comparisonLabel = $this->comparisonLabel($selectedPeriod);
        $timeComparison = $this->dashboardTimeComparison($request, $periodContext);
        $chartDatasets = [
            'revenue' => [
                'title' => 'Phân tích doanh thu',
                'description' => 'Thống kê doanh thu theo kỳ đang chọn',
                'bars' => $this->chartBarsForMetric($selectedPeriod, $currentFrom, $currentTo, 'revenue', $amountColumn),
            ],
            'orders' => [
                'title' => 'Phân tích đơn hàng',
                'description' => 'Thống kê số lượng đơn hàng theo kỳ đang chọn',
                'bars' => $this->chartBarsForMetric($selectedPeriod, $currentFrom, $currentTo, 'orders', $amountColumn),
            ],
            'users' => [
                'title' => 'Phân tích người dùng mới',
                'description' => 'Thống kê tài khoản khách hàng mới theo kỳ đang chọn',
                'bars' => $this->chartBarsForMetric($selectedPeriod, $currentFrom, $currentTo, 'users', $amountColumn),
            ],
        ];
        $chartBars = $chartDatasets['revenue']['bars'];
        $topProducts = $this->topProducts($currentFrom, $currentTo);

        $recentOrdersQuery = Order::with('user');
        // Apply branch scope
        $recentOrdersQuery = $this->applyBranchScope($recentOrdersQuery);
        
        if (Schema::hasColumn('orders', 'created_at')) {
            $recentOrdersQuery->latest();
        } else {
            $recentOrdersQuery->orderByDesc('id');
        }
        $recentOrders = $recentOrdersQuery->take(5)->get();

        return compact(
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
            'recentOrders',
            'timeComparison'
        );
    }

    /**
     * Resolve comparison range specifically for the product section.
     *
     * @return array{0:?Carbon,1:?Carbon,2:string,3:string}
     */
    private function resolveProductComparison(Request $request, string $selectedPeriod): array
    {
        $compareType = in_array($request->query('compare_type'), ['none', 'previous', 'previous_year', 'custom'], true)
            ? (string) $request->query('compare_type')
            : 'previous';

        if ($compareType === 'none') {
            return [null, null, 'Không đối chiếu', $compareType];
        }

        [$currentFrom, $currentTo] = $this->periodComparisonRange($selectedPeriod);

        if (! $currentFrom || ! $currentTo) {
            return [null, null, 'Không đối chiếu', 'none'];
        }

        if ($compareType === 'previous_year') {
            return [
                $currentFrom->copy()->subYearNoOverflow(),
                $currentTo->copy()->subYearNoOverflow(),
                'Cùng kỳ năm trước',
                $compareType,
            ];
        }

        if ($compareType === 'custom') {
            return $this->customProductComparisonRange($request, $selectedPeriod, $currentFrom, $currentTo);
        }

        return match ($selectedPeriod) {
            'day' => [
                $currentFrom->copy()->subDay()->startOfDay(),
                $currentTo->copy()->subDay()->endOfDay(),
                'Kỳ liền trước',
                $compareType,
            ],
            'month' => [
                $currentFrom->copy()->subMonthNoOverflow()->startOfMonth(),
                $currentFrom->copy()->subMonthNoOverflow()->endOfMonth(),
                'Kỳ liền trước',
                $compareType,
            ],
            'year' => [
                $currentFrom->copy()->subYearNoOverflow()->startOfYear(),
                $currentFrom->copy()->subYearNoOverflow()->endOfYear(),
                'Kỳ liền trước',
                $compareType,
            ],
            default => [
                $currentFrom->copy()->subWeek()->startOfWeek(Carbon::MONDAY),
                $currentFrom->copy()->subWeek()->endOfWeek(Carbon::SUNDAY),
                'Kỳ liền trước',
                $compareType,
            ],
        };
    }

    /**
     * @return array{0:?Carbon,1:?Carbon,2:string,3:string}
     */
    private function customProductComparisonRange(Request $request, string $selectedPeriod, Carbon $currentFrom, Carbon $currentTo): array
    {
        return match ($selectedPeriod) {
            'day' => array_merge(
                $this->compareDateRange(
                    $this->parseCompareDate($request->query('compare_date'), $currentFrom->copy()->subDay()),
                    'Ngày'
                ),
                ['custom']
            ),
            'month' => array_merge(
                $this->compareMonthRange(
                    $this->parseCompareMonth($request->query('compare_month'), $currentFrom->copy()->subMonthNoOverflow()),
                    'Tháng'
                ),
                ['custom']
            ),
            'year' => array_merge(
                $this->compareYearRange(
                    $this->parseCompareYear($request->query('compare_year'), $currentFrom->copy()->subYearNoOverflow()),
                    'Năm'
                ),
                ['custom']
            ),
            default => array_merge(
                $this->compareDateSpan(
                    $this->parseCompareDate($request->query('compare_start_date'), $currentFrom->copy()->subWeek()->startOfWeek(Carbon::MONDAY)),
                    $this->parseCompareDate($request->query('compare_end_date'), $currentFrom->copy()->subWeek()->endOfWeek(Carbon::SUNDAY)),
                    'Tùy chọn'
                ),
                ['custom']
            ),
        };
    }

    /**
     * @return array{0:?Carbon,1:?Carbon,2:string}
     */
    private function compareDateRange(Carbon $date, string $labelPrefix): array
    {
        return [
            $date->copy()->startOfDay(),
            $date->copy()->endOfDay(),
            $labelPrefix.' '.$date->format('d/m/Y'),
        ];
    }

    /**
     * @return array{0:?Carbon,1:?Carbon,2:string}
     */
    private function compareMonthRange(Carbon $month, string $labelPrefix): array
    {
        $month = $month->copy()->startOfMonth();

        return [
            $month->copy()->startOfMonth(),
            $month->copy()->endOfMonth(),
            $labelPrefix.' '.$month->format('m/Y'),
        ];
    }

    /**
     * @return array{0:?Carbon,1:?Carbon,2:string}
     */
    private function compareYearRange(Carbon $year, string $labelPrefix): array
    {
        $year = $year->copy()->startOfYear();

        return [
            $year->copy()->startOfYear(),
            $year->copy()->endOfYear(),
            $labelPrefix.' '.$year->format('Y'),
        ];
    }

    /**
     * @return array{0:?Carbon,1:?Carbon,2:string}
     */
    private function compareDateSpan(Carbon $start, Carbon $end, string $label): array
    {
        if ($start->greaterThan($end)) {
            [$start, $end] = [$end, $start];
        }

        return [
            $start->copy()->startOfDay(),
            $end->copy()->endOfDay(),
            'Từ '.$start->format('d/m/Y').' đến '.$end->format('d/m/Y'),
        ];
    }

    private function parseCompareDate(mixed $value, Carbon $fallback): Carbon
    {
        if (! is_string($value) || $value === '') {
            return $fallback;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value);
        } catch (\Throwable) {
            return $fallback;
        }
    }

    private function parseCompareMonth(mixed $value, Carbon $fallback): Carbon
    {
        if (! is_string($value) || $value === '') {
            return $fallback;
        }

        try {
            return Carbon::createFromFormat('Y-m', $value);
        } catch (\Throwable) {
            return $fallback;
        }
    }

    private function parseCompareYear(mixed $value, Carbon $fallback): Carbon
    {
        if (! is_numeric($value)) {
            return $fallback;
        }

        $year = (int) $value;

        try {
            return Carbon::create($year, 1, 1);
        } catch (\Throwable) {
            return $fallback;
        }
    }

    /**
     * Apply branch scope to a query based on current user's role and branch
     * Super Admin sees all data, Admin sees only their branch's data
     */
    private function applyBranchScope($query)
    {
        if (! $this->dashboardUseBranchScope || ! $this->dashboardBranchId) {
            return $query;
        }

        return $query->where('branch_id', $this->dashboardBranchId);
    }

    private function resolveDashboardScope(Request $request): void
    {
        $user = $request->user();

        if (! $user) {
            $this->dashboardUseBranchScope = false;
            $this->dashboardBranchId = null;

            return;
        }

        if ($user->isSuperAdmin()) {
            $branchId = $request->query('branch_id');

            if (! is_numeric($branchId) && $user->isViewingAdminWorkspace()) {
                $branchId = $user->adminWorkspaceBranchId();
            }

            if (is_numeric($branchId) && Branch::query()->whereKey((int) $branchId)->exists()) {
                $this->dashboardUseBranchScope = true;
                $this->dashboardBranchId = (int) $branchId;

                return;
            }

            $this->dashboardUseBranchScope = false;
            $this->dashboardBranchId = null;

            return;
        }

        $this->dashboardUseBranchScope = true;
        $this->dashboardBranchId = $user->branch_id ? (int) $user->branch_id : null;
    }

    private function dashboardBranch(): ?Branch
    {
        if (! $this->dashboardUseBranchScope || ! $this->dashboardBranchId) {
            return null;
        }

        return Branch::query()->find($this->dashboardBranchId);
    }

    private function normalizeDashboardPeriod(?string $period): string
    {
        return match ($period) {
            'day', 'today' => 'day',
            'week', 'month', 'year', 'custom' => (string) $period,
            default => 'week',
        };
    }

    private function parseDashboardDate(mixed $value, Carbon $fallback): Carbon
    {
        if (! is_string($value) || $value === '') {
            return $fallback->copy();
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value);
        } catch (\Throwable) {
            return $fallback->copy();
        }
    }

    private function parseDashboardMonth(mixed $value, Carbon $fallback): Carbon
    {
        if (! is_string($value) || $value === '') {
            return $fallback->copy()->startOfMonth();
        }

        try {
            return Carbon::createFromFormat('Y-m', $value)->startOfMonth();
        } catch (\Throwable) {
            return $fallback->copy()->startOfMonth();
        }
    }

    private function parseDashboardYear(mixed $value, Carbon $fallback): Carbon
    {
        if (! is_numeric($value)) {
            return $fallback->copy()->startOfYear();
        }

        try {
            return Carbon::create((int) $value, 1, 1)->startOfYear();
        } catch (\Throwable) {
            return $fallback->copy()->startOfYear();
        }
    }

    private function parseDashboardWeek(mixed $value, Carbon $fallback): Carbon
    {
        if (! is_string($value) || ! preg_match('/^(?<year>\d{4})-W(?<week>\d{2})$/', $value, $matches)) {
            return $fallback->copy()->startOfWeek(Carbon::MONDAY);
        }

        try {
            return Carbon::now()->setISODate((int) $matches['year'], (int) $matches['week'])->startOfWeek(Carbon::MONDAY);
        } catch (\Throwable) {
            return $fallback->copy()->startOfWeek(Carbon::MONDAY);
        }
    }

    private function resolveDashboardPeriodContext(Request $request): array
    {
        $period = $this->normalizeDashboardPeriod($request->query('period'));
        $now = Carbon::now();

        $currentFrom = match ($period) {
            'day' => $this->parseDashboardDate($request->query('date'), $now->copy())->startOfDay(),
            'week' => $this->parseDashboardWeek($request->query('week'), $now->copy()->startOfWeek(Carbon::MONDAY))->startOfDay(),
            'month' => $this->parseDashboardMonth($request->query('month'), $now->copy())->startOfMonth(),
            'year' => $this->parseDashboardYear($request->query('year'), $now->copy())->startOfYear(),
            'custom' => $this->parseDashboardDate($request->query('start_date'), $now->copy()->startOfMonth())->startOfDay(),
        };

        $currentTo = match ($period) {
            'day' => $this->parseDashboardDate($request->query('date'), $now->copy())->endOfDay(),
            'week' => $currentFrom->copy()->endOfWeek(Carbon::SUNDAY),
            'month' => $currentFrom->copy()->endOfMonth(),
            'year' => $currentFrom->copy()->endOfYear(),
            'custom' => $this->parseDashboardDate($request->query('end_date'), $now->copy())->endOfDay(),
        };

        if ($period === 'custom' && $currentFrom->greaterThan($currentTo)) {
            [$currentFrom, $currentTo] = [$currentTo->copy()->startOfDay(), $currentFrom->copy()->endOfDay()];
        }

        $currentTo = $this->capToNow($currentTo->copy()->endOfDay());
        if ($currentFrom->greaterThan($currentTo)) {
            $currentFrom = $currentTo->copy()->startOfDay();
        }

        $label = match ($period) {
            'day' => 'Hôm nay',
            'week' => 'Tuần này',
            'month' => 'Tháng này',
            'year' => 'Năm nay',
            'custom' => $currentFrom->isSameDay($currentTo)
                ? $currentFrom->format('d/m/Y')
                : $currentFrom->format('d/m/Y') . ' - ' . $currentTo->format('d/m/Y'),
        };

        return [
            'period' => $period,
            'currentFrom' => $currentFrom,
            'currentTo' => $currentTo,
            'days' => max(1, $currentFrom->diffInDays($currentTo) + 1),
            'label' => $label,
        ];
    }

    /**
     * @return array{0:Carbon,1:Carbon}
     */
    private function comparisonPeriodRange(string $period, Carbon $currentFrom, Carbon $currentTo, int $periodDays): array
    {
        return match ($period) {
            'day' => [
                $currentFrom->copy()->subDay()->startOfDay(),
                $currentFrom->copy()->subDay()->endOfDay(),
            ],
            'month' => [
                $currentFrom->copy()->subMonthNoOverflow()->startOfMonth(),
                $currentFrom->copy()->subMonthNoOverflow()->endOfMonth(),
            ],
            'year' => [
                $currentFrom->copy()->subYearNoOverflow()->startOfYear(),
                $currentFrom->copy()->subYearNoOverflow()->endOfYear(),
            ],
            default => [
                $currentFrom->copy()->subDays($periodDays)->startOfDay(),
                $currentFrom->copy()->subDay()->endOfDay(),
            ],
        };
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

        if ($from && $to) {
            $to = $this->capToNow($to);
            if ($from->greaterThan($to)) {
                return 0;
            }
        }

        $query = Order::query();
        
        // Apply branch scope
        $query = $this->applyBranchScope($query);

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

        $to = $this->capToNow($to);
        if ($from->greaterThan($to)) {
            return 0;
        }

        $query = Order::query()->whereBetween('created_at', [$from, $to]);
        
        // Apply branch scope
        $query = $this->applyBranchScope($query);

        if (Schema::hasColumn('orders', 'status')) {
            $query->where('status', 'completed');
        }

        return (float) $query->sum($amountColumn);
    }

    private function orderCountFor(?Carbon $from, ?Carbon $to): int
    {
        if ($from && $to) {
            $to = $this->capToNow($to);
            if ($from->greaterThan($to)) {
                return 0;
            }
        }

        $query = Order::query();
        
        // Apply branch scope
        $query = $this->applyBranchScope($query);

        if ($from && $to && Schema::hasColumn('orders', 'created_at')) {
            $query->whereBetween('created_at', [$from, $to]);
        }

        return $query->count();
    }

    private function capToNow(Carbon $date): Carbon
    {
        $now = Carbon::now();

        return $date->greaterThan($now) ? $now : $date;
    }

    private function periodStats(?string $amountColumn): array
    {
        $now = Carbon::now();
        $periods = [
            [
                'key' => 'day',
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

    private function cardTrends(Carbon $currentFrom, Carbon $currentTo, Carbon $previousFrom, Carbon $previousTo, ?string $amountColumn): array
    {
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
        $period = $this->normalizeDashboardPeriod($period);
        $now = Carbon::now();

        if ($period === 'day') {
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
        $period = $this->normalizeDashboardPeriod($period);

        return match ($period) {
            'day' => 'So với hôm qua',
            'month' => 'So với tháng trước',
            'year' => 'So với năm trước',
            'custom' => 'So với kỳ trước',
            default => 'So với tuần trước',
        };
    }

    private function dashboardTimeComparison(Request $request, array $periodContext): array
    {
        $selectedPeriod = (string) ($periodContext['period'] ?? 'week');
        $currentFrom = $periodContext['currentFrom'] instanceof Carbon ? $periodContext['currentFrom']->copy() : Carbon::now()->startOfWeek(Carbon::MONDAY);
        $currentTo = $periodContext['currentTo'] instanceof Carbon ? $periodContext['currentTo']->copy() : Carbon::now()->endOfWeek(Carbon::SUNDAY);
        $group = $this->dashboardMatrixGroup($selectedPeriod, $currentFrom, $currentTo);
        $periodOptions = $this->dashboardMatrixPeriodOptions($group);
        $periodCount = $this->dashboardMatrixPeriodCount($request->query('admin_matrix_periods'), $group);
        $periods = $this->buildDashboardMatrixPeriods($group, $periodCount, $currentTo);
        $timeComparisonQuery = $this->dashboardTimeComparisonQuery($periods);
        $bucketMap = $this->dashboardTimeComparisonBuckets($periods, $timeComparisonQuery);
        $rows = $this->dashboardTimeComparisonRows($periods, $bucketMap);
        return [
            'period_type' => $selectedPeriod,
            'group' => $group,
            'period_count' => $periodCount,
            'period_options' => collect($periodOptions)->map(fn (int $count) => [
                'value' => $count,
                'label' => $count.' kỳ',
            ])->values()->all(),
            'periods' => $periods,
            'rows' => $rows,
            'scope_label' => $this->dashboardScopeLabel(),
        ];
    }

    private function dashboardScopeLabel(): string
    {
        $branch = $this->dashboardBranch();

        return $branch ? 'chi nhánh '.$branch->name : 'cửa hàng';
    }

    private function dashboardMatrixGroup(string $selectedPeriod, Carbon $currentFrom, Carbon $currentTo): string
    {
        $selectedPeriod = $this->normalizeDashboardPeriod($selectedPeriod);

        if ($selectedPeriod !== 'custom') {
            return $selectedPeriod;
        }

        $days = max(1, $currentFrom->diffInDays($currentTo) + 1);

        return match (true) {
            $days <= 31 => 'day',
            $days <= 180 => 'week',
            default => 'month',
        };
    }

    /**
     * @return array<int, int>
     */
    private function dashboardMatrixPeriodOptions(string $group): array
    {
        return [4, 8, 12];
    }

    private function dashboardMatrixPeriodCount(mixed $value, string $group): int
    {
        $options = $this->dashboardMatrixPeriodOptions($group);
        $default = $options[1] ?? $options[0] ?? 8;

        if (! is_numeric($value)) {
            return $default;
        }

        $count = (int) $value;

        return in_array($count, $options, true) ? $count : $default;
    }

    /**
     * @return array<int, array{key:string,label:string,start:string,end:string,start_at:string,end_at:string,is_partial:bool}>
     */
    private function buildDashboardMatrixPeriods(string $group, int $periodCount, Carbon $currentTo): array
    {
        $periods = [];
        $periodCount = max(1, $periodCount);

        for ($index = 0; $index < $periodCount; $index++) {
            $anchor = match ($group) {
                'day' => $currentTo->copy()->subDays($index),
                'week' => $currentTo->copy()->subWeeks($index),
                'month' => $currentTo->copy()->subMonthsNoOverflow($index),
                'year' => $currentTo->copy()->subYearsNoOverflow($index),
                default => $currentTo->copy()->subDays($index),
            };

            [$start, $end, $label, $key] = match ($group) {
                'week' => [
                    $anchor->copy()->startOfWeek(Carbon::MONDAY),
                    $index === 0 ? $currentTo->copy() : $anchor->copy()->endOfWeek(Carbon::SUNDAY),
                    'Tuần '.$anchor->format('W').'/'.$anchor->format('o'),
                    $anchor->format('o-\WW'),
                ],
                'month' => [
                    $anchor->copy()->startOfMonth(),
                    $index === 0 ? $currentTo->copy() : $anchor->copy()->endOfMonth(),
                    $anchor->format('m/Y'),
                    $anchor->format('Y-m'),
                ],
                'year' => [
                    $anchor->copy()->startOfYear(),
                    $index === 0 ? $currentTo->copy() : $anchor->copy()->endOfYear(),
                    $anchor->format('Y'),
                    $anchor->format('Y'),
                ],
                default => [
                    $anchor->copy()->startOfDay(),
                    $index === 0 ? $currentTo->copy() : $anchor->copy()->endOfDay(),
                    $anchor->format('d/m/Y'),
                    $anchor->format('Y-m-d'),
                ],
            };

            $periods[] = [
                'key' => $key,
                'label' => $label,
                'start' => $start->copy()->format('Y-m-d'),
                'end' => $end->copy()->format('Y-m-d'),
                'start_at' => $start->copy()->format('Y-m-d H:i:s'),
                'end_at' => $end->copy()->format('Y-m-d H:i:s'),
                'is_partial' => $index === 0 && $end->lessThan($anchor->copy()->endOfDay()),
            ];
        }

        return $periods;
    }

    /**
     * @param array<int, array{key:string,label:string,start:string,end:string,start_at:string,end_at:string,is_partial:bool}> $periods
     */
    private function dashboardTimeComparisonQuery(array $periods)
    {
        if ($periods === [] || ! Schema::hasColumn('orders', 'created_at')) {
            return [
                'orders' => collect(),
                'amount_column' => $this->dashboardSalesAmountColumn(),
                'start' => null,
                'end' => null,
            ];
        }

        $start = Carbon::parse((string) ($periods[array_key_last($periods)]['start_at'] ?? now()->format('Y-m-d 00:00:00')));
        $end = Carbon::parse((string) ($periods[0]['end_at'] ?? now()->format('Y-m-d H:i:s')));
        $amountColumn = $this->dashboardSalesAmountColumn();

        $query = $this->dashboardSalesBaseQuery();
        $query->whereBetween('created_at', [$start, $end]);

        $selectColumns = ['id', 'created_at'];
        foreach (['total', 'total_price', 'subtotal'] as $column) {
            if (Schema::hasColumn('orders', $column)) {
                $selectColumns[] = $column;
                break;
            }
        }

        $orders = $query->select($selectColumns)->orderBy('created_at')->get();

        return [
            'orders' => $orders,
            'amount_column' => $amountColumn,
            'start' => $start,
            'end' => $end,
        ];
    }

    /**
     * @param array<int, array{key:string,label:string,start:string,end:string,start_at:string,end_at:string,is_partial:bool}> $periods
     * @param array{orders:\Illuminate\Support\Collection,amount_column:?string,start:Carbon,end:Carbon}|\Illuminate\Support\Collection $queryData
     * @return array<string, array{revenue:float,valid_order_count:int}>
     */
    private function dashboardTimeComparisonBuckets(array $periods, array $queryData): array
    {
        $bucketMap = [];

        foreach ($periods as $period) {
            $bucketMap[$period['key']] = [
                'revenue' => 0.0,
                'valid_order_count' => 0,
            ];
        }

        $orders = $queryData['orders'] ?? collect();
        $amountColumn = $queryData['amount_column'] ?? $this->dashboardSalesAmountColumn();

        foreach ($orders as $order) {
            $createdAt = $order->created_at instanceof Carbon ? $order->created_at->copy() : Carbon::parse((string) $order->created_at);
            $periodKey = $this->dashboardMatrixPeriodKey($createdAt, $periods);

            if (! isset($bucketMap[$periodKey])) {
                continue;
            }

            $bucketMap[$periodKey]['valid_order_count'] += 1;
            $bucketMap[$periodKey]['revenue'] += (float) ($amountColumn && isset($order->{$amountColumn}) ? $order->{$amountColumn} : ($order->total ?? $order->total_price ?? $order->subtotal ?? 0));
        }

        return $bucketMap;
    }

    /**
     * @param array<int, array{key:string,label:string,start:string,end:string,start_at:string,end_at:string,is_partial:bool}> $periods
     * @param array<string, array{revenue:float,valid_order_count:int}> $bucketMap
     */
    private function dashboardTimeComparisonRows(array $periods, array $bucketMap): array
    {
        $rows = [];

        foreach ($periods as $index => $period) {
            $summary = $this->dashboardTimeComparisonPeriodSummary($period, $bucketMap);
            $comparison = $this->dashboardTimeComparisonLatestChange($periods, $bucketMap, $index);
            $revenue = (float) ($summary['revenue'] ?? 0);
            $orderCount = (int) ($summary['valid_order_count'] ?? 0);

            $rows[] = [
                ...$period,
                'revenue' => $revenue,
                'valid_order_count' => $orderCount,
                'average_order_value' => $orderCount > 0 ? $revenue / $orderCount : 0.0,
                'latest_change' => $comparison,
            ];
        }

        return $rows;
    }

    /**
     * @param array<string, array{revenue:float,valid_order_count:int}> $bucketMap
     * @return array{revenue:float,valid_order_count:int}
     */
    private function dashboardTimeComparisonPeriodSummary(array $period, array $bucketMap): array
    {
        return [
            'revenue' => (float) ($bucketMap[$period['key']]['revenue'] ?? 0),
            'valid_order_count' => (int) ($bucketMap[$period['key']]['valid_order_count'] ?? 0),
        ];
    }

    /**
     * @param array<int, array{key:string,label:string,start:string,end:string,start_at:string,end_at:string,is_partial:bool}> $periods
     * @param array<string, array{revenue:float,valid_order_count:int}> $bucketMap
     */
    private function dashboardTimeComparisonLatestChange(array $periods, array $bucketMap, int $index): array
    {
        $currentPeriod = $periods[$index] ?? null;
        $previousPeriod = $periods[$index + 1] ?? null;

        if (! $currentPeriod || ! $previousPeriod) {
            return [
                'type' => 'insufficient',
                'label' => 'Chưa đủ dữ liệu',
                'revenue' => [
                    'type' => 'insufficient',
                    'label' => 'Chưa đủ dữ liệu',
                ],
                'orders' => [
                    'type' => 'insufficient',
                    'label' => 'Chưa đủ dữ liệu',
                ],
            ];
        }

        [$currentRevenue, $previousRevenue, $currentOrders, $previousOrders] = $this->dashboardTimeComparisonComparableValues(
            $currentPeriod,
            $previousPeriod,
            $bucketMap
        );

        $revenueChange = $this->dashboardTimeComparisonChangeLabel($currentRevenue, $previousRevenue);
        $orderChange = $this->dashboardTimeComparisonChangeLabel($currentOrders, $previousOrders);

        return [
            'type' => $revenueChange['type'],
            'label' => $revenueChange['label'],
            'revenue' => $revenueChange,
            'orders' => $orderChange,
        ];
    }

    /**
     * @param array{key:string,label:string,start:string,end:string,start_at:string,end_at:string,is_partial:bool} $currentPeriod
     * @param array{key:string,label:string,start:string,end:string,start_at:string,end_at:string,is_partial:bool} $previousPeriod
     * @param array<string, array{revenue:float,valid_order_count:int}> $bucketMap
     * @return array{0:float,1:float,2:float,3:float}
     */
    private function dashboardTimeComparisonComparableValues(array $currentPeriod, array $previousPeriod, array $bucketMap): array
    {
        $currentKey = (string) ($currentPeriod['key'] ?? '');
        $previousKey = (string) ($previousPeriod['key'] ?? '');
        $currentRevenue = (float) ($bucketMap[$currentKey]['revenue'] ?? 0);
        $currentOrders = (float) ($bucketMap[$currentKey]['valid_order_count'] ?? 0);

        if (! ($currentPeriod['is_partial'] ?? false)) {
            return [
                $currentRevenue,
                (float) ($bucketMap[$previousKey]['revenue'] ?? 0),
                $currentOrders,
                (float) ($bucketMap[$previousKey]['valid_order_count'] ?? 0),
            ];
        }

        $currentFrom = Carbon::parse((string) ($currentPeriod['start_at'] ?? now()->format('Y-m-d H:i:s')));
        $currentTo = Carbon::parse((string) ($currentPeriod['end_at'] ?? now()->format('Y-m-d H:i:s')));
        $previousFrom = Carbon::parse((string) ($previousPeriod['start_at'] ?? now()->format('Y-m-d H:i:s')));
        $previousEnd = Carbon::parse((string) ($previousPeriod['end_at'] ?? now()->format('Y-m-d H:i:s')));
        $elapsedSeconds = max(1, $currentFrom->diffInSeconds($currentTo));
        $previousComparableEnd = $previousFrom->copy()->addSeconds($elapsedSeconds);

        if ($previousComparableEnd->greaterThan($previousEnd)) {
            $previousComparableEnd = $previousEnd;
        }

        $previousWindow = $this->dashboardTimeComparisonMetricSummaryBetween($previousFrom, $previousComparableEnd);

        return [
            $currentRevenue,
            (float) ($previousWindow['revenue'] ?? 0),
            $currentOrders,
            (float) ($previousWindow['valid_order_count'] ?? 0),
        ];
    }

    private function dashboardTimeComparisonChangeLabel(float|int $current, float|int $previous): array
    {
        $currentValue = (float) $current;
        $previousValue = (float) $previous;

        if ($currentValue <= 0.0 && $previousValue <= 0.0) {
            return [
                'type' => 'flat',
                'label' => 'Không đổi',
            ];
        }

        if ($currentValue > 0.0 && $previousValue <= 0.0) {
            return [
                'type' => 'new',
                'label' => 'Phát sinh mới',
            ];
        }

        if (abs($currentValue - $previousValue) < 0.00001) {
            return [
                'type' => 'flat',
                'label' => 'Không đổi',
            ];
        }

        $percent = $previousValue > 0 ? round((($currentValue - $previousValue) / $previousValue) * 100, 1) : 0.0;

        return [
            'type' => $currentValue > $previousValue ? 'up' : 'down',
            'label' => ($currentValue > $previousValue ? '↑ ' : '↓ ') . number_format(abs($percent), 1, ',', '.') . '%',
        ];
    }

    /**
     * @return array{revenue:float,valid_order_count:int}
     */
    private function dashboardTimeComparisonMetricSummaryBetween(Carbon $from, Carbon $to): array
    {
        if (! Schema::hasColumn('orders', 'created_at')) {
            return [
                'revenue' => 0.0,
                'valid_order_count' => 0,
            ];
        }

        $from = $from->copy();
        $to = $this->capToNow($to->copy());

        if ($from->greaterThan($to)) {
            return [
                'revenue' => 0.0,
                'valid_order_count' => 0,
            ];
        }

        $amountColumn = $this->dashboardSalesAmountColumn();
        $revenueQuery = $this->dashboardSalesBaseQuery();
        $revenueQuery->whereBetween('created_at', [$from, $to]);
        $countQuery = $this->dashboardSalesBaseQuery();
        $countQuery->whereBetween('created_at', [$from, $to]);

        $revenue = $amountColumn ? (float) $revenueQuery->sum($amountColumn) : 0.0;

        return [
            'revenue' => $revenue,
            'valid_order_count' => (int) $countQuery->count(),
        ];
    }

    /**
     * @param array<int, array{key:string,label:string,start:string,end:string,start_at:string,end_at:string,is_partial:bool}> $periods
     */
    private function dashboardMatrixPeriodKey(Carbon $date, array $periods): string
    {
        foreach ($periods as $period) {
            $start = Carbon::parse($period['start_at']);
            $end = Carbon::parse($period['end_at']);
            if ($date->betweenIncluded($start, $end)) {
                return $period['key'];
            }
        }

        return $periods[0]['key'] ?? '';
    }

    private function dashboardSalesAmountColumn(): ?string
    {
        foreach (['total', 'total_price', 'subtotal'] as $column) {
            if (Schema::hasColumn('orders', $column)) {
                return $column;
            }
        }

        return null;
    }

    private function dashboardSalesBaseQuery()
    {
        $query = Order::query();
        $query = $this->applyBranchScope($query);

        if (Schema::hasColumn('orders', 'status')) {
            $query->where('status', '!=', 'cancelled');
        }

        if (Schema::hasColumn('orders', 'payment_status') && Schema::hasColumn('orders', 'status')) {
            $query->where(function ($builder) {
                $builder->where('payment_status', 'paid')
                    ->orWhere('status', 'completed');
            });
        } elseif (Schema::hasColumn('orders', 'payment_status')) {
            $query->where('payment_status', 'paid');
        } elseif (Schema::hasColumn('orders', 'status')) {
            $query->where('status', 'completed');
        }

        return $query;
    }

    /**
     * @param array{
     *     periods?: array<int, array{key:string,label:string,start:string,end:string,start_at:string,end_at:string,is_partial:bool}>,
     *     rows?: array<int, array{
     *         key:string,
     *         label:string,
     *         start:string,
     *         end:string,
     *         start_at:string,
     *         end_at:string,
     *         is_partial:bool,
     *         revenue:float,
     *         valid_order_count:int,
     *         average_order_value:float,
     *         latest_change:array{
     *             type:string,
     *             label:string,
     *             revenue:array{type:string,label:string},
     *             orders:array{type:string,label:string}
     *         }
     *     }>,
     *     scope_label?: string,
     *     group?: string,
     *     period_count?: int,
     *     period_type?: string
     * } $timeComparison
     * @return array<int, array<int, mixed>>
     */
    private function buildTimeComparisonSheetRows(array $timeComparison): array
    {
        $rows = [];

        $rows[] = ['So sánh theo thời gian'];
        $rows[] = [];

        $header = ['Kỳ', 'Doanh thu', 'Số đơn', 'Trung bình/đơn', 'Biến động doanh thu', 'Biến động số đơn'];
        $rows[] = $header;

        foreach (($timeComparison['rows'] ?? []) as $row) {
            $change = $row['latest_change'] ?? [];
            $line = [
                (string) ($row['label'] ?? ''),
                (int) round((float) ($row['revenue'] ?? 0)),
                (int) ($row['valid_order_count'] ?? 0),
                (int) round((float) ($row['average_order_value'] ?? 0)),
                (string) ($change['revenue']['label'] ?? ($change['label'] ?? 'Chưa đủ dữ liệu')),
                (string) ($change['orders']['label'] ?? 'Chưa đủ dữ liệu'),
            ];
            $rows[] = $line;
        }

        return $rows;
    }

    /**
     * @param array{
     *     scope_label?: string,
     *     group?: string,
     *     period_count?: int,
     *     periods?: array<int, array{key:string,label:string,start:string,end:string,start_at:string,end_at:string,is_partial:bool}>
     * } $timeComparison
     * @return array<int, array<int, mixed>>
     */
    private function buildTimeComparisonConditionRows(?Branch $branch, array $periodContext, array $timeComparison): array
    {
        $periodLabel = (string) ($periodContext['label'] ?? 'Kỳ đang chọn');
        $currentFrom = $periodContext['currentFrom'] instanceof Carbon ? $periodContext['currentFrom'] : null;
        $currentTo = $periodContext['currentTo'] instanceof Carbon ? $periodContext['currentTo'] : null;

        return [
            ['Hạng mục', 'Giá trị'],
            ['Chi nhánh', $branch?->name ? $branch->name.' ('.$branch->code.')' : 'Cửa hàng'],
            ['Thời điểm xuất', now()->format('d/m/Y H:i')],
            ['Loại kỳ', $periodLabel],
            ['Khoảng thời gian', $currentFrom && $currentTo ? $currentFrom->format('d/m/Y').' - '.$currentTo->format('d/m/Y') : 'Không xác định'],
            ['Số kỳ', (int) ($timeComparison['period_count'] ?? 0)],
            ['Bố cục bảng', 'Dọc - mỗi hàng là một kỳ'],
            ['Quy tắc doanh thu', 'SUM(orders.total) với đơn hợp lệ'],
            ['Quy tắc số đơn', 'COUNT DISTINCT orders.id với đơn hợp lệ'],
            ['Thứ tự kỳ', 'Mới nhất ở trên'],
        ];
    }

    private function chartBarsForMetric(string $period, Carbon $currentFrom, Carbon $currentTo, string $metric, ?string $amountColumn): array
    {
        if (! Schema::hasColumn('orders', 'created_at')) {
            return [];
        }
        if ($metric === 'revenue' && ! $amountColumn) {
            return [];
        }

        $period = $this->normalizeDashboardPeriod($period);
        $now = Carbon::now();
        $slots = [];

        if ($period === 'day') {
            $start = $currentFrom->copy()->startOfDay();
            $limit = $this->capToNow($currentTo->copy());
            for ($i = 0; $i < 12; $i++) {
                $slotStart = $start->copy()->addHours($i * 2);
                if ($slotStart->greaterThan($limit)) {
                    break;
                }
                $slotEnd = $slotStart->copy()->addHours(2)->subSecond();
                if ($slotEnd->greaterThan($limit)) {
                    $slotEnd = $limit->copy();
                }
                $slots[] = ['label' => $slotStart->format('H:i'), 'from' => $slotStart, 'to' => $slotEnd];
            }
        } elseif ($period === 'week') {
            $start = $currentFrom->copy()->startOfWeek(Carbon::MONDAY);
            $limit = $this->capToNow($currentTo->copy());
            for ($i = 0; $i < 7; $i++) {
                $slotStart = $start->copy()->addDays($i)->startOfDay();
                if ($slotStart->greaterThan($limit)) {
                    break;
                }
                $slotEnd = $slotStart->copy()->endOfDay();
                if ($slotEnd->greaterThan($limit)) {
                    $slotEnd = $limit->copy();
                }
                $slots[] = ['label' => 'T' . ($i + 2), 'from' => $slotStart, 'to' => $slotEnd];
            }
        } elseif ($period === 'month') {
            $cursor = $currentFrom->copy()->startOfMonth();
            $monthEnd = $this->capToNow($currentTo->copy());

            while ($cursor->lessThanOrEqualTo($monthEnd)) {
                $slotStart = $cursor->copy()->startOfDay();
                $slotEnd = $cursor->copy()->endOfWeek(Carbon::SUNDAY);
                if ($slotEnd->greaterThan($monthEnd)) {
                    $slotEnd = $monthEnd->copy();
                }

                $slots[] = ['label' => $slotStart->format('d/m'), 'from' => $slotStart, 'to' => $slotEnd];

                $cursor = $slotEnd->copy()->addDay()->startOfDay();
            }
        } elseif ($period === 'custom') {
            $cursor = $currentFrom->copy()->startOfDay();
            $customEnd = $this->capToNow($currentTo->copy());

            while ($cursor->lessThanOrEqualTo($customEnd)) {
                $slotStart = $cursor->copy()->startOfDay();
                $slotEnd = $cursor->copy()->endOfDay();
                if ($slotEnd->greaterThan($customEnd)) {
                    $slotEnd = $customEnd->copy();
                }

                $slots[] = ['label' => $slotStart->format('d/m'), 'from' => $slotStart, 'to' => $slotEnd];
                $cursor = $cursor->addDay();
            }
        } else {
            for ($m = 1; $m <= 12; $m++) {
                $slotStart = $currentFrom->copy()->startOfYear()->month($m)->startOfMonth();
                $limit = $this->capToNow($currentTo->copy());
                if ($slotStart->greaterThan($limit)) {
                    break;
                }
                $slotEnd = $slotStart->copy()->endOfMonth();
                if ($slotEnd->greaterThan($limit)) {
                    $slotEnd = $limit->copy();
                }

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
                // New users don't need branch scope (global metric)
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

    private function topProducts(?Carbon $from = null, ?Carbon $to = null, int $limit = 4): array
    {
        if (! Schema::hasTable('order_items') || ! Schema::hasColumn('order_items', 'product_id')) {
            return [];
        }

        $quantityColumn = Schema::hasColumn('order_items', 'quantity') ? 'quantity' : null;
        if (! $quantityColumn) {
            return [];
        }

        if ($to) {
            $to = $this->capToNow($to);
            if ($from && $from->greaterThan($to)) {
                return [];
            }
        }

        $salesQuery = DB::table('order_items')
            ->select(
                'product_id',
                DB::raw('SUM(' . $quantityColumn . ') as sold_qty'),
                DB::raw('SUM(COALESCE(total_price, 0)) as revenue')
            )
            ->whereNotNull('product_id');

        $orderJoinAvailable = Schema::hasTable('orders') && Schema::hasColumn('order_items', 'order_id');
        $orderCreatedAtAvailable = $orderJoinAvailable && Schema::hasColumn('orders', 'created_at');

        if ($orderJoinAvailable) {
            $salesQuery->join('orders', 'orders.id', '=', 'order_items.order_id');
        }

        if ($orderCreatedAtAvailable && $from && $to) {
            $salesQuery->whereBetween('orders.created_at', [$from, $to]);
        }

        // Apply branch scope to orders
        if ($orderJoinAvailable && $this->dashboardUseBranchScope && $this->dashboardBranchId !== null) {
            $salesQuery->where('orders.branch_id', $this->dashboardBranchId);
        }

        if ($orderJoinAvailable && Schema::hasColumn('orders', 'status')) {
            $salesQuery->where('orders.status', 'completed');
        }

        $salesQuery->groupBy('product_id')
            ->orderByDesc('sold_qty')
            ->limit($limit);

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

                $currentQty = (int) $row->sold_qty;
                $revenue = (float) ($row->revenue ?? 0);

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku ?? ('#' . $product->id),
                    'image_url' => $product->image_url,
                    'sold_qty' => $currentQty,
                    'revenue' => $revenue,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
