@extends('layouts.admin')

@section('page-title', 'Dashboard')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-secondary mb-1">Tổng Người Dùng</p>
                <div class="d-flex justify-content-between align-items-center">
                    <strong class="display-6">{{ $totalUsers }}</strong>
                    <span class="badge text-bg-primary rounded-pill">Users</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-secondary mb-1">Tổng Sản Phẩm</p>
                <div class="d-flex justify-content-between align-items-center">
                    <strong class="display-6">{{ $totalProducts }}</strong>
                    <span class="badge text-bg-success rounded-pill">Products</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-secondary mb-1">Tổng Đơn Hàng</p>
                <div class="d-flex justify-content-between align-items-center">
                    <strong class="display-6">{{ $totalOrders }}</strong>
                    <span class="badge text-bg-warning rounded-pill">Orders</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-secondary mb-1">Tổng Doanh Thu</p>
                <div class="d-flex justify-content-between align-items-center">
                    <strong class="h2 mb-0">{{ number_format($totalRevenue, 0, ',', '.') }}đ</strong>
                    <span class="badge text-bg-info rounded-pill">Revenue</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h2 class="h5 fw-bold mb-0">Đơn Hàng Gần Đây</h2>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Khách Hàng</th>
                    <th>Tổng Tiền</th>
                    <th>Trạng Thái</th>
                    <th>Ngày Đặt</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentOrders as $order)
                    <tr>
                        <td>#{{ $order->id }}</td>
                        <td>{{ $order->user->name }}</td>
                        <td>{{ number_format($order->total_price, 0, ',', '.') }}đ</td>
                        <td>
                            <span class="badge
                                @if($order->status == 'completed') text-bg-success
                                @elseif($order->status == 'pending') text-bg-warning
                                @elseif($order->status == 'cancelled') text-bg-danger
                                @else text-bg-primary
                                @endif">
                                {{ ucfirst($order->status) }}
                            </span>
                        </td>
                        <td class="text-secondary">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-secondary py-4">Chưa có đơn hàng nào</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
