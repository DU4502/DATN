@extends('layouts.admin')

@section('page-title', 'Chi tiết sản phẩm')

@section('content')
<div class="row g-4">
    <div class="col-lg-5">
        <div class="admin-card card border-0 overflow-hidden p-3">
            <x-product-image
                :sku="$product->sku ?? null"
                :name="$product->name"
                :alt="$product->name"
                :category="$product->category?->name"
                class="w-100"
                style="height: 360px; object-fit: contain;"
                :width="800"
            />
        </div>
    </div>

    <div class="col-lg-7">
        <div class="admin-card card border-0">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                    <div>
                        <span class="badge text-bg-info mb-2">{{ $product->category->name ?? 'Chưa phân loại' }}</span>
                        <h2 class="h3 fw-bold mb-1">{{ $product->name }}</h2>
                        <p class="text-secondary mb-0">{{ $product->slug }}</p>
                        @if(!empty($product->sku))
                            <p class="text-secondary small font-monospace mb-0">{{ $product->sku }}</p>
                        @endif
                    </div>
                    <span class="badge {{ $product->status ? 'text-bg-success' : 'text-bg-secondary' }}">
                        {{ $product->status ? 'Đang bán' : 'Đã ẩn' }}
                    </span>
                </div>

                <p class="h4 text-primary fw-bold mb-3">{{ number_format($product->price ?? 0, 0, ',', '.') }}đ</p>
                <p class="text-secondary">{{ $product->description ?: 'Chưa có mô tả.' }}</p>

                <div class="row g-3 my-4">
                    <div class="col-sm-4">
                        <div class="border rounded-3 p-3">
                            <div class="text-secondary small">Tồn kho</div>
                            <div class="h5 fw-bold mb-0">{{ $product->stock ?? 0 }}</div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="border rounded-3 p-3">
                            <div class="text-secondary small">Lượt bán</div>
                            <div class="h5 fw-bold mb-0">{{ $product->order_items_count }}</div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="border rounded-3 p-3">
                            <div class="text-secondary small">Đánh giá</div>
                            <div class="h5 fw-bold mb-0">{{ $product->reviews_count }}</div>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.products.edit', $product->id) }}" class="btn btn-primary">Sửa sản phẩm</a>
                    @if(!empty($product->slug))
                        <a href="{{ route('products.show', $product->slug) }}" class="btn btn-outline-secondary rounded-pill">Xem ngoài web</a>
                    @endif
                    <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary rounded-pill">Quay lại</a>
                    <form action="{{ route('admin.products.destroy', $product->id) }}" method="POST" onsubmit="return confirm('Bạn chắc chắn muốn xóa sản phẩm này?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger rounded-pill">Xóa</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
