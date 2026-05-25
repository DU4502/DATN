@extends('layouts.admin')

@section('page-title', 'Đơn hàng')
@section('search-placeholder', 'Tìm mã đơn, khách hàng...')

@section('content')
<section class="row g-3 align-items-end mb-4">
    <div class="col-md-3">
        <label class="admin-kicker mb-2 d-block">Trạng thái đơn</label>
        <select class="admin-filter">
            <option>Tất cả trạng thái</option>
            <option>Chờ xử lý</option>
            <option>Đang giao</option>
            <option>Hoàn tất</option>
            <option>Đã hủy</option>
        </select>
    </div>
    <div class="col-md-5">
        <label class="admin-kicker mb-2 d-block">Khoảng ngày</label>
        <div class="d-flex gap-2 align-items-center">
            <input class="admin-input" type="date">
            <span class="text-secondary">đến</span>
            <input class="admin-input" type="date">
        </div>
    </div>
    <div class="col-md-4 d-flex gap-2">
        <button class="btn btn-primary flex-grow-1">Áp dụng lọc</button>
        <button class="btn btn-outline-primary">Làm mới</button>
    </div>
</section>

<section class="admin-card">
    <div class="table-responsive">
        <table class="table admin-table align-middle">
            <thead>
                <tr>
                    <th>Mã đơn</th>
                    <th>Ngày đặt</th>
                    <th>Khách hàng</th>
                    <th>Thanh toán</th>
                    <th class="text-end">Tổng tiền</th>
                    <th class="text-center">Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td class="fw-bold text-primary">#{{ $order->id }}</td>
                        <td class="text-secondary">{{ optional($order->created_at)->format('d/m/Y H:i') }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="admin-avatar" style="width:34px;height:34px;font-size:.8rem;">{{ mb_substr($order->user->name ?? 'K', 0, 1) }}</span>
                                <span>
                                    <span class="fw-bold d-block">{{ $order->user->name ?? 'Khách hàng' }}</span>
                                    <small class="text-secondary">{{ $order->user->email ?? '' }}</small>
                                </span>
                            </div>
                        </td>
                        <td>{{ strtoupper(str_replace('_', ' ', $order->payment_method ?? 'cod')) }}</td>
                        <td class="text-end fw-bold text-primary">{{ number_format($order->total_price ?? 0, 0, ',', '.') }}đ</td>
                        <td class="text-center">
                            @php($status = $order->status ?? 'pending')
                            <span class="badge
                                @if($status === 'completed') badge-soft-primary
                                @elseif($status === 'cancelled') badge-soft-danger
                                @else badge-soft-muted
                                @endif">
                                @if($status === 'completed') Hoàn tất
                                @elseif($status === 'cancelled') Đã hủy
                                @elseif($status === 'pending') Chờ xử lý
                                @else {{ $status }}
                                @endif
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-secondary py-5">
                            <div class="fw-bold text-dark mb-1">Chưa có đơn hàng</div>
                            <div>Các đơn mới sẽ xuất hiện tại đây.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 p-4 border-top" style="background: var(--admin-soft-2);">
        <p class="text-secondary mb-0">Đang hiển thị {{ $orders->count() }} đơn hàng</p>
        {{ $orders->links() }}
    </div>
</section>
@endsection
