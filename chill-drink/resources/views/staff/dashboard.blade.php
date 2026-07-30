@extends('layouts.staff')

@section('page-title', 'Tổng quát')
@section('hide-topbar-search')

@section('content')
<div class="mb-4">
    <h2 class="h3 fw-bold text-dark mb-1">Xin chào, {{ auth()->user()->name }}!</h2>
    <p class="text-secondary mb-0">
        <i class="bi bi-building me-1"></i>
        Chi nhánh: <strong>{{ auth()->user()->branch?->name ?? 'Chưa được gán chi nhánh' }}</strong>
    </p>
</div>

<!-- Thống kê nhanh -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="admin-card p-4 h-100 d-flex flex-column gap-2">
            <div class="d-flex align-items-center justify-content-between">
                <span class="text-secondary" style="font-size:0.8rem;font-weight:600;letter-spacing:.04em;">ĐƠN HÔM NAY</span>
                <span style="width:36px;height:36px;border-radius:10px;background:#e6f7f2;display:flex;align-items:center;justify-content:center;color:#00a870;"><i class="bi bi-receipt"></i></span>
            </div>
            <span class="fw-bold" style="font-size:1.8rem;color:#111827;line-height:1;">{{ $todayOrders }}</span>
            <small class="text-secondary">đơn hàng mới</small>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="admin-card p-4 h-100 d-flex flex-column gap-2">
            <div class="d-flex align-items-center justify-content-between">
                <span class="text-secondary" style="font-size:0.8rem;font-weight:600;letter-spacing:.04em;">CHỜ XỬ LÝ</span>
                <span style="width:36px;height:36px;border-radius:10px;background:#fff8e6;display:flex;align-items:center;justify-content:center;color:#d97706;"><i class="bi bi-hourglass-split"></i></span>
            </div>
            <span class="fw-bold" style="font-size:1.8rem;color:#d97706;line-height:1;">{{ $pendingOrders }}</span>
            <small class="text-secondary">cần xử lý ngay</small>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="admin-card p-4 h-100 d-flex flex-column gap-2">
            <div class="d-flex align-items-center justify-content-between">
                <span class="text-secondary" style="font-size:0.8rem;font-weight:600;letter-spacing:.04em;">DOANH THU HÔM NAY</span>
                <span style="width:36px;height:36px;border-radius:10px;background:#edf7ff;display:flex;align-items:center;justify-content:center;color:#0284c7;"><i class="bi bi-currency-dollar"></i></span>
            </div>
            <span class="fw-bold" style="font-size:1.4rem;color:#0284c7;line-height:1;">{{ number_format($todayRevenue, 0, ',', '.') }}đ</span>
            <small class="text-secondary">đã thanh toán</small>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="admin-card p-4 h-100 d-flex flex-column gap-2">
            <div class="d-flex align-items-center justify-content-between">
                <span class="text-secondary" style="font-size:0.8rem;font-weight:600;letter-spacing:.04em;">ĐƠN NHÓM MỞ</span>
                <span style="width:36px;height:36px;border-radius:10px;background:#f1f0ff;display:flex;align-items:center;justify-content:center;color:#7c3aed;"><i class="bi bi-people-fill"></i></span>
            </div>
            <span class="fw-bold" style="font-size:1.8rem;color:#7c3aed;line-height:1;">{{ $openGroups }}</span>
            <small class="text-secondary">đang mở</small>
        </div>
    </div>
</div>

<!-- Đơn hàng cần xử lý -->
<div class="admin-card mb-4">
    <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
        <div>
            <h3 class="h5 fw-bold mb-1">Đơn hàng cần xử lý</h3>
            <small class="text-secondary">Đơn đang chờ xác nhận hoặc đang chuẩn bị</small>
        </div>
        <a href="{{ route('staff.orders.index') }}" class="btn btn-sm btn-outline-primary">Xem tất cả</a>
    </div>
    @forelse($recentOrders as $order)
    @php
        $fulfillmentType = $order->fulfillment_type ?? 'delivery';
        $nextStatus = \App\Support\OrderStatus::nextStatus((string) $order->status, $fulfillmentType);
    @endphp
    <div class="p-4 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3">
            <div style="width:40px;height:40px;border-radius:10px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;color:#64748b;flex-shrink:0;">
                <i class="bi bi-bag"></i>
            </div>
            <div>
                <div class="fw-bold text-primary">{{ $order->displayCode() }}</div>
                <div class="text-secondary" style="font-size:0.82rem;">{{ $order->customerName() ?: 'Khách hàng' }}</div>
                <div class="text-secondary" style="font-size:0.75rem;">{{ $order->created_at?->format('H:i · d/m/Y') }}</div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="fw-bold text-primary">{{ number_format($order->total ?? 0, 0, ',', '.') }}đ</span>
            @if($nextStatus)
            <form action="{{ route('staff.orders.updateStatus', $order->id) }}" method="POST" class="mb-0">
                @csrf
                @method('PUT')
                <input type="hidden" name="status" value="{{ $nextStatus }}">
                <button type="submit" class="btn btn-sm btn-primary" style="background:#00a870;border-color:#00a870;">
                    {{ \App\Support\OrderStatus::label($nextStatus) }} <i class="bi bi-arrow-right ms-1"></i>
                </button>
            </form>
            @else
            <span class="badge bg-success">{{ \App\Support\OrderStatus::label($order->status) }}</span>
            @endif
        </div>
    </div>
    @empty
    <div class="p-5 text-center text-secondary">
        <i class="bi bi-check-circle" style="font-size:2rem;color:#00a870;"></i>
        <p class="mt-2 mb-0 fw-semibold">Không có đơn hàng nào cần xử lý!</p>
    </div>
    @endforelse
</div>

<!-- Liên kết nhanh -->
<div class="row g-3">
    <div class="col-md-4">
        <a href="{{ route('staff.orders.index') }}" class="admin-card p-4 d-flex align-items-center gap-3 text-decoration-none" style="color:inherit;">
            <span style="width:48px;height:48px;border-radius:12px;background:#e6f7f2;display:flex;align-items:center;justify-content:center;color:#00a870;font-size:1.4rem;flex-shrink:0;"><i class="bi bi-receipt"></i></span>
            <div>
                <div class="fw-bold">Quản lý đơn hàng</div>
                <small class="text-secondary">Xem & cập nhật trạng thái</small>
            </div>
            <i class="bi bi-chevron-right ms-auto text-secondary"></i>
        </a>
    </div>
    <div class="col-md-4">
        <a href="{{ route('staff.group-orders.index') }}" class="admin-card p-4 d-flex align-items-center gap-3 text-decoration-none" style="color:inherit;">
            <span style="width:48px;height:48px;border-radius:12px;background:#f1f0ff;display:flex;align-items:center;justify-content:center;color:#7c3aed;font-size:1.4rem;flex-shrink:0;"><i class="bi bi-people-fill"></i></span>
            <div>
                <div class="fw-bold">Đơn nhóm</div>
                <small class="text-secondary">Xem & quản lý đơn nhóm</small>
            </div>
            <i class="bi bi-chevron-right ms-auto text-secondary"></i>
        </a>
    </div>
    <div class="col-md-4">
        <a href="{{ route('staff.chat.index') }}" class="admin-card p-4 d-flex align-items-center gap-3 text-decoration-none" style="color:inherit;">
            <span style="width:48px;height:48px;border-radius:12px;background:#fff8e6;display:flex;align-items:center;justify-content:center;color:#d97706;font-size:1.4rem;flex-shrink:0;"><i class="bi bi-chat-dots"></i></span>
            <div>
                <div class="fw-bold">Chat hỗ trợ</div>
                <small class="text-secondary">Trả lời khách hàng</small>
            </div>
            <i class="bi bi-chevron-right ms-auto text-secondary"></i>
        </a>
    </div>
</div>
@endsection
