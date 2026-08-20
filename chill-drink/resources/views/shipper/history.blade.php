@extends('layouts.shipper')

@section('title', 'Lịch sử giao hàng')
@section('mobile-title', 'Lịch sử')
@section('mobile-subtitle', 'Các chuyến đã hoàn thành')

@section('content')
<div class="ship-page-head">
    <div>
        <h1>Lịch sử giao hàng</h1>
        <p>Xem lại những đơn đã giao và thời gian hoàn tất.</p>
    </div>
    <span class="ship-head-icon"><i class="fa-solid fa-clock-rotate-left"></i></span>
</div>

<div class="ship-order-list">
    @forelse($orders as $order)
        @php
            $normalized = \App\Support\OrderStatus::normalize((string)$order->status);
            $label = \App\Support\OrderStatus::label((string)$order->status);
            $phone = $order->customerPhone();
        @endphp
        <article class="ship-order-card">
            <div class="ship-order-top">
                <div>
                    <div class="ship-order-code">{{ $order->displayCode() }}</div>
                    <div class="ship-order-time">{{ $order->updated_at?->format('d/m/Y · H:i') }}</div>
                </div>
                <span class="ship-badge success"><i class="fa-solid fa-circle-check me-1"></i>{{ $label }}</span>
            </div>

            <div class="ship-order-customer">
                <span class="mini-avatar"><i class="fa-solid fa-user"></i></span>
                <div class="min-w-0 flex-grow-1">
                    <b>{{ $order->customerName() ?: 'Khách hàng' }}</b>
                    <span>{{ $phone ?: 'Không có số điện thoại' }}</span>
                </div>
            </div>

            <div class="ship-address"><i class="fa-solid fa-location-dot"></i><span>{{ $order->getShippingAddress() }}</span></div>

            <div class="ship-order-actions one">
                <a href="{{ route('shipper.orders.show', $order->id) }}" class="btn btn-outline-secondary"><i class="fa-solid fa-receipt me-1"></i>Xem chi tiết đơn</a>
            </div>
        </article>
    @empty
        <div class="ship-empty"><i class="fa-solid fa-clock-rotate-left"></i><b>Chưa có lịch sử giao hàng</b><p>Các chuyến giao hoàn tất sẽ được lưu tại đây.</p></div>
    @endforelse
</div>

@if($orders->hasPages())
    <div class="ship-pagination">{{ $orders->links('pagination::bootstrap-5') }}</div>
@endif
@endsection
