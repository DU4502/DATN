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

    .filter-title {
        color: var(--drink-primary);
        font-size: 0.78rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .category-chip {
        display: block;
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

    .category-chip:hover {
        background: var(--drink-primary-soft);
        color: var(--drink-primary-dark);
        box-shadow: 0 12px 24px rgba(0, 107, 95, 0.10);
        transform: translateX(6px);
    }

    .category-chip.active {
        background: var(--drink-primary);
        color: #ffffff;
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

    .add-round {
        width: 42px;
        height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 0;
        border-radius: 50%;
        background: var(--drink-primary);
        color: #ffffff;
        transition: background 0.18s ease, transform 0.18s ease;
    }

    .add-round:hover {
        background: var(--drink-primary-dark);
        transform: scale(1.05);
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
                        @if(!$uiProductVisible($product->sku ?? null))
                            @continue
                        @endif
                        <div class="col-sm-6 col-xl-4">
                            <article class="shop-product-card">
                                <a href="{{ route('products.show', $product->slug) }}" class="shop-product-image d-block mb-3">
                                    <x-product-image
                                        :sku="$product->sku"
                                        :alt="$product->name"
                                        :name="$product->name"
                                        :category="$product->category?->name"
                                    />
                                    <span class="product-tag">{{ $product->category->name ?? 'Đồ uống' }}</span>
                                </a>

                                <h2 class="h4 fw-bold mb-1">
                                    <a href="{{ route('products.show', $product->slug) }}" class="text-dark text-decoration-none">{{ $product->name }}</a>
                                </h2>
                                <p class="text-secondary small font-monospace mb-2">{{ $product->sku }}</p>
                                <p class="text-secondary mb-4">{{ \Illuminate\Support\Str::limit($product->display_description, 90) }}</p>

                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="h4 fw-bold text-primary mb-0">{{ number_format($product->price ?? 0, 0, ',', '.') }}đ</span>
                                    @if(($product->stock ?? 1) > 0)
                                        <form action="{{ route('cart.add', $product->id) }}" method="POST" data-ajax-cart>
                                            @csrf
                                            <button type="submit" class="add-round" aria-label="Thêm vào giỏ">
                                                <svg width="19" height="19" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14" />
                                                </svg>
                                            </button>
                                        </form>
                                    @else
                                        <span class="badge text-bg-danger rounded-pill">Hết hàng</span>
                                    @endif
                                </div>
                            </article>
                        </div>
                    @empty
                        @foreach([
                            ['Sinh Tố Dâu', 'Dâu tươi chín ngọt xay mịn với sữa, vị chua ngọt thanh mát.', '45.000đ', 'https://images.unsplash.com/photo-1553530666-ba11a7da3888?auto=format&fit=crop&w=700&q=85', 'Bán chạy', 'sinh-to-dau'],
                            ['Matcha Latte Đá', 'Matcha thơm nhẹ kết hợp sữa tươi béo mịn, hợp cho ngày cần tỉnh táo.', '57.000đ', 'https://images.unsplash.com/photo-1515823064-d6e0c04616a7?auto=format&fit=crop&w=700&q=85', '', 'matcha-latte-da'],
                            ['Nước Ép Cam Chanh Dây', 'Cam, chanh dây và soda tạo vị chua ngọt sảng khoái.', '49.000đ', 'https://images.unsplash.com/photo-1544145945-f90425340c7e?auto=format&fit=crop&w=700&q=85', 'Mới', 'citrus-sunset'],
                            ['Trà Sữa Trân Châu', 'Trà sữa đậm vị cùng trân châu mềm, lựa chọn quen thuộc dễ uống.', '62.000đ', 'https://images.unsplash.com/photo-1558857563-b371033873b8?auto=format&fit=crop&w=700&q=85', '', 'tra-sua-tran-chau-demo'],
                            ['Cà Phê Ủ Lạnh', 'Cà phê ủ lạnh êm vị, uống cùng đá viên lớn cực mát.', '52.000đ', 'https://images.unsplash.com/photo-1517701550927-30cf4ba1dba5?auto=format&fit=crop&w=700&q=85', '', 'cold-brew-arctic'],
                            ['Trà Trái Cây Nhiệt Đới', 'Xoài, thanh long và trà xanh tạo một ly trái cây rực rỡ.', '59.000đ', 'https://images.unsplash.com/photo-1622597467836-f3285f2131b8?auto=format&fit=crop&w=700&q=85', '', 'tropical-frost'],
                        ] as $item)
                            <div class="col-sm-6 col-xl-4">
                                <article class="shop-product-card">
                                    <a href="{{ isset($item[5]) ? route('products.show', $item[5]) : route('products.index') }}" class="shop-product-image d-block mb-3">
                                        <img src="{{ $item[3] }}" alt="{{ $item[0] }}">
                                        @if($item[4])
                                            <span class="product-tag">{{ $item[4] }}</span>
                                        @endif
                                    </a>
                                    <h2 class="h4 fw-bold mb-2">{{ $item[0] }}</h2>
                                    <p class="text-secondary mb-4">{{ $item[1] }}</p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="h4 fw-bold text-primary mb-0">{{ $item[2] }}</span>
                                        <form action="{{ route('cart.add', 'demo-' . $item[5]) }}" method="POST" data-ajax-cart>
                                            @csrf
                                            <button type="submit" class="add-round" aria-label="Thêm vào giỏ">
                                                <svg width="19" height="19" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </article>
                            </div>
                        @endforeach
                    @endforelse
                </div>

                @if($products->count() > 0)
                    <div class="mt-4">
                        {{ $products->links() }}
                    </div>
                @else
                    <div class="d-flex justify-content-center align-items-center gap-2 mt-5">
                        <span class="pager-dot">‹</span>
                        <span class="pager-dot active">1</span>
                        <span class="pager-dot">2</span>
                        <span class="pager-dot">3</span>
                        <span class="pager-dot">›</span>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

<a href="{{ route('cart.index') }}" class="position-fixed bottom-0 end-0 m-4 add-round shadow-lg" style="z-index: 30;" aria-label="Giỏ hàng">
    <svg width="21" height="21" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.75 8.25h10.5l-.75 10.5a2.25 2.25 0 0 1-2.25 2.1h-6.5a2.25 2.25 0 0 1-2.25-2.1L4.75 8.25Z" />
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.75 8.25a3.25 3.25 0 0 1 6.5 0" />
    </svg>
    @if(session('cart'))
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">{{ count(session('cart')) }}</span>
    @endif
</a>
@endsection
