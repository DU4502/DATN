@extends('layouts.client')

@section('title', 'Giỏ Hàng')

@section('content')
@php
    extract(require resource_path('views/partials/ui-product-data.php'));
    $shippingTiers = \App\Support\ShippingFee::distanceOptions();
@endphp
<script>
    document.body.dataset.page = 'cart';
</script>
<style>
    .cart-page {
        --cart-sticky-offset: 116px;
        background: linear-gradient(180deg, #effcf9 0%, #f7fffd 48%, #ffffff 100%);
        padding: 3rem 0 4.5rem;
    }

    .cart-title {
        font-size: clamp(2rem, 3.5vw, 3.2rem);
        line-height: 1.15;
        font-weight: 800;
        letter-spacing: 0;
    }

    .cart-items-card,
    .cart-select-toolbar,
    .cart-summary-card,
    .cart-recommend-card,
    .cart-free-card {
        border: 1px solid rgba(0, 139, 122, 0.10);
        border-radius: 28px;
        background: rgba(255, 255, 255, 0.94);
        box-shadow: 0 24px 60px rgba(8, 42, 38, 0.07);
    }

    .cart-item-image {
        width: 112px;
        height: 112px;
        border-radius: 8px;
        object-fit: cover;
        background: var(--drink-soft);
        flex: 0 0 auto;
    }

    .cart-select-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem 1.1rem;
        border-radius: 20px;
    }

    .cart-top-actions {
        min-height: 48px;
    }

    .cart-clear-all {
        display: inline-flex !important;
        align-items: center;
        gap: 0.45rem;
        padding: 0.65rem 1rem !important;
        border: 1px solid rgba(220, 53, 69, 0.22) !important;
        border-radius: 999px !important;
        background: rgba(255, 255, 255, 0.82) !important;
        color: #dc3545 !important;
        font-weight: 700;
        line-height: 1.2;
        opacity: 1 !important;
        visibility: visible !important;
    }

    .cart-clear-all:hover {
        border-color: #dc3545 !important;
        background: #fff1f2 !important;
        color: #b42334 !important;
    }

    .cart-select-meta {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 1rem;
    }

    .cart-select-check {
        width: 42px;
        height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--drink-border);
        border-radius: 50%;
        background: var(--drink-soft);
        flex: 0 0 auto;
        cursor: pointer;
    }

    .cart-select-check .form-check-input {
        width: 1.15rem;
        height: 1.15rem;
        margin: 0;
        cursor: pointer;
    }

    .cart-items-card {
        max-height: calc(100vh - 265px);
        overflow-y: auto;
        overscroll-behavior: contain;
        scroll-behavior: smooth;
        scrollbar-width: thin;
        scrollbar-color: rgba(0, 139, 122, 0.35) transparent;
    }

    .cart-items-card::-webkit-scrollbar {
        width: 8px;
    }

    .cart-items-card::-webkit-scrollbar-thumb {
        background: rgba(0, 139, 122, 0.35);
        border-radius: 999px;
    }

    .cart-items-card::-webkit-scrollbar-track {
        background: transparent;
    }

    .cart-summary-sticky {
        position: sticky;
        top: var(--cart-sticky-offset);
        z-index: 1;
    }

    .cart-item-card {
        border: 0;
        border-radius: 0;
        background: transparent;
        box-shadow: none;
        border-bottom: 1px solid rgba(0, 139, 122, 0.10);
        transition: opacity 0.18s ease, transform 0.18s ease, border-color 0.18s ease;
    }

    .cart-item-card:last-child {
        border-bottom: 0;
    }

    .cart-item-card.is-unselected {
        opacity: 0.58;
        border-bottom-color: rgba(100, 123, 120, 0.12);
    }

    .cart-item-card.is-unavailable {
        opacity: 1;
        background: linear-gradient(90deg, rgba(255, 241, 242, 0.9), rgba(255, 255, 255, 0.72));
        border-bottom-color: rgba(220, 53, 69, 0.18);
    }

    .cart-item-card.is-unavailable .cart-item-image {
        filter: grayscale(0.55);
        opacity: 0.72;
    }

    .cart-unavailable-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        margin-top: 0.35rem;
        padding: 0.3rem 0.55rem;
        border-radius: 999px;
        background: #fff1f2;
        color: #c52d3f;
        font-size: 0.72rem;
        font-weight: 800;
    }

    .cart-item-card:not(.is-unavailable) .cart-unavailable-badge {
        display: none;
    }

    .cart-qty {
        display: inline-flex;
        align-items: center;
        overflow: hidden;
        border: 1px solid var(--drink-border);
        border-radius: 999px;
        background: var(--drink-soft);
    }

    .cart-qty input {
        width: 52px;
        border: 0;
        background: transparent;
        text-align: center;
        font-weight: 700;
        padding: 0.6rem 0.2rem;
    }

    .cart-qty button {
        width: 38px;
        height: 38px;
        border: 0;
        background: transparent;
        color: var(--drink-primary);
        font-weight: 800;
    }

    .cart-remove {
        width: 36px;
        height: 36px;
        border: 0;
        border-radius: 50%;
        background: transparent;
        color: var(--drink-muted);
        transition: background 0.18s ease, color 0.18s ease;
    }

    .cart-remove:hover {
        background: #fff0f0;
        color: #d94b4b;
    }

    .payment-mark {
        width: 24px;
        height: 24px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        background: #e5e8e7;
        color: var(--drink-muted);
        font-size: 0.85rem;
    }

    .cart-free-card {
        background: linear-gradient(135deg, var(--drink-primary), #5dc8bb);
        color: #003731;
    }

    .cart-recommend-wrap { position: relative; height: 100%; display: flex; flex-direction: column; }
    .cart-recommend-card {
        display: flex;
        flex-direction: column;
        height: 100%;
        border-radius: 24px;
        overflow: hidden;
        background: #fff;
        transition: transform .22s ease, box-shadow .22s ease;
    }
    .cart-recommend-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 16px 36px rgba(8, 42, 38, 0.12);
    }
    .cart-recommend-card .recommend-image,
    .cart-recommend-card img.product-image {
        aspect-ratio: 1 / 1;
        width: 100%;
        height: auto !important;
        max-height: 220px;
        object-fit: cover;
        border-radius: 24px 24px 0 0;
        flex-shrink: 0;
        display: block;
    }
    .cart-recommend-card > .p-3 {
        flex: 1 1 auto;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 96px;
        padding: 1rem 4.5rem 1rem 1.15rem !important;
    }
    .cart-recommend-actions {
        position: absolute;
        top: 0.85rem;
        right: 0.85rem;
        bottom: 0.85rem;
        z-index: 6;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: .65rem;
        pointer-events: none;
    }
    .cart-recommend-actions form, .cart-recommend-actions a, .cart-recommend-actions button { pointer-events: auto; }
    .cart-recommend-action { display: grid; place-items: center; width: 44px; height: 44px; padding: 0; border: 1px solid rgba(255,255,255,.9); border-radius: 50%; background: rgba(255,255,255,.94); box-shadow: 0 8px 20px rgba(15,65,57,.16); backdrop-filter: blur(8px); transition: transform .18s ease, color .18s ease, background .18s ease; }
    .cart-recommend-action:hover { transform: scale(1.08); }
    .cart-recommend-action.is-favorite { color: #e83e5b; }
    .cart-recommend-action.is-favorite.is-active,
    .cart-recommend-action.is-favorite.is-active:hover,
    .cart-recommend-action.is-favorite.is-active:focus { color: #e83e5b; border-color: rgba(255,255,255,.9); background: rgba(255,255,255,.96); }
    .cart-recommend-action.is-add { color: #fff; border-color: #079b7d; background: #079b7d; }
    .cart-recommend-action.is-add:hover { background: #06735f; }
    .cart-recommend-action i { font-size: 1.15rem; line-height: 1; }

    @media (max-width: 767.98px) {
        .cart-page {
            padding: .85rem 0 2rem;
        }

        .cart-page .container {
            --bs-gutter-x: 1rem;
        }

        .cart-page .cart-title {
            font-size: 1.55rem;
        }

        .cart-page .section-kicker {
            margin-bottom: .15rem !important;
            font-size: .68rem;
        }

        .cart-page > .container > .mb-5 {
            margin-bottom: .8rem !important;
        }

        .cart-top-actions {
            min-height: 0;
            margin-bottom: .65rem !important;
        }

        .cart-top-actions .btn {
            padding-block: .25rem;
        }

        .cart-summary-sticky {
            position: static;
        }

        .cart-items-card {
            border-radius: 14px;
            max-height: none;
            overflow: visible;
        }

        .cart-item-card {
            border-radius: 0;
            padding: .75rem !important;
        }

        .cart-item-image {
            width: 68px;
            height: 68px;
        }

        .cart-select-toolbar {
            align-items: center;
            flex-direction: row;
            gap: .5rem;
            padding: .65rem .7rem;
            border-radius: 14px;
            font-size: .78rem;
        }

        .cart-select-meta {
            width: auto;
            margin-left: auto;
            gap: .4rem;
        }

        .cart-select-meta > .text-secondary {
            display: none;
        }

        .cart-clear-all {
            gap: .25rem;
            padding: .42rem .6rem !important;
            font-size: .75rem;
        }

        .cart-select-toolbar > label {
            gap: .45rem !important;
        }

        .cart-item-layout {
            display: grid !important;
            grid-template-columns: 30px 68px minmax(0, 1fr);
            align-items: center !important;
            gap: .6rem !important;
        }

        .cart-select-check {
            width: 30px;
            height: 30px;
        }

        .cart-select-check .form-check-input {
            width: .95rem;
            height: .95rem;
        }

        .cart-item-info {
            min-width: 0;
        }

        .cart-item-info h2 {
            overflow: hidden;
            margin-bottom: .15rem !important;
            font-size: .95rem !important;
            line-height: 1.25;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .cart-item-info p {
            overflow: hidden;
            margin-bottom: .1rem !important;
            font-size: .7rem !important;
            line-height: 1.3;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .cart-item-info > .fw-bold {
            font-size: .85rem !important;
        }

        .cart-item-controls {
            grid-column: 2 / -1;
            flex-direction: row !important;
            align-items: center !important;
            justify-content: space-between;
            gap: .5rem !important;
            padding-top: .1rem;
        }

        .cart-qty input {
            width: 34px;
            padding: .35rem .1rem;
        }

        .cart-qty button {
            width: 30px;
            height: 30px;
        }

        .cart-item-total {
            gap: .35rem !important;
        }

        .cart-item-total strong {
            font-size: .9rem !important;
        }

        .cart-remove {
            width: 30px;
            height: 30px;
        }

        .cart-summary-card {
            padding: 1rem !important;
            border-radius: 14px;
        }

        .cart-summary-card h2 {
            margin-bottom: .8rem !important;
        }

        .cart-summary-card .d-flex.justify-content-between {
            margin-bottom: .55rem !important;
            font-size: .82rem;
        }

        .cart-summary-card .h4 {
            margin-block: .8rem !important;
            font-size: 1.05rem !important;
        }

        .cart-summary-card .mt-4 {
            margin-top: .75rem !important;
        }

        .cart-page .row.g-5 {
            --bs-gutter-y: .8rem;
        }

        .cart-suggestions {
            margin-top: 1.5rem !important;
            padding-top: 0 !important;
        }

        .cart-suggestions .section-title {
            margin-bottom: .75rem !important;
            font-size: 1.3rem !important;
        }

        .cart-suggestions .row {
            --bs-gutter-x: .65rem;
            --bs-gutter-y: .65rem;
        }

        .cart-recommend-card {
            border-radius: 12px;
        }

        .recommend-image {
            border-radius: 12px 12px 0 0;
        }

        .cart-recommend-card > .p-3 {
            min-height: 78px;
            padding: .55rem 2.35rem .55rem .55rem !important;
        }

        .cart-recommend-card h3 {
            overflow: hidden;
            font-size: .82rem !important;
            line-height: 1.25;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .cart-recommend-card p {
            font-size: .78rem;
        }

        .cart-recommend-actions {
            top: .45rem;
            right: .4rem;
            bottom: .45rem;
        }

        .cart-recommend-action {
            width: 30px;
            height: 30px;
        }

        .cart-recommend-action i {
            font-size: .85rem;
        }
    }

    @media (min-width: 768px) and (max-width: 991.98px) {
        .cart-page { padding: 1.5rem 0 3rem; }
        .cart-page > .container > .row > .col-lg-8 { width: 66.666667%; }
        .cart-page > .container > .row > .col-lg-4 { width: 33.333333%; }
        .cart-page .row.g-5 { --bs-gutter-x: 1rem; }
        .cart-items-card,
        .cart-select-toolbar,
        .cart-summary-card { border-radius: 16px; }
        .cart-item-card { padding: 1rem !important; }
        .cart-item-image { width: 76px; height: 76px; }
        .cart-item-layout { gap: .8rem !important; }
        .cart-item-info h2 { font-size: 1rem !important; }
        .cart-item-controls { gap: .65rem !important; }
        .cart-summary-card { padding: 1rem !important; }
        .cart-summary-card h2 { margin-bottom: 1rem !important; }
        .cart-summary-card .d-flex.justify-content-between { gap: .5rem; font-size: .78rem; }
        .cart-suggestions { margin-top: 2rem !important; padding-top: 0 !important; }
        .cart-suggestions .col-6 { width: 25%; }
        .cart-suggestions .row { --bs-gutter-x: .75rem; }
        .cart-recommend-card > .p-3 { min-height: 90px; padding-right: 3rem !important; }
        .cart-recommend-action { width: 34px; height: 34px; }
    }
</style>

<section class="cart-page">
    <div class="container">
        <div class="mb-5">
            <p class="section-kicker mb-2">Giỏ hàng</p>
            <h1 class="cart-title mb-0">Giỏ hàng của bạn</h1>
        </div>

        @if(!empty($cart))
            @php
                $total = 0;
                $tax = 0;
                $initialSelectedCount = collect($cart)->filter(function ($item) use ($cartAvailability) {
                    $status = $cartAvailability->get((int) ($item['product_id'] ?? 0));

                    return $status && $status->is_available;
                })->count();
                $initialUnavailableCount = count($cart) - $initialSelectedCount;
            @endphp

            <div class="cart-top-actions d-flex align-items-center mb-4">
                <a href="{{ route('products.index') }}" class="btn btn-link text-primary text-decoration-none px-0">
                    <i class="bi bi-arrow-left me-1"></i>Tiếp tục mua hàng
                </a>
            </div>

            <div class="row g-5 align-items-start">
                <div class="col-lg-8">
                    <div class="cart-select-toolbar mb-3">
                        <label class="d-inline-flex align-items-center gap-3 fw-bold mb-0" for="cartSelectAll">
                            <input class="form-check-input m-0" type="checkbox" id="cartSelectAll" checked>
                            Chọn tất cả sản phẩm
                        </label>
                        <div class="cart-select-meta">
                            <div class="text-secondary">
                                Đã chọn <strong class="text-primary" data-selected-count>{{ $initialSelectedCount }}</strong> sản phẩm
                            </div>
                            <form action="{{ route('cart.clear') }}" method="POST" class="d-block m-0" data-ajax-cart>
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn cart-clear-all" aria-label="Xóa tất cả sản phẩm trong giỏ hàng">
                                    <i class="bi bi-trash3"></i>Xóa tất cả
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="alert alert-warning border-0 rounded-4 mb-3 {{ $initialUnavailableCount > 0 ? '' : 'd-none' }}" role="status" data-cart-availability-warning>
                        <i class="bi bi-exclamation-triangle-fill me-2" aria-hidden="true"></i>
                        Có sản phẩm đang tạm hết hàng tại chi nhánh này. Món vẫn được giữ trong giỏ để bạn có thể xóa hoặc chờ bán lại.
                    </div>

                    <div class="cart-items-card">
                        @foreach($cart as $id => $item)
                            @php
                                $subtotal = $item['price'] * $item['quantity'];
                                $cartItemStatus = $cartAvailability->get((int) ($item['product_id'] ?? 0));
                                $isCartItemAvailable = $cartItemStatus && $cartItemStatus->is_available;
                                if ($isCartItemAvailable) {
                                    $total += $subtotal;
                                }
                            @endphp

                            <div
                                class="cart-item-card p-3 p-md-4 {{ $isCartItemAvailable ? '' : 'is-unavailable is-unselected' }}"
                                data-cart-row
                                data-cart-key="{{ $id }}"
                                data-product-id="{{ $item['product_id'] ?? '' }}"
                                data-product-name="{{ $item['name'] }}"
                                data-product-availability="{{ $item['product_id'] ?? '' }}"
                                data-branch-id="{{ $branch?->id }}"
                                data-cart-available="{{ $isCartItemAvailable ? '1' : '0' }}"
                                data-cart-subtotal-value="{{ $subtotal }}"
                            >
                                <div class="cart-item-layout d-flex flex-column flex-md-row align-items-md-center gap-3 gap-md-4">
                                    <label class="cart-select-check" aria-label="Chọn {{ $item['name'] }}">
                                        <input class="form-check-input" type="checkbox" name="items[]" value="{{ $id }}" data-cart-select-item @checked($isCartItemAvailable) @disabled(! $isCartItemAvailable)>
                                    </label>

                                    <x-product-image
                                        :src="$item['image'] ?? null"
                                        :sku="$item['sku'] ?? null"
                                        :name="$item['name']"
                                        :alt="$item['name']"
                                        :category="$item['category'] ?? null"
                                        class="cart-item-image"
                                        :width="400"
                                    />

                                    <div class="cart-item-info flex-grow-1">
                                        <h2 class="h4 fw-bold mb-1">{{ $item['name'] }}</h2>
                                        <span class="cart-unavailable-badge" data-cart-unavailable-badge>
                                            <i class="bi bi-x-circle-fill" aria-hidden="true"></i>
                                            Tạm hết hàng tại {{ $branch?->name ?? 'chi nhánh hiện tại' }}
                                        </span>
                                        <p class="text-secondary small mb-1">
                                            {{ $item['size_label'] ?? 'Kích cỡ M' }}
                                            @if(($item['size_extra'] ?? 0) > 0)
                                                · +{{ number_format($item['size_extra'], 0, ',', '.') }}đ
                                            @endif
                                        </p>
                                        @if(!empty($item['toppings']))
                                            <p class="text-primary small fw-semibold mb-1">
                                                Món thêm: {{ collect($item['toppings'])->pluck('name')->filter()->implode(', ') }}
                                            </p>
                                        @endif
                                        <p class="text-secondary small mb-1">
                                            Đường {{ $item['sugar_level'] ?? 100 }}% · Đá {{ $item['ice_level'] ?? 100 }}%
                                        </p>
                                        <p class="text-secondary small mb-0">Đơn giá: <span class="fw-bold text-dark">{{ number_format($item['price'], 0, ',', '.') }}đ</span></p>
                                    </div>

                                    <div class="cart-item-controls d-flex flex-column align-items-md-end gap-3">
                                        <form action="{{ route('cart.update', $id) }}" method="POST" class="cart-qty" data-ajax-cart data-cart-qty-form>
                                            @csrf
                                            @method('PATCH')
                                            <button type="button" data-cart-qty-minus aria-label="Giảm số lượng" @disabled(! $isCartItemAvailable)>-</button>
                                            <input type="text" name="quantity" value="{{ $item['quantity'] }}" inputmode="numeric" pattern="[0-9]*" autocomplete="off" spellcheck="false" min="1" max="99" aria-label="Số lượng" data-cart-quantity="{{ $id }}" data-cart-qty-input @disabled(! $isCartItemAvailable)>
                                            <button type="button" data-cart-qty-plus aria-label="Tăng số lượng" @disabled(! $isCartItemAvailable)>+</button>
                                        </form>

                                        <div class="cart-item-total d-flex align-items-center gap-3">
                                            <strong class="h5 text-primary mb-0" data-cart-subtotal="{{ $id }}">{{ number_format($subtotal, 0, ',', '.') }}đ</strong>
                                            <form action="{{ route('cart.remove', $id) }}" method="POST" data-ajax-cart data-cart-remove="true">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="cart-remove" aria-label="Xóa {{ $item['name'] }}">
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                </div>

                <div class="col-lg-4">
                    <div class="cart-summary-card cart-summary-sticky p-4 p-md-5">
                        <h2 class="h4 fw-bold mb-4">Tóm tắt đơn</h2>

                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-secondary">Sản phẩm đã chọn</span>
                            <strong><span data-selected-count>{{ $initialSelectedCount }}</span> món</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-secondary">Tạm tính tiền món</span>
                            <strong data-selected-total>{{ number_format($total, 0, ',', '.') }}đ</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-secondary">Phí vận chuyển</span>
                            <strong class="text-primary small">Tính ở bước thanh toán</strong>
                        </div>
                        @if($tax > 0)
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-secondary">Thuế ước tính</span>
                            <strong>{{ number_format($tax, 0, ',', '.') }}đ</strong>
                        </div>
                        @endif

                        <hr class="my-3" style="border-color: rgba(0, 139, 122, 0.15);">

                        <div class="d-flex justify-content-between align-items-center h4 fw-bold mb-4">
                            <span>Tổng thanh toán</span>
                            <span class="text-primary" data-selected-grand-total>{{ number_format($total + $tax, 0, ',', '.') }}đ</span>
                        </div>

                        @auth
                            <button type="button" class="btn btn-primary btn-lg w-100 rounded-pill {{ $initialSelectedCount < 1 ? 'disabled' : '' }}" data-cart-checkout-button data-checkout-url="{{ route('checkout.index') }}" @disabled($initialSelectedCount < 1)>
                                Thanh toán ngay <i class="bi bi-arrow-right ms-2"></i>
                            </button>
                            <p class="small text-danger text-center mt-3 mb-0 {{ $initialSelectedCount > 0 ? 'd-none' : '' }}" data-cart-selection-warning>
                                Vui lòng chọn ít nhất một sản phẩm để thanh toán.
                            </p>
                        @else
                            <button type="button" class="btn btn-primary btn-lg w-100 rounded-pill {{ $initialSelectedCount < 1 ? 'disabled' : '' }}" data-cart-checkout-button data-checkout-url="{{ route('checkout.index') }}" data-guest-checkout-url="{{ route('checkout.guest.index') }}" @disabled($initialSelectedCount < 1)>
                                Thanh toán ngay <i class="bi bi-arrow-right ms-2"></i>
                            </button>
                            <p class="small text-danger text-center mt-3 mb-0 {{ $initialSelectedCount > 0 ? 'd-none' : '' }}" data-cart-selection-warning>
                                Vui lòng chọn ít nhất một sản phẩm để thanh toán.
                            </p>
                        @endauth

                        <div class="d-flex justify-content-center gap-3 mt-4">
                            <span class="payment-mark"><i class="bi bi-credit-card"></i></span>
                            <span class="payment-mark"><i class="bi bi-wallet2"></i></span>
                            <span class="payment-mark"><i class="bi bi-shield-check"></i></span>
                        </div>
                    </div>
                </div>
            </div>

            @if(isset($suggestions) && $suggestions->isNotEmpty())
                <section class="cart-suggestions mt-5 pt-5">
                    <h2 class="section-title h1 mb-4">Gợi ý thêm</h2>
                    <div class="row g-4">
                        @foreach($suggestions->take(4) as $product)
                            <div class="col-6 col-lg-3">
                                <div class="cart-recommend-wrap">
                                <div class="cart-recommend-actions">
                                    @auth
                                        @php($isFavorite = $favoriteProductIds->contains($product->id))
                                        <form method="POST" action="{{ route('favorites.toggle', $product) }}" data-cart-favorite-form>@csrf<button type="submit" class="cart-recommend-action is-favorite {{ $isFavorite ? 'is-active' : '' }}" aria-label="{{ $isFavorite ? 'Bỏ yêu thích' : 'Thêm vào yêu thích' }}" data-cart-favorite-button><i class="bi {{ $isFavorite ? 'bi-heart-fill' : 'bi-heart' }}"></i></button></form>
                                    @else
                                        <a href="{{ route('login') }}" class="cart-recommend-action is-favorite" aria-label="Đăng nhập để yêu thích"><i class="bi bi-heart"></i></a>
                                    @endauth
                                    @if($product->availabilityAt($branch) === true)
                                        <form method="POST" action="{{ route('cart.add', $product->id) }}" data-ajax-cart data-product-availability="{{ $product->id }}" data-branch-id="{{ $branch?->id }}">@csrf<input type="hidden" name="size" value="S"><input type="hidden" name="sugar_level" value="100"><input type="hidden" name="ice_level" value="100"><input type="hidden" name="toppings" value="[]"><button type="submit" class="cart-recommend-action is-add" aria-label="Thêm {{ $product->name }} vào giỏ" data-product-action><i class="bi bi-plus-lg"></i></button></form>
                                    @endif
                                </div>
                                <a href="{{ route('products.show', $product->slug) }}" class="cart-recommend-card overflow-hidden h-100 d-block text-decoration-none text-dark">
                                    <x-product-image
                                        :src="$product->image_url ?? null"
                                        :sku="$product->sku"
                                        :name="$product->name"
                                        :alt="$product->name"
                                        :category="$product->category?->name"
                                        class="recommend-image"
                                    />
                                    <div class="p-3">
                                        <x-product-availability-badge :product="$product" :branch="$branch" class="mb-2" />
                                        <h3 class="h5 fw-bold mb-1">{{ $product->name }}</h3>
                                        <p class="text-primary fw-semibold mb-0">{{ number_format($product->price, 0, ',', '.') }}đ</p>
                                    </div>
                                </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif
        @else
            <div class="cart-summary-card text-center p-5">
                <span class="checkout-step mx-auto mb-3"><i class="bi bi-bag"></i></span>
                <h2 class="h3 fw-bold">Giỏ hàng trống</h2>
                <p class="text-secondary">Bạn chưa có sản phẩm nào trong giỏ hàng.</p>
                <a href="{{ route('products.index') }}" class="btn btn-primary rounded-pill px-4">Mua sắm ngay</a>
            </div>
        @endif
    </div>
</section>

<div class="modal fade" id="cartCheckoutGateModal" tabindex="-1" aria-labelledby="cartCheckoutGateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 24px;">
            <div class="modal-header border-0 pb-0">
                <div>
                    <p class="section-kicker mb-1">Thanh toán</p>
                    <h5 class="modal-title fw-bold" id="cartCheckoutGateModalLabel">Bạn có <span data-gate-selected-count>0</span> món · <span data-gate-selected-total>0đ</span></h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body pt-3">
                <div class="p-4 rounded-4 mb-3" style="background: #f0faf7; border: 1px solid #cceee4;">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="checkout-step"><i class="bi bi-stars"></i></span>
                        <strong>Đăng nhập để tích điểm & dùng voucher</strong>
                    </div>
                    <ul class="small text-secondary mb-4 ps-3">
                        <li>Tích điểm mỗi đơn (1.000đ = 1 điểm)</li>
                        <li>Phiếu giảm giá theo hạng thành viên</li>
                        <li>Theo dõi đơn nhanh hơn</li>
                    </ul>
                    <a href="{{ route('login') }}" class="btn btn-outline-primary w-100 rounded-pill py-2">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Đăng nhập / Đăng ký
                    </a>
                </div>

                <div class="text-center text-secondary small my-3">hoặc</div>

                <button type="button" class="btn btn-primary w-100 rounded-pill py-3" data-guest-checkout-confirm>
                    <i class="bi bi-lightning-charge me-2"></i>Mua hàng nhanh — không cần tài khoản
                </button>
                <p class="small text-secondary text-center mt-2 mb-0">Chỉ cần SĐT & email để nhận hóa đơn</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-link text-secondary w-100" data-bs-dismiss="modal">Quay lại giỏ hàng</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-cart-favorite-form]').forEach((form) => {
            form.addEventListener('submit', async function (event) {
                event.preventDefault();
                const button = form.querySelector('[data-cart-favorite-button]');
                if (!button || button.disabled) return;
                button.disabled = true;
                try {
                    const response = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
                    if (!response.ok) throw new Error('favorite_failed');
                    const result = await response.json();
                    button.classList.toggle('is-active', result.favorited);
                    button.querySelector('i').className = 'bi ' + (result.favorited ? 'bi-heart-fill' : 'bi-heart');
                    button.setAttribute('aria-label', result.favorited ? 'Bỏ yêu thích' : 'Thêm vào yêu thích');
                } catch (error) {
                    form.submit();
                } finally {
                    button.disabled = false;
                }
            });
        });

        document.querySelectorAll('[data-cart-qty-form]').forEach((form) => {
            const input = form.querySelector('[data-cart-qty-input]');
            const minusButton = form.querySelector('[data-cart-qty-minus]');
            const plusButton = form.querySelector('[data-cart-qty-plus]');
            const cartRow = form.closest('[data-cart-row]');

            if (!input || !minusButton || !plusButton) {
                return;
            }

            const clampQuantity = (value) => {
                const normalized = Number.parseInt(String(value || '').replace(/[^\d]/g, ''), 10);
                return Number.isFinite(normalized) ? Math.min(99, Math.max(1, normalized)) : 1;
            };

            const render = () => {
                const quantity = clampQuantity(input.value || 1);
                const isAvailable = cartRow?.dataset.cartAvailable !== '0';
                input.value = String(quantity);
                input.disabled = !isAvailable;
                minusButton.disabled = !isAvailable || quantity <= 1;
                plusButton.disabled = !isAvailable || quantity >= 99;
            };

            const stopRepeat = () => {
                if (minusButton._repeatTimer) {
                    window.clearTimeout(minusButton._repeatTimer);
                    minusButton._repeatTimer = null;
                }

                if (minusButton._repeatInterval) {
                    window.clearInterval(minusButton._repeatInterval);
                    minusButton._repeatInterval = null;
                }

                if (plusButton._repeatTimer) {
                    window.clearTimeout(plusButton._repeatTimer);
                    plusButton._repeatTimer = null;
                }

                if (plusButton._repeatInterval) {
                    window.clearInterval(plusButton._repeatInterval);
                    plusButton._repeatInterval = null;
                }
            };

            const submitForm = () => {
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                    return;
                }

                form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
            };

            const syncSoon = (() => {
                let timer = null;

                return (delay = 220) => {
                    clearTimeout(timer);
                    timer = window.setTimeout(() => {
                        timer = null;
                        submitForm();
                    }, delay);
                };
            })();

            const setQuantity = (nextQuantity, immediate = false) => {
                input.value = String(clampQuantity(nextQuantity));
                render();
                syncSoon(immediate ? 0 : 220);
            };

            const startRepeat = (delta, button) => {
                stopRepeat();
                setQuantity(clampQuantity(input.value || 1) + delta, true);
                button._repeatTimer = window.setTimeout(() => {
                    button._repeatInterval = window.setInterval(() => {
                        setQuantity(clampQuantity(input.value || 1) + delta, true);
                    }, 75);
                }, 260);
            };

            minusButton.addEventListener('pointerdown', (event) => {
                event.preventDefault();
                startRepeat(-1, minusButton);
            });

            plusButton.addEventListener('pointerdown', (event) => {
                event.preventDefault();
                startRepeat(1, plusButton);
            });

            [minusButton, plusButton].forEach((button) => {
                button.addEventListener('pointerup', stopRepeat);
                button.addEventListener('pointercancel', stopRepeat);
                button.addEventListener('lostpointercapture', stopRepeat);
            });

            input.addEventListener('input', () => {
                const digitsOnly = String(input.value || '').replace(/[^\d]/g, '');
                if (input.value !== digitsOnly) {
                    input.value = digitsOnly;
                }

                if (digitsOnly === '') {
                    return;
                }

                render();
                syncSoon();
            });

            input.addEventListener('click', () => {
                input.select();
            });

            input.addEventListener('blur', () => {
                if (String(input.value || '').trim() === '') {
                    setQuantity(1, true);
                    return;
                }

                setQuantity(input.value, true);
            });

            input.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    stopRepeat();
                    setQuantity(input.value || 1, true);
                    return;
                }

                if (event.key === 'ArrowUp') {
                    event.preventDefault();
                    stopRepeat();
                    setQuantity(clampQuantity(input.value || 1) + 1, true);
                } else if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    stopRepeat();
                    setQuantity(clampQuantity(input.value || 1) - 1, true);
                }
            });

            render();
        });

        const selectAll = document.getElementById('cartSelectAll');
        const checkoutButton = document.querySelector('[data-cart-checkout-button]');
        const selectionWarning = document.querySelector('[data-cart-selection-warning]');
        const moneyFormatter = new Intl.NumberFormat('vi-VN');

        function itemChecks() {
            return Array.from(document.querySelectorAll('[data-cart-select-item]'));
        }

        function selectableItemChecks() {
            return itemChecks().filter((input) => !input.disabled);
        }

        function formatMoney(value) {
            return `${moneyFormatter.format(Math.max(0, Math.round(value)))}đ`;
        }

        function selectedItems() {
            return selectableItemChecks().filter((input) => input.checked && input.closest('[data-cart-row]'));
        }

        function updateSelectionSummary() {
            const checks = itemChecks();
            const selectableChecks = selectableItemChecks();
            let total = 0;
            let selectedCount = 0;

            checks.forEach((input) => {
                const row = input.closest('[data-cart-row]');

                if (!row) {
                    return;
                }

                if (input.disabled) input.checked = false;
                row.classList.toggle('is-unselected', !input.checked);

                if (input.checked && !input.disabled) {
                    selectedCount += 1;
                    total += Number(row.dataset.cartSubtotalValue || 0);
                }
            });

            document.querySelectorAll('[data-selected-count]').forEach((element) => {
                element.textContent = selectedCount;
            });

            document.querySelectorAll('[data-selected-total], [data-selected-grand-total]').forEach((element) => {
                element.textContent = formatMoney(total);
            });

            if (selectAll) {
                selectAll.disabled = selectableChecks.length === 0;
                selectAll.checked = selectableChecks.length > 0 && selectedCount === selectableChecks.length;
                selectAll.indeterminate = selectedCount > 0 && selectedCount < selectableChecks.length;
            }

            if (checkoutButton) {
                checkoutButton.disabled = selectedCount < 1;
                checkoutButton.classList.toggle('disabled', selectedCount < 1);
            }

            selectionWarning?.classList.toggle('d-none', selectedCount > 0);
            document.querySelector('[data-cart-availability-warning]')?.classList.toggle(
                'd-none',
                !document.querySelector('[data-cart-row][data-cart-available="0"]')
            );
        }

        selectAll?.addEventListener('change', function () {
            selectableItemChecks().forEach((input) => {
                input.checked = selectAll.checked;
            });

            updateSelectionSummary();
        });

        document.addEventListener('change', function (event) {
            if (event.target.matches('[data-cart-select-item]')) {
                updateSelectionSummary();
            }
        });

        checkoutButton?.addEventListener('click', function () {
            const checkedItems = selectedItems();

            if (checkedItems.length < 1) {
                updateSelectionSummary();
                return;
            }

            const guestCheckoutUrl = checkoutButton.dataset.guestCheckoutUrl;

            if (guestCheckoutUrl) {
                document.querySelectorAll('[data-gate-selected-count]').forEach((element) => {
                    element.textContent = checkedItems.length;
                });
                document.querySelectorAll('[data-gate-selected-total]').forEach((element) => {
                    const totalText = document.querySelector('[data-selected-grand-total]')?.textContent || '0đ';
                    element.textContent = totalText;
                });

                const gateModal = document.getElementById('cartCheckoutGateModal');
                if (gateModal) {
                    new bootstrap.Modal(gateModal).show();
                }

                return;
            }

            const url = new URL(checkoutButton.dataset.checkoutUrl, window.location.origin);
            checkedItems.forEach((input) => {
                url.searchParams.append('items[]', input.value);
            });

            checkoutButton.disabled = true;
            checkoutButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang chuyển hướng...';
            window.location.href = url.toString();
        });

        document.querySelector('[data-guest-checkout-confirm]')?.addEventListener('click', function () {
            const checkoutBtn = document.querySelector('[data-cart-checkout-button]');
            const guestUrl = checkoutBtn?.dataset.guestCheckoutUrl;

            if (!guestUrl) {
                return;
            }

            const url = new URL(guestUrl, window.location.origin);
            url.searchParams.set('require_location', '1');
            selectedItems().forEach((input) => {
                url.searchParams.append('items[]', input.value);
            });

            window.location.href = url.toString();
        });

        document.addEventListener('cart:updated', updateSelectionSummary);
        document.addEventListener('product:availability-applied', function (event) {
            const payload = event.detail;
            if (!payload) return;

            document.querySelectorAll(
                `[data-cart-row][data-product-id="${payload.product_id}"][data-branch-id="${payload.branch_id}"]`
            ).forEach((row) => {
                const wasAvailable = row.dataset.cartAvailable === '1';
                const isAvailable = Boolean(payload.is_available);
                const checkbox = row.querySelector('[data-cart-select-item]');
                const quantityInput = row.querySelector('[data-cart-qty-input]');
                const minusButton = row.querySelector('[data-cart-qty-minus]');
                const plusButton = row.querySelector('[data-cart-qty-plus]');
                const unavailableBadge = row.querySelector('[data-cart-unavailable-badge]');
                const quantity = Number.parseInt(quantityInput?.value || '1', 10) || 1;

                row.dataset.cartAvailable = isAvailable ? '1' : '0';
                row.classList.toggle('is-unavailable', !isAvailable);
                if (checkbox) {
                    if (!isAvailable) checkbox.checked = false;
                    checkbox.disabled = !isAvailable;
                }
                if (quantityInput) quantityInput.disabled = !isAvailable;
                if (minusButton) minusButton.disabled = !isAvailable || quantity <= 1;
                if (plusButton) plusButton.disabled = !isAvailable || quantity >= 99;
                if (unavailableBadge && !isAvailable) {
                    unavailableBadge.innerHTML = '<i class="bi bi-x-circle-fill" aria-hidden="true"></i> Tạm hết hàng tại '
                        + (payload.branch_name || 'chi nhánh hiện tại');
                }

                if (wasAvailable && !isAvailable && typeof window.showRealtimeToast === 'function') {
                    window.showRealtimeToast(
                        `${payload.product_name || row.dataset.productName || 'Sản phẩm'} vừa tạm hết hàng. Món vẫn được giữ trong giỏ.`,
                        'warning'
                    );
                }
            });

            updateSelectionSummary();
        });
        updateSelectionSummary();

        const flashToast = sessionStorage.getItem('cart_flash_toast');
        if (flashToast) {
            sessionStorage.removeItem('cart_flash_toast');
            let feedback = document.querySelector('[data-cart-feedback]');
            if (!feedback) {
                feedback = document.createElement('div');
                feedback.className = 'cart-feedback';
                feedback.dataset.cartFeedback = 'true';
                document.body.appendChild(feedback);
            }
            feedback.innerHTML = `
                <span class="cart-feedback-icon"><i class="bi bi-bag-check"></i></span>
                <span class="cart-feedback-copy">
                    <strong>Thành công</strong>
                    <span>${flashToast}</span>
                </span>
                <span class="cart-feedback-actions">
                    <button type="button" class="cart-feedback-close" aria-label="Đóng"><i class="bi bi-x-lg"></i></button>
                </span>
            `;
            feedback.querySelector('.cart-feedback-close')?.addEventListener('click', () => feedback.classList.remove('show'));
            feedback.classList.add('show');
            window.setTimeout(() => feedback.classList.remove('show'), 2500);
        }
    });
</script>
@endsection
