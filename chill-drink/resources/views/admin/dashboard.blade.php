@extends('layouts.admin')

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
    }

    .period-segment {
        padding: 6px 16px;
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--a-muted);
        border-radius: var(--radius-full);
        cursor: pointer;
        transition: all 0.2s ease;
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
</style>

@php
$initialPeriodLabel = $selectedPeriodStat['label'] ?? 'Hôm nay';
$initialPeriodLabelLower = \Illuminate\Support\Str::lower($initialPeriodLabel);
@endphp

<div class="dashboard-header d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3">
    <div>
        <h2 class="h3 fw-bold mb-1">Hi, Admin 👋</h2>
        <p id="dashboard-summary-text" class="text-secondary mb-0">Đây là hoạt động kinh doanh {{ $initialPeriodLabelLower }} của cửa hàng.</p>
    </div>
    <div class="period-segmented-control">
        <a href="{{ route('admin.dashboard', ['period' => 'today']) }}" data-period="today" class="period-segment text-decoration-none {{ $selectedPeriod === 'today' ? 'active' : '' }}">Hôm nay</a>
        <a href="{{ route('admin.dashboard', ['period' => 'week']) }}" data-period="week" class="period-segment text-decoration-none {{ $selectedPeriod === 'week' ? 'active' : '' }}">Tuần này</a>
        <a href="{{ route('admin.dashboard', ['period' => 'month']) }}" data-period="month" class="period-segment text-decoration-none {{ $selectedPeriod === 'month' ? 'active' : '' }}">Tháng này</a>
        <a href="{{ route('admin.dashboard', ['period' => 'year']) }}" data-period="year" class="period-segment text-decoration-none {{ $selectedPeriod === 'year' ? 'active' : '' }}">Năm nay</a>
    </div>
</div>

<div class="row g-4 mb-5">
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
                    role="img"
                    aria-label="{{ $bar['label'] }} - {{ $bar['tooltip_value'] ?? number_format($bar['value'], 0, ',', '.').'đ' }}"
                    data-label="{{ $bar['label'] }}"
                    data-value="{{ $bar['tooltip_value'] ?? number_format($bar['value'], 0, ',', '.').'đ' }}">
                </div>
                @empty
                <div class="chart-col" style="height: 15%"></div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="admin-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="h5 fw-bold mb-0">Món bán chạy</h3>
                <a href="{{ route('admin.products.index') }}" class="btn btn-link text-primary p-0 text-decoration-none small">Xem tất</a>
            </div>

            <div id="top-products-list" class="d-flex flex-column gap-3">
                @forelse(($topProducts ?? []) as $topProduct)
                <div class="d-flex align-items-center gap-3 p-2 rounded-3" style="transition: background 0.2s; cursor: pointer;" onmouseover="this.style.background='var(--a-bg-subtle)'" onmouseout="this.style.background='transparent'">
                    <div class="admin-thumb" style="width: 50px; height: 50px; border-radius: var(--radius-md);">
                        <img src="{{ $topProduct['image_url'] }}" alt="{{ $topProduct['name'] }}">
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold fs-6">{{ $topProduct['name'] }}</div>
                        <div class="text-secondary small">{{ $topProduct['sku'] }}</div>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold text-primary">{{ number_format($topProduct['sold_qty']) }} <span class="fw-normal text-secondary small">ly</span></div>
                    </div>
                </div>
                @empty
                <div class="text-secondary small">Chưa đủ dữ liệu bán hàng để xếp hạng.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="admin-card overflow-hidden">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 p-4 border-bottom">
        <div>
            <h3 class="h5 fw-bold mb-1">Đơn hàng mới nhất</h3>
            <p class="text-secondary small mb-0">Quản lý và theo dõi các đơn hàng vửa được đặt.</p>
        </div>
        <a href="{{ route('admin.orders.index') }}" class="btn btn-primary btn-sm rounded-pill px-4">Tất cả đơn hàng</a>
    </div>
    <div class="table-responsive">
        <table class="table admin-table align-middle">
            <thead>
                <tr>
                    <th>Đơn hàng</th>
                    <th>Khách hàng</th>
                    <th>Ngày đặt</th>
                    <th>Thanh toán</th>
                    <th>Trạng thái</th>
                    <th class="text-end">Tổng tiền</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentOrders as $order)
                @php
                $statusClass = 'pending';
                $statusText = 'Đang xử lý';
                if(isset($order->status)) {
                if($order->status === 'completed') { $statusClass = 'completed'; $statusText = 'Hoàn tất'; }
                if($order->status === 'cancelled') { $statusClass = 'cancelled'; $statusText = 'Đã hủy'; }
                }
                @endphp
                <tr>
                    <td class="fw-bold">
                        <a href="#" class="text-primary text-decoration-none">#{{ $order->id }}</a>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="admin-avatar avatar-sm">{{ mb_substr($order->user->name ?? 'K', 0, 1) }}</span>
                            <span class="fw-semibold">{{ $order->user->name ?? 'Khách hàng' }}</span>
                        </div>
                    </td>
                    <td class="text-secondary small">{{ optional($order->created_at)->format('d/m/Y H:i') ?? 'Vừa xong' }}</td>
                    <td>
                        <span class="badge badge-soft-muted"><i class="bi bi-wallet2 me-1"></i> {{ strtoupper(str_replace('_', ' ', $order->payment_method ?? 'COD')) }}</span>
                    </td>
                    <td>
                        <span class="fw-semibold d-inline-flex align-items-center" style="font-size: 0.8125rem;">
                            <span class="status-dot {{ $statusClass }}"></span> {{ $statusText }}
                        </span>
                    </td>
                    <td class="text-end fw-bold">{{ number_format($order->total_price ?? $order->total ?? 0, 0, ',', '.') }}đ</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-5">
                        <div class="admin-empty-state mx-auto" style="max-width: 400px; border: none; background: transparent;">
                            <span class="admin-icon-dot mx-auto mb-3"><i class="bi bi-receipt"></i></span>
                            <div class="fw-bold text-dark mb-1">Chưa có đơn hàng nào</div>
                            <p class="small text-secondary mb-0">Các đơn hàng mới nhất sẽ hiển thị tại đây.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        let chartDatasets = @json($chartDatasets ?? []);
        // Period links (AJAX)
        const periodLinks = Array.from(document.querySelectorAll('.period-segment'));

        const formatCurrency = (value) => {
            try {
                const n = Number(value || 0);
                return n.toLocaleString('vi-VN') + 'đ';
            } catch (e) {
                return value;
            }
        };

        const setActivePeriodLink = (periodKey) => {
            periodLinks.forEach((el) => el.classList.toggle('active', el.dataset.period === periodKey));
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

                return `
                    <div class="d-flex align-items-center gap-3 p-2 rounded-3" style="transition: background 0.2s; cursor: pointer;" onmouseover="this.style.background='var(--a-bg-subtle)'" onmouseout="this.style.background='transparent'">
                        <div class="admin-thumb" style="width: 50px; height: 50px; border-radius: var(--radius-md);">
                            <img src="${imageUrl}" alt="${name}">
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-bold fs-6">${name}</div>
                            <div class="text-secondary small">${sku}</div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold text-primary">${soldQty} <span class="fw-normal text-secondary small">ly</span></div>
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
                summaryText.textContent = `Đây là hoạt động kinh doanh ${lowerLabel} của cửa hàng.`;
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

            if (typeof renderTopProducts === 'function' && Array.isArray(data.topProducts)) {
                renderTopProducts(data.topProducts);
            }
        };

        const updatePeriodInUrl = (periodKey) => {
            if (!periodKey) {
                return;
            }

            try {
                const url = new URL(window.location.href);
                url.searchParams.set('period', periodKey);
                window.history.replaceState({}, '', url.toString());
            } catch (error) {
                console.warn('Không thể cập nhật URL period', error);
            }
        };

        const fetchDashboardData = (period) => {
            const url = new URL('/admin/dashboard/data', window.location.origin);
            if (period) url.searchParams.set('period', period);
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
                    setActivePeriodLink(resolvedPeriod);
                    updatePeriodInUrl(resolvedPeriod);
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
                ev.preventDefault();
                const period = link.dataset.period;
                fetchDashboardData(period);
            });
        });
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
            barEl.setAttribute('role', 'img');
            barEl.setAttribute('aria-label', `${bar.label || ''} - ${bar.tooltip_value || '0'}`);
            barEl.dataset.label = bar.label || '';
            barEl.dataset.value = bar.tooltip_value || '0';
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
            tooltipEl.innerHTML = `<span class="label">${label}</span><span class="value">${value}</span>`;

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
        updatePeriodInUrl(@json($selectedPeriod));
    });
</script>

@endsection
