@php
    $branchComparison = $branchRankingComparison ?? ['paginator' => null, 'period_label' => $analyticsContext->displayLabel ?? 'Tất cả thời gian', 'comparison_label' => $analyticsContext->comparisonLabel ?? 'Không so sánh', 'search' => '', 'sort' => 'revenue', 'direction' => 'desc', 'performance' => 'all', 'per_page' => 5];
    $branchPaginator = $branchComparison['paginator'] ?? null;
    $branchRows = $branchPaginator ? $branchPaginator->getCollection() : collect();
    $rankingCompatQueryBase = request()->except('ranking_period');
    $branchQueryBase = request()->except(['branch_search', 'branch_sort', 'branch_direction', 'branch_performance', 'branch_per_page', 'branch_page']);
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
@endphp

<section class="sa-panel" id="branch-ranking" data-branch-ranking-region>
    <div class="sa-panel-header sa-branch-compare-header">
        <div class="sa-branch-compare-header-copy">
            <h2 class="sa-branch-compare-title">Lọc trong bảng chi nhánh</h2>
            <p class="sa-panel-note" style="margin: 0.25rem 0 0;">Chỉ áp dụng cho bảng này.</p>
            <div class="sa-branch-compare-meta">
                <span class="sa-state sa-state-active" style="background:#eafaf5; color:var(--sa-green);">{{ $branchComparison['period_label'] }}</span>
                <span class="sa-state" style="background:#eef2ff; color:#4338ca;">{{ $branchComparison['comparison_label'] }}</span>
                <span class="sa-state" style="background:#f8fafc; color:#334155;">{{ number_format($branchTotal) }} chi nhánh</span>
            </div>
        </div>
        <div class="sa-branch-compare-tools">
            <div class="sa-branch-period-switcher">
                <a href="{{ route('admin.super-admin', array_merge($rankingCompatQueryBase, ['ranking_period' => 'all'])) }}#branch-ranking" data-ranking-period="all" class="sa-btn sa-branch-period-link {{ $rankingPeriod === 'all' ? 'sa-btn-primary' : '' }}" style="{{ $rankingPeriod === 'all' ? '' : 'background:#fff; color:var(--sa-ink); border:1px solid transparent;' }}">Tất cả</a>
                <a href="{{ route('admin.super-admin', array_merge($rankingCompatQueryBase, ['ranking_period' => 'week'])) }}#branch-ranking" data-ranking-period="week" class="sa-btn sa-branch-period-link {{ $rankingPeriod === 'week' ? 'sa-btn-primary' : '' }}" style="{{ $rankingPeriod === 'week' ? '' : 'background:#fff; color:var(--sa-ink); border:1px solid transparent;' }}">Tuần</a>
                <a href="{{ route('admin.super-admin', array_merge($rankingCompatQueryBase, ['ranking_period' => 'month'])) }}#branch-ranking" data-ranking-period="month" class="sa-btn sa-branch-period-link {{ $rankingPeriod === 'month' ? 'sa-btn-primary' : '' }}" style="{{ $rankingPeriod === 'month' ? '' : 'background:#fff; color:var(--sa-ink); border:1px solid transparent;' }}">Tháng</a>
                <a href="{{ route('admin.super-admin', array_merge($rankingCompatQueryBase, ['ranking_period' => 'year'])) }}#branch-ranking" data-ranking-period="year" class="sa-btn sa-branch-period-link {{ $rankingPeriod === 'year' ? 'sa-btn-primary' : '' }}" style="{{ $rankingPeriod === 'year' ? '' : 'background:#fff; color:var(--sa-ink); border:1px solid transparent;' }}">Năm</a>
            </div>
            <button type="button" class="sa-btn sa-btn-primary" data-bs-toggle="modal" data-bs-target="#createBranchModal" style="min-height:40px; padding:0.42rem 0.9rem; border-radius:999px; white-space:nowrap; font-size:0.84rem;"><i class="bi bi-plus-circle"></i> Thêm chi nhánh</button>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.super-admin', $branchQueryBase) }}#branch-ranking" class="sa-filter-form sa-branch-compare-form" data-branch-ranking-form style="border-top: 0;">
        <div>
            <label class="sa-panel-note" style="display:block; margin:0 0 0.35rem; font-weight:800; color:var(--sa-ink);">Tìm chi nhánh</label>
            <input class="sa-control" type="search" name="branch_search" value="{{ $branchSearch }}" placeholder="Tên chi nhánh, mã, admin..." aria-label="Tìm chi nhánh">
        </div>
        <div>
            <label class="sa-panel-note" style="display:block; margin:0 0 0.35rem; font-weight:800; color:var(--sa-ink);">Sắp xếp</label>
            <select class="sa-control" name="branch_sort" aria-label="Sắp xếp chi nhánh">
                @foreach($branchSortOptions as $value => $label)
                    <option value="{{ $value }}" @selected($branchSort === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="sa-panel-note" style="display:block; margin:0 0 0.35rem; font-weight:800; color:var(--sa-ink);">Hướng</label>
            <select class="sa-control" name="branch_direction" aria-label="Hướng sắp xếp">
                <option value="desc" @selected($branchDirection === 'desc')>Giảm dần</option>
                <option value="asc" @selected($branchDirection === 'asc')>Tăng dần</option>
            </select>
        </div>
        <div>
            <label class="sa-panel-note" style="display:block; margin:0 0 0.35rem; font-weight:800; color:var(--sa-ink);">Hiệu suất</label>
            <select class="sa-control" name="branch_performance" aria-label="Lọc hiệu suất">
                @foreach($branchPerformanceOptions as $value => $label)
                    <option value="{{ $value }}" @selected($branchPerformance === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="sa-filter-actions sa-branch-compare-actions">
            <a class="sa-btn" href="{{ route('admin.super-admin', array_merge($branchQueryBase, ['branch_search' => null, 'branch_sort' => null, 'branch_direction' => null, 'branch_performance' => null, 'branch_page' => null])) }}#branch-ranking" title="Xóa bộ lọc"><i class="bi bi-arrow-counterclockwise"></i> Xóa lọc</a>
            <button class="sa-btn sa-btn-primary" type="submit"><i class="bi bi-funnel"></i> Áp dụng</button>
        </div>
    </form>

    @if($branchRows->isNotEmpty())
        <div class="sa-branch-compare-summary">
            <div class="sa-branch-compare-summary-text">Đang hiển thị {{ $branchShowingFrom }}-{{ $branchShowingTo }} / {{ number_format($branchTotal) }} chi nhánh</div>
            <div class="sa-branch-compare-summary-meta">
                <span class="sa-state" style="background:#f8fafc; color:#334155;">Kết quả: {{ number_format($branchTotal) }}</span>
                <span class="sa-state" style="background:#f8fafc; color:#334155;">Sắp xếp: {{ $branchSortOptions[$branchSort] ?? 'Doanh thu' }}</span>
            </div>
        </div>
        <div class="sa-table-wrap">
            <table class="sa-table sa-branch-ranking-table">
                <thead>
                    <tr>
                        <th style="position: sticky; top: 0; z-index: 1;">Hạng</th>
                        <th style="position: sticky; top: 0; z-index: 1;">Chi nhánh</th>
                        <th style="position: sticky; top: 0; z-index: 1;">Doanh thu</th>
                        <th style="position: sticky; top: 0; z-index: 1;">Đơn hàng</th>
                        <th style="position: sticky; top: 0; z-index: 1;">Trung bình/đơn</th>
                        <th style="position: sticky; top: 0; z-index: 1;">Sản phẩm bán ra</th>
                        <th style="position: sticky; top: 0; z-index: 1;">Tăng trưởng</th>
                        <th style="position: sticky; top: 0; z-index: 1;">Món bán chạy nhất</th>
                        <th style="position: sticky; top: 0; z-index: 1;">SL món</th>
                        <th style="position: sticky; top: 0; z-index: 1;">Tỷ lệ hủy</th>
                        <th style="position: sticky; top: 0; z-index: 1;">Trạng thái</th>
                        <th style="position: sticky; top: 0; z-index: 1;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($branchRows as $branch)
                        @php
                            $revenueGrowth = $branch['revenue_change_percentage'];
                            $growthState = $branch['change_state'] ?? 'unavailable';
                            $growthLabel = $growthState === 'unavailable'
                                ? 'N/A'
                                : (($revenueGrowth ?? 0) > 0 ? '+'.number_format($revenueGrowth, 1).'%' : number_format((float) $revenueGrowth, 1).'%');
                            $growthTone = in_array($growthState, ['increased', 'new_activity'], true) ? 'color:#15803d;' : (in_array($growthState, ['decreased'], true) ? 'color:#b91c1c;' : 'color:var(--sa-muted);');
                            $growthIcon = $growthState === 'increased' || $growthState === 'new_activity'
                                ? 'bi-arrow-up-right'
                                : ($growthState === 'decreased' ? 'bi-arrow-down-right' : 'bi-dash');
                            $branchStatusValue = (int) ($branch['branch_status'] ?? 0);
                            $isEditingThisBranch = (string) old('branch_modal_id') === (string) $branch['branch_id'];
                        @endphp
                        <tr data-branch-row="{{ $branch['branch_id'] }}" @if($branch['rank'] === 1) style="background-color:#f0fdf4;" @endif>
                            <td style="font-weight:800; color:var(--sa-green);">{{ $branch['rank'] }}</td>
                            <td data-branch-name-cell style="min-width: 250px;">
                                <div style="font-weight:800; color:var(--sa-ink);">{{ $branch['branch_name'] }}</div>
                                <div style="margin-top:0.16rem; color:var(--sa-muted); font-size:0.7rem; line-height:1.35;">
                                    @if($branch['admin_id'])
                                        {{ $branch['admin_name'] ?? 'Chưa gán admin' }}
                                    @else
                                        Chưa gán admin
                                    @endif
                                </div>
                            </td>
                            <td style="font-weight:800; color:var(--sa-green); white-space:nowrap;">{{ number_format($branch['revenue'], 0, ',', '.') }}đ</td>
                            <td style="font-weight:700; white-space:nowrap;">{{ number_format($branch['valid_order_count']) }}</td>
                            <td style="font-weight:700; white-space:nowrap;">{{ number_format($branch['average_order_value'], 0, ',', '.') }}đ</td>
                            <td style="font-weight:700; white-space:nowrap;">{{ number_format($branch['items_sold']) }}</td>
                            <td style="font-weight:800; {{ $growthTone }} white-space:nowrap;">
                                <i class="bi {{ $growthIcon }}"></i> {{ $growthLabel }}
                            </td>
                            <td style="min-width: 200px;">
                                <div style="font-weight:700; color:var(--sa-ink);">{{ $branch['top_product_name'] }}</div>
                                <div style="color:var(--sa-muted); font-size:0.72rem; margin-top:0.15rem;">
                                    {{ number_format($branch['top_product_revenue'], 0, ',', '.') }}đ
                                </div>
                            </td>
                            <td style="font-weight:700; white-space:nowrap;">{{ number_format($branch['top_product_quantity']) }}</td>
                            <td style="font-weight:800; white-space:nowrap; color:{{ $branch['cancellation_rate'] > 0 ? '#b91c1c' : 'var(--sa-muted)' }};">{{ number_format($branch['cancellation_rate'], 1) }}%</td>
                            <td>
                                @if($branchStatusValue)
                                    <span class="sa-state sa-state-active" data-branch-status-badge="{{ $branch['branch_id'] }}"><i class="bi bi-check-circle"></i> Hoạt động</span>
                                @else
                                    <span class="sa-state" style="background:#fef2f2; color:#991b1b;" data-branch-status-badge="{{ $branch['branch_id'] }}"><i class="bi bi-pause-circle"></i> Tạm ngưng</span>
                                @endif
                            </td>
                            <td>
                                <div class="sa-actions">
                                    <button class="sa-action-btn" type="button" data-bs-toggle="modal" data-bs-target="#branchEditModal{{ $branch['branch_id'] }}" title="Sửa chi nhánh">
                                        <i class="bi bi-gear"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="sa-pagination">
            <span>Hiển thị {{ $branchShowingFrom }}-{{ $branchShowingTo }} / {{ number_format($branchTotal) }}</span>
            <div class="sa-page-links" aria-label="Phân trang chi nhánh">
                <a class="sa-page-link {{ $branchPaginator->onFirstPage() ? 'disabled' : '' }}" href="{{ $branchPaginator->previousPageUrl() ?? '#' }}" aria-label="Trang trước"><i class="bi bi-chevron-left"></i></a>
                @foreach(range(1, max(1, $branchPaginator->lastPage())) as $page)
                    <a class="sa-page-link {{ $page === $branchPaginator->currentPage() ? 'active' : '' }}" href="{{ $branchPaginator->url($page) }}">{{ $page }}</a>
                @endforeach
                <a class="sa-page-link {{ $branchPaginator->hasMorePages() ? '' : 'disabled' }}" href="{{ $branchPaginator->nextPageUrl() ?? '#' }}" aria-label="Trang sau"><i class="bi bi-chevron-right"></i></a>
            </div>
        </div>
        @foreach($branchRows as $branch)
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
                                    <input id="branch_email_{{ $branch['branch_id'] }}" class="form-control @error('email', 'editBranch') is-invalid @enderror" type="email" name="email" value="{{ $isEditingThisBranch ? old('email', $branch['branch_email'] ?? '') : ($branch['branch_email'] ?? '') }}" placeholder="branch@example.com">
                                    @error('email', 'editBranch')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold" for="branch_phone_{{ $branch['branch_id'] }}">Điện thoại</label>
                                    <input id="branch_phone_{{ $branch['branch_id'] }}" class="form-control @error('phone', 'editBranch') is-invalid @enderror" type="text" name="phone" value="{{ $isEditingThisBranch ? old('phone', $branch['branch_phone'] ?? '') : ($branch['branch_phone'] ?? '') }}" placeholder="0123456789">
                                    @error('phone', 'editBranch')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold" for="branch_address_{{ $branch['branch_id'] }}">Địa chỉ</label>
                                <textarea id="branch_address_{{ $branch['branch_id'] }}" class="form-control @error('address', 'editBranch') is-invalid @enderror" name="address" rows="2" placeholder="Nhập địa chỉ chi nhánh">{{ $isEditingThisBranch ? old('address', $branch['branch_address'] ?? '') : ($branch['branch_address'] ?? '') }}</textarea>
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
                                'mapLinkValue' => (($branch['branch_latitude'] ?? null) !== null && ($branch['branch_longitude'] ?? null) !== null) ? 'https://www.google.com/maps?q='.$branch['branch_latitude'].','.$branch['branch_longitude'] : '',
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
        <div class="sa-empty">
            <i class="bi bi-shop"></i>
            <strong>Chưa có dữ liệu chi nhánh</strong>
            <p style="margin-top: 0.3rem; font-size: 0.82rem;">Không có chi nhánh nào khớp với bộ lọc hiện tại.</p>
        </div>
    @endif
</section>
