@extends('layouts.client')

@section('title', 'Đơn Hàng Của Tôi')

@section('content')
<section class="orders-page py-5">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
            <div>
                <p class="text-primary fw-semibold mb-1">Đơn hàng</p>
                <h1 class="h2 fw-bold mb-1">Đơn hàng của tôi</h1>
                <p class="text-secondary mb-0">Theo dõi lịch sử mua hàng, trạng thái xử lý và tổng thanh toán.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('profile.edit') }}" class="btn btn-outline-primary rounded-pill px-4">
                    <i class="bi bi-person me-1"></i>Tài khoản
                </a>
                <a href="{{ route('products.index') }}" class="btn btn-primary rounded-pill px-4">
                    <i class="bi bi-cup-straw me-1"></i>Mua thêm
                </a>
            </div>
        </div>

        @include('profile.partials.my-orders')
    </div>
</section>
@endsection
