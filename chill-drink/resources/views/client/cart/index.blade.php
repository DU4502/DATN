@extends('layouts.client')

@section('title', 'Giỏ Hàng')

@section('content')
@php extract(require resource_path('views/partials/ui-product-data.php')); @endphp
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

    .cart-item-card,
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
        border: 1px solid var(--drink-border);
        border-radius: 999px;
        background: var(--drink-soft);
        overflow: hidden;
        padding: 0.35rem;
    }

    .promo-control input {
        border: 0;
        background: transparent;
        padding-left: 0.85rem;
        min-width: 0;
    }

    .promo-control input:focus {
        box-shadow: none;
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
        .cart-item-card {
            border-radius: 22px;
        }

        .cart-item-image {
            width: 92px;
            height: 92px;
        }
    }
</style>

<section class="cart-page">
    <div class="container">
        <div class="mb-5">
            <p class="section-kicker mb-2">Giỏ hàng</p>
            <h1 class="cart-title mb-0">Giỏ hàng của bạn</h1>
        </div>

        @if(session('cart') && count(session('cart')) > 0)
            @php
                $total = 0;
                $shipping = 0;
                $tax = 0;
            @endphp

            <div class="row g-5 align-items-start">
                <div class="col-lg-8">
                    <div class="vstack gap-4">
                        @foreach(session('cart') as $id => $item)
                            @php
                                $subtotal = $item['price'] * $item['quantity'];
                                $total += $subtotal;
                            @endphp

                            <div class="cart-item-card p-3 p-md-4" data-cart-row>
                                <div class="d-flex flex-column flex-md-row align-items-md-center gap-4">
                                    <x-product-image
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
                            <span class="text-secondary">Tạm tính</span>
                            <strong data-cart-total>{{ number_format($total, 0, ',', '.') }}đ</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-secondary">Phí vận chuyển</span>
                            <strong class="text-primary">{{ $shipping > 0 ? number_format($shipping, 0, ',', '.') . 'đ' : 'Miễn phí' }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-4">
                            <span class="text-secondary">Thuế ước tính</span>
                            <strong>{{ $tax > 0 ? number_format($tax, 0, ',', '.') . 'đ' : '0đ' }}</strong>
                        </div>

                        <div class="d-flex justify-content-between align-items-center h4 fw-bold mb-4">
                            <span>Tổng</span>
                            <span class="text-primary" data-cart-total>{{ number_format($total + $shipping + $tax, 0, ',', '.') }}đ</span>
                        </div>

                        <div class="promo-control d-flex align-items-center mb-4">
                            <input type="text" class="form-control" placeholder="Mã voucher">
                            <button type="button" class="btn btn-primary rounded-pill px-3">Áp dụng</button>
                        </div>

                        @auth
                            <a href="{{ route('checkout.index') }}" class="btn btn-primary btn-lg w-100 rounded-pill">
                                Thanh toán ngay <i class="bi bi-arrow-right ms-2"></i>
                            </a>
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

                    <div class="cart-free-card p-4 mt-4 d-flex align-items-center gap-3">
                        <i class="bi bi-truck fs-4"></i>
                        <div>
                            <div class="fw-bold">Đã mở khóa miễn phí giao hàng!</div>
                            <div class="small">Đơn hàng của bạn đang được miễn phí ship.</div>
                        </div>
                    </div>
                </div>
            </div>

            @if(isset($suggestions) && $suggestions->isNotEmpty())
                <section class="mt-5 pt-5">
                    <h2 class="section-title h1 mb-4">Gợi ý thêm</h2>
                    <div class="row g-4">
                        @foreach($suggestions->filter(fn ($product) => $uiProductVisible($product->sku ?? null))->take(4) as $product)
                            <div class="col-sm-6 col-lg-3">
                                <a href="{{ route('products.show', $product->slug) }}" class="cart-recommend-card overflow-hidden h-100 d-block text-decoration-none text-dark">
                                    <x-product-image
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
@endsection
