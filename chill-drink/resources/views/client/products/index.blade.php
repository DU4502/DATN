@extends('layouts.client')

@section('title', 'Sản Phẩm')

@section('content')
<section class="py-5">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
            <div>
                <p class="section-kicker mb-1">Menu</p>
                <h1 class="section-title h2 mb-1">Khám phá đồ uống</h1>
                <p class="text-secondary mb-0">Chọn nhanh món bạn thích, thêm vào giỏ và thanh toán thật gọn.</p>
            </div>
            <form action="{{ route('products.index') }}" method="GET" class="d-flex" role="search">
                @if(request('category'))
                    <input type="hidden" name="category" value="{{ request('category') }}">
                @endif
                <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Tìm kiếm sản phẩm...">
                <button type="submit" class="btn btn-primary ms-2">Tìm</button>
            </form>
        </div>

        <div class="row g-4">
            <aside class="col-lg-3">
                <div class="drink-card card border-0 sticky-top" style="top: 100px;">
                    <div class="card-body">
                        <h2 class="h5 fw-bold mb-3">Danh Mục</h2>
                        <div class="list-group list-group-flush rounded-4 overflow-hidden">
                            <a href="{{ route('products.index') }}" class="list-group-item list-group-item-action {{ !request('category') ? 'active' : '' }}">Tất Cả</a>
                            @foreach($categories as $category)
                                <a href="{{ route('products.index', ['category' => $category->id]) }}" class="list-group-item list-group-item-action {{ request('category') == $category->id ? 'active' : '' }}">
                                    {{ $category->name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </aside>

            <div class="col-lg-9">
                @if($products->count() > 0)
                    <div class="row g-4">
                        @foreach($products as $product)
                            <div class="col-sm-6 col-xl-4">
                                <div class="drink-card card border-0 h-100 overflow-hidden">
                                    <a href="{{ route('products.show', $product->slug) }}">
                                        <img src="{{ $product->image }}" alt="{{ $product->name }}" class="card-img-top" style="height: 210px; object-fit: cover;">
                                    </a>
                                    <div class="card-body d-flex flex-column">
                                        <span class="badge rounded-pill align-self-start mb-2" style="background: var(--drink-soft); color: var(--drink-primary);">{{ $product->category->name }}</span>
                                        <h3 class="h5">
                                            <a href="{{ route('products.show', $product->slug) }}" class="text-dark text-decoration-none">{{ $product->name }}</a>
                                        </h3>
                                        <div class="mt-auto d-flex justify-content-between align-items-center gap-3">
                                            <strong class="h5 text-primary mb-0">{{ number_format($product->price, 0, ',', '.') }}đ</strong>
                                            @if($product->stock > 0)
                                                <form action="{{ route('cart.add', $product->id) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="btn btn-primary btn-sm">Thêm vào giỏ</button>
                                                </form>
                                            @else
                                                <span class="badge text-bg-danger">Hết hàng</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4">
                        {{ $products->links() }}
                    </div>
                @else
                    <div class="drink-card card border-0">
                        <div class="card-body text-center py-5">
                            <h2 class="h5 fw-bold">Không tìm thấy sản phẩm</h2>
                            <p class="text-secondary mb-0">Thử tìm kiếm với từ khóa khác.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
