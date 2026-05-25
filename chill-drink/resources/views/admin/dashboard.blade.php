@extends('layouts.admin')

@section('page-title', 'Tổng quát')
@section('search-placeholder', 'Tìm báo cáo, đơn hàng...')

@section('content')
@php
    $now = now('Asia/Ho_Chi_Minh');
    $dayStart = $now->copy()->startOfDay()->format('d/m/Y H:i');
    $dayEnd = $now->copy()->endOfDay()->format('d/m/Y H:i');
    $weekStart = $now->copy()->startOfWeek()->format('d/m/Y');
    $weekEnd = $now->copy()->endOfWeek()->format('d/m/Y');
    $monthStart = $now->copy()->startOfMonth()->format('d/m/Y');
    $monthEnd = $now->copy()->endOfMonth()->format('d/m/Y');
    $yearStart = $now->copy()->startOfYear()->format('d/m/Y');
    $yearEnd = $now->copy()->endOfYear()->format('d/m/Y');
@endphp

<style>
    .revenue-toolbar {
        align-items: flex-start;
        flex-wrap: nowrap !important;
        max-width: 100%;
        overflow-x: auto;
        padding-bottom: 0;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    .revenue-toolbar::-webkit-scrollbar {
        display: none;
    }

    .revenue-toolbar .btn {
        flex: 0 0 auto;
        white-space: nowrap;
    }

    .revenue-period-label {
        color: var(--admin-muted);
        font-size: 0.76rem;
        font-weight: 800;
        text-transform: uppercase;
    }

    .revenue-period-card {
        position: relative;
        min-height: 150px;
        border-radius: 18px;
    }

    .revenue-period-card.active {
        border-color: rgba(0, 107, 95, 0.35);
        background: linear-gradient(180deg, #ffffff 0%, #f2fffb 100%);
        box-shadow: 0 18px 42px rgba(0, 107, 95, 0.12);
    }

    .revenue-period-icon {
        width: 38px;
        height: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        background: var(--admin-primary-soft);
        color: var(--admin-primary);
    }

    .revenue-filter-card {
        border-radius: 20px;
    }

    .revenue-filter-title {
        color: var(--admin-ink);
        font-weight: 800;
    }

    @media (max-width: 767.98px) {
        .revenue-toolbar {
            width: 100%;
        }
    }
</style>

<section class="d-flex flex-column flex-xl-row justify-content-between gap-3 mb-4">
    <div>
        <h2 class="h2 fw-bold mb-1">Tổng quan hệ thống</h2>
        <p class="text-secondary mb-0">Theo dõi lịch sử doanh thu theo ngày, tuần, tháng hoặc năm để đối chiếu tình hình kinh doanh.</p>
    </div>
    <div class="d-flex flex-wrap gap-2 revenue-toolbar">
        <button type="button" class="btn btn-primary"><i class="bi bi-calendar2-day me-1"></i>Ngày hôm nay</button>
        <button type="button" class="btn btn-outline-primary"><i class="bi bi-calendar-week me-1"></i>Tuần này</button>
        <button type="button" class="btn btn-outline-primary"><i class="bi bi-calendar3 me-1"></i>Tháng này</button>
        <button type="button" class="btn btn-outline-primary"><i class="bi bi-calendar4 me-1"></i>Năm nay</button>
    </div>
</section>

<section class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="admin-card revenue-period-card active p-3 h-100">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <p class="admin-kicker mb-1">Doanh thu ngày</p>
                    <div class="fw-bold">{{ $now->format('d/m/Y') }}</div>
                </div>
                <span class="revenue-period-icon"><i class="bi bi-calendar2-day"></i></span>
            </div>
            <small class="text-secondary d-block">Từ {{ $dayStart }}</small>
            <small class="text-secondary d-block">Đến {{ $dayEnd }}</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="admin-card revenue-period-card p-3 h-100">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <p class="admin-kicker mb-1">Doanh thu tuần</p>
                    <div class="fw-bold">{{ $weekStart }}</div>
                </div>
                <span class="revenue-period-icon"><i class="bi bi-calendar-week"></i></span>
            </div>
            <small class="text-secondary d-block">Từ {{ $weekStart }}</small>
            <small class="text-secondary d-block">Đến {{ $weekEnd }}</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="admin-card revenue-period-card p-3 h-100">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <p class="admin-kicker mb-1">Doanh thu tháng</p>
                    <div class="fw-bold">Tháng {{ $now->format('m/Y') }}</div>
                </div>
                <span class="revenue-period-icon"><i class="bi bi-calendar3"></i></span>
            </div>
            <small class="text-secondary d-block">Từ {{ $monthStart }}</small>
            <small class="text-secondary d-block">Đến {{ $monthEnd }}</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="admin-card revenue-period-card p-3 h-100">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <p class="admin-kicker mb-1">Doanh thu năm</p>
                    <div class="fw-bold">Năm {{ $now->format('Y') }}</div>
                </div>
                <span class="revenue-period-icon"><i class="bi bi-calendar4"></i></span>
            </div>
            <small class="text-secondary d-block">Từ {{ $yearStart }}</small>
            <small class="text-secondary d-block">Đến {{ $yearEnd }}</small>
        </div>
    </div>
</section>

<section class="admin-card revenue-filter-card p-4 mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <p class="admin-kicker mb-1">Lịch sử doanh thu</p>
            <h3 class="h4 revenue-filter-title mb-1">Chọn khoảng thời gian cần xem</h3>
            <p class="text-secondary mb-0">Dùng để xem doanh thu phát sinh trong một ngày, một tuần, một tháng, một năm hoặc khoảng tùy chọn.</p>
        </div>
        <span class="badge badge-soft-primary"><i class="bi bi-clock-history me-1"></i>Bộ lọc báo cáo</span>
    </div>
    <div class="row g-3 align-items-end">
        <div class="col-lg-3">
            <label class="admin-kicker d-block mb-2">Ngày bắt đầu</label>
            <input type="date" class="admin-input" value="{{ $now->copy()->startOfDay()->format('Y-m-d') }}">
        </div>
        <div class="col-lg-3">
            <label class="admin-kicker d-block mb-2">Ngày kết thúc</label>
            <input type="date" class="admin-input" value="{{ $now->copy()->format('Y-m-d') }}">
        </div>
        <div class="col-lg-3">
            <label class="admin-kicker d-block mb-2">Kỳ thống kê</label>
            <select class="admin-filter">
                <option>Doanh thu theo ngày</option>
                <option>Doanh thu theo tuần</option>
                <option>Doanh thu theo tháng</option>
                <option>Doanh thu theo năm</option>
            </select>
        </div>
        <div class="col-lg-3 d-flex gap-2">
            <button type="button" class="btn btn-primary flex-grow-1"><i class="bi bi-funnel me-1"></i>Xem doanh thu</button>
            <button type="button" class="btn btn-outline-primary" title="Đặt lại bộ lọc"><i class="bi bi-arrow-clockwise"></i></button>
        </div>
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
                        <td class="text-end fw-bold">{{ number_format($order->total_price ?? 0, 0, ',', '.') }}đ</td>
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
