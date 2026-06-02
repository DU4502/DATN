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

    .cart-qty {
        display: inline-flex;
        align-items: center;
        overflow: hidden;
        border: 1px solid var(--drink-border);
        border-radius: 999px;
        background: var(--drink-soft);
    }

    .cart-qty input {
        width: 44px;
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

    .promo-control {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: center;
        gap: 0.5rem;
        border: 1px solid rgba(0, 139, 122, 0.16);
        border-radius: 18px;
        background: #f7fffd;
        padding: 0.45rem;
    }

    .promo-control input {
        border: 0;
        background: transparent;
        height: 44px;
        padding: 0 0.9rem;
        min-width: 0;
        font-weight: 600;
    }

    .promo-control input:focus {
        box-shadow: none;
    }

    .promo-control .btn {
        min-width: 92px;
        min-height: 44px;
        border-radius: 14px !important;
        padding-inline: 1rem !important;
        box-shadow: none;
        white-space: nowrap;
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

    .recommend-image {
        aspect-ratio: 1 / 1;
        width: 100%;
        object-fit: cover;
        border-radius: 18px 18px 0 0;
    }

    @media (max-width: 767.98px) {
        .cart-items-card {
            border-radius: 22px;
            max-height: 58vh;
        }

        .cart-item-card {
            border-radius: 0;
        }

        .cart-item-image {
            width: 92px;
            height: 92px;
        }

        .cart-select-toolbar {
            align-items: flex-start;
            flex-direction: column;
            border-radius: 18px;
        }
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
            @endphp

            <div class="row g-5 align-items-start">
                <div class="col-lg-8">
                    <div class="cart-select-toolbar mb-3">
                        <label class="d-inline-flex align-items-center gap-3 fw-bold mb-0" for="cartSelectAll">
                            <input class="form-check-input m-0" type="checkbox" id="cartSelectAll" checked>
                            Chọn tất cả sản phẩm
                        </label>
                        <div class="text-secondary">
                            Đã chọn <strong class="text-primary" data-selected-count>{{ count($cart) }}</strong> sản phẩm
                        </div>
                    </div>

                    <div class="cart-items-card">
                        @foreach($cart as $id => $item)
                            @php
                                $subtotal = $item['price'] * $item['quantity'];
                                $total += $subtotal;
                            @endphp

                            <div class="cart-item-card p-3 p-md-4" data-cart-row data-cart-key="{{ $id }}" data-cart-subtotal-value="{{ $subtotal }}">
                                <div class="d-flex flex-column flex-md-row align-items-md-center gap-3 gap-md-4">
                                    <label class="cart-select-check" aria-label="Chọn {{ $item['name'] }}">
                                        <input class="form-check-input" type="checkbox" name="items[]" value="{{ $id }}" checked data-cart-select-item>
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

                                    <div class="flex-grow-1">
                                        <h2 class="h4 fw-bold mb-1">{{ $item['name'] }}</h2>
                                        <p class="text-secondary small mb-1">
                                            {{ $item['size_label'] ?? 'Size M' }}
                                            @if(($item['size_extra'] ?? 0) > 0)
                                                · +{{ number_format($item['size_extra'], 0, ',', '.') }}đ
                                            @endif
                                        </p>
                                        <p class="text-primary fw-bold mb-0">{{ number_format($item['price'], 0, ',', '.') }}đ</p>
                                    </div>

                                    <div class="d-flex flex-column align-items-md-end gap-3">
                                        <form action="{{ route('cart.update', $id) }}" method="POST" class="cart-qty" data-ajax-cart>
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" name="quantity" value="{{ max(1, $item['quantity'] - 1) }}" aria-label="Giảm số lượng">-</button>
                                            <input type="number" name="quantity" value="{{ $item['quantity'] }}" min="1" max="99" aria-label="Số lượng" data-cart-quantity="{{ $id }}">
                                            <button type="submit" name="quantity" value="{{ $item['quantity'] + 1 }}" aria-label="Tăng số lượng">+</button>
                                        </form>

                                        <div class="d-flex align-items-center gap-3">
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

                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-4">
                        <a href="{{ route('products.index') }}" class="btn btn-link text-primary text-decoration-none px-0">
                            <i class="bi bi-arrow-left me-1"></i>Tiếp tục mua hàng
                        </a>
                        <form action="{{ route('cart.clear') }}" method="POST" data-ajax-cart>
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-link text-dark text-decoration-none px-0">Xóa giỏ hàng</button>
                        </form>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="cart-summary-card p-4 p-md-5 sticky-top" style="top: 96px;">
                        <h2 class="h4 fw-bold mb-4">Tóm tắt đơn</h2>

                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-secondary">Sản phẩm đã chọn</span>
                            <strong><span data-selected-count>{{ count($cart) }}</span> món</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-secondary">Tạm tính đã chọn</span>
                            <strong data-selected-total>{{ number_format($total, 0, ',', '.') }}đ</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-secondary">Phí vận chuyển</span>
                            <strong class="text-primary">Tính theo km</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-4">
                            <span class="text-secondary">Thuế ước tính</span>
                            <strong>{{ $tax > 0 ? number_format($tax, 0, ',', '.') . 'đ' : '0đ' }}</strong>
                        </div>

                        <div class="d-flex justify-content-between align-items-center h4 fw-bold mb-4">
                            <span>Tạm tính</span>
                            <span class="text-primary" data-selected-grand-total>{{ number_format($total + $tax, 0, ',', '.') }}đ</span>
                        </div>

                        <div class="promo-control mb-4">
                            <input type="text" class="form-control" placeholder="Mã voucher">
                            <button type="button" class="btn btn-primary">Áp dụng</button>
                        </div>

                        @auth
                            <button type="button" class="btn btn-primary btn-lg w-100 rounded-pill" data-cart-checkout-button data-checkout-url="{{ route('checkout.index') }}">
                                Thanh toán ngay <i class="bi bi-arrow-right ms-2"></i>
                            </button>
                            <p class="small text-danger text-center mt-3 mb-0 d-none" data-cart-selection-warning>
                                Vui lòng chọn ít nhất một sản phẩm để thanh toán.
                            </p>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-primary btn-lg w-100 rounded-pill">
                                Đăng nhập để thanh toán
                            </a>
                        @endauth

                        <div class="d-flex justify-content-center gap-3 mt-4">
                            <span class="payment-mark"><i class="bi bi-credit-card"></i></span>
                            <span class="payment-mark"><i class="bi bi-wallet2"></i></span>
                            <span class="payment-mark"><i class="bi bi-shield-check"></i></span>
                        </div>
                    </div>

                    <div class="cart-free-card p-4 mt-4 d-flex align-items-start gap-3">
                        <i class="bi bi-truck fs-4"></i>
                        <div>
                            <div class="fw-bold">Phí giao hàng theo khoảng cách</div>
                            <div class="small mb-2">Chọn mốc km ở bước thanh toán để hệ thống cộng phí ship vào đơn.</div>
                            <div class="small">
                                Từ {{ number_format($shippingTiers[0]['base_fee'] ?? 10000, 0, ',', '.') }}đ
                                đến {{ number_format($shippingTiers[array_key_last($shippingTiers)]['base_fee'] ?? 50000, 0, ',', '.') }}đ.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if(isset($suggestions) && $suggestions->isNotEmpty())
                <section class="mt-5 pt-5">
                    <h2 class="section-title h1 mb-4">Gợi ý thêm</h2>
                    <div class="row g-4">
                        @foreach($suggestions->take(4) as $product)
                            <div class="col-sm-6 col-lg-3">
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
                                        <h3 class="h5 fw-bold mb-1">{{ $product->name }}</h3>
                                        <p class="text-primary fw-semibold mb-0">{{ number_format($product->price, 0, ',', '.') }}đ</p>
                                    </div>
                                </a>
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const selectAll = document.getElementById('cartSelectAll');
        const checkoutButton = document.querySelector('[data-cart-checkout-button]');
        const selectionWarning = document.querySelector('[data-cart-selection-warning]');
        const moneyFormatter = new Intl.NumberFormat('vi-VN');

        function itemChecks() {
            return Array.from(document.querySelectorAll('[data-cart-select-item]'));
        }

        function formatMoney(value) {
            return `${moneyFormatter.format(Math.max(0, Math.round(value)))}đ`;
        }

        function selectedItems() {
            return itemChecks().filter((input) => input.checked && input.closest('[data-cart-row]'));
        }

        function updateSelectionSummary() {
            const checks = itemChecks();
            let total = 0;
            let selectedCount = 0;

            checks.forEach((input) => {
                const row = input.closest('[data-cart-row]');

                if (!row) {
                    return;
                }

                row.classList.toggle('is-unselected', !input.checked);

                if (input.checked) {
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
                selectAll.checked = checks.length > 0 && selectedCount === checks.length;
                selectAll.indeterminate = selectedCount > 0 && selectedCount < checks.length;
            }

            if (checkoutButton) {
                checkoutButton.disabled = selectedCount < 1;
                checkoutButton.classList.toggle('disabled', selectedCount < 1);
            }

            selectionWarning?.classList.toggle('d-none', selectedCount > 0);
        }

        selectAll?.addEventListener('change', function () {
            itemChecks().forEach((input) => {
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

            const url = new URL(checkoutButton.dataset.checkoutUrl, window.location.origin);
            checkedItems.forEach((input) => {
                url.searchParams.append('items[]', input.value);
            });

            window.location.href = url.toString();
        });

        document.addEventListener('cart:updated', updateSelectionSummary);
        updateSelectionSummary();
    });
</script>
@endsection
