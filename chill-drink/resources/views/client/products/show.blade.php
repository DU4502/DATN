@extends('layouts.client')

@section('title', $product->name)

@section('content')
<style>
    .product-detail-wrap {
        padding-top: 2.25rem;
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
        border: 1px solid var(--drink-border);
        border-radius: 12px;
        background: #ffffff;
        box-shadow: 0 16px 36px rgba(7, 52, 58, 0.08);
        aspect-ratio: 5 / 4;
        height: min(54vw, 520px);
        min-height: 420px;
    }

    .detail-photo-card img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        padding: 0;
        transition: opacity 0.18s ease;
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
        border-radius: 8px;
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
        object-fit: contain;
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
    .option-card,
    .mini-info-card {
        border: 1px solid var(--drink-border);
        border-radius: 22px;
        background: rgba(255, 255, 255, 0.82);
        box-shadow: 0 18px 42px rgba(79, 183, 168, 0.10);
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
        padding: 0.72rem 1rem;
        transition: border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
    }

    .choice-btn:hover,
    .choice-btn.active {
        border-color: var(--drink-primary);
        box-shadow: 0 0 0 3px rgba(0, 139, 122, 0.13);
        color: var(--drink-primary-dark);
    }

    .qty-control {
        min-width: 150px;
        border: 1px solid var(--drink-border);
        border-radius: 999px;
        background: #ffffff;
        padding: 0.45rem 0.75rem;
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

        <div class="row g-5 align-items-start">
            <div class="col-lg-6">
                @php
                    $mainImage = $product->image ?: 'https://images.unsplash.com/photo-1515823064-d6e0c04616a7?auto=format&fit=crop&w=1000&q=85';
                    $galleryImages = collect([
                        $mainImage,
                        $product->image ?: 'https://images.unsplash.com/photo-1544145945-f90425340c7e?auto=format&fit=crop&w=1000&q=85',
                    ])->filter()->unique()->values();
                @endphp
                <div class="detail-gallery">
                    <div class="detail-photo-card">
                        <img
                            id="detailMainImage"
                            src="{{ $mainImage }}"
                            alt="{{ $product->name }}"
                            onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1515823064-d6e0c04616a7?auto=format&fit=crop&w=1000&q=85';"
                        >
                    </div>
                    <div class="detail-thumbs" aria-label="Ảnh sản phẩm">
                        @foreach($galleryImages as $index => $image)
                            <button type="button" class="detail-thumb {{ $index === 0 ? 'active' : '' }}" data-detail-thumb="{{ $image }}" aria-label="Xem ảnh {{ $index + 1 }}">
                                <img src="{{ $image }}" alt="{{ $product->name }} ảnh {{ $index + 1 }}">
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="d-flex flex-column gap-4">
                    <div>
                        <span class="detail-pill mb-3">{{ $product->category->name ?? 'Đồ uống' }}</span>
                        <h1 class="display-5 fw-bold mb-3">{{ $product->name }}</h1>
                        <p class="h2 text-primary fw-bold mb-0">{{ number_format($product->price ?? 0, 0, ',', '.') }}đ</p>
                    </div>

                    <div class="detail-info-card p-4">
                        <p class="option-label mb-2">Trải nghiệm hương vị</p>
                        <p class="text-secondary mb-3">
                            {{ $product->description ?? 'Đồ uống được pha chế tươi, vị cân bằng, phù hợp cho những lúc cần một ly mát lành và dễ uống.' }}
                        </p>
                        <div class="d-flex flex-wrap gap-3">
                            <span class="text-primary fw-semibold">Tươi mát</span>
                            <span class="text-primary fw-semibold">Dễ uống</span>
                            <span class="text-primary fw-semibold">Pha trong ngày</span>
                        </div>
                    </div>

                    <div class="option-card p-4">
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

                        <div class="d-flex flex-column flex-sm-row gap-3">
                            <div class="qty-control d-flex align-items-center justify-content-between">
                                <button type="button" data-qty-minus aria-label="Giảm số lượng">-</button>
                                <span class="h5 fw-bold mb-0" data-qty-value>1</span>
                                <button type="button" data-qty-plus aria-label="Tăng số lượng">+</button>
                            </div>

                            @if(($product->stock ?? 1) > 0)
                                <form action="{{ route('cart.add', $product->id) }}" method="POST" class="flex-grow-1" data-ajax-cart>
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-lg w-100">Thêm vào giỏ</button>
                                </form>
                            @else
                                <span class="btn btn-outline-danger btn-lg disabled flex-grow-1">Hết hàng</span>
                            @endif
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="mini-info-card p-3 d-flex align-items-center gap-3 h-100">
                                <span class="brand-mark">G</span>
                                <div>
                                    <div class="fw-bold">Giao hàng nhanh</div>
                                    <small class="text-secondary">Miễn phí theo ưu đãi</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="mini-info-card p-3 d-flex align-items-center gap-3 h-100">
                                <span class="brand-mark">T</span>
                                <div>
                                    <div class="fw-bold">Thanh toán an toàn</div>
                                    <small class="text-secondary">Xác nhận rõ ràng</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <a href="{{ route('products.index') }}" class="btn btn-outline-primary align-self-start">Quay lại danh sách</a>
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
                                <img
                                    src="{{ $item->image ?: 'https://images.unsplash.com/photo-1544145945-f90425340c7e?auto=format&fit=crop&w=700&q=85' }}"
                                    alt="{{ $item->name }}"
                                    class="card-img-top"
                                    onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1544145945-f90425340c7e?auto=format&fit=crop&w=700&q=85';"
                                >
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
                        ['Cà phê ủ lạnh vani', '55.000đ', 'https://images.unsplash.com/photo-1517701550927-30cf4ba1dba5?auto=format&fit=crop&w=700&q=85'],
                        ['Trà Earl Grey Đá', '45.000đ', 'https://images.unsplash.com/photo-1556679343-c7306c1976bc?auto=format&fit=crop&w=700&q=85'],
                        ['Trà hoa bụp giấm mát lạnh', '52.000đ', 'https://images.unsplash.com/photo-1551024709-8f23befc6f87?auto=format&fit=crop&w=700&q=85'],
                        ['Trà Sữa Khoai Môn', '60.000đ', 'https://images.unsplash.com/photo-1558857563-b371033873b8?auto=format&fit=crop&w=700&q=85'],
                    ] as $item)
                        <div class="col-sm-6 col-lg-3">
                            <div class="related-card drink-card card border-0 h-100 overflow-hidden">
                                <img src="{{ $item[2] }}" alt="{{ $item[0] }}" class="card-img-top">
                                <div class="card-body">
                                    <h3 class="h5">{{ $item[0] }}</h3>
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

        if (minus && plus && value) {
            let qty = 1;
            const render = function () {
                value.textContent = qty;
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

        const mainImage = document.getElementById('detailMainImage');
        const thumbs = document.querySelectorAll('[data-detail-thumb]');

        if (mainImage && thumbs.length) {
            thumbs.forEach(function (thumb) {
                thumb.addEventListener('click', function () {
                    thumbs.forEach(function (item) {
                        item.classList.remove('active');
                    });

                    thumb.classList.add('active');
                    mainImage.style.opacity = '0';

                    setTimeout(function () {
                        mainImage.src = thumb.dataset.detailThumb;
                        mainImage.style.opacity = '1';
                    }, 120);
                });
            });
        }
    });
</script>
@endsection
