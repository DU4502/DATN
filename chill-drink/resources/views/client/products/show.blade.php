@extends('layouts.client')

@section('title', $product->name)

@section('content')
<section class="py-5">
    <div class="container">
        <div class="row g-5 align-items-start">
            <div class="col-lg-6">
                <img src="{{ $product->image }}" alt="{{ $product->name }}" class="img-fluid rounded-3 shadow-sm w-100" style="max-height: 520px; object-fit: cover;">
            </div>
            <div class="col-lg-6">
                <span class="badge text-bg-primary mb-3">{{ $product->category->name }}</span>
                <h1 class="display-6 fw-bold">{{ $product->name }}</h1>
                <p class="h3 text-primary fw-bold my-4">{{ number_format($product->price, 0, ',', '.') }}đ</p>
                <p class="text-secondary">{{ $product->description ?? 'Sản phẩm đồ uống thơm ngon, được chuẩn bị tươi mới mỗi ngày.' }}</p>

                <div class="d-flex flex-wrap gap-3 mt-4">
                    @if($product->stock > 0)
                        <form action="{{ route('cart.add', $product->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-lg">Thêm vào giỏ</button>
                        </form>
                    @else
                        <span class="btn btn-outline-danger btn-lg disabled">Hết hàng</span>
                    @endif
                    <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-lg">Quay lại</a>
                </div>
            </div>
        </div>

        @if($relatedProducts->count() > 0)
            <div class="mt-5">
                <h2 class="h4 fw-bold mb-4">Sản phẩm liên quan</h2>
                <div class="row g-4">
                    @foreach($relatedProducts as $item)
                        <div class="col-sm-6 col-lg-3">
                            <div class="card border-0 shadow-sm h-100">
                                <a href="{{ route('products.show', $item->slug) }}">
                                    <img src="{{ $item->image }}" alt="{{ $item->name }}" class="card-img-top" style="height: 180px; object-fit: cover;">
                                </a>
                                <div class="card-body">
                                    <h3 class="h6"><a href="{{ route('products.show', $item->slug) }}" class="text-dark text-decoration-none">{{ $item->name }}</a></h3>
                                    <strong class="text-primary">{{ number_format($item->price, 0, ',', '.') }}đ</strong>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</section>
@endsection
