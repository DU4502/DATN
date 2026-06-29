@extends('layouts.admin')

@section('page-title', 'Người dùng')
@section('hide-topbar-search', true)

@section('content')
<section class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
    <div>
        <p class="admin-kicker mb-1">Quản lý tài khoản</p>
        <h2 class="h2 fw-bold mb-1">Người dùng</h2>
        <p class="text-secondary mb-0">Theo dõi tài khoản, vai trò và trạng thái đăng nhập.</p>
    </div>
</section>

<section class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="admin-card p-4 h-100">
            <p class="admin-kicker mb-1">Tổng tài khoản</p>
            <p class="admin-value text-primary mb-0">{{ $stats['total'] }}</p>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="admin-card p-4 h-100">
            <p class="admin-kicker mb-1">Người dùng</p>
            <p class="admin-value mb-0">{{ $stats['customers'] }}</p>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="admin-card p-4 h-100">
            <p class="admin-kicker mb-1">Quản trị viên</p>
            <p class="admin-value mb-0">{{ $stats['admins'] }}</p>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="admin-card p-4 h-100">
            <p class="admin-kicker mb-1">Đã khóa</p>
            <p class="admin-value mb-0" style="color: var(--a-danger);">{{ $stats['locked'] }}</p>
        </div>
    </div>
</section>

<section class="admin-card p-4 mb-4">
    <form method="GET" action="{{ route('admin.users.index') }}" class="row g-3 align-items-end">
        <div class="col-lg-5">
            <label for="q" class="admin-kicker mb-2 d-block">Tìm kiếm</label>
            <input id="q" type="search" name="q" value="{{ request('q') }}" class="admin-input" placeholder="Tên, email, số điện thoại">
        </div>
        <div class="col-sm-6 col-lg-3">
            <label for="role" class="admin-kicker mb-2 d-block">Vai trò</label>
            <select id="role" name="role" class="admin-filter">
                <option value="">Tất cả vai trò</option>
                @foreach($roleOptions as $roleId => $roleName)
                    <option value="{{ $roleId }}" @selected((string) request('role') === (string) $roleId)>{{ $roleName }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-6 col-lg-2">
            <label for="status" class="admin-kicker mb-2 d-block">Trạng thái</label>
            <select id="status" name="status" class="admin-filter">
                <option value="">Tất cả</option>
                <option value="active" @selected(request('status') === 'active')>Hoạt động</option>
                <option value="locked" @selected(request('status') === 'locked')>Đã khóa</option>
            </select>
        </div>
        <div class="col-lg-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-search me-1"></i>Lọc</button>
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-primary" title="Xóa lọc"><i class="bi bi-x-lg"></i></a>
        </div>
    </form>
</section>

<section class="admin-card admin-table-card">
    <div class="table-responsive">
        <table class="table admin-table align-middle" style="min-width: 920px;">
            <thead>
                <tr>
                    <th>Hồ sơ</th>
                    <th>Liên hệ</th>
                    <th>Vai trò</th>
                    <th>Trạng thái</th>
                    <th>Ngày tạo</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    @php
                        $avatar = $user->avatar;
                        $avatarIsImage = $avatar && ! str_starts_with($avatar, 'preset-');
                        $avatarUrl = $avatarIsImage ? \Illuminate\Support\Facades\Storage::disk('public')->url($avatar) : null;
                        $roleName = $roleOptions[(int) $user->role_id] ?? 'Không rõ';
                    @endphp
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <span class="admin-avatar" style="width:48px;height:48px;" aria-label="Avatar {{ $user->name }}">
                                    @if($avatarUrl)
                                        <img src="{{ $avatarUrl }}" alt="{{ $user->name }}">
                                    @else
                                        {{ mb_substr($user->name, 0, 1) }}
                                    @endif
                                </span>
                                <span>
                                    <span class="fw-bold d-block">{{ $user->name }}</span>
                                    <small class="text-secondary">ID #{{ $user->id }}</small>
                                </span>
                            </div>
                        </td>
                        <td>
                            <div class="fw-bold">{{ $user->email }}</div>
                            <small class="text-secondary">{{ $user->phone ?: 'Chưa có số điện thoại' }}</small>
                        </td>
                        <td>
                            <span class="badge {{ $user->isAdmin() ? 'badge-soft-primary' : 'badge-soft-muted' }}">{{ $roleName }}</span>
                        </td>
                        <td>
                            @if($user->is_active)
                                <span class="badge badge-soft-primary">Hoạt động</span>
                            @else
                                <span class="badge badge-soft-danger">Đã khóa</span>
                            @endif
                        </td>
                        <td class="text-secondary">{{ optional($user->created_at)->format('d/m/Y') ?: '-' }}</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('admin.users.show', $user) }}" class="admin-action text-decoration-none" title="Xem"><i class="bi bi-eye"></i></a>
                            <button type="button" class="admin-action" title="Đổi vai trò" data-bs-toggle="modal" data-bs-target="#change-user-role-{{ $user->id }}">
                                <i class="bi bi-pencil"></i>
                            </button>
                            @if($user->id !== auth()->id())
                                <form action="{{ route('admin.users.toggle-status', $user) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ $user->is_active ? 'Khóa tài khoản này?' : 'Mở khóa tài khoản này?' }}');">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="admin-action" title="{{ $user->is_active ? 'Khóa tài khoản' : 'Mở khóa tài khoản' }}" style="color: {{ $user->is_active ? 'var(--a-danger)' : 'var(--a-primary)' }};">
                                        <i class="bi {{ $user->is_active ? 'bi-lock' : 'bi-unlock' }}"></i>
                                    </button>
                                </form>
                            @endif

                            <div class="modal fade text-start" id="change-user-role-{{ $user->id }}" tabindex="-1" aria-labelledby="change-user-role-title-{{ $user->id }}" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content" style="border-radius: var(--radius-md);">
                                        <form action="{{ route('admin.users.update', $user) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-header">
                                                <h3 class="modal-title h4 fw-bold" id="change-user-role-title-{{ $user->id }}">Thay đổi vai trò</h3>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p class="admin-kicker mb-1">Người dùng</p>
                                                <div class="fw-bold mb-3">{{ $user->name }}</div>

                                                <label for="role_id_{{ $user->id }}" class="form-label fw-semibold">Vai trò mới</label>
                                                <select id="role_id_{{ $user->id }}" name="role_id" class="form-select" @disabled($user->id === auth()->id())>
                                                    @foreach($roleOptions as $roleId => $roleName)
                                                        <option value="{{ $roleId }}" @selected((int) $user->role_id === (int) $roleId)>{{ $roleName }}</option>
                                                    @endforeach
                                                </select>

                                                @if($user->id === auth()->id())
                                                    <input type="hidden" name="role_id" value="{{ $user->role_id }}">
                                                    <small class="text-secondary d-block mt-2">Không thể tự thay đổi vai trò của tài khoản đang đăng nhập.</small>
                                                @endif
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Hủy</button>
                                                <button type="submit" class="btn btn-primary" @disabled($user->id === auth()->id())>Lưu thay đổi</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-secondary py-5">
                            <div class="fw-bold text-dark mb-1">Không có người dùng phù hợp</div>
                            <div>Thử xóa bộ lọc hoặc thay đổi từ khóa tìm kiếm.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 p-4 border-top" style="background: var(--admin-soft-2);">
        <p class="text-secondary mb-0">
            @if($users->total() > 0)
                Đang hiển thị {{ $users->firstItem() }}-{{ $users->lastItem() }} / {{ $users->total() }} người dùng
            @else
                Chưa có người dùng phù hợp
            @endif
        </p>
        {{ $users->links('pagination::bootstrap-5') }}
    </div>
</section>

<section class="row g-4 mt-4">
    <div class="col-lg-8">
        <div class="admin-card p-4">
            <h3 class="h4 fw-bold text-primary mb-2">Phân tích khách hàng</h3>
            <div class="admin-empty-state">
                <span class="admin-icon-dot mx-auto mb-3"><i class="bi bi-graph-up"></i></span>
                <div class="fw-bold text-dark mb-1">Chưa có thống kê hành vi thật</div>
                <p class="mb-0">Không hiển thị chỉ số phân tích khi chưa có dữ liệu thật từ đơn hàng.</p>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="admin-card p-4 h-100">
            <h3 class="h4 fw-bold mb-2">Ghi chú dữ liệu</h3>
            <p class="text-secondary mb-0">Trang này chỉ hiển thị thông tin đang có trong bảng người dùng: tên, email, số điện thoại, vai trò và ngày tạo.</p>
        </div>
    </div>
</section>
@endsection
