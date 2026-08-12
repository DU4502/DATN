@extends('layouts.shipper')

@section('title', 'Đơn hàng')

@section('content')

<div class="container-fluid">

    {{-- Tiêu đề --}}
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h3 class="fw-bold mb-1">
                <i class="fas fa-box me-2"></i>
                Đơn hàng của tôi
            </h3>

            <p class="text-muted mb-0">
                Quản lý các đơn hàng được giao cho bạn
            </p>
        </div>

        <a href="{{ route('shipper.dashboard') }}"
           class="btn btn-outline-primary">

            <i class="fas fa-home me-1"></i>
            Dashboard

        </a>

    </div>


    {{-- Danh sách đơn --}}
    <div class="card shadow-sm border-0">

        <div class="card-header bg-white py-3">

            <h5 class="mb-0 fw-bold">
                Danh sách đơn hàng
            </h5>

        </div>


        <div class="table-responsive">

            <table class="table table-hover align-middle mb-0">

                <thead class="table-light">

                    <tr>

                        <th>#</th>

                        <th>Mã đơn</th>

                        <th>Khách hàng</th>

                        <th>SĐT</th>

                        <th>Địa chỉ</th>

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


                            {{-- Mã đơn --}}
                            <td>

                                <strong>
                                    #{{ $order->id }}
                                </strong>

                            </td>


                            {{-- THÔNG TIN KHÁCH HÀNG LẤY TRỰC TIẾP TỪ ORDERS --}}
                            <td>

                                <div class="fw-bold">

                                    <i class="fas fa-user me-1 text-primary"></i>

                                    {{ $order->customer_name ?? 'Khách hàng' }}

                                </div>

                            </td>


                            {{-- SỐ ĐIỆN THOẠI LẤY TỪ ORDERS --}}
                            <td>

                                @if(!empty($order->phone))

                                    <a href="tel:{{ $order->phone }}"
                                       class="text-decoration-none">

                                        <i class="fas fa-phone me-1 text-success"></i>

                                        {{ $order->phone }}

                                    </a>

                                @else

                                    <span class="text-muted">
                                        Chưa có SĐT
                                    </span>

                                @endif

                            </td>


                            {{-- ĐỊA CHỈ LẤY TỪ ORDERS --}}
                            <td style="min-width: 220px;">

                                @if(!empty($order->address))

                                    <i class="fas fa-location-dot me-1 text-danger"></i>

                                    {{ $order->address }}

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

                                {{ $order->created_at?->format('d/m/Y H:i') }}

                            </td>


                            {{-- THAO TÁC --}}
                            <td>

                                <a href="{{ route('shipper.orders.show', $order->id) }}"
                                   class="btn btn-primary btn-sm">

                                    <i class="fas fa-eye me-1"></i>

                                    Chi tiết

                                </a>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="8"
                                class="text-center py-5">

                                <div class="text-muted">

                                    <i class="fas fa-box-open fa-3x mb-3"></i>

                                    <h5>
                                        Chưa có đơn hàng
                                    </h5>

                                    <p class="mb-0">
                                        Hiện tại bạn chưa có đơn hàng nào.
                                    </p>

                                </div>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- Pagination --}}
        @if($orders->hasPages())

            <div class="card-footer bg-white">

                {{ $orders->links() }}

            </div>

        @endif

    </div>

</div>

@endsection