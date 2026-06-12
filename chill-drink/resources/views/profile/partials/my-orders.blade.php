@php
$profileOrders = $profileOrders ?? collect();
$orderStatusLabels = $orderStatusLabels ?? [
'pending' => ['label' => 'Chờ xử lý', 'class' => 'order-status-pending'],
'processing' => ['label' => 'Đang xử lý', 'class' => 'order-status-processing'],
'shipping' => ['label' => 'Đang giao', 'class' => 'order-status-shipping'],
'completed' => ['label' => 'Hoàn tất', 'class' => 'order-status-completed'],
'cancelled' => ['label' => 'Đã hủy', 'class' => 'order-status-cancelled'],
];
$paymentLabels = $paymentLabels ?? [
'cod' => 'Tiền mặt (COD)',
'bank_transfer' => 'Chuyển khoản',
'momo' => 'MoMo',
'vnpay' => 'VNPay',
'card' => 'Thẻ',
'wallet' => 'Ví điện tử',
];
@endphp

<style>
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

    .order-status-pending {
        background: #fff6db;
        color: #9a6b00;
    }

    .order-status-processing {
        background: #e8f4ff;
        color: #1d5f9c;
    }

    .order-status-shipping {
        background: #f1e9ff;
        color: #5b3f9e;
    }

    .order-status-completed {
        background: var(--drink-primary-soft);
        color: var(--drink-primary-dark);
    }

    .order-status-cancelled {
        background: #ffe8e8;
        color: #b42318;
    }

    .order-item-row {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.9rem 1.25rem;
        border-bottom: 1px solid rgba(213, 238, 232, 0.65);
    }

    .order-item-row:last-child {
        border-bottom: 0;
    }

    .order-review-collapse {
        border-top: 1px solid rgba(213, 238, 232, 0.9);
        background: #fbfffe;
    }

    .order-review-toggle {
        appearance: none;
        -webkit-appearance: none;
        border: 1px solid var(--drink-primary, var(--c-primary)) !important;
        background-color: var(--drink-primary, var(--c-primary)) !important;
        color: #ffffff !important;
        border-radius: 999px;
        padding: 0.42rem 0.85rem;
        font-size: 0.85rem;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        transition: transform 0.18s ease, background 0.18s ease, box-shadow 0.18s ease;
        box-shadow: 0 10px 18px rgba(0, 107, 95, 0.14);
    }

    .order-review-toggle .label {
        color: #ffffff !important;
    }

    .order-review-toggle:hover {
        background-color: var(--drink-primary-dark, var(--c-primary-dark)) !important;
        border-color: var(--drink-primary-dark, var(--c-primary-dark)) !important;
        transform: translateY(-1px);
    }

    .order-review-toggle i {
        transition: transform 0.2s ease;
    }

    .order-review-toggle[aria-expanded="true"] i {
        transform: rotate(180deg);
    }

    .order-review-panel {
        border-radius: 16px;
        background: #ffffff;
        border: 1px solid rgba(213, 238, 232, 0.9);
    }

    .order-review-stars {
        display: inline-flex;
        flex-direction: row-reverse;
        gap: 0.35rem;
    }

    .order-review-stars input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .order-review-stars label {
        cursor: pointer;
        color: #cbd5e1;
        font-size: 1.3rem;
        transition: color 0.16s ease, transform 0.16s ease;
    }

    .order-review-stars label:hover,
    .order-review-stars label:hover ~ label,
    .order-review-stars input:checked ~ label {
        color: #f59e0b;
        transform: translateY(-1px);
    }

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

<div id="profile-orders" class="mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0">Lịch sử mua hàng</h2>
        </div>
        <a href="{{ route('products.index') }}" class="btn btn-outline-primary">Tiếp tục mua sắm</a>
    </div>

    @forelse($profileOrders as $order)
    <?php $statusKey = $order->status_display_key ?? $order->status; ?>
    <?php $status = $orderStatusLabels[$statusKey] ?? ['label' => $order->status, 'class' => 'order-status-pending']; ?>
    <article class="order-card mb-4">
        <div class="order-card-header">
            <div>
                <div class="fw-bold text-primary">#{{ str_pad((string) $order->id, 5, '0', STR_PAD_LEFT) }}</div>
                <div class="text-secondary small">{{ $order->created_at?->format('d/m/Y H:i') }}</div>
            </div>
            <span class="order-status-badge {{ $status['class'] }}">{{ $status['label'] }}</span>
        </div>

        @php
        $reviewedProducts = $order->reviewed_products ?? [];
        $groupedItems = $order->orderItems->groupBy(function ($item) {
        return $item->product?->id ? 'product-' . $item->product->id : 'item-' . $item->id;
        });
        @endphp

        @foreach($groupedItems as $group)
        @php
        $item = $group->first();
        $product = $item->product;
        $totalQuantity = $group->sum('quantity');
        $totalSubtotal = $group->sum(fn($subItem) => $subItem->getSubtotal());
        $hasReviewedForThisItem = $product ? isset($reviewedProducts[$product->id]) : false;
        $reviewPanelId = $product ? 'order-review-'.$order->id.'-'.$product->id : null;
        @endphp
        <div class="order-item-row">
            <div class="order-item-thumb">
                @if($product)
                <a href="{{ route('products.show', $product->slug) }}" class="d-block h-100">
                    <x-product-image
                        :src="$product->image_url"
                        :sku="$product->sku"
                        :name="$product->name"
                        :alt="$product->name"
                        :category="$product->category?->name"
                        :width="200" />
                </a>
                @else
                <img src="{{ view()->shared('uiDefaultImage', 'https://images.unsplash.com/photo-1544145945-f90425340c7e?auto=format&fit=crop&w=200&q=85') }}" alt="Sản phẩm">
                @endif
            </div>
            <div class="flex-grow-1">
                <div class="fw-bold">
                    @if($product)
                    <a href="{{ route('products.show', $product->slug) }}" class="text-decoration-none text-dark">{{ $product->name }}</a>
                    @else
                    {{ 'Sản phẩm đã xóa' }}
                    @endif
                </div>
                <div class="text-secondary small">Số lượng: {{ $totalQuantity }}</div>
            </div>
            <div class="d-flex flex-column align-items-end">
                <div class="fw-bold text-primary">{{ number_format($totalSubtotal, 0, ',', '.') }}đ</div>

                @if(($statusKey ?? '') === 'completed' && $product)
                @if(auth()->check() && ! $hasReviewedForThisItem)
                <button
                    type="button"
                    class="order-review-toggle mt-2"
                    data-review-toggle
                    data-review-target="{{ $reviewPanelId }}"
                    aria-expanded="false"
                    aria-controls="{{ $reviewPanelId }}"
                >
                    <span class="label">Đánh giá</span>
                    <i class="bi bi-chevron-down"></i>
                </button>
                @else
                <span class="badge bg-light text-secondary mt-2">Đã đánh giá</span>
                @endif
                @endif
            </div>
        </div>

        @if(($statusKey ?? '') === 'completed' && $product && auth()->check() && ! $hasReviewedForThisItem)
        <div class="order-review-collapse d-none" id="{{ $reviewPanelId }}" data-review-panel>
            <div class="p-3 p-md-4">
                <div class="order-review-panel p-3 p-md-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                        <div>
                            <h3 class="h6 fw-bold mb-1">Viết đánh giá cho {{ $product->name }}</h3>
                            <p class="text-secondary small mb-0">Mỗi đơn hoàn tất cho phép gửi một đánh giá cho sản phẩm này.</p>
                        </div>
                        <span class="badge text-bg-light border">Từ lịch sử đơn hàng</span>
                    </div>

                    <form method="POST" action="{{ route('products.reviews.store', $product) }}" data-review-form>
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Chấm sao</label>
                            <div class="order-review-stars">
                                @for($star = 5; $star >= 1; $star--)
                                    <input type="radio" name="rating" id="order-review-{{ $order->id }}-{{ $product->id }}-star-{{ $star }}" value="{{ $star }}">
                                    <label for="order-review-{{ $order->id }}-{{ $product->id }}-star-{{ $star }}">
                                        <i class="bi bi-star-fill"></i>
                                    </label>
                                @endfor
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="order-review-comment-{{ $order->id }}-{{ $product->id }}" class="form-label fw-semibold">Nhận xét</label>
                            <textarea
                                id="order-review-comment-{{ $order->id }}-{{ $product->id }}"
                                name="comment"
                                rows="3"
                                class="form-control"
                                placeholder="Chia sẻ cảm nhận của bạn về hương vị, độ ngọt, và chất lượng..."
                            ></textarea>
                        </div>

                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                            <div class="text-secondary small">Sau khi gửi, đánh giá sẽ hiển thị trong lịch sử mua hàng và trang sản phẩm.</div>
                            <button type="submit" class="btn btn-primary rounded-pill px-4">Gửi đánh giá</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endif
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
                <div class="h5 fw-bold text-primary mb-0">{{ number_format((int) ($order->display_total ?? $order->total ?? 0), 0, ',', '.') }}đ</div>
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-review-toggle]').forEach(function (button) {
            const targetId = button.dataset.reviewTarget;
            const panel = targetId ? document.getElementById(targetId) : null;

            if (!panel) {
                return;
            }

            button.addEventListener('click', function () {
                const isHidden = panel.classList.contains('d-none');
                panel.classList.toggle('d-none', !isHidden);
                button.setAttribute('aria-expanded', String(isHidden));
            });
        });
    });
</script>
