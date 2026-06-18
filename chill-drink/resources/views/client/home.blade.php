@extends('layouts.client')

@section('title', 'Trang Chủ')

@section('content')
@php extract(require resource_path('views/partials/ui-product-data.php')); @endphp
<style>
    .home-premium-page {
        background: var(--c-bg);
        color: var(--c-ink);
        overflow: hidden;
    }

    .home-premium-page .section-title,
    .home-premium-page h1,
    .home-premium-page h2,
    .home-premium-page h3 {
        color: var(--c-ink);
    }

    .home-premium-page .section-kicker {
        color: var(--c-primary);
    }

    .home-premium-page .text-secondary,
    .home-premium-page .text-muted {
        color: var(--c-muted) !important;
    }

    .premium-hero {
        position: relative;
        min-height: 100vh;
        display: flex;
        align-items: center;
        padding: 5rem 0;
        overflow: hidden;
        background: var(--c-bg-warm);
    }

    .premium-hero::before {
        content: '';
        position: absolute;
        inset: 0;
        background:
            linear-gradient(90deg, rgba(249, 250, 251, 0.96) 0%, rgba(249, 250, 251, 0.78) 42%, rgba(249, 250, 251, 0.22) 100%),
            linear-gradient(180deg, rgba(249, 250, 251, 0) 0%, var(--c-bg) 100%);
        z-index: 1;
    }

    .premium-hero img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        opacity: 0.9;
    }

    .premium-hero-content {
        position: relative;
        z-index: 2;
        max-width: 620px;
    }

    .premium-pill {
        display: inline-flex;
        align-items: center;
        border: 1px solid rgba(13, 147, 115, 0.35);
        color: var(--c-primary);
        background: rgba(13, 147, 115, 0.10);
        border-radius: var(--radius-full);
        padding: 0.35rem 0.85rem;
        font-size: 0.78rem;
        font-weight: 800;
        letter-spacing: 0.04em;
    }

    .premium-hero-title {
        font-size: clamp(2.4rem, 5.2vw, 4.8rem);
        line-height: 1;
        font-weight: 900;
        letter-spacing: -0.04em !important;
        margin: 1rem 0;
    }

    .premium-hero-title span {
        color: var(--c-primary);
    }

    .premium-hero-copy {
        max-width: 520px;
        color: var(--c-ink-secondary);
        font-size: 1rem;
    }

    .premium-slide-dots {
        position: absolute;
        right: max(1.5rem, calc((100vw - 1140px) / 2));
        bottom: 3rem;
        z-index: 2;
        display: flex;
        gap: 0.5rem;
    }

    .premium-slide-dots span {
        width: 28px;
        height: 3px;
        border-radius: var(--radius-full);
        background: rgba(17, 24, 39, 0.18);
    }

    .premium-slide-dots span:first-child {
        width: 46px;
        background: var(--c-primary);
    }

    /* ─── Shared ─── */
    .glass-card {
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid rgba(255, 255, 255, 0.6);
        box-shadow: var(--shadow-sm);
    }

    /* ─── Featured Products ─── */
    .featured-section {
        padding: 4.5rem 0;
        background: #fff;
    }

    .product-card {
        border-radius: var(--radius-2xl);
        overflow: hidden;
        border: 1px solid var(--c-border);
        background: var(--c-surface);
        box-shadow: var(--shadow-sm);
        transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1),
                    border-color 0.35s ease,
                    box-shadow 0.35s ease;
    }

    .product-card:hover {
        transform: translateY(-6px);
        border-color: var(--c-primary);
        box-shadow: var(--shadow-xl), 0 18px 46px rgba(13, 147, 115, 0.13);
    }

    .product-img-wrap {
        position: relative;
        aspect-ratio: 1;
        overflow: hidden;
        background: var(--c-bg-warm);
    }

    .product-img-wrap img {
        width: 100%; height: 100%; object-fit: cover;
        transition: transform 0.7s cubic-bezier(0.22, 1, 0.36, 1);
    }

    .product-card:hover .product-img-wrap img {
        transform: scale(1.08);
    }

    .product-img-wrap::after {
        content: "";
        position: absolute;
        inset: 0;
        z-index: 1;
        background: rgba(17, 24, 39, 0.18);
        opacity: 0;
        transition: opacity 0.25s ease;
        pointer-events: none;
    }

    .product-card:hover .product-img-wrap::after,
    .product-img-wrap:focus-within::after {
        opacity: 1;
    }

    .product-badge {
        position: absolute;
        top: 1rem; left: 1rem;
        background: var(--c-primary);
        color: #fff;
        padding: 0.35rem 0.75rem;
        border-radius: var(--radius-full);
        font-weight: 800; font-size: 0.7rem;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        box-shadow: var(--shadow-sm);
        z-index: 2;
    }

    .product-rating {
        display: flex; align-items: center; gap: 4px;
        color: #F59E0B; font-size: 0.8rem;
        margin-bottom: 0.5rem;
    }

    .product-card .card-body { padding: 1.5rem; }

    .product-card h3 {
        color: var(--c-ink);
        line-height: 1.18;
    }

    .product-card h3 a {
        color: #111827 !important;
    }

    .product-title-tail {
        display: inline-block;
        white-space: nowrap;
    }

    .product-price {
        text-align: center;
    }

    .product-cart-btn {
        width: 52px; height: 52px;
        border-radius: var(--radius-full);
        padding: 0; display: inline-flex; align-items: center; justify-content: center;
        background: var(--c-primary); color: #fff;
        border: 0;
        box-shadow: 0 16px 34px rgba(13, 147, 115, 0.28);
        transition: all 0.2s ease;
    }

    .product-card:hover .product-cart-btn, .product-cart-btn:hover {
        background: var(--c-primary-dark); color: #fff;
        transform: scale(1.1);
    }

    .product-image-cart-form {
        position: absolute;
        inset: 0;
        z-index: 3;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transform: translateY(8px);
        pointer-events: none;
        transition: opacity 0.24s ease, transform 0.24s ease;
    }

    .product-card:hover .product-image-cart-form,
    .product-img-wrap:focus-within .product-image-cart-form {
        opacity: 1;
        transform: translateY(0);
        pointer-events: auto;
    }

    .quick-add-modal .modal-content {
        border: 0;
        border-radius: 22px;
        box-shadow: 0 26px 70px rgba(8, 42, 38, 0.24);
    }

    .quick-add-thumb {
        width: 76px;
        height: 76px;
        border-radius: 18px;
        object-fit: contain;
        object-position: center;
        background: #ffffff;
        border: 1px solid var(--c-border);
        padding: 0.35rem;
        flex: 0 0 auto;
    }

    .quick-choice {
        min-width: 64px;
        border: 1.5px solid var(--c-border, #e5e7eb) !important;
        border-radius: 999px;
        background: #ffffff !important;
        color: var(--c-ink, #111827) !important;
        font-weight: 800;
        padding: 0.55rem 0.9rem;
        cursor: pointer;
        transition: background-color 0.16s ease, border-color 0.16s ease, color 0.16s ease, box-shadow 0.16s ease, transform 0.16s ease;
    }

    .quick-choice:hover {
        border-color: var(--c-primary, #0d9373) !important;
        background: var(--c-primary-light, #e6f7f2) !important;
        color: var(--c-primary-dark, #067a5f) !important;
        box-shadow: 0 0 0 3px rgba(13, 147, 115, 0.13);
    }

    .quick-choice.active {
        border-color: var(--c-primary, #0d9373) !important;
        background: var(--c-primary, #0d9373) !important;
        color: #ffffff !important;
        box-shadow: 0 8px 18px rgba(13, 147, 115, 0.24);
        transform: translateY(-1px);
    }

    .quick-topping-choice {
        min-width: 150px;
        text-align: left;
    }

    .quick-topping-choice small {
        display: block;
        margin-top: 0.1rem;
        font-size: 0.72rem;
        opacity: 0.82;
    }

    .section-heading-row {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 1.5rem;
        margin-bottom: 1.6rem;
    }

    .section-heading-row .section-copy {
        max-width: 560px;
    }

    .premium-underline {
        display: block;
        width: 66px;
        height: 3px;
        margin-top: 0.55rem;
        border-radius: var(--radius-full);
        background: var(--c-primary);
    }

    .promo-section {
        padding: 3rem 0 4.5rem;
        background: var(--c-bg);
    }

    .promo-grid {
        display: grid;
        grid-template-columns: minmax(0, 2fr) minmax(280px, 1fr);
        gap: 1.5rem;
        min-height: 360px;
    }

    .promo-main,
    .promo-side {
        position: relative;
        overflow: hidden;
        border-radius: var(--radius-2xl);
        border: 1px solid rgba(13, 147, 115, 0.14);
        box-shadow: var(--shadow-lg);
    }

    .promo-main img {
        width: 100%;
        height: 100%;
        min-height: 360px;
        object-fit: cover;
        transition: transform 0.9s cubic-bezier(0.22, 1, 0.36, 1);
    }

    .promo-main:hover img {
        transform: scale(1.05);
    }

    .promo-main-content {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: flex-start;
        padding: 3rem;
        background: linear-gradient(90deg, rgba(17, 24, 39, 0.82), rgba(17, 24, 39, 0.2), rgba(17, 24, 39, 0.02));
        color: #fff;
    }

    .promo-main-content p,
    .promo-main-content h3 {
        color: #fff;
    }

    .promo-side {
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 2rem;
        background:
            radial-gradient(circle at top right, rgba(13, 147, 115, 0.18), transparent 36%),
            var(--c-surface);
    }

    .promo-icon {
        width: 72px;
        height: 72px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1.5rem;
        border-radius: var(--radius-xl);
        background: var(--c-primary-light);
        color: var(--c-primary);
        font-size: 2rem;
    }

    .promo-action {
        width: fit-content;
        align-self: flex-start;
    }

    /* ─── Feature Band ─── */
    .feature-band {
        padding: 6rem 0;
        background:
            linear-gradient(135deg, rgba(255, 246, 225, 0.26), rgba(255, 191, 118, 0.14)),
            url('https://png.pngtree.com/background/20250106/original/pngtree-bubble-tea-cup-with-splashing-milk-summer-drinks-background-picture-image_15464755.jpg');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        color: #fff;
        position: relative;
        overflow: hidden;
    }

    .feature-band::before {
        content: '';
        position: absolute;
        inset: 0;
        background:
            radial-gradient(circle at 18% 20%, rgba(255, 255, 255, 0.30), transparent 34%),
            linear-gradient(180deg, rgba(74, 39, 20, 0.14), rgba(74, 39, 20, 0.20));
        pointer-events: none;
    }

    .feature-band .container {
        position: relative;
        z-index: 1;
    }

    .feature-item {
        text-align: center;
        padding: 2rem;
        background: rgba(61, 35, 22, 0.28);
        border: 1px solid rgba(255, 246, 225, 0.30);
        border-radius: var(--radius-2xl);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        box-shadow: 0 26px 58px rgba(74, 39, 20, 0.24);
        transition: transform 0.3s ease;
    }

    .feature-item:hover { transform: translateY(-10px); }

    .feature-icon-lg {
        width: 72px; height: 72px; margin: 0 auto 1.5rem;
        display: flex; align-items: center; justify-content: center;
        background: rgba(255, 246, 225, 0.18);
        border-radius: var(--radius-xl);
        font-size: 2rem; color: #fff;
        box-shadow: inset 0 0 0 1px rgba(255, 246, 225, 0.34);
    }

    /* ─── CTA Section ─── */
    .cta-section {
        padding: 4rem 0;
        background: #fff;
    }

    .cta-card {
        background: transparent;
        border-radius: 0;
        overflow: hidden;
        border: 0;
        box-shadow: none;
        display: block;
        text-align: center;
        max-width: 720px;
        margin: 0 auto;
    }

    .cta-content {
        padding: 0;
        display: block;
    }

    .newsletter-form {
        display: flex;
        align-items: stretch;
        gap: 0.75rem;
        max-width: 520px;
        margin: 1.5rem auto 0;
    }

    .newsletter-form .form-control {
        flex: 1 1 auto;
        min-width: 0;
        min-height: 44px;
        background: var(--c-surface);
        border-color: var(--c-border);
        border-radius: var(--radius-md);
        color: var(--c-ink);
    }

    .newsletter-form .form-control::placeholder {
        color: var(--c-subtle);
    }

    .newsletter-form .btn {
        flex: 0 0 auto;
        min-width: 112px;
        min-height: 44px;
        border-radius: var(--radius-md);
        padding-inline: 1.35rem !important;
        white-space: nowrap;
    }

    @media (max-width: 767.98px) {
        .section-heading-row { display: block; margin-bottom: 2rem; }
        .promo-grid { grid-template-columns: 1fr; min-height: 0; }
        .promo-main img { min-height: 360px; }
        .promo-main-content { padding: 2rem; }
        .cta-content { padding: 2rem; }
        .newsletter-form { flex-direction: column; }
        .newsletter-form .btn { width: 100%; }
    }
</style>

<div class="home-premium-page">
<x-animated-slider />

<section id="featured-products" class="featured-section">
    <div class="container">
        <div class="section-heading-row">
            <div class="section-copy">
                <h2 class="section-title h3 mb-0">Sản phẩm nổi bật</h2>
                <span class="premium-underline"></span>
            </div>
            <a class="fw-bold text-decoration-none" href="{{ route('products.index') }}" style="font-size: 0.8rem;">Xem tất cả <i class="bi bi-box-arrow-up-right ms-1"></i></a>
        </div>

        <div class="row g-4 g-lg-5">
            @php
                $homeFeaturedSkus = $uiHomeFeaturedSkus ?? [
                    'CD-TS-001', 'CD-CF-001', 'CD-ST-001', 'CD-NE-001',
                    'CD-TC-001', 'CD-SD-001', 'CD-TS-002', 'CD-CF-002',
                ];
                $homeHasSkuColumn = \Illuminate\Support\Facades\Schema::hasColumn('products', 'sku');
                $homeHasReviewsTable = \Illuminate\Support\Facades\Schema::hasTable('reviews');
                $homeProductQuery = \App\Models\Product::with('category')
                    ->when($homeHasReviewsTable, fn ($query) => $query->withAvg('reviews', 'rating')->withCount('reviews'));
                $homeFeaturedProducts = $homeHasSkuColumn
                    ? (clone $homeProductQuery)
                        ->whereIn('sku', $homeFeaturedSkus)
                        ->get()
                        ->sortBy(fn ($product) => array_search($product->sku, $homeFeaturedSkus, true))
                        ->take(4)
                        ->values()
                    : (clone $homeProductQuery)
                        ->where('status', true)
                        ->latest()
                        ->limit(4)
                        ->get();
            @endphp
            @forelse($homeFeaturedProducts as $product)
                @php
                    $reviewCount = (int) ($product->reviews_count ?? 0);
                    $rating = $reviewCount > 0 ? round((float) ($product->reviews_avg_rating ?? 0), 1) : 0;
                @endphp
                <div class="col-sm-6 col-lg-3">
                    <div class="product-card h-100 d-flex flex-column">
                        <div class="product-img-wrap">
                            <span class="product-badge">{{ $product->category->name }}</span>
                            <a href="{{ route('products.show', $product->slug) }}">
                                <x-product-image
                                    :src="$product->image_url"
                                    :sku="$product->sku ?? null"
                                    :name="$product->name"
                                    :alt="$product->name"
                                    :category="$product->category?->name"
                                />
                            </a>
                            <div class="product-image-cart-form">
                                <button
                                    type="button"
                                    class="product-cart-btn"
                                    aria-label="Chọn size và topping cho {{ $product->name }}"
                                    data-quick-add
                                    data-action="{{ route('cart.add', $product->id) }}"
                                    data-name="{{ $product->name }}"
                                    data-price="{{ number_format($product->price, 0, ',', '.') }}đ"
                                    data-image="{{ $product->image_url }}"
                                    data-category="{{ $product->category?->name }}"
                                >
                                    <i class="bi bi-cart-plus" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body d-flex flex-column flex-grow-1">
                            <div class="product-rating">
                                @if($reviewCount > 0)
                                    @for($star = 1; $star <= 5; $star++)
                                        <i class="bi {{ $rating >= $star ? 'bi-star-fill' : ($rating >= $star - 0.5 ? 'bi-star-half' : 'bi-star') }}"></i>
                                    @endfor
                                    <span class="text-secondary ms-1">({{ number_format($rating, 1) }} · {{ $reviewCount }})</span>
                                @else
                                    <i class="bi bi-star text-secondary"></i>
                                    <span class="text-secondary ms-1">Chưa có đánh giá</span>
                                @endif
                            </div>
                            <h3 class="h5 fw-bold mb-1">
                                <a href="{{ route('products.show', $product->slug) }}" class="text-dark text-decoration-none">
                                    @if(\Illuminate\Support\Str::endsWith($product->name, ' Đường Đen'))
                                        {{ \Illuminate\Support\Str::beforeLast($product->name, ' Đường Đen') }}
                                        <span class="product-title-tail">Đường Đen</span>
                                    @else
                                        {{ $product->name }}
                                    @endif
                                </a>
                            </h3>
                            @if(!empty($product->sku))
                                <p class="text-muted small font-monospace mb-3">{{ $product->sku }}</p>
                            @else
                                <div class="mb-3"></div>
                            @endif
                            <div class="mt-auto product-price">
                                <strong class="text-primary h5 mb-0">{{ number_format($product->price, 0, ',', '.') }}đ</strong>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                @foreach([
                    ['Matcha Latte', '45.000đ', 'https://images.unsplash.com/photo-1515823064-d6e0c04616a7?auto=format&fit=crop&w=700&q=85', 'Trà', 'matcha-latte-da'],
                    ['Trà Dâu Dứa', '38.000đ', 'https://images.unsplash.com/photo-1551024709-8f23befc6f87?auto=format&fit=crop&w=700&q=85', 'Trái cây', 'tropical-frost'],
                    ['Bạc Xỉu Đá', '29.000đ', 'https://images.unsplash.com/photo-1517701604599-bb29b565090c?auto=format&fit=crop&w=700&q=85', 'Cà phê', 'ca-phe-sua-da'],
                    ['Nước Chanh Bạc Hà', '35.000đ', 'https://images.unsplash.com/photo-1544145945-f90425340c7e?auto=format&fit=crop&w=700&q=85', 'Giải khát', 'citrus-sunset'],
                ] as $item)
                    <div class="col-sm-6 col-lg-3">
                        <div class="product-card h-100 d-flex flex-column">
                            <div class="product-img-wrap">
                                <span class="product-badge">{{ $item[3] }}</span>
                                <a href="{{ route('products.show', $item[4]) }}">
                                    <img src="{{ $item[2] }}" alt="{{ $item[0] }}">
                                </a>
                                <div class="product-image-cart-form">
                                    <button
                                        type="button"
                                        class="product-cart-btn"
                                        aria-label="Chọn size và topping cho {{ $item[0] }}"
                                        data-quick-add
                                        data-action="{{ route('cart.add', 'demo-' . $item[4]) }}"
                                        data-name="{{ $item[0] }}"
                                        data-price="{{ $item[1] }}"
                                        data-image="{{ $item[2] }}"
                                        data-category="{{ $item[3] }}"
                                    >
                                        <i class="bi bi-cart-plus" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body d-flex flex-column flex-grow-1">
                                <div class="product-rating">
                                    <i class="bi bi-star text-secondary"></i>
                                    <span class="text-secondary ms-1">Chưa có đánh giá</span>
                                </div>
                                <h3 class="h5 fw-bold mb-3">
                                    <a href="{{ route('products.show', $item[4]) }}" class="text-dark text-decoration-none">
                                        @if(\Illuminate\Support\Str::endsWith($item[0], ' Đường Đen'))
                                            {{ \Illuminate\Support\Str::beforeLast($item[0], ' Đường Đen') }}
                                            <span class="product-title-tail">Đường Đen</span>
                                        @else
                                            {{ $item[0] }}
                                        @endif
                                    </a>
                                </h3>
                                <div class="mt-auto product-price">
                                    <strong class="text-primary h5 mb-0">{{ $item[1] }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endforelse
        </div>
        
    </div>
</section>

<section class="promo-section">
    <div class="container">
        <div class="text-center mb-4">
            <h2 class="section-title h3 mb-0">Ưu đãi hấp dẫn</h2>
        </div>

        <div class="promo-grid">
            <div class="promo-main">
                <img src="{{ asset('images/chill-drink-promo.png') }}" alt="Chill Drink - đồ uống tươi mát">
                <div class="promo-main-content">
                    <p class="section-kicker text-white mb-2">Giao hàng siêu tốc</p>
                    <h3 class="display-6 fw-bold mb-3">Đồ uống tươi mát<br>tới tay trong 30 phút</h3>
                    <p class="mb-4" style="max-width: 460px;">Đơn hàng được chuẩn bị nhanh, giữ đúng hương vị và giao tiện lợi đến địa chỉ của bạn.</p>
                    <a href="{{ route('products.index') }}" class="btn btn-light fw-bold rounded-pill px-4">Đặt hàng ngay</a>
                </div>
            </div>

            <div class="promo-side">
                <div class="promo-icon"><i class="bi bi-ticket-perforated"></i></div>
                <h3 class="h4 fw-bold mb-3">Thành viên Chill</h3>
                <p class="text-secondary mb-4">Tích điểm nhận quà và nhận voucher riêng cho khách hàng thân thiết.</p>
                @guest
                    <a href="{{ route('register') }}" class="fw-bold text-decoration-none promo-action">Đăng ký ngay <i class="bi bi-chevron-right ms-1"></i></a>
                @else
                    <a href="{{ route('products.index') }}" class="fw-bold text-decoration-none promo-action">Đặt hàng ngay <i class="bi bi-chevron-right ms-1"></i></a>
                @endguest
            </div>
        </div>
    </div>
</section>

<section class="cta-section">
    <div class="container">
        @php
            $ctaCategory = $categories->firstWhere('name', 'Trà Sữa') ?? $categories->firstWhere('name', 'Trà sữa');
            $ctaImage = $ctaCategory?->image_url
                ?? asset('storage/categories/U2o2CJ5ILRKraiJJ8hXWvt1VA2YZPFmqTvnGLgTJ.png');
        @endphp
        <div class="cta-card">
            <div class="cta-content">
                <h2 class="h3 fw-bold mb-3">Đừng bỏ lỡ bất kỳ tin tức nào</h2>
                <p class="text-secondary mb-0">Đăng ký nhận tin để thành người đầu tiên biết về các sản phẩm mới và chương trình khuyến mãi độc quyền từ Chill Drink.</p>
                <form action="{{ route('products.index') }}" method="GET" class="newsletter-form">
                    <input class="form-control" type="email" name="email" placeholder="Địa chỉ email của bạn" aria-label="Địa chỉ email">
                    <button class="btn btn-primary px-4" type="submit">Đăng ký</button>
                </form>
            </div>
        </div>
    </div>
</section>
</div>

<div class="modal fade quick-add-modal" id="homeQuickAddModal" tabindex="-1" aria-labelledby="homeQuickAddTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="homeQuickAddForm" method="POST" data-ajax-cart>
                @csrf
                <input type="hidden" name="size" value="M" data-quick-size-input>
                <input type="hidden" name="sugar_level" value="50" data-quick-sugar-input>
                <input type="hidden" name="ice_level" value="100" data-quick-ice-input>
                <input type="hidden" name="toppings" value="[]" data-quick-toppings-input>
                <input type="hidden" name="quantity" value="1">

                <div class="modal-header border-0 pb-0">
                    <h2 class="modal-title h4 fw-bold" id="homeQuickAddTitle">Tùy chọn đồ uống</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>

                <div class="modal-body">
                    <div class="d-flex gap-3 align-items-center mb-4">
                        <img src="" alt="" class="quick-add-thumb" data-quick-image>
                        <div>
                            <div class="fw-bold fs-5" data-quick-name></div>
                            <div class="text-primary fw-bold" data-quick-price></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="fw-bold mb-2">Size</div>
                        <div class="d-flex flex-wrap gap-2" data-quick-group="size">
                            <button type="button" class="quick-choice" data-value="S">S</button>
                            <button type="button" class="quick-choice active" data-value="M">M</button>
                            <button type="button" class="quick-choice" data-value="L">L</button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="fw-bold mb-2">Mức đường</div>
                        <div class="d-flex flex-wrap gap-2" data-quick-group="sugar">
                            <button type="button" class="quick-choice" data-value="0">0%</button>
                            <button type="button" class="quick-choice" data-value="30">30%</button>
                            <button type="button" class="quick-choice active" data-value="50">50%</button>
                            <button type="button" class="quick-choice" data-value="70">70%</button>
                            <button type="button" class="quick-choice" data-value="100">100%</button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="fw-bold mb-2">Mức đá</div>
                        <div class="d-flex flex-wrap gap-2" data-quick-group="ice">
                            <button type="button" class="quick-choice" data-value="0">Không đá</button>
                            <button type="button" class="quick-choice" data-value="50">Ít đá</button>
                            <button type="button" class="quick-choice active" data-value="100">Bình thường</button>
                        </div>
                    </div>

                    <div>
                        <div class="fw-bold mb-2">Topping</div>
                        <div class="d-flex flex-wrap gap-2" data-quick-topping-group></div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="submit" class="btn btn-primary w-100 rounded-pill py-3 fw-bold">
                        Thêm vào giỏ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modalElement = document.getElementById('homeQuickAddModal');
        const form = document.getElementById('homeQuickAddForm');

        if (!modalElement || !form || !window.bootstrap) {
            return;
        }

        const modal = new bootstrap.Modal(modalElement);
        const fields = {
            name: modalElement.querySelector('[data-quick-name]'),
            price: modalElement.querySelector('[data-quick-price]'),
            image: modalElement.querySelector('[data-quick-image]'),
            size: modalElement.querySelector('[data-quick-size-input]'),
            sugar: modalElement.querySelector('[data-quick-sugar-input]'),
            ice: modalElement.querySelector('[data-quick-ice-input]'),
            toppings: modalElement.querySelector('[data-quick-toppings-input]'),
            toppingGroup: modalElement.querySelector('[data-quick-topping-group]'),
        };

        function setGroupValue(group, value) {
            modalElement.querySelectorAll(`[data-quick-group="${group}"] .quick-choice`).forEach((button) => {
                button.classList.toggle('active', button.dataset.value === value);
            });
        }

        function normalizeText(value) {
            return String(value || '')
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/đ/g, 'd');
        }

        function toppingOptionsFor(name, category) {
            const text = normalizeText(`${name} ${category}`);

            if (text.includes('matcha')) {
                return [['Trân châu đen', 5000], ['Kem cheese', 7000], ['Thạch matcha', 6000]];
            }

            if (text.includes('tra sua')) {
                return [['Trân châu đen', 5000], ['Pudding trứng', 7000], ['Thạch phô mai', 8000]];
            }

            if (text.includes('ca phe')) {
                return [['Kem mặn', 7000], ['Shot espresso', 10000], ['Caramel', 6000]];
            }

            if (text.includes('sinh to')) {
                return [['Hạt chia', 5000], ['Sữa chua', 7000], ['Nha đam', 6000]];
            }

            if (text.includes('nuoc ep')) {
                return [['Nha đam', 6000], ['Hạt chia', 5000], ['Soda', 7000]];
            }

            if (text.includes('soda')) {
                return [['Thạch trái cây', 6000], ['Nha đam', 6000], ['Trân châu trắng', 7000]];
            }

            return [['Trân châu trắng', 7000], ['Thạch nha đam', 6000], ['Kem cheese', 7000]];
        }

        function syncQuickToppings() {
            const toppings = Array.from(fields.toppingGroup?.querySelectorAll('.quick-topping-choice.active') || []).map((button) => ({
                name: button.dataset.toppingName || '',
                price: Number(button.dataset.toppingPrice || 0),
            }));

            fields.toppings.value = JSON.stringify(toppings);
        }

        function renderQuickToppings(name, category) {
            fields.toppingGroup.innerHTML = toppingOptionsFor(name, category).map(([toppingName, price]) => `
                <button type="button" class="quick-choice quick-topping-choice" data-topping-name="${toppingName}" data-topping-price="${price}">
                    ${toppingName}
                    <small>+${Number(price).toLocaleString('vi-VN')}đ</small>
                </button>
            `).join('');

            syncQuickToppings();
        }

        document.querySelectorAll('[data-quick-add]').forEach((button) => {
            button.addEventListener('click', () => {
                form.action = button.dataset.action || '#';
                fields.name.textContent = button.dataset.name || 'Đồ uống';
                fields.price.textContent = button.dataset.price || '';
                fields.image.src = button.dataset.image || '';
                fields.image.alt = button.dataset.name || 'Đồ uống';
                fields.size.value = 'M';
                fields.sugar.value = '50';
                fields.ice.value = '100';
                fields.toppings.value = '[]';
                setGroupValue('size', 'M');
                setGroupValue('sugar', '50');
                setGroupValue('ice', '100');
                renderQuickToppings(button.dataset.name || '', button.dataset.category || '');
                modal.show();
            });
        });

        modalElement.querySelectorAll('[data-quick-group]').forEach((group) => {
            group.addEventListener('click', (event) => {
                const button = event.target.closest('.quick-choice');

                if (!button) {
                    return;
                }

                group.querySelectorAll('.quick-choice').forEach((item) => item.classList.remove('active'));
                button.classList.add('active');

                if (group.dataset.quickGroup === 'size') {
                    fields.size.value = button.dataset.value;
                }

                if (group.dataset.quickGroup === 'sugar') {
                    fields.sugar.value = button.dataset.value;
                }

                if (group.dataset.quickGroup === 'ice') {
                    fields.ice.value = button.dataset.value;
                }
            });
        });

        fields.toppingGroup.addEventListener('click', (event) => {
            const button = event.target.closest('.quick-topping-choice');

            if (!button) {
                return;
            }

            button.classList.toggle('active');
            syncQuickToppings();
        });
    });
</script>
@endsection
