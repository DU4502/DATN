@extends('layouts.client')

@section('title', 'Trang Chủ')

@section('content')
<style>
    .home-hero {
        position: relative;
        overflow: hidden;
        background: #043d32;
        color: #ffffff;
    }

    .home-hero::after {
        content: "";
        position: absolute;
        right: -8rem;
        bottom: -10rem;
        width: 34rem;
        height: 34rem;
        border-radius: 50%;
        background: rgba(184, 234, 223, 0.26);
    }

    .hero-content {
        position: relative;
        z-index: 1;
    }

    .hero-carousel .carousel-item {
        min-height: 540px;
        overflow: hidden;
        transition: opacity 1.45s cubic-bezier(0.4, 0, 0.2, 1) !important;
        transform: translateZ(0);
        backface-visibility: hidden;
        will-change: opacity;
    }

    .hero-carousel .carousel-item-next,
    .hero-carousel .carousel-item-prev,
    .hero-carousel .carousel-item.active {
        display: block;
    }

    .hero-carousel .carousel-item::before {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, rgba(0, 31, 25, 0.90), rgba(0, 58, 48, 0.62) 48%, rgba(12, 77, 55, 0.25));
        z-index: 1;
    }

    .hero-slide-image {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transform: scale(1.01) translateZ(0);
        backface-visibility: hidden;
    }

    .hero-carousel .carousel-item.active .hero-copy {
        animation: heroCopyIn 0.85s ease both;
    }

    .hero-carousel .carousel-indicators {
        bottom: 28px;
        margin-bottom: 0;
    }

    .hero-carousel .carousel-indicators [data-bs-target] {
        width: 34px;
        height: 7px;
        border: 0;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.55);
    }

    .hero-carousel .carousel-indicators .active {
        background: #ffffff;
    }

    .hero-carousel .carousel-control-prev,
    .hero-carousel .carousel-control-next {
        width: 7%;
    }

    @keyframes heroCopyIn {
        from {
            opacity: 0;
            transform: translateY(18px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .hero-badge {
        background: rgba(255, 255, 255, 0.16);
        border: 1px solid rgba(255, 255, 255, 0.28);
        color: #ffffff;
        backdrop-filter: blur(12px);
    }

    .product-card img {
        height: 285px;
        object-fit: cover;
    }

    .feature-icon {
        width: 54px;
        height: 54px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 18px;
        background: linear-gradient(135deg, rgba(79, 183, 168, 0.13), rgba(184, 234, 223, 0.26));
        color: var(--drink-primary);
        font-weight: 800;
    }

    .promo-card {
        background: rgba(255, 255, 255, 0.94);
        border: 1px solid rgba(255, 255, 255, 0.72);
        border-radius: 22px;
        box-shadow: 0 28px 70px rgba(7, 52, 58, 0.24);
        backdrop-filter: blur(16px);
    }

    .promo-pill {
        background: #edf9f6;
        color: #2f8f83;
        border-radius: 999px;
        padding: 0.35rem 0.75rem;
        font-weight: 800;
        font-size: 0.8rem;
    }

    .category-card {
        display: block;
        color: var(--drink-ink);
        border-radius: 20px;
        transform: translateY(0);
        transition: transform 0.24s ease, color 0.24s ease;
    }

    .category-image {
        aspect-ratio: 1 / 1;
        width: 100%;
        border-radius: 18px;
        object-fit: cover;
        box-shadow: 0 18px 35px rgba(7, 52, 58, 0.16);
        border: 2px solid transparent;
        transition: transform 0.28s ease, box-shadow 0.28s ease, border-color 0.28s ease, filter 0.28s ease;
    }

    .category-title {
        font-size: 1.15rem;
        font-weight: 700;
        margin-top: 0.9rem;
        text-align: center;
        transition: color 0.24s ease, transform 0.24s ease;
    }

    .category-card:hover {
        color: var(--drink-primary);
        transform: translateY(-8px);
    }

    .category-card:hover .category-image {
        border-color: rgba(0, 139, 122, 0.45);
        box-shadow: 0 26px 50px rgba(0, 107, 95, 0.22);
        filter: saturate(1.12) contrast(1.03);
        transform: scale(1.035);
    }

    .category-card:hover .category-title {
        color: var(--drink-primary);
        transform: translateY(2px);
    }

    .home-section-soft {
        background: #eaf8f5;
    }

    .product-card {
        border-radius: 18px;
    }

    .product-card .card-body {
        padding: 1.35rem;
    }

    .product-cart-btn {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .product-detail-btn {
        text-decoration: none;
    }

    .feature-band {
        background: #edf9f6;
    }

    .feature-card {
        min-height: 260px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
    }

    @media (max-width: 991.98px) {
        .hero-carousel .carousel-item,
        .hero-carousel .row {
            min-height: 620px !important;
        }
    }

    @media (max-width: 575.98px) {
        .product-card img {
            height: 230px;
        }
    }
</style>

<section class="home-hero">
    <div id="drinkHeroCarousel" class="carousel slide carousel-fade hero-carousel" data-bs-interval="5200" data-bs-pause="hover">
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#drinkHeroCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
            <button type="button" data-bs-target="#drinkHeroCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
            <button type="button" data-bs-target="#drinkHeroCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
        </div>

        <div class="carousel-inner">
            <div class="carousel-item active">
                <img class="hero-slide-image" src="https://images.unsplash.com/photo-1544145945-f90425340c7e?auto=format&fit=crop&w=1800&q=85" alt="Đồ uống mát lạnh" fetchpriority="high">
                <div class="container hero-content py-5">
                    <div class="row align-items-center g-5" style="min-height: 540px;">
                        <div class="col-lg-7 hero-copy">
                            <span class="badge hero-badge rounded-pill px-3 py-2 mb-3">Pha tươi mỗi ngày</span>
                            <h1 class="display-4 fw-bold mb-3">Đồ uống mát lạnh giao tới bạn trong tích tắc</h1>
                            <p class="lead mb-4">Trà sữa, cà phê, nước ép và trà trái cây được tuyển chọn gọn gàng để bạn tìm nhanh, chọn dễ và đặt hàng thật nhẹ.</p>
                            <div class="d-flex flex-wrap gap-3">
                                <a href="{{ route('products.index') }}" class="btn btn-light btn-lg text-primary fw-bold">Đặt ngay</a>
                                <a href="#featured-products" class="btn btn-outline-light btn-lg">Xem gợi ý hôm nay</a>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="promo-card text-dark p-4 p-md-5">
                                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                    <div>
                                        <span class="promo-pill">Combo nổi bật</span>
                                        <h2 class="h3 fw-bold mt-3 mb-2">Combo tiết kiệm</h2>
                                    </div>
                                    <span class="display-6 fw-bold text-primary">-20%</span>
                                </div>
                                <p class="text-secondary mb-4">Combo trà trái cây, cà phê sữa và nước ép được cập nhật theo ngày.</p>
                                <div class="row g-3 text-center">
                                    <div class="col-4">
                                        <div class="h3 fw-bold text-primary mb-0">30'</div>
                                        <small class="text-secondary">Giao nhanh</small>
                                    </div>
                                    <div class="col-4">
                                        <div class="h3 fw-bold text-primary mb-0">8+</div>
                                        <small class="text-secondary">Danh mục</small>
                                    </div>
                                    <div class="col-4">
                                        <div class="h3 fw-bold text-primary mb-0">24/7</div>
                                        <small class="text-secondary">Đặt hàng</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="carousel-item">
                <img class="hero-slide-image" src="https://images.unsplash.com/photo-1551024709-8f23befc6f87?auto=format&fit=crop&w=1800&q=85" alt="Nước ép trái cây" loading="eager">
                <div class="container hero-content py-5">
                    <div class="row align-items-center g-5" style="min-height: 540px;">
                        <div class="col-lg-7 hero-copy">
                            <span class="badge hero-badge rounded-pill px-3 py-2 mb-3">Trái cây tươi</span>
                            <h1 class="display-4 fw-bold mb-3">Nước ép và trà trái cây cho ngày thật nhẹ</h1>
                            <p class="lead mb-4">Vị chua ngọt cân bằng, màu sắc bắt mắt và menu rõ ràng để bạn chọn món hợp tâm trạng.</p>
                            <div class="d-flex flex-wrap gap-3">
                                <a href="{{ route('products.index', ['category' => 4]) }}" class="btn btn-light btn-lg text-primary fw-bold">Xem nước ép</a>
                                <a href="{{ route('products.index') }}" class="btn btn-outline-light btn-lg">Mở menu</a>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="promo-card text-dark p-4 p-md-5">
                                <span class="promo-pill">Gợi ý tươi mát</span>
                                <h2 class="h3 fw-bold mt-3 mb-3">Mát lành tự nhiên</h2>
                                <p class="text-secondary mb-0">Phù hợp cho bữa trưa, buổi học, giờ làm hoặc những lúc cần nạp lại năng lượng.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="carousel-item">
                <img class="hero-slide-image" src="https://images.unsplash.com/photo-1517701604599-bb29b565090c?auto=format&fit=crop&w=1800&q=85" alt="Cà phê và trà sữa" loading="eager">
                <div class="container hero-content py-5">
                    <div class="row align-items-center g-5" style="min-height: 540px;">
                        <div class="col-lg-7 hero-copy">
                            <span class="badge hero-badge rounded-pill px-3 py-2 mb-3">Cà phê & trà sữa</span>
                            <h1 class="display-4 fw-bold mb-3">Đậm vị, đẹp mắt, sẵn sàng để thêm vào giỏ</h1>
                            <p class="lead mb-4">Các món được trình bày bằng card rõ giá, rõ danh mục, giúp trải nghiệm mua hàng nhanh và dễ chịu hơn.</p>
                            <div class="d-flex flex-wrap gap-3">
                                <a href="{{ route('products.index', ['category' => 2]) }}" class="btn btn-light btn-lg text-primary fw-bold">Xem cà phê</a>
                                <a href="{{ route('cart.index') }}" class="btn btn-outline-light btn-lg">Tới giỏ hàng</a>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="promo-card text-dark p-4 p-md-5">
                                <span class="promo-pill">Đặt nhanh</span>
                                <h2 class="h3 fw-bold mt-3 mb-3">Đặt nhanh hơn</h2>
                                <p class="text-secondary mb-0">Tìm kiếm, chọn món, thêm giỏ và thanh toán trong vài thao tác gọn gàng.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <button class="carousel-control-prev" type="button" data-bs-target="#drinkHeroCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Trước</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#drinkHeroCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Sau</span>
        </button>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const hero = document.getElementById('drinkHeroCarousel');
        if (!hero || !window.bootstrap) {
            return;
        }

        const images = Array.from(hero.querySelectorAll('.hero-slide-image')).map((image) => image.src);
        Promise.all(images.map((src) => new Promise((resolve) => {
            const image = new Image();
            image.onload = resolve;
            image.onerror = resolve;
            image.src = src;
        }))).then(() => {
            bootstrap.Carousel.getOrCreateInstance(hero, {
                interval: 5200,
                ride: 'carousel',
                pause: 'hover',
                touch: true,
                wrap: true
            });
        });
    });
</script>

<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <p class="section-kicker mb-1">Danh mục</p>
                <h2 class="section-title h1 mb-0">Chọn đồ uống yêu thích</h2>
            </div>
            <a href="{{ route('products.index') }}" class="btn btn-outline-primary d-none d-md-inline-flex">Xem tất cả</a>
        </div>

        <div class="row g-4">
            @forelse($categories as $category)
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="{{ route('products.index', ['category' => $category->id]) }}" class="category-card text-decoration-none">
                        <img
                            src="{{ [
                                'https://images.unsplash.com/photo-1558857563-b371033873b8?auto=format&fit=crop&w=500&q=85',
                                'https://images.unsplash.com/photo-1517701550927-30cf4ba1dba5?auto=format&fit=crop&w=500&q=85',
                                'https://images.unsplash.com/photo-1622597467836-f3285f2131b8?auto=format&fit=crop&w=500&q=85',
                                'https://images.unsplash.com/photo-1621506289937-a8e4df240d0b?auto=format&fit=crop&w=500&q=85',
                                'https://images.unsplash.com/photo-1556679343-c7306c1976bc?auto=format&fit=crop&w=500&q=85',
                                'https://images.unsplash.com/photo-1570197788417-0e82375c9371?auto=format&fit=crop&w=500&q=85',
                            ][$loop->index % 6] }}"
                            alt="{{ $category->name }}"
                            class="category-image"
                        >
                        <div class="category-title">{{ $category->name }}</div>
                    </a>
                </div>
            @empty
                @foreach([
                    ['Trà Sữa', 'https://images.unsplash.com/photo-1558857563-b371033873b8?auto=format&fit=crop&w=500&q=85'],
                    ['Cà Phê', 'https://images.unsplash.com/photo-1517701550927-30cf4ba1dba5?auto=format&fit=crop&w=500&q=85'],
                    ['Nước Ép', 'https://images.unsplash.com/photo-1622597467836-f3285f2131b8?auto=format&fit=crop&w=500&q=85'],
                    ['Trà Trái Cây', 'https://images.unsplash.com/photo-1556679343-c7306c1976bc?auto=format&fit=crop&w=500&q=85'],
                    ['Sinh Tố', 'https://images.unsplash.com/photo-1621506289937-a8e4df240d0b?auto=format&fit=crop&w=500&q=85'],
                    ['Khác', 'https://images.unsplash.com/photo-1570197788417-0e82375c9371?auto=format&fit=crop&w=500&q=85'],
                ] as $category)
                    <div class="col-6 col-md-4 col-lg-2">
                        <a href="{{ route('products.index') }}" class="category-card text-decoration-none">
                            <img src="{{ $category[1] }}" alt="{{ $category[0] }}" class="category-image">
                            <div class="category-title">{{ $category[0] }}</div>
                        </a>
                    </div>
                @endforeach
            @endforelse
        </div>
    </div>
</section>

<section id="featured-products" class="home-section-soft py-5">
    <div class="container">
        <div class="text-center mb-5">
            <p class="section-kicker mb-1">Sản phẩm</p>
            <h2 class="section-title h1 mb-2">Gợi ý đồ uống hôm nay</h2>
            <p class="text-secondary mb-0">Những món dễ uống, hợp vị và được chọn nhiều trong menu.</p>
        </div>

        <div class="row g-4">
            @forelse($featuredProducts as $product)
                <div class="col-sm-6 col-lg-3">
                    <div class="product-card drink-card card h-100 overflow-hidden border-0">
                        <a href="{{ route('products.show', $product->slug) }}">
                            <img src="{{ $product->image ?: 'https://images.unsplash.com/photo-1544145945-f90425340c7e?auto=format&fit=crop&w=700&q=85' }}" class="card-img-top" alt="{{ $product->name }}">
                        </a>
                        <div class="card-body d-flex flex-column">
                            <span class="badge rounded-pill align-self-start mb-2" style="background: var(--drink-soft); color: var(--drink-primary);">{{ $product->category->name }}</span>
                            <h3 class="h5 card-title">
                                <a href="{{ route('products.show', $product->slug) }}" class="text-dark text-decoration-none">
                                    {{ $product->name }}
                                </a>
                            </h3>
                            <div class="mt-auto d-flex align-items-center justify-content-between gap-3">
                                <strong class="text-primary">{{ number_format($product->price, 0, ',', '.') }}đ</strong>
                                <a href="{{ route('products.show', $product->slug) }}" class="btn btn-primary product-cart-btn product-detail-btn" aria-label="Xem chi tiết {{ $product->name }}" title="Xem chi tiết">
                                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 12a2.25 2.25 0 1 0 4.5 0 2.25 2.25 0 0 0-4.5 0Z" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                @foreach([
                    ['Matcha Latte', '45.000đ', 'https://images.unsplash.com/photo-1515823064-d6e0c04616a7?auto=format&fit=crop&w=700&q=85', 'Mới'],
                    ['Trà Dâu Dứa', '38.000đ', 'https://images.unsplash.com/photo-1551024709-8f23befc6f87?auto=format&fit=crop&w=700&q=85', 'Bán chạy'],
                    ['Bạc Xỉu Đá', '29.000đ', 'https://images.unsplash.com/photo-1517701604599-bb29b565090c?auto=format&fit=crop&w=700&q=85', ''],
                    ['Nước Chanh Bạc Hà', '35.000đ', 'https://images.unsplash.com/photo-1544145945-f90425340c7e?auto=format&fit=crop&w=700&q=85', 'Combo mát lạnh'],
                ] as $item)
                    <div class="col-sm-6 col-lg-3">
                        <div class="product-card drink-card card h-100 overflow-hidden border-0">
                            <div class="position-relative">
                                @if($item[3])
                                    <span class="badge rounded-pill position-absolute top-0 start-0 m-3" style="background: var(--drink-primary);">{{ $item[3] }}</span>
                                @endif
                                <img src="{{ $item[2] }}" class="card-img-top" alt="{{ $item[0] }}">
                            </div>
                            <div class="card-body d-flex flex-column">
                                <h3 class="h5 card-title mb-3">{{ $item[0] }}</h3>
                                <div class="mt-auto d-flex align-items-center justify-content-between gap-3">
                                    <strong class="text-primary">{{ $item[1] }}</strong>
                                    <a href="{{ route('products.index') }}" class="btn btn-primary product-cart-btn product-detail-btn" aria-label="Xem chi tiết {{ $item[0] }}" title="Xem chi tiết">
                                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 12a2.25 2.25 0 1 0 4.5 0 2.25 2.25 0 0 0-4.5 0Z" />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endforelse
        </div>
    </div>
</section>

<section class="feature-band py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-card drink-card bg-white p-4 h-100">
                    <div class="feature-icon mb-3">1</div>
                    <h3 class="h5 fw-bold">Giao hàng nhanh</h3>
                    <p class="text-secondary mb-0">Nhận đơn nhanh, chuẩn bị gọn và giao tới bạn khi đồ uống còn ngon.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card drink-card bg-white p-4 h-100">
                    <div class="feature-icon mb-3">2</div>
                    <h3 class="h5 fw-bold">Dễ tìm dễ chọn</h3>
                    <p class="text-secondary mb-0">Danh mục rõ ràng, tìm kiếm nhanh, card sản phẩm dễ xem trên mọi màn hình.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card drink-card bg-white p-4 h-100">
                    <div class="feature-icon mb-3">3</div>
                    <h3 class="h5 fw-bold">Thanh toán tiện</h3>
                    <p class="text-secondary mb-0">Giỏ hàng, đăng nhập và thanh toán được sắp xếp gọn để thao tác ít bước hơn.</p>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
