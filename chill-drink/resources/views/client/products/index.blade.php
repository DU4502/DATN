@extends('layouts.client')

@section('title', 'Sản Phẩm')

@section('content')
@php extract(require resource_path('views/partials/ui-product-data.php')); @endphp
<style>
    .shop-page {
        padding-top: 3rem;
        padding-bottom: 5rem;
    }

    .shop-heading {
        max-width: 720px;
    }

    .shop-sidebar {
        position: sticky;
        top: 108px;
    }

    .filter-panel,
    .promo-panel,
    .shop-product-card {
        border: 1px solid var(--drink-border);
        border-radius: 24px;
        background: rgba(255, 255, 255, 0.84);
        box-shadow: 0 18px 42px rgba(79, 183, 168, 0.10);
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
        min-height: 46px;
        display: flex;
        align-items: center;
        justify-content: flex-start;
        width: 100%;
        border: 0;
        border-radius: 999px;
        background: transparent;
        color: var(--drink-muted);
        font-weight: 800;
        padding: 0.82rem 1rem;
        text-align: left;
        text-decoration: none;
        transition: background 0.2s ease, color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
    }

    .category-list {
        gap: 0.35rem !important;
        margin-top: 0;
    }

    .category-chip:hover {
        background: var(--drink-primary-soft);
        color: var(--drink-primary-dark);
        box-shadow: 0 12px 24px rgba(0, 107, 95, 0.10);
        transform: translateX(6px);
    }

    .category-chip.active {
        background: #008b7a !important;
        color: #ffffff !important;
        box-shadow: 0 12px 24px rgba(0, 107, 95, 0.16);
    }

    .category-chip.active:hover {
        background: #006b5f !important;
        color: #ffffff !important;
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
        padding: 1rem;
        height: 100%;
        display: flex;
        flex-direction: column;
        transition: transform 0.24s ease, box-shadow 0.24s ease, border-color 0.24s ease;
    }

    .shop-product-card:hover {
        border-color: rgba(0, 139, 122, 0.38);
        transform: translateY(-8px);
        box-shadow: 0 28px 58px rgba(0, 107, 95, 0.18);
    }

    .shop-product-image {
        position: relative;
        overflow: hidden;
        border-radius: 18px;
        aspect-ratio: 1 / 1;
        background: var(--drink-primary-soft);
    }

    .shop-product-image img,
    .shop-product-image .product-image {
        width: 100%;
        height: 100%;
        min-height: 100%;
        object-fit: cover;
        display: block;
        background: var(--drink-primary-soft);
        transition: transform 0.55s ease, filter 0.35s ease;
    }

    .shop-product-card:hover .shop-product-image img {
        filter: saturate(1.12) contrast(1.03);
        transform: scale(1.07);
    }

    .product-tag {
        position: absolute;
        top: 0.8rem;
        right: 0.8rem;
        border-radius: 999px;
        background: #dff4ef;
        color: var(--drink-primary-dark);
        font-size: 0.72rem;
        font-weight: 800;
        padding: 0.38rem 0.75rem;
    }

    .shop-product-title {
        min-height: 3.75rem;
        display: -webkit-box;
        overflow: hidden;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
        line-height: 1.18;
    }

    .shop-product-sku {
        min-height: 1.25rem;
    }

    .shop-product-desc {
        min-height: 4.75rem;
        display: -webkit-box;
        overflow: hidden;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 3;
    }

    .shop-product-actions {
        margin-top: auto;
    }

    .add-round {
        width: 46px;
        height: 46px;
        min-width: 46px;
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
        width: 44px;
        height: 44px;
        border: 0;
        border-radius: 999px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--c-primary-light, var(--drink-primary-soft));
        color: var(--c-primary, var(--drink-primary));
        transition: all 0.2s ease;
    }

    .shop-product-card:hover .product-cart-btn,
    .product-cart-btn:hover {
        background: var(--c-primary, var(--drink-primary));
        color: #ffffff;
        transform: scale(1.1);
    }

    .product-cart-btn i {
        line-height: 1;
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
        object-fit: cover;
        background: var(--drink-primary-soft);
        flex: 0 0 auto;
    }

    .quick-choice {
        border: 1px solid var(--drink-border);
        border-radius: 999px;
        background: #ffffff;
        color: var(--drink-muted);
        font-weight: 800;
        padding: 0.55rem 0.9rem;
    }

    .quick-choice.active {
        border-color: var(--drink-primary);
        background: var(--drink-primary-soft);
        color: var(--drink-primary-dark);
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
        }
    }
</style>

<section class="shop-page">
    <div class="container">
        <header class="shop-heading mb-5">
            <p class="section-kicker mb-2">Cửa hàng</p>
            <h1 class="section-title display-5 mb-3">Làm mới ngày của bạn</h1>
            <p class="text-secondary fs-5 mb-0">Khám phá menu đồ uống mát lành, từ trà sữa béo nhẹ, cà phê đậm vị đến nước ép và sinh tố trái cây.</p>
        </header>

        <div class="row g-4 g-xl-5">
            <aside class="col-lg-3">
                <div class="shop-sidebar d-flex flex-column gap-4">
                    <div class="filter-panel p-4">
                        <h2 class="filter-title d-flex align-items-center gap-2 mb-3">
                            <span>☰</span>
                            Danh mục
                        </h2>

                        <div class="category-list d-grid gap-2">
                            <a href="{{ route('products.index', request()->only('search')) }}" class="category-chip {{ !request('category') ? 'active' : '' }}">Tất cả đồ uống</a>
                            @forelse($categories as $category)
                                <a href="{{ route('products.index', array_filter(['category' => $category->id, 'search' => $searchQuery ?? request('search')])) }}" class="category-chip {{ request('category') == $category->id ? 'active' : '' }}">
                                    {{ $category->name }}
                                </a>
                            @empty
                                <a href="{{ route('products.index') }}" class="category-chip">Trà sữa</a>
                                <a href="{{ route('products.index') }}" class="category-chip">Cà phê</a>
                                <a href="{{ route('products.index') }}" class="category-chip">Nước ép</a>
                                <a href="{{ route('products.index') }}" class="category-chip">Sinh tố</a>
                            @endforelse
                        </div>

                        <div class="border-top mt-4 pt-4">
                            <h2 class="filter-title mb-3">Khoảng giá</h2>
                            <input class="range-control w-100" type="range" min="0" max="100000" value="50000">
                            <div class="d-flex justify-content-between text-secondary small fw-semibold mt-2">
                                <span>0đ</span>
                                <span>100.000đ</span>
                            </div>
                        </div>

                        <div class="border-top mt-4 pt-4">
                            <h2 class="filter-title mb-3">Sắp xếp</h2>
                            <select class="form-select">
                                <option>Phổ biến nhất</option>
                                <option>Mới nhất</option>
                                <option>Giá thấp đến cao</option>
                                <option>Giá cao đến thấp</option>
                            </select>
                        </div>
                    </div>

                    <div class="promo-panel">
                        <img src="https://images.unsplash.com/photo-1515823064-d6e0c04616a7?auto=format&fit=crop&w=700&q=85" alt="Ưu đãi đồ uống">
                        <div class="promo-panel-content">
                            <p class="small fw-bold text-uppercase mb-1">Ưu đãi giới hạn</p>
                            <h3 class="h4 fw-bold mb-0">Arctic Mint Special</h3>
                        </div>
                    </div>
                </div>
            </aside>

            <div class="col-lg-9">
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

                                <h2 class="h4 fw-bold mb-1 shop-product-title">
                                    <a href="{{ route('products.show', $product->slug) }}" class="text-dark text-decoration-none">{{ $product->name }}</a>
                                </h2>
                                @if(!empty($product->sku))
                                    <p class="text-secondary small font-monospace mb-2 shop-product-sku">{{ $product->sku }}</p>
                                @else
                                    <p class="text-secondary small font-monospace mb-2 shop-product-sku">&nbsp;</p>
                                @endif
                                <p class="text-secondary mb-4 shop-product-desc">{{ \Illuminate\Support\Str::limit($product->display_description, 90) }}</p>

                                <div class="d-flex justify-content-between align-items-center shop-product-actions">
                                    <span class="h4 fw-bold text-primary mb-0">{{ number_format($product->price ?? 0, 0, ',', '.') }}đ</span>
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
                                        >
                                            <i class="bi bi-bag-plus fs-5" aria-hidden="true"></i>
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
                                        <h2 class="h4 fw-bold mb-2 shop-product-title">{{ $item[0] }}</h2>
                                        <p class="text-secondary small font-monospace mb-2 shop-product-sku">&nbsp;</p>
                                        <p class="text-secondary mb-4 shop-product-desc">{{ $item[1] }}</p>
                                        <div class="d-flex justify-content-between align-items-center shop-product-actions">
                                            <span class="h4 fw-bold text-primary mb-0">{{ $item[2] }}</span>
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
                                                <i class="bi bi-bag-plus fs-5" aria-hidden="true"></i>
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
                                    <a href="{{ route('products.index', request()->only('search')) }}" class="btn btn-primary rounded-pill px-4">Xem tất cả</a>
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
                            <button type="button" class="quick-choice" data-value="XL">XL</button>
                            <button type="button" class="quick-choice" data-value="XXL">XXL</button>
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

                    <div>
                        <div class="fw-bold mb-2">Mức đá</div>
                        <div class="d-flex flex-wrap gap-2" data-quick-group="ice">
                            <button type="button" class="quick-choice" data-value="0">Không đá</button>
                            <button type="button" class="quick-choice" data-value="50">Ít đá</button>
                            <button type="button" class="quick-choice active" data-value="100">Bình thường</button>
                        </div>
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

<a href="{{ route('cart.index') }}" class="position-fixed bottom-0 end-0 m-4 add-round shadow-lg" style="z-index: 30;" aria-label="Giỏ hàng" data-cart-button>
    <svg width="21" height="21" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.75 8.25h10.5l-.75 10.5a2.25 2.25 0 0 1-2.25 2.1h-6.5a2.25 2.25 0 0 1-2.25-2.1L4.75 8.25Z" />
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.75 8.25a3.25 3.25 0 0 1 6.5 0" />
    </svg>
    <span data-cart-badge class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger {{ session('cart') ? '' : 'd-none' }}">
        {{ session('cart') ? count(session('cart')) : 0 }}
    </span>
</a>

<script>
    document.addEventListener('DOMContentLoaded', function () {
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
        };

        function setGroupValue(group, value) {
            modalElement.querySelectorAll(`[data-quick-group="${group}"] .quick-choice`).forEach((button) => {
                button.classList.toggle('active', button.dataset.value === value);
            });
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
                setGroupValue('size', 'M');
                setGroupValue('sugar', '50');
                setGroupValue('ice', '100');
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
    });
</script>
@endsection
