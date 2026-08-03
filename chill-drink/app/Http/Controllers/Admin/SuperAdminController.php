<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\SuperAdminAnalyticsRequest;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SystemLog;
use App\Models\User;
use App\Services\AnalyticsPeriodContext;
use App\Services\SuperAdminAnalyticsService;
use App\Support\SimpleXlsxWriter;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class SuperAdminController extends Controller
{
    /**
     * @var array<string, \Illuminate\Support\Collection>
     */
    private array $branchChartMetricsCache = [];

    public function __construct(
        private readonly SuperAdminAnalyticsService $analyticsService
    ) {
    }

    public function index(SuperAdminAnalyticsRequest $request)
    {
        $adminQuery = User::admins();
        $search = trim((string) $request->query('q'));
        $status = (string) $request->query('status', 'all');
        $role = (string) $request->query('role', 'all');
        $created = (string) $request->query('created', 'all');
        $rankingPeriod = in_array($request->query('ranking_period'), ['all', 'week', 'month', 'year'], true)
            ? $request->query('ranking_period')
            : 'all';
        $analyticsContext = $request->analyticsPeriodContext();
        $analyticsBranchIds = $analyticsContext->normalizedBranchIds();

        if ($search !== '') {
            $adminQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status === 'active') {
            $adminQuery->where('is_active', true);
        } elseif ($status === 'locked') {
            $adminQuery->where('is_active', false);
        }

        if ($role === 'super') {
            $adminQuery->where('role_id', 3);
        } elseif ($role === 'admin') {
            $adminQuery->where('role_id', 2);
        }

        if ($created === 'today') {
            $adminQuery->whereDate('created_at', today());
        } elseif ($created === 'week') {
            $adminQuery->where('created_at', '>=', now()->subDays(7));
        } elseif ($created === 'month') {
            $adminQuery->where('created_at', '>=', now()->subDays(30));
        }

        $adminUsers = $adminQuery
            ->with('branch')
            ->orderByDesc('is_active');
        
        if (Schema::hasColumn('users', 'last_login_at')) {
            $adminUsers = $adminUsers->orderByDesc('last_login_at');
        }
        
        $adminUsers = $adminUsers
            ->orderBy('name')
            ->paginate(8)
            ->withQueryString();

        $allAdmins = User::admins()->get();
        $loginHistoryByAdmin = $this->loginHistoryByAdmin($adminUsers);
        $businessSummary = $this->analyticsService->businessSummary($analyticsContext);
        $branchSummaryStats = $this->branchSummaryStats($analyticsContext);
        $branchRankingComparison = $this->analyticsService->branchComparison($analyticsContext, [
            'ranking_period' => $rankingPeriod,
            'search' => trim((string) $request->query('branch_search', '')),
            'sort' => (string) $request->query('branch_sort', 'revenue'),
            'direction' => (string) $request->query('branch_direction', 'desc'),
            'performance' => (string) $request->query('branch_performance', 'all'),
            'per_page' => 5,
            'page' => (int) $request->query('branch_page', 1),
            'analytics_branch_ids' => $analyticsBranchIds,
        ]);
        $branchRankingStats = $branchRankingComparison['paginator']->getCollection();
        $branchDetailBranchId = $this->resolveBranchDetailBranchId(
            $request,
            $branchRankingStats,
            $analyticsContext
        );
        $branchProductDetail = $this->analyticsService->branchProductDetail($analyticsContext, $branchDetailBranchId ?? 0, [
            'sort_by' => (string) $request->query('branch_product_sort', 'quantity'),
            'analytics_branch_ids' => $analyticsBranchIds,
        ]);
        $topProductSort = in_array($request->query('analytics_product_sort'), ['quantity', 'revenue'], true)
            ? (string) $request->query('analytics_product_sort')
            : 'quantity';
        $topProducts = $this->analyticsService->topProducts($analyticsContext, $topProductSort, 5);
        $branchTimeIndicator = in_array($request->query('branch_time_indicator'), ['both', 'revenue', 'orders'], true)
            ? (string) $request->query('branch_time_indicator')
            : 'both';
        $branchTimeComparison = $this->analyticsService->branchTimeComparison($analyticsContext, [
            'indicator' => $branchTimeIndicator,
            'period_count' => $request->query('branch_time_period_count'),
            'analytics_branch_ids' => $analyticsBranchIds,
        ]);
        $branchTimeSearch = trim((string) $request->query('branch_time_search', ''));
        $branchTimePerPage = in_array((int) $request->query('branch_time_per_page', 10), [10, 25, 50], true)
            ? (int) $request->query('branch_time_per_page', 10)
            : 10;
        $branchTimePage = max(1, (int) $request->query('branch_time_page', 1));
        $branchTimeRows = $branchTimeComparison['branches'] instanceof Collection
            ? $branchTimeComparison['branches']
            : collect($branchTimeComparison['branches'] ?? []);
        if ($branchTimeSearch !== '') {
            $searchNeedle = Str::lower($branchTimeSearch);
            $branchTimeRows = $branchTimeRows->filter(function (array $branch) use ($searchNeedle): bool {
                return Str::contains(Str::lower((string) ($branch['branch_name'] ?? '')), $searchNeedle)
                    || Str::contains(Str::lower((string) ($branch['branch_code'] ?? '')), $searchNeedle);
            })->values();
        }
        $branchTimeLastPage = max(1, (int) ceil(max(1, $branchTimeRows->count()) / $branchTimePerPage));
        $branchTimePage = min($branchTimePage, $branchTimeLastPage);
        $branchTimePaginator = new LengthAwarePaginator(
            $branchTimeRows->forPage($branchTimePage, $branchTimePerPage)->values(),
            $branchTimeRows->count(),
            $branchTimePerPage,
            $branchTimePage,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'pageName' => 'branch_time_page',
            ]
        );
        $branchTimePaginator->withQueryString();
        $branchTimeComparison['filtered_branches'] = $branchTimeRows;
        $branchTimeComparison['visible_branches'] = $branchTimePaginator->getCollection();
        $branchTimeComparison['paginator'] = $branchTimePaginator;
        $branchTimeComparison['search'] = $branchTimeSearch;
        $branchTimeComparison['per_page'] = $branchTimePerPage;
        $branchTimeComparison['page'] = $branchTimePage;
        $branchTimeComparison['total_filtered'] = $branchTimeRows->count();
        $branchTimeComparison['period_count_selected'] = $branchTimeComparison['period_count_selected'] ?? null;
        $branchTimeExportBase = array_filter(
            array_merge($request->query(), $analyticsContext->normalizedQueryParameters),
            static fn ($value) => $value !== null && $value !== ''
        );
        $branchTimeComparison['export_current_url'] = route('admin.super-admin', array_filter(array_merge(
            $branchTimeExportBase,
            [
                'branch_time_search' => $branchTimeSearch !== '' ? $branchTimeSearch : null,
                'branch_time_per_page' => $branchTimePerPage,
                'branch_time_indicator' => $branchTimeIndicator,
                'branch_time_period_count' => $branchTimeComparison['period_count_selected'],
                'branch_time_page' => $branchTimePage,
                'analytics_time_matrix_export' => 'current',
            ]
        ), static fn ($value) => $value !== null && $value !== ''));
        $branchTimeComparison['export_all_url'] = route('admin.super-admin', array_filter(array_merge(
            $branchTimeExportBase,
            [
                'branch_time_search' => null,
                'branch_time_per_page' => $branchTimePerPage,
                'branch_time_indicator' => $branchTimeIndicator,
                'branch_time_period_count' => $branchTimeComparison['period_count_selected'],
                'branch_time_page' => null,
                'analytics_time_matrix_export' => 'all',
            ]
        ), static fn ($value) => $value !== null && $value !== ''));
        $branchTimeComparison['period_count_query_value'] = $branchTimeComparison['period_count_selected'] ?? $branchTimeComparison['period_count'];

        if ($request->filled('analytics_time_matrix_export')) {
            return $this->downloadBranchTimeComparisonExport($request, $analyticsContext, $branchTimeComparison);
        }
        $focusProductSort = in_array($request->query('analytics_focus_product_sort'), ['quantity', 'revenue'], true)
            ? (string) $request->query('analytics_focus_product_sort')
            : 'quantity';
        $focusProductQuery = trim((string) $request->query('analytics_focus_product_query', ''));
        $focusProductId = $this->resolveFocusProductId($request, $topProducts);
        $focusProductCandidates = $this->analyticsService->focusProducts($focusProductQuery, 8);
        $focusProductPerformance = $focusProductId !== null
            ? $this->analyticsService->productBranchPerformance($analyticsContext, $focusProductId, [
                'sort_by' => $focusProductSort,
                'search' => trim((string) $request->query('analytics_focus_branch_search', '')),
                'page' => (int) $request->query('analytics_focus_branch_page', 1),
                'analytics_branch_ids' => $analyticsBranchIds,
            ])
            : $this->emptyFocusProductPerformance($analyticsContext, $focusProductSort);

        return view('admin.super-admin', [
            'adminUsers' => $adminUsers,
            'adminCount' => $allAdmins->count(),
            'activeAdminCount' => $allAdmins->where('is_active', true)->count(),
            'totalUserCount' => User::count(),
            'customerCount' => User::customers()->count(),
            'productCount' => Schema::hasTable('products') ? Product::count() : 0,
            'categoryCount' => Schema::hasTable('categories') ? Category::count() : 0,
            'branchCount' => Schema::hasTable('branches') ? Branch::count() : 0,
            'roleCount' => Schema::hasTable('roles') ? DB::table('roles')->count() : 0,
            'branches' => Schema::hasTable('branches')
                ? Branch::query()
                    ->select(['id', 'name', 'code', 'address', 'status'])
                    ->with([
                        'users' => static fn ($query) => $query->select(['id', 'name', 'branch_id']),
                    ])
                    ->latest()
                    ->get()
                : collect(),
            'businessSummary' => $businessSummary,
            'branchTimeComparison' => $branchTimeComparison,
            'activityLogs' => Schema::hasTable('system_logs')
                ? SystemLog::latest()->limit(8)->get()
                : collect(),
            'loginHistoryByAdmin' => $loginHistoryByAdmin,
            'securityStats' => $this->securityStats(),
            'systemHealth' => $this->systemHealth(),
            'notifications' => $request->user()->notifications()->latest()->limit(5)->get(),
            'unreadNotificationCount' => $request->user()->unreadNotifications()->count(),
            'filters' => compact('search', 'status', 'role', 'created'),
            'branchSummaryStats' => $branchSummaryStats,
            'branchRankingStats' => $branchRankingStats,
            'branchRankingComparison' => $branchRankingComparison,
            'branchProductDetail' => $branchProductDetail,
            'branchDetailBranchId' => $branchDetailBranchId,
            'rankingPeriod' => $rankingPeriod,
            'topProductSort' => $topProductSort,
            'topProducts' => $topProducts,
            'focusProductSort' => $focusProductSort,
            'focusProductQuery' => $focusProductQuery,
            'focusProductId' => $focusProductId,
            'focusProductCandidates' => $focusProductCandidates,
            'focusProductPerformance' => $focusProductPerformance,
            'analyticsContext' => $analyticsContext,
        ]);
    }

    private function safeDashboardAnalytics(array $filters = []): array
    {
        try {
            return $this->dashboardAnalytics($filters);
        } catch (Throwable $exception) {
            report($exception);

            return $this->emptyDashboardAnalytics();
        }
    }

    private function emptyDashboardAnalytics(): array
    {
        return [
            'period_label' => '30 ngày gần nhất',
            'compare_label' => 'Kỳ trước',
            'revenue' => 0,
            'orders' => 0,
            'customers' => 0,
            'units' => 0,
            'average_order' => 0,
            'growth' => ['revenue' => 0, 'orders' => 0, 'customers' => 0, 'units' => 0, 'average_order' => 0],
            'top_products' => collect(),
            'daily' => ['labels' => collect(), 'values' => collect(), 'previous' => collect()],
            'branches' => collect(),
            'highlights' => [
                'top_revenue_branch' => null,
                'top_order_branch' => null,
                'top_units_branch' => null,
                'average_revenue_per_branch' => 0,
            ],
        ];
    }

    private function buildValidSalesOrdersQuery(
        ?CarbonInterface $from = null,
        ?CarbonInterface $to = null,
        int|array|null $branchScope = null,
        $orderFilterIds = null
    ): \Illuminate\Database\Eloquent\Builder {
        $query = $this->analyticsService->validSalesOrdersQuery();

        $this->analyticsService->applyDateRange($query, $from, $to);
        $this->analyticsService->applyBranchScope($query, $branchScope);

        if ($orderFilterIds) {
            $query->whereIn('orders.id', $orderFilterIds);
        }

        return $query;
    }

    private function buildValidSalesOrderItemsQuery(
        ?CarbonInterface $from = null,
        ?CarbonInterface $to = null,
        int|array|null $branchScope = null,
        $orderFilterIds = null
    ): \Illuminate\Database\Eloquent\Builder {
        $query = $this->analyticsService->validSalesOrderItemsQuery();

        $this->analyticsService->applyDateRange($query, $from, $to);
        $this->analyticsService->applyBranchScope($query, $branchScope);

        if ($orderFilterIds) {
            $query->whereIn('order_items.order_id', $orderFilterIds);
        }

        return $query;
    }

    /**
     * @return array<int>
     */
    private function analyticsBranchScopeIds(?AnalyticsPeriodContext $context = null, mixed $fallback = null): array
    {
        if ($context?->hasBranchScope()) {
            return $context->normalizedBranchIds();
        }

        return $this->normalizeBranchScopeSelection($fallback);
    }

    /**
     * @return array<int>
     */
    private function normalizeBranchScopeSelection(int|array|string|null $branchScope): array
    {
        if (is_string($branchScope) && $branchScope !== '') {
            $branchScope = explode(',', $branchScope);
        }

        if (! is_array($branchScope)) {
            $branchScope = $branchScope === null || $branchScope === '' ? [] : [$branchScope];
        }

        return collect($branchScope)
            ->filter(static fn ($value) => $value !== null && $value !== '' && is_numeric($value))
            ->map(static fn ($value) => (int) $value)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @param array<string, mixed> $branchTimeComparison
     */
    private function downloadBranchTimeComparisonExport(Request $request, AnalyticsPeriodContext $context, array $branchTimeComparison)
    {
        $exportScope = $request->query('analytics_time_matrix_export') === 'all' ? 'all' : 'current';
        $fileName = sprintf(
            'so-sanh-chi-nhanh-theo-thoi-gian-%s-%s.xlsx',
            $exportScope,
            now($context->timezone)->format('Ymd_His')
        );
        $tempPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.Str::random(24).'.xlsx';

        $writer = new SimpleXlsxWriter();
        $writer->write($tempPath, $this->buildBranchTimeComparisonWorkbook($branchTimeComparison, $context, $exportScope));

        return response()->download($tempPath, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * @param array<string, mixed> $branchTimeComparison
     * @return array<int, array{name: string, rows: array<int, array<int, mixed>>}>
     */
    private function buildBranchTimeComparisonWorkbook(array $branchTimeComparison, AnalyticsPeriodContext $context, string $exportScope): array
    {
        $periods = collect($branchTimeComparison['periods'] ?? []);
        $sourceBranches = $exportScope === 'all'
            ? collect($branchTimeComparison['branches'] ?? [])
            : collect($branchTimeComparison['visible_branches'] ?? $branchTimeComparison['filtered_branches'] ?? []);
        $rows = $sourceBranches->values();
        $totalBranchCount = $exportScope === 'all'
            ? $rows->count()
            : (int) ($branchTimeComparison['total_filtered'] ?? $rows->count());
        $totals = $branchTimeComparison['totals'] ?? [];
        $branchScopeLabel = (string) ($branchTimeComparison['branch_scope_label'] ?? $context->branchScopeLabel);
        $indicatorLabel = (string) ($branchTimeComparison['indicator_label'] ?? 'Cả hai');
        $groupLabel = (string) ($branchTimeComparison['group_label'] ?? 'Ngày');
        $periodCount = (int) ($branchTimeComparison['period_count'] ?? $periods->count());
        $search = (string) ($branchTimeComparison['search'] ?? '');
        $generatedAt = now($context->timezone)->format('d/m/Y H:i:s');

        return [
            [
                'name' => 'So sánh',
                'rows' => $this->buildComparisonSheetRows($rows, $periods, $totals, $indicatorLabel, $groupLabel, $periodCount, $branchScopeLabel, $search, $exportScope, $generatedAt, $totalBranchCount),
            ],
            [
                'name' => 'Dữ liệu chuẩn',
                'rows' => $this->buildStandardDataSheetRows($rows, $periods, $branchTimeComparison, $context, $exportScope, $generatedAt),
            ],
            [
                'name' => 'Điều kiện báo cáo',
                'rows' => $this->buildReportConditionSheetRows($branchTimeComparison, $context, $exportScope, $generatedAt),
            ],
        ];
    }

    /**
     * @param \Illuminate\Support\Collection<int, array<string, mixed>> $branches
     * @param \Illuminate\Support\Collection<int, array<string, mixed>> $periods
     * @param array<string, mixed> $totals
     * @return array<int, array<int, mixed>>
     */
    private function buildComparisonSheetRows(Collection $branches, Collection $periods, array $totals, string $indicatorLabel, string $groupLabel, int $periodCount, string $branchScopeLabel, string $search, string $exportScope, string $generatedAt, int $totalBranchCount): array
    {
        $rows = [
            ['So sánh chi nhánh theo thời gian'],
            [
                'Phạm vi chi nhánh',
                $branchScopeLabel,
                'Kỳ',
                $groupLabel,
                'Số kỳ',
                $periodCount > 0 ? $periodCount : $periods->count(),
                'Chỉ số',
                $indicatorLabel,
                'Tìm chi nhánh',
                $search !== '' ? $search : 'Không',
                'Xuất',
                $exportScope === 'all' ? 'Toàn bộ dữ liệu' : 'Bảng đang xem',
                'Tạo lúc',
                $generatedAt,
            ],
            [],
        ];

        $headerRow = ['STT', 'Chi nhánh'];
        $subHeaderRow = ['', ''];

        foreach ($periods as $period) {
            $periodLabel = (string) ($period['display_label'] ?? $period['label'] ?? $period['key'] ?? '');

            if ($indicatorLabel === 'Cả hai') {
                $headerRow[] = $periodLabel;
                $headerRow[] = '';
                $subHeaderRow[] = 'Doanh thu';
                $subHeaderRow[] = 'Đơn';
            } else {
                $headerRow[] = $periodLabel;
                $subHeaderRow[] = $indicatorLabel;
            }
        }

        $headerRow[] = 'Tổng doanh thu';
        $headerRow[] = 'Tổng đơn';
        $headerRow[] = 'Thay đổi gần nhất doanh thu';
        $headerRow[] = 'Thay đổi gần nhất số đơn';

        if ($indicatorLabel === 'Cả hai') {
            $subHeaderRow[] = '';
            $subHeaderRow[] = '';
            $subHeaderRow[] = '';
            $subHeaderRow[] = '';
            $rows[] = $headerRow;
            $rows[] = $subHeaderRow;
        } else {
            $rows[] = $headerRow;
        }

        $rows[] = $this->buildComparisonTotalRow($branches, $periods, $totals, $indicatorLabel, $totalBranchCount);

        foreach ($branches as $index => $branch) {
            $rows[] = $this->buildComparisonBranchRow($branch, $periods, $indicatorLabel, $index + 1);
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $branch
     * @param \Illuminate\Support\Collection<int, array<string, mixed>> $periods
     * @return array<int, mixed>
     */
    private function buildComparisonTotalRow(Collection $branches, Collection $periods, array $totals, string $indicatorLabel, int $totalBranchCount): array
    {
        $row = ['Tổng '.number_format($totalBranchCount).' chi nhánh', ''];
        $periodTotals = collect($totals['periods'] ?? [])->keyBy('period_key');

        foreach ($periods as $period) {
            $periodTotalsRow = $periodTotals->get($period['key']) ?? [];
            if ($indicatorLabel === 'Cả hai') {
                $row[] = (int) ($periodTotalsRow['revenue'] ?? 0);
                $row[] = (int) ($periodTotalsRow['valid_order_count'] ?? 0);
            } else {
                $row[] = (int) ($periodTotalsRow[$indicatorLabel === 'Doanh thu' ? 'revenue' : 'valid_order_count'] ?? 0);
            }
        }

        $row[] = (int) ($totals['total_revenue'] ?? 0);
        $row[] = (int) ($totals['total_valid_orders'] ?? 0);
        $row[] = 'Không áp dụng';
        $row[] = 'Không áp dụng';

        return $row;
    }

    /**
     * @param array<string, mixed> $branch
     * @param \Illuminate\Support\Collection<int, array<string, mixed>> $periods
     * @return array<int, mixed>
     */
    private function buildComparisonBranchRow(array $branch, Collection $periods, string $indicatorLabel, int $rank): array
    {
        $row = [
            $rank,
            filled($branch['branch_name'] ?? null) ? (string) $branch['branch_name'] : 'Chưa rõ',
        ];

        foreach ($periods as $period) {
            $bucket = $branch['periods'][$period['key']] ?? ['revenue' => 0, 'valid_order_count' => 0];

            if ($indicatorLabel === 'Cả hai') {
                $row[] = (int) ($bucket['revenue'] ?? 0);
                $row[] = (int) ($bucket['valid_order_count'] ?? 0);
            } else {
                $row[] = (int) ($bucket[$indicatorLabel === 'Doanh thu' ? 'revenue' : 'valid_order_count'] ?? 0);
            }
        }

        $row[] = (int) ($branch['total_revenue'] ?? 0);
        $row[] = (int) ($branch['total_valid_orders'] ?? 0);
        $row[] = (string) ($branch['latest_revenue_change']['label'] ?? 'Không đổi');
        $row[] = (string) ($branch['latest_order_change']['label'] ?? 'Không đổi');

        return $row;
    }

    /**
     * @param \Illuminate\Support\Collection<int, array<string, mixed>> $branches
     * @param \Illuminate\Support\Collection<int, array<string, mixed>> $periods
     * @param array<string, mixed> $branchTimeComparison
     * @return array<int, array<int, mixed>>
     */
    private function buildStandardDataSheetRows(Collection $branches, Collection $periods, array $branchTimeComparison, AnalyticsPeriodContext $context, string $exportScope, string $generatedAt): array
    {
        $rows = [
            ['Dữ liệu chuẩn'],
            [
                'Xuất',
                $exportScope === 'all' ? 'Toàn bộ dữ liệu' : 'Bảng đang xem',
                'Kỳ',
                (string) ($branchTimeComparison['group_label'] ?? 'Ngày'),
                'Phạm vi chi nhánh',
                (string) ($branchTimeComparison['branch_scope_label'] ?? $context->branchScopeLabel),
                'Tạo lúc',
                $generatedAt,
            ],
            [],
            ['STT', 'Branch ID', 'Mã chi nhánh', 'Chi nhánh', 'Mã kỳ', 'Nhãn kỳ', 'Bắt đầu', 'Kết thúc', 'Doanh thu', 'Số đơn', 'Tổng doanh thu', 'Tổng đơn', 'Thay đổi doanh thu', 'Thay đổi số đơn'],
        ];

        foreach ($branches as $index => $branch) {
            foreach ($periods as $period) {
                $bucket = is_array(($branch['periods'][$period['key']] ?? null))
                    ? $branch['periods'][$period['key']]
                    : ['revenue' => 0, 'valid_order_count' => 0];
                $rows[] = [
                    $index + 1,
                    $branch['branch_id'] ?? '',
                    $branch['branch_code'] ?? '',
                    $branch['branch_name'] ?? '',
                    $period['key'] ?? '',
                    $period['display_label'] ?? ($period['label'] ?? ''),
                    $this->formatDateTimeForExport($period['start'] ?? null),
                    $this->formatDateTimeForExport($period['end'] ?? null),
                    (int) ($bucket['revenue'] ?? 0),
                    (int) ($bucket['valid_order_count'] ?? 0),
                    (int) ($branch['total_revenue'] ?? 0),
                    (int) ($branch['total_valid_orders'] ?? 0),
                    (string) ($branch['latest_revenue_change']['label'] ?? 'Không đổi'),
                    (string) ($branch['latest_order_change']['label'] ?? 'Không đổi'),
                ];
            }
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $branchTimeComparison
     * @return array<int, array<int, mixed>>
     */
    private function buildReportConditionSheetRows(array $branchTimeComparison, AnalyticsPeriodContext $context, string $exportScope, string $generatedAt): array
    {
        $rows = [
            ['Điều kiện báo cáo'],
            ['Xuất', $exportScope === 'all' ? 'Toàn bộ dữ liệu' : 'Bảng đang xem'],
            ['Loại kỳ', (string) ($branchTimeComparison['period_type'] ?? $context->periodType)],
            ['Nhóm kỳ', (string) ($branchTimeComparison['group_label'] ?? 'Ngày')],
            ['Số kỳ', (int) ($branchTimeComparison['period_count'] ?? 0)],
            ['Chỉ số', (string) ($branchTimeComparison['indicator_label'] ?? 'Cả hai')],
            ['Phạm vi chi nhánh', (string) ($branchTimeComparison['branch_scope_label'] ?? $context->branchScopeLabel)],
            ['Tìm chi nhánh', (string) ($branchTimeComparison['search'] ?? '')],
            ['Trang hiện tại', (int) ($branchTimeComparison['page'] ?? 1)],
            ['Kích thước trang', (int) ($branchTimeComparison['per_page'] ?? 0)],
            ['Tổng chi nhánh lọc', (int) ($branchTimeComparison['total_filtered'] ?? 0)],
            ['Tạo lúc', $generatedAt],
            [],
            ['Mã kỳ', 'Nhãn', 'Bắt đầu', 'Kết thúc', 'Đang diễn ra'],
        ];

        foreach (collect($branchTimeComparison['periods'] ?? []) as $period) {
            $rows[] = [
                $period['key'] ?? '',
                $period['display_label'] ?? ($period['label'] ?? ''),
                $this->formatDateTimeForExport($period['start'] ?? null),
                $this->formatDateTimeForExport($period['end'] ?? null),
                ! empty($period['is_partial']) ? 'Có' : 'Không',
            ];
        }

        return $rows;
    }

    private function formatDateTimeForExport(mixed $value): string
    {
        if ($value instanceof CarbonInterface) {
            return $value->format('d/m/Y H:i:s');
        }

        if (is_string($value) && $value !== '') {
            try {
                return Carbon::parse($value)->format('d/m/Y H:i:s');
            } catch (Throwable) {
                return $value;
            }
        }

        return '';
    }

    private function dashboardAnalytics(array $filters = []): array
    {
        if (! Schema::hasTable('orders')) {
            return $this->emptyDashboardAnalytics();
        }

        ['start' => $start, 'end' => $end, 'label' => $periodLabel, 'days' => $periodDays] = $this->analyticsPeriodRange($filters['period'] ?? '30');
        ['start' => $comparisonStart, 'end' => $comparisonEnd, 'label' => $compareLabel, 'offset_days' => $comparisonOffsetDays] = $this->analyticsComparisonRange(
            $start,
            $end,
            $periodDays,
            $filters['compare'] ?? 'previous'
        );
        $orderColumns = ['orders.id', 'orders.user_id', 'orders.created_at', 'orders.total'];
        $orderFilterIds = $this->analyticsService->filteredOrderIdsSubquery(
            $filters['category_id'] ?? null,
            $filters['product_id'] ?? null
        );
        $branchScope = $this->analyticsBranchScopeIds(null, $filters['analytics_branch_ids'] ?? ($filters['branch_ids'] ?? ($filters['branch_id'] ?? null)));

        $currentOrdersQuery = $this->buildValidSalesOrdersQuery(
            $start,
            $end,
            $branchScope,
            $orderFilterIds ? clone $orderFilterIds : null
        );
        $previousOrdersQuery = $this->buildValidSalesOrdersQuery(
            $comparisonStart,
            $comparisonEnd,
            $branchScope,
            $orderFilterIds ? clone $orderFilterIds : null
        );

        $current = (clone $currentOrdersQuery)->get($orderColumns);
        $previous = (clone $previousOrdersQuery)->get($orderColumns);

        $currentMetrics = [
            'revenue' => $this->analyticsService->revenueSummary(clone $currentOrdersQuery),
            'orders' => $this->analyticsService->orderSummary(clone $currentOrdersQuery),
            'customers' => $this->analyticsService->customerSummary(clone $currentOrdersQuery),
        ];
        $previousMetrics = [
            'revenue' => $this->analyticsService->revenueSummary(clone $previousOrdersQuery),
            'orders' => $this->analyticsService->orderSummary(clone $previousOrdersQuery),
            'customers' => $this->analyticsService->customerSummary(clone $previousOrdersQuery),
        ];

        $units = 0;
        $previousUnits = 0;
        if (Schema::hasTable('order_items')) {
            $currentItemsQuery = $this->buildValidSalesOrderItemsQuery(
                $start,
                $end,
                $branchScope,
                $orderFilterIds ? clone $orderFilterIds : null
            );
            $previousItemsQuery = $this->buildValidSalesOrderItemsQuery(
                $comparisonStart,
                $comparisonEnd,
                $branchScope,
                $orderFilterIds ? clone $orderFilterIds : null
            );

            $units = $this->analyticsService->itemQuantitySummary($currentItemsQuery);
            $previousUnits = $this->analyticsService->itemQuantitySummary($previousItemsQuery);
        }

        $average = $currentMetrics['orders'] > 0 ? (int) round($currentMetrics['revenue'] / $currentMetrics['orders']) : 0;
        $previousAverage = $previousMetrics['orders'] > 0 ? (int) round($previousMetrics['revenue'] / $previousMetrics['orders']) : 0;
        $growth = static function (int $currentValue, int $previousValue): float {
            return $previousValue > 0 ? round((($currentValue - $previousValue) / $previousValue) * 100, 1) : ($currentValue > 0 ? 100 : 0);
        };

        $days = collect(range($periodDays - 1, 0))->map(fn (int $offset) => $end->copy()->subDays($offset));
        $dailyValues = $days->map(fn (Carbon $day) => (int) $current->filter(fn ($order) => $order->created_at->isSameDay($day))->sum('total'));
        $previousDailyValues = $days->map(function (Carbon $day) use ($previous, $comparisonOffsetDays, $comparisonStart): int {
            $mappedDay = $comparisonOffsetDays !== null
                ? $day->copy()->subDays($comparisonOffsetDays)
                : $comparisonStart->copy()->addDays($comparisonStart->diffInDays($day));

            return (int) $previous->filter(fn ($order) => $order->created_at->isSameDay($mappedDay))->sum('total');
        });

        $branches = $this->analyticsBranchBreakdown($filters, $start, $end, $comparisonStart, $comparisonEnd);
        $topRevenueBranch = $branches->sortByDesc('revenue')->first();
        $topOrderBranch = $branches->sortByDesc('orders')->first();
        $topUnitsBranch = $branches->sortByDesc('units')->first();

        return [
            'period_label' => $periodLabel,
            'compare_label' => $compareLabel,
            'revenue' => $currentMetrics['revenue'],
            'orders' => $currentMetrics['orders'],
            'customers' => $currentMetrics['customers'],
            'units' => $units,
            'average_order' => $average,
            'growth' => [
                'revenue' => $growth($currentMetrics['revenue'], $previousMetrics['revenue']),
                'orders' => $growth($currentMetrics['orders'], $previousMetrics['orders']),
                'customers' => $growth($currentMetrics['customers'], $previousMetrics['customers']),
                'units' => $growth($units, $previousUnits),
                'average_order' => $growth($average, $previousAverage),
            ],
            'top_products' => collect(),
            'daily' => [
                'labels' => $days->map(fn (Carbon $day) => $day->format('d/m')),
                'values' => $dailyValues,
                'previous' => $previousDailyValues,
            ],
            'branches' => $branches,
            'highlights' => [
                'top_revenue_branch' => $topRevenueBranch,
                'top_order_branch' => $topOrderBranch,
                'top_units_branch' => $topUnitsBranch,
                'average_revenue_per_branch' => $branches->isNotEmpty() ? (int) round($branches->avg('revenue')) : 0,
            ],
        ];
    }

    private function analyticsPeriodRange(string $period): array
    {
        $today = today();

        return match ($period) {
            'today' => [
                'start' => $today->copy()->startOfDay(),
                'end' => $today->copy()->endOfDay(),
                'label' => 'Hôm nay',
                'days' => 1,
            ],
            '7' => [
                'start' => $today->copy()->subDays(6)->startOfDay(),
                'end' => $today->copy()->endOfDay(),
                'label' => '7 ngày gần nhất',
                'days' => 7,
            ],
            'month' => [
                'start' => $today->copy()->startOfMonth(),
                'end' => $today->copy()->endOfDay(),
                'label' => 'Tháng này',
                'days' => max(1, $today->copy()->startOfMonth()->diffInDays($today) + 1),
            ],
            default => [
                'start' => $today->copy()->subDays(29)->startOfDay(),
                'end' => $today->copy()->endOfDay(),
                'label' => '30 ngày gần nhất',
                'days' => 30,
            ],
        };
    }

    private function analyticsComparisonRange(Carbon $start, Carbon $end, int $periodDays, string $compare): array
    {
        if ($compare === 'year') {
            return [
                'start' => $start->copy()->subYear(),
                'end' => $end->copy()->subYear(),
                'label' => 'Cùng kỳ năm trước',
                'offset_days' => null,
            ];
        }

        return [
            'start' => $start->copy()->subDays($periodDays),
            'end' => $start->copy()->subDay()->endOfDay(),
            'label' => 'Kỳ trước',
            'offset_days' => $periodDays,
        ];
    }

    private function analyticsBranchBreakdown(
        array $filters,
        Carbon $start,
        Carbon $end,
        Carbon $comparisonStart,
        Carbon $comparisonEnd
    ): Collection {
        if (! Schema::hasTable('branches') || ! Schema::hasTable('orders')) {
            return collect();
        }

        $orderFilterIds = $this->analyticsService->filteredOrderIdsSubquery(
            $filters['category_id'] ?? null,
            $filters['product_id'] ?? null
        );
        $branchScope = $this->analyticsBranchScopeIds(null, $filters['analytics_branch_ids'] ?? ($filters['branch_ids'] ?? ($filters['branch_id'] ?? null)));

        $currentRows = $this->buildValidSalesOrdersQuery(
            $start,
            $end,
            $branchScope,
            $orderFilterIds ? clone $orderFilterIds : null
        )
            ->join('branches', 'branches.id', '=', 'orders.branch_id')
            ->selectRaw('branches.id as branch_id, branches.name as branch_name, branches.code as branch_code, SUM(orders.total) as revenue, COUNT(orders.id) as orders')
            ->groupBy('branches.id', 'branches.name', 'branches.code')
            ->get()
            ->keyBy('branch_id');

        $previousRows = $this->buildValidSalesOrdersQuery(
            $comparisonStart,
            $comparisonEnd,
            $branchScope,
            $orderFilterIds ? clone $orderFilterIds : null
        )
            ->join('branches', 'branches.id', '=', 'orders.branch_id')
            ->selectRaw('branches.id as branch_id, SUM(orders.total) as revenue')
            ->groupBy('branches.id')
            ->get()
            ->keyBy('branch_id');

        $unitsRows = collect();
        if (Schema::hasTable('order_items')) {
            $unitsRows = $this->buildValidSalesOrderItemsQuery(
                $start,
                $end,
                $branchScope,
                $orderFilterIds ? clone $orderFilterIds : null
            )
                ->join('branches', 'branches.id', '=', 'orders.branch_id')
                ->selectRaw('branches.id as branch_id, SUM(order_items.quantity) as units')
                ->groupBy('branches.id')
                ->get()
                ->keyBy('branch_id');
        }

        $branchIds = $branchScope !== []
            ? collect($branchScope)->values()
            : $currentRows->keys()->merge($previousRows->keys())->unique()->values();
        if ($branchIds->isEmpty()) {
            $branchIds = Branch::query()
                ->when($branchScope !== [], fn ($query) => $query->whereIn('id', $branchScope))
                ->pluck('id');
        }

        $branches = Branch::withCount('users')
            ->whereIn('id', $branchIds)
            ->get()
            ->keyBy('id');

        $totalRevenue = max(1, (int) $currentRows->sum('revenue'));

        return $branchIds->map(function ($branchId) use ($branches, $currentRows, $previousRows, $unitsRows, $totalRevenue) {
            $branch = $branches->get($branchId);
            if (! $branch) {
                return null;
            }

            $current = $currentRows->get($branchId);
            $previous = $previousRows->get($branchId);
            $revenue = (int) ($current->revenue ?? 0);
            $orders = (int) ($current->orders ?? 0);
            $previousRevenue = (int) ($previous->revenue ?? 0);
            $units = (int) (($unitsRows->get($branchId)->units ?? 0));
            $growth = $previousRevenue > 0 ? round((($revenue - $previousRevenue) / $previousRevenue) * 100, 1) : ($revenue > 0 ? 100 : 0);

            return [
                'branch_id' => $branch->id,
                'branch_name' => $branch->name,
                'branch_code' => $branch->code,
                'revenue' => $revenue,
                'orders' => $orders,
                'units' => $units,
                'staff_count' => (int) $branch->users_count,
                'average_order_value' => $orders > 0 ? (int) round($revenue / $orders) : 0,
                'growth' => $growth,
                'performance_percentage' => round(($revenue / $totalRevenue) * 100, 1),
            ];
        })
            ->filter()
            ->sortByDesc('revenue')
            ->values();
    }

    public function storeAdmin(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('createAdmin', [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Vui lòng nhập tên quản trị viên.',
            'email.required' => 'Vui lòng nhập email.',
            'email.unique' => 'Email này đã được sử dụng.',
            'password.required' => 'Vui lòng nhập mật khẩu ban đầu.',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp.',
        ]);

        try {
            DB::beginTransaction();

            // Create admin user
            $admin = User::create([
                'name' => $validated['name'],
                'email' => strtolower($validated['email']),
                'password' => Hash::make($validated['password']),
                'role_id' => 2,
                'is_active' => $request->boolean('is_active', true),
            ]);

            // Auto-create branch for this admin
            $branch = Branch::create([
                'name' => "Chi nhánh - {$admin->name}",
                'code' => "ADM{$admin->id}",
                'email' => $admin->email,
                'phone' => null,
                'address' => 'Không áp dụng',
                'status' => $admin->is_active,
            ]);

            // Assign branch to admin
            $admin->update(['branch_id' => $branch->id]);

            DB::commit();

            SystemLog::record(
                $request->user(),
                "Đã tạo tài khoản Admin {$admin->email} và chi nhánh {$branch->name}",
                'admin',
                'success',
                ['target_user_id' => $admin->id, 'target_branch_id' => $branch->id],
            );

            return redirect()
                ->route('admin.super-admin', ['q' => $admin->email])
                ->with('success', 'Đã tạo tài khoản Admin và chi nhánh quản lý mới.');
        } catch (Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            \Log::error('Admin creation failed', [
                'email' => $validated['email'],
                'message' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Có lỗi xảy ra khi tạo Admin. Vui lòng thử lại.');
        }
    }

    public function enterAdminWorkspace(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        ]);

        $branchId = $validated['branch_id'] ?? null;

        $request->session()->put('super_admin_admin_view', true);
        $request->session()->put('super_admin_preview_branch_id', $branchId);

        return redirect()->route('admin.dashboard', array_filter([
            'branch_id' => $branchId,
        ], static fn ($value) => $value !== null));
    }

    public function exitAdminWorkspace(Request $request): RedirectResponse
    {
        $request->session()->forget([
            'super_admin_admin_view',
            'super_admin_preview_branch_id',
        ]);

        return redirect()->route('admin.super-admin');
    }

    private function orderStats(?AnalyticsPeriodContext $context = null): array
    {
        if (! Schema::hasTable('orders')) {
            return ['today_count' => 0, 'today_revenue' => 0, 'month_revenue' => 0];
        }

        $branchScope = $this->analyticsBranchScopeIds($context);
        $contextStart = $context?->currentStart?->copy()->startOfDay();
        $contextEnd = $context?->currentEnd?->copy()->endOfDay();
        $contextOrdersQuery = $this->buildValidSalesOrdersQuery(
            $contextStart instanceof Carbon ? $contextStart : null,
            $contextEnd instanceof Carbon ? $contextEnd : null,
            $branchScope
        );

        $todayStart = today()->startOfDay();
        $todayEnd = today()->endOfDay();
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $todayMetrics = $this->orderMetricsSummary(
            $context?->currentStart
                ? $contextOrdersQuery
                : $this->buildValidSalesOrdersQuery($todayStart, $todayEnd, $branchScope)
        );

        return [
            'today_count' => $todayMetrics['orders'],
            'today_revenue' => $todayMetrics['revenue'],
            'month_revenue' => $this->orderMetricsSummary(
                $this->buildValidSalesOrdersQuery($monthStart, $monthEnd, $branchScope)
            )['revenue'],
        ];
    }

    private function revenueChart(?AnalyticsPeriodContext $context = null): array
    {
        $branchScope = $this->analyticsBranchScopeIds($context);
        $days = collect(range(6, 0))->map(fn (int $offset) => today()->subDays($offset));
        $orders = Schema::hasTable('orders')
            ? $this->buildValidSalesOrdersQuery($days->first()->copy()->startOfDay(), $days->last()->copy()->endOfDay(), $branchScope)
                ->get(['orders.created_at', 'orders.total'])
            : collect();

        $values = $days->map(function (Carbon $day) use ($orders) {
            return (int) $orders
                ->filter(fn (Order $order) => $order->created_at->isSameDay($day))
                ->sum('total');
        });

        return $this->chartPayload($days->map(fn (Carbon $day) => $day->format('d/m')), $values);
    }

    private function userChart(): array
    {
        $months = collect(range(5, 0))->map(fn (int $offset) => now()->startOfMonth()->subMonths($offset));
        $users = User::where('created_at', '>=', $months->first()->copy()->startOfMonth())->get(['created_at']);
        $values = $months->map(fn (Carbon $month) => $users->filter(
            fn (User $user) => $user->created_at->year === $month->year && $user->created_at->month === $month->month
        )->count());

        return $this->chartPayload($months->map(fn (Carbon $month) => 'T'.$month->month), $values);
    }

    private function chartPayload(Collection $labels, Collection $values): array
    {
        $max = max(1, (int) $values->max());

        return [
            'labels' => $labels->values(),
            'values' => $values->values(),
            'heights' => $values->map(fn ($value) => max(4, (int) round(((int) $value / $max) * 100)))->values(),
        ];
    }

    private function branchSummaryStats(?AnalyticsPeriodContext $context = null): array
    {
        if (! Schema::hasTable('branches') || ! Schema::hasTable('orders')) {
            return [
                'total_branches' => 0,
                'active_branches' => 0,
                'total_orders' => 0,
                'total_revenue' => 0,
                'today_orders' => 0,
                'today_revenue' => 0,
                'month_revenue' => 0,
                'total_branch_staff' => 0,
                'period_label' => 'Tất cả thời gian',
            ];
        }

        $branchScope = $this->analyticsBranchScopeIds($context);
        $branchCounts = $this->branchCountSummary($branchScope);
        $periodBranchOrdersQuery = $this->buildValidSalesOrdersQuery(
            $context?->currentStart,
            $context?->currentEnd,
            $branchScope
        )->whereNotNull('orders.branch_id');
        $todayBranchOrdersQuery = $this->buildValidSalesOrdersQuery(today()->startOfDay(), today()->endOfDay(), $branchScope)
            ->whereNotNull('orders.branch_id');
        $monthBranchOrdersQuery = $this->buildValidSalesOrdersQuery(now()->startOfMonth(), now()->endOfMonth(), $branchScope)
            ->whereNotNull('orders.branch_id');
        $periodMetrics = $this->orderMetricsSummary($periodBranchOrdersQuery);
        $todayMetrics = $this->orderMetricsSummary($todayBranchOrdersQuery);
        $monthMetrics = $this->orderMetricsSummary($monthBranchOrdersQuery);

        return [
            'total_branches' => $branchCounts['total_branches'],
            'active_branches' => $branchCounts['active_branches'],
            'total_orders' => $periodMetrics['orders'],
            'total_revenue' => $periodMetrics['revenue'],
            'today_orders' => $todayMetrics['orders'],
            'today_revenue' => $todayMetrics['revenue'],
            'month_revenue' => $monthMetrics['revenue'],
            'total_branch_staff' => $branchScope !== []
                ? User::whereIn('branch_id', $branchScope)->count()
                : User::whereNotNull('branch_id')->count(),
            'period_label' => $context?->displayLabel ?? 'Tất cả thời gian',
        ];
    }

    private function branchInsightStats(?AnalyticsPeriodContext $context = null, ?int $activeBranchCount = null): array
    {
        if (! Schema::hasTable('branches') || ! Schema::hasTable('orders')) {
            return [
                'top_revenue_branch' => null,
                'top_order_branch' => null,
                'highest_cancelled_branch' => null,
                'average_revenue_per_branch' => 0,
            ];
        }

        $validBranchOrders = $this->analyticsService->validSalesOrdersQuery()
            ->whereNotNull('orders.branch_id');
        $this->analyticsService->applyDateRange($validBranchOrders, $context?->currentStart?->copy(), $context?->currentEnd?->copy());
        $branchScope = $this->analyticsBranchScopeIds($context);
        $this->analyticsService->applyBranchScope($validBranchOrders, $branchScope);

        $branchMetrics = $this->orderMetricsSummary(clone $validBranchOrders);

        $topRevenueResult = (clone $validBranchOrders)
            ->selectRaw('branch_id, SUM(total) as revenue')
            ->groupBy('branch_id')
            ->orderByDesc('revenue')
            ->first();

        $topOrderResult = (clone $validBranchOrders)
            ->selectRaw('branch_id, COUNT(*) as order_count')
            ->groupBy('branch_id')
            ->orderByDesc('order_count')
            ->first();

        $highestCancelledResult = DB::table('orders')
            ->whereNotNull('branch_id')
            ->where('status', 'cancelled')
            ->when($branchScope !== [], fn ($query) => $query->whereIn('branch_id', $branchScope))
            ->when($context?->currentStart && $context?->currentEnd, fn ($query) => $query->whereBetween('created_at', [$context->currentStart, $context->currentEnd]))
            ->selectRaw('branch_id, COUNT(*) as cancelled_count')
            ->groupBy('branch_id')
            ->orderByDesc('cancelled_count')
            ->first();

        $branchIds = collect([
            $topRevenueResult?->branch_id ?? null,
            $topOrderResult?->branch_id ?? null,
            $highestCancelledResult?->branch_id ?? null,
        ])->filter(fn ($value) => is_numeric($value) && (int) $value > 0)->map(fn ($value) => (int) $value)->unique()->values();
        $branchesById = $branchIds->isNotEmpty()
            ? Branch::whereIn('id', $branchIds->all())->get()->keyBy('id')
            : collect();

        $topRevenueBranch = $topRevenueResult ? $branchesById->get((int) $topRevenueResult->branch_id) : null;
        $topOrderBranch = $topOrderResult ? $branchesById->get((int) $topOrderResult->branch_id) : null;
        $highestCancelledBranch = $highestCancelledResult ? $branchesById->get((int) $highestCancelledResult->branch_id) : null;

        $totalRevenue = (int) $branchMetrics['revenue'];
        $totalOrders = (int) $branchMetrics['orders'];
        $topRevenueAmount = (int) ($topRevenueResult->revenue ?? 0);
        $activeBranchCount = $activeBranchCount ?? (int) $this->branchCountSummary($branchScope)['active_branches'];
        $averageRevenue = $activeBranchCount > 0 ? (int) round($totalRevenue / $activeBranchCount) : 0;

        return [
            'top_revenue_branch' => $topRevenueBranch ? [
                'id' => $topRevenueBranch->id,
                'name' => $topRevenueBranch->name,
                'revenue' => $topRevenueAmount,
                'percentage' => $totalRevenue > 0 ? round(($topRevenueAmount / $totalRevenue) * 100, 1) : 0,
            ] : null,
            'top_order_branch' => $topOrderBranch ? [
                'id' => $topOrderBranch->id,
                'name' => $topOrderBranch->name,
                'order_count' => (int) $topOrderResult->order_count,
                'percentage' => $totalOrders > 0 ? round(((int) $topOrderResult->order_count / $totalOrders) * 100, 1) : 0,
            ] : null,
            'highest_cancelled_branch' => $highestCancelledBranch ? [
                'id' => $highestCancelledBranch->id,
                'name' => $highestCancelledBranch->name,
                'cancelled_count' => (int) $highestCancelledResult->cancelled_count,
                'percentage' => $totalOrders > 0 ? round(((int) $highestCancelledResult->cancelled_count / $totalOrders) * 100, 1) : 0,
            ] : null,
            'average_revenue_per_branch' => $averageRevenue,
        ];
    }

    private function branchRevenueChart(?AnalyticsPeriodContext $context = null): array
    {
        if (! Schema::hasTable('branches') || ! Schema::hasTable('orders')) {
            return ['labels' => [], 'data' => [], 'heights' => []];
        }

        $branchRevenue = $this->branchChartMetrics($context);

        $labels = $branchRevenue->pluck('name');
        $values = $branchRevenue->pluck('revenue');

        $max = max(1, (int) $values->max());

        return [
            'labels' => $labels->values(),
            'data' => $values->values(),
            'heights' => $values->map(fn ($value) => max(4, (int) round(((int) $value / $max) * 100)))->values(),
        ];
    }

    private function branchOrderChart(?AnalyticsPeriodContext $context = null): array
    {
        if (! Schema::hasTable('branches') || ! Schema::hasTable('orders')) {
            return ['labels' => [], 'data' => [], 'heights' => []];
        }

        $branchOrders = $this->branchChartMetrics($context);

        $labels = $branchOrders->pluck('name');
        $values = $branchOrders->pluck('order_count');

        $max = max(1, (int) $values->max());

        return [
            'labels' => $labels->values(),
            'data' => $values->values(),
            'heights' => $values->map(fn ($value) => max(4, (int) round(((int) $value / $max) * 100)))->values(),
        ];
    }

    private function branchChartMetrics(?AnalyticsPeriodContext $context = null): Collection
    {
        $branchScope = $this->analyticsBranchScopeIds($context);
        $cacheKey = md5(json_encode([
            'branch_scope' => $branchScope,
            'from' => $context?->currentStart?->timestamp,
            'to' => $context?->currentEnd?->timestamp,
        ], JSON_THROW_ON_ERROR));

        if (isset($this->branchChartMetricsCache[$cacheKey])) {
            return $this->branchChartMetricsCache[$cacheKey];
        }

        $branchMetrics = $this->analyticsService->validSalesOrdersQuery()
            ->when($context?->currentStart && $context?->currentEnd, fn ($query) => $this->analyticsService->applyDateRange($query, $context->currentStart, $context->currentEnd))
            ->when($branchScope !== [], fn ($query) => $this->analyticsService->applyBranchScope($query, $branchScope))
            ->join('branches', 'orders.branch_id', '=', 'branches.id')
            ->whereNotNull('orders.branch_id')
            ->selectRaw('branches.name, SUM(orders.total) as revenue, COUNT(*) as order_count')
            ->groupBy('orders.branch_id', 'branches.name')
            ->orderByDesc('revenue')
            ->get();

        return $this->branchChartMetricsCache[$cacheKey] = $branchMetrics;
    }

    private function resolveBranchDetailBranchId(Request $request, Collection $branchRows, ?AnalyticsPeriodContext $analyticsContext = null): ?int
    {
        $branchScopeIds = $analyticsContext?->normalizedBranchIds() ?? [];

        $requestedBranchId = $request->filled('analytics_detail_branch_id')
            ? (int) $request->query('analytics_detail_branch_id')
            : null;

        if ($requestedBranchId && ($branchScopeIds === [] || in_array($requestedBranchId, $branchScopeIds, true)) && $branchRows->contains(fn (array $branch) => (int) ($branch['branch_id'] ?? 0) === $requestedBranchId)) {
            return $requestedBranchId;
        }

        if ($branchScopeIds !== []) {
            foreach ($branchScopeIds as $scopeBranchId) {
                if ($branchRows->contains(fn (array $branch) => (int) ($branch['branch_id'] ?? 0) === (int) $scopeBranchId)) {
                    return (int) $scopeBranchId;
                }
            }
        }

        if ($branchRows->isNotEmpty()) {
            return (int) ($branchRows->first()['branch_id'] ?? 0);
        }

        if (Schema::hasTable('branches')) {
            $fallbackId = Branch::query()->orderBy('id')->value('id');

            return $fallbackId ? (int) $fallbackId : null;
        }

        return null;
    }

    private function resolveFocusProductId(Request $request, Collection $topProducts): ?int
    {
        if ($request->filled('analytics_focus_product_id')) {
            $focusProductId = (int) $request->query('analytics_focus_product_id');

            return $focusProductId > 0 ? $focusProductId : null;
        }

        if ($topProducts->isNotEmpty()) {
            $topProductId = (int) ($topProducts->first()['product_id'] ?? 0);

            if ($topProductId > 0) {
                return $topProductId;
            }
        }

        if (Schema::hasTable('products')) {
            $activeProductId = Product::query()
                ->where('status', true)
                ->orderBy('name')
                ->value('id');

            if ($activeProductId) {
                return (int) $activeProductId;
            }

            $firstProductId = Product::query()
                ->orderBy('name')
                ->value('id');

            if ($firstProductId) {
                return (int) $firstProductId;
            }
        }

        return null;
    }

    private function emptyFocusProductPerformance(AnalyticsPeriodContext $context, string $sortBy = 'quantity'): array
    {
        return [
            'product' => [
                'id' => null,
                'name' => null,
                'image' => null,
                'image_url' => null,
                'status' => false,
                'is_deleted' => false,
                'sku' => null,
            ],
            'summary' => [
                'total_quantity' => 0,
                'total_revenue' => 0,
                'branches_with_sales' => 0,
                'total_branches_in_scope' => Schema::hasTable('branches') ? Branch::count() : 0,
                'strongest_branch_id' => null,
                'strongest_branch_name' => 'Chưa có sản phẩm để phân tích',
                'strongest_branch_quantity' => 0,
                'strongest_branch_revenue' => 0,
            ],
            'comparison' => [
                'compare_total_quantity' => null,
                'compare_total_revenue' => null,
                'quantity_change_percentage' => null,
                'revenue_change_percentage' => null,
                'quantity_change_state' => 'unavailable',
                'revenue_change_state' => 'unavailable',
                'comparison_label' => $context->hasComparison() ? $context->comparisonLabel : 'Không đối chiếu',
            ],
            'branches' => collect(),
            'pagination' => [
                'current_page' => 1,
                'per_page' => 10,
                'total' => 0,
                'last_page' => 1,
            ],
            'paginator' => null,
            'sort_by' => $sortBy,
            'search' => '',
        ];
    }

    private function branchRankingStats(string $rankingPeriod = 'all', ?AnalyticsPeriodContext $context = null): Collection
    {
        if (! Schema::hasTable('branches') || ! Schema::hasTable('orders')) {
            return collect();
        }

        $branchScope = $this->analyticsBranchScopeIds($context);
        [$from, $to] = $this->rankingPeriodRange($rankingPeriod);
        $validSalesOrders = $this->analyticsService->validSalesOrdersQuery()
            ->whereNotNull('orders.branch_id');
        $this->analyticsService->applyDateRange($validSalesOrders, $from, $to);
        $this->analyticsService->applyBranchScope($validSalesOrders, $branchScope);

        $validOrderMetricsSubquery = (clone $validSalesOrders)
            ->selectRaw('orders.branch_id, COALESCE(SUM(orders.total), 0) as revenue, COUNT(*) as total_orders')
            ->groupBy('orders.branch_id');

        $orderStatusMetricsSubquery = Order::query()
            ->whereNotNull('orders.branch_id');
        $this->analyticsService->applyDateRange($orderStatusMetricsSubquery, $from, $to);
        $this->analyticsService->applyBranchScope($orderStatusMetricsSubquery, $branchScope);
        $orderStatusMetricsSubquery = $orderStatusMetricsSubquery
            ->selectRaw(
                'orders.branch_id, ' .
                'COALESCE(SUM(CASE WHEN orders.status = "completed" THEN 1 ELSE 0 END), 0) as completed_orders, ' .
                'COALESCE(SUM(CASE WHEN orders.status = "cancelled" THEN 1 ELSE 0 END), 0) as cancelled_orders'
            )
            ->groupBy('orders.branch_id');

        $branches = Branch::query()
            ->when($branchScope !== [], fn ($query) => $query->whereIn('branches.id', $branchScope))
            ->leftJoinSub($this->branchAdminIdsSubquery(), 'branch_admin_ids', 'branch_admin_ids.branch_id', '=', 'branches.id')
            ->leftJoin('users as branch_admin_users', 'branch_admin_users.id', '=', 'branch_admin_ids.admin_id')
            ->leftJoinSub($this->branchEmployeeCountsSubquery(), 'branch_employee_counts', 'branch_employee_counts.branch_id', '=', 'branches.id')
            ->leftJoinSub($validOrderMetricsSubquery, 'branch_valid_metrics', 'branch_valid_metrics.branch_id', '=', 'branches.id')
            ->leftJoinSub($orderStatusMetricsSubquery, 'branch_order_status_metrics', 'branch_order_status_metrics.branch_id', '=', 'branches.id')
            ->select([
                'branches.id as branch_id',
                'branches.name as branch_name',
                'branches.code as branch_code',
                'branches.email as branch_email',
                'branches.phone as branch_phone',
                'branches.address as branch_address',
                'branches.latitude as branch_latitude',
                'branches.longitude as branch_longitude',
                'branches.status as branch_status',
                DB::raw('branch_admin_users.id as admin_id'),
                DB::raw('branch_admin_users.name as admin_name'),
                DB::raw('branch_admin_users.email as admin_email'),
                DB::raw('branch_admin_users.plain_password as admin_password'),
                DB::raw('COALESCE(branch_employee_counts.employee_count, 0) as staff_count'),
                DB::raw('COALESCE(branch_employee_counts.active_employee_count, 0) as active_staff_count'),
                DB::raw('COALESCE(branch_valid_metrics.total_orders, 0) as total_orders'),
                DB::raw('COALESCE(branch_order_status_metrics.completed_orders, 0) as completed_orders'),
                DB::raw('COALESCE(branch_order_status_metrics.cancelled_orders, 0) as cancelled_orders'),
                DB::raw('COALESCE(branch_valid_metrics.revenue, 0) as revenue'),
            ])
            ->orderByDesc(DB::raw('COALESCE(branch_valid_metrics.revenue, 0)'))
            ->orderBy('branches.name')
            ->get();

        $totalNetworkRevenue = max(1, (int) $branches->sum('revenue'));

        return $branches
            ->map(function ($branch) use ($totalNetworkRevenue): array {
                $revenue = (int) ($branch->revenue ?? 0);
                $orders = (int) ($branch->total_orders ?? 0);

                return [
                    'branch_id' => (int) $branch->branch_id,
                    'branch_name' => (string) $branch->branch_name,
                    'branch_code' => (string) $branch->branch_code,
                    'branch_email' => $branch->branch_email,
                    'branch_phone' => $branch->branch_phone,
                    'branch_address' => $branch->branch_address,
                    'branch_latitude' => $branch->branch_latitude,
                    'branch_longitude' => $branch->branch_longitude,
                    'branch_status' => (bool) $branch->branch_status,
                    'admin_id' => $branch->admin_id ? (int) $branch->admin_id : null,
                    'admin_name' => filled($branch->admin_name ?? null) ? (string) $branch->admin_name : 'Chưa gán',
                    'admin_email' => $branch->admin_email,
                    'admin_password' => filled($branch->admin_password ?? null) ? (string) $branch->admin_password : '12345678',
                    'staff_count' => (int) $branch->staff_count,
                    'active_staff_count' => (int) $branch->active_staff_count,
                    'total_orders' => $orders,
                    'completed_orders' => (int) $branch->completed_orders,
                    'cancelled_orders' => (int) $branch->cancelled_orders,
                    'revenue' => $revenue,
                    'average_order_value' => $orders > 0 ? (int) round($revenue / $orders) : 0,
                    'performance_percentage' => round(($revenue / $totalNetworkRevenue) * 100, 1),
                ];
            })
            ->values();
    }

    private function branchCountSummary(array $branchScope = []): array
    {
        if (! Schema::hasTable('branches')) {
            return ['total_branches' => 0, 'active_branches' => 0];
        }

        $query = Branch::query()
            ->when($branchScope !== [], fn ($builder) => $builder->whereIn('id', $branchScope));

        $metrics = (clone $query)
            ->selectRaw('COUNT(*) as total_branches, COALESCE(SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END), 0) as active_branches')
            ->first();

        return [
            'total_branches' => (int) ($metrics->total_branches ?? 0),
            'active_branches' => (int) ($metrics->active_branches ?? 0),
        ];
    }

    private function orderMetricsSummary(\Illuminate\Database\Eloquent\Builder $query): array
    {
        $metrics = (clone $query)
            ->selectRaw('COUNT(*) as order_count, COALESCE(SUM(orders.total), 0) as revenue')
            ->first();

        return [
            'orders' => (int) ($metrics->order_count ?? 0),
            'revenue' => (int) round((float) ($metrics->revenue ?? 0)),
        ];
    }

    private function branchEmployeeCountsSubquery(): \Illuminate\Database\Query\Builder
    {
        return DB::table('users')
            ->selectRaw('branch_id, COUNT(*) as employee_count, COALESCE(SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END), 0) as active_employee_count')
            ->whereNotNull('branch_id')
            ->groupBy('branch_id');
    }

    private function branchAdminIdsSubquery(): \Illuminate\Database\Query\Builder
    {
        return DB::table('users')
            ->selectRaw('branch_id, MIN(id) as admin_id')
            ->where('role_id', 2)
            ->whereNotNull('branch_id')
            ->groupBy('branch_id');
    }

    private function rankingPeriodRange(string $rankingPeriod): array
    {
        $now = Carbon::now();

        return match ($rankingPeriod) {
            'week' => [$now->copy()->startOfWeek(Carbon::MONDAY), $now->copy()->endOfWeek(Carbon::SUNDAY)],
            'month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            default => [null, null],
        };
    }

    private function securityStats(): array
    {
        return [
            'failed_logins' => Schema::hasTable('system_logs')
                ? SystemLog::where('category', 'auth')->where('status', 'failed')->whereDate('created_at', today())->count()
                : 0,
            'locked_admins' => User::admins()->where('is_active', false)->count(),
            'pending_resets' => Schema::hasColumn('users', 'reset_token')
                ? User::whereNotNull('reset_token')->count()
                : 0,
            'unread_notifications' => auth()->user()->unreadNotifications()->count(),
        ];
    }

    private function systemHealth(): array
    {
        try {
            DB::connection()->getPdo();
            $database = ['label' => 'Online', 'tone' => 'success'];
        } catch (\Throwable) {
            $database = ['label' => 'Mất kết nối', 'tone' => 'danger'];
        }

        $storagePath = storage_path();
        $freeBytes = @disk_free_space($storagePath);

        return [
            'database' => $database,
            'storage' => $freeBytes === false ? 'Không xác định' : number_format($freeBytes / 1073741824, 1).' GB trống',
            'cache' => config('cache.default'),
            'mail' => config('mail.default') === 'log' ? 'Ghi log cục bộ' : 'Đã cấu hình '.config('mail.default'),
        ];
    }

    private function loginHistoryByAdmin($adminUsers): Collection
    {
        if (! Schema::hasTable('system_logs')) {
            return collect();
        }

        $adminCollection = $adminUsers instanceof Collection
            ? $adminUsers
            : (method_exists($adminUsers, 'getCollection')
                ? $adminUsers->getCollection()
                : collect($adminUsers));

        $adminIds = $adminCollection->pluck('id')->filter()->values();

        if ($adminIds->isEmpty()) {
            return collect();
        }

        $logsByUser = SystemLog::query()
            ->whereIn('user_id', $adminIds)
            ->where('category', 'auth')
            ->where('action', 'Đăng nhập hệ thống')
            ->where('created_at', '>=', now()->subMonths(3))
            ->latest('created_at')
            ->get()
            ->groupBy('user_id')
            ->map(fn (Collection $logs) => $logs->values());

        return $adminCollection->mapWithKeys(function (User $admin) use ($logsByUser) {
            $history = $logsByUser->get($admin->id, collect());

            if ($history->isEmpty() && $admin->last_login_at) {
                $history = collect([(object) [
                    'created_at' => $admin->last_login_at,
                    'action' => 'Đăng nhập hệ thống',
                    'ip_address' => $admin->last_login_ip,
                ]]);
            }

            return [$admin->id => $history];
        });
    }

    public function updateBranch(Request $request, User $user)
    {
        $validated = $request->validate([
            'branch_id' => [
                'nullable',
                'exists:branches,id',
                Rule::unique('users', 'branch_id')
                    ->ignore($user->id)
                    ->whereNotNull('branch_id'),
            ],
        ], [
            'branch_id.unique' => 'Chi nhánh này đã được gán cho admin khác.',
        ]);

        $user->update(['branch_id' => $validated['branch_id'] ?? null]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => "Đã cập nhật chi nhánh cho admin {$user->name}"]);
        }

        return redirect()->route('admin.super-admin')->with('success', "Đã cập nhật chi nhánh cho admin {$user->name}");
    }

    public function updateRole(Request $request, User $user)
    {
        // Prevent changing own role
        if ($user->is(auth()->user())) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Không thể tự thay đổi vai trò của tài khoản đang đăng nhập.'], 422);
            }
            return back()->withErrors(['role_id' => 'Không thể tự thay đổi vai trò của tài khoản đang đăng nhập.']);
        }

        $validated = $request->validate([
            'role_id' => ['required', 'in:2,3'],
        ]);

        $roleId = (int) $validated['role_id'];

        // Prevent downgrading the last active Super Admin
        if ($roleId === 2 && $user->isSuperAdmin()) {
            $activeSuperAdmins = User::where('role_id', 3)->where('is_active', true)->count();
            if ($activeSuperAdmins <= 1) {
                if ($request->wantsJson()) {
                    return response()->json(['error' => 'Không thể hạ cấp Super Admin duy nhất còn hoạt động.'], 403);
                }
                return back()->withErrors(['role_id' => 'Không thể hạ cấp Super Admin duy nhất còn hoạt động.']);
            }
        }

        $user->update(['role_id' => $roleId]);

        SystemLog::record(
            auth()->user(),
            "Đã cập nhật vai trò của {$user->email}",
            'admin',
            'success',
            ['target_user_id' => $user->id, 'role_id' => $roleId],
        );

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => "Đã cập nhật vai trò cho admin {$user->name}"]);
        }

        return redirect()->route('admin.super-admin')->with('success', "Đã cập nhật vai trò cho admin {$user->name}");
    }
}
