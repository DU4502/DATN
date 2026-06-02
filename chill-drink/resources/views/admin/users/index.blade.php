@extends('layouts.admin')

@section('page-title', 'Khách hàng')
@section('search-placeholder', 'Tìm tên hoặc email...')

@section('content')
<section class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="admin-card p-4">
            <p class="admin-kicker mb-1">Tổng khách hàng</p>
            <p class="admin-value text-primary mb-2">{{ $totalCustomers }}</p>
            <span class="badge badge-soft-muted">Khách đăng ký thật</span>
        </div>
    </div>
    <div class="col-md-3">
        <div class="admin-card p-4">
            <p class="admin-kicker mb-1">Hoạt động</p>
            <p class="admin-value mb-2">{{ $activeCustomers }}</p>
            <span class="badge badge-soft-primary">Được đăng nhập</span>
        </div>
    </div>
    <div class="col-md-3">
        <div class="admin-card p-4">
            <p class="admin-kicker mb-1">Đã khóa</p>
            <p class="admin-value mb-2" style="color:var(--admin-danger);">{{ $lockedCustomers }}</p>
            <span class="badge badge-soft-danger">Không thể đăng nhập</span>
        </div>
    </div>
    <div class="col-md-3 d-grid gap-2">
        <button class="btn btn-primary"><i class="bi bi-download me-1"></i>Xuất danh sách</button>
        <button class="btn btn-outline-primary"><i class="bi bi-funnel me-1"></i>Bộ lọc</button>
    </div>
</section>

<section class="admin-card admin-table-card">
    <div class="table-responsive">
        <table class="table admin-table align-middle">
            <thead>
                <tr>
                    <th>Hồ sơ</th>
                    <th>Email</th>
                    <th>Số điện thoại</th>
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
                                    <small class="text-secondary">Thành viên từ {{ optional($user->created_at)->format('Y') }}</small>
                                </span>
                            </div>
                        </td>
                        <td class="text-secondary">{{ $user->email }}</td>
                        <td>{{ $user->phone ?: 'Chưa cập nhật' }}</td>
                        <td>
                            <span class="badge badge-soft-muted">Khách hàng</span>
                        </td>
                        <td>
                            @if($user->is_active)
                                <span class="badge badge-soft-primary">Hoạt động</span>
                            @else
                                <span class="badge badge-soft-danger">Đã khóa</span>
                            @endif
                        </td>
                        <td class="text-secondary">{{ optional($user->created_at)->format('d/m/Y') }}</td>
                        <td class="text-end">
                            <div class="dropdown dropstart">
                                <button class="admin-action" type="button" data-bs-toggle="dropdown" data-bs-boundary="viewport" data-bs-display="dynamic" aria-expanded="false" title="Thao tác">
                                    <i class="bi bi-three-dots"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end admin-dropdown-menu">
                                    <a href="{{ route('admin.users.show', $user) }}" class="dropdown-item">
                                        <i class="bi bi-eye"></i>
                                        Xem
                                    </a>
                                    <a href="{{ route('admin.users.edit', $user) }}" class="dropdown-item">
                                        <i class="bi bi-pencil"></i>
                                        Sửa
                                    </a>
                                    <form action="{{ route('admin.users.toggle-status', $user) }}" method="POST" onsubmit="return confirm('{{ $user->is_active ? 'Bạn chắc chắn muốn khóa tài khoản này? Người dùng sẽ không đăng nhập được nữa.' : 'Bạn chắc chắn muốn mở khóa tài khoản này?' }}');">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="dropdown-item {{ $user->is_active ? 'danger' : '' }}">
                                            <i class="bi {{ $user->is_active ? 'bi-lock' : 'bi-unlock' }}"></i>
                                            {{ $user->is_active ? 'Khóa tài khoản' : 'Mở khóa tài khoản' }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-secondary py-5">
                            <div class="fw-bold text-dark mb-1">Chưa có người dùng</div>
                            <div>Tài khoản mới sẽ hiển thị trong danh sách này.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 p-4 border-top" style="background: var(--admin-soft-2);">
        <p class="text-secondary mb-0">
            @if($totalCustomers > 0)
                Đang hiển thị {{ $users->count() }} / {{ $totalCustomers }} khách hàng
            @else
                Chưa có khách hàng đăng ký
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
