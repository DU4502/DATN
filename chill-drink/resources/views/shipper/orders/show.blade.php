@extends('layouts.shipper')

@section('title', 'Chi tiết đơn hàng')

@section('content')

<div class="container-fluid">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h3 class="fw-bold mb-1">
                <i class="fas fa-file-invoice me-2 text-primary"></i>
                Chi tiết đơn hàng
            </h3>

            <p class="text-muted mb-0">
                Thông tin chi tiết đơn hàng cần giao
            </p>
        </div>

        <a href="{{ route('shipper.orders') }}"
           class="btn btn-outline-secondary">

            <i class="fas fa-arrow-left me-1"></i>
            Quay lại

        </a>

    </div>


    {{-- KIỂM TRA ORDER --}}
    @if(!isset($order))

        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Không tìm thấy thông tin đơn hàng.
        </div>

    @else

        <div class="row g-4">

            {{-- ================= THÔNG TIN ĐƠN ================= --}}
            <div class="col-lg-8">

                <div class="card shadow-sm border-0">

                    <div class="card-header bg-white py-3">

                        <h5 class="fw-bold mb-0">
                            <i class="fas fa-box me-2 text-primary"></i>
                            Thông tin đơn hàng
                        </h5>

                    </div>

                    <div class="card-body">

                        <div class="row g-3">

                            {{-- MÃ ĐƠN --}}
                            <div class="col-md-6">

                                <label class="text-muted">
                                    Mã đơn hàng
                                </label>

                                <div class="fw-bold text-primary fs-5">
                                    #{{ $order->order_code ?? $order->id }}
                                </div>

                            </div>


                            {{-- TRẠNG THÁI --}}
                            <div class="col-md-6">

                                <label class="text-muted">
                                    Trạng thái
                                </label>

                                <div>

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

                                </div>

                            </div>


                            {{-- NGÀY ĐẶT --}}
                            <div class="col-md-6">

                                <label class="text-muted">
                                    Ngày đặt hàng
                                </label>

                                <div class="fw-semibold">

                                    @if($order->created_at)

                                        {{ $order->created_at->format('d/m/Y H:i') }}

                                    @else

                                        ---

                                    @endif

                                </div>

                            </div>


                            {{-- PHÍ GIAO HÀNG --}}
                            <div class="col-md-6">

                                <label class="text-muted">
                                    Phí giao hàng
                                </label>

                                <div class="fw-bold text-success">

                                    {{ number_format($order->shipping_fee ?? 0) }}đ

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- ================= KHÁCH HÀNG ================= --}}
                <div class="card shadow-sm border-0 mt-4">

                    <div class="card-header bg-white py-3">

                        <h5 class="fw-bold mb-0">

                            <i class="fas fa-user me-2 text-primary"></i>

                            Thông tin khách hàng

                        </h5>

                    </div>


                    <div class="card-body">

                        <div class="row g-3">

                            {{-- TÊN --}}
                            <div class="col-md-6">

                                <label class="text-muted">
                                    Họ tên
                                </label>

                                <div class="fw-bold">

                                    {{ $order->customer_name ?? 'Khách hàng' }}

                                </div>

                            </div>


                            {{-- SỐ ĐIỆN THOẠI --}}
                            <div class="col-md-6">

                                <label class="text-muted">
                                    Số điện thoại
                                </label>

                                <div>

                                    @if(!empty($order->phone))

                                        <a href="tel:{{ $order->phone }}"
                                           class="text-decoration-none">

                                            <i class="fas fa-phone text-success me-1"></i>

                                            {{ $order->phone }}

                                        </a>

                                    @else

                                        <span class="text-muted">
                                            Chưa có số điện thoại
                                        </span>

                                    @endif

                                </div>

                            </div>


                            {{-- ĐỊA CHỈ --}}
                            <div class="col-12">

                                <label class="text-muted">
                                    Địa chỉ giao hàng
                                </label>

                                <div class="mt-1">

                                    @if(!empty($order->address))

                                        <div>

                                            <i class="fas fa-location-dot text-danger me-2"></i>

                                            {{ $order->address }}

                                        </div>

                                    @else

                                        <span class="text-muted">
                                            Chưa có địa chỉ giao hàng
                                        </span>

                                    @endif

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- ================= SẢN PHẨM ================= --}}
                @if($order->relationLoaded('orderItems') && $order->orderItems->count())

                    <div class="card shadow-sm border-0 mt-4">

                        <div class="card-header bg-white py-3">

                            <h5 class="fw-bold mb-0">

                                <i class="fas fa-shopping-bag me-2 text-primary"></i>

                                Sản phẩm trong đơn

                            </h5>

                        </div>


                        <div class="table-responsive">

                            <table class="table table-hover align-middle mb-0">

                                <thead class="table-light">

                                    <tr>

                                        <th>#</th>

                                        <th>Sản phẩm</th>

                                        <th class="text-center">
                                            Số lượng
                                        </th>

                                        <th class="text-end">
                                            Đơn giá
                                        </th>

                                        <th class="text-end">
                                            Thành tiền
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>

                                    @foreach($order->orderItems as $item)

                                        <tr>

                                            <td>
                                                {{ $loop->iteration }}
                                            </td>

                                            <td>

                                                {{ $item->product->name
                                                    ?? $item->product_name
                                                    ?? 'Sản phẩm' }}

                                            </td>

                                            <td class="text-center">

                                                {{ $item->quantity }}

                                            </td>

                                            <td class="text-end">

                                                {{ number_format($item->price ?? 0) }}đ

                                            </td>

                                            <td class="text-end fw-bold">

                                                {{ number_format(
                                                    ($item->price ?? 0) *
                                                    ($item->quantity ?? 0)
                                                ) }}đ

                                            </td>

                                        </tr>

                                    @endforeach

                                </tbody>

                            </table>

                        </div>

                    </div>

                @endif

            </div>


            {{-- ================= THAO TÁC SHIPPER ================= --}}
            <div class="col-lg-4">

                <div class="card shadow-sm border-0">

                    <div class="card-header bg-white py-3">

                        <h5 class="fw-bold mb-0">

                            <i class="fas fa-motorcycle me-2 text-primary"></i>

                            Thao tác giao hàng

                        </h5>

                    </div>


                    <div class="card-body">

                        {{-- ĐƠN CHƯA CÓ SHIPPER --}}
                        @if(empty($order->shipper_id))

                            <form action="{{ route('shipper.orders.accept', $order->id) }}"
                                  method="POST">

                                @csrf

                                <button type="submit"
                                        class="btn btn-success w-100"
                                        onclick="return confirm('Bạn có muốn nhận đơn hàng này không?')">

                                    <i class="fas fa-check me-2"></i>

                                    Nhận đơn hàng

                                </button>

                            </form>


                        {{-- ĐƠN THUỘC SHIPPER HIỆN TẠI --}}
                        @elseif(
                            isset($shipperInfo) &&
                            $order->shipper_id == $shipperInfo->id
                        )

                            {{-- PROCESSING --}}
                            @if($order->status === 'processing')

                                <form action="{{ route('shipper.orders.start', $order->id) }}"
                                      method="POST">

                                    @csrf

                                    <button type="submit"
                                            class="btn btn-warning w-100">

                                        <i class="fas fa-motorcycle me-2"></i>

                                        Bắt đầu giao hàng

                                    </button>

                                </form>


                            {{-- SHIPPING --}}
                            @elseif($order->status === 'shipping')

                                <a href="{{ route('shipper.map', $order->id) }}"
                                   class="btn btn-primary w-100 mb-2">

                                    <i class="fas fa-map-marked-alt me-2"></i>

                                    Xem bản đồ giao hàng

                                </a>


                                <form action="{{ route('shipper.orders.complete', $order->id) }}"
                                      method="POST">

                                    @csrf

                                    <button type="submit"
                                            class="btn btn-success w-100"
                                            onclick="return confirm('Xác nhận đã giao hàng thành công?')">

                                        <i class="fas fa-check-circle me-2"></i>

                                        Hoàn thành đơn hàng

                                    </button>

                                </form>


                            {{-- COMPLETED --}}
                            @elseif($order->status === 'completed')

                                <div class="alert alert-success mb-0">

                                    <i class="fas fa-check-circle me-2"></i>

                                    Đơn hàng đã hoàn thành.

                                </div>


                            {{-- CANCELLED --}}
                            @elseif($order->status === 'cancelled')

                                <div class="alert alert-danger mb-0">

                                    <i class="fas fa-times-circle me-2"></i>

                                    Đơn hàng đã bị hủy.

                                </div>

                            @endif

                        @else

                            <div class="alert alert-info mb-0">

                                <i class="fas fa-info-circle me-2"></i>

                                Đơn hàng đang thuộc shipper khác.

                            </div>

                        @endif

                    </div>

                </div>


                {{-- ================= THÔNG TIN SHIPPER ================= --}}
                <div class="card shadow-sm border-0 mt-4">

                    <div class="card-header bg-white py-3">

                        <h5 class="fw-bold mb-0">

                            <i class="fas fa-id-card me-2 text-primary"></i>

                            Shipper

                        </h5>

                    </div>


                    <div class="card-body">

                        <p class="mb-2">

                            <strong>Mã shipper:</strong>

                            {{ $shipperInfo->code ?? '---' }}

                        </p>


                        <p class="mb-0">

                            <strong>Trạng thái:</strong>

                            @if(($shipperInfo->status ?? '') === 'online')

                                <span class="badge bg-success">
                                    Online
                                </span>

                            @elseif(($shipperInfo->status ?? '') === 'busy')

                                <span class="badge bg-warning text-dark">
                                    Đang bận
                                </span>

                            @else

                                <span class="badge bg-secondary">
                                    Offline
                                </span>

                            @endif

                        </p>

                    </div>

                </div>

            </div>

        </div>

    @endif

</div>

@endsection