@php
    $branchComparison = $branchRankingComparison ?? ['paginator' => null, 'period_label' => 'Hôm nay', 'search' => '', 'sort' => 'revenue', 'per_page' => 5];
    $branchPaginator = $branchComparison['paginator'] ?? null;
    $branchRows = $branchPaginator ? $branchPaginator->getCollection() : collect();

    $branchPeriod = in_array($branchPeriod ?? request('branch_period', 'day'), ['day', 'week', 'month', 'year', 'range'], true)
        ? (string) ($branchPeriod ?? request('branch_period', 'day'))
        : 'day';
    $branchStartDateValue = trim((string) ($branchStartDate ?? request('branch_start_date', '')));
    $branchEndDateValue = trim((string) ($branchEndDate ?? request('branch_end_date', '')));
    $branchStartDate = $branchStartDateValue !== '' ? $branchStartDateValue : now()->startOfMonth()->format('Y-m-d');
    $branchEndDate = $branchEndDateValue !== '' ? $branchEndDateValue : now()->format('Y-m-d');

    $branchSearch = (string) request('branch_search', $branchComparison['search'] ?? '');
    $branchSort = (string) request('branch_sort', $branchComparison['sort'] ?? 'revenue');
    $branchTotal = $branchPaginator ? (int) $branchPaginator->total() : 0;
    $branchShowingFrom = $branchPaginator ? (int) ($branchPaginator->firstItem() ?? 0) : 0;
    $branchShowingTo = $branchPaginator ? (int) ($branchPaginator->lastItem() ?? 0) : 0;

    $branchSortOptions = [
        'revenue' => 'Doanh thu',
        'orders' => 'Đơn hàng',
        'average_order_value' => 'Trung bình/đơn',
        'items_sold' => 'Sản phẩm bán ra',
        'cancellation_rate' => 'Tỷ lệ hủy',
        'name' => 'Tên chi nhánh',
    ];

    $branchPeriodQueryBase = request()->except([
        'branch_period', 'branch_start_date', 'branch_end_date',
        'branch_direction', 'branch_performance', 'branch_page',
    ]);
    $branchResetQueryBase = request()->except([
        'branch_search', 'branch_sort', 'branch_direction', 'branch_performance', 'branch_page',
    ]);
    $branchFormPreserveQuery = request()->except([
        'branch_period', 'branch_start_date', 'branch_end_date',
        'branch_search', 'branch_sort', 'branch_direction', 'branch_performance', 'branch_page',
    ]);
@endphp

<section class="sa-panel" id="branch-ranking" data-branch-ranking-region>
    <div class="sa-panel-header sa-branch-compare-header">
        <div class="sa-branch-compare-header-copy">
            <h2 class="sa-branch-compare-title">Lọc & xếp hạng chi nhánh</h2>
            <div class="sa-branch-compare-subtitle">Chỉ áp dụng cho bảng bên dưới</div>
        </div>

        <div class="sa-branch-compare-tools">
            <div class="sa-branch-period-group">
                <span class="sa-branch-period-label">Thời gian</span>
                <div class="sa-branch-period-switcher" aria-label="Khoảng thời gian bảng chi nhánh">
                    <a href="{{ route('admin.super-admin', array_merge($branchPeriodQueryBase, ['branch_period' => 'day'])) }}#branch-ranking"
                       data-ranking-period="day"
                       class="sa-btn sa-branch-period-link {{ $branchPeriod === 'day' ? 'sa-btn-primary' : '' }}"
                       style="{{ $branchPeriod === 'day' ? '' : 'background:transparent; color:var(--sa-ink); border:0;' }}">Hôm nay</a>
                    <a href="{{ route('admin.super-admin', array_merge($branchPeriodQueryBase, ['branch_period' => 'week'])) }}#branch-ranking"
                       data-ranking-period="week"
                       class="sa-btn sa-branch-period-link {{ $branchPeriod === 'week' ? 'sa-btn-primary' : '' }}"
                       style="{{ $branchPeriod === 'week' ? '' : 'background:transparent; color:var(--sa-ink); border:0;' }}">Tuần</a>
                    <a href="{{ route('admin.super-admin', array_merge($branchPeriodQueryBase, ['branch_period' => 'month'])) }}#branch-ranking"
                       data-ranking-period="month"
                       class="sa-btn sa-branch-period-link {{ $branchPeriod === 'month' ? 'sa-btn-primary' : '' }}"
                       style="{{ $branchPeriod === 'month' ? '' : 'background:transparent; color:var(--sa-ink); border:0;' }}">Tháng</a>
                    <a href="{{ route('admin.super-admin', array_merge($branchPeriodQueryBase, ['branch_period' => 'year'])) }}#branch-ranking"
                       data-ranking-period="year"
                       class="sa-btn sa-branch-period-link {{ $branchPeriod === 'year' ? 'sa-btn-primary' : '' }}"
                       style="{{ $branchPeriod === 'year' ? '' : 'background:transparent; color:var(--sa-ink); border:0;' }}">Năm</a>
                    <a href="{{ route('admin.super-admin', array_merge($branchPeriodQueryBase, ['branch_period' => 'range', 'branch_start_date' => $branchStartDate, 'branch_end_date' => $branchEndDate])) }}#branch-ranking"
                       data-ranking-period="range"
                       class="sa-btn sa-branch-period-link {{ $branchPeriod === 'range' ? 'sa-btn-primary' : '' }}"
                       style="{{ $branchPeriod === 'range' ? '' : 'background:transparent; color:var(--sa-ink); border:0;' }}">Tùy chọn</a>
                </div>
            </div>

            <button type="button" class="sa-btn sa-btn-primary sa-branch-add-btn" data-bs-toggle="modal" data-bs-target="#createBranchModal"><i class="bi bi-plus-circle"></i> Thêm chi nhánh</button>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.super-admin') }}#branch-ranking"
          class="sa-filter-form sa-branch-compare-form {{ $branchPeriod === 'range' ? 'sa-branch-compare-form-range' : 'sa-branch-compare-form-compact' }}"
          data-branch-ranking-form>
        @foreach($branchFormPreserveQuery as $preserveKey => $preserveValue)
            @if(is_array($preserveValue))
                @foreach($preserveValue as $preserveItem)
                    <input type="hidden" name="{{ $preserveKey }}[]" value="{{ $preserveItem }}">
                @endforeach
            @elseif($preserveValue !== null && $preserveValue !== '')
                <input type="hidden" name="{{ $preserveKey }}" value="{{ $preserveValue }}">
            @endif
        @endforeach
        <input type="hidden" name="branch_period" value="{{ $branchPeriod }}">

        @if($branchPeriod === 'range')
            <div class="sa-branch-filter-field">
                <label for="branch_start_date">Từ ngày</label>
                <input id="branch_start_date" class="sa-control" type="date" name="branch_start_date" value="{{ $branchStartDate }}">
            </div>
            <div class="sa-branch-filter-field">
                <label for="branch_end_date">Đến ngày</label>
                <input id="branch_end_date" class="sa-control" type="date" name="branch_end_date" value="{{ $branchEndDate }}">
            </div>
        @endif

        <div class="sa-branch-filter-field">
            <label for="branch_search">Tìm chi nhánh</label>
            <input id="branch_search" class="sa-control" type="search" name="branch_search" value="{{ $branchSearch }}" placeholder="Tên, mã hoặc admin...">
        </div>

        <div class="sa-branch-filter-field">
            <label for="branch_sort">Sắp xếp theo</label>
            <select id="branch_sort" class="sa-control" name="branch_sort">
                @foreach($branchSortOptions as $value => $label)
                    <option value="{{ $value }}" @selected($branchSort === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="sa-filter-actions sa-branch-compare-actions">
            <a class="sa-btn" href="{{ route('admin.super-admin', $branchResetQueryBase) }}#branch-ranking" title="Đặt lại bộ lọc"><i class="bi bi-arrow-counterclockwise"></i> Đặt lại</a>
            <button class="sa-btn sa-btn-primary" type="submit"><i class="bi bi-check2"></i> Áp dụng</button>
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
                            <td tabindex="0" role="button" data-drilldown="revenue" data-branch-id="{{ $branch['branch_id'] }}" data-from="{{ $branchComparison['current_start'] ?? '' }}" data-to="{{ $branchComparison['current_end'] ?? '' }}" title="Nhấn để xem dữ liệu chi tiết" style="font-weight:800; color:var(--sa-green); white-space:nowrap;">{{ number_format($branch['revenue'], 0, ',', '.') }}đ</td>
                            <td tabindex="0" role="button" data-drilldown="orders" data-branch-id="{{ $branch['branch_id'] }}" data-from="{{ $branchComparison['current_start'] ?? '' }}" data-to="{{ $branchComparison['current_end'] ?? '' }}" style="font-weight:700; white-space:nowrap;">{{ number_format($branch['valid_order_count']) }}</td>
                            <td tabindex="0" role="button" data-drilldown="average_order_value" data-branch-id="{{ $branch['branch_id'] }}" data-from="{{ $branchComparison['current_start'] ?? '' }}" data-to="{{ $branchComparison['current_end'] ?? '' }}" style="font-weight:700; white-space:nowrap;">{{ number_format($branch['average_order_value'], 0, ',', '.') }}đ</td>
                            <td tabindex="0" role="button" data-drilldown="items_sold" data-branch-id="{{ $branch['branch_id'] }}" data-from="{{ $branchComparison['current_start'] ?? '' }}" data-to="{{ $branchComparison['current_end'] ?? '' }}" style="font-weight:700; white-space:nowrap;">{{ number_format($branch['items_sold']) }}</td>
                            <td style="min-width: 200px;">
                                <div style="font-weight:700; color:var(--sa-ink);">{{ $branch['top_product_name'] }}</div>
                                <div style="color:var(--sa-muted); font-size:0.72rem; margin-top:0.15rem;">
                                    {{ number_format($branch['top_product_revenue'], 0, ',', '.') }}đ
                                </div>
                            </td>
                            <td @if($branch['top_product_id']) tabindex="0" role="button" data-drilldown="product_sales" data-product-id="{{ $branch['top_product_id'] }}" data-branch-id="{{ $branch['branch_id'] }}" data-from="{{ $branchComparison['current_start'] ?? '' }}" data-to="{{ $branchComparison['current_end'] ?? '' }}" title="Nhấn để xem dữ liệu sản phẩm bán chạy" @else aria-disabled="true" title="Chi nhánh chưa có sản phẩm bán ra trong kỳ này" @endif style="font-weight:700; white-space:nowrap;">{{ number_format($branch['top_product_quantity']) }}</td>
                            <td tabindex="0" role="button" data-drilldown="cancellation_rate" data-branch-id="{{ $branch['branch_id'] }}" data-from="{{ $branchComparison['current_start'] ?? '' }}" data-to="{{ $branchComparison['current_end'] ?? '' }}" title="Nhấn để xem cách tính và các đơn đã hủy" style="font-weight:800; white-space:nowrap; color:{{ $branch['cancellation_rate'] > 0 ? '#b91c1c' : 'var(--sa-muted)' }};">{{ number_format($branch['cancellation_rate'], 1) }}%</td>
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
