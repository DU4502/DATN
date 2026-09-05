@extends(auth()->user()?->preferredAdminLayout() ?? 'layouts.admin')

@section('page-title', 'Tổng quát hệ thống')
@section('search-placeholder', 'Tìm báo cáo, đơn hàng...')

@section('content')
<style>
    /* Admin Dashboard Specific Styles */
    .dashboard-header {
        margin-bottom: 2rem;
    }

    .period-segmented-control {
        display: inline-flex;
        background: var(--a-border-light);
        padding: 4px;
        border-radius: var(--radius-full);
        gap: 2px;
    }

    .period-segment {
        padding: 6px 14px;
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--a-muted);
        border-radius: var(--radius-full);
        cursor: pointer;
        transition: all 0.2s ease;
        white-space: nowrap;
    }

    .period-segment-button {
        border: 0;
        background: transparent;
        appearance: none;
    }

    .period-segment:hover {
        color: var(--a-ink);
    }

    .period-segment.active {
        background: var(--a-surface);
        color: var(--a-primary);
        box-shadow: var(--shadow-sm);
    }

    .stat-card {
        padding: 1.5rem;
        position: relative;
        overflow: hidden;
        border: 1px solid var(--a-border);
        border-radius: var(--radius-xl);
        background: var(--a-surface);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .stat-card.chart-trigger {
        cursor: pointer;
    }

    .stat-card.chart-trigger.active {
        border-color: rgba(13, 147, 115, 0.45);
        box-shadow: 0 0 0 3px rgba(13, 147, 115, 0.1);
    }

    .stat-card.chart-trigger.active[data-chart-type="orders"],
    .stat-card.chart-trigger.active[data-chart-type="users"] {
        border-color: var(--chart-accent-strong);
        box-shadow: 0 0 0 3px var(--chart-accent-soft);
    }

    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-md);
        border-color: rgba(13, 147, 115, 0.3);
    }

    .stat-card[data-chart-type="orders"] {
        --chart-accent: #F9A8D4;
        --chart-accent-strong: #EC4899;
        --chart-accent-soft: rgba(249, 168, 212, 0.32);
    }

    .stat-card[data-chart-type="orders"] .stat-icon.info {
        background: rgba(249, 168, 212, 0.28);
        color: #EC4899;
    }

    .stat-card[data-chart-type="users"] {
        --chart-accent: #FDE68A;
        --chart-accent-strong: #F59E0B;
        --chart-accent-soft: rgba(253, 230, 138, 0.36);
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--radius-lg);
        font-size: 1.25rem;
    }

    .stat-icon.primary {
        background: var(--a-primary-light);
        color: var(--a-primary);
    }

    .stat-icon.success {
        background: #D1FAE5;
        color: #10B981;
    }

    .stat-icon.warning {
        background: #FEF3C7;
        color: #F59E0B;
    }

    .stat-icon.info {
        background: #DBEAFE;
        color: #3B82F6;
    }

    .stat-value {
        font-size: 1.75rem;
        font-weight: 800;
        letter-spacing: -0.03em;
        margin: 0.5rem 0 0.25rem;
    }

    .stat-label {
        color: var(--a-muted);
        font-size: 0.8125rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .stat-trend {
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 2px 8px;
        border-radius: var(--radius-full);
    }

    .stat-trend.up {
        background: #D1FAE5;
        color: #059669;
    }

    .stat-trend.down {
        background: #FEE2E2;
        color: #DC2626;
    }

    .stat-trend.flat {
        background: #E5E7EB;
        color: #4B5563;
    }

    /* CSS Mini Chart / Sparkline */
    .sparkline {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 40px;
        display: flex;
        align-items: flex-end;
        gap: 4px;
        padding: 0 1rem;
        opacity: 0.2;
    }

    .spark-bar {
        flex: 1;
        background: var(--a-primary);
        border-radius: 4px 4px 0 0;
    }

    .stat-card[data-chart-type="orders"] .sparkline,
    .stat-card[data-chart-type="users"] .sparkline {
        opacity: 0.42 !important;
        filter: none !important;
    }

    .stat-card[data-chart-type="orders"] .spark-bar,
    .stat-card[data-chart-type="users"] .spark-bar {
        background: var(--chart-accent);
    }

    /* Animated Chart Mockup */
    .chart-mockup {
        --chart-accent: var(--a-primary-light);
        --chart-accent-strong: var(--a-primary);
        height: 300px;
        width: 100%;
        border-radius: var(--radius-lg);
        background: linear-gradient(180deg, var(--a-bg-subtle) 0%, rgba(241, 245, 244, 0.3) 100%);
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        padding: 2rem 1rem 0;
        border: 1px dashed var(--a-border);
    }

    .chart-col {
        width: calc(100% / var(--bar-count, 12) - 10px);
        background: linear-gradient(180deg, var(--chart-accent) 0%, var(--chart-accent-strong) 100%);
        border-radius: 6px 6px 0 0;
        position: relative;
        opacity: 0.8;
        transform-origin: bottom;
        animation: growBar 1.5s cubic-bezier(0.1, 0.8, 0.2, 1) forwards;
        transition: transform 0.2s ease, opacity 0.2s ease, filter 0.2s ease;
        cursor: pointer;
        outline: none;
    }

    .chart-col:hover,
    .chart-col:focus,
    .chart-col:focus-visible,
    .chart-col:active {
        opacity: 1;
        transform: translateY(-2px);
        filter: saturate(1.08);
        z-index: 5;
    }

    .chart-col.active {
        opacity: 1;
        filter: saturate(1.1);
    }

    .chart-mockup[data-active-chart="orders"] {
        --chart-accent: #F9A8D4;
        --chart-accent-strong: #EC4899;
    }

    .chart-mockup[data-active-chart="users"] {
        --chart-accent: #FDE68A;
        --chart-accent-strong: #F59E0B;
    }

    .chart-tooltip {
        position: absolute;
        left: 0;
        top: 0;
        transform: translate3d(0, 0, 0);
        white-space: pre-line;
        text-align: left;
        min-width: 110px;
        max-width: 180px;
        padding: 8px 10px;
        border-radius: 10px;
        border: 1px solid rgba(13, 147, 115, 0.18);
        background: rgba(18, 25, 38, 0.94);
        box-shadow: 0 12px 30px rgba(18, 25, 38, 0.25);
        color: #fff;
        font-size: 0.76rem;
        line-height: 1.45;
        font-weight: 600;
        letter-spacing: 0.01em;
        opacity: 0;
        visibility: visible;
        pointer-events: none;
        transition: opacity 0.18s ease;
        z-index: 7;
    }

    .chart-tooltip.show {
        opacity: 1;
    }

    .chart-tooltip .label {
        color: rgba(255, 255, 255, 0.85);
        display: block;
        margin-bottom: 2px;
    }

    .chart-tooltip .value {
        font-weight: 700;
        color: #ffffff;
    }

    @keyframes growBar {
        from {
            transform: scaleY(0);
        }

        to {
            transform: scaleY(1);
        }
    }

    /* Table avatars */
    .avatar-sm {
        width: 32px;
        height: 32px;
        font-size: 0.75rem;
    }

    .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 6px;
    }

    .status-dot.pending {
        background: #F59E0B;
        box-shadow: 0 0 8px rgba(245, 158, 11, 0.4);
    }

    .status-dot.completed {
        background: #10B981;
        box-shadow: 0 0 8px rgba(16, 185, 129, 0.4);
    }

    .status-dot.cancelled {
        background: #EF4444;
        box-shadow: 0 0 8px rgba(239, 68, 68, 0.4);
    }

    .period-filter-shell {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 0.75rem;
    }

    .period-custom-popover {
        width: min(560px, calc(100vw - 2rem));
        background: var(--a-surface);
        border: 1px solid var(--a-border);
        border-radius: var(--radius-xl);
        padding: 1rem;
        box-shadow: var(--shadow-lg);
        z-index: 20;
    }

    .period-custom-popover[hidden] {
        display: none !important;
    }

    .dashboard-time-comparison .comparison-header {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        padding: 1.25rem 1.25rem 1rem;
    }

    .dashboard-time-comparison .comparison-toolbar {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        align-items: center;
        gap: 0.75rem;
    }

    .dashboard-time-comparison .comparison-toolbar-label {
        font-size: 0.75rem;
        font-weight: 700;
        color: var(--a-muted);
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .dashboard-time-comparison .comparison-pills {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 4px;
        border: 1px solid var(--a-border);
        border-radius: var(--radius-full);
        background: var(--a-surface);
    }

    .dashboard-time-comparison .comparison-pill-button {
        border: 0;
        background: transparent;
        color: var(--a-muted);
        border-radius: var(--radius-full);
        padding: 0.5rem 0.95rem;
        font-size: 0.84rem;
        font-weight: 700;
        line-height: 1;
        white-space: nowrap;
        transition: all 0.18s ease;
    }

    .dashboard-time-comparison .comparison-pill-button:hover {
        color: var(--a-primary);
    }

    .dashboard-time-comparison .comparison-pill-button.active {
        background: var(--a-primary);
        color: #fff;
        box-shadow: 0 8px 18px rgba(13, 147, 115, 0.16);
    }

    .dashboard-time-comparison .comparison-export {
        white-space: nowrap;
    }

    .dashboard-time-comparison .comparison-table-wrap {
        padding: 0 1.25rem 1.25rem;
        overflow-x: hidden;
        background: linear-gradient(180deg, rgba(248, 251, 250, 0.6) 0%, rgba(255, 255, 255, 1) 100%);
    }

    .dashboard-time-comparison .comparison-list-head {
        display: grid;
        grid-template-columns: minmax(180px, 1.2fr) minmax(120px, 0.75fr) minmax(90px, 0.55fr) minmax(120px, 0.75fr) minmax(200px, 1fr);
        gap: 0.5rem;
        padding: 0.7rem 0.85rem 0.35rem;
        color: var(--a-muted);
        font-size: 0.7rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.07em;
    }

    .dashboard-time-comparison .comparison-list {
        display: flex;
        flex-direction: column;
        gap: 0.68rem;
    }

    .dashboard-time-comparison .comparison-row {
        display: grid;
        grid-template-columns: minmax(180px, 1.2fr) minmax(120px, 0.75fr) minmax(90px, 0.55fr) minmax(120px, 0.75fr) minmax(200px, 1fr);
        gap: 0.5rem;
        align-items: center;
        padding: 0.95rem 1rem;
        background: var(--a-surface);
        border: 1px solid rgba(148, 163, 184, 0.16);
        border-radius: 18px;
        box-shadow: 0 1px 0 rgba(15, 23, 42, 0.02);
        transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
    }

    .dashboard-time-comparison .comparison-row:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
        border-color: rgba(13, 147, 115, 0.18);
    }

    .dashboard-time-comparison .comparison-row.is-partial {
        background: linear-gradient(90deg, rgba(13, 147, 115, 0.08) 0%, rgba(255, 255, 255, 1) 48%);
        border-color: rgba(13, 147, 115, 0.24);
    }

    .dashboard-time-comparison .comparison-cell {
        min-width: 0;
    }

    .dashboard-time-comparison .comparison-period-cell {
        text-align: left;
    }

    .dashboard-time-comparison .comparison-period-title {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
        line-height: 1.2;
    }

    .dashboard-time-comparison .comparison-period-title strong {
        font-size: 0.95rem;
        letter-spacing: -0.01em;
    }

    .dashboard-time-comparison .comparison-period-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.18rem 0.48rem;
        border-radius: 999px;
        background: rgba(13, 147, 115, 0.12);
        color: var(--a-primary);
        font-size: 0.68rem;
        font-weight: 800;
        white-space: nowrap;
    }

    .dashboard-time-comparison .comparison-number {
        text-align: right;
        font-variant-numeric: tabular-nums;
        font-weight: 700;
        white-space: nowrap;
        font-size: 0.95rem;
    }

    .dashboard-time-comparison .comparison-change {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 0.15rem;
        text-align: right;
        font-variant-numeric: tabular-nums;
    }

    .dashboard-time-comparison .comparison-change-main {
        font-size: 0.84rem;
        font-weight: 800;
        white-space: nowrap;
    }

    .dashboard-time-comparison .comparison-change-sub {
        font-size: 0.72rem;
        color: var(--a-muted);
        white-space: nowrap;
    }

    .dashboard-time-comparison .comparison-change.up .comparison-change-main,
    .dashboard-time-comparison .comparison-change.up .comparison-change-sub {
        color: #059669;
    }

    .dashboard-time-comparison .comparison-change.down .comparison-change-main,
    .dashboard-time-comparison .comparison-change.down .comparison-change-sub {
        color: #DC2626;
    }

    .dashboard-time-comparison .comparison-change.flat .comparison-change-main,
    .dashboard-time-comparison .comparison-change.flat .comparison-change-sub {
        color: #4B5563;
    }

    .dashboard-time-comparison .comparison-change.new .comparison-change-main,
    .dashboard-time-comparison .comparison-change.new .comparison-change-sub {
        color: #D97706;
    }

    .dashboard-time-comparison .comparison-change.insufficient .comparison-change-main,
    .dashboard-time-comparison .comparison-change.insufficient .comparison-change-sub {
        color: #6B7280;
    }

    .dashboard-time-comparison .comparison-change-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.3rem;
        padding: 0.36rem 0.65rem;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 800;
        white-space: nowrap;
        line-height: 1;
    }

    .dashboard-time-comparison .comparison-change.up .comparison-change-badge {
        background: #D1FAE5;
        color: #059669;
    }

    .dashboard-time-comparison .comparison-change.down .comparison-change-badge {
        background: #FEE2E2;
        color: #DC2626;
    }

    .dashboard-time-comparison .comparison-change.flat .comparison-change-badge {
        background: #E5E7EB;
        color: #4B5563;
    }

    .dashboard-time-comparison .comparison-change.new .comparison-change-badge {
        background: #FEF3C7;
        color: #D97706;
    }

    .dashboard-time-comparison .comparison-change.insufficient .comparison-change-badge {
        background: #E5E7EB;
        color: #6B7280;
    }

    .dashboard-time-comparison .comparison-empty {
        padding: 2rem 1rem;
        text-align: center;
        color: var(--a-muted);
    }

    @media (max-width: 575.98px) {
        .dashboard-header { gap: 0.65rem !important; margin-bottom: 1rem; }
        .period-filter-shell { width: 100%; align-items: stretch; gap: 0.45rem; }
        .period-segmented-control {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            width: 100%;
            gap: 2px;
            padding: 3px;
        }
        .period-segment {
            min-width: 0;
            padding: 6px 2px;
            font-size: 0.63rem;
            text-align: center;
        }
        .dashboard-kpi-grid { --bs-gutter-x: 0.65rem; --bs-gutter-y: 0.65rem; }
        .dashboard-kpi-grid > .col-md-6 { width: 50%; }
        .dashboard-kpi-grid .stat-card { height: 100%; padding: 0.8rem; border-radius: 14px; }
        .dashboard-kpi-grid .stat-icon { width: 36px; height: 36px; }
        .dashboard-kpi-grid .stat-card h3 { font-size: 1.35rem; }
        .dashboard-kpi-grid .stat-card .mb-3 { margin-bottom: 0.55rem !important; }
        .dashboard-time-comparison .comparison-list-head {
            display: none;
        }

        .dashboard-time-comparison .comparison-row {
            grid-template-columns: 1fr;
            gap: 0.7rem;
        }

        .dashboard-time-comparison .comparison-number,
        .dashboard-time-comparison .comparison-change {
            text-align: left;
            align-items: flex-start;
        }

        .dashboard-time-comparison .comparison-change-sub {
            display: none;
        }
    }
</style>

@php
$initialPeriodLabel = $selectedPeriodStat['label'] ?? 'Hôm nay';
$initialPeriodLabelLower = \Illuminate\Support\Str::lower($initialPeriodLabel);
$selectedPeriodStart = $selectedPeriodStat['start'] ?? now()->format('Y-m-d');
$selectedPeriodEnd = $selectedPeriodStat['end'] ?? now()->format('Y-m-d');
$timeComparison = $timeComparison ?? [];
$timeComparisonPeriods = $timeComparison['periods'] ?? [];
$timeComparisonRows = $timeComparison['rows'] ?? [];
$timeComparisonPeriodCount = (int) ($timeComparison['period_count'] ?? 8);
$timeComparisonPeriodOptions = $timeComparison['period_options'] ?? [];
$timeComparisonExportQuery = array_filter([
    'period' => request()->query('period', 'week'),
    'date' => request()->query('date'),
    'week' => request()->query('week'),
    'month' => request()->query('month'),
    'year' => request()->query('year'),
    'start_date' => request()->query('start_date'),
    'end_date' => request()->query('end_date'),
    'admin_matrix_periods' => $timeComparisonPeriodCount,
], static fn ($value) => $value !== null && $value !== '');
$timeComparisonExportUrl = route('admin.dashboard.export', $timeComparisonExportQuery);
$dashboardQueryBase = array_filter([
    'branch_id' => $dashboardBranch?->id,
], static fn ($value) => $value !== null && $value !== '');
$dashboardScopeLabel = $dashboardBranch ? 'chi nhánh ' . $dashboardBranch->name : 'cửa hàng';
@endphp

<div class="dashboard-header d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3">
    <div>
        <h2 class="h3 fw-bold mb-1">Xin chào, Quản trị viên 👋</h2>
        <p id="dashboard-summary-text" class="text-secondary mb-0">Đây là hoạt động kinh doanh {{ $initialPeriodLabelLower }} của {{ $dashboardScopeLabel }}.</p>
        @if($dashboardBranch)
            <span class="badge text-bg-light border mt-2">Đang xem: {{ $dashboardBranch->name }} ({{ $dashboardBranch->code }})</span>
        @endif
    </div>
    <div class="period-filter-shell">
        <div class="period-segmented-control">
            <a href="{{ route('admin.dashboard', array_merge($dashboardQueryBase, ['period' => 'day'])) }}" data-period="day" class="period-segment text-decoration-none {{ $selectedPeriod === 'day' ? 'active' : '' }}">Hôm nay</a>
            <a href="{{ route('admin.dashboard', array_merge($dashboardQueryBase, ['period' => 'week'])) }}" data-period="week" class="period-segment text-decoration-none {{ $selectedPeriod === 'week' ? 'active' : '' }}">Tuần này</a>
            <a href="{{ route('admin.dashboard', array_merge($dashboardQueryBase, ['period' => 'month'])) }}" data-period="month" class="period-segment text-decoration-none {{ $selectedPeriod === 'month' ? 'active' : '' }}">Tháng này</a>
            <a href="{{ route('admin.dashboard', array_merge($dashboardQueryBase, ['period' => 'year'])) }}" data-period="year" class="period-segment text-decoration-none {{ $selectedPeriod === 'year' ? 'active' : '' }}">Năm nay</a>
            <button type="button" data-period="custom" class="period-segment period-segment-button {{ $selectedPeriod === 'custom' ? 'active' : '' }}">Tùy chọn</button>
        </div>
        <div id="dashboard-custom-period" class="period-custom-popover" hidden>
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-semibold mb-1" for="dashboard-custom-start">Từ ngày</label>
                    <input id="dashboard-custom-start" type="date" class="form-control" value="{{ $selectedPeriodStart }}">
                </div>
                <div class="col-md-5">
                    <label class="form-label small fw-semibold mb-1" for="dashboard-custom-end">Đến ngày</label>
                    <input id="dashboard-custom-end" type="date" class="form-control" value="{{ $selectedPeriodEnd }}">
                </div>
                <div class="col-md-2 d-grid">
                    <button id="dashboard-custom-apply" type="button" class="btn btn-primary rounded-pill">Áp dụng</button>
                </div>
            </div>
            <div id="dashboard-custom-error" class="small text-danger mt-2 d-none"></div>
        </div>
    </div>
</div>

<div class="row g-4 mb-5 dashboard-kpi-grid">
    <div class="col-md-6 col-xl-3">
        <div class="stat-card chart-trigger active" data-chart-type="revenue" data-kpi="revenue" tabindex="0" role="button" aria-label="Xem biểu đồ doanh thu">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="stat-icon primary"><i class="bi bi-wallet2"></i></div>
                <span class="stat-trend {{ $cardTrends['revenue']['direction'] ?? 'flat' }}">
                    <i class="bi {{ $cardTrends['revenue']['icon'] ?? 'bi-dash' }}"></i> {{ $cardTrends['revenue']['value'] ?? '0%' }}
                </span>
            </div>
            <div class="stat-label">Tổng doanh thu</div>
            <div id="kpi-revenue-value" class="stat-value">{{ number_format($totalRevenue, 0, ',', '.') }}đ</div>
            <div id="kpi-revenue-comparison" class="text-secondary small">{{ $comparisonLabel ?? 'So với tuần trước' }}</div>
            <button type="button" class="dashboard-trace-link" data-drilldown="revenue"><i class="bi bi-search"></i> Xem dữ liệu nguồn</button>

            <div class="sparkline">
                <div class="spark-bar" style="height: 30%"></div>
                <div class="spark-bar" style="height: 50%"></div>
                <div class="spark-bar" style="height: 40%"></div>
                <div class="spark-bar" style="height: 70%"></div>
                <div class="spark-bar" style="height: 60%"></div>
                <div class="spark-bar" style="height: 90%"></div>
                <div class="spark-bar" style="height: 100%"></div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="stat-card chart-trigger" data-chart-type="orders" data-kpi="orders" tabindex="0" role="button" aria-label="Xem biểu đồ đơn hàng">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="stat-icon info"><i class="bi bi-bag-check"></i></div>
                <span class="stat-trend {{ $cardTrends['orders']['direction'] ?? 'flat' }}">
                    <i class="bi {{ $cardTrends['orders']['icon'] ?? 'bi-dash' }}"></i> {{ $cardTrends['orders']['value'] ?? '0%' }}
                </span>
            </div>
            <div class="stat-label">Đơn hàng mới</div>
            <div id="kpi-orders-value" class="stat-value">{{ $selectedPeriodStat['orders'] ?? 0 }}</div>
            <div class="text-secondary small" id="kpi-orders-label">{{ $selectedPeriodStat['label'] ?? 'Kỳ hiện tại' }}</div>
            <button type="button" class="dashboard-trace-link" data-drilldown="orders"><i class="bi bi-search"></i> Xem dữ liệu nguồn</button>

            <div class="sparkline" style="opacity: 0.15; filter: hue-rotate(180deg);">
                <div class="spark-bar" style="height: 40%"></div>
                <div class="spark-bar" style="height: 50%"></div>
                <div class="spark-bar" style="height: 80%"></div>
                <div class="spark-bar" style="height: 60%"></div>
                <div class="spark-bar" style="height: 70%"></div>
                <div class="spark-bar" style="height: 40%"></div>
                <div class="spark-bar" style="height: 90%"></div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="stat-card chart-trigger" data-chart-type="users" data-kpi="users" tabindex="0" role="button" aria-label="Xem biểu đồ người dùng mới">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="stat-icon warning"><i class="bi bi-people"></i></div>
                <span class="stat-trend {{ $cardTrends['users']['direction'] ?? 'flat' }}">
                    <i class="bi {{ $cardTrends['users']['icon'] ?? 'bi-dash' }}"></i> {{ $cardTrends['users']['value'] ?? '0%' }}
                </span>
            </div>
            <div class="stat-label">Khách hàng</div>
            <div id="kpi-users-value" class="stat-value">{{ $totalUsers }}</div>
            <div class="text-secondary small">Khách hàng đăng ký mới</div>
            <button type="button" class="dashboard-trace-link" data-drilldown="new_customers"><i class="bi bi-search"></i> Xem dữ liệu nguồn</button>

            <div class="sparkline">
                <div class="spark-bar" style="height: 35%"></div>
                <div class="spark-bar" style="height: 45%"></div>
                <div class="spark-bar" style="height: 55%"></div>
                <div class="spark-bar" style="height: 70%"></div>
                <div class="spark-bar" style="height: 50%"></div>
                <div class="spark-bar" style="height: 80%"></div>
                <div class="spark-bar" style="height: 65%"></div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="stat-card" data-kpi="products">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="stat-icon success"><i class="bi bi-cup-straw"></i></div>
                <span class="stat-trend {{ $cardTrends['products']['direction'] ?? 'flat' }}">
                    <i class="bi {{ $cardTrends['products']['icon'] ?? 'bi-dash' }}"></i> {{ $cardTrends['products']['value'] ?? '0%' }}
                </span>
            </div>
            <div class="stat-label">Sản phẩm menu</div>
            <div id="kpi-products-value" class="stat-value">{{ $totalProducts }}</div>
            <div class="text-secondary small">Sản phẩm đang bán</div>
            <button type="button" class="dashboard-trace-link" data-drilldown="products"><i class="bi bi-search"></i> Xem dữ liệu nguồn</button>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <div class="admin-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 id="chart-title" class="h5 fw-bold mb-1">Phân tích doanh thu</h3>
                    <p id="chart-description" class="text-secondary small mb-0">Thống kê doanh thu theo kỳ đang chọn</p>
                </div>
                <div class="dropdown">
                    <span id="selected-period-label" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                        {{ $selectedPeriodStat['label'] ?? 'Tuần này' }}
                    </span>
                </div>
            </div>

            <div id="dashboard-chart" class="chart-mockup d-flex" style="--bar-count: {{ max(count($chartBars ?? []), 1) }};">
                @forelse(($chartBars ?? []) as $bar)
                <div
                    class="chart-col"
                    style="height: {{ $bar['height'] }}%"
                    tabindex="0"
                    role="button"
                    aria-label="{{ $bar['label'] }} - Doanh thu: {{ $bar['tooltip_value'] ?? number_format($bar['value'], 0, ',', '.').'đ' }}. Nhấn để xem chi tiết"
                    title="{{ $bar['label'] }}: Doanh thu {{ $bar['tooltip_value'] ?? number_format($bar['value'], 0, ',', '.').'đ' }} — Nhấn để xem chi tiết"
                    data-label="{{ $bar['label'] }}"
                    data-value="{{ $bar['tooltip_value'] ?? number_format($bar['value'], 0, ',', '.').'đ' }}"
                    data-drilldown="revenue"
                    data-from="{{ $bar['from']->format('Y-m-d H:i:s') }}"
                    data-to="{{ $bar['to']->format('Y-m-d H:i:s') }}">
                </div>
                @empty
                <div class="chart-col" style="height: 15%"></div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="admin-card p-4 h-100">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <h3 class="h5 fw-bold mb-1">Món bán chạy</h3>
                    <p class="text-secondary small mb-0">Xếp hạng sản phẩm trong kỳ đang chọn.</p>
                </div>
                <a href="{{ route('admin.products.index') }}" class="btn btn-link text-primary p-0 text-decoration-none small">Xem tất</a>
            </div>

            <div id="top-products-list" class="d-flex flex-column gap-3">
                @forelse(($topProducts ?? []) as $topProduct)
                <div class="d-flex align-items-center gap-3 p-2 rounded-3" tabindex="0" role="button" data-drilldown="product_sales" data-product-id="{{ $topProduct['id'] }}" style="transition: background 0.2s; cursor: pointer;" onmouseover="this.style.background='var(--a-bg-subtle)'" onmouseout="this.style.background='transparent'">
                    <div class="admin-thumb" style="width: 50px; height: 50px; border-radius: var(--radius-md);">
                        <img src="{{ $topProduct['image_url'] }}" alt="{{ $topProduct['name'] }}">
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold fs-6">{{ $topProduct['name'] }}</div>
                        <div class="text-secondary small">{{ $topProduct['sku'] }}</div>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold text-primary">{{ number_format($topProduct['sold_qty']) }} <span class="fw-normal text-secondary small">ly</span></div>
                        <div class="text-secondary small">{{ number_format((float) ($topProduct['revenue'] ?? 0), 0, ',', '.') }}đ</div>
                    </div>
                </div>
                @empty
                <div class="text-secondary small">Chưa đủ dữ liệu bán hàng để xếp hạng.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        let chartDatasets = @json($chartDatasets ?? []);
        const dashboardBranchId = @json($dashboardBranch?->id);
        const dashboardScopeLabel = @json($dashboardScopeLabel);
        const dashboardQueryBase = @json($dashboardQueryBase);
        let selectedPeriodKey = @json($selectedPeriod);
        const selectedPeriodStart = @json($selectedPeriodStart);
        const selectedPeriodEnd = @json($selectedPeriodEnd);
        const initialTimeComparison = @json($timeComparison);
        let dashboardMatrixState = {
            period_count: Number(initialTimeComparison.period_count ?? 8),
            group: initialTimeComparison.group ?? 'week',
            period_type: initialTimeComparison.period_type ?? selectedPeriodKey,
        };
        const periodLinks = Array.from(document.querySelectorAll('.period-segment[data-period]'));
        const customPeriodButton = document.querySelector('.period-segment[data-period="custom"]');
        const customPeriodPopover = document.getElementById('dashboard-custom-period');
        const customPeriodStartInput = document.getElementById('dashboard-custom-start');
        const customPeriodEndInput = document.getElementById('dashboard-custom-end');
        const customPeriodApplyButton = document.getElementById('dashboard-custom-apply');
        const customPeriodError = document.getElementById('dashboard-custom-error');
        let customPeriodOpen = selectedPeriodKey === 'custom';
        const dashboardQueryKeys = ['period', 'date', 'week', 'month', 'year', 'start_date', 'end_date', 'compare_type', 'compare_date', 'compare_month', 'compare_year', 'compare_start_date', 'compare_end_date', 'admin_matrix_periods', 'admin_matrix_metric'];
        const timeComparisonPeriodOptionsContainer = document.getElementById('dashboard-time-comparison-period-options');
        const timeComparisonExportLink = document.getElementById('dashboard-time-comparison-export');
        const timeComparisonTableWrap = document.getElementById('dashboard-time-comparison-table');
        const timeComparisonCard = document.getElementById('dashboard-time-comparison-card');

        const formatCurrency = (value) => {
            try {
                const n = Number(value || 0);
                return n.toLocaleString('vi-VN') + 'đ';
            } catch (e) {
                return value;
            }
        };

        const escapeHtml = (value) => {
            return String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#39;');
        };

        const normalizeMatrixState = (state = {}) => ({
            period_count: Number(state.period_count || dashboardMatrixState.period_count || 8),
            group: state.group || dashboardMatrixState.group || 'week',
            period_type: state.period_type || dashboardMatrixState.period_type || selectedPeriodKey,
        });

        const renderTimeComparisonPeriodButtons = (data) => {
            if (!timeComparisonPeriodOptionsContainer) {
                return;
            }

            const periodCount = Number(data?.period_count || dashboardMatrixState.period_count || 8);
            const options = Array.isArray(data?.period_options) ? data.period_options : [];
            timeComparisonPeriodOptionsContainer.innerHTML = options.map((option) => {
                const value = Number(option.value || 0);
                const label = escapeHtml(option.label || `${value} kỳ`);
                const active = value === periodCount ? 'active' : '';

                return `<button type="button" class="comparison-pill-button ${active}" data-dashboard-matrix-period="${value}">${label}</button>`;
            }).join('');
        };

        const renderTimeComparisonTable = (data) => {
            if (!timeComparisonTableWrap) {
                return;
            }

            const rows = Array.isArray(data?.rows) ? data.rows : [];

            if (rows.length === 0) {
                timeComparisonTableWrap.innerHTML = '<div class="comparison-empty">Chưa có dữ liệu trong khoảng này.</div>';
                return;
            }

            const header = `
                <div class="comparison-list-head">
                    <div>Kỳ</div>
                    <div class="text-end">Doanh thu</div>
                    <div class="text-end">Số đơn</div>
                    <div class="text-end">Trung bình/đơn</div>
                    <div class="text-end">So với kỳ trước</div>
                </div>
                <div class="comparison-list">
                    ${rows.map((row) => {
                        const latest = row.latest_change || {};
                        const revenueChange = latest.revenue || { type: 'insufficient', label: 'Chưa đủ dữ liệu' };
                        const orderChange = latest.orders || { type: 'insufficient', label: 'Chưa đủ dữ liệu' };
                        const changeType = revenueChange.type || 'insufficient';

                        return `
                            <div class="comparison-row ${row.is_partial ? 'is-partial' : ''}">
                                <div class="comparison-cell comparison-period-cell">
                                    <div class="comparison-period-title">
                                        <strong>${escapeHtml(row.label || '')}</strong>
                                        ${row.is_partial ? '<span class="comparison-period-pill">Đang diễn ra</span>' : ''}
                                    </div>
                                </div>
                                <div class="comparison-cell comparison-number" tabindex="0" role="button" data-drilldown="revenue" data-from="${escapeHtml(row.start_at || '')}" data-to="${escapeHtml(row.end_at || '')}">${escapeHtml(formatCurrency(row.revenue || 0))}</div>
                                <div class="comparison-cell comparison-number" tabindex="0" role="button" data-drilldown="orders" data-from="${escapeHtml(row.start_at || '')}" data-to="${escapeHtml(row.end_at || '')}">${escapeHtml(Number(row.valid_order_count || 0).toLocaleString('vi-VN'))}</div>
                                <div class="comparison-cell comparison-number" tabindex="0" role="button" data-drilldown="average_order_value" data-from="${escapeHtml(row.start_at || '')}" data-to="${escapeHtml(row.end_at || '')}">${escapeHtml(formatCurrency(row.average_order_value || 0))}</div>
                                <div class="comparison-cell">
                                    <div class="comparison-change ${escapeHtml(changeType)}">
                                        <span class="comparison-change-badge">${escapeHtml(revenueChange.label || 'Chưa đủ dữ liệu')}</span>
                                        <span class="comparison-change-sub">${escapeHtml(orderChange.label || 'Chưa đủ dữ liệu')}</span>
                                    </div>
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>
            `;

            timeComparisonTableWrap.innerHTML = header;
        };

        const renderTimeComparison = (data) => {
            if (!data) {
                return;
            }

            dashboardMatrixState = normalizeMatrixState(data);
            renderTimeComparisonPeriodButtons(data);
            renderTimeComparisonTable(data);

            if (timeComparisonExportLink) {
                timeComparisonExportLink.href = buildTimeComparisonExportUrl();
            }
        };

        const buildTimeComparisonExportUrl = (periodKey = selectedPeriodKey, overrides = {}) => {
            const url = new URL('/admin/dashboard/export', window.location.origin);
            applyDashboardQueryParams(url, periodKey, overrides);
            return url.toString();
        };

        const pad2 = (value) => String(value).padStart(2, '0');

        const formatDateInput = (date) => {
            const year = date.getFullYear();
            const month = pad2(date.getMonth() + 1);
            const day = pad2(date.getDate());
            return `${year}-${month}-${day}`;
        };

        const formatIsoWeek = (date) => {
            const temp = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
            const dayNum = temp.getUTCDay() || 7;
            temp.setUTCDate(temp.getUTCDate() + 4 - dayNum);
            const yearStart = new Date(Date.UTC(temp.getUTCFullYear(), 0, 1));
            const weekNo = Math.ceil((((temp - yearStart) / 86400000) + 1) / 7);
            return `${temp.getUTCFullYear()}-W${pad2(weekNo)}`;
        };

        const getPresetPeriodParams = (periodKey) => {
            const now = new Date();

            switch (periodKey) {
                case 'day':
                    return {
                        period: 'day',
                        date: formatDateInput(now),
                    };
                case 'week':
                    return {
                        period: 'week',
                        week: formatIsoWeek(now),
                    };
                case 'month':
                    return {
                        period: 'month',
                        month: `${now.getFullYear()}-${pad2(now.getMonth() + 1)}`,
                    };
                case 'year':
                    return {
                        period: 'year',
                        year: String(now.getFullYear()),
                    };
                case 'custom':
                    return {
                        period: 'custom',
                        start_date: customPeriodStartInput ? customPeriodStartInput.value : selectedPeriodStart,
                        end_date: customPeriodEndInput ? customPeriodEndInput.value : selectedPeriodEnd,
                    };
                default:
                    return {
                        period: 'week',
                    };
            }
        };

        const buildDashboardParams = (periodKey, overrides = {}) => {
            const matrixState = normalizeMatrixState({
                period_count: overrides.admin_matrix_periods ?? dashboardMatrixState.period_count,
                group: dashboardMatrixState.group,
                period_type: periodKey,
            });
            const params = {
                ...dashboardQueryBase,
                ...getPresetPeriodParams(periodKey),
                admin_matrix_periods: matrixState.period_count,
                ...overrides,
            };

            return Object.fromEntries(Object.entries(params).filter(([, value]) => value !== null && value !== undefined && value !== ''));
        };

        const applyDashboardQueryParams = (url, periodKey, overrides = {}) => {
            const params = buildDashboardParams(periodKey, overrides);
            dashboardQueryKeys.forEach((key) => url.searchParams.delete(key));
            Object.entries(params).forEach(([key, value]) => {
                url.searchParams.set(key, value);
            });

            return url;
        };

        const setActivePeriodLink = (periodKey) => {
            periodLinks.forEach((el) => el.classList.toggle('active', el.dataset.period === periodKey));
            if (customPeriodButton) {
                customPeriodButton.classList.toggle('active', periodKey === 'custom');
            }
        };

        const setCustomPeriodError = (message = '') => {
            if (!customPeriodError) return;
            customPeriodError.textContent = message;
            customPeriodError.classList.toggle('d-none', !message);
        };

        const setCustomPeriodVisible = (visible) => {
            customPeriodOpen = Boolean(visible);
            if (!customPeriodPopover) return;
            customPeriodPopover.hidden = !customPeriodOpen;
            if (customPeriodOpen) {
                setCustomPeriodError('');
            }
            if (customPeriodButton) {
                customPeriodButton.setAttribute('aria-expanded', customPeriodOpen ? 'true' : 'false');
            }
        };

        const toggleCustomPeriodPopover = () => {
            setCustomPeriodVisible(!customPeriodOpen);
        };

        const applyCustomPeriod = () => {
            if (!customPeriodStartInput || !customPeriodEndInput) {
                return;
            }

            const startValue = customPeriodStartInput.value;
            const endValue = customPeriodEndInput.value;

            if (!startValue || !endValue) {
                setCustomPeriodError('Vui lòng chọn đủ ngày bắt đầu và ngày kết thúc.');
                return;
            }

            setCustomPeriodError('');

            const periodStart = startValue <= endValue ? startValue : endValue;
            const periodEnd = startValue <= endValue ? endValue : startValue;
            customPeriodStartInput.value = periodStart;
            customPeriodEndInput.value = periodEnd;
            setCustomPeriodVisible(false);
            fetchDashboardData('custom', {
                start_date: periodStart,
                end_date: periodEnd,
            });
        };

        const topProductsContainer = document.getElementById('top-products-list');

        const renderTopProducts = (items) => {
            if (!topProductsContainer) {
                return;
            }

            if (!Array.isArray(items) || items.length === 0) {
                topProductsContainer.innerHTML = '<div class="text-secondary small">Chưa đủ dữ liệu bán hàng để xếp hạng.</div>';
                return;
            }

            topProductsContainer.innerHTML = items.map((topProduct) => {
                const imageUrl = topProduct.image_url || '';
                const name = topProduct.name || 'N/A';
                const sku = topProduct.sku || '';
                const soldQty = Number(topProduct.sold_qty || 0).toLocaleString('vi-VN');
                const revenue = formatCurrency(topProduct.revenue || 0);

                return `
                    <div class="d-flex align-items-center gap-3 p-2 rounded-3" tabindex="0" role="button" data-drilldown="product_sales" data-product-id="${Number(topProduct.id || 0)}" style="transition: background 0.2s; cursor: pointer;" onmouseover="this.style.background='var(--a-bg-subtle)'" onmouseout="this.style.background='transparent'">
                        <div class="admin-thumb" style="width: 50px; height: 50px; border-radius: var(--radius-md);">
                            <img src="${escapeHtml(imageUrl)}" alt="${escapeHtml(name)}">
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-bold fs-6">${escapeHtml(name)}</div>
                            <div class="text-secondary small">${escapeHtml(sku)}</div>
                            <div class="text-secondary small" style="font-size: 0.75rem;">${escapeHtml(revenue)} doanh thu</div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold text-primary">${soldQty} <span class="fw-normal text-secondary small">ly</span></div>
                            <div class="text-secondary small">${revenue}</div>
                        </div>
                    </div>
                `;
            }).join('');
        };

        const updateKPIsFromData = (data) => {
            if (!data) return;
            // Update KPI numbers
            const revEl = document.getElementById('kpi-revenue-value');
            const ordEl = document.getElementById('kpi-orders-value');
            const usrEl = document.getElementById('kpi-users-value');
            const prodEl = document.getElementById('kpi-products-value');
            const selLabel = document.getElementById('selected-period-label');
            const revComp = document.getElementById('kpi-revenue-comparison');
            const ordersLabel = document.getElementById('kpi-orders-label');
            const summaryText = document.getElementById('dashboard-summary-text');

            if (revEl && typeof data.totalRevenue !== 'undefined') revEl.textContent = formatCurrency(data.totalRevenue);
            if (ordEl && typeof data.totalOrders !== 'undefined') ordEl.textContent = (data.totalOrders || 0);
            if (usrEl && typeof data.totalUsers !== 'undefined') usrEl.textContent = (data.totalUsers || 0);
            if (prodEl && typeof data.totalProducts !== 'undefined') prodEl.textContent = (data.totalProducts || 0);
            if (selLabel && data.selectedPeriodStat && data.selectedPeriodStat.label) selLabel.textContent = data.selectedPeriodStat.label;
            if (ordersLabel && data.selectedPeriodStat && data.selectedPeriodStat.label) ordersLabel.textContent = data.selectedPeriodStat.label;
            if (summaryText && data.selectedPeriodStat && data.selectedPeriodStat.label) {
                const lowerLabel = data.selectedPeriodStat.label.toLowerCase();
                summaryText.textContent = `Đây là hoạt động kinh doanh ${lowerLabel} của ${dashboardScopeLabel}.`;
            }
            if (revComp && data.comparisonLabel) revComp.textContent = data.comparisonLabel;

            // Update card trends
            if (data.cardTrends) {
                Object.keys(data.cardTrends).forEach((k) => {
                    const card = document.querySelector(`.stat-card[data-kpi="${k}"]`);
                    if (!card) return;
                    const trend = card.querySelector('.stat-trend');
                    if (!trend) return;
                    const info = data.cardTrends[k];
                    trend.className = 'stat-trend ' + (info.direction || 'flat');
                    const iconEl = trend.querySelector('i') || document.createElement('i');
                    iconEl.className = 'bi ' + (info.icon || 'bi-dash');
                    trend.innerHTML = '';
                    trend.appendChild(iconEl);
                    trend.append(' ' + (info.value || '0%'));
                });
            }

            // replace chart datasets for chart rendering
            if (data.chartDatasets) {
                chartDatasets = data.chartDatasets;
            }
            if (data.selectedPeriodStat && typeof window.setDashboardDrilldownDefaults === 'function') {
                window.setDashboardDrilldownDefaults({
                    from: `${data.selectedPeriodStat.start} 00:00:00`,
                    to: `${data.selectedPeriodStat.end} 23:59:59`
                });
            }

            if (typeof renderTopProducts === 'function' && Array.isArray(data.topProducts)) {
                renderTopProducts(data.topProducts);
            }
        };

        const updatePeriodInUrl = (periodKey, overrides = {}) => {
            if (!periodKey) {
                return;
            }

            try {
                const url = new URL(window.location.href);
                applyDashboardQueryParams(url, periodKey, overrides);
                window.history.replaceState({}, '', url.toString());
            } catch (error) {
                console.warn('Không thể cập nhật URL period', error);
            }
        };

        const fetchDashboardData = (period, overrides = {}) => {
            const url = new URL('/admin/dashboard/data', window.location.origin);
            applyDashboardQueryParams(url, period || selectedPeriodKey, overrides);
            return fetch(url.toString(), {
                    credentials: 'same-origin'
                })
                .then((r) => {
                    if (!r.ok) throw new Error('Network response not ok');
                    return r.json();
                })
                .then((json) => {
                    updateKPIsFromData(json);
                    const resolvedPeriod = json.selectedPeriod || period;
                    selectedPeriodKey = resolvedPeriod || selectedPeriodKey;
                    setActivePeriodLink(resolvedPeriod);
                    if (json.timeComparison) {
                        renderTimeComparison(json.timeComparison);
                    }
                    updatePeriodInUrl(resolvedPeriod, overrides);
                    setCustomPeriodVisible(resolvedPeriod === 'custom' ? true : customPeriodOpen);
                    // re-render current chart using existing active card
                    const activeCard = document.querySelector('.chart-trigger.active');
                    const chartType = activeCard ? activeCard.dataset.chartType : 'revenue';
                    renderChart(chartType);
                })
                .catch((err) => {
                    console.error('Dashboard AJAX error', err);
                });
        };

        // attach click handlers to period links to fetch data without reloading
        periodLinks.forEach((link) => {
            link.addEventListener('click', (ev) => {
                const period = link.dataset.period;
                if (period === 'custom') {
                    ev.preventDefault();
                    toggleCustomPeriodPopover();
                    return;
                }

                ev.preventDefault();
                setCustomPeriodVisible(false);
                fetchDashboardData(period);
            });
        });

        document.addEventListener('click', (event) => {
            const periodButton = event.target.closest('[data-dashboard-matrix-period]');
            if (periodButton && timeComparisonCard && timeComparisonCard.contains(periodButton)) {
                event.preventDefault();
                const periodCount = Number(periodButton.dataset.dashboardMatrixPeriod || 0);
                if (periodCount > 0) {
                    fetchDashboardData(selectedPeriodKey, {
                        admin_matrix_periods: periodCount,
                    });
                }
                return;
            }
        });

        if (customPeriodApplyButton) {
            customPeriodApplyButton.addEventListener('click', () => {
                applyCustomPeriod();
            });
        }

        if (customPeriodButton) {
            customPeriodButton.setAttribute('aria-expanded', customPeriodOpen ? 'true' : 'false');
        }

        if (customPeriodPopover) {
            customPeriodPopover.hidden = !customPeriodOpen;
        }

        renderTimeComparison(initialTimeComparison);

        const chartContainer = document.getElementById('dashboard-chart');
        const chartTitle = document.getElementById('chart-title');
        const chartDescription = document.getElementById('chart-description');
        const triggerCards = Array.from(document.querySelectorAll('.chart-trigger'));

        if (!chartContainer || !chartTitle || !chartDescription || triggerCards.length === 0) {
            return;
        }

        const tooltipEl = document.createElement('div');
        tooltipEl.className = 'chart-tooltip';
        chartContainer.appendChild(tooltipEl);

        const getBars = () => Array.from(chartContainer.querySelectorAll('.chart-col'));
        const metricLabels = {
            revenue: 'Doanh thu',
            orders: 'Đơn hàng',
            users: 'Khách hàng mới',
        };

        const clearActiveBars = () => {
            getBars().forEach((barEl) => barEl.classList.remove('active'));
        };

        const hideTooltip = () => {
            tooltipEl.classList.remove('show');
            clearActiveBars();
        };

        const createBarEl = (bar, index) => {
            const barEl = document.createElement('div');
            barEl.className = 'chart-col';
            barEl.style.height = `${Math.max(10, Number(bar.height || 0))}%`;
            barEl.style.animationDelay = `${index * 0.04}s`;
            barEl.setAttribute('tabindex', '0');
            barEl.setAttribute('role', 'button');
            barEl.dataset.label = bar.label || '';
            barEl.dataset.value = bar.tooltip_value || '0';
            const activeMetric = chartContainer.dataset.activeChart || 'revenue';
            const metricLabel = metricLabels[activeMetric] || 'Số liệu';
            barEl.setAttribute('aria-label', `${bar.label || ''} - ${metricLabel}: ${bar.tooltip_value || '0'}. Nhấn để xem chi tiết`);
            barEl.setAttribute('title', `${bar.label || ''}: ${metricLabel} ${bar.tooltip_value || '0'} — Nhấn để xem chi tiết`);
            barEl.dataset.drilldown = activeMetric === 'users' ? 'new_customers' : activeMetric;
            barEl.dataset.from = bar.from || '';
            barEl.dataset.to = bar.to || '';
            return barEl;
        };

        const showTooltipAtPoint = (clientX, clientY) => {
            const bars = getBars();
            if (bars.length === 0) {
                hideTooltip();
                return;
            }

            const rect = chartContainer.getBoundingClientRect();
            const isInside = clientX >= rect.left && clientX <= rect.right && clientY >= rect.top && clientY <= rect.bottom;
            if (!isInside) {
                hideTooltip();
                return;
            }

            let closestBar = bars[0];
            let closestDistance = Number.MAX_SAFE_INTEGER;

            bars.forEach((barEl) => {
                const barRect = barEl.getBoundingClientRect();
                const centerX = (barRect.left + barRect.right) / 2;
                const distance = Math.abs(centerX - clientX);
                if (distance < closestDistance) {
                    closestDistance = distance;
                    closestBar = barEl;
                }
            });

            bars.forEach((barEl) => barEl.classList.toggle('active', barEl === closestBar));

            const label = closestBar.dataset.label || '';
            const value = closestBar.dataset.value || '0';
            const metricLabel = metricLabels[chartContainer.dataset.activeChart || 'revenue'] || 'Số liệu';
            tooltipEl.innerHTML = `<span class="label">${label}</span><span class="value">${metricLabel}: ${value}</span><span class="small d-block mt-1">Nhấn để xem chi tiết</span>`;

            const tooltipGap = 12;
            const maxX = rect.width - tooltipEl.offsetWidth - 8;
            const maxY = rect.height - tooltipEl.offsetHeight - 8;

            let x = clientX - rect.left + tooltipGap;
            let y = clientY - rect.top + tooltipGap;

            if (x > maxX) {
                x = clientX - rect.left - tooltipEl.offsetWidth - tooltipGap;
            }
            if (x < 8) {
                x = 8;
            }
            if (y > maxY) {
                y = clientY - rect.top - tooltipEl.offsetHeight - tooltipGap;
            }
            if (y < 8) {
                y = 8;
            }

            tooltipEl.style.transform = `translate3d(${x}px, ${y}px, 0)`;
            tooltipEl.classList.add('show');
        };

        const renderChart = (type) => {
            const dataset = chartDatasets[type];
            if (!dataset) {
                return;
            }

            const bars = Array.isArray(dataset.bars) ? dataset.bars : [];
            chartContainer.dataset.activeChart = type;
            chartContainer.style.setProperty('--bar-count', String(Math.max(bars.length, 1)));
            chartTitle.textContent = dataset.title || 'Phân tích dữ liệu';
            chartDescription.textContent = dataset.description || '';
            chartContainer.innerHTML = '';
            chartContainer.appendChild(tooltipEl);
            hideTooltip();

            if (bars.length === 0) {
                const emptyBar = document.createElement('div');
                emptyBar.className = 'chart-col';
                emptyBar.style.height = '15%';
                chartContainer.appendChild(emptyBar);
                return;
            }

            bars.forEach((bar, index) => chartContainer.appendChild(createBarEl(bar, index)));
        };

        const setActiveTrigger = (targetType) => {
            triggerCards.forEach((card) => {
                const isActive = card.dataset.chartType === targetType;
                card.classList.toggle('active', isActive);
                card.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });
        };

        triggerCards.forEach((card) => {
            const activate = () => {
                const type = card.dataset.chartType;
                if (!type) {
                    return;
                }

                renderChart(type);
                setActiveTrigger(type);
            };

            card.addEventListener('click', activate);
            card.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    activate();
                }
            });
        });

        chartContainer.addEventListener('mousemove', (event) => {
            showTooltipAtPoint(event.clientX, event.clientY);
        });

        chartContainer.addEventListener('mouseleave', () => {
            hideTooltip();
        });

        chartContainer.addEventListener('click', (event) => {
            showTooltipAtPoint(event.clientX, event.clientY);
        });

        chartContainer.addEventListener('focusin', (event) => {
            const targetBar = event.target.closest('.chart-col');
            if (!targetBar) {
                return;
            }

            const barRect = targetBar.getBoundingClientRect();
            showTooltipAtPoint(barRect.left + (barRect.width / 2), barRect.top + 10);
        });

        chartContainer.addEventListener('focusout', () => {
            window.setTimeout(() => {
                if (!chartContainer.contains(document.activeElement)) {
                    hideTooltip();
                }
            }, 0);
        });

        chartContainer.addEventListener('touchstart', (event) => {
            const touch = event.touches[0];
            if (!touch) {
                return;
            }
            showTooltipAtPoint(touch.clientX, touch.clientY);
        }, {
            passive: true
        });

        chartContainer.addEventListener('touchmove', (event) => {
            const touch = event.touches[0];
            if (!touch) {
                return;
            }
            showTooltipAtPoint(touch.clientX, touch.clientY);
        }, {
            passive: true
        });

        chartContainer.addEventListener('touchend', () => {
            hideTooltip();
        }, {
            passive: true
        });

        chartContainer.addEventListener('touchcancel', () => {
            hideTooltip();
        }, {
            passive: true
        });

        renderChart('revenue');
        setActiveTrigger('revenue');
        setActivePeriodLink(selectedPeriodKey);
        updatePeriodInUrl(selectedPeriodKey);
    });
</script>

@php
    $drilldownEndpoint = route('admin.dashboard.drilldown');
    $drilldownDefaults = [
        'from' => ($selectedPeriodStat['start'] ?? now()->format('Y-m-d')).' 00:00:00',
        'to' => ($selectedPeriodStat['end'] ?? now()->format('Y-m-d')).' 23:59:59',
        'branch_id' => $dashboardBranch?->id,
    ];
@endphp
@include('admin.partials.dashboard-drilldown')

@endsection
