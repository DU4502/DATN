@extends('layouts.super-admin')

@section('page-title', 'Dashboard')

@section('content')
<style>
    .sa-page {
        --sa-green: #0d9373;
        --sa-green-dark: #067a5f;
        --sa-green-soft: #e7f7f2;
        --sa-ink: #111827;
        --sa-muted: #6b7280;
        --sa-border: #e1e6e4;
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
        <button type="button" class="sa-btn sa-btn-primary" data-bs-toggle="modal" data-bs-target="#createAdminModal">
            <i class="bi bi-person-plus"></i> Thêm Admin
        </button>
    </header>

    @if(session('success'))
        <div class="alert alert-success sa-alert"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger sa-alert"><i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}</div>
    @endif

    <section class="sa-stats" aria-label="Thống kê hệ thống">
        @foreach([
            ['bi-people', $totalUserCount, 'Tổng tài khoản', 'Toàn hệ thống'],
            ['bi-person-badge', $adminCount, 'Tổng Admin', 'Quyền quản trị'],
            ['bi-person-check', $activeAdminCount, 'Admin hoạt động', 'Đang được phép'],
            ['bi-shop', $branchCount, 'Tổng chi nhánh', 'Trong hệ thống'],
            ['bi-bag-check', $orderStats['today_count'], 'Đơn hàng hôm nay', 'Trong ngày'],
            ['bi-cash-stack', number_format($orderStats['today_revenue'], 0, ',', '.').'đ', 'Doanh thu hôm nay', 'Đã thanh toán'],
            ['bi-calendar3', number_format($orderStats['month_revenue'], 0, ',', '.').'đ', 'Doanh thu tháng', 'Tháng hiện tại'],
            ['bi-cup-straw', $productCount, 'Tổng sản phẩm', 'Trong thực đơn'],
            ['bi-grid', $categoryCount, 'Tổng danh mục', 'Đang quản lý'],
            ['bi-diagram-3', $roleCount, 'Vai trò hệ thống', 'Bảng phân quyền'],
        ] as [$icon, $value, $label, $source])
            <article class="sa-stat">
                <div class="sa-stat-top"><span class="sa-stat-icon"><i class="bi {{ $icon }}"></i></span><span class="sa-stat-note">{{ $source }}</span></div>
                <div class="sa-stat-value">{{ is_numeric($value) ? number_format($value) : $value }}</div>
                <div class="sa-stat-label">{{ $label }}</div>
            </article>
        @endforeach
    </section>

    <section class="sa-grid-2" aria-label="Biểu đồ hệ thống">
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

    <section class="sa-panel" id="admins">
        <div class="sa-panel-header">
            <div><h2 class="sa-panel-title">Danh sách quản trị viên</h2><p class="sa-panel-note">{{ $adminUsers->total() }} tài khoản phù hợp</p></div>
        </div>
        <form class="sa-filter-form" method="GET" action="{{ route('admin.super-admin') }}">
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
                <a class="sa-btn" href="{{ route('admin.super-admin') }}#admins" title="Xóa bộ lọc"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>

        <div class="sa-table-wrap">
            <table class="sa-table">
                <thead><tr><th>Quản trị viên</th><th>Vai trò</th><th>Lần đăng nhập cuối</th><th>Hiện diện</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
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
                        <tr>
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
                            <td>{{ $adminUser->last_login_at?->format('d/m/Y H:i') ?? ($isCurrentAccount ? 'Phiên hiện tại' : 'Chưa đăng nhập') }}</td>
                            <td><span class="sa-presence sa-presence-{{ $presence }}">{{ $presenceLabel }}</span></td>
                            <td><span class="sa-state {{ $adminUser->is_active ? 'sa-state-active' : 'sa-state-locked' }}">{{ $adminUser->is_active ? 'Active' : 'Locked' }}</span></td>
                            <td>
                                <div class="sa-actions">
                                    <a class="sa-action-btn" href="{{ route('admin.users.show', $adminUser) }}" title="Xem"><i class="bi bi-eye"></i></a>
                                    <a class="sa-action-btn" href="{{ route('admin.users.edit', $adminUser) }}" title="Đổi quyền"><i class="bi bi-key"></i></a>
                                    @if(!$adminUser->is(auth()->user()))
                                        <form method="POST" action="{{ route('admin.users.toggle-status', $adminUser) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="sa-action-btn {{ $adminUser->is_active ? 'danger' : '' }}" type="submit" title="{{ $adminUser->is_active ? 'Khóa' : 'Mở khóa' }}"><i class="bi {{ $adminUser->is_active ? 'bi-lock' : 'bi-unlock' }}"></i></button>
                                        </form>
                                    @endif
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
                <a class="sa-page-link {{ $adminUsers->hasMorePages() ? '' : 'disabled' }}" href="{{ $adminUsers->nextPageUrl() ?? '#' }}" aria-label="Trang sau"><i class="bi bi-chevron-right"></i></a>
            </div>
        </div>
    </section>

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
            <div class="modal-header"><h2 class="modal-title fs-6 fw-bold" id="createAdminModalLabel">Thêm tài khoản Admin</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label small fw-bold" for="admin_name">Họ và tên</label><input id="admin_name" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="mb-3"><label class="form-label small fw-bold" for="admin_email">Email</label><input id="admin_email" class="form-control @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email') }}" required>@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="row g-3">
                    <div class="col-sm-6"><label class="form-label small fw-bold" for="admin_password">Mật khẩu ban đầu</label><input id="admin_password" class="form-control @error('password') is-invalid @enderror" type="password" name="password" required>@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-sm-6"><label class="form-label small fw-bold" for="admin_password_confirmation">Xác nhận mật khẩu</label><input id="admin_password_confirmation" class="form-control" type="password" name="password_confirmation" required></div>
                </div>
                <div class="form-check form-switch mt-3"><input type="hidden" name="is_active" value="0"><input id="admin_active" class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', '1') === '1')><label class="form-check-label small fw-semibold" for="admin_active">Kích hoạt ngay</label></div>
            </div>
            <div class="modal-footer"><button type="button" class="sa-btn" data-bs-dismiss="modal">Hủy</button><button type="submit" class="sa-btn sa-btn-primary"><i class="bi bi-person-plus"></i>Tạo Admin</button></div>
        </form>
    </div>
</div>

@if($errors->any())
<script>
    document.addEventListener('DOMContentLoaded', () => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('createAdminModal')).show();
    });
</script>
@endif
@endsection
