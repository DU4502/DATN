@extends('layouts.client')

@section('title', 'Giỏ Hàng')

@section('content')
<section class="py-5">
    <div class="container">
        <h1 class="h2 fw-bold mb-4">Giỏ Hàng</h1>

        @if(session('cart') && count(session('cart')) > 0)
            @php $total = 0; @endphp
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="drink-card card border-0">
                        <div class="list-group list-group-flush">
                            @foreach(session('cart') as $id => $item)
                                @php $subtotal = $item['price'] * $item['quantity']; $total += $subtotal; @endphp
                                <div class="list-group-item p-3">
                                    <div class="row g-3 align-items-center">
                                        <div class="col-auto">
                                            <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}" class="rounded" style="width: 84px; height: 84px; object-fit: cover;">
                                        </div>
                                        <div class="col">
                                            <h2 class="h6 fw-bold mb-1">{{ $item['name'] }}</h2>
                                            <p class="text-primary fw-semibold mb-0">{{ number_format($item['price'], 0, ',', '.') }}đ</p>
                                        </div>
                                        <div class="col-md-auto">
                                            <form action="{{ route('cart.update', $id) }}" method="POST" class="d-flex gap-2">
                                                @csrf
                                                @method('PATCH')
                                                <input type="number" name="quantity" value="{{ $item['quantity'] }}" min="1" max="99" class="form-control text-center" style="width: 84px;">
                                                <button type="submit" class="btn btn-outline-primary">Cập nhật</button>
                                            </form>
                                        </div>
                                        <div class="col-md-auto text-md-end">
                                            <strong>{{ number_format($subtotal, 0, ',', '.') }}đ</strong>
                                            <form action="{{ route('cart.remove', $id) }}" method="POST" class="mt-2">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-link link-danger p-0 text-decoration-none">Xóa</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <form action="{{ route('cart.clear') }}" method="POST" class="mt-3">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger">Xóa Tất Cả</button>
                    </form>
                </div>

                <div class="col-lg-4">
                    <div class="drink-card card border-0 sticky-top" style="top: 96px;">
                        <div class="card-body p-4">
                            <h2 class="h5 fw-bold mb-4">Tổng Đơn Hàng</h2>
                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-secondary">Tạm tính</span>
                                <span>{{ number_format($total, 0, ',', '.') }}đ</span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-secondary">Phí vận chuyển</span>
                                <span>Miễn phí</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between h5">
                                <span>Tổng cộng</span>
                                <strong class="text-primary">{{ number_format($total, 0, ',', '.') }}đ</strong>
                            </div>

                            @auth
                                <a href="{{ route('checkout.index') }}" class="btn btn-primary w-100 mt-3">Thanh Toán</a>
                            @else
                                <a href="{{ route('login') }}" class="btn btn-primary w-100 mt-3">Đăng Nhập Để Thanh Toán</a>
                            @endauth

                            <a href="{{ route('products.index') }}" class="btn btn-outline-secondary w-100 mt-2">Tiếp Tục Mua Hàng</a>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="drink-card card border-0">
                <div class="card-body text-center py-5">
                    <h2 class="h4 fw-bold">Giỏ hàng trống</h2>
                    <p class="text-secondary">Bạn chưa có sản phẩm nào trong giỏ hàng.</p>
                    <a href="{{ route('products.index') }}" class="btn btn-primary">Mua Sắm Ngay</a>
                </div>
            </div>
        @endif
    </div>
</section>
@endsection
