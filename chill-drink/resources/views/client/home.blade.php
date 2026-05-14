@extends('layouts.client')

@section('title', 'Trang Chủ')

@section('content')
<style>
    .home-hero {
        position: relative;
        overflow: hidden;
        background: #086972;
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
        background: rgba(255, 183, 3, 0.18);
    }

    .hero-content {
        position: relative;
        z-index: 1;
    }

    .hero-carousel .carousel-item {
        min-height: 650px;
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
        background: linear-gradient(115deg, rgba(5, 52, 58, 0.92), rgba(15, 139, 141, 0.82) 50%, rgba(59, 214, 181, 0.72));
        z-index: 1;
    }

    .hero-slide-image {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transform: scale(1.02) translateZ(0);
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
        height: 220px;
        object-fit: cover;
    }

    .feature-icon {
        width: 54px;
        height: 54px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 18px;
        background: linear-gradient(135deg, rgba(15, 139, 141, 0.12), rgba(255, 183, 3, 0.18));
        color: var(--drink-primary);
        font-weight: 800;
    }

    .promo-card {
        background: rgba(255, 255, 255, 0.94);
        border: 1px solid rgba(255, 255, 255, 0.6);
        border-radius: 28px;
        box-shadow: 0 28px 70px rgba(7, 52, 58, 0.24);
        backdrop-filter: blur(16px);
    }

    .promo-pill {
        background: #fff7df;
        color: #8a5a00;
        border-radius: 999px;
        padding: 0.35rem 0.75rem;
        font-weight: 800;
        font-size: 0.8rem;
    }

    .category-card {
        min-height: 150px;
    }

    .feature-band {
        background:
            radial-gradient(circle at 15% 15%, rgba(255, 183, 3, 0.12), transparent 20rem),
            linear-gradient(180deg, #ffffff, #f2fbf8);
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
                    <div class="row align-items-center g-5" style="min-height: 650px;">
                        <div class="col-lg-7 hero-copy">
                            <span class="badge hero-badge rounded-pill px-3 py-2 mb-3">Pha tươi mỗi ngày</span>
                            <h1 class="display-3 fw-bold mb-3">Đồ uống mát lạnh giao tới bạn trong tích tắc</h1>
                            <p class="lead mb-4">Trà sữa, cà phê, nước ép và trà trái cây được tuyển chọn gọn gàng để bạn tìm nhanh, chọn dễ và đặt hàng thật nhẹ.</p>
                            <div class="d-flex flex-wrap gap-3">
                                <a href="{{ route('products.index') }}" class="btn btn-light btn-lg text-primary fw-bold">Đặt ngay</a>
                                <a href="#featured-products" class="btn btn-outline-light btn-lg">Xem món nổi bật</a>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="promo-card text-dark p-4 p-md-5">
                                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                    <div>
                                        <span class="promo-pill">Best combo</span>
                                        <h2 class="h3 fw-bold mt-3 mb-2">Ưu đãi hôm nay</h2>
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
                    <div class="row align-items-center g-5" style="min-height: 650px;">
                        <div class="col-lg-7 hero-copy">
                            <span class="badge hero-badge rounded-pill px-3 py-2 mb-3">Trái cây tươi</span>
                            <h1 class="display-3 fw-bold mb-3">Nước ép và trà trái cây cho ngày thật nhẹ</h1>
                            <p class="lead mb-4">Vị chua ngọt cân bằng, màu sắc bắt mắt và menu rõ ràng để bạn chọn món hợp tâm trạng.</p>
                            <div class="d-flex flex-wrap gap-3">
                                <a href="{{ route('products.index', ['category' => 4]) }}" class="btn btn-light btn-lg text-primary fw-bold">Xem nước ép</a>
                                <a href="{{ route('products.index') }}" class="btn btn-outline-light btn-lg">Mở menu</a>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="promo-card text-dark p-4 p-md-5">
                                <span class="promo-pill">Fresh pick</span>
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
                    <div class="row align-items-center g-5" style="min-height: 650px;">
                        <div class="col-lg-7 hero-copy">
                            <span class="badge hero-badge rounded-pill px-3 py-2 mb-3">Cà phê & trà sữa</span>
                            <h1 class="display-3 fw-bold mb-3">Đậm vị, đẹp mắt, sẵn sàng để thêm vào giỏ</h1>
                            <p class="lead mb-4">Các món được trình bày bằng card rõ giá, rõ danh mục, giúp trải nghiệm mua hàng nhanh và dễ chịu hơn.</p>
                            <div class="d-flex flex-wrap gap-3">
                                <a href="{{ route('products.index', ['category' => 2]) }}" class="btn btn-light btn-lg text-primary fw-bold">Xem cà phê</a>
                                <a href="{{ route('cart.index') }}" class="btn btn-outline-light btn-lg">Tới giỏ hàng</a>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="promo-card text-dark p-4 p-md-5">
                                <span class="promo-pill">Quick order</span>
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
                    <a href="{{ route('products.index', ['category' => $category->id]) }}" class="text-decoration-none">
                        <div class="category-card drink-card bg-white p-4 text-center h-100">
                            <div class="feature-icon mx-auto mb-3">{{ mb_substr($category->name, 0, 1) }}</div>
                            <h3 class="h6 text-dark mb-0">{{ $category->name }}</h3>
                        </div>
                    </a>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-info mb-0">Chưa có danh mục sản phẩm.</div>
                </div>
            @endforelse
        </div>
    </div>
</section>

<section id="featured-products" class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <p class="section-kicker mb-1">Sản phẩm</p>
            <h2 class="section-title h1 mb-2">Nổi bật trong ngày</h2>
            <p class="text-secondary mb-0">Các món đang được khách hàng chọn nhiều nhất.</p>
        </div>

        <div class="row g-4">
            @forelse($featuredProducts as $product)
                <div class="col-sm-6 col-lg-3">
                    <div class="product-card drink-card card h-100 overflow-hidden border-0">
                        <a href="{{ route('products.show', $product->slug) }}">
                            <img src="{{ $product->image }}" class="card-img-top" alt="{{ $product->name }}">
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
                                <form action="{{ route('cart.add', $product->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm">Thêm</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-info mb-0">Chưa có sản phẩm nổi bật.</div>
                </div>
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
