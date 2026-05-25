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
            <p class="admin-kicker mb-1">Đang hiển thị</p>
            <p class="admin-value mb-2">{{ $users->count() }}</p>
            <span class="badge badge-soft-muted">Trang {{ $users->currentPage() }}</span>
        </div>
    </div>
    <div class="col-md-3">
        <div class="admin-card p-4">
            <p class="admin-kicker mb-1">Quản trị viên</p>
            <p class="admin-value mb-2">{{ $totalAdmins }}</p>
            <span class="badge badge-soft-primary">Nội bộ</span>
        </div>
    </div>
    <div class="col-md-3 d-grid gap-2">
        <button class="btn btn-primary"><i class="bi bi-download me-1"></i>Xuất danh sách</button>
        <button class="btn btn-outline-primary"><i class="bi bi-funnel me-1"></i>Bộ lọc</button>
    </div>
</section>

<section class="admin-card">
    <div class="table-responsive">
        <table class="table admin-table align-middle">
            <thead>
                <tr>
                    <th>Hồ sơ</th>
                    <th>Email</th>
                    <th>Số điện thoại</th>
                    <th>Vai trò</th>
                    <th>Ngày tạo</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <span class="admin-avatar" style="width:48px;height:48px;">{{ mb_substr($user->name, 0, 1) }}</span>
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
                        <td class="text-secondary">{{ optional($user->created_at)->format('d/m/Y') }}</td>
                        <td class="text-end">
                            <button class="admin-action" title="Chi tiết">⋯</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-secondary py-5">
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
        {{ $users->links() }}
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
