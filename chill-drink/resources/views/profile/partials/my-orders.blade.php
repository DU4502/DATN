@php
$profileOrders = $profileOrders ?? collect();
$orderStatusLabels = $orderStatusLabels ?? \App\Support\OrderStatus::userBadgeStyles();
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
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .order-card.is-realtime-updated {
        border-color: rgba(13, 147, 115, 0.55);
        box-shadow: 0 0 0 3px rgba(13, 147, 115, 0.12), 0 14px 34px rgba(79, 183, 168, 0.12);
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
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
    }

    .order-status-pending {
        background: #fff6db;
        color: #9a6b00;
    }

    .order-status-in-progress {
        background: #e8f4ff;
        color: #1d5f9c;
    }

    .order-status-confirmed,
    .order-status-preparing {
        background: #e8f4ff;
        color: #1d5f9c;
    }

    .order-status-ready,
    .order-status-delivered {
        background: #e6f9f4;
        color: #0a6b4e;
    }

    .order-status-shipper-picked-up,
    .order-status-delivering {
        background: #f1e9ff;
        color: #5b3f9e;
    }

    .order-status-shipper-accepted {
        background: #f1e9ff;
        color: #5b3f9e;
    }

    .order-status-arrived {
        background: #fff4e8;
        color: #9a5b00;
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

    .order-detail-toggle {
        border: 1px solid rgba(13, 147, 115, 0.22);
        background: #ffffff;
        color: var(--drink-primary-dark);
        border-radius: 999px;
        padding: 0.45rem 0.9rem;
        font-size: 0.82rem;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        transition: transform 0.18s ease, border-color 0.18s ease, background 0.18s ease;
    }

    .order-detail-toggle:hover {
        background: var(--drink-primary-soft);
        border-color: rgba(13, 147, 115, 0.34);
        transform: translateY(-1px);
    }

    .order-detail-toggle i {
        transition: transform 0.2s ease;
    }

    .order-detail-toggle[aria-expanded="true"] i {
        transform: rotate(180deg);
    }

    .order-detail-panel {
        border-top: 1px solid rgba(213, 238, 232, 0.9);
        background: linear-gradient(180deg, #fcfffe 0%, #f7fffc 100%);
    }

    .order-detail-list {
        display: flex;
        flex-direction: column;
        gap: 0.9rem;
    }

    .order-detail-item {
        padding: 1rem;
        border: 1px solid rgba(213, 238, 232, 0.92);
        border-radius: 18px;
        background: #ffffff;
    }

    .order-detail-head {
        display: flex;
        align-items: flex-start;
        gap: 0.85rem;
    }

    .order-detail-thumb {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        overflow: hidden;
        flex: 0 0 auto;
        background: var(--drink-primary-soft);
    }

    .order-detail-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .order-detail-main {
        flex: 1 1 auto;
        min-width: 0;
    }

    .order-detail-title {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 0.75rem;
        align-items: start;
    }

    .order-detail-name {
        font-weight: 800;
        color: var(--drink-ink);
        line-height: 1.35;
    }

    .order-detail-meta {
        color: var(--drink-muted);
        font-size: 0.82rem;
        margin-top: 0.2rem;
    }

    .order-detail-total {
        color: var(--drink-primary-dark);
        font-weight: 800;
    }

    .order-detail-body {
        margin-top: 0.9rem;
        padding-left: calc(52px + 0.85rem);
        display: flex;
        flex-direction: column;
        gap: 0.55rem;
    }

    .order-detail-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 0.75rem;
        align-items: start;
        font-size: 0.9rem;
    }

    .order-detail-row strong {
        font-weight: 700;
    }

    .order-detail-label {
        color: var(--drink-muted);
        font-size: 0.74rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .order-detail-note {
        color: var(--drink-muted);
        font-size: 0.84rem;
        line-height: 1.55;
    }

    .order-detail-line-total {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 0.75rem;
        align-items: center;
        margin-top: 0.2rem;
        padding: 0.65rem 0.8rem;
        border-radius: 10px;
        background: linear-gradient(180deg, #effcf8 0%, #e7faf4 100%);
        color: var(--drink-primary-dark);
        font-weight: 800;
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

    @media (max-width: 575.98px) {
        .order-detail-body {
            padding-left: 0;
        }

        .order-detail-title,
        .order-detail-row,
        .order-detail-line-total {
            grid-template-columns: 1fr;
            gap: 0.3rem;
        }
    }
</style>

<div id="profile-orders" class="mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0">Lịch sử mua hàng</h2>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('loyalty.index') }}" class="btn btn-outline-warning"><i class="bi bi-star-fill me-1"></i>Điểm thưởng</a>
            <a href="{{ route('favorites.index') }}" class="btn btn-outline-danger"><i class="bi bi-heart me-1"></i>Món yêu thích</a>
            <a href="{{ route('group-orders.index') }}" class="btn btn-outline-primary"><i class="bi bi-people me-1"></i>Đơn nhóm</a>
            <a href="{{ route('products.index') }}" class="btn btn-outline-primary">Tiếp tục mua sắm</a>
        </div>
    </div>

    @forelse($profileOrders as $order)
    <?php $statusKey = $order->status_display_key ?? $order->status; ?>
    <?php $status = $orderStatusLabels[$statusKey] ?? ['label' => $order->status, 'class' => 'order-status-pending']; ?>
    <article class="order-card mb-4" id="order-{{ $order->id }}" data-order-id="{{ $order->id }}" data-order-status="{{ $statusKey }}">
        <div class="order-card-header">
            <div>
                <div class="fw-bold text-primary">{{ $order->displayCode() }}</div>
                <div class="text-secondary small">{{ $order->created_at?->format('d/m/Y H:i') }}</div>
                @if($order->scheduled_at)
                <div class="small fw-semibold text-primary mt-1"><i class="bi bi-calendar-check me-1"></i>Nhận lúc {{ $order->scheduled_at->format('H:i · d/m/Y') }}</div>
                @endif
            </div>
            <span class="order-status-badge {{ $status['class'] }}" data-order-status-badge data-status="{{ $statusKey }}">
                <i class="bi {{ \App\Support\OrderStatus::notificationIcon($statusKey) }}" data-order-status-icon aria-hidden="true"></i>
                <span data-order-status-label>{{ $status['label'] }}</span>
            </span>
        </div>

        @php
        $reviewedProducts = $order->reviewed_products ?? [];
        @endphp

        @foreach(collect($order->orderItems ?? [])->groupBy(function ($item) {
        return $item->product?->id ? 'product-' . $item->product->id : 'item-' . $item->id;
        }) as $group)
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

                @if($product)
                <form method="POST" action="{{ route('orders.items.reorder', [$order, $item]) }}" class="mt-2">
                    @csrf
                    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-repeat me-1"></i>Mua lại món này</button>
                </form>
                @endif

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

        <div class="order-card-footer">
            <div class="text-secondary small">
                <div class="mb-2">
                    <strong class="text-dark">
                        {{ ($order->fulfillment_type ?? 'delivery') === 'pickup' ? 'Thông tin nhận hàng:' : 'Thông tin giao hàng:' }}
                    </strong>
                </div>
                <div class="d-flex align-items-start gap-2 mb-1">
                    <i class="bi bi-person text-primary"></i>
                    <span>{{ $order->customerName() ?: 'Khách hàng' }}</span>
                </div>
                <div class="d-flex align-items-start gap-2 mb-1">
                    <i class="bi bi-telephone text-primary"></i>
                    <span>{{ $order->customerPhone() ?: 'Chưa cập nhật' }}</span>
                </div>
                @if(($order->fulfillment_type ?? 'delivery') === 'pickup')
                    <div class="d-flex align-items-start gap-2 mb-1">
                        <i class="bi bi-shop text-primary"></i>
                        <span>
                            Nhận tại
                            <strong class="text-dark">{{ $order->branch?->name ?? 'chi nhánh đã chọn' }}</strong>
                        </span>
                    </div>
                    <div class="d-flex align-items-start gap-2 mb-2">
                        <i class="bi bi-geo-alt text-primary"></i>
                        <span>{{ $order->branch?->address ?? 'Chưa cập nhật địa chỉ chi nhánh' }}</span>
                    </div>
                @else
                    <div class="d-flex align-items-start gap-2 mb-2">
                        <i class="bi bi-geo-alt text-primary"></i>
                        <span>{{ $order->getShippingAddress() }}</span>
                    </div>
                @endif
                
                <div class="border-top pt-2 mt-2">
                    <div class="mb-1">
                        Hình thức nhận:
                        <strong class="text-dark">
                            {{ ($order->fulfillment_type ?? 'delivery') === 'pickup' ? 'Nhận tại chi nhánh' : 'Giao đến địa chỉ' }}
                        </strong>
                    </div>
                    <div class="mb-1">Thanh toán: <strong class="text-dark">{{ $paymentLabels[$order->payment_method] ?? strtoupper($order->payment_method) }}</strong></div>
                    @if($order->note)
                    <div class="mt-1">
                        @php
                            // Split note into customer note and delivery info
                            $noteText = $order->note;
                            $customerNote = '';
                            $deliveryInfo = '';
                            
                            // Check if note contains "Giao hàng:"
                            if (preg_match('/^(.*?)(Giao hàng:.*)$/uis', $noteText, $matches)) {
                                $customerNote = trim($matches[1]);
                                $deliveryInfo = trim($matches[2]);
                            } else {
                                $customerNote = $noteText;
                            }
                        @endphp
                        
                        @if($customerNote)
                        <div class="mb-1">Ghi chú: {{ $customerNote }}</div>
                        @endif
                        
                        @if($deliveryInfo)
                        <div>{{ $deliveryInfo }}</div>
                        @endif
                    </div>
                    @endif
                </div>
                @if($order->status === 'cancelled' && $order->cancellation_reason)
                <div class="alert alert-danger d-flex align-items-start gap-2 mt-3 mb-0 p-2 cancellation-reason-alert" style="border-radius: 12px; border-left: 4px solid #dc2626; font-size: 0.9rem;">
                    <i class="bi bi-exclamation-triangle-fill" style="font-size: 1.1rem; flex-shrink: 0;"></i>
                    <div class="flex-grow-1">
                        <div class="fw-bold mb-1">Lý do hủy đơn:</div>
                        <div>{{ $order->cancellation_reason }}</div>
                    </div>
                </div>
                @endif
            </div>
            <div class="text-end">
                <div class="text-secondary small">Tổng thanh toán</div>
                <div class="h5 fw-bold text-primary mb-0">{{ number_format((int) ($order->display_total ?? $order->total ?? 0), 0, ',', '.') }}đ</div>
                
                <div class="d-flex flex-column gap-2 mt-2">
                    <button
                        type="button"
                        class="order-detail-toggle w-100 justify-content-center"
                        data-order-detail-toggle
                        data-order-detail-target="order-detail-{{ $order->id }}"
                        aria-expanded="false"
                        aria-controls="order-detail-{{ $order->id }}"
                    >
                        <span>Xem chi tiết đơn hàng</span>
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    @if(($order->fulfillment_type ?? 'delivery') === 'delivery' && $statusKey !== 'cancelled')
                        <a href="{{ route('orders.track', $order) }}" class="btn btn-sm btn-primary w-100 fw-semibold">
                            <i class="bi bi-geo-alt-fill me-1"></i>Theo dõi đơn hàng
                        </a>
                    @endif

                    <form method="POST" action="{{ route('orders.reorder', $order) }}">
                        @csrf
                        <button class="btn btn-sm btn-outline-primary w-100"><i class="bi bi-lightning-charge me-1"></i>Đặt lại đơn</button>
                    </form>
                    @if($statusKey === 'completed' || $order->status === 'completed')
                        <a href="{{ route('orders.issues.create', $order) }}" class="btn btn-sm btn-outline-secondary w-100"><i class="bi bi-headset me-1"></i>Báo vấn đề đơn</a>
                    @endif
                    
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-danger w-100 {{ $statusKey === \App\Support\OrderStatus::PENDING ? '' : 'd-none' }}"
                        data-bs-toggle="modal"
                        data-bs-target="#customerCancelOrderModal"
                        data-order-id="{{ $order->id }}"
                        data-order-cancel-action
                    >
                        <i class="bi bi-x-circle me-1"></i>Hủy đơn hàng
                    </button>
                    
                    @if($statusKey === 'delivered' || $order->status === 'delivered')
                        @if($order->delivered_at)
                            <div class="small text-secondary text-center">
                                Tự hoàn thành lúc <strong>{{ $order->delivered_at->copy()->addMinutes(\App\Services\DeliveredOrderCompletionService::AUTO_COMPLETE_AFTER_MINUTES)->format('H:i') }}</strong> nếu bạn không xác nhận.
                            </div>
                        @endif
                    @endif
                    <form
                        method="POST"
                        action="{{ route('orders.confirm-received', $order) }}"
                        class="{{ $statusKey === \App\Support\OrderStatus::DELIVERED ? '' : 'd-none' }}"
                        data-order-confirm-action
                        onsubmit="return confirm('Xác nhận bạn đã nhận được đơn hàng này?');"
                    >
                        @csrf
                        <button type="submit" class="btn btn-sm btn-success w-100">
                            <i class="bi bi-check-circle me-1"></i>Xác nhận đã nhận
                        </button>
                    </form>
                </div>
                
                @if($order->payment_method === 'vnpay' && in_array($order->payment_status, ['pending', 'failed']) && $order->status !== 'cancelled')
                    <div class="mt-2" data-order-payment-action>
                        @if($order->payment_status === 'failed')
                            <div class="badge bg-danger-subtle text-danger mb-2">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                Thanh toán thất bại
                            </div>
                        @else
                            <div class="badge bg-warning-subtle text-warning mb-2">
                                <i class="bi bi-clock-history me-1"></i>
                                Chưa thanh toán
                            </div>
                        @endif
                        <div>
                            <a href="{{ route('vnpay.payment', $order) }}" class="btn btn-primary btn-sm">
                                <i class="bi bi-credit-card me-1"></i>
                                {{ $order->payment_status === 'failed' ? 'Thanh toán lại' : 'Thanh toán ngay' }}
                            </a>
                        </div>
                    </div>
                @endif
                
                @if($order->payment_method === 'vnpay' && $order->payment_status === 'paid')
                    <div class="badge bg-success-subtle text-success mt-2">
                        <i class="bi bi-check-circle-fill me-1"></i>
                        Đã thanh toán
                    </div>
                @endif
            </div>
        </div>

        <div class="order-detail-panel d-none" id="order-detail-{{ $order->id }}" data-order-detail-panel>
            <div class="p-3 p-md-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                    <div>
                        <h3 class="h6 fw-bold mb-1">Chi tiết đơn hàng</h3>
                        <p class="text-secondary small mb-0">Hiển thị đầy đủ size, topping, đường, đá và ghi chú của từng món.</p>
                    </div>
                    <span class="badge text-bg-light border">{{ $order->orderItems->count() }} món cấu hình</span>
                </div>

                <div class="order-detail-list">
                    @foreach($order->orderItems as $detailItem)
                        @php
                            $detailProduct = $detailItem->product;
                            $detailToppings = $detailItem->toppingLines ?? collect();
                            $detailSizeName = trim((string) ($detailItem->productSize?->size?->name ?? ''));
                            $detailSizeLabel = $detailSizeName !== '' ? 'Size '.$detailSizeName : null;
                            $detailQuantity = max(1, (int) ($detailItem->quantity ?? 1));
                            $detailBaseUnitPrice = max(0, (int) ($detailProduct->price ?? 0));
                            $detailLineTotal = (int) $detailItem->getSubtotal();
                            $detailUnitPrice = max(0, (int) ($detailItem->unit_price ?? 0));
                            $detailToppingUnitTotal = (int) $detailToppings->sum(fn ($line) => (int) ($line->price ?? 0));
                            $detailSizeExtraUnit = max(0, $detailUnitPrice - $detailBaseUnitPrice - $detailToppingUnitTotal);
                        @endphp
                        <div class="order-detail-item">
                            <div class="order-detail-head">
                                <div class="order-detail-thumb">
                                    @if($detailProduct)
                                        <x-product-image
                                            :src="$detailProduct->image_url"
                                            :sku="$detailProduct->sku"
                                            :name="$detailProduct->name"
                                            :alt="$detailProduct->name"
                                            :category="$detailProduct->category?->name"
                                            :width="180" />
                                    @else
                                        <img src="{{ view()->shared('uiDefaultImage', 'https://images.unsplash.com/photo-1544145945-f90425340c7e?auto=format&fit=crop&w=200&q=85') }}" alt="Sản phẩm">
                                    @endif
                                </div>
                                <div class="order-detail-main">
                                    <div class="order-detail-title">
                                        <div>
                                            <div class="order-detail-name">{{ $detailProduct?->name ?? 'Sản phẩm đã xóa' }}</div>
                                            <div class="order-detail-meta">
                                                {{ collect([$detailSizeLabel, 'x'.$detailQuantity])->filter()->implode(' · ') }}
                                            </div>
                                        </div>
                                        <strong class="order-detail-total">{{ number_format($detailLineTotal, 0, ',', '.') }}đ</strong>
                                    </div>
                                </div>
                            </div>

                            <div class="order-detail-body">
                                <div>
                                    <div class="order-detail-label">Giá gốc</div>
                                    <div class="order-detail-row">
                                        <span>{{ number_format($detailBaseUnitPrice, 0, ',', '.') }}đ × {{ $detailQuantity }}</span>
                                        <strong>{{ number_format($detailBaseUnitPrice * $detailQuantity, 0, ',', '.') }}đ</strong>
                                    </div>
                                </div>

                                @if($detailSizeExtraUnit > 0)
                                    <div>
                                        <div class="order-detail-label">Phụ thu size</div>
                                        <div class="order-detail-row">
                                            <span>+{{ number_format($detailSizeExtraUnit, 0, ',', '.') }}đ × {{ $detailQuantity }}</span>
                                            <strong>+{{ number_format($detailSizeExtraUnit * $detailQuantity, 0, ',', '.') }}đ</strong>
                                        </div>
                                    </div>
                                @endif

                                @if($detailToppings->isNotEmpty())
                                    <div>
                                        <div class="order-detail-label">Topping</div>
                                        @foreach($detailToppings as $toppingLine)
                                            @php($detailToppingLineTotal = max(0, (int) ($toppingLine->price ?? 0)) * $detailQuantity)
                                            <div class="order-detail-row">
                                                <span>
                                                    + {{ $toppingLine->topping?->name ?? 'Topping' }}
                                                    @if($detailQuantity > 1)
                                                        <span class="d-block text-secondary small">{{ number_format((int) ($toppingLine->price ?? 0), 0, ',', '.') }}đ × {{ $detailQuantity }}</span>
                                                    @endif
                                                </span>
                                                <strong>+{{ number_format($detailToppingLineTotal, 0, ',', '.') }}đ</strong>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                <div class="order-detail-row">
                                    <span>Đường</span>
                                    <strong>{{ (int) ($detailItem->sugar_level ?? 100) }}%</strong>
                                </div>
                                <div class="order-detail-row">
                                    <span>Đá</span>
                                    <strong>{{ (int) ($detailItem->ice_level ?? 100) }}%</strong>
                                </div>

                                @if(filled($detailItem->item_note))
                                    <div class="order-detail-note">Ghi chú: {{ $detailItem->item_note }}</div>
                                @endif

                                <div class="order-detail-line-total">
                                    <span>Tạm tính món</span>
                                    <strong>{{ number_format($detailLineTotal, 0, ',', '.') }}đ</strong>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
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

<!-- Modal Hủy đơn hàng (Customer) -->
<div class="modal fade" id="customerCancelOrderModal" tabindex="-1" aria-labelledby="customerCancelOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);">
            <div class="modal-header" style="border-bottom: 1px solid #e9ecef;">
                <h5 class="modal-title fw-bold" id="customerCancelOrderModalLabel">
                    <i class="bi bi-x-circle text-danger me-2"></i>Xác nhận hủy đơn hàng
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="customerCancelOrderForm" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-warning d-flex align-items-start gap-2 mb-3" style="border-radius: 12px;">
                        <i class="bi bi-info-circle-fill" style="font-size: 1.2rem;"></i>
                        <div class="flex-grow-1">
                            <div class="fw-semibold mb-1">Lưu ý quan trọng:</div>
                            <small>Bạn chỉ có thể hủy đơn hàng khi đơn đang ở trạng thái <strong>"Chờ xác nhận"</strong>. Sau khi quán xác nhận, đơn hàng không thể hủy.</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="customerCancellationReason" class="form-label fw-semibold">Lý do hủy đơn <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="customerCancellationReason" name="cancellation_reason" rows="4" placeholder="Vui lòng cho chúng tôi biết lý do bạn muốn hủy đơn hàng..." required style="border-radius: 12px;"></textarea>
                        <div class="form-text">Lý do của bạn giúp chúng tôi cải thiện dịch vụ tốt hơn.</div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #e9ecef;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 12px;">Đóng</button>
                    <button type="submit" class="btn btn-danger" style="border-radius: 12px;">
                        <i class="bi bi-x-circle me-1"></i>Xác nhận hủy
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Handle customer cancel order modal
    document.addEventListener('DOMContentLoaded', function() {
        const customerCancelModal = document.getElementById('customerCancelOrderModal');
        const customerCancelForm = document.getElementById('customerCancelOrderForm');
        const customerCancelReasonTextarea = document.getElementById('customerCancellationReason');
        
        if (customerCancelModal) {
            customerCancelModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const orderId = button.getAttribute('data-order-id');
                const cancelUrl = '{{ route("orders.cancel", ":id") }}'.replace(':id', orderId);
                
                customerCancelForm.setAttribute('action', cancelUrl);
                customerCancelReasonTextarea.value = '';
            });
        }
    });
</script>

<script>
    function highlightOrderCard(orderCard, shouldScroll = false) {
        if (!orderCard) {
            return;
        }

        if (shouldScroll) {
            orderCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        orderCard.classList.add('is-realtime-updated');

        window.setTimeout(() => {
            orderCard.classList.remove('is-realtime-updated');
        }, 2500);
    }

    document.addEventListener('DOMContentLoaded', function () {
        const params = new URLSearchParams(window.location.search);
        const orderId = params.get('order');

        if (orderId) {
            highlightOrderCard(
                document.getElementById(`order-${orderId}`)
                    || document.querySelector(`[data-order-id="${orderId}"]`),
                true
            );
        }

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

        document.querySelectorAll('[data-order-detail-toggle]').forEach(function (button) {
            const targetId = button.dataset.orderDetailTarget;
            const panel = targetId ? document.getElementById(targetId) : null;

            if (!panel) {
                return;
            }

            button.addEventListener('click', function () {
                const isHidden = panel.classList.contains('d-none');
                panel.classList.toggle('d-none', !isHidden);
                button.setAttribute('aria-expanded', String(isHidden));
                button.querySelector('span').textContent = isHidden ? 'Thu gọn chi tiết' : 'Xem chi tiết đơn hàng';
            });
        });
    });

    const statusClassMap = @json(collect(\App\Support\OrderStatus::userBadgeStyles())->mapWithKeys(fn ($item, $key) => [$key => $item['class']]));
    const statusLabelMap = @json(collect(\App\Support\OrderStatus::userBadgeStyles())->mapWithKeys(fn ($item, $key) => [$key => $item['label']]));
    const statusIconMap = @json(collect(array_keys(\App\Support\OrderStatus::userBadgeStyles()))->mapWithKeys(fn ($status) => [$status => \App\Support\OrderStatus::notificationIcon($status)]));

    function applyOrderStatusUpdate(payload) {
        const orderId = String(payload?.order_id ?? '').trim();
        const status = String(payload?.status ?? payload?.order_status ?? '').trim();
        const orderCard = Array.from(document.querySelectorAll('.order-card[data-order-id]'))
            .find(card => String(card.dataset.orderId) === orderId);

        if (!orderCard || !status) {
            console.warn('[Order realtime] matching order card not found', { orderId, status });
            return false;
        }

        const badge = orderCard.querySelector('[data-order-status-badge]');

        if (!badge) {
            console.warn('[Order realtime] status badge not found', { orderId, status });
            return false;
        }

        let statusLabel = badge.querySelector('[data-order-status-label]');
        let statusIcon = badge.querySelector('[data-order-status-icon]');

        if (!statusLabel || !statusIcon) {
            badge.replaceChildren();
            statusIcon = document.createElement('i');
            statusIcon.dataset.orderStatusIcon = '';
            statusIcon.setAttribute('aria-hidden', 'true');
            statusLabel = document.createElement('span');
            statusLabel.dataset.orderStatusLabel = '';
            badge.append(statusIcon, statusLabel);
        }

        orderCard.dataset.orderStatus = status;
        if (payload.updated_at) orderCard.dataset.orderUpdatedAt = payload.updated_at;
        badge.dataset.status = status;
        statusLabel.textContent = statusLabelMap[status] || payload.status_label || status;
        statusIcon.className = `bi ${payload.status_icon || statusIconMap[status] || 'bi-bell'}`;
        badge.className = `order-status-badge ${statusClassMap[status] || 'order-status-pending'}`;

        const cancelAction = orderCard.querySelector('[data-order-cancel-action]');
        const confirmAction = orderCard.querySelector('[data-order-confirm-action]');
        const paymentAction = orderCard.querySelector('[data-order-payment-action]');
        if (cancelAction) cancelAction.classList.toggle('d-none', status !== 'pending');
        if (confirmAction) confirmAction.classList.toggle('d-none', status !== 'delivered');
        if (paymentAction && status === 'cancelled') paymentAction.classList.add('d-none');
        
        // If order is cancelled and has a reason, display it in the footer
        if (status === 'cancelled' && payload.cancellation_reason) {
            const footer = orderCard.querySelector('.order-card-footer > div:first-child');
            if (footer) {
                // Remove existing cancellation reason if any
                const existingReason = footer.querySelector('.cancellation-reason-alert');
                if (existingReason) {
                    existingReason.remove();
                }
                
                // Add new cancellation reason
                const reasonHtml = `
                    <div class="alert alert-danger d-flex align-items-start gap-2 mt-3 mb-0 p-2 cancellation-reason-alert" style="border-radius: 12px; border-left: 4px solid #dc2626; font-size: 0.9rem;">
                        <i class="bi bi-exclamation-triangle-fill" style="font-size: 1.1rem; flex-shrink: 0;"></i>
                        <div class="flex-grow-1">
                            <div class="fw-bold mb-1">Lý do hủy đơn:</div>
                            <div>${escapeHtml(payload.cancellation_reason)}</div>
                        </div>
                    </div>
                `;
                footer.insertAdjacentHTML('beforeend', reasonHtml);
            }
        }
        
        highlightOrderCard(orderCard);
        return true;
    }

    document.addEventListener('order:status-updated', function (event) {
        applyOrderStatusUpdate(event.detail || {});
    });

    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text ? String(text).replace(/[&<>"']/g, m => map[m]) : '';
    }
</script>
