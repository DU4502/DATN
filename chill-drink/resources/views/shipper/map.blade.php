@extends('layouts.shipper')

@section('title', 'Bản đồ giao hàng')

@section('content')

<div class="container-fluid py-4">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h3 class="fw-bold mb-1">
                <i class="fa-solid fa-map-location-dot text-danger me-2"></i>
                Bản đồ giao hàng
            </h3>

            <p class="text-muted mb-0">
                Đang giao đơn hàng #{{ $order->id }}
            </p>
        </div>

        <span class="badge bg-warning text-dark px-3 py-2">
            <i class="fa-solid fa-truck-fast me-1"></i>
            Đang giao hàng
        </span>

    </div>


    <div class="row g-4">

        {{-- THÔNG TIN KHÁCH HÀNG --}}
        <div class="col-lg-4">

            <div class="card border-0 shadow-sm rounded-4">

                <div class="card-body p-4">

                    <h5 class="fw-bold mb-4">
                        <i class="fa-solid fa-user text-primary me-2"></i>
                        Thông tin khách hàng
                    </h5>


                    {{-- Tên --}}
                    <div class="customer-info">

                        <div class="icon">
                            <i class="fa-solid fa-user"></i>
                        </div>

                        <div>
                            <small class="text-muted">
                                Họ và tên
                            </small>

                            <div class="fw-bold">
                                {{ $order->customer->name ?? 'Chưa có' }}
                            </div>
                        </div>

                    </div>


                    {{-- Số điện thoại --}}
                    <div class="customer-info">

                        <div class="icon">
                            <i class="fa-solid fa-phone"></i>
                        </div>

                        <div>
                            <small class="text-muted">
                                Số điện thoại
                            </small>

                            <div class="fw-bold">
                                {{ $order->customer->phone ?? 'Chưa có' }}
                            </div>
                        </div>

                    </div>


                    {{-- Địa chỉ --}}
                    <div class="customer-info">

                        <div class="icon">
                            <i class="fa-solid fa-location-dot"></i>
                        </div>

                        <div>
                            <small class="text-muted">
                                Địa chỉ giao hàng
                            </small>

                            <div class="fw-bold">
                                {{ $order->shipping_address ?? $order->address ?? 'Chưa có' }}
                            </div>
                        </div>

                    </div>


                    {{-- Nút gọi --}}
                    @if(!empty($order->customer->phone))

                        <a href="tel:{{ $order->customer->phone }}"
                           class="btn btn-success w-100 mt-3">

                            <i class="fa-solid fa-phone me-2"></i>
                            Gọi cho khách hàng

                        </a>

                    @endif

                </div>

            </div>


            {{-- THÔNG TIN ĐƠN --}}
            <div class="card border-0 shadow-sm rounded-4 mt-4">

                <div class="card-body p-4">

                    <h5 class="fw-bold mb-3">
                        <i class="fa-solid fa-box text-warning me-2"></i>
                        Thông tin đơn hàng
                    </h5>

                    <div class="d-flex justify-content-between mb-2">

                        <span class="text-muted">
                            Mã đơn
                        </span>

                        <strong>
                            #{{ $order->id }}
                        </strong>

                    </div>


                    <div class="d-flex justify-content-between mb-2">

                        <span class="text-muted">
                            Trạng thái
                        </span>

                        <span class="badge bg-warning text-dark">
                            Đang giao
                        </span>

                    </div>


                    <div class="d-flex justify-content-between">

                        <span class="text-muted">
                            Phí giao hàng
                        </span>

                        <strong class="text-success">
                            {{ number_format($order->shipping_fee ?? 0) }} đ
                        </strong>

                    </div>

                </div>

            </div>

        </div>


        {{-- BẢN ĐỒ --}}
        <div class="col-lg-8">

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">

                <div class="card-header bg-white border-0 p-4">

                    <h5 class="fw-bold mb-1">

                        <i class="fa-solid fa-location-crosshairs text-danger me-2"></i>

                        Vị trí giao hàng

                    </h5>

                    <small class="text-muted">

                        Địa chỉ khách hàng được hiển thị trên bản đồ

                    </small>

                </div>


                <div class="card-body p-0">

                    <div id="map"
                         style="
                            width:100%;
                            height:550px;
                            border-radius:0 0 16px 16px;
                         ">
                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


<style>

.customer-info {
    display:flex;
    align-items:flex-start;
    gap:15px;
    padding:15px 0;
    border-bottom:1px solid #eee;
}

.customer-info:last-child {
    border-bottom:none;
}

.customer-info .icon {
    width:42px;
    height:42px;
    min-width:42px;

    border-radius:12px;

    background:#f1f5ff;

    color:#0d6efd;

    display:flex;
    align-items:center;
    justify-content:center;
}

</style>

@endsection