@php
    $branchComparison = $branchRankingComparison ?? ['paginator' => null, 'period_label' => $analyticsContext->displayLabel ?? 'Tất cả thời gian', 'comparison_label' => $analyticsContext->comparisonLabel ?? 'Không so sánh', 'search' => '', 'sort' => 'revenue', 'direction' => 'desc', 'performance' => 'all', 'per_page' => 5];
    $branchPaginator = $branchComparison['paginator'] ?? null;
    $branchRows = $branchPaginator ? $branchPaginator->getCollection() : collect();
    $branchDetail = $branchProductDetail ?? ['branch' => null, 'summary' => [], 'comparison' => [], 'top_products' => collect(), 'sort_by' => 'quantity'];
    $detailBranch = $branchDetail['branch'] ?? null;
    $selectedBranchId = (int) ($branchDetailBranchId ?? ($detailBranch['id'] ?? 0));

    $rankingCompatQueryBase = request()->except('ranking_period');
    $branchQueryBase = request()->except(['branch_search', 'branch_sort', 'branch_direction', 'branch_performance', 'branch_per_page', 'branch_page']);
    $branchQueryBase['analytics_detail_branch_id'] = $selectedBranchId;
    $branchDetailQueryBase = request()->query();
    $branchDetailQueryBase['analytics_detail_branch_id'] = $selectedBranchId;

    $branchSearch = (string) request('branch_search', $branchComparison['search'] ?? '');
    $branchSort = (string) request('branch_sort', $branchComparison['sort'] ?? 'revenue');
    $branchDirection = (string) request('branch_direction', $branchComparison['direction'] ?? 'desc');
    $branchPerformance = (string) request('branch_performance', $branchComparison['performance'] ?? 'all');
    $branchTotal = $branchPaginator ? (int) $branchPaginator->total() : 0;
    $branchShowingFrom = $branchPaginator ? (int) ($branchPaginator->firstItem() ?? 0) : 0;
    $branchShowingTo = $branchPaginator ? (int) ($branchPaginator->lastItem() ?? 0) : 0;
    $branchSortOptions = [
        'revenue' => 'Doanh thu',
        'orders' => 'Đơn hàng',
        'average_order_value' => 'Trung bình/đơn',
        'items_sold' => 'Sản phẩm bán ra',
        'growth' => 'Tăng trưởng',
        'cancellation_rate' => 'Tỷ lệ hủy',
        'name' => 'Tên chi nhánh',
    ];
    $branchPerformanceOptions = [
        'all' => 'Tất cả',
        'increased' => 'Tăng trưởng',
        'decreased' => 'Giảm',
        'unchanged' => 'Không đổi',
        'new_activity' => 'Mới phát sinh',
        'no_orders' => 'Chưa có đơn',
    ];
    $summary = $branchDetail['summary'] ?? [];
    $comparison = $branchDetail['comparison'] ?? [];
    $topProducts = collect($branchDetail['top_products'] ?? []);
@endphp

<section class="sa-panel" id="branch-product-detail" data-branch-product-detail-region>
    <div class="branch-product-controls">
        <div class="branch-product-toolbar">
            <div class="branch-product-toolbar-copy">
                <h2 class="branch-product-toolbar-title">Bán chạy theo chi nhánh</h2>
                <p class="branch-product-toolbar-note">Tổng hợp chi nhánh và sản phẩm nổi bật trong kỳ đã chọn.</p>
                <div class="branch-product-toolbar-meta">
                    <span class="sa-state sa-state-active" style="background:#eafaf5; color:var(--sa-green);">{{ $branchComparison['period_label'] }}</span>
                    <span class="sa-state" style="background:#eef2ff; color:#4338ca;">{{ $branchComparison['comparison_label'] }}</span>
                    <span class="sa-state" style="background:#f8fafc; color:#334155;">{{ number_format($branchTotal) }} chi nhánh</span>
                </div>
            </div>
            <div class="branch-product-toolbar-actions">
                <div class="branch-product-period-switcher" role="tablist" aria-label="Chọn kỳ phân tích">
                    <a href="{{ route('admin.super-admin', array_merge($rankingCompatQueryBase, ['ranking_period' => 'all', 'analytics_detail_branch_id' => $selectedBranchId])) }}#branch-product-detail" data-ranking-period="all" class="sa-btn branch-product-period-link {{ $rankingPeriod === 'all' ? 'sa-btn-primary' : '' }}" style="{{ $rankingPeriod === 'all' ? '' : 'background:#fff; color:var(--sa-ink); border:1px solid transparent;' }}">Tất cả</a>
                    <a href="{{ route('admin.super-admin', array_merge($rankingCompatQueryBase, ['ranking_period' => 'week', 'analytics_detail_branch_id' => $selectedBranchId])) }}#branch-product-detail" data-ranking-period="week" class="sa-btn branch-product-period-link {{ $rankingPeriod === 'week' ? 'sa-btn-primary' : '' }}" style="{{ $rankingPeriod === 'week' ? '' : 'background:#fff; color:var(--sa-ink); border:1px solid transparent;' }}">Tuần</a>
                    <a href="{{ route('admin.super-admin', array_merge($rankingCompatQueryBase, ['ranking_period' => 'month', 'analytics_detail_branch_id' => $selectedBranchId])) }}#branch-product-detail" data-ranking-period="month" class="sa-btn branch-product-period-link {{ $rankingPeriod === 'month' ? 'sa-btn-primary' : '' }}" style="{{ $rankingPeriod === 'month' ? '' : 'background:#fff; color:var(--sa-ink); border:1px solid transparent;' }}">Tháng</a>
                    <a href="{{ route('admin.super-admin', array_merge($rankingCompatQueryBase, ['ranking_period' => 'year', 'analytics_detail_branch_id' => $selectedBranchId])) }}#branch-product-detail" data-ranking-period="year" class="sa-btn branch-product-period-link {{ $rankingPeriod === 'year' ? 'sa-btn-primary' : '' }}" style="{{ $rankingPeriod === 'year' ? '' : 'background:#fff; color:var(--sa-ink); border:1px solid transparent;' }}">Năm</a>
                </div>
                <button type="button" class="sa-btn sa-btn-primary branch-product-add-btn" data-bs-toggle="modal" data-bs-target="#createBranchModal"><i class="bi bi-plus-circle"></i> Thêm chi nhánh</button>
            </div>
        </div>
        <form method="GET" action="{{ route('admin.super-admin', $branchQueryBase) }}#branch-product-detail" class="branch-product-filterbar sa-filter-form" data-branch-ranking-form>
            <input type="hidden" name="analytics_detail_branch_id" value="{{ $selectedBranchId }}">
            <div class="branch-product-filter-field">
                <label class="branch-product-filter-label" for="branch_search">Tìm chi nhánh</label>
                <input id="branch_search" class="sa-control" type="search" name="branch_search" value="{{ $branchSearch }}" placeholder="Tên chi nhánh, mã, admin..." aria-label="Tìm chi nhánh">
            </div>
            <div class="branch-product-filter-field">
                <label class="branch-product-filter-label" for="branch_sort">Sắp xếp</label>
                <select id="branch_sort" class="sa-control" name="branch_sort" aria-label="Sắp xếp chi nhánh">
                    @foreach($branchSortOptions as $value => $label)
                        <option value="{{ $value }}" @selected($branchSort === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="branch-product-filter-field">
                <label class="branch-product-filter-label" for="branch_direction">Hướng</label>
                <select id="branch_direction" class="sa-control" name="branch_direction" aria-label="Hướng sắp xếp">
                    <option value="desc" @selected($branchDirection === 'desc')>Giảm dần</option>
                    <option value="asc" @selected($branchDirection === 'asc')>Tăng dần</option>
                </select>
            </div>
            <div class="branch-product-filter-field">
                <label class="branch-product-filter-label" for="branch_performance">Hiệu suất</label>
                <select id="branch_performance" class="sa-control" name="branch_performance" aria-label="Lọc hiệu suất">
                    @foreach($branchPerformanceOptions as $value => $label)
                        <option value="{{ $value }}" @selected($branchPerformance === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="branch-product-filter-actions">
                <a class="sa-btn" href="{{ route('admin.super-admin', array_merge($branchQueryBase, ['analytics_detail_branch_id' => $selectedBranchId, 'branch_search' => null, 'branch_sort' => null, 'branch_direction' => null, 'branch_performance' => null, 'branch_page' => null])) }}#branch-product-detail" title="Xóa bộ lọc"><i class="bi bi-arrow-counterclockwise"></i> Xóa lọc</a>
                <button class="sa-btn sa-btn-primary" type="submit"><i class="bi bi-funnel"></i> Áp dụng</button>
            </div>
        </form>
    </div>

    @if($branchRows->isNotEmpty())
        <div style="padding:0.85rem 1rem 0.35rem; display:flex; align-items:center; justify-content:space-between; gap:0.85rem; flex-wrap:wrap;">
            <div style="color:var(--sa-muted); font-size:0.78rem;">Đang hiển thị {{ $branchShowingFrom }}-{{ $branchShowingTo }} / {{ number_format($branchTotal) }} chi nhánh</div>
            <div style="display:flex; gap:0.35rem; flex-wrap:wrap;">
                <span class="sa-state" style="background:#f8fafc; color:#334155;">Kết quả: {{ number_format($branchTotal) }}</span>
                <span class="sa-state" style="background:#f8fafc; color:#334155;">Sắp xếp: {{ $branchSortOptions[$branchSort] ?? 'Doanh thu' }}</span>
            </div>
        </div>

        <div class="branch-product-layout">
            <aside class="branch-product-panel">
                <div class="branch-product-panel-header">
                    <div>
                        <h3 class="branch-product-panel-title">Danh sách chi nhánh</h3>
                        <p class="branch-product-panel-note">Chọn một chi nhánh để xem chi tiết bán chạy trong kỳ.</p>
                    </div>
                </div>
                <div class="branch-product-list-wrap">
                    <table class="branch-product-list-table">
                        <thead>
                            <tr>
                                <th style="width: 44px;">Hạng</th>
                                <th>Chi nhánh</th>
                                <th style="width: 30%;">Top 1</th>
                                <th style="width: 92px;">Doanh thu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($branchRows as $branch)
                                @php
                                    $isSelectedBranch = (int) ($branch['branch_id'] ?? 0) === $selectedBranchId;
                                    $detailUrl = route('admin.super-admin', array_merge($branchDetailQueryBase, ['analytics_detail_branch_id' => $branch['branch_id']])) . '#branch-product-detail';
                                @endphp
                                <tr @if($isSelectedBranch) style="background:#f0fdf4;" @endif>
                                    <td style="font-weight:800; color:var(--sa-green);">{{ $branch['rank'] }}</td>
                                    <td>
                                        <a href="{{ $detailUrl }}" class="branch-product-select {{ $isSelectedBranch ? 'active' : '' }}" data-branch-detail-link data-branch-id="{{ $branch['branch_id'] }}" aria-current="{{ $isSelectedBranch ? 'true' : 'false' }}">
                                            <div class="branch-product-name">{{ $branch['branch_name'] }}</div>
                                            <div class="branch-product-code">{{ $branch['branch_code'] }}</div>
                                            <div class="branch-product-subtext">
                                                @if($branch['admin_id'])
                                                    {{ $branch['admin_name'] }}
                                                @else
                                                    Chưa gán admin
                                                @endif
                                            </div>
                                        </a>
                                    </td>
                                    <td>
                                        <div class="branch-product-name">{{ $branch['top_product_name'] }}</div>
                                        <div class="branch-product-subtext">{{ number_format($branch['top_product_quantity']) }} ly</div>
                                    </td>
                                    <td style="font-weight:800; color:var(--sa-green); white-space:nowrap;">{{ number_format($branch['revenue'], 0, ',', '.') }}đ</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </aside>

            <article class="branch-product-panel branch-product-detail">
                @if($detailBranch)
                    <div class="branch-product-detail-head">
                        <div style="min-width: 0;">
                            <h3 class="branch-product-detail-title">{{ $detailBranch['name'] }}</h3>
                            <div class="branch-product-detail-meta">
                                Mã chi nhánh: <strong>{{ $detailBranch['code'] }}</strong>
                                <span style="margin:0 0.25rem;">·</span>
                                Kỳ: <strong>{{ $branchDetail['period_label'] ?? $branchComparison['period_label'] }}</strong>
                                <span style="margin:0 0.25rem;">·</span>
                                Đối chiếu: <strong>{{ $branchDetail['comparison_label'] ?? $branchComparison['comparison_label'] }}</strong>
                            </div>
                        </div>
                        <div class="branch-product-detail-chiprow">
                            @if(($detailBranch['status'] ?? false))
                                <span class="sa-state sa-state-active" style="background:#eafaf5; color:var(--sa-green);"><i class="bi bi-check-circle"></i> Hoạt động</span>
                            @else
                                <span class="sa-state" style="background:#fef2f2; color:#991b1b;"><i class="bi bi-pause-circle"></i> Tạm ngưng</span>
                            @endif
                            <span class="sa-state" style="background:#f8fafc; color:#334155;">{{ $branchDetail['comparison_label'] ?? 'Không đối chiếu' }}</span>
                        </div>
                    </div>

                    <div class="branch-product-summary-grid">
                        <div class="branch-product-summary-card">
                            <div class="branch-product-summary-label">Doanh thu</div>
                            <div class="branch-product-summary-value">{{ number_format((int) ($summary['revenue'] ?? 0), 0, ',', '.') }}đ</div>
                            <div class="branch-product-summary-note">
                                @if(($comparison['revenue_change_percentage'] ?? null) !== null)
                                    {{ (($comparison['revenue_change_percentage'] ?? 0) >= 0 ? '+' : '') . number_format((float) $comparison['revenue_change_percentage'], 1) }}% so với kỳ đối chiếu
                                @else
                                    Không đối chiếu
                                @endif
                            </div>
                        </div>
                        <div class="branch-product-summary-card">
                            <div class="branch-product-summary-label">Đơn hợp lệ</div>
                            <div class="branch-product-summary-value">{{ number_format((int) ($summary['valid_order_count'] ?? 0)) }}</div>
                            <div class="branch-product-summary-note">
                                @if(($comparison['order_change_percentage'] ?? null) !== null)
                                    {{ (($comparison['order_change_percentage'] ?? 0) >= 0 ? '+' : '') . number_format((float) $comparison['order_change_percentage'], 1) }}% so với kỳ đối chiếu
                                @else
                                    Không đối chiếu
                                @endif
                            </div>
                        </div>
                        <div class="branch-product-summary-card">
                            <div class="branch-product-summary-label">Khách hàng thành viên</div>
                            <div class="branch-product-summary-value">{{ number_format((int) ($summary['unique_customer_count'] ?? 0)) }}</div>
                            <div class="branch-product-summary-note">Bỏ qua đơn vãng lai</div>
                        </div>
                        <div class="branch-product-summary-card">
                            <div class="branch-product-summary-label">Sản phẩm bán ra</div>
                            <div class="branch-product-summary-value">{{ number_format((int) ($summary['items_sold'] ?? 0)) }}</div>
                            <div class="branch-product-summary-note">
                                @if(($comparison['items_change_percentage'] ?? null) !== null)
                                    {{ (($comparison['items_change_percentage'] ?? 0) >= 0 ? '+' : '') . number_format((float) $comparison['items_change_percentage'], 1) }}% so với kỳ đối chiếu
                                @else
                                    Không đối chiếu
                                @endif
                            </div>
                        </div>
                        <div class="branch-product-summary-card">
                            <div class="branch-product-summary-label">Trung bình/đơn</div>
                            <div class="branch-product-summary-value">{{ number_format((int) ($summary['average_order_value'] ?? 0), 0, ',', '.') }}đ</div>
                            <div class="branch-product-summary-note">
                                @if(($comparison['revenue_change_percentage'] ?? null) !== null)
                                    Điểm so với doanh thu kỳ trước
                                @else
                                    Không đối chiếu
                                @endif
                            </div>
                        </div>
                        <div class="branch-product-summary-card">
                            <div class="branch-product-summary-label">Tỷ lệ hủy</div>
                            <div class="branch-product-summary-value">{{ number_format((float) ($summary['cancellation_rate'] ?? 0), 1) }}%</div>
                            <div class="branch-product-summary-note">{{ number_format((int) ($summary['total_created_order_count'] ?? 0)) }} đơn đã tạo</div>
                        </div>
                    </div>

                    <div class="branch-product-mini-grid">
                        <div class="branch-product-mini">
                            <div class="branch-product-mini-label">Đơn đã tạo</div>
                            <div class="branch-product-mini-value">{{ number_format((int) ($summary['total_created_order_count'] ?? 0)) }}</div>
                        </div>
                        <div class="branch-product-mini">
                            <div class="branch-product-mini-label">Hoàn thành</div>
                            <div class="branch-product-mini-value">{{ number_format((int) ($summary['completed_order_count'] ?? 0)) }}</div>
                        </div>
                        <div class="branch-product-mini">
                            <div class="branch-product-mini-label">Đơn hủy</div>
                            <div class="branch-product-mini-value">{{ number_format((int) ($summary['cancelled_order_count'] ?? 0)) }}</div>
                        </div>
                    </div>

                    <div style="display:flex; align-items:center; justify-content:space-between; gap:0.8rem; flex-wrap:wrap;">
                        <div>
                            <h4 class="branch-product-panel-title" style="font-size:0.96rem;">Top 5 sản phẩm</h4>
                            <p class="branch-product-panel-note">Sắp xếp theo {{ ($branchDetail['sort_by'] ?? 'quantity') === 'revenue' ? 'doanh thu' : 'số lượng' }} bán ra trong kỳ đã chọn.</p>
                        </div>
                        <div class="branch-product-detail-chiprow">
                            <span class="sa-state" style="background:#f8fafc; color:#334155;">{{ $branchDetail['comparison_label'] ?? 'Không đối chiếu' }}</span>
                        </div>
                    </div>

                    @if($topProducts->isNotEmpty())
                        <div class="branch-product-toplist">
                            @foreach($topProducts as $topProduct)
                                @php
                                    $productGrowthState = $topProduct['change_state'] ?? 'unavailable';
                                    $productGrowthTone = match ($productGrowthState) {
                                        'increased', 'new_activity' => 'up',
                                        'decreased' => 'down',
                                        default => 'flat',
                                    };
                                    $quantityChange = $topProduct['quantity_change_percentage'];
                                    $revenueChange = $topProduct['revenue_change_percentage'];
                                @endphp
                                <div class="branch-product-toprow">
                                    <div class="branch-product-toprank">{{ $topProduct['rank'] }}</div>
                                    <div class="branch-product-thumb">
                                        @if(! empty($topProduct['product_image_url']))
                                            <img src="{{ $topProduct['product_image_url'] }}" alt="{{ $topProduct['product_name'] }}">
                                        @else
                                            <i class="bi bi-cup-straw"></i>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="branch-product-topname">{{ $topProduct['product_name'] }}</div>
                                        <div class="branch-product-topmeta">
                                            Tỷ trọng DT: {{ number_format((float) ($topProduct['revenue_share_percentage'] ?? 0), 1) }}% · Tỷ trọng SL: {{ number_format((float) ($topProduct['quantity_share_percentage'] ?? 0), 1) }}%
                                        </div>
                                    </div>
                                    <div class="branch-product-topmetric">
                                        <strong>{{ number_format((int) ($topProduct['total_quantity'] ?? 0)) }}</strong>
                                        Số lượng
                                    </div>
                                    <div class="branch-product-topmetric">
                                        <strong>{{ number_format((int) ($topProduct['total_revenue'] ?? 0), 0, ',', '.') }}đ</strong>
                                        Doanh thu
                                    </div>
                                    <div class="branch-product-topmetric">
                                        <span class="branch-product-badge {{ $productGrowthTone }}">
                                            <i class="bi {{ in_array($productGrowthState, ['increased', 'new_activity'], true) ? 'bi-arrow-up-right' : (in_array($productGrowthState, ['decreased'], true) ? 'bi-arrow-down-right' : 'bi-dash') }}"></i>
                                            @if($quantityChange === null)
                                                N/A
                                            @else
                                                {{ (($quantityChange ?? 0) >= 0 ? '+' : '') . number_format((float) $quantityChange, 1) }}%
                                            @endif
                                        </span>
                                        <div class="branch-product-topmeta" style="margin-top:0.22rem;">
                                            DT {{ $revenueChange === null ? 'N/A' : ((($revenueChange ?? 0) >= 0 ? '+' : '') . number_format((float) $revenueChange, 1).'%' ) }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="branch-product-empty-detail">
                            <div>
                                <i class="bi bi-bag-x" style="font-size:1.3rem; color:var(--sa-green);"></i>
                                <div style="margin-top:0.35rem; color:var(--sa-ink); font-weight:850;">Chi nhánh chưa bán sản phẩm nào trong kỳ này.</div>
                                <div style="margin-top:0.2rem; font-size:0.8rem;">Không có dữ liệu sản phẩm phù hợp để hiển thị.</div>
                            </div>
                        </div>
                    @endif
                @else
                    <div class="branch-product-empty-detail">
                        <div>
                            <i class="bi bi-shop" style="font-size:1.3rem; color:var(--sa-green);"></i>
                            <div style="margin-top:0.35rem; color:var(--sa-ink); font-weight:850;">Chưa có chi nhánh phù hợp.</div>
                            <div style="margin-top:0.2rem; font-size:0.8rem;">Không thể chọn chi nhánh chi tiết từ dữ liệu hiện tại.</div>
                        </div>
                    </div>
                @endif
            </article>
        </div>

        <div class="sa-pagination">
            <span>Hiển thị {{ $branchShowingFrom }}-{{ $branchShowingTo }} / {{ number_format($branchTotal) }}</span>
            <div class="sa-page-links" aria-label="Phân trang chi nhánh">
                @php
                    $branchPageBase = array_merge($branchQueryBase, ['analytics_detail_branch_id' => $selectedBranchId]);
                @endphp
                <a class="sa-page-link {{ $branchPaginator->onFirstPage() ? 'disabled' : '' }}" href="{{ $branchPaginator->onFirstPage() ? '#' : route('admin.super-admin', array_merge($branchPageBase, ['branch_page' => $branchPaginator->currentPage() - 1])) . '#branch-product-detail' }}" aria-label="Trang trước"><i class="bi bi-chevron-left"></i></a>
                @foreach(range(1, max(1, $branchPaginator->lastPage())) as $page)
                    <a class="sa-page-link {{ $page === $branchPaginator->currentPage() ? 'active' : '' }}" href="{{ route('admin.super-admin', array_merge($branchPageBase, ['branch_page' => $page])) }}#branch-product-detail">{{ $page }}</a>
                @endforeach
                <a class="sa-page-link {{ $branchPaginator->hasMorePages() ? '' : 'disabled' }}" href="{{ $branchPaginator->hasMorePages() ? route('admin.super-admin', array_merge($branchPageBase, ['branch_page' => $branchPaginator->currentPage() + 1])) . '#branch-product-detail' : '#' }}" aria-label="Trang sau"><i class="bi bi-chevron-right"></i></a>
            </div>
        </div>

        @foreach($branchRows as $branch)
            @php
                $branchStatusValue = (int) ($branch['branch_status'] ?? 0);
                $isEditingThisBranch = (string) old('branch_modal_id') === (string) $branch['branch_id'];
            @endphp
            <div class="modal fade" id="branchEditModal{{ $branch['branch_id'] }}" tabindex="-1" aria-labelledby="branchEditModalLabel{{ $branch['branch_id'] }}" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <form class="modal-content branch-edit-form" method="POST" action="{{ route('admin.branches.update', ['branch' => $branch['branch_id']], false) }}" data-branch-id="{{ $branch['branch_id'] }}" style="border:0;border-radius:8px;">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="form_type" value="branch-edit">
                        <input type="hidden" name="branch_modal_id" value="{{ $branch['branch_id'] }}">
                        <div class="modal-header">
                            <h2 class="modal-title fs-6 fw-bold" id="branchEditModalLabel{{ $branch['branch_id'] }}">Sửa chi nhánh</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-danger d-none" role="alert" data-branch-edit-errors="{{ $branch['branch_id'] }}" style="font-size: 0.8rem;"></div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold" for="branch_name_{{ $branch['branch_id'] }}">Tên chi nhánh <span class="text-danger">*</span></label>
                                    <input id="branch_name_{{ $branch['branch_id'] }}" class="form-control @error('name', 'editBranch') is-invalid @enderror" name="name" value="{{ $isEditingThisBranch ? old('name', $branch['branch_name']) : $branch['branch_name'] }}" placeholder="Nhập tên chi nhánh" required>
                                    @error('name', 'editBranch')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold" for="branch_code_{{ $branch['branch_id'] }}">Mã chi nhánh <span class="text-danger">*</span></label>
                                    <input id="branch_code_{{ $branch['branch_id'] }}" class="form-control @error('code', 'editBranch') is-invalid @enderror" name="code" value="{{ $isEditingThisBranch ? old('code', $branch['branch_code']) : $branch['branch_code'] }}" placeholder="VD: CN1, CN2" required>
                                    @error('code', 'editBranch')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold" for="branch_email_{{ $branch['branch_id'] }}">Email</label>
                                    <input id="branch_email_{{ $branch['branch_id'] }}" class="form-control @error('email', 'editBranch') is-invalid @enderror" type="email" name="email" value="{{ $isEditingThisBranch ? old('email', $branch['branch_email'] ?? null) : ($branch['branch_email'] ?? null) }}" placeholder="branch@example.com">
                                    @error('email', 'editBranch')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold" for="branch_phone_{{ $branch['branch_id'] }}">Điện thoại</label>
                                    <input id="branch_phone_{{ $branch['branch_id'] }}" class="form-control @error('phone', 'editBranch') is-invalid @enderror" type="text" name="phone" value="{{ $isEditingThisBranch ? old('phone', $branch['branch_phone'] ?? null) : ($branch['branch_phone'] ?? null) }}" placeholder="0123456789">
                                    @error('phone', 'editBranch')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold" for="branch_address_{{ $branch['branch_id'] }}">Địa chỉ</label>
                                <textarea id="branch_address_{{ $branch['branch_id'] }}" class="form-control @error('address', 'editBranch') is-invalid @enderror" name="address" rows="2" placeholder="Nhập địa chỉ chi nhánh">{{ $isEditingThisBranch ? old('address', $branch['branch_address'] ?? null) : ($branch['branch_address'] ?? null) }}</textarea>
                                @error('address', 'editBranch')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            @include('admin.partials.branch-map-link', [
                                'pickerId' => 'super-admin-edit-branch-map-link-'.$branch['branch_id'],
                                'label' => 'Link Google Maps',
                                'hint' => 'Dán link Google Maps có chứa tọa độ để cập nhật latitude/longitude cho chi nhánh.',
                                'addressTarget' => '#branch_address_'.$branch['branch_id'],
                                'latName' => 'latitude',
                                'lngName' => 'longitude',
                                'latValue' => $isEditingThisBranch ? old('latitude', $branch['branch_latitude'] ?? null) : ($branch['branch_latitude'] ?? null),
                                'lngValue' => $isEditingThisBranch ? old('longitude', $branch['branch_longitude'] ?? null) : ($branch['branch_longitude'] ?? null),
                                'mapLinkValue' => (!is_null($branch['branch_latitude'] ?? null) && !is_null($branch['branch_longitude'] ?? null)) ? 'https://www.google.com/maps?q='.($branch['branch_latitude'] ?? '').','.($branch['branch_longitude'] ?? '') : '',
                                'errorBag' => 'editBranch',
                            ])
                            <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                                <input type="hidden" name="status" value="{{ $branchStatusValue ? 1 : 0 }}" data-branch-status-input="{{ $branch['branch_id'] }}">
                                <button
                                    type="button"
                                    class="btn btn-sm px-3 fw-semibold {{ $branchStatusValue ? 'btn-success' : 'btn-danger' }}"
                                    data-branch-status-toggle="{{ $branch['branch_id'] }}"
                                >
                                    <i class="bi bi-{{ $branchStatusValue ? 'toggle-on' : 'toggle-off' }} me-1"></i>
                                    <span data-branch-status-label="{{ $branch['branch_id'] }}">{{ $branchStatusValue ? 'Đóng chi nhánh' : 'Mở chi nhánh' }}</span>
                                </button>
                                <small class="text-secondary">Nhấn để đổi trạng thái chi nhánh</small>
                            </div>
                        </div>
                        <div class="modal-footer" style="gap: 0.75rem;">
                            <button type="button" class="sa-btn" data-bs-dismiss="modal">Hủy</button>
                            <button type="submit" class="sa-btn sa-btn-primary" style="min-width: 160px; background: var(--sa-green); color: #fff; border-color: var(--sa-green);">
                                <i class="bi bi-gear"></i>Lưu thay đổi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endforeach
    @else
        <div class="branch-product-empty-detail" style="margin: 0.85rem;">
            <div>
                <i class="bi bi-shop" style="font-size:1.3rem; color:var(--sa-green);"></i>
                <div style="margin-top:0.35rem; color:var(--sa-ink); font-weight:850;">Chưa có chi nhánh phù hợp.</div>
                <div style="margin-top:0.2rem; font-size:0.78rem;">Không có chi nhánh nào khớp với bộ lọc hiện tại.</div>
            </div>
        </div>
    @endif
</section>
