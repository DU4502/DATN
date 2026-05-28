@extends('layouts.client')

@section('title', 'Tài khoản')

@section('content')
<section class="py-5">
    <div class="container">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 p-md-5">
                <span class="badge rounded-pill mb-3" style="background: var(--drink-soft); color: var(--drink-primary);">Tài khoản</span>
                <h1 class="h3 fw-bold mb-2">Xin chào, {{ auth()->user()->name }}</h1>
                <p class="text-secondary mb-4">Bạn đã đăng nhập thành công. Từ đây bạn có thể xem thông tin tài khoản hoặc tiếp tục chọn đồ uống.</p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="{{ route('profile.edit') }}" class="btn btn-primary">Thông tin tài khoản</a>
                    <a href="{{ route('profile.orders') }}" class="btn btn-outline-primary">Đơn hàng của tôi</a>
                    <a href="{{ route('products.index') }}" class="btn btn-outline-primary">Xem sản phẩm</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
