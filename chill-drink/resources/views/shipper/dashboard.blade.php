@extends('layouts.shipper')

@section('title', 'Shipper Dashboard')

@section('content')

<div class="container-fluid">

    {{-- ================= THỐNG KÊ ================= --}}
    <div class="row g-4">

        {{-- Đơn hôm nay --}}
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body text-center">

                    <div class="mb-2">
                        <i class="fas fa-calendar-day fa-2x text-primary"></i>
                    </div>

                    <h1 class="fw-bold">
                        {{ $todayOrders ?? 0 }}
                    </h1>

                    <p class="text-muted mb-0">
                        Đơn hôm nay
                    </p>

                </div>
            </div>
        </div>

        {{-- Đang giao --}}
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body text-center">

                    <div class="mb-2">
                        <i class="fas fa-motorcycle fa-2x text-warning"></i>
                    </div>

                    <h1 class="fw-bold">
                        {{ $shippingOrders ?? 0 }}
                    </h1>

                    <p class="text-muted mb-0">
                        Đang giao
                    </p>

                </div>
            </div>
        </div>

        {{-- Hoàn thành --}}
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body text-center">

                    <div class="mb-2">
                        <i class="fas fa-check-circle fa-2x text-success"></i>
                    </div>

                    <h1 class="fw-bold">
                        {{ $completedOrders ?? 0 }}
                    </h1>

                    <p class="text-muted mb-0">
                        Hoàn thành
                    </p>

                </div>
            </div>
        </div>

        {{-- Thu nhập --}}
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body text-center">

                    <div class="mb-2">
                        <i class="fas fa-money-bill-wave fa-2x text-success"></i>
                    </div>

                    <h1 class="fw-bold">
                        {{ number_format($income ?? 0) }}đ
                    </h1>

                    <p class="text-muted mb-0">
                        Thu nhập
                    </p>

                </div>
            </div>
        </div>

    </div>


    {{-- ================= THÔNG TIN SHIPPER ================= --}}
    <div class="card mt-4 shadow-sm border-0">

        <div class="card-body">

            <div class="d-flex justify-content-between align-items-center">

                <div>

                    <h4 class="fw-bold mb-1">
                        Xin chào,
                        {{ $shipperUser->name ?? 'Shipper' }} 👋
                    </h4>

                    <p class="text-muted mb-0">
                        Mã shipper:

                        <strong>
                            {{ $shipperInfo->code ?? '---' }}
                        </strong>
                    </p>

                </div>

                <div>

                    @if(($shipperInfo->status ?? 'offline') === 'online')

                        <span class="badge bg-success px-3 py-2">
                            <i class="fas fa-circle me-1"></i>
                            Đang online
                        </span>

                    @elseif(($shipperInfo->status ?? 'offline') === 'busy')

                        <span class="badge bg-warning text-dark px-3 py-2">
                            <i class="fas fa-circle me-1"></i>
                            Đang bận
                        </span>

                    @else

                        <span class="badge bg-secondary px-3 py-2">
                            <i class="fas fa-circle me-1"></i>
                            Offline
                        </span>

                    @endif

                </div>

            </div>

        </div>

    </div>


    {{-- ================= ĐƠN HÀNG CẦN GIAO ================= --}}
    <div class="card mt-4 shadow-sm border-0">

        <div class="card-header bg-white py-3">

            <div class="d-flex justify-content-between align-items-center">

                <div>

                    <h5 class="mb-1 fw-bold">

                        <i class="fas fa-box me-2 text-primary"></i>

                        Đơn hàng cần giao

                    </h5>

                    <small class="text-muted">
                        Các đơn hàng đang chờ shipper nhận
                    </small>

                </div>

                <a href="{{ route('shipper.orders') }}"
                   class="btn btn-outline-primary btn-sm">

                    <i class="fas fa-list me-1"></i>

                    Xem tất cả

                </a>

            </div>

        </div>


        <div class="table-responsive">

            <table class="table table-hover align-middle mb-0">

                <thead class="table-light">

                    <tr>

                        <th>#</th>

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
                            <td>
                                {{ $orders->firstItem() + $loop->index }}
                            </td>


                            {{-- MÃ ĐƠN HÀNG --}}
                            <td>

                                <strong class="text-primary">

                                    {{ $order->displayCode() }}

                                </strong>

                            </td>


                            {{-- KHÁCH HÀNG --}}
                            <td>

                                <div class="fw-bold">

                                    <i class="fas fa-user me-1 text-primary"></i>

                                    {{ $order->customerName() ?: 'Khách hàng' }}

                                </div>

                            </td>


                            {{-- SỐ ĐIỆN THOẠI --}}
                            <td>

                                @php
                                    $phone = $order->customerPhone();
                                @endphp

                                @if(!empty($phone))

                                    <a href="tel:{{ $phone }}"
                                       class="text-decoration-none">

                                        <i class="fas fa-phone me-1 text-success"></i>

                                        {{ $phone }}

                                    </a>

                                @else

                                    <span class="text-muted">
                                        Chưa có SĐT
                                    </span>

                                @endif

                            </td>


                            {{-- ĐỊA CHỈ --}}
                            <td style="min-width: 250px;">

                                @php
                                    $shippingAddress = $order->getShippingAddress();
                                @endphp

                                @if(!empty($shippingAddress))

                                    <div>

                                        <i class="fas fa-location-dot me-1 text-danger"></i>

                                        {{ $shippingAddress }}

                                    </div>

                                @else

                                    <span class="text-muted">
                                        Chưa có địa chỉ
                                    </span>

                                @endif

                            </td>


                            {{-- TRẠNG THÁI --}}
                            <td>

                                @switch($order->status)

                                    @case('pending')

                                        <span class="badge bg-secondary">
                                            Chờ xử lý
                                        </span>

                                        @break


                                    @case('confirmed')

                                        <span class="badge bg-info">
                                            Đã xác nhận
                                        </span>

                                        @break


                                    @case('processing')

                                        <span class="badge bg-primary">
                                            Đang xử lý
                                        </span>

                                        @break


                                    @case('shipping')

                                        <span class="badge bg-warning text-dark">
                                            Đang giao
                                        </span>

                                        @break


                                    @case('completed')

                                        <span class="badge bg-success">
                                            Hoàn thành
                                        </span>

                                        @break


                                    @case('cancelled')

                                        <span class="badge bg-danger">
                                            Đã hủy
                                        </span>

                                        @break


                                    @default

                                        <span class="badge bg-secondary">
                                            {{ $order->status }}
                                        </span>

                                @endswitch

                            </td>


                            {{-- NGÀY ĐẶT --}}
                            <td>

                                @if($order->created_at)

                                    {{ $order->created_at->format('d/m/Y H:i') }}

                                @else

                                    ---

                                @endif

                            </td>


                            {{-- THAO TÁC --}}
                            <td>

                                {{-- CHI TIẾT --}}
                                <a href="{{ route('shipper.orders.show', $order->id) }}"
                                   class="btn btn-primary btn-sm mb-1">

                                    <i class="fas fa-eye me-1"></i>

                                    Chi tiết

                                </a>


                                {{-- NHẬN ĐƠN --}}
                                @if(empty($order->shipper_id))

                                    <form action="{{ route('shipper.orders.accept', $order->id) }}"
                                          method="POST"
                                          class="d-inline">

                                        @csrf

                                        <button type="submit"
                                                class="btn btn-success btn-sm mb-1"
                                                onclick="return confirm('Bạn có muốn nhận đơn hàng này không?')">

                                            <i class="fas fa-check me-1"></i>

                                            Nhận đơn

                                        </button>

                                    </form>


                                {{-- ĐƠN CỦA SHIPPER --}}
                                @elseif($order->shipper_id == ($shipperInfo->id ?? 0))

                                    {{-- BẮT ĐẦU GIAO --}}
                                    @if($order->status === 'processing')

                                        <form action="{{ route('shipper.orders.start', $order->id) }}"
                                              method="POST"
                                              class="d-inline">

                                            @csrf

                                            <button type="submit"
                                                    class="btn btn-warning btn-sm mb-1">

                                                <i class="fas fa-motorcycle me-1"></i>

                                                Bắt đầu giao

                                            </button>

                                        </form>


                                    {{-- HOÀN THÀNH --}}
                                    @elseif($order->status === 'shipping')

                                        <form action="{{ route('shipper.orders.complete', $order->id) }}"
                                              method="POST"
                                              class="d-inline">

                                            @csrf

                                            <button type="submit"
                                                    class="btn btn-success btn-sm mb-1"
                                                    onclick="return confirm('Xác nhận đã giao hàng thành công?')">

                                                <i class="fas fa-check-circle me-1"></i>

                                                Hoàn thành

                                            </button>

                                        </form>

                                    @endif

                                @endif

                            </td>

                        </tr>


                    @empty

                        <tr>

                            <td colspan="8"
                                class="text-center py-5">

                                <div class="text-muted">

                                    <i class="fas fa-box-open fa-3x mb-3"></i>

                                    <h5>
                                        Chưa có đơn hàng cần giao
                                    </h5>

                                    <p class="mb-0">
                                        Hiện tại không có đơn hàng nào đang chờ shipper nhận.
                                    </p>

                                </div>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- PHÂN TRANG --}}
        @if($orders->hasPages())

            <div class="card-footer bg-white">

                {{ $orders->links() }}

            </div>

        @endif

    </div>

</div>

@endsection