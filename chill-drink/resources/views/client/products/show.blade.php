@extends('layouts.client')

@section('title', $product->name)

@section('content')
@php extract(require resource_path('views/partials/ui-product-data.php')); @endphp
<style>
    .product-detail-wrap {
        padding-top: 1.5rem;
        padding-bottom: 4rem;
    }

    .breadcrumb-soft {
        color: var(--c-muted, #6b7280);
        font-size: 0.9rem;
        font-weight: 600;
    }

    .breadcrumb-soft a {
        color: var(--c-muted, #6b7280);
        text-decoration: none;
    }

    .breadcrumb-soft a:hover {
        color: var(--c-primary, #0d9373);
    }

    .detail-photo-card {
        position: relative;
        overflow: hidden;
        border: 0;
        border-radius: var(--radius-md, 12px);
        background: var(--c-bg-warm, #f0fdf9);
        box-shadow: var(--shadow-lg);
        aspect-ratio: 1 / 1;
        height: auto;
        min-height: 0;
    }

    .detail-photo-card img {
        width: 100%;
        height: 100%;
        object-fit: contain !important;
        object-position: center;
        padding: 1rem;
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
        color: var(--c-primary-dark, #067a5f);
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
        gap: 0.65rem;
        margin-top: 0.75rem;
        overflow-x: auto;
        padding-bottom: 0.2rem;
    }

    .detail-thumb {
        width: 86px;
        height: 70px;
        border: 1.5px solid transparent;
        border-radius: var(--radius-sm, 8px);
        background: #ffffff;
        padding: 0.25rem;
        box-shadow: 0 8px 18px rgba(7, 52, 58, 0.08);
        cursor: pointer;
        flex: 0 0 auto;
        transition: border-color 0.16s ease, transform 0.16s ease;
    }

    .detail-thumb:hover,
    .detail-thumb.active {
        border-color: var(--c-primary, #0d9373);
        transform: translateY(-1px);
    }

    .detail-thumb img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        border-radius: 6px;
    }

    .detail-pill {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        background: #dff4ef;
        color: var(--c-primary-dark, #067a5f);
        font-size: 0.76rem;
        font-weight: 800;
        letter-spacing: 0.04em;
        padding: 0.45rem 0.85rem;
        text-transform: uppercase;
    }

    .detail-layout {
        --bs-gutter-x: 2.25rem;
    }

    .detail-summary {
        max-width: 560px;
    }

    .detail-summary h1 {
        margin-bottom: 0.35rem !important;
    }

    .detail-summary .detail-desc {
        font-size: 0.95rem;
        line-height: 1.7;
        margin-bottom: 0;
    }

    .detail-info-card,
    .option-card {
        border: 0;
        border-radius: 0;
        background: transparent;
        box-shadow: none;
        padding: 0 !important;
    }

    .option-card {
        max-width: 560px;
    }

    .option-block {
        margin-bottom: 1.05rem;
    }

    .option-label {
        color: var(--c-ink, #111827);
        font-size: 0.82rem;
        font-weight: 800;
        letter-spacing: 0;
        text-transform: none;
    }

    .choice-btn {
        min-height: 50px;
        border: 1.5px solid var(--c-border, #e5e7eb);
        border-radius: var(--radius-sm, 8px);
        background: var(--c-surface, #ffffff);
        color: var(--c-ink, #111827);
        font-weight: 700;
        padding: 0.55rem 0.9rem;
        cursor: pointer;
        transition: border-color 0.18s ease, background-color 0.18s ease, box-shadow 0.18s ease, color 0.18s ease, transform 0.18s ease;
    }

    .choice-btn:hover,
    .choice-btn.active {
        border-color: var(--c-primary, #0d9373);
        background: var(--c-primary-light, #e6f7f2);
        box-shadow: 0 0 0 3px rgba(13, 147, 115, 0.13);
        color: var(--c-primary-dark, #067a5f);
    }

    .choice-btn.active {
        border-width: 1.5px;
        background: var(--c-primary-light, #e6f7f2);
        color: var(--c-primary-dark, #067a5f);
        box-shadow: inset 0 0 0 1px var(--c-primary, #0d9373), 0 8px 18px rgba(13, 147, 115, 0.12);
        transform: translateY(-1px);
    }

    .choice-btn.active small {
        color: var(--c-primary-dark, #067a5f);
    }

    .size-choice {
        min-width: 0;
        flex: 1 1 0;
        text-align: center;
    }

    .size-choice small {
        display: block;
        color: var(--c-muted, #6b7280);
        font-size: 0.72rem;
        font-weight: 700;
        margin-top: 0.15rem;
    }

    .qty-control {
        min-width: 142px;
        border: 1px solid var(--c-border, #e5e7eb);
        border-radius: var(--radius-full, 999px);
        background: #ffffff;
        padding: 0.45rem 0.75rem;
    }

    .detail-action-row {
        display: grid;
        grid-template-columns: 142px minmax(0, 1fr);
        gap: 0.9rem;
        align-items: stretch;
    }

    .detail-button-row {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.9rem;
    }

    .product-detail-actions {
        border-top: 0;
        padding-top: 0.5rem;
    }

    .product-detail-actions .btn-primary {
        min-height: 52px;
    }

    .detail-buy-btn {
        min-height: 52px;
        border-radius: var(--radius-full, 999px);
        font-weight: 800;
    }

    .detail-info-card {
        max-width: 560px;
    }

    .product-detail-wrap .display-5 {
        font-size: clamp(1.55rem, 2.3vw, 2rem);
        line-height: 1.12;
    }

    .qty-control button {
        width: 34px;
        height: 34px;
        border: 0;
        border-radius: 50%;
        background: var(--c-primary-light, #e6f7f2);
        color: var(--c-primary-dark, #067a5f);
        font-weight: 800;
    }

    .detail-benefits {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.75rem;
        padding-top: 0.9rem;
    }

    .detail-benefit {
        text-align: center;
        color: var(--c-muted, #6b7280);
        font-size: 0.74rem;
        font-weight: 800;
        text-transform: uppercase;
    }

    .detail-benefit i {
        display: block;
        color: var(--c-primary, #0d9373);
        font-size: 1.15rem;
        margin-bottom: 0.35rem;
    }

    .topping-choice {
        flex: 1 1 calc(33.333% - 0.5rem);
        min-width: 150px;
        display: flex;
        align-items: center;
        gap: 0.55rem;
        text-align: left;
    }

    .topping-choice::before {
        content: "";
        width: 16px;
        height: 16px;
        flex: 0 0 auto;
        border-radius: 4px;
        border: 1.5px solid var(--c-subtle, #9ca3af);
        background: #fff;
        transition: background 0.16s ease, border-color 0.16s ease, box-shadow 0.16s ease;
    }

    .topping-choice.active::before {
        border-color: var(--c-primary, #0d9373);
        background: var(--c-primary, #0d9373);
        box-shadow: inset 0 0 0 3px #fff;
    }

    .topping-choice small {
        display: block;
        margin-top: 0.1rem;
        color: var(--c-muted, #6b7280);
        font-size: 0.72rem;
    }

    .compact-select {
        position: relative;
    }

    .compact-select-toggle {
        width: 100%;
        min-height: 50px;
        border: 1.5px solid var(--c-border, #e5e7eb);
        border-radius: var(--radius-sm, 8px);
        padding: 0.65rem 0.9rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        color: var(--c-ink, #111827);
        font-weight: 800;
        background: var(--c-surface, #fff);
        cursor: pointer;
        transition: border-color 0.16s ease, box-shadow 0.16s ease;
    }

    .compact-select.open .compact-select-toggle,
    .compact-select-toggle:focus {
        border-color: var(--c-primary, #0d9373);
        box-shadow: 0 0 0 3px rgba(13, 147, 115, 0.13);
    }

    .compact-select-toggle i {
        color: var(--c-muted, #6b7280);
        transition: transform 0.16s ease;
    }

    .compact-select.open .compact-select-toggle i {
        transform: rotate(180deg);
    }

    .compact-select-menu {
        position: absolute;
        top: calc(100% + 0.35rem);
        left: 0;
        right: 0;
        z-index: 30;
        display: none;
        overflow: hidden;
        border: 1px solid var(--c-border, #e5e7eb);
        border-radius: var(--radius-sm, 8px);
        background: #fff;
        box-shadow: var(--shadow-lg);
    }

    .compact-select.open .compact-select-menu {
        display: block;
    }

    .compact-select-option {
        width: 100%;
        border: 0;
        background: #fff;
        color: var(--c-ink, #111827);
        text-align: left;
        padding: 0.7rem 0.9rem;
        font-weight: 700;
    }

    .compact-select-option:hover,
    .compact-select-option.active {
        background: var(--c-primary-light, #e6f7f2);
        color: var(--c-primary-dark, #067a5f);
    }

    .related-card img {
        height: 230px;
        object-fit: contain;
        background: var(--c-bg-warm, #f0fdf9);
        padding: 0.75rem;
    }

    .review-shell {
        border: 1px solid var(--c-border, #e5e7eb);
        border-radius: 24px;
        background: #ffffff;
        box-shadow: 0 18px 40px rgba(7, 52, 58, 0.06);
    }

    .review-score-box {
        border-radius: 20px;
        background: linear-gradient(135deg, #f4fffb, #ffffff);
        border: 1px solid rgba(13, 147, 115, 0.12);
    }

    .review-star-row {
        display: inline-flex;
        gap: 0.22rem;
        color: #f59e0b;
    }

    .review-meter {
        height: 8px;
        border-radius: 999px;
        background: #edf2f7;
        overflow: hidden;
    }

    .review-meter>span {
        display: block;
        height: 100%;
        background: linear-gradient(90deg, #f59e0b, #fbbf24);
    }

    .review-card {
        border: 1px solid var(--c-border, #e5e7eb);
        border-radius: 18px;
        background: #ffffff;
    }

    .review-avatar {
        width: 46px;
        height: 46px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: linear-gradient(135deg, #dff4ef, #8fd8ce);
        color: var(--c-primary-dark, #067a5f);
        font-weight: 800;
    }

    .review-form-panel {
        border-radius: 20px;
        background: #fbfffe;
        border: 1px solid var(--c-border, #e5e7eb);
    }

    .review-rating-input {
        display: inline-flex;
        flex-direction: row-reverse;
        justify-content: flex-end;
        gap: 0.4rem;
    }

    .review-rating-input input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .review-rating-input label {
        cursor: pointer;
        font-size: 1.45rem;
        color: #cbd5e1;
        transition: transform 0.15s ease, color 0.15s ease;
    }

    .review-rating-input label:hover,
    .review-rating-input label:hover~label,
    .review-rating-input input:checked~label {
        color: #f59e0b;
        transform: translateY(-1px);
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

        .detail-summary,
        .option-card {
            max-width: none;
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

        .detail-action-row,
        .detail-button-row,
        .detail-benefits {
            grid-template-columns: 1fr;
        }

        .qty-control {
            width: 100%;
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

        <div class="row g-4 align-items-start detail-layout">
            <div class="col-lg-5">
                @php
                $detailCategory = $product->category->name ?? null;
                $detailGalleryImages = $product instanceof \App\Models\Product
                ? $product->gallery_images
                : ($product->gallery_images ?? []);

                if (empty($detailGalleryImages)) {
                $detailGalleryImages = $uiGetProductGallery(
                $product->sku ?? null,
                $detailCategory,
                $product->name,
                6,
                $product->image_url ?? $product->image ?? null
                );
                }

                    $detailMainImage = $detailGalleryImages[0]
                        ?? $uiResolveProductImage($product->sku ?? null, $detailCategory, $product->name, 1000);
                    $detailFallbackImage = $uiPlaceholderImage($product->name, $detailCategory);
                    $productNameLower = mb_strtolower($product->name ?? '');
                    $categoryLower = mb_strtolower($detailCategory ?? '');
                    $detailToppings = match (true) {
                        str_contains($productNameLower, 'matcha') => [
                            ['Trân châu đen', 5000],
                            ['Kem cheese', 7000],
                            ['Thạch matcha', 6000],
                        ],
                        str_contains($categoryLower, 'trà sữa') || str_contains($productNameLower, 'trà sữa') => [
                            ['Trân châu đen', 5000],
                            ['Pudding trứng', 7000],
                            ['Thạch phô mai', 8000],
                        ],
                        str_contains($categoryLower, 'cà phê') || str_contains($productNameLower, 'cà phê') => [
                            ['Kem mặn', 7000],
                            ['Shot espresso', 10000],
                            ['Caramel', 6000],
                        ],
                        str_contains($categoryLower, 'sinh tố') || str_contains($productNameLower, 'sinh tố') => [
                            ['Hạt chia', 5000],
                            ['Sữa chua', 7000],
                            ['Nha đam', 6000],
                        ],
                        str_contains($categoryLower, 'nước ép') || str_contains($productNameLower, 'nước ép') => [
                            ['Nha đam', 6000],
                            ['Hạt chia', 5000],
                            ['Soda', 7000],
                        ],
                        str_contains($categoryLower, 'soda') || str_contains($productNameLower, 'soda') => [
                            ['Thạch trái cây', 6000],
                            ['Nha đam', 6000],
                            ['Trân châu trắng', 7000],
                        ],
                        default => [
                            ['Trân châu trắng', 7000],
                            ['Thạch nha đam', 6000],
                            ['Kem cheese', 7000],
                        ],
                    };
                @endphp
                <div class="detail-gallery">
                    <div class="detail-photo-card">
                        <img
                            id="detailMainImage"
                            src="{{ $detailMainImage }}"
                            alt="{{ $product->name }}"
                            data-detail-fallback="{{ $detailFallbackImage }}"
                            onerror="this.onerror=null;this.src='{{ $detailFallbackImage }}';">
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
                            aria-label="Xem ảnh {{ $index + 1 }}">
                            <img src="{{ $image }}" alt="{{ $product->name }} ảnh {{ $index + 1 }}" loading="lazy" onerror="this.onerror=null;this.src='{{ $detailFallbackImage }}';">
                        </button>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>

            <div class="col-lg-7">
                <div class="d-flex flex-column gap-3 detail-summary">
                    <div>
                        <span class="detail-pill mb-3">{{ $product->category->name ?? 'Đồ uống' }}</span>
                        <h1 class="display-5 fw-bold mb-3">{{ $product->name }}</h1>
                        @if(!empty($product->sku))
                        <p class="text-secondary small font-monospace mb-2">Mã sản phẩm: {{ $product->sku }}</p>
                        @endif
                        <p class="h2 text-primary fw-bold mb-0">{{ number_format($product->price ?? 0, 0, ',', '.') }}đ</p>
                    </div>

                    <div class="detail-info-card p-4">
                        <p class="text-secondary detail-desc">
                            {{ $product instanceof \App\Models\Product ? $product->display_description : ($product->description ?? \App\Support\ProductCatalog::descriptionFor($product->name ?? '', $product->category->name ?? null)) }}
                        </p>
                    </div>

                    <div class="option-card p-4">
                        <div class="option-block">
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

                        <div class="row g-3 option-block">
                            <div class="col-md-6">
                                <label class="option-label d-block mb-3">Mức đường</label>
                                <div class="compact-select" data-compact-choice="sugar">
                                    <button type="button" class="compact-select-toggle">
                                        <span data-compact-label>100% (Tiêu chuẩn)</span>
                                        <i class="bi bi-chevron-down"></i>
                                    </button>
                                    <div class="compact-select-menu">
                                        <button type="button" class="compact-select-option" data-value="0">0%</button>
                                        <button type="button" class="compact-select-option" data-value="30">30%</button>
                                        <button type="button" class="compact-select-option" data-value="50">50%</button>
                                        <button type="button" class="compact-select-option" data-value="70">70%</button>
                                        <button type="button" class="compact-select-option active" data-value="100">100% (Tiêu chuẩn)</button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="option-label d-block mb-3">Mức đá</label>
                                <div class="compact-select" data-compact-choice="ice">
                                    <button type="button" class="compact-select-toggle">
                                        <span data-compact-label>100% (Tiêu chuẩn)</span>
                                        <i class="bi bi-chevron-down"></i>
                                    </button>
                                    <div class="compact-select-menu">
                                        <button type="button" class="compact-select-option" data-value="0">Không đá</button>
                                        <button type="button" class="compact-select-option" data-value="50">Ít đá</button>
                                        <button type="button" class="compact-select-option active" data-value="100">100% (Tiêu chuẩn)</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="option-block">
                            <label class="option-label d-block mb-3">Thêm topping</label>
                            <div class="d-flex flex-wrap gap-2" data-topping-group>
                                @foreach($detailToppings as $topping)
                                    <button type="button" class="choice-btn topping-choice" data-topping-name="{{ $topping[0] }}" data-topping-price="{{ $topping[1] }}">
                                        <span>
                                            {{ $topping[0] }}
                                            <small>+{{ number_format($topping[1], 0, ',', '.') }}đ</small>
                                        </span>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div class="product-detail-actions detail-action-row">
                            <div class="qty-control d-flex align-items-center justify-content-between">
                                <button type="button" data-qty-minus aria-label="Giảm số lượng">-</button>
                                <span class="h5 fw-bold mb-0" data-qty-value>1</span>
                                <button type="button" data-qty-plus aria-label="Tăng số lượng">+</button>
                            </div>

                            @if(($product->stock ?? 1) > 0)
                                <form action="{{ route('cart.add', $product->id) }}" method="POST" data-ajax-cart>
                                    @csrf
                                    <input type="hidden" name="size" value="M" data-size-input>
                                    <input type="hidden" name="sugar_level" value="100" data-choice-input="sugar">
                                    <input type="hidden" name="ice_level" value="100" data-choice-input="ice">
                                    <input type="hidden" name="toppings" value="" data-topping-input>
                                    <input type="hidden" name="quantity" value="1" data-qty-input>
                                    <div class="detail-button-row">
                                        <button type="submit" class="btn btn-outline-primary detail-buy-btn flex-fill">
                                            <i class="bi bi-cart-plus me-2"></i>Thêm vào giỏ
                                        </button>
                                        <button type="submit" name="buy_now" value="1" class="btn btn-primary detail-buy-btn flex-fill">Mua ngay</button>
                                    </div>
                                </form>
                            @else
                            <span class="btn btn-outline-danger btn-lg disabled flex-grow-1">Hết hàng</span>
                            @endif
                        </div>

                        <div class="detail-benefits">
                            <div class="detail-benefit">
                                <i class="bi bi-patch-check"></i>
                                Tự nhiên 100%
                            </div>
                            <div class="detail-benefit">
                                <i class="bi bi-truck"></i>
                                Giao nhanh 30'
                            </div>
                            <div class="detail-benefit">
                                <i class="bi bi-shield-check"></i>
                                Đảm bảo an toàn
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <section id="reviews" class="mt-5 pt-2">
            @php
            $reviewSummary = $reviewSummary ?? ['count' => 0, 'average' => 0, 'counts' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0]];
            $approvedReviews = $approvedReviews ?? collect();
            $reviewFormState = $reviewFormState ?? ['can_review' => false, 'message' => null, 'remaining_reviews' => 0];
            $reviewCount = (int) ($reviewSummary['count'] ?? 0);
            $reviewAverage = (float) ($reviewSummary['average'] ?? 0);
            $remainingReviews = (int) ($reviewFormState['remaining_reviews'] ?? 0);
            @endphp
            <div class="review-shell p-4 p-lg-5">
                <div class="row g-4 align-items-start">
                    <div class="col-lg-4">
                        <div class="review-score-box p-4 h-100">
                            <p class="section-kicker mb-2">Đánh giá sản phẩm</p>
                            <div class="display-6 fw-bold text-primary mb-2" data-review-average>{{ number_format($reviewAverage, 1, ',', '.') }}</div>
                            <div class="review-star-row mb-2" data-review-average-stars aria-label="Điểm trung bình {{ $reviewAverage }} trên 5">
                                @for($star = 1; $star <= 5; $star++)
                                    <i class="bi {{ $reviewAverage >= $star ? 'bi-star-fill' : ($reviewAverage >= ($star - 0.5) ? 'bi-star-half' : 'bi-star') }}"></i>
                                    @endfor
                            </div>
                            <p class="text-secondary mb-4" data-review-total>{{ $reviewCount > 0 ? number_format($reviewCount) . ' lượt đánh giá từ khách đã mua' : 'Chưa có đánh giá nào cho sản phẩm này.' }}</p>

                            <div class="d-flex flex-column gap-3">
                                @for($star = 5; $star >= 1; $star--)
                                @php
                                $starCount = (int) ($reviewSummary['counts'][$star] ?? 0);
                                $starPercent = $reviewCount > 0 ? (int) round(($starCount / $reviewCount) * 100) : 0;
                                @endphp
                                <div class="d-flex align-items-center gap-3" data-review-star-row="{{ $star }}">
                                    <div class="small fw-semibold text-secondary" style="width: 54px;">{{ $star }} sao</div>
                                    <div class="review-meter flex-grow-1"><span data-review-star-meter="{{ $star }}" style="width: {{ $starPercent }}%"></span></div>
                                    <div class="small fw-semibold text-secondary" style="width: 42px;" data-review-star-count="{{ $star }}">{{ $starCount }}</div>
                                </div>
                                @endfor
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-8">
                        <div class="review-form-panel p-4 mb-4">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                                <div>
                                    <h2 class="h4 fw-bold mb-1">Viết đánh giá</h2>
                                    <p class="text-secondary mb-0">Mỗi lần mua chỉ được gửi một đánh giá cho sản phẩm này.</p>
                                </div>
                                @if($remainingReviews > 0)
                                <span class="badge text-bg-light border" data-review-remaining-badge>Còn {{ $remainingReviews }} lượt đánh giá</span>
                                @else
                                <span class="badge text-bg-light border d-none" data-review-remaining-badge></span>
                                @endif
                            </div>

                            <div data-review-alerts>
                                @if(session('success'))
                                <div class="alert alert-success">{{ session('success') }}</div>
                                @endif
                                @if(session('error'))
                                <div class="alert alert-danger">{{ session('error') }}</div>
                                @endif
                            </div>

                            @auth
                            @if($reviewFormState['can_review'])
                            <form method="POST" action="{{ route('products.reviews.store', $product) }}" data-review-form>
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">Số sao</label>
                                    <div class="review-rating-input">
                                        @for($star = 5; $star >= 1; $star--)
                                        <input
                                            type="radio"
                                            id="rating-{{ $star }}"
                                            name="rating"
                                            value="{{ $star }}"
                                            @checked((int) old('rating', $userReview->rating ?? 0) === $star)
                                        >
                                        <label for="rating-{{ $star }}" title="{{ $star }} sao"><i class="bi bi-star-fill"></i></label>
                                        @endfor
                                    </div>
                                    <div class="text-danger small mt-2 {{ $errors->has('rating') ? '' : 'd-none' }}" data-error-target="rating">
                                        {{ $errors->first('rating') }}
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="review-comment" class="form-label">Nhận xét</label>
                                    <textarea
                                        id="review-comment"
                                        name="comment"
                                        rows="4"
                                        class="form-control"
                                        placeholder="Chia sẻ cảm nhận về hương vị, chất lượng và trải nghiệm của bạn...">{{ old('comment', $userReview->comment ?? '') }}</textarea>
                                    <div class="text-danger small mt-2 {{ $errors->has('comment') ? '' : 'd-none' }}" data-error-target="comment">
                                        {{ $errors->first('comment') }}
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary" data-review-submit>
                                    Gửi đánh giá
                                </button>
                            </form>
                            <div class="alert alert-warning mt-3 d-none" data-review-info></div>
                            @else
                            @if($reviewFormState['message'])
                            <div class="alert alert-warning mb-0" data-review-info>
                                {{ $reviewFormState['message'] }}
                            </div>
                            @endif
                            @endif
                            @else
                            <div class="alert alert-info mb-0">
                                <a href="{{ route('login') }}" class="fw-semibold text-decoration-none">Đăng nhập</a> để đánh giá sau khi bạn đã mua sản phẩm.
                            </div>
                            @endauth
                        </div>

                        <div class="d-flex flex-column gap-3" data-review-list>
                            @forelse($approvedReviews as $review)
                            <article class="review-card p-4">
                                <div class="d-flex gap-3">
                                    <span class="review-avatar">{{ mb_substr($review->user?->name ?? 'U', 0, 1) }}</span>
                                    <div class="flex-grow-1">
                                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                                            <div>
                                                <div class="fw-bold">{{ $review->user?->name ?? 'Khách hàng' }}</div>
                                                <div class="review-star-row small">
                                                    @for($star = 1; $star <= 5; $star++)
                                                        <i class="bi {{ (int) $review->rating >= $star ? 'bi-star-fill' : 'bi-star' }}"></i>
                                                        @endfor
                                                </div>
                                            </div>
                                            <div class="text-secondary small">
                                                {{ optional($review->created_at)->format('d/m/Y H:i') }}
                                            </div>
                                        </div>

                                        @if(filled($review->comment))
                                        <p class="mb-0 text-secondary">{{ $review->comment }}</p>
                                        @else
                                        <p class="mb-0 text-secondary fst-italic">Khách hàng đã để lại đánh giá sao mà không viết nhận xét.</p>
                                        @endif
                                    </div>
                                </div>
                            </article>
                            @empty
                            <div class="review-card p-4 text-secondary" data-review-empty>
                                Chưa có nhận xét nào. Hãy là người đầu tiên đánh giá sản phẩm này sau khi nhận hàng.
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mt-5 pt-4">
            <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
                <div>
                    <h2 class="section-title h2 mb-2">Sản phẩm liên quan</h2>
                    <p class="text-secondary mb-0">Gợi ý riêng cho gu thưởng thức của bạn.</p>
                </div>
                <a href="{{ route('products.index') }}" class="btn btn-outline-primary">Xem tất cả</a>
            </div>

            <div class="row g-4">
                @forelse($relatedProducts as $item)
                <div class="col-sm-6 col-lg-3">
                    <div class="related-card drink-card card border-0 h-100 overflow-hidden">
                        <a href="{{ route('products.show', $item->slug) }}">
                            <x-product-image
                                :src="$item->image_url ?? null"
                                :sku="$item->sku ?? null"
                                :name="$item->name"
                                :alt="$item->name"
                                :category="$item->category?->name"
                                class="card-img-top"
                                style="aspect-ratio: 4/3;" />
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
            const input = document.querySelector(`[data-choice-input="${group.dataset.choiceGroup}"]`);

            group.querySelectorAll('.choice-btn').forEach(function (button) {
                button.addEventListener('click', function () {
                    group.querySelectorAll('.choice-btn').forEach(function (item) {
                        item.classList.remove('active');
                    });
                    button.classList.add('active');

                    if (input) {
                        input.value = button.dataset.choiceValue || '';
                    }
                });
            });
        });

        document.querySelectorAll('[data-compact-choice]').forEach(function (select) {
            const input = document.querySelector(`[data-choice-input="${select.dataset.compactChoice}"]`);
            const toggle = select.querySelector('.compact-select-toggle');
            const label = select.querySelector('[data-compact-label]');
            const options = select.querySelectorAll('.compact-select-option');

            if (!input) {
                return;
            }

            const activeOption = select.querySelector('.compact-select-option.active') || options[0];
            input.value = activeOption?.dataset.value || input.value;
            if (label && activeOption) {
                label.textContent = activeOption.textContent.trim();
            }

            toggle?.addEventListener('click', function () {
                document.querySelectorAll('.compact-select.open').forEach(function (item) {
                    if (item !== select) {
                        item.classList.remove('open');
                    }
                });
                select.classList.toggle('open');
            });

            options.forEach(function (option) {
                option.addEventListener('click', function () {
                    options.forEach((item) => item.classList.remove('active'));
                    option.classList.add('active');
                    input.value = option.dataset.value || '';
                    if (label) {
                        label.textContent = option.textContent.trim();
                    }
                    select.classList.remove('open');
                });
            });
        });

        document.addEventListener('click', function (event) {
            if (!event.target.closest('.compact-select')) {
                document.querySelectorAll('.compact-select.open').forEach((item) => item.classList.remove('open'));
            }
        });

        const minus = document.querySelector('[data-qty-minus]');
        const plus = document.querySelector('[data-qty-plus]');
        const value = document.querySelector('[data-qty-value]');
        const qtyInput = document.querySelector('[data-qty-input]');

        if (minus && plus && value) {
            let qty = 1;
            const render = function() {
                value.textContent = qty;
                if (qtyInput) {
                    qtyInput.value = qty;
                }
            };

            minus.addEventListener('click', function() {
                qty = Math.max(1, qty - 1);
                render();
            });

            plus.addEventListener('click', function() {
                qty += 1;
                render();
            });
        }

        // If user navigated with #reviews, scroll to review form and focus
        if (window.location.hash === '#reviews') {
            const reviewPanel = document.querySelector('.review-form-panel');
            const reviewComment = document.getElementById('review-comment');
            if (reviewPanel) {
                // Scroll the review panel into view and focus the textarea when possible
                reviewPanel.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
                setTimeout(function() {
                    if (reviewComment) {
                        reviewComment.focus();
                    } else {
                        // fallback: focus first input inside the panel
                        const firstInput = reviewPanel.querySelector('input, textarea, button');
                        firstInput?.focus();
                    }
                }, 450);
            }
        }

        const sizeGroup = document.querySelector('[data-size-group]');
        const sizeInput = document.querySelector('[data-size-input]');

        if (sizeGroup && sizeInput) {
            sizeGroup.querySelectorAll('[data-size-option]').forEach(function(button) {
                button.addEventListener('click', function() {
                    sizeGroup.querySelectorAll('[data-size-option]').forEach(function(item) {
                        item.classList.remove('active');
                    });
                    button.classList.add('active');
                    sizeInput.value = button.dataset.sizeOption || 'M';
                });
            });
        }

        const toppingGroup = document.querySelector('[data-topping-group]');
        const toppingInput = document.querySelector('[data-topping-input]');

        if (toppingGroup && toppingInput) {
            const syncToppings = function () {
                const toppings = Array.from(toppingGroup.querySelectorAll('.topping-choice.active')).map(function (button) {
                    return {
                        name: button.dataset.toppingName || '',
                        price: Number(button.dataset.toppingPrice || 0),
                    };
                });

                toppingInput.value = JSON.stringify(toppings);
            };

            toppingGroup.querySelectorAll('.topping-choice').forEach(function (button) {
                button.addEventListener('click', function () {
                    button.classList.toggle('active');
                    syncToppings();
                });
            });
        }

        const mainImage = document.getElementById('detailMainImage');
        const thumbs = document.querySelectorAll('[data-detail-thumb]');
        const prevButton = document.querySelector('[data-gallery-prev]');
        const nextButton = document.querySelector('[data-gallery-next]');
        let activeImageIndex = 0;

        const setActiveImage = function(index) {
            if (!mainImage || !thumbs.length) {
                return;
            }

            activeImageIndex = (index + thumbs.length) % thumbs.length;

            thumbs.forEach(function(item) {
                item.classList.remove('active');
            });

            const activeThumb = thumbs[activeImageIndex];
            activeThumb.classList.add('active');
            mainImage.style.opacity = '0';

            setTimeout(function() {
                mainImage.onerror = function() {
                    mainImage.onerror = null;
                    mainImage.src = mainImage.dataset.detailFallback || '';
                };
                mainImage.src = activeThumb.dataset.detailThumb;
                mainImage.style.opacity = '1';
            }, 120);
        };

        if (mainImage && thumbs.length) {
            thumbs.forEach(function(thumb, index) {
                thumb.addEventListener('click', function() {
                    setActiveImage(index);
                });
            });

            prevButton?.addEventListener('click', function() {
                setActiveImage(activeImageIndex - 1);
            });

            nextButton?.addEventListener('click', function() {
                setActiveImage(activeImageIndex + 1);
            });
        }

        const reviewForm = document.querySelector('[data-review-form]');

        if (reviewForm) {
            const submitButton = reviewForm.querySelector('[data-review-submit]');
            const alertContainer = document.querySelector('[data-review-alerts]');
            const ratingInputs = reviewForm.querySelectorAll('input[name="rating"]');
            const commentField = reviewForm.querySelector('[name="comment"]');
            const ratingError = reviewForm.querySelector('[data-error-target="rating"]');
            const commentError = reviewForm.querySelector('[data-error-target="comment"]');
            const remainingBadge = document.querySelector('[data-review-remaining-badge]');
            const infoAlert = document.querySelector('[data-review-info]');
            const reviewList = document.querySelector('[data-review-list]');
            const emptyState = document.querySelector('[data-review-empty]');
            const summaryAverage = document.querySelector('[data-review-average]');
            const summaryAverageStars = document.querySelector('[data-review-average-stars]');
            const summaryTotalText = document.querySelector('[data-review-total]');

            const starRows = Array.from(document.querySelectorAll('[data-review-star-row]'));

            const renderStars = function(average) {
                const starIcons = Array.from({
                    length: 5
                }, function(_, index) {
                    const starNumber = index + 1;
                    if (average >= starNumber) {
                        return '<i class="bi bi-star-fill"></i>';
                    }
                    if (average >= starNumber - 0.5) {
                        return '<i class="bi bi-star-half"></i>';
                    }

                    return '<i class="bi bi-star"></i>';
                }).join('');

                if (summaryAverageStars) {
                    summaryAverageStars.innerHTML = starIcons;
                }
            };

            const formatNumber = function(number, minimumFractionDigits = 0) {
                return Number(number || 0).toLocaleString('vi-VN', {
                    minimumFractionDigits,
                    maximumFractionDigits: minimumFractionDigits,
                });
            };

            const clearErrors = function() {
                [ratingError, commentError].forEach(function(element) {
                    if (element) {
                        element.classList.add('d-none');
                        element.textContent = '';
                    }
                });

                ratingInputs.forEach(function(input) {
                    input.classList.remove('is-invalid');
                });

                if (commentField) {
                    commentField.classList.remove('is-invalid');
                }
            };

            let alertTimeoutId = null;

            const showAlert = function(message, type) {
                if (!alertContainer) {
                    return;
                }

                if (alertTimeoutId) {
                    clearTimeout(alertTimeoutId);
                }

                alertContainer.innerHTML = '';
                const alert = document.createElement('div');
                alert.className = 'alert alert-' + type;
                alert.textContent = message;
                alertContainer.appendChild(alert);

                alertTimeoutId = setTimeout(function() {
                    alert.classList.add('fade');
                    setTimeout(function() {
                        alert.remove();
                    }, 150);
                }, 5000);
            };

            const resetButtonState = function() {
                if (!submitButton) {
                    return;
                }

                submitButton.disabled = false;
                submitButton.innerHTML = 'Gửi đánh giá';
            };

            reviewForm.addEventListener('submit', function(event) {
                event.preventDefault();

                if (!submitButton) {
                    return;
                }

                clearErrors();
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Đang gửi...';

                const formData = new FormData(reviewForm);

                fetch(reviewForm.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                    },
                    body: formData,
                }).then(function(response) {
                    return response.json().then(function(data) {
                        if (!response.ok) {
                            const error = new Error('Đã xảy ra lỗi');
                            error.response = response;
                            error.data = data;
                            throw error;
                        }

                        return data;
                    });
                }).then(function(data) {
                    showAlert(data.message || 'Đánh giá của bạn đã được lưu.', 'success');

                    if (ratingInputs.length) {
                        ratingInputs.forEach(function(input) {
                            input.checked = false;
                        });
                    }

                    if (commentField) {
                        commentField.value = '';
                    }

                    if (data.review && reviewList) {
                        if (emptyState) {
                            emptyState.remove();
                        }

                        const reviewItem = document.createElement('article');
                        reviewItem.className = 'review-card p-4';
                        const starsHtml = Array.from({
                            length: 5
                        }, function(_, index) {
                            const starNumber = index + 1;
                            return '<i class="bi ' + (data.review.rating >= starNumber ? 'bi-star-fill' : 'bi-star') + '"></i>';
                        }).join('');

                        reviewItem.innerHTML = `
                            <div class="d-flex gap-3">
                                <span class="review-avatar">${data.review.initial || 'U'}</span>
                                <div class="flex-grow-1">
                                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                                        <div>
                                            <div class="fw-bold">${data.review.user_name || 'Khách hàng'}</div>
                                            <div class="review-star-row small">${starsHtml}</div>
                                        </div>
                                        <div class="text-secondary small">${data.review.created_at || ''}</div>
                                    </div>
                                    ${data.review.comment ? `<p class="mb-0 text-secondary"></p>` : `<p class="mb-0 text-secondary fst-italic">Khách hàng đã để lại đánh giá sao mà không viết nhận xét.</p>`}
                                </div>
                            </div>
                        `;

                        const commentParagraph = reviewItem.querySelector('p.mb-0.text-secondary');
                        if (commentParagraph && data.review.comment) {
                            commentParagraph.textContent = data.review.comment;
                        }

                        reviewList.prepend(reviewItem);
                    }

                    if (data.summary) {
                        const average = Number(data.summary.average || 0);

                        if (summaryAverage) {
                            summaryAverage.textContent = formatNumber(average, 1);
                        }

                        renderStars(average);

                        if (summaryTotalText) {
                            const totalText = Number(data.summary.count || 0) > 0 ?
                                `${formatNumber(data.summary.count)} lượt đánh giá từ khách đã mua` :
                                'Chưa có đánh giá nào cho sản phẩm này.';
                            summaryTotalText.textContent = totalText;
                        }

                        starRows.forEach(function(row) {
                            const starValue = Number(row.dataset.reviewStarRow || 0);
                            const totalStarCount = Number(data.summary.counts?.[starValue] || 0);
                            const percent = Number(data.summary.count || 0) > 0 ?
                                Math.round((totalStarCount / Number(data.summary.count)) * 100) :
                                0;

                            const meter = row.querySelector(`[data-review-star-meter="${starValue}"]`);
                            const countEl = row.querySelector(`[data-review-star-count="${starValue}"]`);

                            if (meter) {
                                meter.style.width = `${percent}%`;
                            }

                            if (countEl) {
                                countEl.textContent = formatNumber(totalStarCount);
                            }
                        });
                    }

                    if (remainingBadge) {
                        const remaining = Number(data.remaining_reviews || 0);
                        if (remaining > 0) {
                            remainingBadge.textContent = `Còn ${formatNumber(remaining)} lượt đánh giá`;
                            remainingBadge.classList.remove('d-none');
                        } else {
                            remainingBadge.textContent = '';
                            remainingBadge.classList.add('d-none');
                        }
                    }

                    if (infoAlert) {
                        infoAlert.textContent = '';
                        infoAlert.classList.add('d-none');
                    }

                    if (data.can_review === false && reviewForm) {
                        reviewForm.classList.add('d-none');
                    }
                }).catch(function(error) {
                    const errors = error?.data?.errors;

                    if (error?.response?.status === 422 && errors) {
                        if (errors.rating && ratingError) {
                            ratingError.textContent = errors.rating[0];
                            ratingError.classList.remove('d-none');
                        }

                        if (errors.comment && commentError) {
                            commentError.textContent = errors.comment[0];
                            commentError.classList.remove('d-none');
                        }

                        if (errors.rating) {
                            ratingInputs.forEach(function(input) {
                                input.classList.add('is-invalid');
                            });
                        }

                        if (errors.comment && commentField) {
                            commentField.classList.add('is-invalid');
                        }

                        showAlert('Vui lòng kiểm tra lại thông tin trước khi gửi.', 'danger');
                    } else if (error?.data?.message) {
                        showAlert(error.data.message, 'danger');
                    } else {
                        showAlert('Không thể gửi đánh giá ngay lúc này. Vui lòng thử lại sau.', 'danger');
                    }
                }).finally(function() {
                    resetButtonState();
                });
            });
        }
    });
</script>
@endsection
