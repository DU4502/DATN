@extends('layouts.client')

@section('title', 'Trang Chủ')

@section('content')
@php extract(require resource_path('views/partials/ui-product-data.php')); @endphp
<style>
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
        padding: 5rem 0;
        background: #fff;
    }

    .product-card {
        border-radius: var(--radius-2xl);
        overflow: hidden;
        border: 1px solid var(--c-border);
        background: var(--c-surface);
        transition: all 0.3s ease;
    }

    .product-card:hover {
        transform: translateY(-6px);
        border-color: var(--c-primary);
        box-shadow: var(--shadow-xl);
    }

    .product-img-wrap {
        position: relative;
        aspect-ratio: 1;
        overflow: hidden;
        background: var(--c-bg-warm);
    }

    .product-img-wrap img {
        width: 100%; height: 100%; object-fit: cover;
        transition: transform 0.5s ease;
    }

    .product-card:hover .product-img-wrap img {
        transform: scale(1.08);
    }

    .product-badge {
        position: absolute;
        top: 1rem; left: 1rem;
        background: var(--c-surface);
        color: var(--c-primary);
        padding: 0.35rem 0.8rem;
        border-radius: var(--radius-full);
        font-weight: 700; font-size: 0.75rem;
        box-shadow: var(--shadow-sm);
        z-index: 2;
    }

    .product-rating {
        display: flex; align-items: center; gap: 4px;
        color: #F59E0B; font-size: 0.8rem;
        margin-bottom: 0.5rem;
    }

    .product-card .card-body { padding: 1.5rem; }

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
    .cta-section { padding: 5rem 0; background: var(--c-bg); }

    .cta-card {
        background: var(--c-surface);
        border-radius: 32px;
        overflow: hidden;
        border: 1px solid var(--c-border);
        box-shadow: var(--shadow-xl);
        display: flex; flex-wrap: wrap;
    }

    .cta-content { padding: 4rem; flex: 1; min-width: 300px; display: flex; flex-direction: column; justify-content: center;}
    .cta-image { flex: 1; min-width: 300px; background: var(--c-primary-light); position: relative; }
    
    .cta-image img {
        position: absolute; width: 100%; height: 100%; object-fit: cover;
    }

    @media (max-width: 767.98px) {
        .category-scroll {
            display: flex; flex-wrap: nowrap; overflow-x: auto;
            gap: 1rem; padding-bottom: 1rem; scroll-snap-type: x mandatory;
        }
        .category-scroll > div { flex: 0 0 calc(70% - 1rem); scroll-snap-align: center; }
        .cta-content { padding: 2rem; }
        .cta-image { min-height: 250px; }
    }
</style>

<x-animated-slider />

<section class="category-section">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-5">
            <div>
                <p class="section-kicker mb-2">Thực đơn</p>
                <h2 class="section-title h1 mb-0">Hương vị yêu thích</h2>
            </div>
            <a href="{{ route('products.index') }}" class="btn btn-outline-primary d-none d-md-inline-flex px-4 rounded-pill">Khám phá tất cả <i class="bi bi-arrow-right ms-2"></i></a>
        </div>

        <div class="row g-4 category-scroll">
            @forelse($categories as $category)
                <div class="col-md-4 col-lg-3">
                    <a href="{{ route('products.index', ['category' => $category->id]) }}" class="category-card">
                        <img src="{{ $uiCategoryImages[$category->name] ?? $uiDefaultImage }}" alt="{{ $category->name }}" class="category-image">
                        <div class="category-overlay">
                            <span class="category-icon"><i class="bi bi-arrow-up-right"></i></span>
                            <div class="category-title">{{ $category->name }}</div>
                            <span class="text-white opacity-75 small">Xem lựa chọn</span>
                        </div>
                    </a>
                </div>
            @empty
                @foreach([
                    ['Trà Sữa', 'https://images.unsplash.com/photo-1558857563-b371033873b8?auto=format&fit=crop&w=500&q=85', 'bi-cup-hot'],
                    ['Cà Phê', 'https://images.unsplash.com/photo-1517701550927-30cf4ba1dba5?auto=format&fit=crop&w=500&q=85', 'bi-cup'],
                    ['Nước Ép', 'https://images.unsplash.com/photo-1622597467836-f3285f2131b8?auto=format&fit=crop&w=500&q=85', 'bi-arrow-up-right'],
                    ['Sinh Tố', 'https://images.unsplash.com/photo-1621506289937-a8e4df240d0b?auto=format&fit=crop&w=500&q=85', 'bi-snow'],
                ] as $category)
                    <div class="col-md-4 col-lg-3">
                        <a href="{{ route('products.index') }}" class="category-card">
                            <img src="{{ $category[1] }}" alt="{{ $category[0] }}" class="category-image">
                            <div class="category-overlay">
                                <span class="category-icon"><i class="bi {{ $category[2] }}"></i></span>
                                <div class="category-title">{{ $category[0] }}</div>
                                <span class="text-white opacity-75 small">Xem lựa chọn</span>
                            </div>
                        </a>
                    </div>
                @endforeach
            @endforelse
        </div>
    </div>
</section>

<section id="featured-products" class="featured-section">
    <div class="container">
        <div class="text-center mb-5 max-w-2xl mx-auto" style="max-width: 600px; margin: 0 auto;">
            <p class="section-kicker mb-2">Bán chạy nhất</p>
            <h2 class="section-title h1 mb-3">Gợi ý hôm nay</h2>
            <p class="text-secondary text-lg mb-0">Những món uống được yêu thích nhất tại Chill Drink, mang đến sự tươi mới cho ngày của bạn.</p>
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
                        ->values()
                    : (clone $homeProductQuery)
                        ->where('status', true)
                        ->latest()
                        ->limit(8)
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
                            <div class="mt-auto d-flex align-items-center justify-content-between">
                                <strong class="text-primary h5 mb-0">{{ number_format($product->price, 0, ',', '.') }}đ</strong>
                                <a href="{{ route('products.show', $product->slug) }}" class="product-cart-btn" aria-label="Xem chi tiết {{ $product->name }}">
                                    <i class="bi bi-bag-plus fs-5"></i>
                                </a>
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
                            </div>
                            <div class="card-body d-flex flex-column flex-grow-1">
                                <div class="product-rating">
                                    <i class="bi bi-star text-secondary"></i>
                                    <span class="text-secondary ms-1">Chưa có đánh giá</span>
                                </div>
                                <h3 class="h5 fw-bold mb-3">
                                    <a href="{{ route('products.show', $item[4]) }}" class="text-dark text-decoration-none">{{ $item[0] }}</a>
                                </h3>
                                <div class="mt-auto d-flex align-items-center justify-content-between">
                                    <strong class="text-primary h5 mb-0">{{ $item[1] }}</strong>
                                    <a href="{{ route('products.show', $item[4]) }}" class="product-cart-btn" aria-label="Xem chi tiết {{ $item[0] }}">
                                        <i class="bi bi-bag-plus fs-5"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endforelse
        </div>
        
        <div class="text-center mt-5 pt-3">
            <a href="{{ route('products.index') }}" class="btn btn-outline-primary px-5 py-2 rounded-pill fw-bold">Xem toàn bộ menu</a>
        </div>
    </div>
</section>

<section class="feature-band">
    <div class="container position-relative z-1">
        <div class="row g-4 g-lg-5">
            <div class="col-md-4">
                <div class="feature-item h-100">
                    <div class="feature-icon-lg"><i class="bi bi-rocket-takeoff"></i></div>
                    <h3 class="h4 fw-bold text-white mb-3">Giao Hàng Siêu Tốc</h3>
                    <p class="text-white-50 mb-0">Cam kết giao hàng trong 30 phút. Đồ uống luôn tươi mát và chuẩn vị khi tới tay bạn.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-item h-100">
                    <div class="feature-icon-lg"><i class="bi bi-cup-hot"></i></div>
                    <h3 class="h4 fw-bold text-white mb-3">Nguyên Liệu Tươi Sạch</h3>
                    <p class="text-white-50 mb-0">Sử dụng trái cây tươi mỗi ngày và trà/cà phê chọn lọc kỹ càng, đảm bảo an toàn.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-item h-100">
                    <div class="feature-icon-lg"><i class="bi bi-shield-check"></i></div>
                    <h3 class="h4 fw-bold text-white mb-3">Thanh Toán An Toàn</h3>
                    <p class="text-white-50 mb-0">Hỗ trợ đa dạng phương thức thanh toán, tiện lợi và bảo mật tuyệt đối thông tin.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="cta-section">
    <div class="container">
        <div class="cta-card">
            <div class="cta-content">
                <p class="section-kicker mb-2">Ưu đãi thành viên</p>
                <h2 class="display-6 fw-bold mb-3">Tham gia cộng đồng <br>Chill Drink</h2>
                <p class="text-secondary mb-4 fs-5">Đăng ký thành viên ngay hôm nay để nhận voucher giảm 20% cho đơn hàng đầu tiên và tích điểm đổi quà.</p>
                <div class="d-flex flex-wrap gap-3">
                    @guest
                        <a href="{{ route('register') }}" class="btn btn-primary btn-lg rounded-pill px-4">Đăng ký ngay</a>
                    @else
                        <a href="{{ route('products.index') }}" class="btn btn-primary btn-lg rounded-pill px-4">Đặt hàng ngay</a>
                    @endguest
                </div>
            </div>
            <div class="cta-image">
                <img src="https://images.unsplash.com/photo-1544145945-f90425340c7e?auto=format&fit=crop&w=1000&q=85" alt="Summer drinks">
            </div>
        </div>
    </div>
</section>
@endsection
