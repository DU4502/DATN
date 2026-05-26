@extends('layouts.client')

@section('title', 'Đơn Hàng Của Tôi')

@section('content')
<section class="py-5">
    <div class="container">
        <div class="mb-4">
            <p class="text-primary fw-semibold mb-1">Tài khoản</p>
            <h1 class="h2 fw-bold mb-0">Đơn hàng của tôi</h1>
        </div>

        <nav class="profile-tabs" aria-label="Mục tài khoản">
            <a href="{{ route('profile.edit') }}" class="profile-tab">Thông tin</a>
            <a href="{{ route('profile.orders') }}" class="profile-tab active">Đơn hàng của tôi</a>
        </nav>

        @include('profile.partials.my-orders')
    </div>
</section>
@endsection
