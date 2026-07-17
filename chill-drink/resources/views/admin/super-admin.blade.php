@extends('layouts.super-admin')

@section('page-title', 'Dashboard')

@section('content')
<style>
    :root {
        --sa-green: #0d9373;
        --sa-green-dark: #067a5f;
        --sa-green-soft: #e7f7f2;
        --sa-ink: #111827;
        --sa-muted: #6b7280;
        --sa-border: #e1e6e4;
    }

    .sa-page {
        display: grid;
        gap: 1rem;
    }

    .sa-header, .sa-panel-header, .sa-stat-top, .sa-health-row, .sa-security-item,
    .sa-admin-cell, .sa-actions, .sa-pagination, .sa-chart-meta {
        display: flex;
        align-items: center;
    }

    .sa-header { justify-content: space-between; gap: 1rem; }
    .sa-kicker { margin: 0 0 0.25rem; color: var(--sa-green); font-size: 0.68rem; font-weight: 800; text-transform: uppercase; }
    .sa-title { margin: 0; color: var(--sa-ink); font-size: 1.45rem; font-weight: 800; }
    .sa-subtitle { margin: 0.3rem 0 0; color: var(--sa-muted); font-size: 0.78rem; }

    .sa-btn {
        min-height: 38px;
        border: 1px solid var(--sa-border);
        border-radius: 7px;
        padding: 0.5rem 0.75rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        background: #fff;
        color: var(--sa-ink);
        font-size: 0.72rem;
        font-weight: 750;
        text-decoration: none;
    }

    .sa-btn:hover { border-color: var(--sa-green); color: var(--sa-green-dark); }
    .sa-btn-primary { border-color: var(--sa-green); background: var(--sa-green); color: #fff; }
    .sa-btn-primary:hover { background: var(--sa-green-dark); color: #fff; }

    .sa-alert { margin: 0; border-radius: 7px; font-size: 0.74rem; }

    .sa-stats { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 0.75rem; }
    .sa-stat { min-height: 104px; padding: 0.9rem; border: 1px solid var(--sa-border); border-radius: 8px; background: #fff; }
    .sa-stat-top { justify-content: space-between; gap: 0.5rem; }
    .sa-stat-icon { width: 34px; height: 34px; border-radius: 7px; display: inline-flex; align-items: center; justify-content: center; background: var(--sa-green-soft); color: var(--sa-green); }
    .sa-stat-note { color: var(--sa-muted); font-size: 0.62rem; font-weight: 650; }
    .sa-stat-value { margin-top: 0.55rem; color: var(--sa-ink); font-size: 1.25rem; font-weight: 800; line-height: 1.05; }
    .sa-stat-label { margin-top: 0.25rem; color: var(--sa-muted); font-size: 0.68rem; font-weight: 650; }

    .sa-grid-2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
    .sa-grid-main { display: grid; grid-template-columns: minmax(0, 1.65fr) minmax(300px, 0.7fr); gap: 1rem; align-items: start; }
    .sa-panel { border: 1px solid var(--sa-border); border-radius: 8px; background: #fff; overflow: hidden; scroll-margin-top: 82px; }
    .sa-panel-header { min-height: 58px; padding: 0.75rem 0.9rem; border-bottom: 1px solid var(--sa-border); justify-content: space-between; gap: 0.75rem; }
    .sa-panel-title { margin: 0; color: var(--sa-ink); font-size: 0.86rem; font-weight: 800; }
    .sa-panel-note { margin: 0.15rem 0 0; color: var(--sa-muted); font-size: 0.64rem; }

    .sa-chart { height: 238px; padding: 0.9rem; display: flex; flex-direction: column; }
    .sa-chart-meta { justify-content: space-between; margin-bottom: 0.65rem; color: var(--sa-muted); font-size: 0.64rem; }
    .sa-chart-bars { min-height: 0; flex: 1; display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); align-items: end; gap: 0.65rem; border-bottom: 1px solid var(--sa-border); }
    .sa-chart-bars.six { grid-template-columns: repeat(6, minmax(0, 1fr)); }
    .sa-chart-column { height: 100%; display: flex; flex-direction: column; justify-content: flex-end; align-items: center; gap: 0.35rem; }
    .sa-chart-value { min-height: 18px; color: var(--sa-muted); font-size: 0.56rem; font-weight: 700; white-space: nowrap; }
    .sa-chart-bar { width: min(38px, 72%); min-height: 4px; border-radius: 4px 4px 0 0; background: var(--sa-green); }
    .sa-chart-bar.alt { background: #4f8fd9; }
    .sa-chart-label { padding-top: 0.45rem; color: var(--sa-muted); font-size: 0.58rem; font-weight: 650; }

    .sa-filter-form { padding: 0.8rem 0.9rem; border-bottom: 1px solid var(--sa-border); display: grid; grid-template-columns: minmax(200px, 1.4fr) repeat(3, minmax(130px, 0.65fr)) auto; gap: 0.6rem; }
    .sa-control { min-width: 0; height: 38px; border: 1px solid var(--sa-border); border-radius: 7px; padding: 0 0.65rem; background: #fff; color: var(--sa-ink); font-size: 0.7rem; outline: 0; }
    .sa-control:focus { border-color: var(--sa-green); box-shadow: 0 0 0 3px rgba(13,147,115,0.1); }
    .sa-filter-actions { display: flex; gap: 0.4rem; }

    .sa-table-wrap { overflow-x: auto; }
    .sa-table { width: 100%; min-width: 970px; border-collapse: collapse; }
    .sa-table th { padding: 0.62rem 0.8rem; background: #f8faf9; color: var(--sa-muted); font-size: 0.6rem; font-weight: 800; text-align: left; text-transform: uppercase; }
    .sa-table td { padding: 0.72rem 0.8rem; border-top: 1px solid #edf1ef; color: #374151; font-size: 0.69rem; vertical-align: middle; }
    .sa-admin-cell { gap: 0.6rem; }
    .sa-avatar { width: 34px; height: 34px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 auto; overflow: hidden; background: var(--sa-green-soft); color: var(--sa-green-dark); font-size: 0.68rem; font-weight: 800; }
    .sa-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .sa-admin-name { color: var(--sa-ink); font-weight: 800; }
    .sa-admin-email { margin-top: 0.1rem; color: var(--sa-muted); font-size: 0.62rem; }
    .sa-role, .sa-state, .sa-presence { border-radius: 999px; padding: 0.26rem 0.5rem; display: inline-flex; align-items: center; gap: 0.3rem; font-size: 0.6rem; font-weight: 800; white-space: nowrap; }
    .sa-role-super { background: #fff3cd; color: #8a4b08; }
    .sa-role-admin { background: #e0f2fe; color: #075985; }
    .sa-state-active { background: #dcfce7; color: #166534; }
    .sa-state-locked { background: #fee2e2; color: #991b1b; }
    .sa-presence::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
    .sa-presence-online { color: #15803d; background: #f0fdf4; }
    .sa-presence-away { color: #a16207; background: #fefce8; }
    .sa-presence-offline { color: #6b7280; background: #f3f4f6; }
    .sa-actions { gap: 0.35rem; }
    .sa-action-btn { width: 30px; height: 30px; border: 1px solid var(--sa-border); border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; background: #fff; color: var(--sa-muted); text-decoration: none; }
    .sa-action-btn:hover { border-color: var(--sa-green); color: var(--sa-green); }
    .sa-action-btn.danger:hover { border-color: #dc2626; color: #dc2626; }

    .sa-pagination { min-height: 52px; padding: 0.7rem 0.9rem; border-top: 1px solid var(--sa-border); justify-content: space-between; gap: 1rem; color: var(--sa-muted); font-size: 0.64rem; }
    .sa-page-links { display: flex; gap: 0.3rem; }
    .sa-page-link { min-width: 30px; height: 30px; border: 1px solid var(--sa-border); border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; background: #fff; color: var(--sa-muted); text-decoration: none; font-size: 0.64rem; }
    .sa-page-link.active { border-color: var(--sa-green); background: var(--sa-green); color: #fff; }
    .sa-page-link.disabled { pointer-events: none; opacity: 0.45; }

    .sa-activity-list { padding: 0.25rem 0.9rem; }
    .sa-activity { position: relative; padding: 0.72rem 0 0.72rem 1.8rem; border-bottom: 1px solid #edf1ef; }
    .sa-activity:last-child { border-bottom: 0; }
    .sa-activity-icon { position: absolute; left: 0; top: 0.76rem; width: 24px; height: 24px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; background: var(--sa-green-soft); color: var(--sa-green); font-size: 0.68rem; }
    .sa-activity p { margin: 0; color: #374151; font-size: 0.67rem; line-height: 1.45; }
    .sa-activity strong { color: var(--sa-ink); }
    .sa-activity time { display: block; margin-top: 0.12rem; color: #9ca3af; font-size: 0.58rem; }
    .sa-empty { min-height: 170px; padding: 1.25rem; display: flex; flex-direction: column; align-items: center; justify-content: center; color: var(--sa-muted); text-align: center; }
    .sa-empty i { margin-bottom: 0.45rem; color: var(--sa-green); font-size: 1.4rem; }
    .sa-empty strong { color: var(--sa-ink); font-size: 0.72rem; }

    .sa-security-grid { padding: 0.9rem; display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.6rem; }
    .sa-security-item { min-height: 72px; padding: 0.7rem; border: 1px solid var(--sa-border); border-radius: 7px; gap: 0.55rem; }
    .sa-security-item i { color: var(--sa-green); font-size: 1rem; }
    .sa-security-value { color: var(--sa-ink); font-size: 1rem; font-weight: 800; }
    .sa-security-label { color: var(--sa-muted); font-size: 0.6rem; }

    .sa-health-list { padding: 0.3rem 0.9rem; }
    .sa-health-row { min-height: 52px; border-bottom: 1px solid #edf1ef; justify-content: space-between; gap: 1rem; }
    .sa-health-row:last-child { border-bottom: 0; }
    .sa-health-name { display: flex; align-items: center; gap: 0.5rem; color: var(--sa-ink); font-size: 0.68rem; font-weight: 750; }
    .sa-health-name i { color: var(--sa-green); }
    .sa-health-value { color: var(--sa-muted); font-size: 0.64rem; font-weight: 650; text-align: right; }
    .sa-health-value.success { color: #15803d; }
    .sa-health-value.danger { color: #b91c1c; }

    .sa-permissions { padding: 0.9rem; display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.55rem; }
    .sa-permission { min-height: 52px; padding: 0.65rem; border: 1px solid var(--sa-border); border-radius: 7px; display: flex; align-items: center; gap: 0.5rem; color: #374151; font-size: 0.66rem; font-weight: 700; }
    .sa-permission i { color: var(--sa-green); }

    @media (max-width: 1399.98px) {
        .sa-grid-main { grid-template-columns: 1fr; }
        .sa-filter-form { grid-template-columns: minmax(200px, 1fr) repeat(3, minmax(120px, 0.7fr)); }
        .sa-filter-actions { grid-column: 1 / -1; }
    }
    @media (max-width: 1199.98px) {
        .sa-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .sa-security-grid, .sa-permissions { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 767.98px) {
        .sa-header { align-items: stretch; flex-direction: column; }
        .sa-grid-2 { grid-template-columns: 1fr; }
        .sa-filter-form { grid-template-columns: 1fr; }
        .sa-filter-actions { grid-column: auto; }
        .sa-pagination { align-items: flex-start; flex-direction: column; }
    }
    @media (max-width: 575.98px) {
        .sa-stats, .sa-security-grid, .sa-permissions { grid-template-columns: 1fr; }
        .sa-chart-bars { gap: 0.25rem; }
        .sa-chart-value { font-size: 0.5rem; }
    }
</style>

<div class="sa-page">
    <header class="sa-header">
        <div>
            <p class="sa-kicker">Tổng quan hệ thống</p>
            <h1 class="sa-title">Super Admin Dashboard</h1>
            <p class="sa-subtitle">Quản trị tài khoản, quyền truy cập, bảo mật và tình trạng nền tảng Chill Drink.</p>
        </div>
        <div style="display: flex; flex-wrap: wrap; gap: 0.75rem;">
            <button type="button" class="sa-btn sa-btn-primary" data-bs-toggle="modal" data-bs-target="#createAdminModal">
                <i class="bi bi-person-plus"></i> Thêm Admin
            </button>
        </div>
    </header>

    @if(session('success'))
        <div class="alert alert-success sa-alert"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger sa-alert"><i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}</div>
    @endif

    <!-- KPI Cards chính - Metrics quan trọng nhất -->
    <section class="sa-stats" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 1.5rem;" aria-label="KPI chính">
        @foreach([
            ['bi-cash-stack', number_format($branchSummaryStats['total_revenue'], 0, ',', '.').'đ', 'Tổng doanh thu', 'Tất cả chi nhánh', 'revenue'],
            ['bi-shop-window', $branchSummaryStats['active_branches'], 'Chi nhánh hoạt động', $branchSummaryStats['total_branches'] . ' tổng', 'branch'],
            ['bi-bag-check', $branchSummaryStats['total_orders'], 'Tổng đơn hàng', 'Đã xử lý', 'order'],
            ['bi-people', $totalUserCount, 'Tổng người dùng', $adminCount . ' Admin', 'user'],
        ] as [$icon, $value, $label, $note, $type])
            <article class="sa-stat" style="min-height: 110px; @if($type === 'revenue') background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%); border-color: rgba(13, 147, 115, 0.25); @endif">
                <div class="sa-stat-top">
                    <span class="sa-stat-icon" style="width: 38px; height: 38px; @if($type === 'revenue') background: var(--sa-green); color: #fff; @endif">
                        <i class="bi {{ $icon }}"></i>
                    </span>
                    <span class="sa-stat-note">{{ $note }}</span>
                </div>
                <div class="sa-stat-value" style="font-size: 1.4rem; @if($type === 'revenue') color: var(--sa-green); @endif">
                    {{ is_numeric($value) ? number_format($value) : $value }}
                </div>
                <div class="sa-stat-label">{{ $label }}</div>
            </article>
        @endforeach
    </section>

    <!-- Expandable Stats Sections -->
    <div style="display: grid; gap: 0.75rem; margin-bottom: 1.5rem;">
        <!-- Chi tiết doanh thu -->
        <div class="sa-panel" style="overflow: visible;">
            <button type="button" class="sa-panel-header" style="width: 100%; cursor: pointer; transition: all 0.2s; background: none; border: none; border-bottom: 1px solid var(--sa-border);" onclick="toggleSection('revenue')">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <span class="sa-stat-icon" style="width: 32px; height: 32px; background: var(--sa-green); color: #fff;">
                        <i class="bi bi-currency-exchange"></i>
                    </span>
                    <div style="text-align: left;">
                        <h3 class="sa-panel-title" style="margin-bottom: 0.1rem;">Chi tiết doanh thu</h3>
                        <p class="sa-panel-note">Phân tích doanh thu theo thời gian</p>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span style="background: rgba(13, 147, 115, 0.1); color: var(--sa-green); padding: 0.25rem 0.6rem; border-radius: 999px; font-size: 0.7rem; font-weight: 800;">
                        {{ number_format($branchSummaryStats['today_revenue'], 0, ',', '.') }}đ hôm nay
                    </span>
                    <i class="bi bi-chevron-down" style="font-size: 0.85rem; color: var(--sa-muted); transition: transform 0.2s;" id="icon-revenue"></i>
                </div>
            </button>
            <div id="content-revenue" style="display: none; overflow: hidden; transition: all 0.3s ease;">
                <div style="padding: 0.75rem 0.9rem; border-top: 1px solid var(--sa-border);">
                    <div class="sa-stats" style="grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 0.6rem;">
                        @foreach([
                            ['bi-calendar-day', number_format($branchSummaryStats['today_revenue'], 0, ',', '.').'đ', 'Hôm nay'],
                            ['bi-calendar-week', number_format($branchSummaryStats['month_revenue'], 0, ',', '.').'đ', 'Tháng này'],
                            ['bi-graph-up', number_format($branchSummaryStats['total_revenue'], 0, ',', '.').'đ', 'Tổng cộng'],
                        ] as [$icon, $value, $label])
                            <div style="padding: 0.65rem; border: 1px solid var(--sa-border); border-radius: 8px; background: #fff;">
                                <div style="display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.45rem;">
                                    <i class="bi {{ $icon }}" style="color: var(--sa-green); font-size: 0.9rem;"></i>
                                    <span style="color: var(--sa-muted); font-size: 0.62rem; font-weight: 650;">{{ $label }}</span>
                                </div>
                                <div style="color: var(--sa-ink); font-size: 1.1rem; font-weight: 800;">{{ $value }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Chi tiết chi nhánh -->
        <div class="sa-panel" style="overflow: visible;">
            <button type="button" class="sa-panel-header" style="width: 100%; cursor: pointer; transition: all 0.2s; background: none; border: none; border-bottom: 1px solid var(--sa-border);" onclick="toggleSection('branch')">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <span class="sa-stat-icon" style="width: 32px; height: 32px;">
                        <i class="bi bi-shop"></i>
                    </span>
                    <div style="text-align: left;">
                        <h3 class="sa-panel-title" style="margin-bottom: 0.1rem;">Chi tiết chi nhánh</h3>
                        <p class="sa-panel-note">Thông tin chi nhánh và nhân sự</p>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span style="background: rgba(13, 147, 115, 0.1); color: var(--sa-green); padding: 0.25rem 0.6rem; border-radius: 999px; font-size: 0.7rem; font-weight: 800;">
                        {{ $branchSummaryStats['active_branches'] }}/{{ $branchSummaryStats['total_branches'] }} hoạt động
                    </span>
                    <i class="bi bi-chevron-down" style="font-size: 0.85rem; color: var(--sa-muted); transition: transform 0.2s;" id="icon-branch"></i>
                </div>
            </button>
            <div id="content-branch" style="display: none; overflow: hidden; transition: all 0.3s ease;">
                <div style="padding: 0.75rem 0.9rem; border-top: 1px solid var(--sa-border);">
                    <div class="sa-stats" style="grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 0.6rem;">
                        @foreach([
                            ['bi-diagram-3', $branchSummaryStats['total_branches'], 'Tổng chi nhánh'],
                            ['bi-shop-window', $branchSummaryStats['active_branches'], 'Đang hoạt động'],
                            ['bi-people', $branchSummaryStats['total_branch_staff'], 'Nhân viên'],
                            ['bi-bag-check', $branchSummaryStats['total_orders'], 'Đơn hàng'],
                        ] as [$icon, $value, $label])
                            <div style="padding: 0.65rem; border: 1px solid var(--sa-border); border-radius: 8px; background: #fff;">
                                <div style="display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.45rem;">
                                    <i class="bi {{ $icon }}" style="color: var(--sa-green); font-size: 0.9rem;"></i>
                                    <span style="color: var(--sa-muted); font-size: 0.62rem; font-weight: 650;">{{ $label }}</span>
                                </div>
                                <div style="color: var(--sa-ink); font-size: 1.1rem; font-weight: 800;">{{ number_format($value) }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Chi tiết hệ thống -->
        <div class="sa-panel" style="overflow: visible;">
            <button type="button" class="sa-panel-header" style="width: 100%; cursor: pointer; transition: all 0.2s; background: none; border: none; border-bottom: 1px solid var(--sa-border);" onclick="toggleSection('system')">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <span class="sa-stat-icon" style="width: 32px; height: 32px;">
                        <i class="bi bi-gear"></i>
                    </span>
                    <div style="text-align: left;">
                        <h3 class="sa-panel-title" style="margin-bottom: 0.1rem;">Chi tiết hệ thống</h3>
                        <p class="sa-panel-note">Quản trị, sản phẩm và phân quyền</p>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span style="background: rgba(13, 147, 115, 0.1); color: var(--sa-green); padding: 0.25rem 0.6rem; border-radius: 999px; font-size: 0.7rem; font-weight: 800;">
                        {{ $activeAdminCount }}/{{ $adminCount }} admin
                    </span>
                    <i class="bi bi-chevron-down" style="font-size: 0.85rem; color: var(--sa-muted); transition: transform 0.2s;" id="icon-system"></i>
                </div>
            </button>
            <div id="content-system" style="display: none; overflow: hidden; transition: all 0.3s ease;">
                <div style="padding: 0.75rem 0.9rem; border-top: 1px solid var(--sa-border);">
                    <div class="sa-stats" style="grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 0.6rem;">
                        @foreach([
                            ['bi-person-badge', $adminCount, 'Tổng Admin'],
                            ['bi-person-check', $activeAdminCount, 'Admin hoạt động'],
                            ['bi-cup-straw', $productCount, 'Sản phẩm'],
                            ['bi-grid', $categoryCount, 'Danh mục'],
                            ['bi-diagram-3', $roleCount, 'Vai trò'],
                        ] as [$icon, $value, $label])
                            <div style="padding: 0.65rem; border: 1px solid var(--sa-border); border-radius: 8px; background: #fff;">
                                <div style="display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.45rem;">
                                    <i class="bi {{ $icon }}" style="color: var(--sa-green); font-size: 0.9rem;"></i>
                                    <span style="color: var(--sa-muted); font-size: 0.62rem; font-weight: 650;">{{ $label }}</span>
                                </div>
                                <div style="color: var(--sa-ink); font-size: 1.1rem; font-weight: 800;">{{ number_format($value) }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleSection(section) {
            const content = document.getElementById('content-' + section);
            const icon = document.getElementById('icon-' + section);
            
            if (content && icon) {
                const isVisible = content.style.display !== 'none';
                
                if (isVisible) {
                    content.style.display = 'none';
                    icon.style.transform = 'rotate(0deg)';
                } else {
                    content.style.display = 'block';
                    icon.style.transform = 'rotate(180deg)';
                }
            }
        }
    </script>

    <!-- System Charts -->
    <section class="sa-grid-2" style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem;" aria-label="Biểu đồ hệ thống">
        <!-- System Revenue Chart (7-day) -->
        <article class="sa-panel">
            <div class="sa-panel-header"><div><h2 class="sa-panel-title">Doanh thu 7 ngày</h2><p class="sa-panel-note">Đơn đã thanh toán hoặc hoàn thành</p></div><i class="bi bi-graph-up-arrow text-success"></i></div>
            <div class="sa-chart">
                <div class="sa-chart-meta"><span>VNĐ</span><span>{{ number_format(collect($revenueChart['values'])->sum(), 0, ',', '.') }}đ</span></div>
                <div class="sa-chart-bars">
                    @foreach($revenueChart['labels'] as $index => $label)
                        <div class="sa-chart-column">
                            <span class="sa-chart-value">{{ $revenueChart['values'][$index] > 0 ? number_format($revenueChart['values'][$index] / 1000, 0).'k' : '0' }}</span>
                            <span class="sa-chart-bar" style="height: {{ $revenueChart['heights'][$index] }}%"></span>
                            <span class="sa-chart-label">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </article>

        <!-- System User Chart -->
        <article class="sa-panel">
            <div class="sa-panel-header"><div><h2 class="sa-panel-title">Người dùng mới</h2><p class="sa-panel-note">Sáu tháng gần nhất</p></div><i class="bi bi-person-plus text-primary"></i></div>
            <div class="sa-chart">
                <div class="sa-chart-meta"><span>Tài khoản</span><span>{{ collect($userChart['values'])->sum() }} mới</span></div>
                <div class="sa-chart-bars six">
                    @foreach($userChart['labels'] as $index => $label)
                        <div class="sa-chart-column">
                            <span class="sa-chart-value">{{ $userChart['values'][$index] }}</span>
                            <span class="sa-chart-bar alt" style="height: {{ $userChart['heights'][$index] }}%"></span>
                            <span class="sa-chart-label">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </article>
    </section>

    <!-- Branch Ranking Table -->
    @php
        $rankingQueryBase = request()->except('ranking_period');
        $rankingPeriod = $rankingPeriod ?? 'all';
    @endphp
    <section class="sa-panel" id="branch-ranking" data-branch-ranking-region style="margin-top: 1.5rem;">
        <div class="sa-panel-header" style="gap: 0.8rem; align-items: center; justify-content: space-between; flex-wrap: wrap;">
            <div>
                <h2 class="sa-panel-title">Quản Lý chi nhánh - bảng xếp hạng</h2>
                <p class="sa-panel-note">So sánh hiệu suất từng chi nhánh theo đơn hàng, doanh thu và nhân sự</p>
            </div>
            <div style="display:flex; align-items:center; gap:0.4rem; flex-wrap:wrap; justify-content:flex-end; margin-left:auto;">
                <div style="display:flex; flex-wrap:wrap; gap:0.25rem; padding:0.2rem; border:1px solid var(--sa-border); border-radius:999px; background:#fff;">
                    <a href="{{ route('admin.super-admin', array_merge($rankingQueryBase, ['ranking_period' => 'all'])) }}#branch-ranking" data-ranking-period="all" class="sa-btn {{ $rankingPeriod === 'all' ? 'sa-btn-primary' : '' }}" style="min-height:32px; padding:0.28rem 0.62rem; border-radius:999px; font-size:0.72rem; line-height:1; {{ $rankingPeriod === 'all' ? '' : 'background:#fff; color:var(--sa-ink); border:1px solid transparent;' }}">Tất cả</a>
                    <a href="{{ route('admin.super-admin', array_merge($rankingQueryBase, ['ranking_period' => 'week'])) }}#branch-ranking" data-ranking-period="week" class="sa-btn {{ $rankingPeriod === 'week' ? 'sa-btn-primary' : '' }}" style="min-height:32px; padding:0.28rem 0.62rem; border-radius:999px; font-size:0.72rem; line-height:1; {{ $rankingPeriod === 'week' ? '' : 'background:#fff; color:var(--sa-ink); border:1px solid transparent;' }}">Tuần</a>
                    <a href="{{ route('admin.super-admin', array_merge($rankingQueryBase, ['ranking_period' => 'month'])) }}#branch-ranking" data-ranking-period="month" class="sa-btn {{ $rankingPeriod === 'month' ? 'sa-btn-primary' : '' }}" style="min-height:32px; padding:0.28rem 0.62rem; border-radius:999px; font-size:0.72rem; line-height:1; {{ $rankingPeriod === 'month' ? '' : 'background:#fff; color:var(--sa-ink); border:1px solid transparent;' }}">Tháng</a>
                    <a href="{{ route('admin.super-admin', array_merge($rankingQueryBase, ['ranking_period' => 'year'])) }}#branch-ranking" data-ranking-period="year" class="sa-btn {{ $rankingPeriod === 'year' ? 'sa-btn-primary' : '' }}" style="min-height:32px; padding:0.28rem 0.62rem; border-radius:999px; font-size:0.72rem; line-height:1; {{ $rankingPeriod === 'year' ? '' : 'background:#fff; color:var(--sa-ink); border:1px solid transparent;' }}">Năm</a>
                </div>
                <button type="button" class="sa-btn sa-btn-primary" data-bs-toggle="modal" data-bs-target="#createBranchModal" style="min-height:32px; padding:0.28rem 0.7rem; border-radius:999px; white-space:nowrap; font-size:0.75rem;"><i class="bi bi-plus-lg"></i> Thêm chi nhánh</button>
            </div>
        </div>

        @if($branchRankingStats->count() > 0)
            <div class="sa-table-wrap">
                <table class="sa-table">
                    <thead>
                        <tr>
                            <th>Hạng</th>
                            <th>Chi nhánh</th>
                            <th>Admin</th>
                            <th>Trạng thái</th>
                            <th>Nhân viên</th>
                            <th>Đơn hàng</th>
                            <th>Hoàn thành</th>
                            <th>Đơn hủy</th>
                            <th>Doanh thu</th>
                            <th>GTB/đơn</th>
                            <th>Hiệu suất</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($branchRankingStats as $index => $branch)
                            <tr data-branch-row="{{ $branch['branch_id'] }}" @if($index === 0) style="background-color: #f0fdf4;" @endif>
                                <td style="font-weight: 800; color: var(--sa-green);">{{ $index + 1 }}</td>
                                <td data-branch-name-cell style="font-weight: 800; color: var(--sa-ink);">{{ $branch['branch_name'] }}</td>
                                <td style="font-size: 0.69rem; line-height: 1.4;">
                                    @if($branch['admin_id'])
                                        <div style="color: var(--sa-ink); font-weight: 700;">{{ $branch['admin_name'] }}</div>
                                        <div style="color: var(--sa-muted); font-size: 0.62rem;">{{ $branch['admin_email'] }}</div>
                                    @else
                                        <span style="color: var(--sa-muted);">Chưa gán</span>
                                    @endif
                                </td>
                                <td data-branch-status-cell>
                                    @if($branch['branch_status'])
                                        <span class="sa-state sa-state-active" data-branch-status-badge="{{ $branch['branch_id'] }}"><i class="bi bi-check-circle"></i> Hoạt động</span>
                                    @else
                                        <span class="sa-state" style="background: #fef2f2; color: #991b1b;" data-branch-status-badge="{{ $branch['branch_id'] }}"><i class="bi bi-pause-circle"></i> Tạm ngưng</span>
                                    @endif
                                </td>
                                <td>
                                    <span style="color: var(--sa-ink); font-weight: 700;">{{ $branch['active_staff_count'] }}</span><span style="color: var(--sa-muted);">/{{ $branch['staff_count'] }}</span>
                                </td>
                                <td style="text-align: center;">{{ number_format($branch['total_orders']) }}</td>
                                <td style="text-align: center; color: #15803d; font-weight: 700;">{{ number_format($branch['completed_orders']) }}</td>
                                <td style="text-align: center; color: #991b1b; font-weight: 700;">{{ number_format($branch['cancelled_orders']) }}</td>
                                <td style="font-weight: 700; color: var(--sa-green);">{{ number_format($branch['revenue'], 0, ',', '.').'đ' }}</td>
                                <td style="font-weight: 700;">{{ number_format($branch['average_order_value'], 0, ',', '.').'đ' }}</td>
                                <td style="font-weight: 800; color: var(--sa-green);">{{ $branch['performance_percentage'] }}%</td>
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
            @foreach($branchRankingStats as $branch)
                @php
                    $isEditingThisBranch = (string) old('branch_modal_id') === (string) $branch['branch_id'];
                    $branchStatusValue = (int) ($isEditingThisBranch ? old('status', $branch['branch_status']) : $branch['branch_status']);
                @endphp
                <div class="modal fade" id="branchEditModal{{ $branch['branch_id'] }}" tabindex="-1" aria-labelledby="branchEditModalLabel{{ $branch['branch_id'] }}" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <form class="modal-content branch-edit-form" method="POST" action="{{ route('admin.branches.update', $branch['branch_id']) }}" data-branch-id="{{ $branch['branch_id'] }}" style="border:0;border-radius:8px;">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="form_type" value="branch-edit">
                            <input type="hidden" name="branch_modal_id" value="{{ $branch['branch_id'] }}">
                            <input type="hidden" name="return_to" value="super-admin">
                            <div class="modal-header">
                                <h2 class="modal-title fs-6 fw-bold" id="branchEditModalLabel{{ $branch['branch_id'] }}">Sửa chi nhánh</h2>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                            </div>
                            <div class="modal-body">
                                <div class="alert alert-danger d-none" role="alert" data-branch-edit-errors="{{ $branch['branch_id'] }}" style="font-size: 0.8rem;"></div>
                                
                                {{-- Row 1: Tên + Mã --}}
                                <div class="row g-3 mb-3">
                                    <div class="col-sm-7">
                                        <label class="form-label small fw-bold" for="branch_name_{{ $branch['branch_id'] }}">Tên chi nhánh <span class="text-danger">*</span></label>
                                        <input id="branch_name_{{ $branch['branch_id'] }}" class="form-control @error('name', 'editBranch') is-invalid @enderror" name="name" value="{{ $isEditingThisBranch ? old('name', $branch['branch_name']) : $branch['branch_name'] }}" placeholder="Nhập tên chi nhánh" required>
                                        @error('name', 'editBranch')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-sm-5">
                                        <label class="form-label small fw-bold" for="branch_code_{{ $branch['branch_id'] }}">Mã chi nhánh <span class="text-danger">*</span></label>
                                        <input id="branch_code_{{ $branch['branch_id'] }}" class="form-control @error('code', 'editBranch') is-invalid @enderror" name="code" value="{{ $isEditingThisBranch ? old('code', $branch['branch_code']) : $branch['branch_code'] }}" placeholder="VD: CN1, CN2" required>
                                        @error('code', 'editBranch')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Row 2: Điện thoại --}}
                                <div class="mb-3">
                                    <label class="form-label small fw-bold" for="branch_phone_{{ $branch['branch_id'] }}">Điện thoại</label>
                                    <input id="branch_phone_{{ $branch['branch_id'] }}" class="form-control @error('phone', 'editBranch') is-invalid @enderror" type="text" name="phone" value="{{ $isEditingThisBranch ? old('phone', $branch['branch_phone']) : $branch['branch_phone'] }}" placeholder="0123 456 789">
                                    @error('phone', 'editBranch')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Admin account section --}}
                                <div class="p-3 mb-3" style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px;">
                                    <p class="small fw-bold mb-1" style="color: #15803d;"><i class="bi bi-person-badge me-1"></i> Tài khoản Admin chi nhánh</p>
                                    <p class="small text-muted mb-3" style="font-size: 0.72rem;">Dùng để đăng nhập giao diện quản lý chi nhánh.</p>
                                    <div class="row g-3">
                                        <div class="col-sm-7">
                                            <label class="form-label small fw-bold" for="branch_admin_email_{{ $branch['branch_id'] }}">Email đăng nhập <span class="text-danger">*</span></label>
                                            <input id="branch_admin_email_{{ $branch['branch_id'] }}" class="form-control @error('admin_email', 'editBranch') is-invalid @enderror" type="email" name="admin_email" value="{{ $isEditingThisBranch ? old('admin_email', $branch['admin_email']) : $branch['admin_email'] }}" placeholder="admin@chinhanh.com" required>
                                            @error('admin_email', 'editBranch')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-sm-5">
                                            <label class="form-label small fw-bold" for="branch_admin_password_{{ $branch['branch_id'] }}">Mật khẩu</label>
                                            <input id="branch_admin_password_{{ $branch['branch_id'] }}" class="form-control @error('admin_password', 'editBranch') is-invalid @enderror" type="text" name="admin_password" value="{{ $isEditingThisBranch ? old('admin_password', $branch['admin_password']) : $branch['admin_password'] }}" placeholder="Nhập để đổi mật khẩu hoặc giữ mặc định">
                                            @error('admin_password', 'editBranch')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-bold" for="branch_address_{{ $branch['branch_id'] }}">Địa chỉ</label>
                                    <textarea id="branch_address_{{ $branch['branch_id'] }}" class="form-control @error('address', 'editBranch') is-invalid @enderror" name="address" rows="3" placeholder="Nhập địa chỉ chi nhánh">{{ $isEditingThisBranch ? old('address', $branch['branch_address']) : $branch['branch_address'] }}</textarea>
                                    @error('address', 'editBranch')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    @include('admin.partials.location-picker', [
                                        'pickerId' => 'branch-location-picker-'.$branch['branch_id'],
                                        'label' => 'Vị trí chi nhánh',
                                        'hint' => 'Nhấn chọn hoặc kéo thả pin trên bản đồ, sau đó bấm "Lấy địa chỉ" để tự động điền.',
                                        'latName' => 'latitude',
                                        'lngName' => 'longitude',
                                        'latValue' => $isEditingThisBranch ? old('latitude', $branch['branch_latitude']) : $branch['branch_latitude'],
                                        'lngValue' => $isEditingThisBranch ? old('longitude', $branch['branch_longitude']) : $branch['branch_longitude'],
                                        'addressTarget' => '#branch_address_'.$branch['branch_id'],
                                        'defaultLat' => 19.625017,
                                        'defaultLng' => 105.643070,
                                        'defaultZoom' => 13,
                                    ])
                                </div>

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
                <p style="margin-top: 0.3rem; font-size: 0.7rem;">Hệ thống chưa có chi nhánh hoặc dữ liệu chưa được đồng bộ</p>
            </div>
        @endif
    </section>

    <div id="admins-region" data-admins-region>
    <section class="sa-panel" id="admins">
        <div class="sa-panel-header">
            <div><h2 class="sa-panel-title">Danh sách quản trị viên</h2><p class="sa-panel-note">{{ $adminUsers->total() }} tài khoản phù hợp</p></div>
        </div>
        <form class="sa-filter-form" method="GET" action="{{ route('admin.super-admin') }}" data-admins-filter-form>
            <input class="sa-control" type="search" name="q" value="{{ $filters['search'] }}" placeholder="Tìm theo tên hoặc email" aria-label="Tìm theo tên hoặc email">
            <select class="sa-control" name="role" aria-label="Lọc vai trò">
                <option value="all">Tất cả vai trò</option>
                <option value="super" @selected($filters['role'] === 'super')>Super Admin</option>
                <option value="admin" @selected($filters['role'] === 'admin')>Admin hệ thống</option>
            </select>
            <select class="sa-control" name="status" aria-label="Lọc trạng thái">
                <option value="all">Tất cả trạng thái</option>
                <option value="active" @selected($filters['status'] === 'active')>Hoạt động</option>
                <option value="locked" @selected($filters['status'] === 'locked')>Đã khóa</option>
            </select>
            <select class="sa-control" name="created" aria-label="Lọc ngày tạo">
                <option value="all">Mọi thời điểm</option>
                <option value="today" @selected($filters['created'] === 'today')>Hôm nay</option>
                <option value="week" @selected($filters['created'] === 'week')>7 ngày qua</option>
                <option value="month" @selected($filters['created'] === 'month')>30 ngày qua</option>
            </select>
            <div class="sa-filter-actions">
                <button class="sa-btn sa-btn-primary" type="submit"><i class="bi bi-funnel"></i> Lọc</button>
                <a class="sa-btn" href="{{ route('admin.super-admin') }}#admins" title="Xóa bộ lọc" data-admins-reset><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>

        <div class="sa-table-wrap">
            <table class="sa-table">
                <thead><tr><th>Quản trị viên</th><th>Vai trò</th><th>Nhánh quản lý</th><th>Lần đăng nhập cuối</th><th>Hiện diện</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
                <tbody>
                    @forelse($adminUsers as $adminUser)
                        @php
                            $isSuper = $adminUser->isSuperAdmin();
                            $isCurrentAccount = $adminUser->is(auth()->user());
                            $initials = collect(preg_split('/\s+/u', trim($adminUser->name)))->filter()->take(2)->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->implode('');
                            $minutesSinceLogin = $adminUser->last_login_at?->diffInMinutes(now());
                            $presence = $isCurrentAccount || ($minutesSinceLogin !== null && $minutesSinceLogin <= 5) ? 'online' : ($minutesSinceLogin !== null && $minutesSinceLogin <= 30 ? 'away' : 'offline');
                            $presenceLabel = ['online' => 'Online', 'away' => 'Away', 'offline' => 'Offline'][$presence];
                        @endphp
                        <tr data-admin-id="{{ $adminUser->id }}">
                            <td>
                                <div class="sa-admin-cell">
                                    <span class="sa-avatar">
                                        @if($adminUser->avatar)
                                            <img src="{{ str_starts_with($adminUser->avatar, 'http') ? $adminUser->avatar : asset('storage/'.ltrim($adminUser->avatar, '/')) }}" alt="{{ $adminUser->name }}">
                                        @else
                                            {{ $initials ?: 'AD' }}
                                        @endif
                                    </span>
                                    <div><div class="sa-admin-name">{{ $adminUser->name }}</div><div class="sa-admin-email">{{ $adminUser->email }}</div></div>
                                </div>
                            </td>
                            <td><span class="sa-role {{ $isSuper ? 'sa-role-super' : 'sa-role-admin' }}"><i class="bi {{ $isSuper ? 'bi-shield-fill-check' : 'bi-gear' }}"></i>{{ $isSuper ? 'Super Admin' : 'Admin hệ thống' }}</span></td>
                            <td data-admin-branch-cell="{{ $adminUser->id }}">
                                @if($adminUser->branch)
                                    <div style="font-size: 0.69rem; line-height: 1.4;" data-admin-branch-display>
                                        <div style="color: var(--sa-ink); font-weight: 700;" data-admin-branch-name>{{ $adminUser->branch->name }}</div>
                                        <div style="color: var(--sa-muted); font-size: 0.62rem;" data-admin-branch-code>{{ $adminUser->branch->code }}</div>
                                    </div>
                                @else
                                    <span style="color: var(--sa-muted); font-size: 0.69rem;" data-admin-branch-empty>Chưa gán</span>
                                @endif
                            </td>
                            <td>{{ $adminUser->last_login_at?->format('d/m/Y H:i') ?? ($isCurrentAccount ? 'Phiên hiện tại' : 'Chưa đăng nhập') }}</td>
                            <td><span class="sa-presence sa-presence-{{ $presence }}">{{ $presenceLabel }}</span></td>
                            <td><span class="sa-state {{ $adminUser->is_active ? 'sa-state-active' : 'sa-state-locked' }}">{{ $adminUser->is_active ? 'Active' : 'Locked' }}</span></td>
                            <td>
                                <div class="sa-actions">
                                    @if($adminUser->isSuperAdmin())
                                        <a class="sa-action-btn" href="{{ route('admin.super-admin', ['q' => $adminUser->email]) }}" title="Xem trang quản trị"><i class="bi bi-eye"></i></a>
                                    @elseif($adminUser->branch_id)
                                        <a class="sa-action-btn" href="{{ route('admin.dashboard', ['branch_id' => $adminUser->branch_id]) }}" title="Xem dashboard chi nhánh"><i class="bi bi-eye"></i></a>
                                    @else
                                        <a class="sa-action-btn" href="{{ route('admin.users.show', $adminUser) }}" title="Xem chi tiết"><i class="bi bi-eye"></i></a>
                                    @endif
                                    <button class="sa-action-btn" type="button" data-bs-toggle="modal" data-bs-target="#adminActionsModal{{ $adminUser->id }}" title="Thao tác"><i class="bi bi-gear"></i></button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-secondary py-4">Không có quản trị viên phù hợp.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="sa-pagination">
            <span>Hiển thị {{ $adminUsers->firstItem() ?? 0 }}-{{ $adminUsers->lastItem() ?? 0 }} / {{ $adminUsers->total() }}</span>
            <div class="sa-page-links" aria-label="Phân trang quản trị viên">
                <a class="sa-page-link {{ $adminUsers->onFirstPage() ? 'disabled' : '' }}" href="{{ $adminUsers->previousPageUrl() ?? '#' }}" aria-label="Trang trước"><i class="bi bi-chevron-left"></i></a>
                @foreach(range(1, max(1, $adminUsers->lastPage())) as $page)
                    <a class="sa-page-link {{ $page === $adminUsers->currentPage() ? 'active' : '' }}" href="{{ $adminUsers->url($page) }}">{{ $page }}</a>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Branch Edit Modals -->
    @foreach($adminUsers as $adminUser)

        <!-- Admin Actions Modal -->
        <div class="modal fade" id="adminActionsModal{{ $adminUser->id }}" tabindex="-1" aria-labelledby="adminActionsLabel{{ $adminUser->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="adminActionsLabel{{ $adminUser->id }}">Thao tác quản trị viên</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Admin Info -->
                        <div style="padding: 1rem 0; border-bottom: 1px solid #e1e6e4; margin-bottom: 1rem;">
                            @php
                                $initials = collect(preg_split('/\s+/u', trim($adminUser->name)))->filter()->take(2)->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->implode('');
                            @endphp
                            <div style="display: flex; gap: 0.75rem; align-items: center;">
                                <div style="width: 40px; height: 40px; border-radius: 50%; background: #e7f7f2; color: #0d9373; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">
                                    @if($adminUser->avatar)
                                        <img src="{{ str_starts_with($adminUser->avatar, 'http') ? $adminUser->avatar : asset('storage/'.ltrim($adminUser->avatar, '/')) }}" alt="{{ $adminUser->name }}" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                                    @else
                                        {{ $initials ?: 'AD' }}
                                    @endif
                                </div>
                                <div>
                                    <div style="color: #111827; font-weight: 700; font-size: 0.95rem;">{{ $adminUser->name }}</div>
                                    <div style="color: #6b7280; font-size: 0.85rem;">{{ $adminUser->email }}</div>
                                    <div style="margin-top: 0.3rem;">
                                        <span class="sa-role {{ $adminUser->isSuperAdmin() ? 'sa-role-super' : 'sa-role-admin' }}"><i class="bi {{ $adminUser->isSuperAdmin() ? 'bi-shield-fill-check' : 'bi-gear' }}"></i>{{ $adminUser->isSuperAdmin() ? 'Super Admin' : 'Admin hệ thống' }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Branch Info if exists -->
                            @if($adminUser->branch)
                                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e1e6e4;">
                                    <div style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase; font-weight: 800; margin-bottom: 0.4rem;">Nhánh quản lý</div>
                                    <div style="color: #111827; font-weight: 700;">{{ $adminUser->branch->name }}</div>
                                    <div style="color: #6b7280; font-size: 0.85rem;">{{ $adminUser->branch->code }}</div>
                                </div>
                            @endif
                        </div>

                        @php
                            $tabCount = $adminUser->is(auth()->user()) ? 3 : 4;
                            $loginHistories = $loginHistoryByAdmin->get($adminUser->id, collect());
                        @endphp

                        <!-- Horizontal Tab Navigation -->
                        <div style="display: grid; grid-template-columns: repeat({{ $tabCount }}, minmax(0, 1fr)); gap: 0; border-bottom: 2px solid #e1e6e4; margin-bottom: 1.5rem;">
                            <button class="admin-action-tab" data-tab="permissions" style="width: 100%; min-width: 0; height: 68px; padding: 0.55rem 0.4rem; border: none; background: none; color: #6b7280; cursor: pointer; font-weight: 700; font-size: 0.8rem; border-bottom: 3px solid transparent; transition: all 0.2s ease; text-align: center; position: relative; margin: 0; box-sizing: border-box; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.18rem; white-space: normal; line-height: 1.15;" onclick="switchTab(event, 'permissions', {{ $adminUser->id }})">
                                <i class="bi bi-key"></i><span>Phân quyền</span>
                            </button>
                            <button class="admin-action-tab" data-tab="branch" style="width: 100%; min-width: 0; height: 68px; padding: 0.55rem 0.4rem; border: none; background: none; color: #6b7280; cursor: pointer; font-weight: 700; font-size: 0.8rem; border-bottom: 3px solid transparent; transition: all 0.2s ease; text-align: center; position: relative; margin: 0; box-sizing: border-box; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.18rem; white-space: normal; line-height: 1.15;" onclick="switchTab(event, 'branch', {{ $adminUser->id }})">
                                <i class="bi bi-shop"></i><span>Gán nhánh</span>
                            </button>
                            @if(!$adminUser->is(auth()->user()))
                                <button class="admin-action-tab" data-tab="security" style="width: 100%; min-width: 0; height: 68px; padding: 0.55rem 0.4rem; border: none; background: none; color: #6b7280; cursor: pointer; font-weight: 700; font-size: 0.8rem; border-bottom: 3px solid transparent; transition: all 0.2s ease; text-align: center; position: relative; margin: 0; box-sizing: border-box; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.18rem; white-space: normal; line-height: 1.15;" onclick="switchTab(event, 'security', {{ $adminUser->id }})">
                                    <i class="bi bi-lock"></i><span>Khóa/Mở khóa</span>
                                </button>
                            @endif
                            <button class="admin-action-tab" data-tab="login-history" style="width: 100%; min-width: 0; height: 68px; padding: 0.55rem 0.4rem; border: none; background: none; color: #6b7280; cursor: pointer; font-weight: 700; font-size: 0.8rem; border-bottom: 3px solid transparent; transition: all 0.2s ease; text-align: center; position: relative; margin: 0; box-sizing: border-box; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.18rem; white-space: normal; line-height: 1.15;" onclick="switchTab(event, 'login-history', {{ $adminUser->id }})">
                                <i class="bi bi-clock-history"></i><span>Lịch sử đăng nhập</span>
                            </button>
                        </div>

                        <!-- Tab Contents -->
                        <!-- Tab: Phân quyền -->
                        <div class="admin-action-content" id="content-permissions-{{ $adminUser->id }}" style="display: block;">
                            <div style="display: grid; gap: 0.75rem;">
                                <div style="padding: 0.75rem; background: #f8faf9; border-radius: 6px;">
                                    <div style="font-size: 0.7rem; color: #6b7280; font-weight: 800; text-transform: uppercase; margin-bottom: 0.5rem;">Vai trò hiện tại</div>
                                    <div data-current-role>
                                        <span class="sa-role {{ $adminUser->isSuperAdmin() ? 'sa-role-super' : 'sa-role-admin' }}" style="font-size: 0.75rem;"><i class="bi {{ $adminUser->isSuperAdmin() ? 'bi-shield-fill-check' : 'bi-gear' }}"></i>{{ $adminUser->isSuperAdmin() ? 'Super Admin' : 'Admin hệ thống' }}</span>
                                    </div>
                                </div>


                                <form method="POST" action="{{ route('admin.super-admin.update-role', $adminUser) }}" class="admin-role-form" data-admin-id="{{ $adminUser->id }}" style="margin: 0;">
                                    @csrf
                                    @method('PATCH')
                                    <div style="margin-bottom: 0.75rem;">
                                        <label style="display: block; font-size: 0.7rem; color: #6b7280; font-weight: 800; text-transform: uppercase; margin-bottom: 0.5rem;">Chọn vai trò mới</label>
                                        <select name="role_id" class="form-select" style="font-size: 0.85rem;">
                                            <option value="">-- Chọn vai trò --</option>
                                            <option value="2" @selected($adminUser->role_id === 2)>Admin hệ thống</option>
                                            <option value="3" @selected($adminUser->role_id === 3)>Super Admin</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm" style="width: 100%;">
                                        <i class="bi bi-check-lg" style="margin-right: 0.4rem;"></i>Lưu thay đổi
                                    </button>
                                </form>
                                <a href="{{ route('admin.users.edit', $adminUser) }}" class="btn btn-secondary btn-sm" style="width: 100%;">
                                    <i class="bi bi-arrow-up-right" style="margin-right: 0.4rem;"></i>Xem chi tiết phân quyền
                                </a>
                            </div>
                        </div>

                        <!-- Tab: Gán nhánh -->
                        <div class="admin-action-content" id="content-branch-{{ $adminUser->id }}" style="display: none;">
                            <div style="display: grid; gap: 0.75rem;">
                                <div style="padding: 0.75rem; background: #f8faf9; border-radius: 6px;">
                                    <div style="font-size: 0.7rem; color: #6b7280; font-weight: 800; text-transform: uppercase; margin-bottom: 0.5rem;">Chi nhánh hiện tại</div>
                                    <div data-current-branch style="color: #111827; font-weight: 600; font-size: 0.85rem;">
                                        @if($adminUser->branch)
                                            {{ $adminUser->branch->name }} ({{ $adminUser->branch->code }})
                                        @else
                                            Chưa gán
                                        @endif
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('admin.super-admin.update-branch', $adminUser) }}" class="admin-branch-form" data-admin-id="{{ $adminUser->id }}" style="margin: 0;">
                                    @csrf
                                    @method('PATCH')
                                    <div style="margin-bottom: 0.75rem;">
                                        <label style="display: block; font-size: 0.7rem; color: #6b7280; font-weight: 800; text-transform: uppercase; margin-bottom: 0.5rem;">Chọn chi nhánh</label>
                                        <select name="branch_id" class="form-select" style="font-size: 0.85rem;">
                                            <option value="">-- Không gán --</option>
                                            @forelse($branches ?? [] as $branch)
                                                <option value="{{ $branch->id }}" @selected($adminUser->branch_id === $branch->id)>
                                                    {{ $branch->name }} ({{ $branch->code }})
                                                </option>
                                            @empty
                                                <option disabled>Không có chi nhánh</option>
                                            @endforelse
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm" style="width: 100%;">
                                        <i class="bi bi-check-lg" style="margin-right: 0.4rem;"></i>Lưu thay đổi
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Tab: Lịch sử đăng nhập -->
                        <div class="admin-action-content" id="content-login-history-{{ $adminUser->id }}" style="display: none;">
                            <div style="display: grid; gap: 0.75rem;">
                                <div style="padding: 0.75rem; background: #f8faf9; border-radius: 6px;">
                                    <div style="font-size: 0.7rem; color: #6b7280; font-weight: 800; text-transform: uppercase; margin-bottom: 0.4rem;">Đăng nhập gần nhất</div>
                                    <div style="color: #111827; font-weight: 700;">
                                        {{ $adminUser->last_login_at?->format('d/m/Y H:i') ?? 'Chưa đăng nhập' }}
                                    </div>
                                    <div style="color: #6b7280; font-size: 0.82rem;">
                                        {{ $adminUser->last_login_ip ?: 'Không có IP' }}
                                    </div>
                                </div>

                                <div style="padding: 0.75rem; background: #fff; border: 1px solid #e5ece9; border-radius: 6px;">
                                    <div class="d-flex flex-wrap align-items-end gap-2 mb-3">
                                        <div class="flex-grow-1" style="min-width: 220px;">
                                            <label class="form-label small fw-bold mb-1" style="font-size: 0.7rem; color: #6b7280; text-transform: uppercase;">Tìm theo ngày</label>
                                            <input type="date" class="form-control form-control-sm" data-login-history-date-input="{{ $adminUser->id }}" onchange="filterLoginHistory({{ $adminUser->id }})">
                                        </div>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearLoginHistoryFilter({{ $adminUser->id }})">
                                            Xóa lọc
                                        </button>
                                    </div>
                                    <div style="font-size: 0.7rem; color: #6b7280; font-weight: 800; text-transform: uppercase; margin-bottom: 0.75rem;">4 lần đăng nhập gần nhất trong 3 tháng</div>

                                    @if($loginHistories->isNotEmpty())
                                        <div style="display: grid; gap: 0.6rem;" data-login-history-list="{{ $adminUser->id }}">
                                            @foreach($loginHistories as $history)
                                                <div
                                                    style="display: flex; justify-content: space-between; gap: 0.75rem; padding: 0.65rem 0.75rem; background: #f8faf9; border-radius: 6px;"
                                                    data-login-history-row="{{ $adminUser->id }}"
                                                    data-login-date="{{ $history->created_at?->format('Y-m-d') }}"
                                                    data-default-visible="{{ $loop->index < 4 ? '1' : '0' }}"
                                                    @if($loop->index >= 4) class="d-none" @endif
                                                >
                                                    <div style="min-width: 0;">
                                                        <div style="color: #111827; font-weight: 700; font-size: 0.88rem;">{{ $history->created_at?->format('d/m/Y H:i') }}</div>
                                                        <div style="color: #6b7280; font-size: 0.78rem;">{{ $history->action }}</div>
                                                    </div>
                                                    <div style="color: #6b7280; font-size: 0.78rem; white-space: nowrap;">{{ $history->ip_address ?: 'Nội bộ' }}</div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div style="color: #6b7280; font-size: 0.85rem;">Chưa có lịch sử đăng nhập.</div>
                                    @endif
                                    <div class="d-none" data-login-history-empty="{{ $adminUser->id }}" style="color: #6b7280; font-size: 0.85rem; margin-top: 0.75rem;">Không tìm thấy lịch sử đăng nhập phù hợp.</div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab: Khóa/Mở khóa -->
                        @if(!$adminUser->is(auth()->user()))
                            <div class="admin-action-content" id="content-security-{{ $adminUser->id }}" style="display: none;">
                                <div style="display: grid; gap: 0.75rem;">
                                    <div style="padding: 0.75rem; background: #f8faf9; border-radius: 6px;">
                                        <div style="font-size: 0.7rem; color: #6b7280; font-weight: 800; text-transform: uppercase; margin-bottom: 0.3rem;">Trạng thái tài khoản</div>
                                        <div>
                                            <span class="sa-state {{ $adminUser->is_active ? 'sa-state-active' : 'sa-state-locked' }}" style="font-size: 0.75rem;">{{ $adminUser->is_active ? '✓ Hoạt động' : '✕ Đã khóa' }}</span>
                                        </div>
                                    </div>
                                    <form method="POST" action="{{ route('admin.users.toggle-status', $adminUser) }}" style="margin: 0;">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-{{ $adminUser->is_active ? 'danger' : 'success' }} btn-sm" type="submit" style="width: 100%;">
                                            <i class="bi {{ $adminUser->is_active ? 'bi-lock' : 'bi-unlock' }}" style="margin-right: 0.4rem;"></i>{{ $adminUser->is_active ? 'Khóa tài khoản' : 'Mở khóa tài khoản' }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endforeach
    </div>

    <section class="sa-grid-main">
        <article class="sa-panel" id="audit">
            <div class="sa-panel-header"><div><h2 class="sa-panel-title">Nhật ký hệ thống</h2><p class="sa-panel-note">Sự kiện xác thực và quản trị gần nhất</p></div><i class="bi bi-journal-text text-success"></i></div>
            @forelse($activityLogs as $log)
                @if($loop->first)<div class="sa-activity-list">@endif
                    <div class="sa-activity">
                        <span class="sa-activity-icon"><i class="bi {{ $log->category === 'auth' ? 'bi-box-arrow-in-right' : ($log->category === 'security' ? 'bi-shield-exclamation' : 'bi-activity') }}"></i></span>
                        <p><strong>{{ $log->actor_name ?: 'Hệ thống' }}</strong> · {{ $log->action }}</p>
                        <time datetime="{{ $log->created_at?->toIso8601String() }}">{{ $log->created_at?->format('d/m/Y H:i') }} · {{ $log->ip_address ?: 'Nội bộ' }}</time>
                    </div>
                @if($loop->last)</div>@endif
            @empty
                <div class="sa-empty"><i class="bi bi-journal-check"></i><strong>Chưa có sự kiện hệ thống</strong></div>
            @endforelse
        </article>

        <aside class="sa-panel" id="health">
            <div class="sa-panel-header"><div><h2 class="sa-panel-title">System Health</h2><p class="sa-panel-note">Trạng thái dịch vụ có thể kiểm tra</p></div><i class="bi bi-heart-pulse text-success"></i></div>
            <div class="sa-health-list">
                <div class="sa-health-row"><span class="sa-health-name"><i class="bi bi-database"></i>Database</span><span class="sa-health-value {{ $systemHealth['database']['tone'] }}">{{ $systemHealth['database']['label'] }}</span></div>
                <div class="sa-health-row"><span class="sa-health-name"><i class="bi bi-device-ssd"></i>Storage</span><span class="sa-health-value">{{ $systemHealth['storage'] }}</span></div>
                <div class="sa-health-row"><span class="sa-health-name"><i class="bi bi-lightning"></i>Cache</span><span class="sa-health-value">{{ strtoupper($systemHealth['cache']) }}</span></div>
                <div class="sa-health-row"><span class="sa-health-name"><i class="bi bi-envelope"></i>Email</span><span class="sa-health-value">{{ $systemHealth['mail'] }}</span></div>
            </div>
        </aside>
    </section>

    <section class="sa-panel" id="security">
        <div class="sa-panel-header"><div><h2 class="sa-panel-title">Bảo mật hệ thống</h2><p class="sa-panel-note">Chỉ số được ghi nhận trong ngày</p></div><i class="bi bi-shield-check text-success"></i></div>
        <div class="sa-security-grid">
            @foreach([
                ['bi-person-x', $securityStats['failed_logins'], 'Đăng nhập thất bại'],
                ['bi-lock', $securityStats['locked_admins'], 'Admin đang khóa'],
                ['bi-key', $securityStats['pending_resets'], 'Yêu cầu đặt lại mật khẩu'],
                ['bi-bell', $securityStats['unread_notifications'], 'Thông báo chưa đọc'],
            ] as [$icon, $value, $label])
                <div class="sa-security-item"><i class="bi {{ $icon }}"></i><div><div class="sa-security-value">{{ $value }}</div><div class="sa-security-label">{{ $label }}</div></div></div>
            @endforeach
        </div>
    </section>

    <section class="sa-panel" id="permissions">
        <div class="sa-panel-header"><div><h2 class="sa-panel-title">Phạm vi quyền hiện tại</h2><p class="sa-panel-note">Hai cấp tài khoản đang có trong bảng roles</p></div><span class="sa-role sa-role-super"><i class="bi bi-shield-check"></i>Super Admin</span></div>
        <div class="sa-permissions">
            @foreach(['Dashboard hệ thống', 'Quản trị Admin', 'Vai trò và phân quyền', 'Bảo mật và nhật ký', 'Dashboard vận hành', 'Sản phẩm và danh mục', 'Đơn hàng và khách hàng', 'Voucher và đánh giá'] as $permission)
                <div class="sa-permission"><i class="bi bi-check-circle-fill"></i>{{ $permission }}</div>
            @endforeach
        </div>
    </section>
</div>

<div class="modal fade" id="createAdminModal" tabindex="-1" aria-labelledby="createAdminModalLabel" aria-hidden="true" data-auto-open="{{ $errors->any() ? 'true' : 'false' }}">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="{{ route('admin.super-admin.admins.store') }}" style="border:0;border-radius:8px;">
            @csrf
            <input type="hidden" name="form_type" value="admin">
            <div class="modal-header"><h2 class="modal-title fs-6 fw-bold" id="createAdminModalLabel">Thêm tài khoản Admin</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div>
            <div class="modal-body">
                <div class="alert alert-info" role="alert" style="font-size: 0.75rem; margin-bottom: 1rem;">
                    <i class="bi bi-info-circle me-2"></i>Tạo admin sẽ tự tạo một nhánh quản lý tương ứng.
                </div>
                <div class="mb-3"><label class="form-label small fw-bold" for="admin_name">Họ và tên</label><input id="admin_name" class="form-control @error('name', 'createAdmin') is-invalid @enderror" name="name" value="{{ old('name') }}" required>@error('name', 'createAdmin')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="mb-3"><label class="form-label small fw-bold" for="admin_email">Email</label><input id="admin_email" class="form-control @error('email', 'createAdmin') is-invalid @enderror" type="email" name="email" value="{{ old('email') }}" required>@error('email', 'createAdmin')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="row g-3">
                    <div class="col-sm-6"><label class="form-label small fw-bold" for="admin_password">Mật khẩu ban đầu</label><input id="admin_password" class="form-control @error('password', 'createAdmin') is-invalid @enderror" type="password" name="password" required>@error('password', 'createAdmin')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-sm-6"><label class="form-label small fw-bold" for="admin_password_confirmation">Xác nhận mật khẩu</label><input id="admin_password_confirmation" class="form-control" type="password" name="password_confirmation" required></div>
                </div>
                <div class="form-check form-switch mt-3"><input type="hidden" name="is_active" value="0"><input id="admin_active" class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', '1') === '1')><label class="form-check-label small fw-semibold" for="admin_active">Kích hoạt ngay</label></div>
            </div>
            <div class="modal-footer" style="gap: 0.75rem;">
                <button type="button" class="sa-btn" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="sa-btn sa-btn-primary" style="min-width: 160px; background: var(--sa-green); color: #fff; border-color: var(--sa-green);">
                    <i class="bi bi-person-plus"></i>Tạo Admin
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="createBranchModal" tabindex="-1" aria-labelledby="createBranchModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form class="modal-content" method="POST" action="{{ route('admin.branches.store') }}" style="border:0;border-radius:8px;">
            @csrf
            <input type="hidden" name="form_type" value="branch">
            <input type="hidden" name="return_to" value="super-admin">
            
            <div class="modal-header">
                <h2 class="modal-title fs-6 fw-bold" id="createBranchModalLabel">Thêm chi nhánh mới</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            
            <div class="modal-body">
                {{-- Row 1: Tên + Mã --}}
                <div class="row g-3 mb-3">
                    <div class="col-sm-7">
                        <label class="form-label small fw-bold" for="new_branch_name">Tên chi nhánh <span class="text-danger">*</span></label>
                        <input id="new_branch_name" class="form-control @error('name', 'createBranch') is-invalid @enderror" name="name" value="{{ old('name') }}" placeholder="Nhập tên chi nhánh" required>
                        @error('name', 'createBranch')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-5">
                        <label class="form-label small fw-bold" for="new_branch_code">Mã chi nhánh <span class="text-danger">*</span></label>
                        <input id="new_branch_code" class="form-control @error('code', 'createBranch') is-invalid @enderror" name="code" value="{{ old('code') }}" placeholder="VD: CN1, CN2" required>
                        @error('code', 'createBranch')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Row 2: Điện thoại --}}
                <div class="mb-3">
                    <label class="form-label small fw-bold" for="new_branch_phone">Điện thoại</label>
                    <input id="new_branch_phone" class="form-control @error('phone', 'createBranch') is-invalid @enderror" type="text" name="phone" value="{{ old('phone') }}" placeholder="0123 456 789">
                    @error('phone', 'createBranch')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Admin account section --}}
                <div class="p-3 mb-3" style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px;">
                    <p class="small fw-bold mb-1" style="color: #15803d;"><i class="bi bi-person-badge me-1"></i> Tài khoản Admin chi nhánh</p>
                    <p class="small text-muted mb-3" style="font-size: 0.72rem;">Dùng để đăng nhập giao diện quản lý chi nhánh.</p>
                    <div class="row g-3">
                        <div class="col-sm-7">
                            <label class="form-label small fw-bold" for="new_branch_admin_email">Email đăng nhập <span class="text-danger">*</span></label>
                            <input id="new_branch_admin_email" class="form-control @error('admin_email', 'createBranch') is-invalid @enderror" type="email" name="admin_email" value="{{ old('admin_email') }}" placeholder="admin@chinhanh.com" required>
                            @error('admin_email', 'createBranch')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-sm-5">
                            <label class="form-label small fw-bold" for="new_branch_admin_password">Mật khẩu <span class="text-danger">*</span></label>
                            <input id="new_branch_admin_password" class="form-control @error('admin_password', 'createBranch') is-invalid @enderror" type="password" name="admin_password" placeholder="Ít nhất 8 ký tự" required>
                            @error('admin_password', 'createBranch')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold" for="new_branch_address">Địa chỉ</label>
                    <textarea id="new_branch_address" class="form-control @error('address', 'createBranch') is-invalid @enderror" name="address" rows="3" placeholder="Nhập địa chỉ chi nhánh">{{ old('address') }}</textarea>
                    @error('address', 'createBranch')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    @include('admin.partials.location-picker', [
                        'pickerId' => 'create-branch-location-picker',
                        'label' => 'Vị trí chi nhánh',
                        'hint' => 'Nhấn chọn hoặc kéo thả pin trên bản đồ, sau đó bấm "Lấy địa chỉ" để tự động điền.',
                        'latName' => 'latitude',
                        'lngName' => 'longitude',
                        'latValue' => old('latitude'),
                        'lngValue' => old('longitude'),
                        'addressTarget' => '#new_branch_address',
                        'defaultLat' => 19.625017,
                        'defaultLng' => 105.643070,
                        'defaultZoom' => 13,
                    ])
                </div>

                <div class="form-check form-switch mt-3">
                    <input type="hidden" name="status" value="0">
                    <input id="new_branch_status" class="form-check-input" type="checkbox" name="status" value="1" @checked(old('status', '1') === '1')>
                    <label class="form-check-label small fw-semibold" for="new_branch_status">Kích hoạt chi nhánh ngay</label>
                </div>
            </div>
            
            <div class="modal-footer" style="gap: 0.75rem;">
                <button type="button" class="sa-btn" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="sa-btn sa-btn-primary" style="min-width: 160px; background: var(--sa-green); color: #fff; border-color: var(--sa-green);">
                    <i class="bi bi-shop"></i>Thêm chi nhánh
                </button>
            </div>
        </form>
    </div>
</div>

@include('admin.partials.location-picker-script')

@php
    $modalToOpen = old('form_type') === 'admin'
        ? 'createAdminModal'
        : (old('form_type') === 'branch'
            ? 'createBranchModal'
            : (old('form_type') === 'branch-edit' && old('branch_modal_id') ? 'branchEditModal'.old('branch_modal_id') : null));
@endphp

@if($modalToOpen)
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById(@json($modalToOpen));
        if (modal) {
            bootstrap.Modal.getOrCreateInstance(modal).show();
        }
    });
</script>
@endif
@endsection


<script>
function switchTab(event, tabName, adminId) {
    event.preventDefault();
    
    const modalId = 'adminActionsModal' + adminId;
    const modal = document.getElementById(modalId);
    if (!modal) return;
    
    // Hide all tab content for this admin
    const allContents = modal.querySelectorAll('[id^="content-"]');
    allContents.forEach(content => {
        content.style.display = 'none';
    });
    
    // Show the selected tab content
    const selectedContent = document.getElementById('content-' + tabName + '-' + adminId);
    if (selectedContent) {
        selectedContent.style.display = 'block';
    }
    
    // Update tab button styles
    const tabs = modal.querySelectorAll('.admin-action-tab');
    tabs.forEach(tab => {
        if (tab.getAttribute('data-tab') === tabName) {
            tab.style.color = '#0d9373';
            tab.style.borderBottomColor = '#0d9373';
            tab.style.fontWeight = '700';
        } else {
            tab.style.color = '#6b7280';
            tab.style.borderBottomColor = 'transparent';
            tab.style.fontWeight = '600';
        }
    });
}

function renderLoginHistory(adminId) {
    const modal = document.getElementById('adminActionsModal' + adminId);
    if (!modal) return;

    const dateInput = modal.querySelector(`[data-login-history-date-input="${adminId}"]`);
    const emptyState = modal.querySelector(`[data-login-history-empty="${adminId}"]`);
    const rows = modal.querySelectorAll(`[data-login-history-row="${adminId}"]`);
    const selectedDate = dateInput ? dateInput.value : '';
    let visibleCount = 0;

    rows.forEach((row) => {
        const rowDate = row.getAttribute('data-login-date') || '';
        const defaultVisible = row.getAttribute('data-default-visible') === '1';
        const shouldShow = selectedDate ? rowDate === selectedDate : defaultVisible;

        row.classList.toggle('d-none', !shouldShow);

        if (shouldShow) {
            visibleCount += 1;
        }
    });

    if (emptyState) {
        emptyState.classList.toggle('d-none', visibleCount > 0);
    }
}

function filterLoginHistory(adminId) {
    renderLoginHistory(adminId);
}

function clearLoginHistoryFilter(adminId) {
    const modal = document.getElementById('adminActionsModal' + adminId);
    if (!modal) return;

    const dateInput = modal.querySelector(`[data-login-history-date-input="${adminId}"]`);
    if (dateInput) {
        dateInput.value = '';
    }

    renderLoginHistory(adminId);
}

function renderBranchStatusToggle(branchId) {
    const input = document.querySelector(`[data-branch-status-input="${branchId}"]`);
    const button = document.querySelector(`[data-branch-status-toggle="${branchId}"]`);

    if (!input || !button) {
        return;
    }

    const isActive = input.value === '1';
    button.classList.toggle('btn-success', isActive);
    button.classList.toggle('btn-danger', !isActive);
    button.innerHTML = `<i class="bi bi-${isActive ? 'toggle-on' : 'toggle-off'} me-1"></i><span data-branch-status-label="${branchId}">${isActive ? 'Đóng chi nhánh' : 'Mở chi nhánh'}</span>`;
}

function showSuperAdminToast(message, tone = 'success') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${tone} alert-dismissible fade show`;
    alertDiv.style.position = 'fixed';
    alertDiv.style.top = '20px';
    alertDiv.style.right = '20px';
    alertDiv.style.zIndex = '9999';
    alertDiv.innerHTML = `
        <i class="bi bi-${tone === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alertDiv);

    setTimeout(() => {
        alertDiv.remove();
    }, 3000);
}

function setBranchEditErrors(form, errors) {
    const branchId = form.getAttribute('data-branch-id');
    const errorBox = form.querySelector(`[data-branch-edit-errors="${branchId}"]`);

    if (!errorBox) {
        return;
    }

    const messages = Object.values(errors || {}).flat().filter(Boolean);

    if (messages.length === 0) {
        errorBox.classList.add('d-none');
        errorBox.textContent = '';
        return;
    }

    errorBox.classList.remove('d-none');
    errorBox.innerHTML = messages.map((message) => `<div>${message}</div>`).join('');
}

function clearBranchEditErrors(form) {
    setBranchEditErrors(form, {});
}

function syncBranchEditModal(form, branch) {
    const branchId = String(branch.id);
    const nameInput = form.querySelector(`#branch_name_${branchId}`);
    const codeInput = form.querySelector(`#branch_code_${branchId}`);
    const emailInput = form.querySelector(`#branch_email_${branchId}`);
    const phoneInput = form.querySelector(`#branch_phone_${branchId}`);
    const addressInput = form.querySelector(`#branch_address_${branchId}`);
    const mapLinkInput = form.querySelector(`[name="map_link"]`);
    const latitudeInput = form.querySelector(`[name="latitude"]`);
    const longitudeInput = form.querySelector(`[name="longitude"]`);
    const statusInput = form.querySelector(`[data-branch-status-input="${branchId}"]`);

    if (nameInput) nameInput.value = branch.name ?? '';
    if (codeInput) codeInput.value = branch.code ?? '';
    if (emailInput) emailInput.value = branch.email ?? '';
    if (phoneInput) phoneInput.value = branch.phone ?? '';
    if (addressInput) addressInput.value = branch.address ?? '';
    if (mapLinkInput) {
        mapLinkInput.value = branch.map_link ?? (branch.latitude !== null && branch.longitude !== null
            ? `https://www.google.com/maps?q=${branch.latitude},${branch.longitude}`
            : '');
    }
    if (latitudeInput) latitudeInput.value = branch.latitude ?? '';
    if (longitudeInput) longitudeInput.value = branch.longitude ?? '';
    if (statusInput) statusInput.value = branch.status ? '1' : '0';

    renderBranchStatusToggle(branchId);
}

function syncBranchRankingRow(branch) {
    const branchId = String(branch.id);
    const row = document.querySelector(`tr[data-branch-row="${branchId}"]`);

    if (!row) {
        return;
    }

    const nameCell = row.querySelector('[data-branch-name-cell]');
    if (nameCell) {
        nameCell.textContent = branch.name ?? '';
    }

    const statusCell = row.querySelector('[data-branch-status-cell]');
    if (statusCell) {
        statusCell.innerHTML = branch.status
            ? `<span class="sa-state sa-state-active" data-branch-status-badge="${branchId}"><i class="bi bi-check-circle"></i> Hoạt động</span>`
            : `<span class="sa-state" style="background: #fef2f2; color: #991b1b;" data-branch-status-badge="${branchId}"><i class="bi bi-pause-circle"></i> Tạm ngưng</span>`;
    }
}

function hideBranchEditModal(form) {
    const modal = form.closest('.modal');
    if (!modal) {
        return;
    }

    const instance = bootstrap.Modal.getInstance(modal);
    instance?.hide();
}

async function submitBranchEditForm(form) {
    const formData = new FormData(form);
    const action = form.getAttribute('action');
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn ? submitBtn.innerHTML : '';

    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Đang lưu...';
    }

    clearBranchEditErrors(form);

    try {
        const response = await fetch(action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: formData,
        });

        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            if (data?.errors) {
                setBranchEditErrors(form, data.errors);
            }

            throw new Error(data?.message || 'Có lỗi xảy ra. Vui lòng thử lại.');
        }

        const branch = data.branch || {};
        syncBranchEditModal(form, branch);
        syncBranchRankingRow(branch);
        hideBranchEditModal(form);
        showSuperAdminToast(data?.message || 'Cập nhật chi nhánh thành công!', 'success');
    } catch (error) {
        console.error('Branch edit error:', error);

        if (!form.querySelector('[data-branch-edit-errors]')) {
            alert('Có lỗi xảy ra. Vui lòng thử lại.');
        }
    } finally {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }
}

document.querySelectorAll('.branch-edit-form').forEach((form) => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        submitBranchEditForm(form);
    }, true);
});

document.addEventListener('click', function(e) {
    const toggleButton = e.target.closest('[data-branch-status-toggle]');
    if (!toggleButton) {
        return;
    }

    const branchId = toggleButton.getAttribute('data-branch-status-toggle');
    const input = document.querySelector(`[data-branch-status-input="${branchId}"]`);

    if (!input) {
        return;
    }

    input.value = input.value === '1' ? '0' : '1';
    renderBranchStatusToggle(branchId);
});

async function loadAdminsRegion(url) {
    const targetUrl = new URL(url, window.location.origin);
    targetUrl.hash = 'admins';

    const response = await fetch(targetUrl.toString(), {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'text/html',
        },
    });

    if (!response.ok) {
        window.location.href = targetUrl.toString();
        return;
    }

    const html = await response.text();
    const parser = new DOMParser();
    const nextDoc = parser.parseFromString(html, 'text/html');
    const nextRegion = nextDoc.querySelector('[data-admins-region]');
    const currentRegion = document.querySelector('[data-admins-region]');

    if (!nextRegion || !currentRegion) {
        window.location.href = targetUrl.toString();
        return;
    }

    currentRegion.replaceWith(nextRegion);
    window.history.replaceState({}, '', targetUrl.toString());
}

async function loadBranchRankingRegion(url) {
    const targetUrl = new URL(url, window.location.origin);
    targetUrl.hash = 'branch-ranking';

    const response = await fetch(targetUrl.toString(), {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'text/html',
        },
    });

    if (!response.ok) {
        window.location.href = targetUrl.toString();
        return;
    }

    const html = await response.text();
    const parser = new DOMParser();
    const nextDoc = parser.parseFromString(html, 'text/html');
    const nextRegion = nextDoc.querySelector('[data-branch-ranking-region]');
    const currentRegion = document.querySelector('[data-branch-ranking-region]');

    if (!nextRegion || !currentRegion) {
        window.location.href = targetUrl.toString();
        return;
    }

    currentRegion.replaceWith(nextRegion);
    window.history.replaceState({}, '', targetUrl.toString());
}

// Initialize first tab as active on modal open
document.addEventListener('show.bs.modal', function(e) {
    if (e.target && e.target.id.startsWith('adminActionsModal')) {
        const adminId = e.target.id.replace('adminActionsModal', '');
        const firstTab = e.target.querySelector('.admin-action-tab[data-tab="permissions"]');
        if (firstTab) {
            // Simulate click on the first tab
            firstTab.click();
        }
        renderLoginHistory(adminId);
        return;
    }

    if (e.target && e.target.id.startsWith('branchEditModal')) {
        const branchId = e.target.id.replace('branchEditModal', '');
        renderBranchStatusToggle(branchId);
    }
});

document.addEventListener('submit', function(e) {
    if (e.target && e.target.matches('[data-admins-filter-form]')) {
        e.preventDefault();

        const form = e.target;
        const params = new URLSearchParams(new FormData(form));
        const url = new URL(form.getAttribute('action') || window.location.href, window.location.origin);
        url.search = params.toString();

        loadAdminsRegion(url.toString());
    }
});

document.addEventListener('click', function(e) {
    const rankingLink = e.target.closest('[data-ranking-period]');
    if (rankingLink) {
        e.preventDefault();
        loadBranchRankingRegion(rankingLink.getAttribute('href'));
        return;
    }

    const resetLink = e.target.closest('[data-admins-reset]');
    if (resetLink) {
        e.preventDefault();
        loadAdminsRegion(resetLink.getAttribute('href'));
        return;
    }

    const pageLink = e.target.closest('.sa-page-link');
    if (!pageLink || !pageLink.closest('[data-admins-region]')) {
        return;
    }

    if (pageLink.classList.contains('disabled')) {
        e.preventDefault();
        return;
    }

    e.preventDefault();
    loadAdminsRegion(pageLink.getAttribute('href'));
});

// AJAX form submission for role and branch assignment
document.addEventListener('submit', async function(e) {
    const form = e.target;
    const isRoleForm = form.classList.contains('admin-role-form');
    const isAdminBranchForm = form.classList.contains('admin-branch-form');

    if (!isRoleForm && !isAdminBranchForm) {
        return;
    }

    e.preventDefault();
    e.stopPropagation();

    const formData = new FormData(form);
    const action = form.getAttribute('action');
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn ? submitBtn.innerHTML : '';
    const adminId = form.getAttribute('data-admin-id');

    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Đang lưu...';
    }

    try {
        const response = await fetch(action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: formData,
        });

        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(data?.message || 'Có lỗi xảy ra. Vui lòng thử lại.');
        }

        const successMessage = isRoleForm
            ? 'Đã cập nhật vai trò thành công'
            : 'Đã cập nhật chi nhánh thành công';

        showSuperAdminToast(successMessage, 'success');

        if (isRoleForm) {
            const currentRoleDisplay = form.closest('.modal-body').querySelector('[data-current-role]');
            const selectedOption = form.querySelector('select[name="role_id"] option:checked');
            const newRoleLabel = selectedOption.textContent;
            const roleId = form.querySelector('select[name="role_id"]').value;
            const isSuperAdmin = roleId === '3';

            if (currentRoleDisplay) {
                currentRoleDisplay.innerHTML = `
                    <span class="sa-role ${isSuperAdmin ? 'sa-role-super' : 'sa-role-admin'}" style="font-size: 0.75rem;">
                        <i class="bi ${isSuperAdmin ? 'bi-shield-fill-check' : 'bi-gear'}"></i>
                        ${newRoleLabel}
                    </span>
                `;
            }

            const tableRow = document.querySelector(`tr[data-admin-id="${adminId}"]`);
            if (tableRow) {
                const roleCell = tableRow.querySelector('td:nth-child(2)');
                if (roleCell) {
                    roleCell.innerHTML = `
                        <span class="sa-role ${isSuperAdmin ? 'sa-role-super' : 'sa-role-admin'}">
                            <i class="bi ${isSuperAdmin ? 'bi-shield-fill-check' : 'bi-gear'}"></i>
                            ${newRoleLabel}
                        </span>
                    `;
                }
            }
        } else if (isAdminBranchForm) {
            const selectedOption = form.querySelector('select[name="branch_id"] option:checked');
            const selectedBranchId = form.querySelector('select[name="branch_id"]').value;
            const newBranchLabel = selectedBranchId
                ? (selectedOption?.textContent?.trim() || 'Chưa gán')
                : 'Chưa gán';
            const currentBranchDisplay = form.closest('.modal-body').querySelector('[data-current-branch]');
            const tableRow = document.querySelector(`tr[data-admin-id="${adminId}"]`);

            if (currentBranchDisplay) {
                currentBranchDisplay.textContent = newBranchLabel;
            }

            if (tableRow) {
                const branchCell = tableRow.querySelector('[data-admin-branch-cell]');

                if (branchCell) {
                    if (selectedBranchId) {
                        const branchMatch = newBranchLabel.match(/^(.*)\s+\((.*)\)$/);
                        const branchName = branchMatch ? branchMatch[1] : newBranchLabel;
                        const branchCode = branchMatch ? branchMatch[2] : '';

                        branchCell.innerHTML = `
                            <div style="font-size: 0.69rem; line-height: 1.4;" data-admin-branch-display>
                                <div style="color: var(--sa-ink); font-weight: 700;" data-admin-branch-name>${branchName}</div>
                                <div style="color: var(--sa-muted); font-size: 0.62rem;" data-admin-branch-code>${branchCode}</div>
                            </div>
                        `;
                    } else {
                        branchCell.innerHTML = '<span style="color: var(--sa-muted); font-size: 0.69rem;" data-admin-branch-empty>Chưa gán</span>';
                    }
                }
            }
        }
    } catch (error) {
        console.error('Error:', error);

        alert('Có lỗi xảy ra. Vui lòng thử lại.');
    } finally {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }
});
</script>
