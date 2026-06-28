@extends('layouts.super-admin')

@section('page-title', 'Super Admin')
@section('search-placeholder', 'Tìm quản trị viên, vai trò...')

@section('content')
@php
    $adminUsers = \App\Models\User::admins()
        ->orderByDesc('is_active')
        ->orderBy('name')
        ->get();
    $adminCount = $adminUsers->count();
    $activeAdminCount = $adminUsers->where('is_active', true)->count();
    $customerCount = \App\Models\User::customers()->count();
    $roleCount = \Illuminate\Support\Facades\Schema::hasTable('roles')
        ? \Illuminate\Support\Facades\DB::table('roles')->count()
        : 0;
@endphp
<style>
    .sa-page {
        --sa-green: #0d9373;
        --sa-green-dark: #067a5f;
        --sa-green-soft: #e7f7f2;
        --sa-ink: #111827;
        --sa-muted: #6b7280;
        --sa-border: #e5e7eb;
        display: grid;
        gap: 1.25rem;
    }

    .sa-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 1rem;
    }

    .sa-kicker {
        margin: 0 0 0.3rem;
        color: var(--sa-green);
        font-size: 0.72rem;
        font-weight: 800;
        text-transform: uppercase;
    }

    .sa-title {
        margin: 0;
        color: var(--sa-ink);
        font-size: 1.45rem;
        font-weight: 800;
    }

    .sa-subtitle {
        margin: 0.35rem 0 0;
        color: var(--sa-muted);
        font-size: 0.86rem;
    }

    .sa-actions {
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }

    .sa-btn {
        min-height: 40px;
        border: 1px solid var(--sa-border);
        border-radius: 7px;
        padding: 0.55rem 0.85rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.45rem;
        background: #fff;
        color: var(--sa-ink);
        font-size: 0.8rem;
        font-weight: 700;
    }

    .sa-btn-primary {
        border-color: var(--sa-green);
        background: var(--sa-green);
        color: #fff;
    }

    .sa-btn:hover {
        border-color: var(--sa-green);
        color: var(--sa-green-dark);
    }

    .sa-btn-primary:hover {
        background: var(--sa-green-dark);
        color: #fff;
    }

    .sa-status-band {
        min-height: 54px;
        padding: 0.75rem 1rem;
        border: 1px solid #bfe8dc;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        background: #f4fcf9;
    }

    .sa-status-main,
    .sa-status-items,
    .sa-status-item {
        display: flex;
        align-items: center;
    }

    .sa-status-main { gap: 0.65rem; }
    .sa-status-items { gap: 1.15rem; flex-wrap: wrap; }
    .sa-status-item { gap: 0.4rem; color: var(--sa-muted); font-size: 0.76rem; font-weight: 600; }

    .sa-status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #10b981;
        box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.12);
    }

    .sa-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.85rem;
    }

    .sa-stat {
        min-height: 112px;
        padding: 1rem;
        border: 1px solid var(--sa-border);
        border-radius: 8px;
        background: #fff;
    }

    .sa-stat-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
    }

    .sa-stat-icon {
        width: 36px;
        height: 36px;
        border-radius: 7px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--sa-green-soft);
        color: var(--sa-green);
    }

    .sa-trend {
        color: #059669;
        font-size: 0.72rem;
        font-weight: 700;
    }

    .sa-stat-value {
        margin-top: 0.65rem;
        color: var(--sa-ink);
        font-size: 1.45rem;
        font-weight: 800;
        letter-spacing: 0;
        line-height: 1;
    }

    .sa-stat-label {
        margin-top: 0.35rem;
        color: var(--sa-muted);
        font-size: 0.76rem;
        font-weight: 600;
    }

    .sa-main-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.6fr) minmax(280px, 0.7fr);
        gap: 1rem;
        align-items: start;
    }

    .sa-panel {
        border: 1px solid var(--sa-border);
        border-radius: 8px;
        background: #fff;
        overflow: hidden;
        scroll-margin-top: 84px;
    }

    .sa-panel-header {
        min-height: 58px;
        padding: 0.8rem 1rem;
        border-bottom: 1px solid var(--sa-border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
    }

    .sa-panel-title {
        margin: 0;
        color: var(--sa-ink);
        font-size: 0.92rem;
        font-weight: 800;
    }

    .sa-panel-note {
        margin: 0.2rem 0 0;
        color: var(--sa-muted);
        font-size: 0.72rem;
    }

    .sa-filter {
        display: inline-flex;
        padding: 3px;
        border-radius: 7px;
        background: #f3f4f6;
    }

    .sa-filter button {
        border: 0;
        border-radius: 5px;
        padding: 0.38rem 0.65rem;
        background: transparent;
        color: var(--sa-muted);
        font-size: 0.7rem;
        font-weight: 700;
    }

    .sa-filter button.active {
        background: #fff;
        color: var(--sa-green-dark);
        box-shadow: 0 1px 4px rgba(17, 24, 39, 0.08);
    }

    .sa-table-wrap { overflow-x: auto; }

    .sa-table {
        width: 100%;
        min-width: 760px;
        border-collapse: collapse;
    }

    .sa-table th {
        padding: 0.65rem 1rem;
        background: #f9fafb;
        color: var(--sa-muted);
        font-size: 0.68rem;
        font-weight: 800;
        text-align: left;
        text-transform: uppercase;
    }

    .sa-table td {
        padding: 0.8rem 1rem;
        border-top: 1px solid #f0f2f4;
        color: #374151;
        font-size: 0.78rem;
        vertical-align: middle;
    }

    .sa-admin-cell {
        display: flex;
        align-items: center;
        gap: 0.65rem;
    }

    .sa-avatar {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #e7f7f2;
        color: var(--sa-green-dark);
        font-size: 0.72rem;
        font-weight: 800;
        flex: 0 0 auto;
    }

    .sa-admin-name { color: var(--sa-ink); font-weight: 800; }
    .sa-admin-email { margin-top: 0.1rem; color: var(--sa-muted); font-size: 0.7rem; }

    .sa-role,
    .sa-state {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        border-radius: 999px;
        padding: 0.28rem 0.55rem;
        font-size: 0.68rem;
        font-weight: 800;
        white-space: nowrap;
    }

    .sa-role-super { background: #fef3c7; color: #92400e; }
    .sa-role-ops { background: #e0f2fe; color: #075985; }
    .sa-role-support { background: #f3e8ff; color: #7e22ce; }
    .sa-state { background: #dcfce7; color: #166534; }
    .sa-state::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: #22c55e; }
    .sa-state-inactive { background: #f3f4f6; color: #6b7280; }
    .sa-state-inactive::before { background: #9ca3af; }

    .sa-icon-btn {
        width: 32px;
        height: 32px;
        border: 1px solid var(--sa-border);
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #fff;
        color: var(--sa-muted);
    }

    .sa-icon-btn:hover { border-color: var(--sa-green); color: var(--sa-green); }

    .sa-activity-list { padding: 0.25rem 1rem; }

    .sa-activity {
        position: relative;
        padding: 0.85rem 0 0.85rem 1.75rem;
        border-bottom: 1px solid #f0f2f4;
    }

    .sa-activity:last-child { border-bottom: 0; }

    .sa-activity-icon {
        position: absolute;
        left: 0;
        top: 0.9rem;
        width: 24px;
        height: 24px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--sa-green-soft);
        color: var(--sa-green);
        font-size: 0.72rem;
    }

    .sa-activity p { margin: 0; color: #374151; font-size: 0.76rem; line-height: 1.45; }
    .sa-activity strong { color: var(--sa-ink); }
    .sa-activity time { display: block; margin-top: 0.2rem; color: #9ca3af; font-size: 0.67rem; }

    .sa-empty-log {
        min-height: 210px;
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: var(--sa-muted);
    }

    .sa-empty-log i {
        margin-bottom: 0.65rem;
        color: var(--sa-green);
        font-size: 1.65rem;
    }

    .sa-empty-log strong { color: var(--sa-ink); font-size: 0.8rem; }
    .sa-empty-log span { margin-top: 0.25rem; font-size: 0.7rem; }

    .sa-permission-section { padding: 1rem; }

    .sa-tabs {
        display: flex;
        gap: 0.35rem;
        margin-bottom: 0.9rem;
        overflow-x: auto;
    }

    .sa-tab {
        border: 1px solid var(--sa-border);
        border-radius: 6px;
        padding: 0.45rem 0.7rem;
        background: #fff;
        color: var(--sa-muted);
        font-size: 0.72rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .sa-tab.active { border-color: #b7e4d7; background: var(--sa-green-soft); color: var(--sa-green-dark); }

    .sa-permissions {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.6rem;
    }

    .sa-permission {
        min-height: 58px;
        padding: 0.65rem;
        border: 1px solid var(--sa-border);
        border-radius: 7px;
        display: flex;
        align-items: center;
        gap: 0.55rem;
        color: #374151;
        font-size: 0.72rem;
        font-weight: 700;
    }

    .sa-permission i { color: var(--sa-green); }
    .sa-permission.is-hidden { display: none; }

    .sa-modal-note {
        padding: 0.65rem 0.75rem;
        border: 1px solid #bfdbfe;
        border-radius: 7px;
        background: #eff6ff;
        color: #1e40af;
        font-size: 0.76rem;
    }

    @media (max-width: 1399.98px) {
        .sa-main-grid { grid-template-columns: 1fr; }
    }

    @media (max-width: 1199.98px) {
        .sa-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .sa-permissions { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    @media (max-width: 767.98px) {
        .sa-header { align-items: stretch; flex-direction: column; }
        .sa-actions { display: grid; grid-template-columns: 1fr 1fr; }
        .sa-status-band { align-items: flex-start; flex-direction: column; }
        .sa-stats { grid-template-columns: 1fr; }
        .sa-permissions { grid-template-columns: 1fr; }
    }
</style>

<div class="sa-page">
    <div class="sa-header">
        <div>
            <p class="sa-kicker">Điều hành hệ thống</p>
            <h2 class="sa-title">Trung tâm Super Admin</h2>
            <p class="sa-subtitle">Theo dõi quản trị viên, vai trò và quyền truy cập trên toàn bộ Chill Drink.</p>
        </div>
        <div class="sa-actions">
            <button type="button" class="sa-btn" data-bs-toggle="modal" data-bs-target="#permissionModal">
                <i class="bi bi-sliders"></i> Thiết lập quyền
            </button>
            <button type="button" class="sa-btn sa-btn-primary" data-bs-toggle="modal" data-bs-target="#adminModal">
                <i class="bi bi-person-plus"></i> Thêm quản trị viên
            </button>
        </div>
    </div>

    <div class="sa-status-band">
        <div class="sa-status-main">
            <span class="sa-status-dot"></span>
            <div>
                <strong class="d-block" style="font-size:0.8rem;">Hệ thống hoạt động ổn định</strong>
                <span class="text-secondary" style="font-size:0.7rem;">Cập nhật lần cuối: vừa xong</span>
            </div>
        </div>
        <div class="sa-status-items">
            <span class="sa-status-item"><i class="bi bi-database-check"></i> Database ổn định</span>
            <span class="sa-status-item"><i class="bi bi-shield-check"></i> Phiên đăng nhập hợp lệ</span>
            <span class="sa-status-item"><i class="bi bi-arrow-repeat"></i> Dữ liệu trực tiếp</span>
        </div>
    </div>

    <div class="sa-stats">
        <div class="sa-stat">
            <div class="sa-stat-top"><span class="sa-stat-icon"><i class="bi bi-person-badge"></i></span><span class="sa-trend">Trong database</span></div>
            <div class="sa-stat-value">{{ number_format($adminCount) }}</div>
            <div class="sa-stat-label">Tài khoản quản trị</div>
        </div>
        <div class="sa-stat">
            <div class="sa-stat-top"><span class="sa-stat-icon"><i class="bi bi-person-check"></i></span><span class="sa-trend">Đang được phép</span></div>
            <div class="sa-stat-value">{{ number_format($activeAdminCount) }}</div>
            <div class="sa-stat-label">Admin đang hoạt động</div>
        </div>
        <div class="sa-stat">
            <div class="sa-stat-top"><span class="sa-stat-icon"><i class="bi bi-people"></i></span><span class="sa-trend">Tài khoản hiện có</span></div>
            <div class="sa-stat-value">{{ number_format($customerCount) }}</div>
            <div class="sa-stat-label">Khách hàng</div>
        </div>
        <div class="sa-stat">
            <div class="sa-stat-top"><span class="sa-stat-icon"><i class="bi bi-diagram-3"></i></span><span class="sa-trend">Bảng roles</span></div>
            <div class="sa-stat-value">{{ number_format($roleCount) }}</div>
            <div class="sa-stat-label">Vai trò hệ thống</div>
        </div>
    </div>

    <div class="sa-main-grid">
        <section class="sa-panel" id="admins">
            <div class="sa-panel-header">
                <div>
                    <h3 class="sa-panel-title">Tài khoản quản trị</h3>
                    <p class="sa-panel-note">Quản lý tài khoản có quyền truy cập hệ thống.</p>
                </div>
                <div class="sa-filter" data-admin-filter>
                    <button type="button" class="active" data-filter="all">Tất cả</button>
                    <button type="button" data-filter="super">Super</button>
                    <button type="button" data-filter="ops">Vận hành</button>
                </div>
            </div>
            <div class="sa-table-wrap">
                <table class="sa-table">
                    <thead>
                        <tr><th>Quản trị viên</th><th>Vai trò</th><th>Phạm vi</th><th>Trạng thái</th><th>Thao tác</th></tr>
                    </thead>
                    <tbody>
                        @forelse($adminUsers as $adminUser)
                            @php
                                $isSuperAccount = $adminUser->email === 'superadmin@chilldrink.com';
                                $initials = collect(preg_split('/\s+/u', trim($adminUser->name)))
                                    ->filter()
                                    ->take(2)
                                    ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
                                    ->implode('');
                            @endphp
                            <tr data-admin-row="{{ $isSuperAccount ? 'super' : 'ops' }}">
                                <td><div class="sa-admin-cell"><span class="sa-avatar">{{ $initials ?: 'AD' }}</span><div><div class="sa-admin-name">{{ $adminUser->name }}</div><div class="sa-admin-email">{{ $adminUser->email }}</div></div></div></td>
                                <td>
                                    <span class="sa-role {{ $isSuperAccount ? 'sa-role-super' : 'sa-role-ops' }}">
                                        <i class="bi {{ $isSuperAccount ? 'bi-shield-fill-check' : 'bi-gear' }}"></i>
                                        {{ $isSuperAccount ? 'Super Admin' : 'Admin hệ thống' }}
                                    </span>
                                </td>
                                <td>Toàn hệ thống</td>
                                <td><span class="sa-state {{ $adminUser->is_active ? '' : 'sa-state-inactive' }}">{{ $adminUser->is_active ? 'Đang hoạt động' : 'Đã khóa' }}</span></td>
                                <td><button class="sa-icon-btn" type="button" title="Chỉnh sửa" data-bs-toggle="modal" data-bs-target="#permissionModal"><i class="bi bi-pencil"></i></button></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-secondary py-4">Chưa có tài khoản quản trị.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <aside class="sa-panel" id="audit">
            <div class="sa-panel-header">
                <div><h3 class="sa-panel-title">Hoạt động gần đây</h3><p class="sa-panel-note">Theo dõi các thay đổi quan trọng.</p></div>
                <button type="button" class="sa-icon-btn" title="Lọc nhật ký"><i class="bi bi-funnel"></i></button>
            </div>
            <div class="sa-empty-log">
                <i class="bi bi-journal-check"></i>
                <strong>Chưa ghi nhận nhật ký hệ thống</strong>
                <span>Không có dữ liệu nhật ký trong hệ thống.</span>
            </div>
        </aside>
    </div>

    <section class="sa-panel" id="permissions">
        <div class="sa-panel-header">
            <div><h3 class="sa-panel-title">Ma trận quyền truy cập</h3><p class="sa-panel-note">Chọn vai trò để xem phạm vi quyền tương ứng.</p></div>
            <span class="sa-role sa-role-super"><i class="bi bi-shield-check"></i> Quyền hệ thống</span>
        </div>
        <div class="sa-permission-section">
            <div class="sa-tabs" data-permission-tabs>
                <button type="button" class="sa-tab active" data-role="super">Super Admin</button>
                <button type="button" class="sa-tab" data-role="ops">Admin vận hành</button>
                <button type="button" class="sa-tab" data-role="support">CSKH</button>
                <button type="button" class="sa-tab" data-role="content">Nội dung</button>
            </div>
            <div class="sa-permissions">
                <div class="sa-permission" data-roles="super ops"><i class="bi bi-check-circle-fill"></i> Tổng quan hệ thống</div>
                <div class="sa-permission" data-roles="super ops content"><i class="bi bi-check-circle-fill"></i> Quản lý sản phẩm</div>
                <div class="sa-permission" data-roles="super ops"><i class="bi bi-check-circle-fill"></i> Quản lý đơn hàng</div>
                <div class="sa-permission" data-roles="super support"><i class="bi bi-check-circle-fill"></i> Quản lý khách hàng</div>
                <div class="sa-permission" data-roles="super content"><i class="bi bi-check-circle-fill"></i> Quản lý voucher</div>
                <div class="sa-permission" data-roles="super support"><i class="bi bi-check-circle-fill"></i> Kiểm duyệt đánh giá</div>
                <div class="sa-permission" data-roles="super"><i class="bi bi-check-circle-fill"></i> Phân quyền quản trị</div>
                <div class="sa-permission" data-roles="super"><i class="bi bi-check-circle-fill"></i> Nhật ký và bảo mật</div>
            </div>
        </div>
    </section>
</div>

<div class="modal fade" id="adminModal" tabindex="-1" aria-labelledby="adminModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border:0;border-radius:8px;">
            <div class="modal-header"><h2 class="modal-title fs-6 fw-bold" id="adminModalLabel">Thêm quản trị viên</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div>
            <div class="modal-body">
                <div class="sa-modal-note mb-3"><i class="bi bi-info-circle me-1"></i> Quản trị viên sẽ nhận thông tin truy cập qua email.</div>
                <div class="mb-3"><label class="form-label small fw-bold">Họ và tên</label><input type="text" class="form-control" placeholder="Nhập tên quản trị viên"></div>
                <div class="mb-3"><label class="form-label small fw-bold">Email</label><input type="email" class="form-control" placeholder="admin@chilldrink.vn"></div>
                <div><label class="form-label small fw-bold">Vai trò</label><select class="form-select"><option>Admin vận hành</option><option>Chăm sóc khách hàng</option><option>Quản trị nội dung</option></select></div>
            </div>
            <div class="modal-footer"><button type="button" class="sa-btn" data-bs-dismiss="modal">Hủy</button><button type="button" class="sa-btn sa-btn-primary" data-demo-save>Gửi lời mời</button></div>
        </div>
    </div>
</div>

<div class="modal fade" id="permissionModal" tabindex="-1" aria-labelledby="permissionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border:0;border-radius:8px;">
            <div class="modal-header"><h2 class="modal-title fs-6 fw-bold" id="permissionModalLabel">Thiết lập quyền</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div>
            <div class="modal-body">
                <div class="sa-modal-note mb-3"><i class="bi bi-info-circle me-1"></i> Thay đổi quyền sẽ áp dụng cho vai trò đang chọn.</div>
                @foreach(['Quản lý sản phẩm', 'Quản lý đơn hàng', 'Quản lý khách hàng', 'Quản lý voucher'] as $permission)
                    <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                        <span class="small fw-semibold">{{ $permission }}</span>
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" checked></div>
                    </div>
                @endforeach
            </div>
            <div class="modal-footer"><button type="button" class="sa-btn" data-bs-dismiss="modal">Đóng</button><button type="button" class="sa-btn sa-btn-primary" data-demo-save>Lưu thay đổi</button></div>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('[data-admin-filter] button').forEach((button) => {
        button.addEventListener('click', () => {
            document.querySelectorAll('[data-admin-filter] button').forEach((item) => item.classList.remove('active'));
            button.classList.add('active');
            const filter = button.dataset.filter;
            document.querySelectorAll('[data-admin-row]').forEach((row) => {
                row.hidden = filter !== 'all' && row.dataset.adminRow !== filter;
            });
        });
    });

    document.querySelectorAll('[data-permission-tabs] .sa-tab').forEach((tab) => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('[data-permission-tabs] .sa-tab').forEach((item) => item.classList.remove('active'));
            tab.classList.add('active');
            const role = tab.dataset.role;
            document.querySelectorAll('.sa-permission').forEach((permission) => {
                const roles = permission.dataset.roles.split(' ');
                permission.classList.toggle('is-hidden', role !== 'super' && !roles.includes(role));
            });
        });
    });

    document.querySelectorAll('[data-demo-save]').forEach((button) => {
        button.addEventListener('click', () => {
            const originalLabel = button.textContent.trim();
            button.innerHTML = '<i class="bi bi-check2"></i> Đã hoàn tất';
            button.disabled = true;
            setTimeout(() => {
                button.textContent = originalLabel;
                button.disabled = false;
            }, 1600);
        });
    });
</script>
@endsection
