@extends('layouts.shipper')

@section('title', 'Shipper Dashboard')

@section('content')

<div class="container-fluid py-4">

    {{-- ================= HEADER ================= --}}
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h2 class="fw-bold mb-1">
                <i class="fa-solid fa-gauge-high text-primary me-2"></i>
                Dashboard Shipper
            </h2>

            <p class="text-muted mb-0">
                Quản lý đơn hàng và giao hàng của bạn
            </p>
        </div>

        <div class="text-end">
            <small class="text-muted d-block">
                Hôm nay
            </small>

            <strong>
                {{ now()->format('d/m/Y') }}
            </strong>
        </div>

    </div>


    {{-- ================= THỐNG KÊ ================= --}}
    <div class="row g-4">

        {{-- Đơn hôm nay --}}
        <div class="col-xl-3 col-md-6">

            <div class="card border-0 shadow-sm h-100 statistic-card">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>

                            <p class="text-muted mb-2">
                                Đơn hôm nay
                            </p>

                            <h2 class="fw-bold mb-0">
                                {{ $todayOrders ?? 0 }}
                            </h2>

                        </div>

                        <div class="icon-box bg-primary bg-opacity-10 text-primary">
                            <i class="fa-solid fa-calendar-day"></i>
                        </div>

                    </div>

                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="fa-solid fa-chart-line me-1"></i>
                            Tổng đơn trong ngày
                        </small>
                    </div>

                </div>

            </div>

        </div>


        {{-- Đang giao --}}
        <div class="col-xl-3 col-md-6">

            <div class="card border-0 shadow-sm h-100 statistic-card">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>

                            <p class="text-muted mb-2">
                                Đang giao
                            </p>

                            <h2 class="fw-bold mb-0">
                                {{ $shippingOrders ?? 0 }}
                            </h2>

                        </div>

                        <div class="icon-box bg-warning bg-opacity-10 text-warning">
                            <i class="fa-solid fa-motorcycle"></i>
                        </div>

                    </div>

                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="fa-solid fa-truck-fast me-1"></i>
                            Đơn đang vận chuyển
                        </small>
                    </div>

                </div>

            </div>

        </div>


        {{-- Hoàn thành --}}
        <div class="col-xl-3 col-md-6">

            <div class="card border-0 shadow-sm h-100 statistic-card">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>

                            <p class="text-muted mb-2">
                                Hoàn thành
                            </p>

                            <h2 class="fw-bold mb-0">
                                {{ $completedOrders ?? 0 }}
                            </h2>

                        </div>

                        <div class="icon-box bg-success bg-opacity-10 text-success">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>

                    </div>

                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="fa-solid fa-check me-1"></i>
                            Đơn giao thành công
                        </small>
                    </div>

                </div>

            </div>

        </div>


        {{-- Thu nhập --}}
        <div class="col-xl-3 col-md-6">

            <div class="card border-0 shadow-sm h-100 statistic-card">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>

                            <p class="text-muted mb-2">
                                Thu nhập
                            </p>

                            <h2 class="fw-bold mb-0 text-success">
                                {{ number_format($income ?? 0) }}đ
                            </h2>

                        </div>

                        <div class="icon-box bg-success bg-opacity-10 text-success">
                            <i class="fa-solid fa-money-bill-wave"></i>
                        </div>

                    </div>

                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="fa-solid fa-wallet me-1"></i>
                            Thu nhập hiện tại
                        </small>
                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- ================= THÔNG TIN SHIPPER ================= --}}
    <div class="card border-0 shadow-sm mt-4 shipper-info-card">

        <div class="card-body p-4">

            <div class="row align-items-center">

                <div class="col-md-8">

                    <div class="d-flex align-items-center">

                        <div class="avatar-box me-3">
                            <i class="fa-solid fa-user"></i>
                        </div>

                        <div>

                            <h4 class="fw-bold mb-1">

                                Xin chào,
                                {{ $shipperUser->name ?? 'Shipper' }}
                                👋

                            </h4>

                            <p class="text-muted mb-0">

                                <i class="fa-solid fa-id-card me-1"></i>

                                Mã shipper:

                                <strong class="text-primary">
                                    {{ $shipperInfo->code ?? '---' }}
                                </strong>

                            </p>

                        </div>

                    </div>

                </div>


                <div class="col-md-4 text-md-end mt-3 mt-md-0">

                    @if(($shipperInfo->status ?? 'offline') === 'online')

                        <span class="status-badge status-online">

                            <span class="status-dot"></span>

                            Đang online

                        </span>

                    @elseif(($shipperInfo->status ?? 'offline') === 'busy')

                        <span class="status-badge status-busy">

                            <span class="status-dot"></span>

                            Đang bận

                        </span>

                    @else

                        <span class="status-badge status-offline">

                            <span class="status-dot"></span>

                            Offline

                        </span>

                    @endif

                </div>

            </div>

        </div>

    </div>


    {{-- ================= ĐƠN HÀNG CẦN GIAO ================= --}}
    <div class="card border-0 shadow-sm mt-4">

        {{-- Header --}}
        <div class="card-header bg-white border-0 p-4">

            <div class="d-flex justify-content-between align-items-center">

                <div>

                    <h5 class="fw-bold mb-1">

                        <i class="fa-solid fa-box text-primary me-2"></i>

                        Đơn hàng cần giao

                    </h5>

                    <small class="text-muted">

                        Các đơn hàng đang chờ shipper nhận

                    </small>

                </div>


                <a href="{{ route('shipper.orders') }}"
                   class="btn btn-outline-primary">

                    <i class="fa-solid fa-list me-1"></i>

                    Xem tất cả

                </a>

            </div>

        </div>


        {{-- Table --}}
        <div class="table-responsive">

            <table class="table table-hover align-middle mb-0">

                <thead class="table-light">

                    <tr>

                        <th class="px-4">#</th>

                        <th>Mã đơn</th>

                        <th>Khách hàng</th>

                        <th>SĐT</th>

                        <th>Địa chỉ giao hàng</th>

                        <th>Trạng thái</th>

                        <th>Ngày đặt</th>

                        <th>Thao tác</th>

                    </tr>

                </thead>


                <tbody>

                    @forelse($orders as $order)

                        <tr>

                            {{-- STT --}}
                            <td class="px-4">

                                <span class="text-muted">
                                    {{ $orders->firstItem() + $loop->index }}
                                </span>

                            </td>


                            {{-- Mã đơn --}}
                            <td>

                                <span class="order-code">

                                    {{ $order->displayCode() }}

                                </span>

                            </td>


                            {{-- Khách hàng --}}
                            <td>

                                <div class="fw-semibold">

                                    <i class="fa-solid fa-user text-primary me-1"></i>

                                    {{ $order->customerName() ?: 'Khách hàng' }}

                                </div>

                            </td>


                            {{-- SĐT --}}
                            <td>

                                @php
                                    $phone = $order->customerPhone();
                                @endphp

                                @if(!empty($phone))

                                    <a href="tel:{{ $phone }}"
                                       class="phone-link">

                                        <i class="fa-solid fa-phone me-1"></i>

                                        {{ $phone }}

                                    </a>

                                @else

                                    <span class="text-muted">
                                        Chưa có SĐT
                                    </span>

                                @endif

                            </td>


                            {{-- Địa chỉ --}}
                            <td style="min-width:280px;">

                                @php
                                    $shippingAddress = $order->getShippingAddress();
                                @endphp

                                @if(!empty($shippingAddress))

                                    <div>

                                        <i class="fa-solid fa-location-dot text-danger me-1"></i>

                                        {{ $shippingAddress }}

                                    </div>

                                @else

                                    <span class="text-muted">
                                        Chưa có địa chỉ
                                    </span>

                                @endif

                            </td>


                            {{-- Trạng thái --}}
                            <td>

                                @switch($order->status)

                                    @case('pending')

                                        <span class="badge bg-secondary px-3 py-2">
                                            Chờ xử lý
                                        </span>

                                        @break


                                    @case('confirmed')

                                        <span class="badge bg-info px-3 py-2">
                                            Đã xác nhận
                                        </span>

                                        @break


                                    @case('processing')

                                        <span class="badge bg-primary px-3 py-2">
                                            Đang xử lý
                                        </span>

                                        @break


                                    @case('shipping')

                                        <span class="badge bg-warning text-dark px-3 py-2">
                                            Đang giao
                                        </span>

                                        @break


                                    @case('completed')

                                        <span class="badge bg-success px-3 py-2">
                                            Hoàn thành
                                        </span>

                                        @break


                                    @case('cancelled')

                                        <span class="badge bg-danger px-3 py-2">
                                            Đã hủy
                                        </span>

                                        @break


                                    @default

                                        <span class="badge bg-secondary px-3 py-2">
                                            {{ $order->status }}
                                        </span>

                                @endswitch

                            </td>


                            {{-- Ngày đặt --}}
                            <td>

                                @if($order->created_at)

                                    <div class="small">

                                        <i class="fa-regular fa-calendar me-1 text-muted"></i>

                                        {{ $order->created_at->format('d/m/Y') }}

                                    </div>

                                    <div class="small text-muted">

                                        {{ $order->created_at->format('H:i') }}

                                    </div>

                                @else

                                    ---

                                @endif

                            </td>


                            {{-- Thao tác --}}
                            <td>

                                <div class="d-flex flex-wrap gap-1">

                                    {{-- Chi tiết --}}
                                    <a href="{{ route('shipper.orders.show', $order->id) }}"
                                       class="btn btn-primary btn-sm">

                                        <i class="fa-solid fa-eye"></i>

                                    </a>


                                    {{-- Nhận đơn --}}
                                    @if(empty($order->shipper_id))

                                        <form action="{{ route('shipper.orders.accept', $order->id) }}"
                                              method="POST"
                                              class="d-inline">

                                            @csrf

                                            <button type="submit"
                                                    class="btn btn-success btn-sm"
                                                    title="Nhận đơn"
                                                    onclick="return confirm('Bạn có muốn nhận đơn hàng này không?')">

                                                <i class="fa-solid fa-check"></i>

                                            </button>

                                        </form>


                                    {{-- Đơn của shipper --}}
                                    @elseif($order->shipper_id == ($shipperInfo->id ?? 0))

                                        {{-- Bắt đầu giao --}}
                                        @if($order->status === 'processing')

                                            <form action="{{ route('shipper.orders.start', $order->id) }}"
                                                  method="POST">

                                                @csrf

                                                <button type="submit"
                                                        class="btn btn-warning btn-sm"
                                                        title="Bắt đầu giao">

                                                    <i class="fa-solid fa-motorcycle"></i>

                                                </button>

                                            </form>


                                        {{-- Hoàn thành --}}
                                        @elseif($order->status === 'shipping')

                                            <form action="{{ route('shipper.orders.complete', $order->id) }}"
                                                  method="POST">

                                                @csrf

                                                <button type="submit"
                                                        class="btn btn-success btn-sm"
                                                        title="Hoàn thành"
                                                        onclick="return confirm('Xác nhận đã giao hàng thành công?')">

                                                    <i class="fa-solid fa-check-circle"></i>

                                                </button>

                                            </form>

                                        @endif

                                    @endif

                                </div>

                            </td>

                        </tr>


                    @empty

                        <tr>

                            <td colspan="8"
                                class="text-center py-5">

                                <div class="empty-order">

                                    <div class="empty-icon mb-3">

                                        <i class="fa-solid fa-box-open"></i>

                                    </div>

                                    <h5 class="fw-bold">
                                        Chưa có đơn hàng cần giao
                                    </h5>

                                    <p class="text-muted mb-0">

                                        Hiện tại không có đơn hàng nào đang chờ shipper nhận.

                                    </p>

                                </div>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- Phân trang --}}
        @if($orders->hasPages())

            <div class="card-footer bg-white border-0 p-4">

                {{ $orders->links() }}

            </div>

        @endif

    </div>

</div>


{{-- ================= CSS RESPONSIVE ================= --}}
<style>

    /* ================= CARD THỐNG KÊ ================= */

    .statistic-card {
        border-radius: 15px;
        transition: all 0.25s ease;
        overflow: hidden;
    }

    .statistic-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.08) !important;
    }

    .icon-box {
        width: 55px;
        height: 55px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 23px;
        flex-shrink: 0;
    }


    /* ================= THÔNG TIN SHIPPER ================= */

    .shipper-info-card {
        border-radius: 15px;
        overflow: hidden;
    }

    .avatar-box {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: #e9f2ff;
        color: #0d6efd;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 25px;
        flex-shrink: 0;
    }


    /* ================= TRẠNG THÁI ================= */

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 9px 16px;
        border-radius: 30px;
        font-weight: 600;
        white-space: nowrap;
    }

    .status-online {
        background: #d1e7dd;
        color: #198754;
    }

    .status-busy {
        background: #fff3cd;
        color: #856404;
    }

    .status-offline {
        background: #e9ecef;
        color: #6c757d;
    }

    .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: currentColor;
    }


    /* ================= ĐƠN HÀNG ================= */

    .order-code {
        color: #0d6efd;
        font-weight: 700;
        white-space: nowrap;
    }

    .phone-link {
        text-decoration: none;
        color: #198754;
        font-weight: 500;
        white-space: nowrap;
    }

    .phone-link:hover {
        text-decoration: underline;
    }

    .table thead th {
        white-space: nowrap;
        font-size: 14px;
    }

    .table tbody td {
        padding-top: 14px;
        padding-bottom: 14px;
    }


    /* ================= ĐƠN TRỐNG ================= */

    .empty-icon {
        width: 75px;
        height: 75px;
        margin: auto;
        border-radius: 50%;
        background: #f1f3f5;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        color: #adb5bd;
    }

    .card {
        border-radius: 15px;
    }


    /* =====================================================
       MOBILE
       ===================================================== */

    @media (max-width: 768px) {

        /* Container */

        .container-fluid {
            padding: 15px !important;
        }


        /* ================= HEADER ================= */

        .container-fluid > .d-flex {
            display: block !important;
        }

        .container-fluid > .d-flex h2 {
            font-size: 22px;
        }

        .container-fluid > .d-flex p {
            font-size: 13px;
        }

        .container-fluid > .d-flex .text-end {
            text-align: left !important;
            margin-top: 10px;
        }


        /* ================= THỐNG KÊ ================= */

        .row.g-4 {
            --bs-gutter-y: 15px;
        }

        .statistic-card {
            border-radius: 12px;
        }

        .statistic-card .card-body {
            padding: 18px;
        }

        .statistic-card h2 {
            font-size: 28px;
        }

        .statistic-card p {
            font-size: 14px;
        }

        .icon-box {
            width: 48px;
            height: 48px;
            font-size: 20px;
            border-radius: 12px;
        }


        /* ================= SHIPPER INFO ================= */

        .shipper-info-card .card-body {
            padding: 20px !important;
        }

        .shipper-info-card .row {
            display: block;
        }

        .shipper-info-card .col-md-8 {
            width: 100%;
        }

        .shipper-info-card .col-md-4 {
            width: 100%;
            margin-top: 18px !important;
        }

        .shipper-info-card .text-md-end {
            text-align: left !important;
        }

        .avatar-box {
            width: 50px;
            height: 50px;
            font-size: 21px;
        }

        .shipper-info-card h4 {
            font-size: 18px;
        }

        .shipper-info-card p {
            font-size: 13px;
        }


        /* ================= HEADER ĐƠN HÀNG ================= */

        .card-header {
            padding: 18px !important;
        }

        .card-header .d-flex {
            display: block !important;
        }

        .card-header h5 {
            font-size: 17px;
        }

        .card-header small {
            font-size: 12px;
        }

        .card-header .btn {
            margin-top: 12px;
            width: 100%;
        }


        /* ================= TABLE ================= */

        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .table {
            min-width: 1000px;
            font-size: 13px;
        }

        .table thead th {
            font-size: 12px;
            padding: 10px;
        }

        .table tbody td {
            padding: 12px 10px;
        }


        /* ================= NÚT THAO TÁC ================= */

        .table .btn {
            min-width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }


        /* ================= PHÂN TRANG ================= */

        .card-footer {
            padding: 15px !important;
            overflow-x: auto;
        }

        .pagination {
            flex-wrap: nowrap;
            white-space: nowrap;
        }


        /* ================= EMPTY ================= */

        .empty-icon {
            width: 65px;
            height: 65px;
            font-size: 27px;
        }

        .empty-order h5 {
            font-size: 16px;
        }

        .empty-order p {
            font-size: 13px;
            padding: 0 20px;
        }

    }


    /* =====================================================
       ĐIỆN THOẠI NHỎ
       ===================================================== */

    @media (max-width: 480px) {

        .container-fluid {
            padding: 10px !important;
        }

        /* Header */

        .container-fluid > .d-flex h2 {
            font-size: 20px;
        }

        .container-fluid > .d-flex h2 i {
            font-size: 18px;
        }


        /* Thống kê */

        .statistic-card .card-body {
            padding: 15px;
        }

        .statistic-card h2 {
            font-size: 25px;
        }

        .statistic-card p {
            font-size: 13px;
        }

        .icon-box {
            width: 44px;
            height: 44px;
            font-size: 18px;
        }


        /* Shipper */

        .shipper-info-card h4 {
            font-size: 16px;
        }

        .shipper-info-card p {
            font-size: 12px;
        }

        .avatar-box {
            width: 45px;
            height: 45px;
            font-size: 18px;
        }

        .status-badge {
            font-size: 12px;
            padding: 7px 12px;
        }


        /* Đơn hàng */

        .card-header h5 {
            font-size: 16px;
        }

        .card-header small {
            font-size: 11px;
        }


        /* Table */

        .table {
            min-width: 950px;
            font-size: 12px;
        }


        /* Empty */

        .empty-icon {
            width: 60px;
            height: 60px;
            font-size: 25px;
        }

    }

</style>

@endsection