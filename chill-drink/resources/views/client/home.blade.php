@extends('layouts.client')

@section('title', 'Trang Chủ')

@section('content')
@php extract(require resource_path('views/partials/ui-product-data.php')); @endphp
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

    @media (max-width: 575.98px) {
        .product-card img {
            height: 230px;
        }
    }
</style>

<x-animated-slider />

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
                            src="{{ $uiCategoryImages[$category->name] ?? $uiDefaultImage }}"
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
            @php
                $homeFeaturedSkus = $uiHomeFeaturedSkus ?? [
                    'CD-TS-001', 'CD-CF-001', 'CD-ST-001', 'CD-NE-001',
                    'CD-TC-001', 'CD-SD-001', 'CD-TS-002', 'CD-CF-002',
                ];
                $homeHasSkuColumn = \Illuminate\Support\Facades\Schema::hasColumn('products', 'sku');
                $homeFeaturedProducts = $homeHasSkuColumn
                    ? \App\Models\Product::with('category')
                        ->whereIn('sku', $homeFeaturedSkus)
                        ->get()
                        ->sortBy(fn ($product) => array_search($product->sku, $homeFeaturedSkus, true))
                        ->values()
                    : \App\Models\Product::with('category')
                        ->where('status', true)
                        ->latest()
                        ->limit(8)
                        ->get();
            @endphp
            @forelse($homeFeaturedProducts as $product)
                <div class="col-sm-6 col-lg-3">
                    <div class="product-card drink-card card h-100 overflow-hidden border-0">
                        <a href="{{ route('products.show', $product->slug) }}" class="d-block">
                            <x-product-image
                                :src="$product->image_url"
                                :sku="$product->sku ?? null"
                                :name="$product->name"
                                :alt="$product->name"
                                :category="$product->category?->name"
                                class="card-img-top"
                                style="aspect-ratio: 4/3;"
                            />
                        </a>
                        <div class="card-body d-flex flex-column">
                            <span class="badge rounded-pill align-self-start mb-2" style="background: var(--drink-soft); color: var(--drink-primary);">{{ $product->category->name }}</span>
                            @if(!empty($product->sku))
                                <p class="text-secondary small font-monospace mb-1">{{ $product->sku }}</p>
                            @endif
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
                    ['Matcha Latte', '45.000đ', 'https://images.unsplash.com/photo-1515823064-d6e0c04616a7?auto=format&fit=crop&w=700&q=85', 'Mới', 'matcha-latte-da'],
                    ['Trà Dâu Dứa', '38.000đ', 'https://images.unsplash.com/photo-1551024709-8f23befc6f87?auto=format&fit=crop&w=700&q=85', 'Bán chạy', 'tropical-frost'],
                    ['Bạc Xỉu Đá', '29.000đ', 'https://images.unsplash.com/photo-1517701604599-bb29b565090c?auto=format&fit=crop&w=700&q=85', '', 'ca-phe-sua-da'],
                    ['Nước Chanh Bạc Hà', '35.000đ', 'https://images.unsplash.com/photo-1544145945-f90425340c7e?auto=format&fit=crop&w=700&q=85', 'Combo mát lạnh', 'citrus-sunset'],
                    ['Trà Sữa Trân Châu Đường Đen', '75.450đ', 'https://images.unsplash.com/photo-1558857563-b371033873b8?auto=format&fit=crop&w=700&q=85', 'Đậm vị', 'tra-sua-tran-chau-duong-den'],
                    ['Cà Phê Sữa Đá', '24.971đ', 'https://images.unsplash.com/photo-1517701550927-30cf4ba1dba5?auto=format&fit=crop&w=700&q=85', 'Buổi sáng', 'ca-phe-sua-da'],
                    ['Sinh Tố Dâu', '45.000đ', 'https://images.unsplash.com/photo-1553530666-ba11a7da3888?auto=format&fit=crop&w=700&q=85', 'Trái cây', 'sinh-to-dau'],
                    ['Trà Trái Cây Nhiệt Đới', '59.000đ', 'https://images.unsplash.com/photo-1622597467836-f3285f2131b8?auto=format&fit=crop&w=700&q=85', 'Tươi mát', 'tropical-frost'],
                ] as $item)
                    <div class="col-sm-6 col-lg-3">
                        <div class="product-card drink-card card h-100 overflow-hidden border-0">
                            <a href="{{ route('products.show', $item[4]) }}" class="position-relative d-block text-decoration-none">
                                @if($item[3])
                                    <span class="badge rounded-pill position-absolute top-0 start-0 m-3" style="background: var(--drink-primary);">{{ $item[3] }}</span>
                                @endif
                                <img src="{{ $item[2] }}" class="card-img-top" alt="{{ $item[0] }}">
                            </a>
                            <div class="card-body d-flex flex-column">
                                <h3 class="h5 card-title mb-3">
                                    <a href="{{ route('products.show', $item[4]) }}" class="text-dark text-decoration-none">{{ $item[0] }}</a>
                                </h3>
                                <div class="mt-auto d-flex align-items-center justify-content-between gap-3">
                                    <strong class="text-primary">{{ $item[1] }}</strong>
                                    <a href="{{ route('products.show', $item[4]) }}" class="btn btn-primary product-cart-btn product-detail-btn" aria-label="Xem chi tiết {{ $item[0] }}" title="Xem chi tiết">
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
