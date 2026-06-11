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

    /* ─── Categories ─── */
    .category-section {
        position: relative;
        padding: 5rem 0;
        background: linear-gradient(180deg, var(--c-bg) 0%, #fff 100%);
    }

    .category-card {
        position: relative;
        display: block;
        border-radius: var(--radius-2xl);
        overflow: hidden;
        text-decoration: none;
        background: var(--c-surface);
        box-shadow: var(--shadow-sm);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1.5px solid transparent;
        aspect-ratio: 4/5;
    }

    .category-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .category-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(0deg, rgba(17, 24, 39, 0.85) 0%, rgba(17, 24, 39, 0) 50%);
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        padding: 1.5rem;
        transition: background 0.3s ease;
    }

    .category-card:hover {
        transform: translateY(-8px);
        border-color: var(--c-primary);
        box-shadow: var(--shadow-xl), var(--shadow-glow);
    }

    .category-card:hover .category-image {
        transform: scale(1.08);
    }

    .category-card:hover .category-overlay {
        background: linear-gradient(0deg, var(--c-primary-dark) 0%, rgba(17, 24, 39, 0) 60%);
    }

    .category-title {
        color: #fff;
        font-size: 1.25rem;
        font-weight: 800;
        margin-bottom: 0.25rem;
        transition: transform 0.3s ease;
    }

    .category-icon {
        width: 40px; height: 40px;
        display: inline-flex; align-items: center; justify-content: center;
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(8px);
        border-radius: 50%;
        color: #fff; font-size: 1.25rem;
        margin-bottom: auto; align-self: flex-end;
        transition: transform 0.3s ease;
    }

    .category-card:hover .category-icon {
        transform: scale(1.1) rotate(15deg);
        background: var(--c-primary);
    }
    
    .category-card:hover .category-title {
        transform: translateX(4px);
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

    .product-card h3 a {
        color: var(--c-ink) !important;
    }

    .product-cart-btn {
        width: 44px; height: 44px;
        border-radius: var(--radius-full);
        padding: 0; display: inline-flex; align-items: center; justify-content: center;
        background: var(--c-primary-light); color: var(--c-primary);
        border: 0; transition: all 0.2s ease;
    }

    .product-card:hover .product-cart-btn, .product-cart-btn:hover {
        background: var(--c-primary); color: #fff;
        transform: scale(1.1);
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

    .product-add-label {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.45rem;
        width: 100%;
        margin-top: 1rem;
        padding: 0.48rem 0.75rem;
        border: 1.5px solid var(--c-primary);
        border-radius: var(--radius-sm);
        color: var(--c-primary);
        font-size: 0.78rem;
        font-weight: 800;
        text-decoration: none;
        transition: all 0.25s ease;
    }

    .product-card:hover .product-add-label,
    .product-add-label:hover {
        background: var(--c-primary);
        color: #fff;
        transform: translateY(-1px);
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
        .category-scroll {
            display: flex; flex-wrap: nowrap; overflow-x: auto;
            gap: 1rem; padding-bottom: 1rem; scroll-snap-type: x mandatory;
        }
        .category-scroll > div { flex: 0 0 calc(70% - 1rem); scroll-snap-align: center; }
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
                                <a href="{{ route('products.show', $product->slug) }}" class="text-dark text-decoration-none">{{ $product->name }}</a>
                            </h3>
                            @if(!empty($product->sku))
                                <p class="text-muted small font-monospace mb-3">{{ $product->sku }}</p>
                            @else
                                <div class="mb-3"></div>
                            @endif
                            <div class="mt-auto">
                                <strong class="text-primary h5 mb-0">{{ number_format($product->price, 0, ',', '.') }}đ</strong>
                            </div>
                            <a href="{{ route('products.show', $product->slug) }}" class="product-add-label">
                                <i class="bi bi-cart-plus"></i>
                                Thêm vào giỏ
                            </a>
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
                            </div>
                            <div class="card-body d-flex flex-column flex-grow-1">
                                <div class="product-rating">
                                    <i class="bi bi-star text-secondary"></i>
                                    <span class="text-secondary ms-1">Chưa có đánh giá</span>
                                </div>
                                <h3 class="h5 fw-bold mb-3">
                                    <a href="{{ route('products.show', $item[4]) }}" class="text-dark text-decoration-none">{{ $item[0] }}</a>
                                </h3>
                                <div class="mt-auto">
                                    <strong class="text-primary h5 mb-0">{{ $item[1] }}</strong>
                                </div>
                                <a href="{{ route('products.show', $item[4]) }}" class="product-add-label">
                                    <i class="bi bi-cart-plus"></i>
                                    Thêm vào giỏ
                                </a>
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
@endsection
