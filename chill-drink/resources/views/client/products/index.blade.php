@extends('layouts.client')

@section('title', 'Sản Phẩm')

@section('content')
@php extract(require resource_path('views/partials/ui-product-data.php')); @endphp
<style>
    .shop-page {
        padding-top: 2rem;
        padding-bottom: 5rem;
    }

    .shop-heading {
        max-width: 720px;
    }

    .shop-sidebar {
        position: sticky;
        top: 108px;
    }

    .shop-grid-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.25rem;
    }

    .shop-sort {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        white-space: nowrap;
    }

    .shop-sort .form-select {
        width: auto;
        min-width: 170px;
        min-height: 38px;
        padding-top: 0.4rem;
        padding-right: 2.4rem;
        padding-bottom: 0.4rem;
        font-size: 0.82rem;
        border-radius: var(--radius-sm);
        border: 1.5px solid var(--c-border, #e5e7eb);
        background-color: var(--c-surface, #fff) !important;
        background-image:
            linear-gradient(45deg, transparent 50%, var(--c-muted, #6b7280) 50%),
            linear-gradient(135deg, var(--c-muted, #6b7280) 50%, transparent 50%);
        background-position:
            calc(100% - 18px) calc(50% - 2px),
            calc(100% - 12px) calc(50% - 2px);
        background-size: 6px 6px, 6px 6px;
        background-repeat: no-repeat !important;
        appearance: none !important;
        -webkit-appearance: none !important;
    }

    .shop-sort .form-select:focus {
        border-color: var(--c-primary, #0d9373);
        box-shadow: 0 0 0 3px var(--c-primary-glow, rgba(13, 147, 115, 0.15));
    }

    .sort-dropdown {
        position: relative;
        min-width: 190px;
    }

    .sort-dropdown-toggle {
        width: 100%;
        min-height: 42px;
        border: 1.5px solid var(--c-border, #e5e7eb);
        border-radius: var(--radius-sm, 8px);
        padding: 0.55rem 0.8rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        background: #fff;
        color: var(--c-ink, #111827);
        font-weight: 800;
        cursor: pointer;
        transition: border-color 0.16s ease, box-shadow 0.16s ease;
    }

    .sort-dropdown.open .sort-dropdown-toggle,
    .sort-dropdown-toggle:focus {
        border-color: var(--c-primary, #0d9373);
        box-shadow: 0 0 0 3px rgba(13, 147, 115, 0.13);
    }

    .sort-dropdown-toggle i {
        color: var(--c-muted, #6b7280);
        transition: transform 0.16s ease;
    }

    .sort-dropdown.open .sort-dropdown-toggle i {
        transform: rotate(180deg);
    }

    .sort-dropdown-menu {
        position: absolute;
        top: calc(100% + 0.35rem);
        left: 0;
        right: 0;
        z-index: 50;
        display: none;
        overflow: hidden;
        border: 1px solid var(--c-border, #e5e7eb);
        border-radius: var(--radius-sm, 8px);
        background: #fff;
        box-shadow: var(--shadow-lg);
    }

    .sort-dropdown.open .sort-dropdown-menu {
        display: block;
    }

    .sort-dropdown-option {
        width: 100%;
        border: 0;
        background: #fff;
        color: var(--c-ink, #111827);
        text-align: left;
        padding: 0.72rem 0.9rem;
        font-weight: 700;
    }

    .sort-dropdown-option:hover,
    .sort-dropdown-option.active {
        background: var(--c-primary-light, #e6f7f2);
        color: var(--c-primary-dark, #067a5f);
    }

    .filter-panel,
    .promo-panel,
    .shop-product-card {
        border: 1px solid var(--drink-border);
        border-radius: var(--radius-md);
        background: rgba(255, 255, 255, 0.84);
        box-shadow: var(--shadow-sm);
    }

    .filter-panel {
        overflow: hidden;
    }

    .filter-title {
        color: var(--drink-primary);
        font-size: 0.78rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .category-chip {
        min-height: 30px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        width: 100%;
        border: 0;
        border-radius: var(--radius-sm);
        background: transparent;
        color: var(--drink-muted);
        font-size: 0.84rem;
        font-weight: 700;
        padding: 0.34rem 0;
        text-align: left;
        text-decoration: none;
        transition: background 0.2s ease, color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
    }

    .category-radio {
        width: 18px;
        height: 18px;
        flex: 0 0 auto;
        border-radius: 50%;
        border: 1.5px solid var(--c-subtle, #9ca3af);
        background: #ffffff;
        transition: border-color 0.2s ease, background 0.2s ease, box-shadow 0.2s ease;
    }

    .category-list {
        gap: 0.15rem !important;
        margin-top: 0;
    }

    .category-chip:hover {
        background: transparent;
        color: var(--drink-primary-dark);
        box-shadow: none;
        transform: translateX(3px);
    }

    .category-chip.active {
        background: transparent !important;
        color: var(--drink-primary) !important;
        box-shadow: none;
    }

    .category-chip.active:hover {
        background: transparent !important;
        color: var(--drink-primary-dark) !important;
    }

    .category-chip.active .category-radio {
        border-color: var(--drink-primary, #0d9373);
        background: var(--drink-primary, #0d9373);
        box-shadow: 0 0 0 3px rgba(13, 147, 115, 0.16);
    }

    .category-chip.active .category-radio::after {
        content: "";
        display: block;
        width: 6px;
        height: 6px;
        margin: 4.5px auto 0;
        border-radius: 50%;
        background: #ffffff;
    }

    .range-control {
        accent-color: var(--drink-primary);
    }

    .promo-panel {
        position: relative;
        min-height: 250px;
        overflow: hidden;
        color: #ffffff;
    }

    .promo-panel img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.7s ease;
    }

    .promo-panel:hover img {
        transform: scale(1.08);
    }

    .promo-panel::after {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, transparent 15%, rgba(0, 82, 70, 0.84));
    }

    .promo-panel-content {
        position: relative;
        z-index: 1;
        min-height: 250px;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        padding: 1.5rem;
    }

    .shop-product-card {
        padding: 0.75rem;
        height: 100%;
        display: flex;
        flex-direction: column;
        transition: transform 0.24s ease, box-shadow 0.24s ease, border-color 0.24s ease;
    }

    .shop-product-card:hover {
        border-color: rgba(0, 139, 122, 0.38);
        transform: translateY(-5px);
        box-shadow: var(--shadow-lg), var(--shadow-glow);
    }

    .shop-product-image {
        position: relative;
        overflow: hidden;
        border-radius: var(--radius-sm);
        aspect-ratio: 4 / 3;
        background: var(--drink-primary-soft);
    }

    .shop-product-image img,
    .shop-product-image .product-image {
        width: 100%;
        height: 100%;
        min-height: 100%;
        object-fit: contain !important;
        object-position: center !important;
        padding: 0.55rem;
        display: block;
        background: var(--drink-primary-soft) !important;
        transition: transform 0.55s ease, filter 0.35s ease;
    }

    .shop-product-card:hover .shop-product-image img {
        filter: saturate(1.12) contrast(1.03);
        transform: scale(1.07);
    }

    .product-tag {
        position: absolute;
        top: 0.55rem;
        left: 0.55rem;
        right: auto;
        border-radius: var(--radius-sm);
        background: #dff4ef;
        color: var(--drink-primary-dark);
        font-size: 0.65rem;
        font-weight: 800;
        padding: 0.22rem 0.5rem;
    }

    .shop-product-title {
        min-height: 2.6rem;
        display: -webkit-box;
        overflow: hidden;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
        line-height: 1.2;
    }

    .shop-product-meta {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.75rem;
    }

    .product-rating-mini {
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        gap: 0.18rem;
        color: #f59e0b;
        font-size: 0.78rem;
        font-weight: 800;
        line-height: 1;
        white-space: nowrap;
        margin-top: 0.3rem;
    }

    .product-rating-mini i {
        font-size: 0.72rem;
    }

    .shop-product-sku {
        min-height: 1.25rem;
    }

    .shop-product-desc {
        min-height: 2.75rem;
        display: -webkit-box;
        overflow: hidden;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
    }

    .shop-product-actions {
        margin-top: auto;
    }

    .add-round {
        width: 56px;
        height: 56px;
        min-width: 56px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 0;
        border-radius: 50%;
        background: #008b7a;
        color: #ffffff;
        font-size: 1.75rem;
        font-weight: 900;
        line-height: 1;
        text-decoration: none;
        appearance: none;
        -webkit-appearance: none;
        box-shadow: 0 14px 28px rgba(0, 107, 95, 0.22);
        position: relative;
        z-index: 2;
        transition: background 0.18s ease, transform 0.18s ease, box-shadow 0.18s ease;
    }

    .floating-cart-button {
        right: 1.5rem !important;
        bottom: 1.5rem !important;
        margin: 0 !important;
    }

    .floating-cart-button svg {
        width: 23px;
        height: 23px;
    }

    .floating-cart-button i {
        font-size: 1.35rem !important;
        line-height: 1;
    }

    .floating-cart-button [data-cart-badge] {
        min-width: 22px;
        height: 22px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        top: -2px !important;
        left: auto !important;
        right: -3px !important;
        transform: none !important;
        border: 2px solid #fff;
        font-size: 0.75rem;
        line-height: 1;
        box-shadow: 0 8px 18px rgba(239, 68, 68, 0.25);
    }

    .shop-product-card button.add-round,
    .shop-product-card a.add-round {
        background: #008b7a !important;
        color: #ffffff !important;
        opacity: 1 !important;
    }

    .add-round:hover {
        background: #006b5f;
        transform: scale(1.05);
        box-shadow: 0 18px 34px rgba(0, 107, 95, 0.28);
        color: #ffffff;
    }

    .shop-product-card form[data-ajax-cart] {
        flex: 0 0 auto;
        margin: 0;
    }

    .add-round svg,
    .add-round i,
    .add-round .add-round-symbol {
        display: block;
        color: currentColor !important;
        font-size: 1.75rem;
        font-weight: 900;
        line-height: 1;
    }

    .add-round .add-round-symbol {
        transform: translateY(-1px);
    }

    .product-cart-btn {
        width: 42px;
        height: 42px;
        border: 0;
        border-radius: 14px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--drink-primary-soft);
        color: var(--c-primary, var(--drink-primary));
        box-shadow: inset 0 0 0 1px rgba(13, 147, 115, 0.12);
        transition: background 0.2s ease, color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
    }

    .shop-product-card:hover .product-cart-btn,
    .product-cart-btn:hover {
        background: var(--c-primary, var(--drink-primary));
        color: #ffffff;
        transform: translateY(-1px);
        box-shadow: 0 10px 20px rgba(13, 147, 115, 0.24);
    }

    .product-cart-btn i {
        font-size: 1rem !important;
        line-height: 1;
    }

    .product-detail-btn {
        min-height: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 1 1 auto;
        border-radius: var(--radius-sm);
        background: var(--c-border-light, #f3f4f6);
        color: var(--c-ink, #111827);
        font-size: 0.78rem;
        font-weight: 800;
        text-decoration: none;
        transition: background 0.2s ease, color 0.2s ease;
    }

    .product-detail-btn:hover {
        background: var(--drink-primary-soft);
        color: var(--drink-primary-dark);
    }

    .shop-empty-state {
        border: 1px solid var(--drink-border);
        border-radius: 24px;
        background: rgba(255, 255, 255, 0.86);
        box-shadow: 0 18px 42px rgba(79, 183, 168, 0.10);
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
        border: 1px solid var(--drink-border);
        padding: 0.35rem;
        flex: 0 0 auto;
    }

    .quick-topping-choice {
        min-width: 150px;
        border-radius: 14px;
        text-align: left;
    }

    .quick-topping-choice small {
        display: block;
        margin-top: 0.1rem;
        font-size: 0.72rem;
        opacity: 0.82;
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

    .quick-choice.active:hover {
        background: var(--c-primary-dark, #067a5f) !important;
        border-color: var(--c-primary-dark, #067a5f) !important;
        color: #ffffff !important;
    }

    .pager-dot {
        width: 42px;
        height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--drink-border);
        border-radius: 50%;
        color: var(--drink-muted);
        font-weight: 800;
        text-decoration: none;
    }

    .pager-dot.active,
    .pager-dot:hover {
        background: var(--drink-primary);
        color: #ffffff;
        border-color: var(--drink-primary);
    }

    .pagination {
        justify-content: center;
        gap: 0.4rem;
    }

    .pagination .page-link {
        min-width: 36px;
        min-height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--radius-sm) !important;
        color: var(--drink-primary);
        border-color: var(--drink-border);
        font-weight: 800;
    }

    .pagination .active .page-link,
    .pagination .page-link:hover {
        background: var(--drink-primary);
        border-color: var(--drink-primary);
        color: #fff;
    }

    @media (max-width: 991.98px) {
        .shop-sidebar {
            position: static;
        }

        .category-list {
            display: flex;
            gap: 0.65rem;
            overflow-x: auto;
            padding-bottom: 0.25rem;
        }

        .category-chip {
            flex: 0 0 auto;
            width: auto;
            white-space: nowrap;
            border: 1px solid var(--drink-border);
            padding: 0.55rem 0.75rem;
        }

        .category-radio { display: none; }

        .shop-grid-head {
            align-items: flex-start;
            flex-direction: column;
        }
    }
</style>

<section class="shop-page">
    <div class="container">
        <div class="row g-4">
            <aside class="col-lg-3">
                <div class="shop-sidebar d-flex flex-column gap-4">
                    <div class="filter-panel p-4">
                        <h2 class="h5 fw-bold mb-4">
                            Bộ lọc
                        </h2>

                        <h3 class="filter-title mb-3">Danh mục</h3>
                        <div class="category-list d-grid gap-2">
                            <a href="{{ route('products.index') }}" class="category-chip {{ !request('category') && empty($searchQuery) ? 'active' : '' }}">
                                <span>Tất cả</span>
                                <span class="category-radio" aria-hidden="true"></span>
                            </a>
                            @forelse($categories as $category)
                                <a href="{{ route('products.index', ['category' => $category->id]) }}" class="category-chip {{ request('category') == $category->id ? 'active' : '' }}">
                                    <span>{{ $category->name }}</span>
                                    <span class="category-radio" aria-hidden="true"></span>
                                </a>
                            @empty
                                <a href="{{ route('products.index') }}" class="category-chip"><span>Trà sữa</span><span class="category-radio" aria-hidden="true"></span></a>
                                <a href="{{ route('products.index') }}" class="category-chip"><span>Cà phê</span><span class="category-radio" aria-hidden="true"></span></a>
                                <a href="{{ route('products.index') }}" class="category-chip"><span>Nước ép</span><span class="category-radio" aria-hidden="true"></span></a>
                                <a href="{{ route('products.index') }}" class="category-chip"><span>Sinh tố</span><span class="category-radio" aria-hidden="true"></span></a>
                            @endforelse
                        </div>

                        <div class="border-top mt-4 pt-4">
                            <h3 class="filter-title mb-3">Lọc theo giá</h3>
                            <input class="range-control w-100" type="range" min="0" max="100000" value="50000">
                            <div class="d-flex justify-content-between text-secondary small fw-semibold mt-2">
                                <span>0đ</span>
                                <span>100.000đ</span>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="button" class="btn btn-primary w-100 fw-bold">Áp dụng bộ lọc</button>
                        </div>
                    </div>
                </div>
            </aside>

            <div class="col-lg-9">
                <div class="shop-grid-head">
                    <h1 class="section-title h2 mb-0">Sản phẩm nổi bật</h1>
                    <form action="{{ route('products.index') }}" method="GET" class="shop-sort">
                        @if(request('category'))
                            <input type="hidden" name="category" value="{{ request('category') }}">
                        @endif
                        @if(!empty($searchQuery))
                            <input type="hidden" name="search" value="{{ $searchQuery }}">
                        @endif
                        <label class="small fw-bold text-secondary mb-0">Sắp xếp theo:</label>
@php
    $sortOptions = [
        '' => 'Bán chạy nhất',
        'newest' => 'Mới nhất',
        'price_asc' => 'Giá thấp đến cao',
        'price_desc' => 'Giá cao đến thấp',
    ];
    $currentSort = request('sort', '');
    $currentSortLabel = $sortOptions[$currentSort] ?? $sortOptions[''];
@endphp
<input type="hidden" name="sort" value="{{ $currentSort }}" data-sort-input>
<div class="sort-dropdown" data-sort-dropdown>
    <button type="button" class="sort-dropdown-toggle">
        <span data-sort-label>{{ $currentSortLabel }}</span>
        <i class="bi bi-chevron-down"></i>
    </button>
    <div class="sort-dropdown-menu">
        @foreach($sortOptions as $value => $label)
            <button type="button" class="sort-dropdown-option {{ $currentSort === $value ? 'active d-none' : '' }}" data-sort-value="{{ $value }}">
                {{ $label }}
            </button>
        @endforeach
    </div>
</div>
                    </form>
                </div>

                @if(!empty($searchQuery))
                    <div class="search-results-banner">
                        Kết quả tìm kiếm cho <strong>"{{ $searchQuery }}"</strong>
                        — {{ $products->total() }} sản phẩm
                        <a href="{{ route('products.index', request()->only('category')) }}" class="ms-2 text-decoration-none">Xóa tìm kiếm</a>
                    </div>
                @endif

                <div class="row g-4">
                    @forelse($products as $product)
                        <div class="col-sm-6 col-xl-4">
                            <article class="shop-product-card">
                                <a href="{{ route('products.show', $product->slug) }}" class="shop-product-image d-block mb-3">
                                    <x-product-image
                                        :src="$product->image_url"
                                        :sku="$product->sku ?? null"
                                        :alt="$product->name"
                                        :name="$product->name"
                                        :category="$product->category?->name"
                                    />
                                    <span class="product-tag">{{ $product->category->name ?? 'Đồ uống' }}</span>
                                </a>

                                <div class="shop-product-meta mb-1">
                                    <h2 class="h5 fw-bold mb-0 shop-product-title">
                                        <a href="{{ route('products.show', $product->slug) }}" class="text-dark text-decoration-none">{{ $product->name }}</a>
                                    </h2>
                                    @if(($product->reviews_count ?? 0) > 0)
                                        <span class="product-rating-mini" aria-label="Đánh giá {{ number_format((float) $product->reviews_avg_rating, 1) }} sao">
                                            <i class="bi bi-star-fill" aria-hidden="true"></i>
                                            {{ number_format((float) $product->reviews_avg_rating, 1) }}
                                        </span>
                                    @else
                                        <span class="product-rating-mini text-secondary">Chưa có đánh giá</span>
                                    @endif
                                </div>
                                @if(!empty($product->sku))
                                    <p class="text-secondary small font-monospace mb-2 shop-product-sku">{{ $product->sku }}</p>
                                @else
                                    <p class="text-secondary small font-monospace mb-2 shop-product-sku">&nbsp;</p>
                                @endif
                                <p class="text-secondary small mb-3 shop-product-desc">{{ \Illuminate\Support\Str::limit($product->display_description, 70) }}</p>

                                <div class="mb-3">
                                    <span class="h5 fw-bold text-primary mb-0">{{ number_format($product->price ?? 0, 0, ',', '.') }}đ</span>
                                </div>
                                <div class="d-flex align-items-center gap-2 shop-product-actions">
                                    <a href="{{ route('products.show', $product->slug) }}" class="product-detail-btn">Chi tiết</a>
                                    @if(($product->stock ?? 1) > 0)
                                        <button
                                            type="button"
                                            class="product-cart-btn"
                                            aria-label="Chọn size và thêm {{ $product->name }}"
                                            data-quick-add
                                            data-action="{{ route('cart.add', $product->id) }}"
                                            data-name="{{ $product->name }}"
                                            data-price="{{ number_format($product->price ?? 0, 0, ',', '.') }}đ"
                                            data-image="{{ $product->image_url }}"
                                            data-category="{{ $product->category?->name }}"
                                        >
                                            <i class="bi bi-cart-plus" aria-hidden="true"></i>
                                        </button>
                                    @else
                                        <span class="badge text-bg-danger rounded-pill">Hết hàng</span>
                                    @endif
                                </div>
                            </article>
                        </div>
                    @empty
                        @if(($demoProducts ?? collect())->isNotEmpty())
                            @foreach($demoProducts->map(fn ($item) => [
                                $item['name'],
                                $item['description'],
                                number_format($item['price'], 0, ',', '.') . 'đ',
                                $item['image'],
                                $item['category'] === request('category') ? 'Đang chọn' : '',
                                $item['slug'],
                            ]) as $item)
                                <div class="col-sm-6 col-xl-4">
                                    <article class="shop-product-card">
                                        <a href="{{ isset($item[5]) ? route('products.show', $item[5]) : route('products.index') }}" class="shop-product-image d-block mb-3">
                                            <img src="{{ $item[3] }}" alt="{{ $item[0] }}">
                                            @if($item[4])
                                                <span class="product-tag">{{ $item[4] }}</span>
                                            @endif
                                        </a>
                                        <div class="shop-product-meta mb-2">
                                            <h2 class="h5 fw-bold mb-0 shop-product-title">{{ $item[0] }}</h2>
                                            <span class="product-rating-mini text-secondary">Chưa có đánh giá</span>
                                        </div>
                                        <p class="text-secondary small font-monospace mb-2 shop-product-sku">&nbsp;</p>
                                        <p class="text-secondary small mb-3 shop-product-desc">{{ $item[1] }}</p>
                                        <div class="mb-3">
                                            <span class="h5 fw-bold text-primary mb-0">{{ $item[2] }}</span>
                                        </div>
                                        <div class="d-flex align-items-center gap-2 shop-product-actions">
                                            <a href="{{ isset($item[5]) ? route('products.show', $item[5]) : route('products.index') }}" class="product-detail-btn">Chi tiết</a>
                                            <button
                                                type="button"
                                                class="product-cart-btn"
                                                aria-label="Chọn size và thêm {{ $item[0] }}"
                                                data-quick-add
                                                data-action="{{ route('cart.add', 'demo-' . $item[5]) }}"
                                                data-name="{{ $item[0] }}"
                                                data-price="{{ $item[2] }}"
                                                data-image="{{ $item[3] }}"
                                            >
                                                <i class="bi bi-cart-plus" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </article>
                                </div>
                            @endforeach
                        @else
                            <div class="col-12">
                                <div class="shop-empty-state text-center p-5">
                                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width:52px;height:52px;background:var(--drink-primary-soft);color:var(--drink-primary);">
                                        <i class="bi bi-cup-straw fs-4"></i>
                                    </span>
                                    <h2 class="h4 fw-bold mb-2">Chưa có sản phẩm trong mục này</h2>
                                    <p class="text-secondary mb-4">Bạn chọn danh mục khác hoặc quay lại tất cả đồ uống nhé.</p>
                                    <a href="{{ route('products.index') }}" class="btn btn-primary rounded-pill px-4">Xem tất cả</a>
                                </div>
                            </div>
                        @endif
                    @endforelse
                </div>

                @if($products->count() > 0)
                    <div class="mt-4">
                        {{ $products->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

<div class="modal fade quick-add-modal" id="quickAddModal" tabindex="-1" aria-labelledby="quickAddTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="quickAddForm" method="POST" data-ajax-cart>
                @csrf
                <input type="hidden" name="size" value="M" data-quick-size-input>
                <input type="hidden" name="sugar_level" value="50" data-quick-sugar-input>
                <input type="hidden" name="ice_level" value="100" data-quick-ice-input>
                <input type="hidden" name="toppings" value="[]" data-quick-toppings-input>
                <input type="hidden" name="quantity" value="1">

                <div class="modal-header border-0 pb-0">
                    <h2 class="modal-title h4 fw-bold" id="quickAddTitle">Tùy chọn đồ uống</h2>
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

<a href="{{ route('cart.index') }}" class="position-fixed bottom-0 end-0 m-4 add-round floating-cart-button shadow-lg" style="z-index: 30;" aria-label="Giỏ hàng" data-cart-button>
    <i class="bi bi-cart-plus" aria-hidden="true"></i>
    <span data-cart-badge class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger {{ session('cart') ? '' : 'd-none' }}">
        {{ session('cart') ? count(session('cart')) : 0 }}
    </span>
</a>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-sort-dropdown]').forEach((dropdown) => {
            const form = dropdown.closest('form');
            const input = form?.querySelector('[data-sort-input]');
            const label = dropdown.querySelector('[data-sort-label]');
            const toggle = dropdown.querySelector('.sort-dropdown-toggle');
            const options = dropdown.querySelectorAll('.sort-dropdown-option');

            toggle?.addEventListener('click', () => {
                document.querySelectorAll('.sort-dropdown.open').forEach((item) => {
                    if (item !== dropdown) {
                        item.classList.remove('open');
                    }
                });
                dropdown.classList.toggle('open');
            });

            options.forEach((option) => {
                option.addEventListener('click', () => {
                    options.forEach((item) => item.classList.remove('active', 'd-none'));
                    option.classList.add('active', 'd-none');
                    if (input) {
                        input.value = option.dataset.sortValue || '';
                    }
                    if (label) {
                        label.textContent = option.textContent.trim();
                    }
                    dropdown.classList.remove('open');
                    form?.submit();
                });
            });
        });

        document.addEventListener('click', (event) => {
            if (!event.target.closest('[data-sort-dropdown]')) {
                document.querySelectorAll('.sort-dropdown.open').forEach((item) => item.classList.remove('open'));
            }
        });

        const modalElement = document.getElementById('quickAddModal');
        const form = document.getElementById('quickAddForm');

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

            if (fields.toppings) {
                fields.toppings.value = JSON.stringify(toppings);
            }
        }

        function renderQuickToppings(name, category) {
            if (!fields.toppingGroup) {
                return;
            }

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

        fields.toppingGroup?.addEventListener('click', (event) => {
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

