@extends('layouts.shipper')

@section('title', 'Lịch sử giao hàng')

@section('content')

<div class="container-fluid">

    <div class="card shadow-sm border-0">

        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold">
                <i class="fas fa-history me-2"></i>
                Lịch sử giao hàng
            </h5>
        </div>

        <div class="table-responsive">

            <table class="table table-hover align-middle mb-0">

                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Mã đơn</th>
                        <th>Khách hàng</th>
                        <th>Địa chỉ</th>
                        <th>SĐT</th>
                        <th>Trạng thái</th>
                        <th>Ngày</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($orders as $order)

                        <tr>

                            <td>
                                {{ $loop->iteration }}
                            </td>

                            <td>
                                <strong>#{{ $order->id }}</strong>
                            </td>

                            <td>
                                {{ optional($order->customer)->name ?? 'Khách hàng' }}
                            </td>

                            <td>
                                {{ $order->address ?? '---' }}
                            </td>

                            <td>
                                {{ $order->phone ?? '---' }}
                            </td>

                            <td>

                                @if($order->status === 'completed')

                                    <span class="badge bg-success">
                                        Hoàn thành
                                    </span>

                                @elseif($order->status === 'cancelled')

                                    <span class="badge bg-danger">
                                        Đã hủy
                                    </span>

                                @else

                                    <span class="badge bg-secondary">
                                        {{ $order->status }}
                                    </span>

                                @endif

                            </td>

                            <td>
                                {{ $order->created_at?->format('d/m/Y H:i') }}
                            </td>

                            <td>

                                <a href="{{ route('shipper.orders.show', $order->id) }}"
                                   class="btn btn-sm btn-primary">

                                    <i class="fas fa-eye me-1"></i>
                                    Xem

                                </a>

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="8"
                                class="text-center py-5 text-muted">

                                <i class="fas fa-history fa-3x mb-3"></i>

                                <div>
                                    Chưa có lịch sử giao hàng.
                                </div>

                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        @if($orders->hasPages())

            <div class="card-footer bg-white">
                {{ $orders->links() }}
            </div>

        @endif

    </div>

</div>

@endsection