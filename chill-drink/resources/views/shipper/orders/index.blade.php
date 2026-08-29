@extends('layouts.shipper')

@section('title', 'Đơn hàng')
@section('mobile-title', 'Đơn hàng')
@section('mobile-subtitle', 'Nhiệm vụ được hệ thống điều phối')

@section('content')
<div class="ship-page-head">
    <div>
        <h1>Đơn hàng của tôi</h1>
        <p>Chỉ hiển thị những nhiệm vụ đã được hệ thống giao cho bạn.</p>
    </div>
    <span class="ship-head-icon"><i class="fa-solid fa-box"></i></span>
</div>

<div class="ship-stat-grid mb-3">
    <div class="ship-stat-card">
        <div class="ship-stat-top"><span class="ship-stat-label">Tổng hôm nay</span><span class="ship-stat-icon blue"><i class="fa-solid fa-receipt"></i></span></div>
        <div class="ship-stat-value">{{ (int) ($todayOrders ?? 0) }}</div>
        <div class="ship-stat-note">Đơn đã được giao cho bạn</div>
    </div>
    <div class="ship-stat-card">
        <div class="ship-stat-top"><span class="ship-stat-label">Đang xử lý</span><span class="ship-stat-icon orange"><i class="fa-solid fa-motorcycle"></i></span></div>
        <div class="ship-stat-value">{{ (int) ($activeOrders ?? 0) }}</div>
        <div class="ship-stat-note">Đơn còn trong chuyến</div>
    </div>
</div>

@if(!empty($bundleTrip))
    <div class="ship-info-strip mb-3">
        <div class="strip-icon"><i class="fa-solid fa-layer-group"></i></div>
        <div>
            <b>{{ $bundleLabel ?? 'Chuyến ghép thuận đường' }}</b>
            <p>Lấy hết các quán trước, cùng chi nhánh chỉ tính một điểm. Sau đó giao từng khách theo thứ tự tuyến; giao xong khách hiện tại hệ thống mới mở khách tiếp theo.</p>
        </div>
    </div>
@endif

<div class="ship-order-list">
    @forelse($orders as $order)
        @php
            $normalized = \App\Support\OrderStatus::normalize((string)$order->status);
            $label = \App\Support\OrderStatus::label((string)$order->status);
            $isNew = false;
            $badgeClass = match($normalized) {
                'confirmed','preparing' => 'info',
                'ready_for_delivery','shipper_picked_up','delivering' => 'warn',
                'cancelled' => 'danger',
                default => '',
            };
            $phone = $order->customerPhone();
        @endphp
        <article class="ship-order-card {{ $isNew ? 'is-new' : '' }}">
            <div class="ship-order-top">
                <div>
                    <div class="ship-order-code">{{ $order->displayCode() }}</div>
                    <div class="ship-order-time">{{ $order->created_at?->format('d/m/Y · H:i') }}</div>
                </div>
                <span class="ship-badge {{ $badgeClass }}">{{ $isNew ? 'Chờ xác nhận' : $label }}</span>
            </div>

            <div class="ship-order-customer">
                <span class="mini-avatar"><i class="fa-solid fa-user"></i></span>
                <div class="min-w-0 flex-grow-1">
                    <b>{{ $order->customerName() ?: 'Khách hàng' }}</b>
                    <span>{{ $phone ?: 'Chưa có số điện thoại' }}</span>
                </div>
                @if($phone)
                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="btn btn-light btn-sm"><i class="fa-solid fa-phone"></i></a>
                @endif
            </div>

            <div class="ship-address"><i class="fa-solid fa-location-dot"></i><span>{{ $order->getShippingAddress() }}</span></div>

            <div class="ship-order-meta">
                <span class="ship-meta-chip"><i class="fa-solid fa-store me-1"></i>{{ $order->branch?->name ?: 'Chi nhánh' }}</span>
                <span class="ship-meta-chip"><i class="fa-solid fa-clock me-1"></i>{{ $order->updated_at?->diffForHumans() }}</span>
                <span class="ship-meta-chip"><i class="fa-solid fa-coins me-1"></i>Tổng {{ number_format((int) ($order->total ?? $order->total_price ?? 0)) }}đ</span>
                @if(strtolower((string) $order->payment_method) === 'cod' && strtolower((string) $order->payment_status) !== 'paid')
                    <span class="ship-meta-chip" style="background:#fff2e7;color:#b75e13"><i class="fa-solid fa-hand-holding-dollar me-1"></i>COD cần thu</span>
                @endif
            </div>

            <div class="ship-order-actions">
                <a href="{{ route('shipper.orders.show', $order->id) }}" class="btn btn-outline-secondary"><i class="fa-solid fa-receipt me-1"></i>Chi tiết</a>
                <a href="{{ route('shipper.map', ['id'=>$order->id]) }}" class="btn btn-success"><i class="fa-solid fa-location-arrow me-1"></i>Dẫn đường</a>
            </div>
        </article>
    @empty
        <div class="ship-empty"><i class="fa-solid fa-box-open"></i><b>Chưa có nhiệm vụ hiện tại</b><p>Đơn đã giao nằm trong Lịch sử. Khi có nhiệm vụ phù hợp hệ thống sẽ tự điều phối và báo cho bạn.</p></div>
    @endforelse
</div>

@if($orders->hasPages())
    <div class="ship-pagination">{{ $orders->links('pagination::bootstrap-5') }}</div>
@endif
@endsection
