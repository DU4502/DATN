@extends('layouts.admin')

@section('page-title', 'Tổng quát')
@section('search-placeholder', 'Tìm báo cáo, đơn hàng...')

@section('content')
<section class="mb-4">
    <h2 class="h2 fw-bold mb-1">Tổng quan hệ thống</h2>
    <p class="text-secondary mb-0">Theo dõi doanh thu, đơn hàng, khách hàng và sản phẩm theo ngày, tuần và tháng.</p>
</section>

<section class="admin-card p-4 mb-4">
    <div class="d-flex flex-column flex-xl-row justify-content-between gap-3 mb-4">
        <div>
            <h3 class="h4 fw-bold mb-1">Lịch sử doanh thu</h3>
            <p class="text-secondary mb-0">Số liệu được tách theo ngày, tuần và tháng để xem nhanh tình hình bán hàng.</p>
        </div>
        <div class="admin-period-tabs" role="list" aria-label="Mốc thời gian doanh thu">
            @foreach($periodStats as $period)
                <span class="admin-period-pill {{ $loop->first ? 'active' : '' }}">
                    <i class="bi {{ $period['icon'] }}"></i>
                    {{ $period['label'] }}
                </span>
            @endforeach
        </div>
    </div>
    <div class="row g-3">
        @foreach($periodStats as $period)
            <div class="col-md-6 col-xl-4">
                <div class="admin-period-card">
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                        <span class="admin-icon-dot"><i class="bi {{ $period['icon'] }}"></i></span>
                        <span class="badge badge-soft-muted">{{ $period['orders'] }} đơn</span>
                    </div>
                    <p class="admin-kicker mb-1">{{ $period['label'] }}</p>
                    <p class="admin-value mb-1">{{ number_format($period['revenue'], 0, ',', '.') }}đ</p>
                    <small class="text-secondary">{{ $period['range'] }}</small>
                </div>
            </div>
        @endforeach
    </div>
</section>

<section class="row g-4 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="admin-card admin-metric">
            <div class="d-flex justify-content-between align-items-start">
                <span class="admin-icon-dot"><i class="bi bi-cash-stack"></i></span>
                <span class="badge badge-soft-muted">Thực tế</span>
            </div>
            <div class="mt-4">
                <p class="admin-kicker mb-1">Tổng doanh thu</p>
                <p class="admin-value mb-0">{{ number_format($totalRevenue, 0, ',', '.') }}đ</p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="admin-card admin-metric">
            <div class="d-flex justify-content-between align-items-start">
                <span class="admin-icon-dot"><i class="bi bi-receipt"></i></span>
                <span class="badge badge-soft-muted">Thực tế</span>
            </div>
            <div class="mt-4">
                <p class="admin-kicker mb-1">Tổng đơn hàng</p>
                <p class="admin-value mb-0">{{ $totalOrders }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="admin-card admin-metric">
            <div class="d-flex justify-content-between align-items-start">
                <span class="admin-icon-dot"><i class="bi bi-people"></i></span>
                <span class="badge badge-soft-muted">Khách</span>
            </div>
            <div class="mt-4">
                <p class="admin-kicker mb-1">Tổng khách hàng</p>
                <p class="admin-value mb-0">{{ $totalUsers }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="admin-card admin-metric">
            <div class="d-flex justify-content-between align-items-start">
                <span class="admin-icon-dot"><i class="bi bi-cup-hot"></i></span>
                <span class="badge badge-soft-muted">Menu</span>
            </div>
            <div class="mt-4">
                <p class="admin-kicker mb-1">Tổng sản phẩm</p>
                <p class="admin-value mb-0">{{ $totalProducts }}</p>
            </div>
        </div>
    </div>
</section>

<section class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="admin-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="h4 fw-bold mb-0">Biểu đồ theo thời gian</h3>
                <span class="badge badge-soft-muted">Chưa kết nối dữ liệu</span>
            </div>
            <div class="admin-empty-state" style="min-height: 260px;">
                <span class="admin-icon-dot mx-auto mb-3" style="width: 60px; height: 60px;"><i class="bi bi-bar-chart"></i></span>
                <div class="fw-bold text-dark mb-1">Chưa có dữ liệu biểu đồ thật</div>
                <p class="mb-0">Khi backend có thống kê theo ngày, tuần, tháng hoặc năm, biểu đồ sẽ hiển thị tại đây.</p>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="admin-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="h4 fw-bold mb-0">Món bán chạy</h3>
                <a href="{{ route('admin.products.index') }}" class="text-primary fw-bold text-decoration-none">Xem</a>
            </div>
            <div class="text-center py-4 px-3 rounded-4" style="background: var(--admin-soft-2);">
                <span class="admin-icon-dot mx-auto mb-3" style="width: 56px; height: 56px;"><i class="bi bi-cup-straw"></i></span>
                <div class="fw-bold mb-1">Chưa có dữ liệu bán chạy</div>
                <p class="text-secondary mb-0">Khi có dữ liệu đơn hàng thật, hệ thống mới nên hiển thị món bán chạy.</p>
            </div>
            <div class="mt-3">
                <div class="d-flex align-items-center gap-3 p-2 rounded-4" style="background: #fff;">
                    <span class="admin-icon-dot" style="width: 42px; height: 42px;"><i class="bi bi-info-lg"></i></span>
                    <div>
                        <div class="fw-bold">Không dùng số liệu mẫu</div>
                        <small class="text-secondary">Tránh hiển thị sai tình hình kinh doanh.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="admin-card">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 p-4 border-bottom">
        <div>
            <h3 class="h4 fw-bold mb-1">Đơn hàng gần đây</h3>
            <p class="text-secondary mb-0">Theo dõi nhanh các đơn mới nhất trong hệ thống.</p>
        </div>
        <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-primary">Xem tất cả</a>
    </div>
    <div class="table-responsive">
        <table class="table admin-table align-middle">
            <thead>
                <tr>
                    <th>Mã đơn</th>
                    <th>Khách hàng</th>
                    <th>Trạng thái</th>
                    <th class="text-end">Tổng tiền</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentOrders as $order)
                    <tr>
                        <td class="fw-bold text-primary">#{{ $order->id }}</td>
                        <td>{{ $order->user->name ?? 'Khách hàng' }}</td>
                        <td><span class="badge badge-soft-primary">{{ $order->status ?? 'Đang xử lý' }}</span></td>
                        <td class="text-end fw-bold">{{ number_format($order->total_price ?? $order->total ?? 0, 0, ',', '.') }}đ</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-secondary py-5">Chưa có đơn hàng nào</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
