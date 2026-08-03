@php
    $focusPerformance = $focusProductPerformance ?? [
        'product' => ['id' => null, 'name' => null, 'image' => null, 'image_url' => null, 'status' => false, 'is_deleted' => false, 'sku' => null],
        'summary' => [
            'total_quantity' => 0,
            'total_revenue' => 0,
            'branches_with_sales' => 0,
            'total_branches_in_scope' => \Illuminate\Support\Facades\Schema::hasTable('branches') ? \App\Models\Branch::count() : 0,
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
            'comparison_label' => $analyticsContext->comparisonLabel ?? 'Không đối chiếu',
        ],
        'branches' => collect(),
        'pagination' => [
            'current_page' => 1,
            'per_page' => 10,
            'total' => 0,
            'last_page' => 1,
        ],
        'paginator' => null,
        'sort_by' => 'quantity',
        'search' => '',
    ];

    $focusProduct = $focusPerformance['product'] ?? [];
    $focusSummary = $focusPerformance['summary'] ?? [];
    $focusComparison = $focusPerformance['comparison'] ?? [];
    $focusPaginator = $focusPerformance['paginator'] ?? null;
    $focusBranches = $focusPaginator ? $focusPaginator->getCollection() : collect($focusPerformance['branches'] ?? []);
    $focusCandidates = collect($focusProductCandidates ?? []);

    $focusSelectedProductId = (int) ($focusProduct['id'] ?? 0);
    $focusProductSort = (string) ($focusProductSort ?? ($focusPerformance['sort_by'] ?? 'quantity'));
    $focusProductQuery = (string) ($focusProductQuery ?? request('analytics_focus_product_query', $focusProduct['name'] ?? ''));
    $focusBranchSearch = (string) request('analytics_focus_branch_search', $focusPerformance['search'] ?? '');
    $focusBranchPage = (int) request('analytics_focus_branch_page', $focusPerformance['pagination']['current_page'] ?? 1);

    $focusQueryBase = request()->except([
        'analytics_focus_product_query',
        'analytics_focus_product_id',
        'analytics_focus_product_sort',
        'analytics_focus_branch_search',
        'analytics_focus_branch_page',
    ]);

    $focusCurrentQuery = $focusQueryBase;
    if ($focusSelectedProductId > 0) {
        $focusCurrentQuery['analytics_focus_product_id'] = $focusSelectedProductId;
    }
    if ($focusProductQuery !== '') {
        $focusCurrentQuery['analytics_focus_product_query'] = $focusProductQuery;
    }
    $focusCurrentQuery['analytics_focus_product_sort'] = $focusProductSort;
    if ($focusBranchSearch !== '') {
        $focusCurrentQuery['analytics_focus_branch_search'] = $focusBranchSearch;
    }
    if ($focusBranchPage > 1) {
        $focusCurrentQuery['analytics_focus_branch_page'] = $focusBranchPage;
    }

    $focusSelectorSearchUrl = route('admin.super-admin', array_merge($focusQueryBase, [
        'analytics_focus_product_query' => $focusProductQuery,
        'analytics_focus_product_id' => null,
        'analytics_focus_product_sort' => $focusProductSort,
        'analytics_focus_branch_search' => $focusBranchSearch !== '' ? $focusBranchSearch : null,
        'analytics_focus_branch_page' => 1,
    ]));

    $focusSortLinks = [
        'quantity' => route('admin.super-admin', array_merge($focusCurrentQuery, ['analytics_focus_product_sort' => 'quantity'])) . '#focus-product-section',
        'revenue' => route('admin.super-admin', array_merge($focusCurrentQuery, ['analytics_focus_product_sort' => 'revenue'])) . '#focus-product-section',
    ];

    $focusBranchFilterUrl = route('admin.super-admin', array_merge($focusCurrentQuery, [
        'analytics_focus_product_sort' => $focusProductSort,
        'analytics_focus_branch_page' => 1,
    ]));

    $focusClearBranchUrl = route('admin.super-admin', array_merge($focusCurrentQuery, [
        'analytics_focus_branch_search' => null,
        'analytics_focus_branch_page' => null,
    ]));

    $selectedProductLabel = filled($focusProduct['name'] ?? null) ? (string) $focusProduct['name'] : 'Chưa chọn sản phẩm';
    $selectedProductStatus = $focusProduct['is_deleted'] ?? false
        ? 'Đã xóa'
        : (($focusProduct['status'] ?? false) ? 'Đang bán' : 'Ngừng bán');
    $selectedProductSummary = [
        'Tổng số lượng' => number_format((int) ($focusSummary['total_quantity'] ?? 0)),
        'Doanh thu' => number_format((int) ($focusSummary['total_revenue'] ?? 0), 0, ',', '.').'đ',
        'Chi nhánh có bán' => number_format((int) ($focusSummary['branches_with_sales'] ?? 0)).'/'.number_format((int) ($focusSummary['total_branches_in_scope'] ?? 0)),
        'Chi nhánh mạnh nhất' => filled($focusSummary['strongest_branch_name'] ?? null) ? (string) $focusSummary['strongest_branch_name'] : 'Chưa xác định',
        'Đối chiếu' => $focusComparison['comparison_label'] ?? 'Không đối chiếu',
    ];
    $focusBranchTotal = $focusPaginator ? (int) $focusPaginator->total() : (int) ($focusPerformance['pagination']['total'] ?? 0);
    $focusBranchShowingFrom = $focusPaginator ? (int) ($focusPaginator->firstItem() ?? 0) : 0;
    $focusBranchShowingTo = $focusPaginator ? (int) ($focusPaginator->lastItem() ?? 0) : 0;
    $selectedProductName = filled($focusProduct['name'] ?? null) ? (string) $focusProduct['name'] : 'Sản phẩm';
@endphp

<section class="sa-panel" id="focus-product-section" data-product-branch-performance-region>
    <div class="focus-product-panel-header">
        <div style="min-width: 0;">
            <h2 class="focus-product-panel-title">Một món bán tốt ở đâu?</h2>
            <p class="focus-product-panel-note">Chọn một sản phẩm để xem chi nhánh nào bán mạnh nhất trong kỳ đang chọn. Bộ lọc giữ nguyên theo cùng trang tổng quan.</p>
            <div class="focus-product-selected-chiprow">
                <span class="sa-state sa-state-active" style="background:#eafaf5; color:var(--sa-green);">{{ $analyticsContext->displayLabel ?? 'Tất cả thời gian' }}</span>
                <span class="sa-state" style="background:#eef2ff; color:#4338ca;">{{ $focusComparison['comparison_label'] ?? 'Không đối chiếu' }}</span>
                <span class="sa-state" style="background:#f8fafc; color:#334155;">{{ number_format($focusBranchTotal) }} chi nhánh</span>
            </div>
        </div>
        <div class="focus-product-actions">
            <span class="sa-state" style="background:#fff7ed; color:#9a3412;">{{ $selectedProductStatus }}</span>
            <span class="sa-state" style="background:#f8fafc; color:#334155;">{{ $focusProductSort === 'revenue' ? 'Theo doanh thu' : 'Theo số lượng' }}</span>
        </div>
    </div>

    <div class="focus-product-layout">
        <aside class="focus-product-panel">
            <div class="focus-product-panel-body">
                <form method="GET" action="{{ route('admin.super-admin') }}" class="focus-product-form" data-product-branch-performance-form>
                    @foreach($focusQueryBase as $key => $value)
                        @if(is_array($value))
                            @foreach($value as $arrayValue)
                                <input type="hidden" name="{{ $key }}[]" value="{{ $arrayValue }}">
                            @endforeach
                        @elseif($value !== null && $value !== '')
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endforeach
                    <input type="hidden" name="analytics_focus_product_sort" value="{{ $focusProductSort }}">
                    <input type="hidden" name="analytics_focus_product_id" value="{{ $focusSelectedProductId > 0 ? $focusSelectedProductId : '' }}">
                    <input type="hidden" name="analytics_focus_branch_search" value="{{ $focusBranchSearch }}">
                    <input type="hidden" name="analytics_focus_branch_page" value="{{ $focusBranchPage }}">
                    <div class="focus-product-form-row">
                        <div class="focus-product-search">
                            <label class="sa-panel-note" style="display:block; margin:0 0 0.35rem; font-weight:800; color:var(--sa-ink);">Tìm sản phẩm</label>
                            <input
                                class="sa-control"
                                type="search"
                                name="analytics_focus_product_query"
                                value="{{ $focusProductQuery }}"
                                placeholder="Nhập tên hoặc SKU..."
                                aria-label="Tìm sản phẩm"
                            >
                        </div>
                        <button type="submit" class="sa-btn sa-btn-primary" style="min-width: 104px;"><i class="bi bi-search"></i> Tìm</button>
                    </div>
                </form>

                <div class="focus-product-selected">
                    <div class="focus-product-selected-head">
                        <div style="min-width: 0;">
                            <div class="focus-product-selected-title">{{ $selectedProductLabel }}</div>
                            <div class="focus-product-selected-subtitle">
                                @if(($focusProduct['sku'] ?? null))
                                    SKU: <strong>{{ $focusProduct['sku'] }}</strong>
                                    <span style="margin:0 0.25rem;">·</span>
                                @endif
                                <span>{{ $focusProduct['is_deleted'] ?? false ? 'Đã xóa' : (($focusProduct['status'] ?? false) ? 'Đang bán' : 'Ngừng bán') }}</span>
                            </div>
                        </div>
                        @if(($focusProduct['image_url'] ?? null))
                            <div class="focus-product-candidate-thumb" style="width: 48px; height: 48px; border-radius: 12px;">
                                <img src="{{ $focusProduct['image_url'] }}" alt="{{ $selectedProductLabel }}" loading="lazy">
                            </div>
                        @else
                            <span class="focus-product-candidate-thumb" style="width: 48px; height: 48px; border-radius: 12px;"><i class="bi bi-cup-straw"></i></span>
                        @endif
                    </div>
                    <div class="focus-product-selected-stats" aria-label="Thông tin sản phẩm đã chọn">
                        <div class="focus-product-selected-stat">
                            <div class="focus-product-selected-stat-label">Doanh thu</div>
                            <div class="focus-product-selected-stat-value">{{ number_format((int) ($focusSummary['total_revenue'] ?? 0), 0, ',', '.') }}đ</div>
                        </div>
                        <div class="focus-product-selected-stat">
                            <div class="focus-product-selected-stat-label">Bán được</div>
                            <div class="focus-product-selected-stat-value">{{ number_format((int) ($focusSummary['total_quantity'] ?? 0)) }} cốc</div>
                        </div>
                    </div>
                    <div class="focus-product-selected-chiprow">
                        <span class="sa-state" style="background:#ecfdf5; color:#15803d;">{{ number_format((int) ($focusSummary['total_quantity'] ?? 0)) }} sản phẩm</span>
                        <span class="sa-state" style="background:#eefbf7; color:#0d9373;">{{ number_format((int) ($focusSummary['total_revenue'] ?? 0), 0, ',', '.') }}đ</span>
                        <span class="sa-state" style="background:#f8fafc; color:#334155;">{{ number_format((int) ($focusSummary['branches_with_sales'] ?? 0)) }}/{{ number_format((int) ($focusSummary['total_branches_in_scope'] ?? 0)) }} chi nhánh</span>
                    </div>
                </div>

                <div class="focus-product-candidate-list">
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:0.75rem; flex-wrap:wrap;">
                        <div>
                            <h3 class="focus-product-panel-title" style="font-size:0.92rem;">Gợi ý sản phẩm</h3>
                            <p class="focus-product-panel-note">Kết quả được giới hạn để giữ giao diện gọn.</p>
                        </div>
                        <span class="sa-state" style="background:#f8fafc; color:#334155;">{{ number_format($focusCandidates->count()) }} gợi ý</span>
                    </div>

                    @forelse($focusCandidates as $candidate)
                        @php
                            $candidateQuery = array_filter(array_merge($focusCurrentQuery, [
                                'analytics_focus_product_id' => $candidate['id'],
                                'analytics_focus_product_query' => null,
                                'analytics_focus_product_sort' => $focusProductSort,
                                'analytics_focus_branch_search' => $focusBranchSearch !== '' ? $focusBranchSearch : null,
                                'analytics_focus_branch_page' => 1,
                            ]), static fn ($value) => $value !== null && $value !== '');
                            $candidateUrl = route('admin.super-admin', $candidateQuery) . '#focus-product-section';
                        @endphp
                        <a href="{{ $candidateUrl }}" class="focus-product-candidate {{ (int) ($candidate['id'] ?? 0) === $focusSelectedProductId ? 'active' : '' }}" data-focus-product-link>
                            <div class="focus-product-candidate-thumb">
                                @if(!empty($candidate['image_url']))
                                    <img src="{{ $candidate['image_url'] }}" alt="{{ $candidate['name'] }}" loading="lazy">
                                @else
                                    <i class="bi bi-cup-straw"></i>
                                @endif
                            </div>
                            <div style="min-width:0;">
                                <h4 class="focus-product-candidate-name">{{ $candidate['name'] }}</h4>
                                <div class="focus-product-candidate-meta">
                                    @if(!empty($candidate['sku']))
                                        <span>SKU: {{ $candidate['sku'] }}</span>
                                    @endif
                                    <span>{{ $candidate['status_label'] }}</span>
                                </div>
                            </div>
                            <span class="focus-product-candidate-status">{{ $candidate['is_deleted'] ? 'Đã xóa' : ($candidate['status'] ? 'Đang bán' : 'Ngừng bán') }}</span>
                        </a>
                    @empty
                        <div class="focus-product-empty">
                            Không có kết quả phù hợp. Hãy thử từ khóa khác hoặc chọn từ sản phẩm đang hiển thị.
                        </div>
                    @endforelse
                </div>
            </div>
        </aside>

        <article class="focus-product-panel">
            <div class="focus-product-panel-header">
                <div style="min-width: 0;">
                    <h3 class="focus-product-panel-title" style="font-size:0.9rem;">Hiệu suất theo chi nhánh</h3>
                    <p class="focus-product-panel-note">Xếp hạng theo {{ $focusProductSort === 'revenue' ? 'doanh thu' : 'số lượng' }} của sản phẩm đã chọn.</p>
                </div>
                <div class="focus-product-actions">
                    <span class="sa-state" style="background:#f8fafc; color:#334155;">{{ $selectedProductLabel }}</span>
                    <span class="sa-state" style="background:#f8fafc; color:#334155;">{{ number_format((int) ($focusSummary['branches_with_sales'] ?? 0)) }} chi nhánh có bán</span>
                </div>
            </div>

            <div class="focus-product-controls">
                <div class="focus-product-sort" role="tablist" aria-label="Chọn kiểu xếp hạng sản phẩm">
                    <a href="{{ $focusSortLinks['quantity'] }}" class="focus-product-sort-link {{ $focusProductSort === 'quantity' ? 'active' : '' }}" data-focus-product-sort="quantity">Theo số lượng</a>
                    <a href="{{ $focusSortLinks['revenue'] }}" class="focus-product-sort-link {{ $focusProductSort === 'revenue' ? 'active' : '' }}" data-focus-product-sort="revenue">Theo doanh thu</a>
                </div>
                <div class="focus-product-actions">
                    <span class="sa-state" style="background:#eafaf5; color:var(--sa-green);">
                        Mạnh nhất: {{ $focusSummary['strongest_branch_name'] ?? 'Chưa xác định' }}
                    </span>
                    <span class="sa-state" style="background:#fff7ed; color:#9a3412;">
                        {{ $focusComparison['comparison_label'] ?? 'Không đối chiếu' }}
                    </span>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.super-admin') }}" class="focus-product-searchbar" data-product-branch-performance-form>
                @foreach($focusCurrentQuery as $key => $value)
                    @if($key !== 'analytics_focus_branch_search' && $key !== 'analytics_focus_branch_page' && $key !== 'analytics_focus_product_query')
                        @if(is_array($value))
                            @foreach($value as $arrayValue)
                                <input type="hidden" name="{{ $key }}[]" value="{{ $arrayValue }}">
                            @endforeach
                        @elseif($value !== null && $value !== '')
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endif
                @endforeach
                <input type="hidden" name="analytics_focus_product_id" value="{{ $focusSelectedProductId > 0 ? $focusSelectedProductId : '' }}">
                <input type="hidden" name="analytics_focus_product_sort" value="{{ $focusProductSort }}">
                <input type="hidden" name="analytics_focus_product_query" value="{{ $focusProductQuery }}">
                <input type="hidden" name="analytics_focus_branch_page" value="1">
                <div>
                    <label class="sa-panel-note" style="display:block; margin:0 0 0.35rem; font-weight:800; color:var(--sa-ink);">Tìm chi nhánh</label>
                    <input class="sa-control" type="search" name="analytics_focus_branch_search" value="{{ $focusBranchSearch }}" placeholder="Tên chi nhánh hoặc mã..." aria-label="Tìm chi nhánh trong bảng xếp hạng">
                </div>
                <div class="focus-product-actions">
                    <button type="submit" class="sa-btn sa-btn-primary"><i class="bi bi-filter"></i> Lọc</button>
                    <a class="sa-btn" href="{{ $focusClearBranchUrl }}#focus-product-section"><i class="bi bi-arrow-counterclockwise"></i> Xóa lọc</a>
                </div>
            </form>

            <div class="focus-product-summary-grid">
                <article class="focus-product-summary-card">
                    <div class="focus-product-summary-label">Tổng số lượng</div>
                    <div class="focus-product-summary-value">{{ number_format((int) ($focusSummary['total_quantity'] ?? 0)) }}</div>
                    <div class="focus-product-summary-note">
                        @if(($focusComparison['quantity_change_percentage'] ?? null) !== null)
                            {{ (($focusComparison['quantity_change_percentage'] ?? 0) >= 0 ? '+' : '') . number_format((float) $focusComparison['quantity_change_percentage'], 1) }}% so với {{ $focusComparison['comparison_label'] ?? 'kỳ đối chiếu' }}
                        @else
                            Không đối chiếu
                        @endif
                    </div>
                </article>
                <article class="focus-product-summary-card">
                    <div class="focus-product-summary-label">Tổng doanh thu</div>
                    <div class="focus-product-summary-value">{{ number_format((int) ($focusSummary['total_revenue'] ?? 0), 0, ',', '.') }}đ</div>
                    <div class="focus-product-summary-note">
                        @if(($focusComparison['revenue_change_percentage'] ?? null) !== null)
                            {{ (($focusComparison['revenue_change_percentage'] ?? 0) >= 0 ? '+' : '') . number_format((float) $focusComparison['revenue_change_percentage'], 1) }}% so với {{ $focusComparison['comparison_label'] ?? 'kỳ đối chiếu' }}
                        @else
                            Không đối chiếu
                        @endif
                    </div>
                </article>
                <article class="focus-product-summary-card">
                    <div class="focus-product-summary-label">Chi nhánh có bán</div>
                    <div class="focus-product-summary-value">{{ number_format((int) ($focusSummary['branches_with_sales'] ?? 0)) }}/{{ number_format((int) ($focusSummary['total_branches_in_scope'] ?? 0)) }}</div>
                    <div class="focus-product-summary-note">Đếm trên phạm vi đang lọc</div>
                </article>
                <article class="focus-product-summary-card">
                    <div class="focus-product-summary-label">Chi nhánh mạnh nhất</div>
                    <div class="focus-product-summary-value">{{ $focusSummary['strongest_branch_name'] ?? 'Chưa xác định' }}</div>
                    <div class="focus-product-summary-note">{{ number_format((int) ($focusSummary['strongest_branch_quantity'] ?? 0)) }} sản phẩm · {{ number_format((int) ($focusSummary['strongest_branch_revenue'] ?? 0), 0, ',', '.') }}đ</div>
                </article>
                <article class="focus-product-summary-card">
                    <div class="focus-product-summary-label">Đối chiếu</div>
                    <div class="focus-product-summary-value">{{ $focusComparison['comparison_label'] ?? 'Không đối chiếu' }}</div>
                    <div class="focus-product-summary-note">So sánh theo bộ lọc hiện tại</div>
                </article>
            </div>

            <div class="focus-product-branch-list">
                <div style="display:flex; align-items:center; justify-content:space-between; gap:0.75rem; flex-wrap:wrap; padding:0 0 0.1rem;">
                    <div style="color:var(--sa-muted); font-size:0.78rem;">
                        Đang hiển thị {{ $focusBranchShowingFrom }}-{{ $focusBranchShowingTo }} / {{ number_format($focusBranchTotal) }} chi nhánh
                    </div>
                    <div style="display:flex; gap:0.35rem; flex-wrap:wrap;">
                        <span class="sa-state" style="background:#f8fafc; color:#334155;">Sắp xếp: {{ $focusProductSort === 'revenue' ? 'Doanh thu' : 'Số lượng' }}</span>
                        <span class="sa-state" style="background:#f8fafc; color:#334155;">{{ number_format((int) ($focusSummary['branches_with_sales'] ?? 0)) }} chi nhánh có bán</span>
                    </div>
                </div>

                @forelse($focusBranches as $branch)
                    @php
                        $isTopBranch = (int) ($branch['branch_id'] ?? 0) === (int) ($focusSummary['strongest_branch_id'] ?? 0);
                        $changeState = (string) ($branch['quantity_change_state'] ?? 'unavailable');
                        $changeValue = $focusProductSort === 'revenue'
                            ? (float) ($branch['revenue_change_percentage'] ?? 0)
                            : (float) ($branch['quantity_change_percentage'] ?? 0);
                        $changeLabel = $changeState === 'unavailable'
                            ? 'Không đối chiếu'
                            : (($changeValue >= 0 ? '+' : '') . number_format($changeValue, 1).'%');
                        $changeTone = in_array($changeState, ['increased', 'new_activity'], true)
                            ? 'up'
                            : (in_array($changeState, ['decreased'], true) ? 'down' : 'flat');
                    @endphp
                    <article class="focus-product-branch-card {{ $isTopBranch ? 'top' : '' }}">
                        <span class="focus-product-branch-rank">{{ $branch['rank'] }}</span>
                        <div style="min-width:0;">
                            <h4 class="focus-product-branch-name">{{ $branch['branch_name'] }}</h4>
                            <div class="focus-product-branch-meta">
                                @if($branch['branch_code'])
                                    <span>Mã: {{ $branch['branch_code'] }}</span>
                                @endif
                                <span>{{ $branch['branch_status'] ? 'Hoạt động' : 'Tạm ngưng' }}</span>
                                <span>{{ $focusComparison['comparison_label'] ?? 'Không đối chiếu' }}</span>
                            </div>
                        </div>
                        <div class="focus-product-branch-stats">
                            <div class="focus-product-branch-stat">
                                <div class="focus-product-branch-stat-label">Số lượng</div>
                                <div class="focus-product-branch-stat-value">{{ number_format((int) ($branch['total_quantity'] ?? 0)) }}</div>
                            </div>
                            <div class="focus-product-branch-stat">
                                <div class="focus-product-branch-stat-label">Doanh thu</div>
                                <div class="focus-product-branch-stat-value">{{ number_format((int) ($branch['total_revenue'] ?? 0), 0, ',', '.') }}đ</div>
                            </div>
                            <div class="focus-product-branch-stat">
                                <div class="focus-product-branch-stat-label">Tỷ trọng SL</div>
                                <div class="focus-product-branch-stat-value">{{ number_format((float) ($branch['quantity_share_percentage'] ?? 0), 1, ',', '.') }}%</div>
                            </div>
                            <div class="focus-product-branch-stat">
                                <div class="focus-product-branch-stat-label">Tỷ trọng DT</div>
                                <div class="focus-product-branch-stat-value">{{ number_format((float) ($branch['revenue_share_percentage'] ?? 0), 1, ',', '.') }}%</div>
                            </div>
                        </div>
                        <div class="focus-product-branch-badges">
                            <span class="focus-product-branch-badge {{ $changeTone }}">
                                <i class="bi {{ $changeTone === 'up' ? 'bi-arrow-up-right' : ($changeTone === 'down' ? 'bi-arrow-down-right' : 'bi-dash') }}"></i>
                                {{ $changeLabel }}
                            </span>
                            <span class="focus-product-branch-badge muted">
                                {{ $branch['branch_status'] ? 'Hoạt động' : 'Tạm ngưng' }}
                            </span>
                        </div>
                    </article>
                @empty
                    <div class="focus-product-empty">
                        Chưa có chi nhánh phù hợp hoặc sản phẩm này chưa phát sinh dữ liệu trong kỳ đã chọn.
                    </div>
                @endforelse
            </div>

            @if($focusPaginator && $focusPaginator->lastPage() > 1)
                <div class="focus-product-pagination">
                    <span>Hiển thị {{ $focusBranchShowingFrom }}-{{ $focusBranchShowingTo }} / {{ number_format($focusBranchTotal) }}</span>
                    <div class="sa-page-links" aria-label="Phân trang chi nhánh sản phẩm">
                        <a class="sa-page-link {{ $focusPaginator->onFirstPage() ? 'disabled' : '' }}" href="{{ $focusPaginator->previousPageUrl() ?? '#' }}" aria-label="Trang trước"><i class="bi bi-chevron-left"></i></a>
                        @foreach(range(1, max(1, $focusPaginator->lastPage())) as $page)
                            <a class="sa-page-link {{ $page === $focusPaginator->currentPage() ? 'active' : '' }}" href="{{ $focusPaginator->url($page) }}">{{ $page }}</a>
                        @endforeach
                        <a class="sa-page-link {{ $focusPaginator->hasMorePages() ? '' : 'disabled' }}" href="{{ $focusPaginator->nextPageUrl() ?? '#' }}" aria-label="Trang sau"><i class="bi bi-chevron-right"></i></a>
                    </div>
                </div>
            @endif
        </article>
    </div>
</section>
