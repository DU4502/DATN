@php
    $branchTime = $branchTimeComparison ?? [];
    $branchTimePeriods = collect($branchTime['periods'] ?? []);
    $branchTimeVisibleBranches = collect($branchTime['visible_branches'] ?? []);
    $branchTimeFilteredBranches = collect($branchTime['filtered_branches'] ?? $branchTimeVisibleBranches);
    $branchTimePaginator = $branchTime['paginator'] ?? null;
    $branchTimeIndicator = (string) ($branchTime['indicator'] ?? 'both');
    $branchTimeIndicatorLabel = (string) ($branchTime['indicator_label'] ?? 'Cả hai');
    $branchTimePeriodCountOptions = collect($branchTime['period_count_options'] ?? []);
    $branchTimePeriodCountSelected = $branchTime['period_count_selected'] ?? null;
    $branchTimeSearch = (string) ($branchTime['search'] ?? request('branch_time_search', ''));
    $branchTimePerPage = (int) ($branchTime['per_page'] ?? request('branch_time_per_page', 10));
    $branchTimeGroupLabel = (string) ($branchTime['group_label'] ?? 'Ngày');
    $branchTimeScopeLabel = (string) ($branchTime['branch_scope_label'] ?? 'Tất cả chi nhánh');
    $branchTimeTotalFiltered = (int) ($branchTime['total_filtered'] ?? $branchTimeFilteredBranches->count());
    $branchTimeFirstItem = $branchTimePaginator ? (int) ($branchTimePaginator->firstItem() ?? 0) : 0;
    $branchTimeLastItem = $branchTimePaginator ? (int) ($branchTimePaginator->lastItem() ?? 0) : 0;
    $branchTimeLastPage = $branchTimePaginator ? (int) $branchTimePaginator->lastPage() : 1;
    $branchTimeQueryBase = request()->except([
        'branch_time_indicator',
        'branch_time_period_count',
        'branch_time_search',
        'branch_time_per_page',
        'branch_time_page',
        'analytics_time_matrix_export',
    ]);
    $branchTimeExportCurrentUrl = $branchTime['export_current_url'] ?? route('admin.super-admin', array_merge($branchTimeQueryBase, ['analytics_time_matrix_export' => 'current']));
    $branchTimeExportAllUrl = $branchTime['export_all_url'] ?? route('admin.super-admin', array_merge($branchTimeQueryBase, ['analytics_time_matrix_export' => 'all']));
    $branchTimeError = $branchTime['error'] ?? null;
    $branchTimeEmpty = $branchTimeVisibleBranches->isEmpty();
    $branchTimeVisibleRows = $branchTimeVisibleBranches->values();
    $branchTimePeriodCountValue = $branchTimePeriodCountSelected ?? $branchTimePeriods->count();
@endphp

<section class="sa-panel sa-time-matrix-panel" aria-labelledby="branch-time-matrix-title" data-branch-time-comparison-region>
    <div class="sa-panel-header sa-time-matrix-header">
        <div class="sa-time-matrix-header-copy">
            <h2 class="sa-panel-title" id="branch-time-matrix-title">So sánh chi nhánh theo thời gian</h2>
            <p class="sa-panel-note">Bảng rút gọn theo kỳ và phạm vi đang chọn, tối ưu để sao chép hoặc xuất Excel.</p>
        </div>
        <div class="sa-time-matrix-meta" aria-label="Thông tin nhanh">
            <span class="sa-state" style="background:#f8fafc; color:#334155;">{{ $branchTimeGroupLabel }}</span>
            <span class="sa-state" style="background:#eafaf5; color:var(--sa-green);">{{ $branchTimeScopeLabel }}</span>
            <span class="sa-state" style="background:#eef2ff; color:#4338ca;">{{ $branchTimeIndicatorLabel }}</span>
        </div>
    </div>

    <form class="sa-time-matrix-toolbar" method="GET" action="{{ route('admin.super-admin', $branchTimeQueryBase) }}" data-branch-time-matrix-form>
        <input type="hidden" name="branch_time_page" value="1">
        <input type="hidden" name="branch_time_indicator" value="{{ $branchTimeIndicator }}">
        @if($branchTimePeriodCountSelected !== null)
            <input type="hidden" name="branch_time_period_count" value="{{ $branchTimePeriodCountSelected }}">
        @endif

        <div class="sa-time-matrix-field">
            <p class="sa-time-matrix-label">Hiển thị</p>
            @if(($branchTime['period_type'] ?? $analyticsContext->periodType) === 'range')
                <span class="sa-btn" style="height:44px; justify-content:flex-start;">Tự động</span>
            @else
                <div class="sa-time-matrix-pills" role="group" aria-label="Chọn số kỳ">
                    @foreach($branchTimePeriodCountOptions as $periodCountOption)
                        <button
                            type="submit"
                            class="sa-time-matrix-pill {{ (int) $branchTimePeriodCountValue === (int) $periodCountOption ? 'active' : '' }}"
                            name="branch_time_period_count"
                            value="{{ $periodCountOption }}"
                        >
                            {{ $periodCountOption }} kỳ
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="sa-time-matrix-field">
            <p class="sa-time-matrix-label">Chỉ số</p>
            <div class="sa-time-matrix-pills" role="group" aria-label="Chọn chỉ số">
                <button type="submit" class="sa-time-matrix-pill {{ $branchTimeIndicator === 'both' ? 'active' : '' }}" name="branch_time_indicator" value="both">Cả hai</button>
                <button type="submit" class="sa-time-matrix-pill {{ $branchTimeIndicator === 'revenue' ? 'active' : '' }}" name="branch_time_indicator" value="revenue">Doanh thu</button>
                <button type="submit" class="sa-time-matrix-pill {{ $branchTimeIndicator === 'orders' ? 'active' : '' }}" name="branch_time_indicator" value="orders">Số đơn</button>
            </div>
        </div>

        <div class="sa-time-matrix-field">
            <p class="sa-time-matrix-label" for="branch_time_search">Tìm chi nhánh</p>
            <input
                id="branch_time_search"
                class="sa-control sa-time-matrix-control"
                type="search"
                name="branch_time_search"
                value="{{ $branchTimeSearch }}"
                placeholder="Tên chi nhánh, mã..."
                aria-label="Tìm chi nhánh"
            >
        </div>

        <div class="sa-time-matrix-field">
            <p class="sa-time-matrix-label" for="branch_time_per_page">Phân trang</p>
            <select id="branch_time_per_page" class="sa-control sa-time-matrix-control" name="branch_time_per_page" aria-label="Số dòng mỗi trang" onchange="this.form.submit()">
                @foreach([10, 25, 50] as $perPageOption)
                    <option value="{{ $perPageOption }}" @selected($branchTimePerPage === $perPageOption)>{{ $perPageOption }}</option>
                @endforeach
            </select>
        </div>

        <div class="sa-time-matrix-export">
            <details>
                <summary class="sa-btn sa-btn-primary" style="height:44px; cursor:pointer;">
                    <i class="bi bi-download"></i> Tải Excel
                </summary>
                <div class="sa-time-matrix-export-menu">
                    <a class="sa-time-matrix-export-item" href="{{ $branchTimeExportCurrentUrl }}">Bảng đang xem</a>
                    <a class="sa-time-matrix-export-item" href="{{ $branchTimeExportAllUrl }}">Toàn bộ dữ liệu</a>
                </div>
            </details>
        </div>
    </form>

    <div class="sa-time-matrix-body">
        @if($branchTimeError)
            <div class="sa-time-matrix-error" role="alert">
                <i class="bi bi-exclamation-triangle"></i>
                <div>
                    <strong>Không thể tải dữ liệu so sánh.</strong>
                    <div>{{ $branchTimeError }}</div>
                </div>
            </div>
        @endif

        <div class="sa-time-matrix-summary">
            <div class="sa-time-matrix-summary-text">
                @if($branchTimeEmpty)
                    Không có chi nhánh nào trong phạm vi đang chọn.
                @else
                    Hiển thị {{ $branchTimeFirstItem }}-{{ $branchTimeLastItem }} / {{ number_format($branchTimeTotalFiltered) }} chi nhánh
                @endif
            </div>
            <div class="sa-time-matrix-summary-meta">
                <span class="sa-state" style="background:#f8fafc; color:#334155;">Kết quả: {{ number_format($branchTimeTotalFiltered) }}</span>
                <span class="sa-state" style="background:#f8fafc; color:#334155;">Trang {{ $branchTimePaginator ? $branchTimePaginator->currentPage() : 1 }}/{{ $branchTimeLastPage }}</span>
            </div>
        </div>

        @if($branchTimeEmpty && ! $branchTimeError)
            <div class="sa-time-matrix-empty">
                <i class="bi bi-grid-3x3-gap"></i>
                <strong>Chưa có dữ liệu phù hợp.</strong>
                <p>Thử đổi kỳ, phạm vi chi nhánh hoặc xóa bộ lọc chi nhánh trong bộ lọc chính.</p>
            </div>
        @elseif(! $branchTimeError)
            <div class="sa-time-matrix-table-wrap">
                <table class="sa-time-matrix-table">
                    <thead>
                        <tr>
                            <th class="sticky-col-1" rowspan="2" style="width: 72px;">STT</th>
                            <th class="sticky-col-2" rowspan="2" style="min-width: 240px;">Chi nhánh</th>
                            @foreach($branchTimePeriods as $period)
                                @if(($branchTimeIndicator === 'both'))
                                    <th colspan="2" class="sa-time-matrix-period-group">
                                        <span>{{ $period['display_label'] ?? $period['label'] ?? $period['key'] }}</span>
                                    </th>
                                @else
                                    <th rowspan="2" class="sa-time-matrix-period-group sa-time-matrix-period-single">{{ $period['display_label'] ?? $period['label'] ?? $period['key'] }}</th>
                                @endif
                            @endforeach
                            <th rowspan="2" class="sa-time-matrix-summary-col">Tổng doanh thu</th>
                            <th rowspan="2" class="sa-time-matrix-summary-col">Tổng đơn</th>
                            <th rowspan="2" class="sa-time-matrix-summary-col">Thay đổi gần nhất doanh thu</th>
                            <th rowspan="2" class="sa-time-matrix-summary-col">Thay đổi gần nhất số đơn</th>
                        </tr>
                        @if($branchTimeIndicator === 'both')
                            <tr>
                                @foreach($branchTimePeriods as $period)
                                    <th class="sa-time-matrix-subhead sa-time-matrix-subhead-revenue">Doanh thu</th>
                                    <th class="sa-time-matrix-subhead sa-time-matrix-subhead-orders">Đơn</th>
                                @endforeach
                            </tr>
                        @endif
                    </thead>
                    <tbody>
                        @php
                            $branchTimeOffset = $branchTimePaginator ? max(0, (int) ($branchTimePaginator->firstItem() ?? 1) - 1) : 0;
                        @endphp
                        <tr class="sa-time-matrix-total-row">
                            <td class="sticky-col-1 text-end">Tổng</td>
                            <td class="sticky-col-2">
                                <div class="sa-time-matrix-sticky-stack">
                                    <div class="sa-time-matrix-branch-name">Tổng {{ number_format($branchTimeTotalFiltered) }} chi nhánh</div>
                                </div>
                            </td>
                            @foreach($branchTimePeriods as $period)
                                @php
                                    $periodTotals = collect($branchTime['totals']['periods'] ?? [])->firstWhere('period_key', $period['key']) ?? [];
                                @endphp
                                @if($branchTimeIndicator === 'both')
                                    <td class="sa-time-matrix-period-cell sa-time-matrix-period-revenue text-end">{{ number_format((int) ($periodTotals['revenue'] ?? 0), 0, ',', '.') }}đ</td>
                                    <td class="sa-time-matrix-period-cell sa-time-matrix-period-orders text-end">{{ number_format((int) ($periodTotals['valid_order_count'] ?? 0)) }}</td>
                                @else
                                    <td class="sa-time-matrix-period-cell sa-time-matrix-period-single text-end">
                                        @if($branchTimeIndicator === 'revenue')
                                            {{ number_format((int) ($periodTotals['revenue'] ?? 0), 0, ',', '.') }}đ
                                        @else
                                            {{ number_format((int) ($periodTotals['valid_order_count'] ?? 0)) }}
                                        @endif
                                    </td>
                                @endif
                            @endforeach
                            <td class="text-end">{{ number_format((int) ($branchTime['totals']['total_revenue'] ?? 0), 0, ',', '.') }}đ</td>
                            <td class="text-end">{{ number_format((int) ($branchTime['totals']['total_valid_orders'] ?? 0)) }}</td>
                            <td class="text-end">Không áp dụng</td>
                            <td class="text-end">Không áp dụng</td>
                        </tr>

                        @forelse($branchTimeVisibleRows as $index => $branch)
                            @php
                                $branchRowRank = $branchTimeOffset + $index + 1;
                                $branchPeriods = $branch['periods'] ?? [];
                            @endphp
                            <tr>
                                <td class="sticky-col-1 text-end">{{ number_format($branchRowRank) }}</td>
                                <td class="sticky-col-2">
                                    <div class="sa-time-matrix-sticky-stack">
                                        <div class="sa-time-matrix-branch-name">{{ $branch['branch_name'] ?? 'Chưa rõ' }}</div>
                                        @if(filled($branch['branch_code'] ?? null))
                                            <div class="sa-time-matrix-branch-code">{{ $branch['branch_code'] }}</div>
                                        @endif
                                    </div>
                                </td>
                                @foreach($branchTimePeriods as $period)
                                    @php
                                        $bucket = $branchPeriods[$period['key']] ?? ['revenue' => 0, 'valid_order_count' => 0];
                                    @endphp
                                @if($branchTimeIndicator === 'both')
                                    <td class="text-end">{{ number_format((int) ($bucket['revenue'] ?? 0), 0, ',', '.') }}đ</td>
                                    <td class="text-end">{{ number_format((int) ($bucket['valid_order_count'] ?? 0)) }}</td>
                                @else
                                    <td class="text-end">
                                        @if($branchTimeIndicator === 'revenue')
                                            {{ number_format((int) ($bucket['revenue'] ?? 0), 0, ',', '.') }}đ
                                        @else
                                            {{ number_format((int) ($bucket['valid_order_count'] ?? 0)) }}
                                        @endif
                                    </td>
                                @endif
                            @endforeach
                                <td class="sa-time-matrix-summary-cell text-end">{{ number_format((int) ($branch['total_revenue'] ?? 0), 0, ',', '.') }}đ</td>
                                <td class="sa-time-matrix-summary-cell text-end">{{ number_format((int) ($branch['total_valid_orders'] ?? 0)) }}</td>
                                <td class="sa-time-matrix-change-cell text-end">
                                    <span class="sa-time-matrix-change">{{ $branch['latest_revenue_change']['label'] ?? 'Không đổi' }}</span>
                                </td>
                                <td class="sa-time-matrix-change-cell text-end">
                                    <span class="sa-time-matrix-change">{{ $branch['latest_order_change']['label'] ?? 'Không đổi' }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ ($branchTimeIndicator === 'both' ? (2 + ($branchTimePeriods->count() * 2) + 4) : (2 + $branchTimePeriods->count() + 4)) }}" style="padding: 2rem 1rem; text-align:center; color: var(--sa-muted);">
                                    Không có dữ liệu phù hợp.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($branchTimePaginator && $branchTimePaginator->hasPages())
                <div class="sa-time-matrix-pagination">
                    <span>Hiển thị {{ $branchTimeFirstItem }}-{{ $branchTimeLastItem }} / {{ number_format($branchTimeTotalFiltered) }}</span>
                    <div class="sa-page-links" aria-label="Phân trang bảng so sánh">
                        <a class="sa-page-link {{ $branchTimePaginator->onFirstPage() ? 'disabled' : '' }}" href="{{ $branchTimePaginator->previousPageUrl() ?? '#' }}" aria-label="Trang trước"><i class="bi bi-chevron-left"></i></a>
                        @foreach(range(1, max(1, $branchTimeLastPage)) as $page)
                            <a class="sa-page-link {{ $page === $branchTimePaginator->currentPage() ? 'active' : '' }}" href="{{ $branchTimePaginator->url($page) }}">{{ $page }}</a>
                        @endforeach
                        <a class="sa-page-link {{ $branchTimePaginator->hasMorePages() ? '' : 'disabled' }}" href="{{ $branchTimePaginator->nextPageUrl() ?? '#' }}" aria-label="Trang sau"><i class="bi bi-chevron-right"></i></a>
                    </div>
                </div>
            @endif
        @endif
    </div>
</section>
