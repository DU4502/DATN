@extends('layouts.admin')

@section('page-title', 'Quản lý đơn hàng')

@section('content')
<div class="admin-card card border-0">
    <div class="card-header bg-white py-3">
        <h2 class="h5 fw-bold mb-1">Đơn hàng</h2>
        <p class="text-secondary mb-0">Theo dõi thanh toán, trạng thái xử lý và khách hàng.</p>
    </div>
    <div class="table-responsive">
        <table class="table admin-table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Mã đơn</th>
                    <th>Khách hàng</th>
                    <th>Số món</th>
                    <th>Thanh toán</th>
                    <th>Tổng tiền</th>
                    <th>Trạng thái</th>
                    <th>Ngày đặt</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td class="fw-bold">#{{ $order->id }}</td>
                        <td>
                            <div class="fw-bold">{{ $order->user->name ?? 'Khách hàng' }}</div>
                            <small class="text-secondary">{{ $order->user->email ?? '' }}</small>
                        </td>
                        <td>{{ $order->orderItems->sum('quantity') }}</td>
                        <td>{{ strtoupper(str_replace('_', ' ', $order->payment_method)) }}</td>
                        <td class="fw-bold text-primary">{{ number_format($order->total_price, 0, ',', '.') }}đ</td>
                        <td>
                            <span class="badge
                                @if($order->status === 'completed') text-bg-success
                                @elseif($order->status === 'cancelled') text-bg-danger
                                @elseif($order->status === 'pending') text-bg-warning
                                @else text-bg-primary
                                @endif">
                                {{ ucfirst($order->status) }}
                            </span>
                        </td>
                        <td class="text-secondary">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-secondary py-5">Chưa có đơn hàng.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">
        {{ $orders->links() }}
    </div>
</div>
@endsection
