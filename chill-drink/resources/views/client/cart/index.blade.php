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

    .cart-recommend-wrap { position: relative; height: 100%; }
    .cart-recommend-actions { position: absolute; top: 1rem; right: 1rem; bottom: 1rem; z-index: 6; display: flex; flex-direction: column; justify-content: space-between; gap: .65rem; pointer-events: none; }
    .cart-recommend-actions form, .cart-recommend-actions a, .cart-recommend-actions button { pointer-events: auto; }
    .cart-recommend-action { display: grid; place-items: center; width: 45px; height: 45px; padding: 0; border: 1px solid rgba(255,255,255,.9); border-radius: 50%; background: rgba(255,255,255,.94); box-shadow: 0 10px 24px rgba(15,65,57,.18); backdrop-filter: blur(8px); transition: transform .18s ease, color .18s ease, background .18s ease; }
    .cart-recommend-action:hover { transform: scale(1.08); }
    .cart-recommend-action.is-favorite { color: #e83e5b; }
    .cart-recommend-action.is-favorite.is-active,
    .cart-recommend-action.is-favorite.is-active:hover,
    .cart-recommend-action.is-favorite.is-active:focus { color: #e83e5b; border-color: rgba(255,255,255,.9); background: rgba(255,255,255,.96); }
    .cart-recommend-action.is-add { color: #fff; border-color: #079b7d; background: #079b7d; }
    .cart-recommend-action.is-add:hover { background: #06735f; }
    .cart-recommend-action i { font-size: 1.18rem; line-height: 1; }
    .cart-recommend-card > .p-3 { min-height: 106px; padding-right: 4.75rem !important; }

    @media (max-width: 767.98px) {
        .cart-page {
            padding-top: 2rem;
        }

        .cart-summary-sticky {
            position: static;
        }

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
                                        @if(!empty($item['toppings']))
                                            <p class="text-primary small fw-semibold mb-1">
                                                Topping: {{ collect($item['toppings'])->pluck('name')->filter()->implode(', ') }}
                                            </p>
                                        @endif
                                        <p class="text-secondary small mb-1">
                                            Đường {{ $item['sugar_level'] ?? 100 }}% · Đá {{ $item['ice_level'] ?? 100 }}%
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
                    <div class="cart-summary-card cart-summary-sticky p-4 p-md-5">
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

                        @auth
                            <button type="button" class="btn btn-primary btn-lg w-100 rounded-pill" data-cart-checkout-button data-checkout-url="{{ route('checkout.index') }}">
                                Thanh toán ngay <i class="bi bi-arrow-right ms-2"></i>
                            </button>
                            <p class="small text-danger text-center mt-3 mb-0 d-none" data-cart-selection-warning>
                                Vui lòng chọn ít nhất một sản phẩm để thanh toán.
                            </p>
                        @else
                            <button type="button" class="btn btn-primary btn-lg w-100 rounded-pill" data-cart-checkout-button data-checkout-url="{{ route('checkout.index') }}" data-guest-checkout-url="{{ route('checkout.guest.index') }}">
                                Thanh toán ngay <i class="bi bi-arrow-right ms-2"></i>
                            </button>
                            <p class="small text-danger text-center mt-3 mb-0 d-none" data-cart-selection-warning>
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
                <section class="mt-5 pt-5">
                    <h2 class="section-title h1 mb-4">Gợi ý thêm</h2>
                    <div class="row g-4">
                        @foreach($suggestions->take(4) as $product)
                            <div class="col-sm-6 col-lg-3">
                                <div class="cart-recommend-wrap">
                                <div class="cart-recommend-actions">
                                    @auth
                                        @php($isFavorite = $favoriteProductIds->contains($product->id))
                                        <form method="POST" action="{{ route('favorites.toggle', $product) }}" data-cart-favorite-form>@csrf<button type="submit" class="cart-recommend-action is-favorite {{ $isFavorite ? 'is-active' : '' }}" aria-label="{{ $isFavorite ? 'Bỏ yêu thích' : 'Thêm vào yêu thích' }}" data-cart-favorite-button><i class="bi {{ $isFavorite ? 'bi-heart-fill' : 'bi-heart' }}"></i></button></form>
                                    @else
                                        <a href="{{ route('login') }}" class="cart-recommend-action is-favorite" aria-label="Đăng nhập để yêu thích"><i class="bi bi-heart"></i></a>
                                    @endauth
                                    @if(($product->stock ?? 1) > 0)
                                        <form method="POST" action="{{ route('cart.add', $product->id) }}" data-ajax-cart>@csrf<input type="hidden" name="size" value="S"><input type="hidden" name="sugar_level" value="100"><input type="hidden" name="ice_level" value="100"><input type="hidden" name="toppings" value="[]"><button type="submit" class="cart-recommend-action is-add" aria-label="Thêm {{ $product->name }} vào giỏ"><i class="bi bi-plus-lg"></i></button></form>
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
                        <li>Voucher giảm giá theo hạng thành viên</li>
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

            window.location.href = url.toString();
        });

        document.querySelector('[data-guest-checkout-confirm]')?.addEventListener('click', function () {
            const checkoutBtn = document.querySelector('[data-cart-checkout-button]');
            const guestUrl = checkoutBtn?.dataset.guestCheckoutUrl;

            if (!guestUrl) {
                return;
            }

            const url = new URL(guestUrl, window.location.origin);
            selectedItems().forEach((input) => {
                url.searchParams.append('items[]', input.value);
            });

            window.location.href = url.toString();
        });

        document.addEventListener('cart:updated', updateSelectionSummary);
        updateSelectionSummary();
    });
</script>
@endsection
