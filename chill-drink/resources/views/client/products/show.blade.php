@extends('layouts.client')

@section('title', $product->name)

@section('content')
@php extract(require resource_path('views/partials/ui-product-data.php')); @endphp
<style>
    .product-detail-wrap {
        padding-top: 2rem;
        padding-bottom: 4rem;
    }

    .breadcrumb-soft {
        color: var(--drink-muted);
        font-size: 0.9rem;
        font-weight: 600;
    }

    .breadcrumb-soft a {
        color: var(--drink-muted);
        text-decoration: none;
    }

    .breadcrumb-soft a:hover {
        color: var(--drink-primary);
    }

    .detail-photo-card {
        position: relative;
        overflow: hidden;
        border: 0;
        border-radius: 22px;
        background: #f3efe5;
        box-shadow: 0 18px 42px rgba(7, 52, 58, 0.08);
        aspect-ratio: 1 / 1;
        height: auto;
        min-height: 0;
    }

    .detail-photo-card img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        padding: 0;
        transition: opacity 0.18s ease;
    }

    .detail-gallery-nav {
        position: absolute;
        top: 50%;
        width: 42px;
        height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 0;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.9);
        color: var(--drink-primary-dark);
        box-shadow: 0 12px 28px rgba(7, 52, 58, 0.16);
        transform: translateY(-50%);
        transition: transform 0.18s ease, background 0.18s ease;
        z-index: 2;
    }

    .detail-gallery-nav:hover {
        background: #ffffff;
        transform: translateY(-50%) scale(1.05);
    }

    .detail-gallery-nav.prev {
        left: 1rem;
    }

    .detail-gallery-nav.next {
        right: 1rem;
    }

    .detail-gallery {
        position: sticky;
        top: 108px;
    }

    .detail-thumbs {
        display: flex;
        gap: 0.75rem;
        margin-top: 1rem;
        overflow-x: auto;
        padding-bottom: 0.2rem;
    }

    .detail-thumb {
        width: 86px;
        height: 86px;
        border: 2px solid transparent;
        border-radius: 12px;
        background: #ffffff;
        padding: 0.35rem;
        box-shadow: 0 8px 18px rgba(7, 52, 58, 0.08);
        cursor: pointer;
        flex: 0 0 auto;
        transition: border-color 0.16s ease, transform 0.16s ease;
    }

    .detail-thumb:hover,
    .detail-thumb.active {
        border-color: var(--drink-primary);
        transform: translateY(-1px);
    }

    .detail-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 6px;
    }

    .detail-pill {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        background: #dff4ef;
        color: var(--drink-primary-dark);
        font-size: 0.76rem;
        font-weight: 800;
        letter-spacing: 0.04em;
        padding: 0.45rem 0.85rem;
        text-transform: uppercase;
    }

    .detail-info-card,
    .option-card {
        border: 0;
        border-radius: 0;
        background: transparent;
        box-shadow: none;
        padding: 0 !important;
    }

    .option-label {
        color: var(--drink-muted);
        font-size: 0.78rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .choice-btn {
        border: 1px solid var(--drink-border);
        border-radius: 999px;
        background: #ffffff;
        color: var(--drink-ink);
        font-weight: 700;
        padding: 0.52rem 0.95rem;
        transition: border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
    }

    .choice-btn:hover,
    .choice-btn.active {
        border-color: var(--drink-primary);
        box-shadow: 0 0 0 3px rgba(0, 139, 122, 0.13);
        color: var(--drink-primary-dark);
    }

    .size-choice {
        min-width: 76px;
        text-align: center;
    }

    .size-choice small {
        display: block;
        color: var(--drink-muted);
        font-size: 0.72rem;
        font-weight: 700;
        margin-top: 0.15rem;
    }

    .qty-control {
        min-width: 132px;
        border: 1px solid var(--drink-border);
        border-radius: 999px;
        background: #ffffff;
        padding: 0.45rem 0.75rem;
    }

    .product-detail-actions {
        border-top: 1px solid var(--drink-border);
        padding-top: 1.2rem;
    }

    .product-detail-actions .btn-primary {
        min-height: 52px;
    }

    .detail-info-card {
        max-width: 680px;
    }

    .product-detail-wrap .display-5 {
        font-size: clamp(2rem, 4vw, 3.2rem);
        line-height: 1.12;
    }

    .qty-control button {
        width: 34px;
        height: 34px;
        border: 0;
        border-radius: 50%;
        background: var(--drink-primary-soft);
        color: var(--drink-primary-dark);
        font-weight: 800;
    }

    .related-card img {
        height: 270px;
        object-fit: cover;
    }

    @media (max-width: 991.98px) {
        .detail-photo-card {
            height: auto;
            min-height: 0;
            aspect-ratio: 4 / 3;
        }

        .detail-gallery {
            position: static;
        }
    }

    @media (max-width: 575.98px) {
        .detail-photo-card {
            border-radius: 10px;
            aspect-ratio: 1 / 1;
        }

        .detail-photo-card img {
            padding: 0;
        }

        .detail-thumb {
            width: 68px;
            height: 68px;
        }

        .related-card img {
            height: 220px;
        }
    }
</style>

<section class="product-detail-wrap">
    <div class="container">
        <nav class="breadcrumb-soft d-flex flex-wrap align-items-center gap-2 mb-4">
            <a href="{{ route('home') }}">Trang chủ</a>
            <span>/</span>
            <a href="{{ route('products.index') }}">Sản phẩm</a>
            <span>/</span>
            <span class="text-primary">{{ $product->name }}</span>
        </nav>

        <div class="row g-4 g-xl-5 align-items-start">
            <div class="col-lg-5">
                @php
                    $detailCategory = $product->category->name ?? null;
                    $detailGalleryImages = $uiGetProductGallery(
                        $product->sku ?? null,
                        $detailCategory,
                        $product->name,
                        6
                    );
                    $detailMainImage = $detailGalleryImages[0]
                        ?? $uiResolveProductImage($product->sku ?? null, $detailCategory, $product->name, 1000);
                @endphp
                <div class="detail-gallery">
                    <div class="detail-photo-card">
                        <img
                            id="detailMainImage"
                            src="{{ $detailMainImage }}"
                            alt="{{ $product->name }}"
                            style="width:100%;height:100%;object-fit:cover;"
                            onerror="this.onerror=null;this.src='{{ $uiResolveProductImage($product->sku ?? null, $detailCategory, $product->name, 1000) }}';"
                        >
                        @if(count($detailGalleryImages) > 1)
                            <button type="button" class="detail-gallery-nav prev" data-gallery-prev aria-label="Ảnh trước">
                                <i class="bi bi-chevron-left"></i>
                            </button>
                            <button type="button" class="detail-gallery-nav next" data-gallery-next aria-label="Ảnh tiếp theo">
                                <i class="bi bi-chevron-right"></i>
                            </button>
                        @endif
                    </div>
                    @if(count($detailGalleryImages) > 1)
                        <div class="detail-thumbs" aria-label="Ảnh sản phẩm">
                            @foreach($detailGalleryImages as $index => $image)
                                <button
                                    type="button"
                                    class="detail-thumb {{ $index === 0 ? 'active' : '' }}"
                                    data-detail-thumb="{{ $image }}"
                                    aria-label="Xem ảnh {{ $index + 1 }}"
                                >
                                    <img src="{{ $image }}" alt="{{ $product->name }} ảnh {{ $index + 1 }}" loading="lazy">
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="col-lg-7">
                <div class="d-flex flex-column gap-4">
                    <div>
                        <span class="detail-pill mb-3">{{ $product->category->name ?? 'Đồ uống' }}</span>
                        <h1 class="display-5 fw-bold mb-3">{{ $product->name }}</h1>
                        @if(!empty($product->sku))
                            <p class="text-secondary small font-monospace mb-2">Mã sản phẩm: {{ $product->sku }}</p>
                        @endif
                        <p class="h2 text-primary fw-bold mb-0">{{ number_format($product->price ?? 0, 0, ',', '.') }}đ</p>
                    </div>

                    <div class="detail-info-card p-4">
                        <p class="text-secondary mb-3">
                            {{ $product instanceof \App\Models\Product ? $product->display_description : ($product->description ?? \App\Support\ProductCatalog::descriptionFor($product->name ?? '', $product->category->name ?? null)) }}
                        </p>
                        <div class="d-flex flex-wrap gap-3">
                            <span class="text-primary fw-semibold">Tươi mát</span>
                            <span class="text-primary fw-semibold">Dễ uống</span>
                            <span class="text-primary fw-semibold">Pha trong ngày</span>
                        </div>
                    </div>

                    <div class="option-card p-4">
                        <div class="mb-4">
                            <label class="option-label d-block mb-3">Size</label>
                            <div class="d-flex flex-wrap gap-2" data-size-group>
                                <button type="button" class="choice-btn size-choice" data-size-option="S" data-size-extra="0">
                                    S
                                    <small>Giá gốc</small>
                                </button>
                                <button type="button" class="choice-btn size-choice active" data-size-option="M" data-size-extra="5000">
                                    M
                                    <small>+5.000đ</small>
                                </button>
                                <button type="button" class="choice-btn size-choice" data-size-option="L" data-size-extra="10000">
                                    L
                                    <small>+10.000đ</small>
                                </button>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="option-label d-block mb-3">Mức đường</label>
                            <div class="d-flex flex-wrap gap-2" data-choice-group="sugar">
                                <button type="button" class="choice-btn">0%</button>
                                <button type="button" class="choice-btn active">30%</button>
                                <button type="button" class="choice-btn">50%</button>
                                <button type="button" class="choice-btn">70%</button>
                                <button type="button" class="choice-btn">100%</button>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="option-label d-block mb-3">Mức đá</label>
                            <div class="d-flex flex-wrap gap-2" data-choice-group="ice">
                                <button type="button" class="choice-btn">Không đá</button>
                                <button type="button" class="choice-btn active">Bình thường</button>
                                <button type="button" class="choice-btn">Ít đá</button>
                                <button type="button" class="choice-btn">Nhiều đá</button>
                            </div>
                        </div>

                        <div class="product-detail-actions d-flex flex-column flex-sm-row gap-3">
                            <div class="qty-control d-flex align-items-center justify-content-between">
                                <button type="button" data-qty-minus aria-label="Giảm số lượng">-</button>
                                <span class="h5 fw-bold mb-0" data-qty-value>1</span>
                                <button type="button" data-qty-plus aria-label="Tăng số lượng">+</button>
                            </div>

                            @if(($product->stock ?? 1) > 0)
                                <form action="{{ route('cart.add', $product->id) }}" method="POST" class="flex-grow-1" data-ajax-cart>
                                    @csrf
                                    <input type="hidden" name="size" value="M" data-size-input>
                                    <input type="hidden" name="quantity" value="1" data-qty-input>
                                    <button type="submit" class="btn btn-primary btn-lg w-100">Thêm vào giỏ</button>
                                </form>
                            @else
                                <span class="btn btn-outline-danger btn-lg disabled flex-grow-1">Hết hàng</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <section class="mt-5 pt-4">
            <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
                <div>
                    <h2 class="section-title h1 mb-2">Có thể bạn cũng thích</h2>
                    <p class="text-secondary mb-0">Gợi ý thêm cho một ly đồ uống thật hợp mood.</p>
                </div>
                <a href="{{ route('products.index') }}" class="btn btn-outline-primary">Xem tất cả</a>
            </div>

            <div class="row g-4">
                @forelse($relatedProducts as $item)
                    <div class="col-sm-6 col-lg-3">
                        <div class="related-card drink-card card border-0 h-100 overflow-hidden">
                            <a href="{{ route('products.show', $item->slug) }}">
                                <x-product-image
                                    :sku="$item->sku ?? null"
                                    :name="$item->name"
                                    :alt="$item->name"
                                    :category="$item->category?->name"
                                    class="card-img-top"
                                    style="aspect-ratio: 4/3;"
                                />
                            </a>
                            <div class="card-body">
                                <h3 class="h5">
                                    <a href="{{ route('products.show', $item->slug) }}" class="text-dark text-decoration-none">{{ $item->name }}</a>
                                </h3>
                                <strong class="text-primary">{{ number_format($item->price ?? 0, 0, ',', '.') }}đ</strong>
                            </div>
                        </div>
                    </div>
                @empty
                    @foreach([
                        ['Cà phê ủ lạnh vani', '55.000đ', 'https://images.unsplash.com/photo-1517701550927-30cf4ba1dba5?auto=format&fit=crop&w=700&q=85', 'cold-brew-arctic'],
                        ['Trà Earl Grey Đá', '45.000đ', 'https://images.unsplash.com/photo-1556679343-c7306c1976bc?auto=format&fit=crop&w=700&q=85', 'tropical-frost'],
                        ['Trà hoa bụp giấm mát lạnh', '52.000đ', 'https://images.unsplash.com/photo-1551024709-8f23befc6f87?auto=format&fit=crop&w=700&q=85', 'citrus-sunset'],
                        ['Trà Sữa Khoai Môn', '60.000đ', 'https://images.unsplash.com/photo-1558857563-b371033873b8?auto=format&fit=crop&w=700&q=85', 'tra-sua-tran-chau-demo'],
                    ] as $item)
                        <div class="col-sm-6 col-lg-3">
                            <div class="related-card drink-card card border-0 h-100 overflow-hidden">
                                <a href="{{ route('products.show', $item[3]) }}">
                                    <img src="{{ $item[2] }}" alt="{{ $item[0] }}" class="card-img-top">
                                </a>
                                <div class="card-body">
                                    <h3 class="h5">
                                        <a href="{{ route('products.show', $item[3]) }}" class="text-dark text-decoration-none">{{ $item[0] }}</a>
                                    </h3>
                                    <strong class="text-primary">{{ $item[1] }}</strong>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endforelse
            </div>
        </section>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-choice-group]').forEach(function (group) {
            group.querySelectorAll('.choice-btn').forEach(function (button) {
                button.addEventListener('click', function () {
                    group.querySelectorAll('.choice-btn').forEach(function (item) {
                        item.classList.remove('active');
                    });
                    button.classList.add('active');
                });
            });
        });

        const minus = document.querySelector('[data-qty-minus]');
        const plus = document.querySelector('[data-qty-plus]');
        const value = document.querySelector('[data-qty-value]');
        const qtyInput = document.querySelector('[data-qty-input]');

        if (minus && plus && value) {
            let qty = 1;
            const render = function () {
                value.textContent = qty;
                if (qtyInput) {
                    qtyInput.value = qty;
                }
            };

            minus.addEventListener('click', function () {
                qty = Math.max(1, qty - 1);
                render();
            });

            plus.addEventListener('click', function () {
                qty += 1;
                render();
            });
        }

        const sizeGroup = document.querySelector('[data-size-group]');
        const sizeInput = document.querySelector('[data-size-input]');

        if (sizeGroup && sizeInput) {
            sizeGroup.querySelectorAll('[data-size-option]').forEach(function (button) {
                button.addEventListener('click', function () {
                    sizeGroup.querySelectorAll('[data-size-option]').forEach(function (item) {
                        item.classList.remove('active');
                    });
                    button.classList.add('active');
                    sizeInput.value = button.dataset.sizeOption || 'M';
                });
            });
        }

        const mainImage = document.getElementById('detailMainImage');
        const thumbs = document.querySelectorAll('[data-detail-thumb]');
        const prevButton = document.querySelector('[data-gallery-prev]');
        const nextButton = document.querySelector('[data-gallery-next]');
        let activeImageIndex = 0;

        const setActiveImage = function (index) {
            if (!mainImage || !thumbs.length) {
                return;
            }

            activeImageIndex = (index + thumbs.length) % thumbs.length;

            thumbs.forEach(function (item) {
                item.classList.remove('active');
            });

            const activeThumb = thumbs[activeImageIndex];
            activeThumb.classList.add('active');
            mainImage.style.opacity = '0';

            setTimeout(function () {
                mainImage.src = activeThumb.dataset.detailThumb;
                mainImage.style.opacity = '1';
            }, 120);
        };

        if (mainImage && thumbs.length) {
            thumbs.forEach(function (thumb, index) {
                thumb.addEventListener('click', function () {
                    setActiveImage(index);
                });
            });

            prevButton?.addEventListener('click', function () {
                setActiveImage(activeImageIndex - 1);
            });

            nextButton?.addEventListener('click', function () {
                setActiveImage(activeImageIndex + 1);
            });
        }
    });
</script>
@endsection
