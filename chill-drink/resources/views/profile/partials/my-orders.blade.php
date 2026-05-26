@php
    $profileOrders = auth()->user()
        ->orders()
        ->with(['orderItems.product.category'])
        ->latest()
        ->take(15)
        ->get();

    $orderStatusLabels = [
        'pending' => ['label' => 'Chờ xử lý', 'class' => 'order-status-pending'],
        'processing' => ['label' => 'Đang xử lý', 'class' => 'order-status-processing'],
        'shipping' => ['label' => 'Đang giao', 'class' => 'order-status-shipping'],
        'completed' => ['label' => 'Hoàn tất', 'class' => 'order-status-completed'],
        'cancelled' => ['label' => 'Đã hủy', 'class' => 'order-status-cancelled'],
    ];

    $paymentLabels = [
        'cod' => 'Tiền mặt (COD)',
        'bank_transfer' => 'Chuyển khoản',
        'momo' => 'MoMo',
        'vnpay' => 'VNPay',
    ];
@endphp

<style>
    .profile-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 0.65rem;
        margin-bottom: 1.5rem;
    }

    .profile-tab {
        border: 1px solid var(--drink-border);
        border-radius: 999px;
        padding: 0.65rem 1.15rem;
        font-weight: 700;
        color: var(--drink-muted);
        text-decoration: none;
        background: #ffffff;
        transition: border-color 0.18s ease, color 0.18s ease, background 0.18s ease;
    }

    .profile-tab:hover,
    .profile-tab.active {
        border-color: var(--drink-primary);
        color: var(--drink-primary-dark);
        background: var(--drink-primary-soft);
    }

    .order-card {
        border: 1px solid var(--drink-border);
        border-radius: 20px;
        background: #ffffff;
        overflow: hidden;
        box-shadow: 0 14px 34px rgba(79, 183, 168, 0.08);
    }

    .order-card-header {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        padding: 1.1rem 1.25rem;
        background: linear-gradient(135deg, var(--drink-primary-soft), #ffffff);
        border-bottom: 1px solid var(--drink-border);
    }

    .order-status-badge {
        border-radius: 999px;
        padding: 0.35rem 0.85rem;
        font-size: 0.78rem;
        font-weight: 800;
    }

    .order-status-pending { background: #fff6db; color: #9a6b00; }
    .order-status-processing { background: #e8f4ff; color: #1d5f9c; }
    .order-status-shipping { background: #f1e9ff; color: #5b3f9e; }
    .order-status-completed { background: var(--drink-primary-soft); color: var(--drink-primary-dark); }
    .order-status-cancelled { background: #ffe8e8; color: #b42318; }

    .order-item-row {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.9rem 1.25rem;
        border-bottom: 1px solid rgba(213, 238, 232, 0.65);
    }

    .order-item-row:last-child { border-bottom: 0; }

    .order-item-thumb {
        width: 58px;
        height: 58px;
        border-radius: 14px;
        overflow: hidden;
        flex: 0 0 auto;
        background: var(--drink-primary-soft);
    }

    .order-item-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .order-card-footer {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem 1.25rem;
        background: #f9fffd;
    }

    .orders-empty {
        text-align: center;
        padding: 3rem 1.5rem;
        border: 1px dashed var(--drink-border);
        border-radius: 20px;
        background: var(--drink-primary-soft);
    }
</style>

<div id="profile-orders" class="mt-5">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <p class="text-primary fw-semibold mb-1">Đơn hàng</p>
            <h2 class="h4 fw-bold mb-0">Lịch sử mua hàng</h2>
        </div>
        <a href="{{ route('products.index') }}" class="btn btn-outline-primary">Tiếp tục mua sắm</a>
    </div>

    @forelse($profileOrders as $order)
        @php($status = $orderStatusLabels[$order->status] ?? ['label' => $order->status, 'class' => 'order-status-pending'])
        <article class="order-card mb-4">
            <div class="order-card-header">
                <div>
                    <div class="fw-bold text-primary">#{{ str_pad((string) $order->id, 5, '0', STR_PAD_LEFT) }}</div>
                    <div class="text-secondary small">{{ $order->created_at?->format('d/m/Y H:i') }}</div>
                </div>
                <span class="order-status-badge {{ $status['class'] }}">{{ $status['label'] }}</span>
            </div>

            @foreach($order->orderItems as $item)
                @php($product = $item->product)
                <div class="order-item-row">
                    <div class="order-item-thumb">
                        @if($product)
                            <x-product-image
                                :src="$product->image_url"
                                :sku="$product->sku"
                                :name="$product->name"
                                :alt="$product->name"
                                :category="$product->category?->name"
                                :width="200"
                            />
                        @else
                            <img src="{{ view()->shared('uiDefaultImage', 'https://images.unsplash.com/photo-1544145945-f90425340c7e?auto=format&fit=crop&w=200&q=85') }}" alt="Sản phẩm">
                        @endif
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold">{{ $product->name ?? 'Sản phẩm đã xóa' }}</div>
                        <div class="text-secondary small">Số lượng: {{ $item->quantity }}</div>
                    </div>
                    <div class="fw-bold text-primary">{{ number_format($item->getSubtotal(), 0, ',', '.') }}đ</div>
                </div>
            @endforeach

            <div class="order-card-footer">
                <div class="text-secondary small">
                    Thanh toán: <strong class="text-dark">{{ $paymentLabels[$order->payment_method] ?? strtoupper($order->payment_method) }}</strong>
                    @if($order->note)
                        <span class="d-block mt-1">Ghi chú: {{ $order->note }}</span>
                    @endif
                </div>
                <div class="text-end">
                    <div class="text-secondary small">Tổng thanh toán</div>
                    <div class="h5 fw-bold text-primary mb-0">{{ number_format($order->total_price, 0, ',', '.') }}đ</div>
                </div>
            </div>
        </article>
    @empty
        <div class="orders-empty">
            <div class="display-6 mb-2">🛒</div>
            <h3 class="h5 fw-bold mb-2">Bạn chưa có đơn hàng nào</h3>
            <p class="text-secondary mb-4">Khám phá menu đồ uống và đặt thử ly đầu tiên nhé.</p>
            <a href="{{ route('products.index') }}" class="btn btn-primary">Xem sản phẩm</a>
        </div>
    @endforelse
</div>
