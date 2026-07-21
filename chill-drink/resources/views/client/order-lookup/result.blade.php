@extends('layouts.client')

@section('title', 'Kết quả tra cứu – ' . $order->displayCode())

@section('content')
<style>
    .lookup-page {
        min-height: calc(100vh - 88px);
        padding: 64px 0 88px;
        background: linear-gradient(180deg, #effcf8 0%, #f8fbfa 52%, #ffffff 100%);
    }

    .result-card {
        max-width: 680px;
        margin: 0 auto;
        background: #fff;
        border: 1px solid #dcebe7;
        border-radius: 24px;
        padding: 40px;
        box-shadow: 0 24px 60px rgba(14, 72, 61, 0.1);
    }

    .info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-bottom: 28px;
    }

    .info-item {
        background: #fbfefd;
        border: 1px solid #e3ece9;
        border-radius: 12px;
        padding: 16px;
    }

    .info-label {
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        color: #71807c;
        margin-bottom: 4px;
    }

    .info-value {
        font-weight: 600;
        color: #1a2e28;
    }

    .order-item-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid #f0f5f3;
    }

    .order-item-row:last-child { border-bottom: 0; }

    .item-img {
        width: 52px;
        height: 52px;
        border-radius: 10px;
        object-fit: cover;
        flex-shrink: 0;
        background: #f0f5f3;
    }

    /* Badge styles — dùng cùng hệ màu với my-orders */
    .order-status-badge {
        border-radius: 999px;
        padding: 0.35rem 0.85rem;
        font-size: 0.82rem;
        font-weight: 800;
        display: inline-block;
    }
    .order-status-pending          { background: #fff6db; color: #9a6b00; }
    .order-status-confirmed        { background: #e8f4ff; color: #1d5f9c; }
    .order-status-preparing        { background: #e8f4ff; color: #1d5f9c; }
    .order-status-ready            { background: #e6f9f4; color: #0a6b4e; }
    .order-status-shipper-picked-up{ background: #f1e9ff; color: #5b3f9e; }
    .order-status-delivering       { background: #f1e9ff; color: #5b3f9e; }
    .order-status-delivered        { background: #e6f9f4; color: #0a6b4e; }
    .order-status-completed        { background: #d1fae5; color: #065f46; }
    .order-status-cancelled        { background: #ffe8e8; color: #b42318; }

    /* Badge transition khi cập nhật */
    #status-badge {
        transition: all 0.3s ease;
    }

    #status-badge.updating {
        opacity: 0.5;
        transform: scale(0.95);
    }

    /* Indicator realtime */
    .live-indicator {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.75rem;
        color: #71807c;
    }

    .live-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #22c55e;
        animation: pulse-dot 2s infinite;
    }

    @keyframes pulse-dot {
        0%, 100% { opacity: 1; transform: scale(1); }
        50%       { opacity: 0.5; transform: scale(0.8); }
    }

    @media (max-width: 576px) {
        .result-card { padding: 24px 16px; border-radius: 18px; }
        .info-grid { grid-template-columns: 1fr; }
        .lookup-page { padding: 32px 0 64px; }
    }
</style>

<main class="lookup-page">
    <div class="container">
        <div class="result-card">
            {{-- Header --}}
            <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
                <div>
                    <p class="text-secondary small mb-1">Kết quả tra cứu</p>
                    <h1 class="h5 fw-bold mb-0">{{ $order->displayCode() }}</h1>
                </div>
                <div class="d-flex flex-column align-items-end gap-1">
                    <span id="status-badge" class="order-status-badge {{ \App\Support\OrderStatus::userBadgeStyles()[\App\Support\OrderStatus::normalize((string)$order->status)]['class'] ?? 'order-status-pending' }}">{{ $statusLabel }}</span>
                    <span class="live-indicator">
                        <span class="live-dot"></span>
                        Cập nhật tự động
                    </span>
                </div>
            </div>

            {{-- Thông tin tóm tắt --}}
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Ngày đặt</div>
                    <div class="info-value">{{ $order->created_at?->format('d/m/Y H:i') }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Hình thức</div>
                    <div class="info-value">
                        {{ $order->fulfillment_type === 'pickup' ? 'Lấy tại quán' : 'Giao hàng' }}
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Chi nhánh</div>
                    <div class="info-value">{{ $order->branch?->name ?? '—' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Thanh toán</div>
                    <div class="info-value">
                        {{ match($order->payment_method) {
                            'cod'   => 'Tiền mặt (COD)',
                            'vnpay' => 'VNPay',
                            default => strtoupper($order->payment_method),
                        } }}
                    </div>
                </div>
                @if($order->scheduled_delivery_time)
                <div class="info-item" style="grid-column: 1 / -1;">
                    <div class="info-label">Thời gian giao dự kiến</div>
                    <div class="info-value">{{ $order->scheduled_delivery_time?->format('H:i · d/m/Y') }}</div>
                </div>
                @endif
            </div>

            {{-- Danh sách sản phẩm --}}
            <h2 class="h6 fw-bold mb-3">Sản phẩm đã đặt</h2>
            <div class="mb-4">
                @foreach($order->orderItems as $item)
                    <div class="order-item-row">
                        @php $product = $item->product; @endphp
                        @if($product?->image_url)
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="item-img">
                        @else
                            <div class="item-img d-flex align-items-center justify-content-center text-secondary">
                                <i class="bi bi-cup-straw fs-4"></i>
                            </div>
                        @endif
                        <div class="flex-grow-1">
                            <div class="fw-semibold">{{ $product?->name ?? 'Sản phẩm #' . $item->product_id }}</div>
                            @if($item->size_name ?? null)
                                <div class="text-secondary small">Size: {{ $item->size_name }}</div>
                            @endif
                        </div>
                        <div class="text-end">
                            <div class="fw-semibold">x{{ $item->quantity }}</div>
                            <div class="text-secondary small">{{ number_format((int) ($item->total_price ?? $item->price * $item->quantity), 0, ',', '.') }}đ</div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Tổng tiền --}}
            <div class="border-top pt-3">
                @if(($order->shipping_fee ?? 0) > 0)
                <div class="d-flex justify-content-between text-secondary small mb-1">
                    <span>Tạm tính</span>
                    <span>{{ number_format((int) $order->subtotal, 0, ',', '.') }}đ</span>
                </div>
                <div class="d-flex justify-content-between text-secondary small mb-1">
                    <span>Phí giao hàng</span>
                    <span>{{ number_format((int) $order->shipping_fee, 0, ',', '.') }}đ</span>
                </div>
                @endif
                @if(($order->discount ?? 0) > 0)
                <div class="d-flex justify-content-between text-secondary small mb-1">
                    <span>Giảm giá</span>
                    <span class="text-success">-{{ number_format((int) $order->discount, 0, ',', '.') }}đ</span>
                </div>
                @endif
                <div class="d-flex justify-content-between fw-bold fs-5 mt-2">
                    <span>Tổng cộng</span>
                    <span class="text-primary">{{ number_format((int) $order->total, 0, ',', '.') }}đ</span>
                </div>
            </div>

            {{-- Actions --}}
            <div class="d-flex gap-2 mt-4 flex-wrap">
                <a href="{{ route('order-lookup.index') }}" class="btn btn-outline-secondary rounded-pill px-4">
                    <i class="bi bi-arrow-left me-1"></i>Tra cứu đơn khác
                </a>
                <a href="{{ route('products.index') }}" class="btn btn-primary rounded-pill px-4">
                    <i class="bi bi-cup-straw me-1"></i>Tiếp tục mua hàng
                </a>
            </div>
        </div>
    </div>
</main>

<script>
(function () {
    const ORDER_ID    = @json($order->id);
    const POLL_URL    = '{{ route('order-lookup.status', $order->id) }}';
    const FINAL_STATUSES = ['completed', 'cancelled'];

    // Map badge CSS class từ status key — dùng cùng hệ thống với my-orders
    const STATUS_CLASS_MAP = @json(collect(\App\Support\OrderStatus::userBadgeStyles())->mapWithKeys(fn ($v, $k) => [$k => $v['class']]));

    let currentStatus = @json(\App\Support\OrderStatus::normalize((string) $order->status));
    let pollTimer     = null;

    const badge = document.getElementById('status-badge');

    /** Cập nhật badge không cần reload */
    function applyStatus(statusKey, statusLabel) {
        if (statusKey === currentStatus) return;
        currentStatus = statusKey;

        badge.classList.add('updating');
        setTimeout(() => {
            badge.className = 'order-status-badge ' + (STATUS_CLASS_MAP[statusKey] ?? 'order-status-pending');
            badge.textContent = statusLabel;
            badge.classList.remove('updating');
        }, 200);

        // Dừng polling nếu đơn đã kết thúc
        if (FINAL_STATUSES.includes(statusKey)) {
            stopPolling();
            document.querySelector('.live-dot').style.background = '#94a3b8';
            document.querySelector('.live-dot').style.animation  = 'none';
        }
    }

    /** Polling mỗi 10 giây */
    function startPolling() {
        pollTimer = setInterval(async () => {
            try {
                const res  = await fetch(POLL_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                if (!res.ok) return;
                const data = await res.json();
                applyStatus(data.status, data.status_label);
            } catch (_) { /* silent fail */ }
        }, 10_000);
    }

    function stopPolling() {
        if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
    }

    // Nếu đơn chưa kết thúc → bắt đầu polling
    if (!FINAL_STATUSES.includes(currentStatus)) {
        startPolling();
    } else {
        document.querySelector('.live-dot').style.background = '#94a3b8';
        document.querySelector('.live-dot').style.animation  = 'none';
    }

    // Nếu user đã đăng nhập và Echo khả dụng → lắng nghe realtime qua WebSocket
    // (nhanh hơn polling, dừng polling khi nhận được event)
    @auth
    document.addEventListener('DOMContentLoaded', function () {
        if (!window.Echo) return;

        window.Echo.private('user.' + @json(auth()->id()))
            .listen('.order.status.updated', function (payload) {
                if (parseInt(payload.order_id) !== ORDER_ID) return;
                applyStatus(payload.status, payload.status_label);
            });
    });
    @endauth
})();
</script>
@endsection
