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

    /* ─── Order Detail Toggle ─── */
    .order-detail-toggle {
        appearance: none;
        -webkit-appearance: none;
        border: 1.5px solid var(--c-border, #E5E7EB) !important;
        background-color: var(--c-surface, #fff) !important;
        color: var(--c-primary, #0D9373) !important;
        border-radius: 999px;
        padding: 0.4rem 0.9rem;
        font-size: 0.82rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .order-detail-toggle:hover {
        border-color: var(--c-primary, #0D9373) !important;
        background-color: var(--c-primary-light, #E6F7F2) !important;
        color: var(--c-primary-dark, #067A5F) !important;
    }

    .order-detail-toggle .detail-chevron {
        transition: transform 0.28s cubic-bezier(0.4, 0, 0.2, 1);
        font-size: 0.75rem;
        line-height: 1;
    }

    .order-detail-toggle[aria-expanded="true"] .detail-chevron {
        transform: rotate(180deg);
    }

    .order-detail-toggle[aria-expanded="true"] {
        border-color: var(--c-primary, #0D9373) !important;
        background-color: var(--c-primary-light, #E6F7F2) !important;
    }

    /* ─── Order Detail Panel ─── */
    .order-detail-collapse {
        overflow: hidden;
        max-height: 0;
        transition: max-height 0.36s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.26s ease;
        opacity: 0;
    }

    .order-detail-collapse.is-open {
        max-height: 2000px;
        opacity: 1;
    }

    .order-detail-body {
        padding: 1rem 1.25rem 1.25rem;
        background: #f9fafb;
        border-top: 1px solid #e5e7eb;
    }

    /* Section cards */
    .od-section {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 0.75rem;
    }

    .od-section:last-child { margin-bottom: 0; }

    .od-section-title {
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: var(--c-muted, #6B7280);
        padding: 0.6rem 0.9rem 0.45rem;
        border-bottom: 1px solid #f3f4f6;
        background: #fafafa;
    }

    .od-section-body { padding: 0.75rem 0.9rem; }

    /* Info rows (delivery / payment / note) */
    .od-info-row {
        display: flex;
        align-items: flex-start;
        gap: 0.55rem;
        font-size: 0.83rem;
        color: var(--c-ink, #111827);
        padding: 0.3rem 0;
    }

    .od-info-row + .od-info-row {
        border-top: 1px solid #f3f4f6;
        margin-top: 0.3rem;
        padding-top: 0.5rem;
    }

    .od-info-icon {
        color: var(--c-primary, #0D9373);
        font-size: 0.82rem;
        flex: 0 0 auto;
        margin-top: 0.12rem;
    }

    .od-info-label {
        font-size: 0.72rem;
        font-weight: 600;
        color: var(--c-muted, #6B7280);
        margin-bottom: 0.1rem;
    }

    .od-info-value {
        font-weight: 500;
        line-height: 1.4;
    }

    /* Product rows */
    .od-product-row {
        display: flex;
        align-items: center;
        gap: 0.7rem;
        padding: 0.55rem 0;
    }

    .od-product-row + .od-product-row {
        border-top: 1px solid #f3f4f6;
    }

    .od-product-thumb {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        overflow: hidden;
        flex: 0 0 auto;
        background: #E6F7F2;
    }

    .od-product-thumb img { width: 100%; height: 100%; object-fit: cover; }

    .od-product-info { flex: 1; min-width: 0; }

    .od-product-name {
        font-size: 0.82rem;
        font-weight: 600;
        color: var(--c-ink, #111827);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .od-product-qty {
        font-size: 0.75rem;
        color: var(--c-muted, #6B7280);
        margin-top: 0.05rem;
    }

    .od-product-subtotal {
        font-size: 0.83rem;
        font-weight: 700;
        color: var(--c-primary, #0D9373);
        white-space: nowrap;
        flex: 0 0 auto;
    }

    /* Summary box */
    .od-summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.82rem;
        color: var(--c-muted, #6B7280);
        padding: 0.28rem 0;
    }

    .od-summary-row + .od-summary-row { border-top: 1px solid #f3f4f6; }

    /* Two-col layout on md+ */
    .od-layout {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }

    @media (min-width: 768px) {
        .od-layout {
            grid-template-columns: 1fr auto;
            align-items: start;
        }
        .od-summary-col { min-width: 190px; max-width: 220px; }
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
    <?php $detailPanelId = 'order-detail-' . $order->id; ?>
    <article class="order-card mb-4">
        <div class="order-card-header">
            <div>
                <div class="fw-bold text-primary">#{{ str_pad((string) $order->id, 5, '0', STR_PAD_LEFT) }}</div>
                <div class="text-secondary small">{{ $order->created_at?->format('d/m/Y H:i') }}</div>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="order-status-badge {{ $status['class'] }}">{{ $status['label'] }}</span>
                <button
                    type="button"
                    class="order-detail-toggle"
                    data-detail-toggle
                    data-detail-target="{{ $detailPanelId }}"
                    aria-expanded="false"
                    aria-controls="{{ $detailPanelId }}"
                >
                    <i class="bi bi-layout-text-sidebar-reverse" style="font-size:0.82rem;"></i>
                    <span class="detail-label">Xem chi tiết</span>
                    <i class="bi bi-chevron-down detail-chevron"></i>
                </button>
            </div>
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
                            <label class="form-label fw-semibold">Đánh giá sao</label>
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

        <div class="order-detail-collapse" id="{{ $detailPanelId }}" data-detail-panel>
            <div class="order-detail-body">
                @php
                    $addrParts = array_filter([
                        $order->user->address ?? null,
                        $order->user->area ?? null,
                    ]);
                    $addrText = implode(', ', $addrParts) ?: null;
                    $subtotal    = (int)($order->subtotal ?? $order->orderItems->sum(fn($i) => (int)$i->getSubtotal()));
                    $shippingFee = (int)($order->shipping_fee ?? 0);
                    $discount    = (int)($order->discount ?? 0);
                @endphp

                <div class="od-layout">

                    {{-- Left col: delivery info + products --}}
                    <div>

                        {{-- SECTION 1 — Delivery Information --}}
                        <div class="od-section">
                            <div class="od-section-title">Thông tin giao hàng</div>
                            <div class="od-section-body">

                                {{-- Delivery address --}}
                                @if($addrText)
                                <div class="od-info-row">
                                    <i class="bi bi-geo-alt-fill od-info-icon"></i>
                                    <div>
                                        <div class="od-info-label">Địa chỉ</div>
                                        <div class="od-info-value">{{ $addrText }}</div>
                                    </div>
                                </div>
                                @endif

                                {{-- Payment method --}}
                                <div class="od-info-row">
                                    <i class="bi bi-credit-card od-info-icon"></i>
                                    <div>
                                        <div class="od-info-label">Thanh toán</div>
                                        <div class="od-info-value">{{ $paymentLabels[$order->payment_method] ?? strtoupper($order->payment_method) }}</div>
                                    </div>
                                </div>

                                {{-- Note (only if present) --}}
                                @if($order->note)
                                <div class="od-info-row">
                                    <i class="bi bi-chat-left-text od-info-icon"></i>
                                    <div>
                                        <div class="od-info-label">Ghi chú</div>
                                        <div class="od-info-value">{{ $order->note }}</div>
                                    </div>
                                </div>
                                @endif

                            </div>
                        </div>

                        {{-- SECTION 2 — Ordered Products --}}
                        <div class="od-section">
                            <div class="od-section-title">Sản phẩm đã đặt</div>
                            <div class="od-section-body">
                                @foreach($order->orderItems as $detailItem)
                                @php
                                    $dp     = $detailItem->product;
                                    $dpQty  = $detailItem->quantity;
                                    $dpUnit = (int)($detailItem->unit_price ?? 0);
                                    $dpSub  = (int)$detailItem->getSubtotal();
                                @endphp
                                <div class="od-product-row">
                                    <div class="od-product-thumb">
                                        @if($dp)
                                        <x-product-image
                                            :src="$dp->image_url"
                                            :sku="$dp->sku"
                                            :name="$dp->name"
                                            :alt="$dp->name"
                                            :category="$dp->category?->name"
                                            :width="80" />
                                        @else
                                        <img src="https://images.unsplash.com/photo-1544145945-f90425340c7e?auto=format&fit=crop&w=80&q=75" alt="Sản phẩm">
                                        @endif
                                    </div>
                                    <div class="od-product-info">
                                        <div class="od-product-name">{{ $dp?->name ?? 'Sản phẩm đã xóa' }}</div>
                                        <div class="od-product-qty">{{ $dpQty }} × {{ number_format($dpUnit, 0, ',', '.') }}đ</div>
                                    </div>
                                    <div class="od-product-subtotal">{{ number_format($dpSub, 0, ',', '.') }}đ</div>
                                </div>
                                @endforeach
                            </div>
                        </div>

                    </div>

                    {{-- Right col: SECTION 3 — Cost Breakdown (no total, that lives in footer) --}}
                    <div class="od-summary-col">
                        <div class="od-section">
                            <div class="od-section-title">Chi tiết chi phí</div>
                            <div class="od-section-body">
                                <div class="od-summary-row">
                                    <span>Tạm tính</span>
                                    <span>{{ number_format($subtotal, 0, ',', '.') }}đ</span>
                                </div>
                                <div class="od-summary-row">
                                    <span>Phí vận chuyển</span>
                                    <span>{{ number_format($shippingFee, 0, ',', '.') }}đ</span>
                                </div>
                                @if($discount > 0)
                                <div class="od-summary-row" style="color:#059669;">
                                    <span>Giảm giá</span>
                                    <span>−{{ number_format($discount, 0, ',', '.') }}đ</span>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

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
                @if($order->payment_method === 'vnpay' && $order->payment_status !== 'paid' && $order->status !== 'cancelled')
                <a href="{{ route('vnpay.payment', $order) }}" class="btn btn-primary btn-sm mt-2">
                    Thanh toán VNPay
                </a>
                @endif
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
        // ─── Review toggles ───
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

        // ─── Order detail toggles ───
        document.querySelectorAll('[data-detail-toggle]').forEach(function (button) {
            const targetId = button.dataset.detailTarget;
            const panel = targetId ? document.getElementById(targetId) : null;

            if (!panel) return;

            button.addEventListener('click', function () {
                const isOpen = panel.classList.contains('is-open');

                if (isOpen) {
                    panel.classList.remove('is-open');
                    button.setAttribute('aria-expanded', 'false');
                    const label = button.querySelector('.detail-label');
                    if (label) label.textContent = 'Xem chi tiết';
                } else {
                    panel.classList.add('is-open');
                    button.setAttribute('aria-expanded', 'true');
                    const label = button.querySelector('.detail-label');
                    if (label) label.textContent = 'Ẩn chi tiết';
                }
            });
        });
    });
</script>
