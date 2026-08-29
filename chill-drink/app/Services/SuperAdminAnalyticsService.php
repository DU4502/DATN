<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Support\ProductImage;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SuperAdminAnalyticsService
{
    /**
     * @var array<string, bool>
     */
    private array $tableExistsCache = [];

    /**
     * @var array<string, \App\Models\Product|null>
     */
    private array $productMetadataCache = [];

    /**
     * @var array<string, \Illuminate\Support\Collection<int, \App\Models\Branch>>
     */
    private array $branchMetadataCache = [];

    /**
     * @var array<string, \Illuminate\Support\Collection<int, object>>
     */
    private array $branchProductAggregateCache = [];

    /**
     * @var array<string, \Illuminate\Support\Collection<int, object>>
     */
    private array $productBranchAggregateCache = [];

    /**
     * @var array<string, object>
     */
    private array $branchOrderAggregateCache = [];

    /**
     * Doanh thu chỉ được ghi nhận sau khi đơn đã hoàn tất. Trạng thái thanh toán
     * riêng lẻ không làm doanh thu về sớm khi đơn vẫn đang được xử lý.
     */
    public function validSalesOrdersQuery(): Builder
    {
        return Order::query()
            ->where('orders.status', 'completed');
    }

    public function validSalesOrderItemsQuery(): Builder
    {
        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', 'completed');
    }

    /**
     * @param int|array<int>|null $branchScope
     */
    public function applyBranchScope(Builder $query, int|array|null $branchScope, string $column = 'orders.branch_id'): Builder
    {
        $branchIds = $this->normalizeBranchScopeSelection($branchScope);

        if ($branchIds !== []) {
            $query->whereIn($column, $branchIds);
        }

        return $query;
    }

    public function applyDateRange(
        Builder $query,
        ?CarbonInterface $from,
        ?CarbonInterface $to,
        string $column = 'orders.created_at'
    ): Builder {
        if ($from && $to) {
            $query->whereBetween($column, [$from, $to]);
        }

        return $query;
    }

    public function revenueSummary(Builder $query, string $column = 'orders.total'): int
    {
        return (int) round((float) $query->sum($column));
    }

    public function orderSummary(Builder $query, string $column = 'orders.id'): int
    {
        return (int) $query->distinct()->count($column);
    }

    public function customerSummary(Builder $query, string $userIdColumn = 'orders.user_id'): int
    {
        return (int) $query
            ->whereNotNull($userIdColumn)
            ->distinct()
            ->count($userIdColumn);
    }

    public function itemQuantitySummary(Builder $query, string $quantityColumn = 'order_items.quantity'): int
    {
        return (int) round((float) $query->sum($quantityColumn));
    }

    public function filteredOrderIdsSubquery(?int $categoryId = null, ?int $productId = null): ?Builder
    {
        if ((! $categoryId && ! $productId) || ! $this->hasTableCached('order_items') || ! $this->hasTableCached('products')) {
            return null;
        }

        return OrderItem::query()
            ->select('order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->when($categoryId, fn (Builder $query, int $id) => $query->where('products.category_id', $id))
            ->when($productId, fn (Builder $query, int $id) => $query->where('order_items.product_id', $id));
    }

    public function businessSummary(AnalyticsPeriodContext $context): array
    {
        $branchScopeIds = $this->contextBranchScopeIds($context);

        $currentOrdersQuery = $this->validSalesOrdersQuery();
        $this->applyDateRange($currentOrdersQuery, $context->currentStart, $context->currentEnd);
        $this->applyBranchScope($currentOrdersQuery, $branchScopeIds);

        $currentOrderMetrics = $this->aggregateOrderMetrics($currentOrdersQuery);

        $compareOrderMetrics = null;
        if ($context->hasComparison()) {
            $compareOrdersQuery = $this->validSalesOrdersQuery();
            $this->applyDateRange($compareOrdersQuery, $context->compareStart, $context->compareEnd);
            $this->applyBranchScope($compareOrdersQuery, $branchScopeIds);
            $compareOrderMetrics = $this->aggregateOrderMetrics($compareOrdersQuery);
        }

        $currentItemMetrics = $this->aggregateItemMetrics($context->currentStart, $context->currentEnd, $branchScopeIds);
        $compareItemMetrics = $context->hasComparison()
            ? $this->aggregateItemMetrics($context->compareStart, $context->compareEnd, $branchScopeIds)
            : null;

        $comparisonLabel = $context->hasComparison() ? $context->comparisonLabel : 'Không đối chiếu';

        $revenue = $this->composeMetric(
            (int) $currentOrderMetrics['revenue'],
            $compareOrderMetrics !== null ? (int) $compareOrderMetrics['revenue'] : null,
            $comparisonLabel
        );
        $orders = $this->composeMetric(
            (int) $currentOrderMetrics['orders'],
            $compareOrderMetrics !== null ? (int) $compareOrderMetrics['orders'] : null,
            $comparisonLabel
        );
        $customers = $this->composeMetric(
            (int) $currentOrderMetrics['customers'],
            $compareOrderMetrics !== null ? (int) $compareOrderMetrics['customers'] : null,
            $comparisonLabel
        );
        $itemsSold = $this->composeMetric(
            (int) $currentItemMetrics,
            $compareItemMetrics !== null ? (int) $compareItemMetrics : null,
            $comparisonLabel
        );

        $currentAverageOrderValue = $orders['current_value'] > 0
            ? (int) round($revenue['current_value'] / $orders['current_value'])
            : 0;
        $compareAverageOrderValue = $orders['compare_value'] !== null && (int) $orders['compare_value'] > 0
            ? (int) round(((int) $compareOrderMetrics['revenue']) / (int) $orders['compare_value'])
            : (($compareOrderMetrics !== null && (int) $compareOrderMetrics['revenue'] === 0 && (int) $orders['compare_value'] === 0) ? 0 : null);

        $averageOrderValue = $this->composeMetric(
            $currentAverageOrderValue,
            $compareAverageOrderValue,
            $comparisonLabel
        );

        return [
            'period_label' => $context->displayLabel,
            'comparison_label' => $comparisonLabel,
            'revenue' => $revenue,
            'orders' => $orders,
            'customers' => $customers,
            'items_sold' => $itemsSold,
            'average_order_value' => $averageOrderValue,
            'guest_order_count' => (int) $currentOrderMetrics['guest_orders'],
        ];
    }

    public function topProducts(AnalyticsPeriodContext $context, string $sortBy = 'quantity', int $limit = 5, int|array|null $branchScopeOverride = null): Collection
    {
        $sortBy = in_array($sortBy, ['quantity', 'revenue'], true) ? $sortBy : 'quantity';
        $revenueExpression = 'COALESCE(order_items.total_price, order_items.quantity * order_items.unit_price)';
        $branchScopeIds = $branchScopeOverride !== null
            ? $this->normalizeBranchScopeSelection($branchScopeOverride)
            : $this->contextBranchScopeIds($context);
        $currentOrderItemsQuery = $this->validSalesOrderItemsQuery();
        $this->applyDateRange($currentOrderItemsQuery, $context->currentStart, $context->currentEnd);
        $this->applyBranchScope($currentOrderItemsQuery, $branchScopeIds);

        $topProducts = $currentOrderItemsQuery
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->selectRaw(
                'order_items.product_id, products.name as product_name, products.image as product_image, '
                ."SUM(order_items.quantity) as total_quantity, SUM({$revenueExpression}) as total_revenue"
            )
            ->groupBy('order_items.product_id', 'products.name', 'products.image')
            ->orderByDesc($sortBy === 'revenue' ? 'total_revenue' : 'total_quantity')
            ->orderByDesc($sortBy === 'revenue' ? 'total_quantity' : 'total_revenue')
            ->orderBy('order_items.product_id')
            ->limit(max(1, $limit))
            ->get();

        if ($topProducts->isEmpty()) {
            return collect();
        }

        $branchRows = $this->validSalesOrderItemsQuery();
        $this->applyDateRange($branchRows, $context->currentStart, $context->currentEnd);
        $this->applyBranchScope($branchRows, $branchScopeIds);

        $branchRows = $branchRows
            ->leftJoin('branches', 'branches.id', '=', 'orders.branch_id')
            ->whereIn('order_items.product_id', $topProducts->pluck('product_id')->all())
            ->selectRaw(
                'order_items.product_id, orders.branch_id, branches.name as branch_name, '
                ."SUM(order_items.quantity) as branch_quantity, SUM({$revenueExpression}) as branch_revenue"
            )
            ->groupBy('order_items.product_id', 'orders.branch_id', 'branches.name')
            ->get()
            ->groupBy('product_id');

        return $topProducts
            ->values()
            ->map(function (object $row, int $index) use ($branchRows, $sortBy): array {
                $branchCandidates = $branchRows->get((int) $row->product_id, collect());
                $strongestBranch = $branchCandidates->sort(function (object $left, object $right) use ($sortBy): int {
                    $leftPrimary = $sortBy === 'revenue' ? (int) $left->branch_revenue : (int) $left->branch_quantity;
                    $rightPrimary = $sortBy === 'revenue' ? (int) $right->branch_revenue : (int) $right->branch_quantity;

                    if ($leftPrimary !== $rightPrimary) {
                        return $rightPrimary <=> $leftPrimary;
                    }

                    $leftSecondary = $sortBy === 'revenue' ? (int) $left->branch_quantity : (int) $left->branch_revenue;
                    $rightSecondary = $sortBy === 'revenue' ? (int) $right->branch_quantity : (int) $right->branch_revenue;

                    if ($leftSecondary !== $rightSecondary) {
                        return $rightSecondary <=> $leftSecondary;
                    }

                    return (int) $left->branch_id <=> (int) $right->branch_id;
                })->first();

                $productId = (int) $row->product_id;
                $productName = filled($row->product_name ?? null)
                    ? (string) $row->product_name
                    : 'Sản phẩm #'.$productId;

                return [
                    'rank' => $index + 1,
                    'product_id' => $productId,
                    'product_name' => $productName,
                    'product_image' => $row->product_image,
                    'product_image_url' => ProductImage::resolve(
                        filled($row->product_image ?? null) ? (string) $row->product_image : null,
                        null,
                        $productId,
                        240
                    ),
                    'total_quantity' => (int) $row->total_quantity,
                    'total_revenue' => (int) $row->total_revenue,
                    'strongest_branch_id' => $strongestBranch ? (int) $strongestBranch->branch_id : null,
                    'strongest_branch_name' => filled($strongestBranch?->branch_name ?? null)
                        ? (string) $strongestBranch->branch_name
                        : 'Chưa xác định chi nhánh',
                    'strongest_branch_quantity' => (int) ($strongestBranch->branch_quantity ?? 0),
                    'strongest_branch_revenue' => (int) ($strongestBranch->branch_revenue ?? 0),
                    'sort_by' => $sortBy,
                ];
            })
            ->values();
    }

    public function focusProducts(string $search = '', int $limit = 8): Collection
    {
        if (! $this->hasTableCached('products')) {
            return collect();
        }

        $search = trim($search);

        $query = Product::query()
            ->withTrashed()
            ->select(['id', 'name', 'sku', 'image', 'status', 'deleted_at'])
            ->orderByDesc('status')
            ->orderBy('name')
            ->limit(max(1, $limit));

        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function (Builder $builder) use ($like): void {
                $builder->where('name', 'like', $like)
                    ->orWhere('sku', 'like', $like);
            });
        }

        return $query
            ->get()
            ->map(function (Product $product): array {
                $isDeleted = $product->deleted_at !== null;
                $name = filled($product->name ?? null)
                    ? (string) $product->name
                    : 'Sản phẩm #'.$product->id;

                return [
                    'id' => (int) $product->id,
                    'name' => $name,
                    'sku' => filled($product->sku ?? null) ? (string) $product->sku : null,
                    'image' => $product->image,
                    'image_url' => ProductImage::resolve(
                        filled($product->image ?? null) ? (string) $product->image : null,
                        null,
                        (int) $product->id,
                        120
                    ),
                    'status' => (bool) $product->status,
                    'status_label' => $isDeleted
                        ? 'Đã xóa'
                        : ((bool) $product->status ? 'Đang bán' : 'Ngừng bán'),
                    'is_deleted' => $isDeleted,
                ];
            })
            ->values();
    }

    public function productBranchPerformance(AnalyticsPeriodContext $context, int $productId, array $options = []): array
    {
        $sortBy = in_array(Arr::get($options, 'sort_by'), ['quantity', 'revenue'], true)
            ? (string) Arr::get($options, 'sort_by')
            : 'quantity';
        $search = trim((string) Arr::get($options, 'search', ''));
        $page = max(1, (int) Arr::get($options, 'page', 1));
        $perPage = 10;
        $scopeBranchIds = $this->contextBranchScopeIds($context, Arr::get($options, 'analytics_branch_ids', Arr::get($options, 'branch_ids')));
        $product = $this->loadProductMetadata($productId);

        $productName = filled($product?->name ?? null)
            ? (string) $product?->name
            : 'Sản phẩm #'.$productId.' đã xóa';
        $allBranches = $this->loadBranchMetadata($scopeBranchIds);
        $currentBranchTotals = $this->productBranchAggregateRows($context->currentStart, $context->currentEnd, $productId, $scopeBranchIds)
            ->keyBy(fn (object $row) => (int) $row->branch_id);
        $compareBranchTotals = $context->hasComparison()
            ? $this->productBranchAggregateRows($context->compareStart, $context->compareEnd, $productId, $scopeBranchIds)
                ->keyBy(fn (object $row) => (int) $row->branch_id)
            : collect();

        $summaryTotalQuantity = (int) $currentBranchTotals->sum(fn (object $row) => (int) ($row->total_quantity ?? 0));
        $summaryTotalRevenue = (int) $currentBranchTotals->sum(fn (object $row) => (int) ($row->total_revenue ?? 0));
        $summaryBranchesWithSales = $currentBranchTotals->count();
        $summaryCompareTotalQuantity = $context->hasComparison()
            ? (int) $compareBranchTotals->sum(fn (object $row) => (int) ($row->total_quantity ?? 0))
            : null;
        $summaryCompareTotalRevenue = $context->hasComparison()
            ? (int) $compareBranchTotals->sum(fn (object $row) => (int) ($row->total_revenue ?? 0))
            : null;

        $allBranchRows = $allBranches->map(function (Branch $branch) use ($currentBranchTotals, $compareBranchTotals, $context, $summaryTotalQuantity, $summaryTotalRevenue, $sortBy): array {
            $currentRow = $currentBranchTotals->get((int) $branch->id);
            $compareRow = $compareBranchTotals->get((int) $branch->id);
            $currentQuantity = $currentRow !== null ? (int) ($currentRow->total_quantity ?? 0) : 0;
            $currentRevenue = $currentRow !== null ? (int) ($currentRow->total_revenue ?? 0) : 0;
            $quantityComparison = $this->composeMetric(
                $currentQuantity,
                $context->hasComparison() ? ($compareRow !== null ? (int) ($compareRow->total_quantity ?? 0) : 0) : null,
                $context->comparisonLabel
            );
            $revenueComparison = $this->composeMetric(
                $currentRevenue,
                $context->hasComparison() ? ($compareRow !== null ? (int) ($compareRow->total_revenue ?? 0) : 0) : null,
                $context->comparisonLabel
            );

            return [
                'branch_id' => (int) $branch->id,
                'branch_name' => (string) $branch->name,
                'branch_code' => filled($branch->code ?? null) ? (string) $branch->code : null,
                'branch_status' => (bool) $branch->status,
                'total_quantity' => $currentQuantity,
                'total_revenue' => $currentRevenue,
                'quantity_share_percentage' => $summaryTotalQuantity > 0
                    ? round(($currentQuantity / $summaryTotalQuantity) * 100, 1)
                    : 0.0,
                'revenue_share_percentage' => $summaryTotalRevenue > 0
                    ? round(($currentRevenue / $summaryTotalRevenue) * 100, 1)
                    : 0.0,
                'compare_quantity' => $quantityComparison['compare_value'],
                'compare_revenue' => $revenueComparison['compare_value'],
                'quantity_change_percentage' => $quantityComparison['percentage_change'],
                'revenue_change_percentage' => $revenueComparison['percentage_change'],
                'quantity_change_state' => $quantityComparison['change_state'],
                'revenue_change_state' => $revenueComparison['change_state'],
                'sort_value' => $sortBy === 'revenue' ? $currentRevenue : $currentQuantity,
            ];
        })->values();

        $sortedAllBranchRows = $this->sortProductBranchRows($allBranchRows, $sortBy);
        $strongestBranchRow = $sortedAllBranchRows->first();

        $filteredBranchRows = $search === ''
            ? $sortedAllBranchRows
            : $sortedAllBranchRows->filter(function (array $row) use ($search): bool {
                $needle = mb_strtolower($search);
                $branchName = mb_strtolower((string) ($row['branch_name'] ?? ''));
                $branchCode = mb_strtolower((string) ($row['branch_code'] ?? ''));

                return str_contains($branchName, $needle) || str_contains($branchCode, $needle);
            })->values();

        $paginatedBranchRows = $filteredBranchRows
            ->slice(($page - 1) * $perPage, $perPage)
            ->values()
            ->map(function (array $row, int $index): array {
                $row['rank'] = $index + 1;

                return $row;
            });

        $paginator = new LengthAwarePaginator(
            $paginatedBranchRows,
            $filteredBranchRows->count(),
            $perPage,
            $page,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'pageName' => 'analytics_focus_branch_page',
            ]
        );
        $paginator->withQueryString();

        $branchRows = $paginator->getCollection()
            ->values()
            ->map(function (array $row): array {
                return [
                    'rank' => (int) $row['rank'],
                    'branch_id' => (int) $row['branch_id'],
                    'branch_name' => (string) $row['branch_name'],
                    'branch_code' => $row['branch_code'],
                    'branch_status' => (bool) $row['branch_status'],
                    'total_quantity' => (int) $row['total_quantity'],
                    'total_revenue' => (int) $row['total_revenue'],
                    'quantity_share_percentage' => (float) $row['quantity_share_percentage'],
                    'revenue_share_percentage' => (float) $row['revenue_share_percentage'],
                    'compare_quantity' => $row['compare_quantity'],
                    'compare_revenue' => $row['compare_revenue'],
                    'quantity_change_percentage' => $row['quantity_change_percentage'],
                    'revenue_change_percentage' => $row['revenue_change_percentage'],
                    'quantity_change_state' => (string) $row['quantity_change_state'],
                    'revenue_change_state' => (string) $row['revenue_change_state'],
                    'sort_value' => $row['sort_value'],
                ];
            });

        $summaryQuantityComparison = $this->composeMetric($summaryTotalQuantity, $summaryCompareTotalQuantity, $context->comparisonLabel);
        $summaryRevenueComparison = $this->composeMetric($summaryTotalRevenue, $summaryCompareTotalRevenue, $context->comparisonLabel);
        $strongestBranch = $strongestBranchRow ? [
            'id' => (int) $strongestBranchRow['branch_id'],
            'name' => (string) $strongestBranchRow['branch_name'],
            'code' => $strongestBranchRow['branch_code'],
            'quantity' => (int) $strongestBranchRow['total_quantity'],
            'revenue' => (int) $strongestBranchRow['total_revenue'],
        ] : null;

        return [
            'product' => [
                'id' => (int) $productId,
                'name' => $productName,
                'image' => $product?->image,
                'image_url' => ProductImage::resolve(
                    filled($product?->image ?? null) ? (string) $product?->image : null,
                    null,
                    $productId,
                    220
                ),
                'status' => $product ? (bool) $product->status : false,
                'is_deleted' => $product ? $product->deleted_at !== null : true,
                'sku' => filled($product?->sku ?? null) ? (string) $product?->sku : null,
            ],
            'summary' => [
                'total_quantity' => $summaryTotalQuantity,
                'total_revenue' => $summaryTotalRevenue,
                'branches_with_sales' => $summaryBranchesWithSales,
                'total_branches_in_scope' => $scopeBranchIds !== [] ? count($scopeBranchIds) : $allBranches->count(),
                'strongest_branch_id' => $strongestBranch['id'] ?? null,
                'strongest_branch_name' => $strongestBranch['name'] ?? 'Chưa xác định chi nhánh',
                'strongest_branch_quantity' => $strongestBranch['quantity'] ?? 0,
                'strongest_branch_revenue' => $strongestBranch['revenue'] ?? 0,
            ],
            'comparison' => [
                'compare_total_quantity' => $summaryCompareTotalQuantity,
                'compare_total_revenue' => $summaryCompareTotalRevenue,
                'quantity_change_percentage' => $summaryQuantityComparison['percentage_change'],
                'revenue_change_percentage' => $summaryRevenueComparison['percentage_change'],
                'quantity_change_state' => $summaryQuantityComparison['change_state'],
                'revenue_change_state' => $summaryRevenueComparison['change_state'],
                'comparison_label' => $context->hasComparison() ? $context->comparisonLabel : 'Không đối chiếu',
            ],
            'branches' => $branchRows,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
            'paginator' => $paginator,
            'sort_by' => $sortBy,
            'search' => $search,
        ];
    }

    public function branchComparison(AnalyticsPeriodContext $context, array $options = []): array
    {
        $legacyRankingPeriod = in_array(Arr::get($options, 'ranking_period'), ['all', 'week', 'month', 'year'], true)
            ? (string) Arr::get($options, 'ranking_period')
            : 'all';
        $branchPeriod = in_array(Arr::get($options, 'branch_period'), ['day', 'week', 'month', 'year', 'range'], true)
            ? (string) Arr::get($options, 'branch_period')
            : null;
        $branchStartDate = trim((string) Arr::get($options, 'branch_start_date', ''));
        $branchEndDate = trim((string) Arr::get($options, 'branch_end_date', ''));
        $search = trim((string) Arr::get($options, 'search', ''));
        $sort = in_array(Arr::get($options, 'sort'), ['revenue', 'orders', 'average_order_value', 'items_sold', 'growth', 'cancellation_rate', 'name'], true)
            ? (string) Arr::get($options, 'sort')
            : 'revenue';
        $direction = strtolower((string) Arr::get($options, 'direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $performance = in_array(Arr::get($options, 'performance'), ['all', 'increased', 'decreased', 'unchanged', 'new_activity', 'no_orders'], true)
            ? (string) Arr::get($options, 'performance')
            : 'all';
        $perPage = max(1, min(50, (int) Arr::get($options, 'per_page', 5)));
        $page = max(1, (int) Arr::get($options, 'page', 1));
        $scopeBranchIds = $this->contextBranchScopeIds($context, Arr::get($options, 'analytics_branch_ids', Arr::get($options, 'branch_ids')));

        [$currentStart, $currentEnd, $compareStart, $compareEnd, $periodLabel, $comparisonLabel] = $branchPeriod !== null
            ? $this->resolveOverviewBranchPeriod($context, $branchPeriod, $branchStartDate, $branchEndDate)
            : $this->resolveBranchComparisonPeriod($context, $legacyRankingPeriod);

        if (! $context->hasComparison() && $sort === 'growth') {
            $sort = 'revenue';
        }

        $query = Branch::query()
            ->select([
                'branches.id as branch_id',
                'branches.name as branch_name',
                'branches.code as branch_code',
                'branches.status as branch_status',
            ])
            ->leftJoinSub($this->branchEmployeeCountsSubquery(), 'branch_employee_counts', 'branch_employee_counts.branch_id', '=', 'branches.id')
            ->leftJoinSub($this->branchAdminIdsSubquery(), 'branch_admin_ids', 'branch_admin_ids.branch_id', '=', 'branches.id')
            ->leftJoin('users as branch_admin_users', 'branch_admin_users.id', '=', 'branch_admin_ids.admin_id')
            ->leftJoinSub($this->branchComparisonMetricsSubquery($currentStart, $currentEnd, $scopeBranchIds), 'current_branch_metrics', 'current_branch_metrics.branch_id', '=', 'branches.id')
            ->leftJoinSub($this->branchProductSummarySubquery($currentStart, $currentEnd, $scopeBranchIds), 'current_product_summary', 'current_product_summary.branch_id', '=', 'branches.id')
            ->selectRaw('COALESCE(branch_employee_counts.employee_count, 0) as employee_count')
            ->selectRaw('COALESCE(branch_employee_counts.active_employee_count, 0) as active_employee_count')
            ->selectRaw('branch_admin_users.id as admin_id')
            ->selectRaw('branch_admin_users.name as admin_name')
            ->selectRaw('branch_admin_users.email as admin_email')
            ->selectRaw('COALESCE(current_branch_metrics.revenue, 0) as revenue')
            ->selectRaw('COALESCE(current_branch_metrics.valid_order_count, 0) as valid_order_count')
            ->selectRaw('COALESCE(current_branch_metrics.unique_customer_count, 0) as unique_customer_count')
            ->selectRaw('COALESCE(current_branch_metrics.total_created_order_count, 0) as total_created_order_count')
            ->selectRaw('COALESCE(current_branch_metrics.completed_order_count, 0) as completed_order_count')
            ->selectRaw('COALESCE(current_branch_metrics.cancelled_order_count, 0) as cancelled_order_count')
            ->selectRaw('COALESCE(current_product_summary.items_sold, 0) as items_sold')
            ->selectRaw('CASE WHEN COALESCE(current_branch_metrics.valid_order_count, 0) > 0 THEN ROUND(COALESCE(current_branch_metrics.revenue, 0) / COALESCE(current_branch_metrics.valid_order_count, 1)) ELSE 0 END as average_order_value')
            ->selectRaw('CASE WHEN COALESCE(current_branch_metrics.total_created_order_count, 0) > 0 THEN ROUND((COALESCE(current_branch_metrics.cancelled_order_count, 0) / COALESCE(current_branch_metrics.total_created_order_count, 1)) * 100, 1) ELSE 0 END as cancellation_rate')
            ->selectRaw('current_product_summary.top_product_id')
            ->selectRaw('current_product_summary.top_product_name')
            ->selectRaw('COALESCE(current_product_summary.top_product_quantity, 0) as top_product_quantity')
            ->selectRaw('COALESCE(current_product_summary.top_product_revenue, 0) as top_product_revenue')
            ->when($context->hasComparison(), function (Builder $builder) use ($compareStart, $compareEnd, $scopeBranchIds): void {
                $builder
                    ->leftJoinSub($this->branchComparisonMetricsSubquery($compareStart, $compareEnd, $scopeBranchIds), 'compare_branch_metrics', 'compare_branch_metrics.branch_id', '=', 'branches.id')
                    ->leftJoinSub($this->branchItemMetricsSubquery($compareStart, $compareEnd, $scopeBranchIds), 'compare_item_metrics', 'compare_item_metrics.branch_id', '=', 'branches.id')
                    ->selectRaw('COALESCE(compare_branch_metrics.revenue, 0) as compare_revenue')
                    ->selectRaw('COALESCE(compare_branch_metrics.valid_order_count, 0) as compare_order_count')
                    ->selectRaw('COALESCE(compare_item_metrics.items_sold, 0) as compare_items_sold')
                    ->selectRaw($this->comparisonPercentageSql('current_branch_metrics.revenue', 'compare_branch_metrics.revenue'). ' as revenue_change_percentage')
                    ->selectRaw($this->comparisonPercentageSql('current_branch_metrics.valid_order_count', 'compare_branch_metrics.valid_order_count'). ' as order_change_percentage')
                    ->selectRaw($this->comparisonPercentageSql('current_product_summary.items_sold', 'compare_item_metrics.items_sold'). ' as items_change_percentage')
                    ->selectRaw($this->comparisonStateSql('current_branch_metrics.revenue', 'compare_branch_metrics.revenue'). ' as change_state');
            })
            ->when(! $context->hasComparison(), function (Builder $builder): void {
                $builder
                    ->selectRaw('NULL as compare_revenue')
                    ->selectRaw('NULL as compare_order_count')
                    ->selectRaw('NULL as compare_items_sold')
                    ->selectRaw('NULL as revenue_change_percentage')
                    ->selectRaw('NULL as order_change_percentage')
                    ->selectRaw('NULL as items_change_percentage')
                    ->selectRaw("'unavailable' as change_state");
            })
            ->when($scopeBranchIds !== [], fn (Builder $builder) => $builder->whereIn('branches.id', $scopeBranchIds))
            ->when($search !== '', function (Builder $builder) use ($search): void {
                $like = '%'.$search.'%';
                $builder->where(function (Builder $query) use ($like): void {
                    $query->where('branches.name', 'like', $like)
                        ->orWhere('branches.code', 'like', $like)
                        ->orWhere('branch_admin_users.name', 'like', $like)
                        ->orWhere('branch_admin_users.email', 'like', $like);
                });
            })
            ->when($performance !== 'all', fn (Builder $builder) => $this->applyBranchPerformanceFilter($builder, $performance, $context->hasComparison()));

        $sortMap = $this->branchComparisonSortMap();
        $sortColumn = $sortMap[$sort] ?? $sortMap['revenue'];

        $query->orderByRaw($sortColumn.' '.$direction);
        if ($sort === 'revenue') {
            $query->orderByRaw('COALESCE(current_branch_metrics.valid_order_count, 0) '.$direction);
        }
        $query->orderBy('branches.id');

        $rows = $query->get()->values()->map(function (object $row) use ($sort, $periodLabel, $comparisonLabel, $legacyRankingPeriod): array {
            $currentRevenue = (int) $row->revenue;
            $compareRevenue = $row->compare_revenue !== null ? (int) $row->compare_revenue : null;
            $currentOrders = (int) $row->valid_order_count;
            $compareOrders = $row->compare_order_count !== null ? (int) $row->compare_order_count : null;
            $currentItems = (int) $row->items_sold;
            $compareItems = $row->compare_items_sold !== null ? (int) $row->compare_items_sold : null;
            $averageOrderValue = $currentOrders > 0 ? (int) round($currentRevenue / $currentOrders) : 0;
            $cancellationRate = ((int) $row->total_created_order_count) > 0
                ? round((((int) $row->cancelled_order_count) / (int) $row->total_created_order_count) * 100, 1)
                : 0.0;
            $sortValue = match ($sort) {
                'orders' => $currentOrders,
                'average_order_value' => $averageOrderValue,
                'items_sold' => $currentItems,
                'growth' => (float) ($row->revenue_change_percentage ?? 0),
                'cancellation_rate' => $cancellationRate,
                'name' => (string) $row->branch_name,
                default => $currentRevenue,
            };

            return [
                'branch_id' => (int) $row->branch_id,
                'branch_name' => (string) $row->branch_name,
                'branch_code' => (string) $row->branch_code,
                'branch_status' => (bool) $row->branch_status,
                'employee_count' => (int) $row->employee_count,
                'active_employee_count' => (int) $row->active_employee_count,
                'admin_id' => $row->admin_id !== null ? (int) $row->admin_id : null,
                'admin_name' => filled($row->admin_name ?? null) ? (string) $row->admin_name : null,
                'admin_email' => filled($row->admin_email ?? null) ? (string) $row->admin_email : null,
                'revenue' => $currentRevenue,
                'valid_order_count' => $currentOrders,
                'completed_order_count' => (int) $row->completed_order_count,
                'cancelled_order_count' => (int) $row->cancelled_order_count,
                'total_created_order_count' => (int) $row->total_created_order_count,
                'unique_customer_count' => (int) $row->unique_customer_count,
                'items_sold' => $currentItems,
                'average_order_value' => $averageOrderValue,
                'cancellation_rate' => $cancellationRate,
                'compare_revenue' => $compareRevenue,
                'compare_order_count' => $compareOrders,
                'compare_items_sold' => $compareItems,
                'revenue_change_percentage' => $row->revenue_change_percentage !== null ? (float) $row->revenue_change_percentage : null,
                'order_change_percentage' => $row->order_change_percentage !== null ? (float) $row->order_change_percentage : null,
                'items_change_percentage' => $row->items_change_percentage !== null ? (float) $row->items_change_percentage : null,
                'change_state' => (string) $row->change_state,
                'top_product_id' => $row->top_product_id !== null ? (int) $row->top_product_id : null,
                'top_product_name' => filled($row->top_product_name ?? null) ? (string) $row->top_product_name : 'Chưa có dữ liệu',
                'top_product_quantity' => (int) $row->top_product_quantity,
                'top_product_revenue' => (int) $row->top_product_revenue,
                'sort_value' => $sortValue,
                'period_label' => $periodLabel,
                'comparison_label' => $comparisonLabel,
                'ranking_period' => $legacyRankingPeriod,
            ];
        });

        $total = $rows->count();
        $pageItems = $rows
            ->forPage($page, $perPage)
            ->values()
            ->map(function (array $row, int $index) use ($page, $perPage): array {
                $row['rank'] = (($page - 1) * $perPage) + $index + 1;

                return $row;
            });

        $paginator = new LengthAwarePaginator(
            $pageItems,
            $total,
            $perPage,
            $page,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'pageName' => 'branch_page',
            ]
        );
        $paginator->withQueryString();

        return [
            'paginator' => $paginator,
            'period_label' => $periodLabel,
            'comparison_label' => $comparisonLabel,
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
            'performance' => $performance,
            'per_page' => $perPage,
            'page' => $page,
            'ranking_period' => $legacyRankingPeriod,
            'branch_period' => $branchPeriod,
            'branch_start_date' => $branchStartDate,
            'branch_end_date' => $branchEndDate,
        ];
    }

    public function branchTimeComparison(AnalyticsPeriodContext $context, array $options = []): array
    {
        if (! Schema::hasTable('branches') || ! Schema::hasTable('orders')) {
            return $this->emptyBranchTimeComparison($context, $options);
        }

        $indicator = in_array(Arr::get($options, 'indicator', 'both'), ['both', 'revenue', 'orders'], true)
            ? (string) Arr::get($options, 'indicator', 'both')
            : 'both';

        $scopeBranchIds = $this->contextBranchScopeIds(
            $context,
            Arr::get($options, 'analytics_branch_ids', Arr::get($options, 'branch_ids'))
        );

        $periodSetup = $this->resolveBranchTimeComparisonSetup($context, $options);
        $periods = $periodSetup['periods'];

        if ($periods->isEmpty()) {
            return $this->emptyBranchTimeComparison($context, $options);
        }

        $periodKeys = $periods->pluck('key')->values()->all();
        $periodLookup = collect($periodKeys)->flip()->all();
        $periodStart = $periods->last()['start'];
        $periodEnd = $periods->first()['end'];
        $latestPeriod = $periods->first();
        $previousPeriod = $periods->get(1);

        $ordersQuery = $this->validSalesOrdersQuery()
            ->whereNotNull('orders.branch_id');
        $this->applyDateRange($ordersQuery, $periodStart, $periodEnd);
        $this->applyBranchScope($ordersQuery, $scopeBranchIds);

        $orders = $ordersQuery->get(['orders.id', 'orders.branch_id', 'orders.total', 'orders.created_at']);
        $ordersByBranch = $orders->groupBy(static fn (object $order): int => (int) ($order->branch_id ?? 0));
        $branches = $this->loadBranchMetadata($scopeBranchIds);

        if ($branches->isEmpty()) {
            return $this->emptyBranchTimeComparison($context, $options);
        }

        $branchBuckets = [];
        foreach ($branches as $branch) {
            $branchId = (int) $branch->id;
            $branchBuckets[$branchId] = [
                'branch_id' => $branchId,
                'branch_code' => (string) ($branch->code ?? ''),
                'branch_name' => (string) ($branch->name ?? ''),
                'periods' => array_fill_keys($periodKeys, ['revenue' => 0, 'valid_order_count' => 0]),
            ];
        }

        $periodTotals = array_fill_keys($periodKeys, ['revenue' => 0, 'valid_order_count' => 0]);

        foreach ($orders as $order) {
            $branchId = (int) ($order->branch_id ?? 0);
            if ($branchId <= 0 || ! isset($branchBuckets[$branchId])) {
                continue;
            }

            $createdAt = $order->created_at instanceof CarbonInterface
                ? CarbonImmutable::instance($order->created_at)
                : CarbonImmutable::parse((string) $order->created_at, $context->timezone);

            $periodKey = $this->branchTimeComparisonPeriodKey($createdAt, $periodSetup['group_type']);
            if (! isset($periodLookup[$periodKey])) {
                continue;
            }

            $revenue = (int) round((float) ($order->total ?? 0));

            $branchBuckets[$branchId]['periods'][$periodKey]['revenue'] += $revenue;
            $branchBuckets[$branchId]['periods'][$periodKey]['valid_order_count'] += 1;
            $periodTotals[$periodKey]['revenue'] += $revenue;
            $periodTotals[$periodKey]['valid_order_count'] += 1;
        }

        $branchRows = collect(array_values($branchBuckets))
            ->map(function (array $branch) use ($periods, $latestPeriod, $previousPeriod): array {
                $totalRevenue = 0;
                $totalOrders = 0;

                foreach ($periods as $period) {
                    $bucket = $branch['periods'][$period['key']] ?? ['revenue' => 0, 'valid_order_count' => 0];
                    $totalRevenue += (int) ($bucket['revenue'] ?? 0);
                    $totalOrders += (int) ($bucket['valid_order_count'] ?? 0);
                }

                $latestBucket = $latestPeriod ? ($branch['periods'][$latestPeriod['key']] ?? ['revenue' => 0, 'valid_order_count' => 0]) : ['revenue' => 0, 'valid_order_count' => 0];
                $previousBucket = $previousPeriod ? ($branch['periods'][$previousPeriod['key']] ?? null) : null;

                return [
                    'branch_id' => $branch['branch_id'],
                    'branch_code' => $branch['branch_code'],
                    'branch_name' => $branch['branch_name'],
                    'periods' => $branch['periods'],
                    'total_revenue' => $totalRevenue,
                    'total_valid_orders' => $totalOrders,
                    'latest_revenue_change' => $previousBucket !== null
                        ? $this->comparisonSnapshot(
                            (int) ($latestBucket['revenue'] ?? 0),
                            (int) ($previousBucket['revenue'] ?? 0),
                        )
                        : $this->comparisonSnapshot((int) ($latestBucket['revenue'] ?? 0), null),
                    'latest_order_change' => $previousBucket !== null
                        ? $this->comparisonSnapshot(
                            (int) ($latestBucket['valid_order_count'] ?? 0),
                            (int) ($previousBucket['valid_order_count'] ?? 0),
                        )
                        : $this->comparisonSnapshot((int) ($latestBucket['valid_order_count'] ?? 0), null),
                ];
            })
            ->sort(function (array $left, array $right): int {
                if ($left['total_revenue'] !== $right['total_revenue']) {
                    return $right['total_revenue'] <=> $left['total_revenue'];
                }

                if ($left['total_valid_orders'] !== $right['total_valid_orders']) {
                    return $right['total_valid_orders'] <=> $left['total_valid_orders'];
                }

                return strcasecmp($left['branch_name'], $right['branch_name']);
            })
            ->values();

        $summaryRevenue = (int) $branchRows->sum('total_revenue');
        $summaryOrders = (int) $branchRows->sum('total_valid_orders');

        return [
            'period_type' => $context->periodType,
            'group_type' => $periodSetup['group_type'],
            'group_label' => $periodSetup['group_label'],
            'indicator' => $indicator,
            'indicator_label' => match ($indicator) {
                'revenue' => 'Doanh thu',
                'orders' => 'Số đơn',
                default => 'Cả hai',
            },
            'period_count' => $periods->count(),
            'period_count_options' => $periodSetup['period_count_options'],
            'period_count_selected' => $periodSetup['period_count_selected'],
            'periods' => $periods->values(),
            'totals' => [
                'periods' => collect($periodTotals)->map(function (array $bucket, string $key) use ($periods): array {
                    $period = $periods->firstWhere('key', $key);

                    return [
                        'period_key' => $key,
                        'label' => $period['label'] ?? $key,
                        'display_label' => $period['display_label'] ?? $key,
                        'revenue' => (int) ($bucket['revenue'] ?? 0),
                        'valid_order_count' => (int) ($bucket['valid_order_count'] ?? 0),
                    ];
                })->values(),
                'total_revenue' => $summaryRevenue,
                'total_valid_orders' => $summaryOrders,
                'branch_count' => $branchRows->count(),
            ],
            'branches' => $branchRows,
            'pagination' => [
                'current_page' => 1,
                'per_page' => $branchRows->count(),
                'total' => $branchRows->count(),
                'last_page' => 1,
            ],
            'branch_scope_label' => $context->branchScopeLabel,
            'search' => '',
            'error' => null,
        ];
    }

    private function emptyBranchTimeComparison(AnalyticsPeriodContext $context, array $options = []): array
    {
        $indicator = in_array(Arr::get($options, 'indicator', 'both'), ['both', 'revenue', 'orders'], true)
            ? (string) Arr::get($options, 'indicator', 'both')
            : 'both';

        return [
            'period_type' => $context->periodType,
            'group_type' => $context->periodType === 'range' ? 'day' : $context->periodType,
            'group_label' => match ($context->periodType) {
                'day' => 'Ngày',
                'week' => 'Tuần',
                'month' => 'Tháng',
                'year' => 'Năm',
                default => 'Tự động',
            },
            'indicator' => $indicator,
            'indicator_label' => match ($indicator) {
                'revenue' => 'Doanh thu',
                'orders' => 'Số đơn',
                default => 'Cả hai',
            },
            'period_count' => 0,
            'period_count_options' => [],
            'period_count_selected' => null,
            'periods' => collect(),
            'totals' => [
                'periods' => collect(),
                'total_revenue' => 0,
                'total_valid_orders' => 0,
                'branch_count' => 0,
            ],
            'branches' => collect(),
            'pagination' => [
                'current_page' => 1,
                'per_page' => 0,
                'total' => 0,
                'last_page' => 1,
            ],
            'branch_scope_label' => $context->branchScopeLabel,
            'search' => '',
            'error' => null,
        ];
    }

    private function resolveBranchTimeComparisonSetup(AnalyticsPeriodContext $context, array $options): array
    {
        $periodType = $context->periodType;
        $timezoneNow = CarbonImmutable::now($context->timezone);
        $baseStart = $context->currentStart
            ? CarbonImmutable::instance($context->currentStart)
            : $timezoneNow->startOfDay();
        $baseEnd = $context->currentEnd
            ? CarbonImmutable::instance($context->currentEnd)
            : $timezoneNow;

        if ($periodType === 'range') {
            $groupType = $this->resolveBranchTimeComparisonRangeGroupType($context);

            return [
                'group_type' => $groupType,
                'group_label' => match ($groupType) {
                    'day' => 'Ngày',
                    'week' => 'Tuần',
                    default => 'Tháng',
                },
                'period_count' => null,
                'period_count_selected' => null,
                'period_count_options' => [],
                'periods' => $this->buildBranchTimeComparisonRangePeriods($baseStart, $baseEnd, $groupType),
            ];
        }

        $defaultCounts = [
            'day' => [7, [7, 14, 30]],
            'week' => [8, [4, 8, 12, 26]],
            'month' => [12, [6, 12, 24]],
            'year' => [5, [3, 5, 10]],
        ];
        [$defaultCount, $countOptions] = $defaultCounts[$periodType] ?? [7, [7, 14, 30]];

        $requestedCount = (int) Arr::get($options, 'period_count', $defaultCount);
        $periodCount = in_array($requestedCount, $countOptions, true) ? $requestedCount : $defaultCount;

        return [
            'group_type' => $periodType,
            'group_label' => match ($periodType) {
                'day' => 'Ngày',
                'week' => 'Tuần',
                'month' => 'Tháng',
                'year' => 'Năm',
                default => 'Ngày',
            },
            'period_count' => $periodCount,
            'period_count_selected' => $periodCount,
            'period_count_options' => $countOptions,
            'periods' => $this->buildBranchTimeComparisonFixedPeriods($baseStart, $baseEnd, $periodType, $periodCount),
        ];
    }

    private function resolveBranchTimeComparisonRangeGroupType(AnalyticsPeriodContext $context): string
    {
        if (! $context->currentStart || ! $context->currentEnd) {
            return 'day';
        }

        $days = max(1, $context->currentStart->startOfDay()->diffInDays($context->currentEnd->startOfDay()) + 1);

        return match (true) {
            $days <= 31 => 'day',
            $days <= 180 => 'week',
            default => 'month',
        };
    }

    private function buildBranchTimeComparisonFixedPeriods(CarbonImmutable $baseStart, CarbonImmutable $baseEnd, string $groupType, int $periodCount): Collection
    {
        $periods = collect();

        for ($offset = max(1, $periodCount) - 1; $offset >= 0; $offset--) {
            $start = match ($groupType) {
                'week' => $baseStart->subWeeks($offset)->startOfWeek(Carbon::MONDAY),
                'month' => $baseStart->subMonthsNoOverflow($offset)->startOfMonth(),
                'year' => $baseStart->subYearsNoOverflow($offset)->startOfYear(),
                default => $baseStart->subDays($offset)->startOfDay(),
            };
            $naturalEnd = match ($groupType) {
                'week' => $start->endOfWeek(Carbon::SUNDAY),
                'month' => $start->endOfMonth(),
                'year' => $start->endOfYear(),
                default => $start->endOfDay(),
            };
            $isLatest = $offset === 0;
            $end = $isLatest ? $baseEnd : $naturalEnd;
            $isPartial = $isLatest && $end->lessThan($naturalEnd);

            $periods->push([
                'key' => $this->branchTimeComparisonPeriodKey($start, $groupType),
                'label' => $isPartial
                    ? 'Đang diễn ra'
                    : $this->branchTimeComparisonPeriodLabel($start, $end, $groupType),
                'display_label' => $this->branchTimeComparisonPeriodDisplayLabel($start, $end, $groupType),
                'start' => $start,
                'end' => $end,
                'is_partial' => $isPartial,
            ]);
        }

        return $periods->reverse()->values();
    }

    private function buildBranchTimeComparisonRangePeriods(CarbonImmutable $rangeStart, CarbonImmutable $rangeEnd, string $groupType): Collection
    {
        $periods = collect();

        if ($groupType === 'day') {
            $cursor = $rangeStart->startOfDay();
            $lastDate = $rangeEnd->startOfDay();

            while ($cursor->lessThanOrEqualTo($lastDate)) {
                $naturalEnd = $cursor->endOfDay();
                $end = $naturalEnd->greaterThan($rangeEnd) ? $rangeEnd : $naturalEnd;
                $isPartial = $end->lessThan($naturalEnd) || $cursor->lessThan($rangeStart->startOfDay());

                $periods->push([
                    'key' => $this->branchTimeComparisonPeriodKey($cursor, $groupType),
                    'label' => $isPartial && $cursor->isSameDay($lastDate) ? 'Đang diễn ra' : $this->branchTimeComparisonPeriodLabel($cursor, $end, $groupType),
                    'display_label' => $this->branchTimeComparisonPeriodDisplayLabel($cursor, $end, $groupType),
                    'start' => $cursor,
                    'end' => $end,
                    'is_partial' => $isPartial,
                ]);

                $cursor = $cursor->addDay();
            }

            return $periods->reverse()->values();
        }

        $cursor = $groupType === 'month'
            ? $rangeStart->startOfMonth()
            : $rangeStart->startOfWeek(Carbon::MONDAY);
        $step = $groupType === 'month' ? 'month' : 'week';

        while ($cursor->lessThanOrEqualTo($rangeEnd)) {
            $naturalEnd = $step === 'month' ? $cursor->endOfMonth() : $cursor->endOfWeek(Carbon::SUNDAY);
            $start = $cursor->greaterThan($rangeStart) ? $cursor : $rangeStart;
            $end = $naturalEnd->greaterThan($rangeEnd) ? $rangeEnd : $naturalEnd;
            $isPartial = $end->lessThan($naturalEnd) || $start->greaterThan($cursor);

            $periods->push([
                'key' => $this->branchTimeComparisonPeriodKey($cursor, $groupType),
                'label' => $isPartial && $naturalEnd->greaterThan($rangeEnd) ? 'Đang diễn ra' : $this->branchTimeComparisonPeriodLabel($cursor, $end, $groupType),
                'display_label' => $this->branchTimeComparisonPeriodDisplayLabel($cursor, $end, $groupType),
                'start' => $start,
                'end' => $end,
                'is_partial' => $isPartial,
            ]);

            $cursor = $step === 'month'
                ? $cursor->addMonthNoOverflow()->startOfMonth()
                : $cursor->addWeek()->startOfWeek(Carbon::MONDAY);
        }

        return $periods->reverse()->values();
    }

    /**
     * @return array{current_start: CarbonImmutable, current_end: CarbonImmutable, previous_start: CarbonImmutable, previous_end: CarbonImmutable}
     */
    private function branchTimeComparisonPreviousWindow(CarbonImmutable $currentStart, CarbonImmutable $currentEnd, string $groupType): array
    {
        $durationSeconds = max(0, $currentStart->diffInSeconds($currentEnd));

        $previousStart = match ($groupType) {
            'week' => $currentStart->subWeek()->startOfWeek(Carbon::MONDAY),
            'month' => $currentStart->subMonthNoOverflow()->startOfMonth(),
            'year' => $currentStart->subYearNoOverflow()->startOfYear(),
            default => $currentStart->subDay()->startOfDay(),
        };

        $previousEnd = $previousStart->addSeconds($durationSeconds);

        return [
            'current_start' => $currentStart,
            'current_end' => $currentEnd,
            'previous_start' => $previousStart,
            'previous_end' => $previousEnd,
        ];
    }

    /**
     * @param Collection<int, object> $orders
     * @return array{revenue: int, valid_order_count: int}
     */
    private function branchTimeComparisonWindowMetrics(Collection $orders, CarbonImmutable $start, CarbonImmutable $end, string $timezone): array
    {
        $revenue = 0;
        $validOrderCount = 0;

        foreach ($orders as $order) {
            $createdAt = $order->created_at instanceof CarbonInterface
                ? CarbonImmutable::instance($order->created_at)
                : CarbonImmutable::parse((string) $order->created_at, $timezone);

            if ($createdAt->lessThan($start) || $createdAt->greaterThan($end)) {
                continue;
            }

            $revenue += (int) round((float) ($order->total ?? 0));
            $validOrderCount += 1;
        }

        return [
            'revenue' => $revenue,
            'valid_order_count' => $validOrderCount,
        ];
    }

    private function branchTimeComparisonPeriodKey(CarbonImmutable $date, string $groupType): string
    {
        return match ($groupType) {
            'week' => $date->format('o-\WW'),
            'month' => $date->format('Y-m'),
            'year' => $date->format('Y'),
            default => $date->format('Y-m-d'),
        };
    }

    private function branchTimeComparisonPeriodLabel(CarbonImmutable $start, CarbonImmutable $end, string $groupType): string
    {
        return match ($groupType) {
            'week' => 'Tuần '.$start->format('d/m').' - '.$end->format('d/m'),
            'month' => 'Tháng '.$start->format('m/Y'),
            'year' => 'Năm '.$start->format('Y'),
            default => $start->format('d/m/Y'),
        };
    }

    private function branchTimeComparisonPeriodDisplayLabel(CarbonImmutable $start, CarbonImmutable $end, string $groupType): string
    {
        return match ($groupType) {
            'week' => $start->format('d/m').' - '.$end->format('d/m/Y'),
            'month' => $start->format('m/Y'),
            'year' => $start->format('Y'),
            default => $start->format('d/m/Y'),
        };
    }

    private function comparisonSnapshot(int $currentValue, ?int $previousValue): array
    {
        if ($previousValue === null) {
            return [
                'current_value' => $currentValue,
                'previous_value' => null,
                'absolute_change' => null,
                'percentage_change' => null,
                'state' => 'unavailable',
                'label' => 'Chưa đủ dữ liệu',
            ];
        }

        if ($previousValue === 0 && $currentValue === 0) {
            return [
                'current_value' => 0,
                'previous_value' => 0,
                'absolute_change' => 0,
                'percentage_change' => 0.0,
                'state' => 'unchanged',
                'label' => 'Không đổi',
            ];
        }

        if ($previousValue === 0 && $currentValue > 0) {
            return [
                'current_value' => $currentValue,
                'previous_value' => 0,
                'absolute_change' => $currentValue,
                'percentage_change' => 100.0,
                'state' => 'new_activity',
                'label' => 'Phát sinh mới',
            ];
        }

        if ($currentValue === 0 && $previousValue > 0) {
            return [
                'current_value' => 0,
                'previous_value' => $previousValue,
                'absolute_change' => -$previousValue,
                'percentage_change' => -100.0,
                'state' => 'decreased',
                'label' => 'Giảm',
            ];
        }

        $absoluteChange = $currentValue - $previousValue;
        $percentageChange = $previousValue !== 0
            ? round(($absoluteChange / $previousValue) * 100, 1)
            : 0.0;

        return [
            'current_value' => $currentValue,
            'previous_value' => $previousValue,
            'absolute_change' => $absoluteChange,
            'percentage_change' => $percentageChange,
            'state' => $absoluteChange > 0 ? 'increased' : ($absoluteChange < 0 ? 'decreased' : 'unchanged'),
            'label' => $absoluteChange > 0 ? 'Tăng' : ($absoluteChange < 0 ? 'Giảm' : 'Không đổi'),
        ];
    }

    public function branchProductDetail(AnalyticsPeriodContext $context, int $branchId, array $options = []): array
    {
        $sortBy = in_array(Arr::get($options, 'sort_by'), ['quantity', 'revenue'], true)
            ? (string) Arr::get($options, 'sort_by')
            : 'quantity';
        $scopeBranchIds = $this->contextBranchScopeIds($context, Arr::get($options, 'analytics_branch_ids', Arr::get($options, 'branch_ids')));

        if ($scopeBranchIds !== [] && ! in_array($branchId, $scopeBranchIds, true)) {
            $branchId = $scopeBranchIds[0];
        }

        $branch = $this->resolveBranchForDetail($branchId, $scopeBranchIds);

        if (! $branch) {
            return [
                'branch' => null,
                'period_label' => $context->displayLabel,
                'comparison_label' => $context->hasComparison() ? $context->comparisonLabel : 'Không đối chiếu',
                'summary' => [
                    'revenue' => 0,
                    'valid_order_count' => 0,
                    'unique_customer_count' => 0,
                    'items_sold' => 0,
                    'average_order_value' => 0,
                    'completed_order_count' => 0,
                    'cancelled_order_count' => 0,
                    'total_created_order_count' => 0,
                    'cancellation_rate' => 0,
                ],
                'comparison' => [
                    'compare_revenue' => null,
                    'compare_order_count' => null,
                    'compare_items_sold' => null,
                    'compare_average_order_value' => null,
                    'revenue_change_percentage' => null,
                    'order_change_percentage' => null,
                    'items_change_percentage' => null,
                    'average_order_value_change_percentage' => null,
                    'change_state' => 'unavailable',
                    'comparison_label' => 'Không đối chiếu',
                    'compare_cancellation_rate' => null,
                ],
                'top_products' => collect(),
                'sort_by' => $sortBy,
            ];
        }

        $comparisonLabel = $context->hasComparison() ? $context->comparisonLabel : 'Không đối chiếu';
        $currentOrderSummary = $this->branchOrderSummary($context->currentStart, $context->currentEnd, (int) $branch->id);
        $currentProductRows = $this->branchProductAggregateRows($context->currentStart, $context->currentEnd, (int) $branch->id);
        $compareOrderSummary = $context->hasComparison()
            ? $this->branchOrderSummary($context->compareStart, $context->compareEnd, (int) $branch->id)
            : null;

        $compareProductRows = collect();
        $currentRevenue = (int) ($currentOrderSummary->revenue ?? 0);
        $currentValidOrderCount = (int) ($currentOrderSummary->valid_order_count ?? 0);
        $currentUniqueCustomerCount = (int) ($currentOrderSummary->unique_customer_count ?? 0);
        $currentItemsSold = (int) $currentProductRows->sum(fn (object $row) => (int) ($row->total_quantity ?? 0));
        $currentItemRevenue = (int) $currentProductRows->sum(fn (object $row) => (int) ($row->total_revenue ?? 0));
        $currentAverageOrderValue = $currentValidOrderCount > 0 ? (int) round($currentRevenue / $currentValidOrderCount) : 0;
        $currentTotalCreatedOrderCount = (int) ($currentOrderSummary->total_created_order_count ?? 0);
        $currentCompletedOrderCount = (int) ($currentOrderSummary->completed_order_count ?? 0);
        $currentCancelledOrderCount = (int) ($currentOrderSummary->cancelled_order_count ?? 0);
        $currentCancellationRate = $currentTotalCreatedOrderCount > 0
            ? (float) round(($currentCancelledOrderCount / $currentTotalCreatedOrderCount) * 100, 1)
            : 0.0;

        $compareRevenueValue = $compareOrderSummary !== null ? (int) ($compareOrderSummary->revenue ?? 0) : null;
        $compareOrderValue = $compareOrderSummary !== null ? (int) ($compareOrderSummary->valid_order_count ?? 0) : null;
        $compareItemsValue = null;
        if ($context->hasComparison()) {
            $compareProductRows = $this->branchProductAggregateRows($context->compareStart, $context->compareEnd, (int) $branch->id);
            $compareItemsValue = (int) $compareProductRows->sum(fn (object $row) => (int) ($row->total_quantity ?? 0));
        }

        $revenueComparison = $this->composeMetric(
            $currentRevenue,
            $compareRevenueValue,
            $comparisonLabel
        );
        $orderComparison = $this->composeMetric(
            $currentValidOrderCount,
            $compareOrderValue,
            $comparisonLabel
        );
        $itemComparison = $this->composeMetric(
            $currentItemsSold,
            $compareItemsValue,
            $comparisonLabel
        );
        $compareAverageOrderValue = $compareOrderValue !== null && $compareOrderValue > 0
            ? (int) round(($compareRevenueValue ?? 0) / $compareOrderValue)
            : ($compareOrderValue !== null && ($compareRevenueValue ?? 0) === 0 && $compareOrderValue === 0 ? 0 : null);
        $averageComparison = $this->composeMetric(
            $currentAverageOrderValue,
            $compareAverageOrderValue,
            $comparisonLabel
        );

        $topProductsRows = $this->sortBranchProductRows($currentProductRows, $sortBy)
            ->take(5)
            ->values();

        $topProductIds = $topProductsRows->pluck('product_id')->filter()->map(fn ($value) => (int) $value)->values()->all();
        $compareTopProducts = collect();
        if ($context->hasComparison() && $topProductIds !== []) {
            $compareTopProducts = $compareProductRows
                ->filter(fn (object $row) => in_array((int) ($row->product_id ?? 0), $topProductIds, true))
                ->keyBy('product_id');
        }

        return [
            'branch' => [
                'id' => (int) $branch->id,
                'name' => (string) $branch->name,
                'code' => (string) $branch->code,
                'status' => (bool) $branch->status,
            ],
            'period_label' => $context->displayLabel,
            'comparison_label' => $comparisonLabel,
            'summary' => [
                'revenue' => $currentRevenue,
                'valid_order_count' => $currentValidOrderCount,
                'unique_customer_count' => $currentUniqueCustomerCount,
                'items_sold' => $currentItemsSold,
                'average_order_value' => $currentAverageOrderValue,
                'completed_order_count' => $currentCompletedOrderCount,
                'cancelled_order_count' => $currentCancelledOrderCount,
                'total_created_order_count' => $currentTotalCreatedOrderCount,
                'cancellation_rate' => $currentCancellationRate,
            ],
            'comparison' => [
                'compare_revenue' => $compareRevenueValue,
                'compare_order_count' => $compareOrderValue,
                'compare_items_sold' => $compareItemsValue,
                'compare_average_order_value' => $averageComparison['compare_value'],
                'revenue_change_percentage' => $revenueComparison['percentage_change'],
                'order_change_percentage' => $orderComparison['percentage_change'],
                'items_change_percentage' => $itemComparison['percentage_change'],
                'average_order_value_change_percentage' => $averageComparison['percentage_change'],
                'change_state' => $revenueComparison['change_state'],
                'comparison_label' => $comparisonLabel,
                'compare_cancellation_rate' => $compareOrderSummary !== null && (int) ($compareOrderSummary->total_created_order_count ?? 0) > 0
                    ? (float) round(((int) ($compareOrderSummary->cancelled_order_count ?? 0) / (int) ($compareOrderSummary->total_created_order_count ?? 1)) * 100, 1)
                    : null,
            ],
            'top_products' => $topProductsRows
                ->values()
                ->map(function (object $row, int $index) use ($compareTopProducts, $sortBy, $currentItemRevenue, $currentItemsSold, $comparisonLabel): array {
                    $productId = (int) $row->product_id;
                    $productName = filled($row->product_name ?? null)
                        ? (string) $row->product_name
                        : 'Sản phẩm #'.$productId.' đã xóa';
                    $compareRow = $compareTopProducts->get($productId);
                    $quantityComparison = $this->composeMetric(
                        (int) $row->total_quantity,
                        $compareRow !== null ? (int) ($compareRow->total_quantity ?? 0) : null,
                        $comparisonLabel
                    );
                    $revenueComparison = $this->composeMetric(
                        (int) $row->total_revenue,
                        $compareRow !== null ? (int) ($compareRow->total_revenue ?? 0) : null,
                        $comparisonLabel
                    );

                    return [
                        'rank' => $index + 1,
                        'product_id' => $productId,
                        'product_name' => $productName,
                        'product_image' => $row->product_image,
                        'product_image_url' => ProductImage::resolve(
                            filled($row->product_image ?? null) ? (string) $row->product_image : null,
                            null,
                            $productId,
                            220
                        ),
                        'total_quantity' => (int) $row->total_quantity,
                        'total_revenue' => (int) $row->total_revenue,
                        'revenue_share_percentage' => $currentItemRevenue > 0 ? round(((int) $row->total_revenue / $currentItemRevenue) * 100, 1) : 0.0,
                        'quantity_share_percentage' => $currentItemsSold > 0 ? round(((int) $row->total_quantity / $currentItemsSold) * 100, 1) : 0.0,
                        'compare_quantity' => $quantityComparison['compare_value'],
                        'compare_revenue' => $revenueComparison['compare_value'],
                        'quantity_change_percentage' => $quantityComparison['percentage_change'],
                        'revenue_change_percentage' => $revenueComparison['percentage_change'],
                        'change_state' => $quantityComparison['change_state'],
                    ];
                })
                ->values(),
            'sort_by' => $sortBy,
        ];
    }

    private function resolveOverviewBranchPeriod(AnalyticsPeriodContext $context, string $branchPeriod, string $branchStartDate = '', string $branchEndDate = ''): array
    {
        $now = now($context->timezone);

        if ($branchPeriod === 'range') {
            try {
                $start = $branchStartDate !== ''
                    ? Carbon::createFromFormat('Y-m-d', $branchStartDate, $context->timezone)->startOfDay()
                    : $now->copy()->startOfMonth()->startOfDay();
                $end = $branchEndDate !== ''
                    ? Carbon::createFromFormat('Y-m-d', $branchEndDate, $context->timezone)->endOfDay()
                    : $now->copy()->endOfDay();
            } catch (\Throwable) {
                $start = $now->copy()->startOfMonth()->startOfDay();
                $end = $now->copy()->endOfDay();
            }

            if ($start->gt($end)) {
                [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
            }

            return [
                $start,
                $end,
                null,
                null,
                $start->format('d/m/Y').' - '.$end->format('d/m/Y'),
                'Không so sánh',
            ];
        }

        return match ($branchPeriod) {
            'week' => [
                $now->copy()->startOfWeek(Carbon::MONDAY),
                $now->copy()->endOfWeek(Carbon::SUNDAY),
                null,
                null,
                'Tuần này',
                'Không so sánh',
            ],
            'month' => [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
                null,
                null,
                'Tháng '.$now->format('m/Y'),
                'Không so sánh',
            ],
            'year' => [
                $now->copy()->startOfYear(),
                $now->copy()->endOfYear(),
                null,
                null,
                'Năm '.$now->format('Y'),
                'Không so sánh',
            ],
            default => [
                $now->copy()->startOfDay(),
                $now->copy()->endOfDay(),
                null,
                null,
                'Hôm nay',
                'Không so sánh',
            ],
        };
    }

    private function resolveBranchComparisonPeriod(AnalyticsPeriodContext $context, string $legacyRankingPeriod): array
    {
        if ($context->periodType !== 'all' && $context->currentStart && $context->currentEnd) {
            return [
                $context->currentStart,
                $context->currentEnd,
                $context->hasComparison() ? $context->compareStart : null,
                $context->hasComparison() ? $context->compareEnd : null,
                $context->displayLabel,
                $context->comparisonLabel,
            ];
        }

        $now = now($context->timezone);

        return match ($legacyRankingPeriod) {
            'week' => [
                $now->copy()->startOfWeek(Carbon::MONDAY),
                $now->copy()->endOfWeek(Carbon::SUNDAY),
                null,
                null,
                'Tuần '.$now->copy()->startOfWeek(Carbon::MONDAY)->format('d/m/Y').' - '.$now->copy()->endOfWeek(Carbon::SUNDAY)->format('d/m/Y'),
                'Không so sánh',
            ],
            'month' => [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
                null,
                null,
                'Tháng '.$now->format('m/Y'),
                'Không so sánh',
            ],
            'year' => [
                $now->copy()->startOfYear(),
                $now->copy()->endOfYear(),
                null,
                null,
                'Năm '.$now->format('Y'),
                'Không so sánh',
            ],
            default => [null, null, null, null, 'Tất cả thời gian', 'Không so sánh'],
        };
    }

    private function branchEmployeeCountsSubquery(): QueryBuilder
    {
        return DB::table('users')
            ->selectRaw('branch_id, COUNT(*) as employee_count, COALESCE(SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END), 0) as active_employee_count')
            ->whereNotNull('branch_id')
            ->groupBy('branch_id');
    }

    private function branchAdminIdsSubquery(): QueryBuilder
    {
        return DB::table('users')
            ->selectRaw('branch_id, MIN(id) as admin_id')
            // Chi nhánh có thể thuộc admin thường (2) hoặc super admin (3)
            ->whereIn('role_id', [2, 3])
            ->whereNotNull('branch_id')
            ->groupBy('branch_id');
    }

    private function branchComparisonMetricsSubquery(?CarbonInterface $from, ?CarbonInterface $to, array $branchIds = []): Builder
    {
        $validSalesSql = $this->validSalesPredicateSql('orders');
        $query = Order::query()->whereNotNull('orders.branch_id');
        $this->applyDateRange($query, $from, $to);

        if ($branchIds !== []) {
            $query->whereIn('orders.branch_id', $branchIds);
        }

        return $query
            ->selectRaw(
                'orders.branch_id, ' .
                'COALESCE(SUM(CASE WHEN '.$validSalesSql.' THEN orders.total ELSE 0 END), 0) as revenue, ' .
                'COALESCE(SUM(CASE WHEN '.$validSalesSql.' THEN 1 ELSE 0 END), 0) as valid_order_count, ' .
                'COUNT(DISTINCT CASE WHEN '.$validSalesSql.' AND orders.user_id IS NOT NULL THEN orders.user_id END) as unique_customer_count, ' .
                'COUNT(*) as total_created_order_count, ' .
                'COALESCE(SUM(CASE WHEN orders.status = "completed" THEN 1 ELSE 0 END), 0) as completed_order_count, ' .
                'COALESCE(SUM(CASE WHEN orders.status = "cancelled" THEN 1 ELSE 0 END), 0) as cancelled_order_count'
            )
            ->groupBy('orders.branch_id');
    }

    private function branchValidMetricsSubquery(?CarbonInterface $from, ?CarbonInterface $to, array $branchIds = []): Builder
    {
        $query = $this->validSalesOrdersQuery()
            ->whereNotNull('orders.branch_id');

        $this->applyDateRange($query, $from, $to);

        if ($branchIds !== []) {
            $query->whereIn('orders.branch_id', $branchIds);
        }

        return $query
            ->selectRaw('orders.branch_id, SUM(orders.total) as revenue, COUNT(DISTINCT orders.id) as valid_order_count, COUNT(DISTINCT orders.user_id) as unique_customer_count')
            ->groupBy('orders.branch_id');
    }

    private function branchOrderMetricsSubquery(?CarbonInterface $from, ?CarbonInterface $to, array $branchIds = []): Builder
    {
        $query = Order::query()
            ->whereNotNull('orders.branch_id');

        $this->applyDateRange($query, $from, $to);

        if ($branchIds !== []) {
            $query->whereIn('orders.branch_id', $branchIds);
        }

        return $query
            ->selectRaw('orders.branch_id, COUNT(*) as total_order_count, COALESCE(SUM(CASE WHEN orders.status = "completed" THEN 1 ELSE 0 END), 0) as completed_order_count, COALESCE(SUM(CASE WHEN orders.status = "cancelled" THEN 1 ELSE 0 END), 0) as cancelled_order_count')
            ->groupBy('orders.branch_id');
    }

    private function branchItemMetricsSubquery(?CarbonInterface $from, ?CarbonInterface $to, array $branchIds = []): Builder
    {
        $query = $this->validSalesOrderItemsQuery()
            ->whereNotNull('orders.branch_id');

        $this->applyDateRange($query, $from, $to);

        if ($branchIds !== []) {
            $query->whereIn('orders.branch_id', $branchIds);
        }

        return $query
            ->selectRaw('orders.branch_id, COALESCE(SUM(order_items.quantity), 0) as items_sold')
            ->groupBy('orders.branch_id');
    }

    private function branchTopProductSubquery(?CarbonInterface $from, ?CarbonInterface $to, array $branchIds = []): QueryBuilder
    {
        $query = $this->validSalesOrderItemsQuery()
            ->whereNotNull('orders.branch_id');

        $this->applyDateRange($query, $from, $to);

        if ($branchIds !== []) {
            $query->whereIn('orders.branch_id', $branchIds);
        }

        $productTotals = $query
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->selectRaw(
                "orders.branch_id as branch_id, order_items.product_id as top_product_id, " .
                'COALESCE(NULLIF(products.name, \'\'), '.$this->sqlConcat(["'Sản phẩm #'", 'order_items.product_id', "' đã xóa'"]).') as top_product_name, ' .
                'products.image as top_product_image, SUM(order_items.quantity) as top_product_quantity, ' .
                'SUM(COALESCE(order_items.total_price, order_items.quantity * order_items.unit_price)) as top_product_revenue'
            )
            ->groupBy('orders.branch_id', 'order_items.product_id', 'products.name', 'products.image');

        $rankedProducts = DB::query()
            ->fromSub($productTotals, 'branch_product_totals')
            ->selectRaw('branch_id, top_product_id, top_product_name, top_product_image, top_product_quantity, top_product_revenue, ROW_NUMBER() OVER (PARTITION BY branch_id ORDER BY top_product_quantity DESC, top_product_revenue DESC, top_product_id ASC) as branch_product_rank');

        return DB::query()
            ->fromSub($rankedProducts, 'ranked_branch_products')
            ->where('branch_product_rank', 1);
    }

    private function branchProductSummarySubquery(?CarbonInterface $from, ?CarbonInterface $to, array $branchIds = []): QueryBuilder
    {
        $query = $this->validSalesOrderItemsQuery()
            ->whereNotNull('orders.branch_id');

        $this->applyDateRange($query, $from, $to);

        if ($branchIds !== []) {
            $query->whereIn('orders.branch_id', $branchIds);
        }

        $productTotals = $query
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->selectRaw(
                'orders.branch_id, ' .
                'order_items.product_id as top_product_id, ' .
                'COALESCE(NULLIF(products.name, \'\'), '.$this->sqlConcat(["'Sản phẩm #'", 'order_items.product_id', "' đã xóa'"]).') as top_product_name, ' .
                'products.image as top_product_image, ' .
                'COALESCE(SUM(order_items.quantity), 0) as top_product_quantity, ' .
                'COALESCE(SUM(COALESCE(order_items.total_price, order_items.quantity * order_items.unit_price)), 0) as top_product_revenue'
            )
            ->groupBy('orders.branch_id', 'order_items.product_id', 'products.name', 'products.image');

        $rankedProducts = DB::query()
            ->fromSub($productTotals, 'branch_product_totals')
            ->selectRaw(
                'branch_id, top_product_id, top_product_name, top_product_image, top_product_quantity, top_product_revenue, ' .
                'SUM(top_product_quantity) OVER (PARTITION BY branch_id) as items_sold, ' .
                'ROW_NUMBER() OVER (PARTITION BY branch_id ORDER BY top_product_quantity DESC, top_product_revenue DESC, top_product_id ASC) as branch_product_rank'
            );

        return DB::query()
            ->fromSub($rankedProducts, 'ranked_branch_products')
            ->where('branch_product_rank', 1);
    }

    private function branchProductAggregationQuery(?CarbonInterface $from, ?CarbonInterface $to, int $branchId): Builder
    {
        $query = $this->validSalesOrderItemsQuery()
            ->where('orders.branch_id', $branchId);

        $this->applyDateRange($query, $from, $to);

        return $query
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->selectRaw(
                'order_items.product_id, ' .
                'COALESCE(NULLIF(products.name, \'\'), '.$this->sqlConcat(["'Sản phẩm #'", 'order_items.product_id', "' đã xóa'"]).') as product_name, ' .
                'products.image as product_image, ' .
                'SUM(order_items.quantity) as total_quantity, ' .
                'SUM(COALESCE(order_items.total_price, order_items.quantity * order_items.unit_price)) as total_revenue'
            )
            ->groupBy('order_items.product_id', 'products.name', 'products.image');
    }

    private function branchProductAggregateRows(
        ?CarbonInterface $from,
        ?CarbonInterface $to,
        int $branchId,
        array $productIds = []
    ): Collection {
        $cacheKey = $this->memoKey([
            'branch-product-aggregate',
            $branchId,
            $this->dateKey($from),
            $this->dateKey($to),
            $productIds,
        ]);

        if (! array_key_exists($cacheKey, $this->branchProductAggregateCache)) {
            $query = $this->branchProductAggregationQuery($from, $to, $branchId);

            if ($productIds !== []) {
                $query->whereIn('order_items.product_id', $productIds);
            }

            $this->branchProductAggregateCache[$cacheKey] = $query->get();
        }

        return $this->branchProductAggregateCache[$cacheKey];
    }

    private function productBranchTotalsSubquery(
        ?CarbonInterface $from,
        ?CarbonInterface $to,
        int $productId,
        array $branchIds = []
    ): Builder {
        $query = $this->validSalesOrderItemsQuery()
            ->where('order_items.product_id', $productId)
            ->whereNotNull('orders.branch_id');

        $this->applyDateRange($query, $from, $to);

        if ($branchIds !== []) {
            $query->whereIn('orders.branch_id', $branchIds);
        }

        return $query
            ->selectRaw(
                'orders.branch_id as branch_id, ' .
                'COALESCE(SUM(order_items.quantity), 0) as total_quantity, ' .
                'COALESCE(SUM(COALESCE(order_items.total_price, order_items.quantity * order_items.unit_price)), 0) as total_revenue'
            )
            ->groupBy('orders.branch_id');
    }

    private function productBranchAggregateRows(
        ?CarbonInterface $from,
        ?CarbonInterface $to,
        int $productId,
        array $branchIds = []
    ): Collection {
        $cacheKey = $this->memoKey([
            'product-branch-aggregate',
            $productId,
            $this->dateKey($from),
            $this->dateKey($to),
            $branchIds,
        ]);

        if (! array_key_exists($cacheKey, $this->productBranchAggregateCache)) {
            $this->productBranchAggregateCache[$cacheKey] = $this->productBranchTotalsSubquery($from, $to, $productId, $branchIds)->get();
        }

        return $this->productBranchAggregateCache[$cacheKey];
    }

    /**
     * @return array<int>
     */
    private function contextBranchScopeIds(AnalyticsPeriodContext $context, mixed $overrideBranchIds = null): array
    {
        if ($context->hasBranchScope()) {
            return $context->normalizedBranchIds();
        }

        $normalizedOverride = $this->normalizeBranchScopeSelection($overrideBranchIds);

        if ($normalizedOverride !== []) {
            return $normalizedOverride;
        }

        return $context->branchId ? [$context->branchId] : [];
    }

    /**
     * @return array<int>
     */
    private function normalizeBranchScopeIds(?int $branchId, mixed $branchIds): array
    {
        if ($branchId) {
            return [$branchId];
        }

        return $this->normalizeBranchScopeSelection($branchIds);
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

    private function hasTableCached(string $table): bool
    {
        if (! array_key_exists($table, $this->tableExistsCache)) {
            $this->tableExistsCache[$table] = Schema::hasTable($table);
        }

        return $this->tableExistsCache[$table];
    }

    private function loadProductMetadata(int $productId): ?Product
    {
        $cacheKey = (string) $productId;

        if (! array_key_exists($cacheKey, $this->productMetadataCache)) {
            $this->productMetadataCache[$cacheKey] = $this->hasTableCached('products')
                ? Product::query()
                    ->withTrashed()
                    ->select(['id', 'name', 'sku', 'image', 'status', 'deleted_at'])
                    ->whereKey($productId)
                    ->first()
                : null;
        }

        return $this->productMetadataCache[$cacheKey];
    }

    private function loadBranchMetadata(array $scopeBranchIds = []): Collection
    {
        $normalizedScope = $this->normalizeBranchScopeSelection($scopeBranchIds);
        $cacheKey = $this->memoKey(['branch-metadata', $normalizedScope]);

        if (! array_key_exists($cacheKey, $this->branchMetadataCache)) {
            $this->branchMetadataCache[$cacheKey] = Branch::query()
                ->select(['id', 'name', 'code', 'status'])
                ->when($normalizedScope !== [], fn (Builder $query) => $query->whereIn('id', $normalizedScope))
                ->orderBy('id')
                ->get();
        }

        return $this->branchMetadataCache[$cacheKey];
    }

    private function resolveBranchForDetail(int $branchId, array $scopeBranchIds = []): ?Branch
    {
        return Branch::query()
            ->select(['id', 'name', 'code', 'status'])
            ->when($scopeBranchIds !== [], fn (Builder $query) => $query->whereIn('id', $scopeBranchIds))
            ->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [$branchId])
            ->orderBy('id')
            ->first();
    }

    private function branchOrderSummary(?CarbonInterface $from, ?CarbonInterface $to, int $branchId): object
    {
        $cacheKey = $this->memoKey([
            'branch-order-summary',
            $branchId,
            $this->dateKey($from),
            $this->dateKey($to),
        ]);

        if (! array_key_exists($cacheKey, $this->branchOrderAggregateCache)) {
            $query = Order::query();
            $this->applyDateRange($query, $from, $to);
            $this->applyBranchScope($query, $branchId);

            $this->branchOrderAggregateCache[$cacheKey] = $query
                ->selectRaw(
                    'COALESCE(SUM(CASE WHEN orders.status = "completed" THEN orders.total ELSE 0 END), 0) as revenue, ' .
                    'COALESCE(SUM(CASE WHEN orders.status = "completed" THEN 1 ELSE 0 END), 0) as valid_order_count, ' .
                    'COUNT(DISTINCT CASE WHEN orders.status = "completed" AND orders.user_id IS NOT NULL THEN orders.user_id END) as unique_customer_count, ' .
                    'COUNT(*) as total_created_order_count, ' .
                    'COALESCE(SUM(CASE WHEN orders.status = "completed" THEN 1 ELSE 0 END), 0) as completed_order_count, ' .
                    'COALESCE(SUM(CASE WHEN orders.status = "cancelled" THEN 1 ELSE 0 END), 0) as cancelled_order_count'
                )
                ->first();
        }

        return $this->branchOrderAggregateCache[$cacheKey];
    }

    private function sortBranchProductRows(Collection $rows, string $sortBy): Collection
    {
        return $rows->sort(function (object $left, object $right) use ($sortBy): int {
            $primaryLeft = $sortBy === 'revenue' ? (int) ($left->total_revenue ?? 0) : (int) ($left->total_quantity ?? 0);
            $primaryRight = $sortBy === 'revenue' ? (int) ($right->total_revenue ?? 0) : (int) ($right->total_quantity ?? 0);

            if ($primaryLeft !== $primaryRight) {
                return $primaryRight <=> $primaryLeft;
            }

            $secondaryLeft = $sortBy === 'revenue' ? (int) ($left->total_quantity ?? 0) : (int) ($left->total_revenue ?? 0);
            $secondaryRight = $sortBy === 'revenue' ? (int) ($right->total_quantity ?? 0) : (int) ($right->total_revenue ?? 0);

            if ($secondaryLeft !== $secondaryRight) {
                return $secondaryRight <=> $secondaryLeft;
            }

            return ((int) ($left->product_id ?? 0)) <=> ((int) ($right->product_id ?? 0));
        })->values();
    }

    private function sortProductBranchRows(Collection $rows, string $sortBy): Collection
    {
        return $rows->sort(function (array $left, array $right) use ($sortBy): int {
            $primaryLeft = $sortBy === 'revenue' ? (int) $left['total_revenue'] : (int) $left['total_quantity'];
            $primaryRight = $sortBy === 'revenue' ? (int) $right['total_revenue'] : (int) $right['total_quantity'];

            if ($primaryLeft !== $primaryRight) {
                return $primaryRight <=> $primaryLeft;
            }

            $secondaryLeft = $sortBy === 'revenue' ? (int) $left['total_quantity'] : (int) $left['total_revenue'];
            $secondaryRight = $sortBy === 'revenue' ? (int) $right['total_quantity'] : (int) $right['total_revenue'];

            if ($secondaryLeft !== $secondaryRight) {
                return $secondaryRight <=> $secondaryLeft;
            }

            return ((int) $left['branch_id']) <=> ((int) $right['branch_id']);
        })->values();
    }

    private function memoKey(array $parts): string
    {
        return md5(json_encode($parts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: serialize($parts));
    }

    private function dateKey(?CarbonInterface $date): ?string
    {
        return $date?->toDateTimeString();
    }

    private function sqlConcat(array $parts): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? implode(' || ', $parts)
            : 'CONCAT('.implode(', ', $parts).')';
    }

    private function comparisonPercentageSql(string $currentColumn, string $compareColumn): string
    {
        return <<<SQL
CASE
    WHEN COALESCE($compareColumn, 0) > 0 THEN ROUND(((1.0 * (COALESCE($currentColumn, 0) - COALESCE($compareColumn, 0))) / COALESCE($compareColumn, 1)) * 100, 1)
    WHEN COALESCE($currentColumn, 0) > 0 THEN 100
    ELSE 0
END
SQL;
    }

    private function validSalesPredicateSql(string $table = 'orders'): string
    {
        return $table.'.status = "completed"';
    }

    private function comparisonStateSql(string $currentColumn, string $compareColumn): string
    {
        return <<<SQL
CASE
    WHEN $compareColumn IS NULL THEN 'unavailable'
    WHEN COALESCE($compareColumn, 0) = 0 AND COALESCE($currentColumn, 0) = 0 THEN 'unchanged'
    WHEN COALESCE($compareColumn, 0) = 0 AND COALESCE($currentColumn, 0) > 0 THEN 'new_activity'
    WHEN COALESCE($currentColumn, 0) > COALESCE($compareColumn, 0) THEN 'increased'
    WHEN COALESCE($currentColumn, 0) < COALESCE($compareColumn, 0) THEN 'decreased'
    ELSE 'unchanged'
END
SQL;
    }

    private function applyBranchPerformanceFilter(Builder $query, string $performance, bool $hasComparison): Builder
    {
        return match ($performance) {
            'increased' => $hasComparison
                ? $query->whereRaw('COALESCE(current_branch_metrics.revenue, 0) > COALESCE(compare_branch_metrics.revenue, 0)')
                : $query->whereRaw('1 = 0'),
            'decreased' => $hasComparison
                ? $query->whereRaw('COALESCE(current_branch_metrics.revenue, 0) < COALESCE(compare_branch_metrics.revenue, 0)')
                : $query->whereRaw('1 = 0'),
            'unchanged' => $hasComparison
                ? $query->whereRaw('COALESCE(current_branch_metrics.revenue, 0) = COALESCE(compare_branch_metrics.revenue, 0) AND COALESCE(compare_branch_metrics.revenue, 0) > 0')
                : $query->whereRaw('1 = 0'),
            'new_activity' => $hasComparison
                ? $query->whereRaw('COALESCE(compare_branch_metrics.revenue, 0) = 0 AND COALESCE(current_branch_metrics.revenue, 0) > 0')
                : $query->whereRaw('1 = 0'),
            'no_orders' => $query->whereRaw('COALESCE(current_branch_metrics.total_created_order_count, 0) = 0'),
            default => $query,
        };
    }

    private function branchComparisonSortMap(): array
    {
        return [
            'revenue' => 'COALESCE(current_branch_metrics.revenue, 0)',
            'orders' => 'COALESCE(current_branch_metrics.valid_order_count, 0)',
            'average_order_value' => 'COALESCE((CASE WHEN COALESCE(current_branch_metrics.valid_order_count, 0) > 0 THEN ROUND(COALESCE(current_branch_metrics.revenue, 0) / COALESCE(current_branch_metrics.valid_order_count, 1)) ELSE 0 END), 0)',
            'items_sold' => 'COALESCE(current_product_summary.items_sold, 0)',
            'growth' => 'COALESCE('.$this->comparisonPercentageSql('current_branch_metrics.revenue', 'compare_branch_metrics.revenue').', 0)',
            'cancellation_rate' => 'COALESCE((CASE WHEN COALESCE(current_branch_metrics.total_created_order_count, 0) > 0 THEN ROUND((COALESCE(current_branch_metrics.cancelled_order_count, 0) / COALESCE(current_branch_metrics.total_created_order_count, 1)) * 100, 1) ELSE 0 END), 0)',
            'name' => 'branches.name',
        ];
    }

    private function aggregateOrderMetrics(Builder $query): array
    {
        $row = $query
            ->selectRaw('COALESCE(SUM(orders.total), 0) as revenue, COUNT(DISTINCT orders.id) as orders_count, COUNT(DISTINCT orders.user_id) as customers_count, COALESCE(SUM(CASE WHEN orders.user_id IS NULL THEN 1 ELSE 0 END), 0) as guest_orders_count')
            ->first();

        return [
            'revenue' => (int) ($row?->revenue ?? 0),
            'orders' => (int) ($row?->orders_count ?? 0),
            'customers' => (int) ($row?->customers_count ?? 0),
            'guest_orders' => (int) ($row?->guest_orders_count ?? 0),
        ];
    }

    private function aggregateItemMetrics(?CarbonInterface $from, ?CarbonInterface $to, int|array|null $branchScope): int
    {
        if (! $this->hasTableCached('order_items')) {
            return 0;
        }

        $query = $this->validSalesOrderItemsQuery();
        $this->applyDateRange($query, $from, $to);
        $this->applyBranchScope($query, $branchScope);

        $row = $query
            ->selectRaw('COALESCE(SUM(order_items.quantity), 0) as items_sold')
            ->first();

        return (int) ($row?->items_sold ?? 0);
    }

    private function composeMetric(int $currentValue, ?int $compareValue, string $comparisonLabel): array
    {
        if ($compareValue === null) {
            return [
                'current_value' => $currentValue,
                'compare_value' => null,
                'absolute_change' => null,
                'percentage_change' => null,
                'change_state' => 'unavailable',
                'comparison_label' => 'Không đối chiếu',
            ];
        }

        $absoluteChange = $currentValue - $compareValue;

        if ($compareValue === 0) {
            if ($currentValue === 0) {
                return [
                    'current_value' => 0,
                    'compare_value' => 0,
                    'absolute_change' => 0,
                    'percentage_change' => 0.0,
                    'change_state' => 'unchanged',
                    'comparison_label' => $comparisonLabel,
                ];
            }

            return [
                'current_value' => $currentValue,
                'compare_value' => 0,
                'absolute_change' => $absoluteChange,
                'percentage_change' => null,
                'change_state' => 'new_activity',
                'comparison_label' => $comparisonLabel,
            ];
        }

        $percentageChange = round((($currentValue - $compareValue) / $compareValue) * 100, 1);

        return [
            'current_value' => $currentValue,
            'compare_value' => $compareValue,
            'absolute_change' => $absoluteChange,
            'percentage_change' => $percentageChange,
            'change_state' => $absoluteChange > 0 ? 'increased' : ($absoluteChange < 0 ? 'decreased' : 'unchanged'),
            'comparison_label' => $comparisonLabel,
        ];
    }
}
