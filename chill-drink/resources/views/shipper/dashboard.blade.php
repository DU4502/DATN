@extends('layouts.shipper')

@section('title', 'Trang chủ shipper')
@section('mobile-title', 'Chill Drink Shipper')
@section('mobile-subtitle', 'Ca giao hôm nay')

@section('content')
@php
    $shipperStatus = $returnPlan ? 'returning' : ($shipperInfo->status ?? 'offline');
    $statusLabel = match($shipperStatus) {
        'returning' => 'Đang quay về',
        'online' => 'Sẵn sàng',
        'busy' => 'Đang bận',
        default => 'Offline',
    };
    $statusClass = match($shipperStatus) {
        'returning' => 'returning',
        'busy' => 'busy',
        'offline' => 'offline',
        default => '',
    };
    $homeBranchName = $shipperInfo->user?->branch?->name
        ?? $shipperInfo->stationBranch?->name
        ?? $shipperInfo->returningBranch?->name
        ?? 'Chi nhánh được phân công';
@endphp

<div class="ship-page-head">
    <div>
        <h1>Xin chào, {{ $shipperUser->name ?? 'Shipper' }} 👋</h1>
        <p>{{ $homeBranchName }} · {{ now()->format('d/m/Y') }}</p>
    </div>
    <div class="text-end">
        <span class="ship-status-pill {{ $statusClass }}"><span class="ship-status-dot"></span>{{ $statusLabel }}</span>
        @if(!$returnPlan && in_array($shipperInfo->status, ['online', 'offline'], true))
            <form action="{{ route('shipper.status.update') }}" method="POST" class="mt-2">
                @csrf
                <input type="hidden" name="status" value="{{ $shipperInfo->status === 'online' ? 'offline' : 'online' }}">
                <button type="submit" class="btn btn-sm {{ $shipperInfo->status === 'online' ? 'btn-outline-secondary' : 'btn-success' }}">
                    <i class="fa-solid {{ $shipperInfo->status === 'online' ? 'fa-circle-pause' : 'fa-play' }} me-1"></i>
                    {{ $shipperInfo->status === 'online' ? 'Ngừng nhận đơn' : 'Bắt đầu nhận đơn' }}
                </button>
            </form>
        @endif
    </div>
</div>

<div class="ship-stat-grid">
    <div class="ship-stat-card">
        <div class="ship-stat-top"><span class="ship-stat-label">Đơn hôm nay</span><span class="ship-stat-icon blue"><i class="fa-solid fa-receipt"></i></span></div>
        <div class="ship-stat-value">{{ (int) ($todayOrders ?? 0) }}</div>
        <div class="ship-stat-note">Tổng nhiệm vụ trong ngày</div>
    </div>
    <div class="ship-stat-card">
        <div class="ship-stat-top"><span class="ship-stat-label">Đang xử lý</span><span class="ship-stat-icon orange"><i class="fa-solid fa-motorcycle"></i></span></div>
        <div class="ship-stat-value">{{ (int) ($shippingOrders ?? 0) }}</div>
        <div class="ship-stat-note">Đơn đang trong chuyến</div>
    </div>
    <div class="ship-stat-card">
        <div class="ship-stat-top"><span class="ship-stat-label">Đã giao</span><span class="ship-stat-icon"><i class="fa-solid fa-circle-check"></i></span></div>
        <div class="ship-stat-value">{{ (int) ($completedOrders ?? 0) }}</div>
        <div class="ship-stat-note">Giao thành công</div>
    </div>
    <div class="ship-stat-card">
        <div class="ship-stat-top"><span class="ship-stat-label">Phí giao hàng</span><span class="ship-stat-icon"><i class="fa-solid fa-wallet"></i></span></div>
        <div class="ship-stat-value">{{ number_format((int) ($income ?? 0)) }}đ</div>
        <div class="ship-stat-note">Tổng phí từ đơn giao thành công</div>
    </div>
</div>

<div class="ship-section-head"><h2>Truy cập nhanh</h2></div>
<div class="ship-quick-grid">
    <a href="{{ route('shipper.orders') }}" class="ship-quick-link"><i class="fa-solid fa-box"></i><span>Đơn hàng</span></a>
    <a href="{{ route('shipper.map') }}" class="ship-quick-link"><i class="fa-solid fa-location-arrow"></i><span>Dẫn đường</span></a>
    <a href="{{ route('shipper.history') }}" class="ship-quick-link"><i class="fa-solid fa-clock-rotate-left"></i><span>Lịch sử</span></a>
    <a href="{{ route('shipper.chats.index') }}" class="ship-quick-link"><i class="fa-solid fa-comments"></i><span>Chat</span></a>
</div>

@if(!empty($returnPlan))
    <div class="ship-info-strip mt-3">
        <div class="strip-icon"><i class="fa-solid fa-route"></i></div>
        <div class="flex-grow-1">
            <b>Đang quay về {{ $returnPlan['branch']->name }}</b>
            <p>Tới đúng home branch, GPS sẽ tự chuyển bạn sang trạng thái Sẵn sàng.</p>
            <a href="{{ route('shipper.returning') }}" class="btn btn-sm btn-success mt-2 w-100"><i class="fa-solid fa-location-arrow me-1"></i>Mở dẫn đường về chi nhánh</a>
        </div>
    </div>
@endif

@if(!empty($bundleTrip))
    <div class="ship-info-strip mt-3">
        <div class="strip-icon"><i class="fa-solid fa-layer-group"></i></div>
        <div>
            <b>{{ $bundleLabel ?? 'Chuyến ghép thuận đường' }}</b>
            <p>Lấy hết quán gần nhất trước, sau đó mới giao khách gần nhất. Tối đa 3 đơn · 20 cốc. Các đơn cùng một chi nhánh được gộp thành 1 điểm đến.</p>
        </div>
    </div>
@endif

<div class="ship-section-head">
    <h2>Nhiệm vụ hiện tại</h2>
    <a href="{{ route('shipper.orders') }}">Xem tất cả</a>
</div>

<div class="ship-order-list">
    @forelse($orders->take(4) as $order)
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
                    <div class="ship-order-time">Cập nhật {{ $order->updated_at?->diffForHumans() }}</div>
                </div>
                <span class="ship-badge {{ $badgeClass }}">{{ $isNew ? 'Mới giao' : $label }}</span>
            </div>

            <div class="ship-order-customer">
                <span class="mini-avatar"><i class="fa-solid fa-user"></i></span>
                <div class="min-w-0 flex-grow-1">
                    <b>{{ $order->customerName() ?: 'Khách hàng' }}</b>
                    <span>{{ $phone ?: 'Chưa có số điện thoại' }}</span>
                </div>
                @if($phone)
                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="btn btn-light btn-sm" aria-label="Gọi khách"><i class="fa-solid fa-phone"></i></a>
                @endif
            </div>

            <div class="ship-address"><i class="fa-solid fa-location-dot"></i><span>{{ $order->getShippingAddress() }}</span></div>

            <div class="ship-order-actions">
                <a href="{{ route('shipper.orders.show', $order->id) }}" class="btn btn-outline-secondary"><i class="fa-solid fa-receipt me-1"></i>Chi tiết</a>
                <a href="{{ route('shipper.map', ['id'=>$order->id]) }}" class="btn btn-success"><i class="fa-solid fa-location-arrow me-1"></i>Dẫn đường</a>
            </div>
        </article>
    @empty
        <div class="ship-empty"><i class="fa-solid fa-mug-hot"></i><b>Chưa có nhiệm vụ cần giao</b><p>Khi hệ thống điều phối đơn mới, bạn sẽ nhận thông báo ngay trên app.</p></div>
    @endforelse
</div>

@if(($income ?? 0) > 0)
    <div class="ship-section-head"><h2>Thu nhập giao hàng</h2></div>
    <div class="ship-profile-card">
        <div class="d-flex align-items-center justify-content-between gap-3">
            <div><div class="small text-muted">Tổng phí giao đã ghi nhận</div><div class="fw-bold fs-4 mt-1" style="color:var(--ship-green-dark)">{{ number_format((int)$income) }}đ</div></div>
            <span class="ship-stat-icon"><i class="fa-solid fa-wallet"></i></span>
        </div>
    </div>
@endif
@endsection
