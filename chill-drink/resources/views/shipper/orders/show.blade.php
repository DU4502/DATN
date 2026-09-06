@extends('layouts.shipper')

@section('title', 'Chi tiết đơn hàng')
@section('mobile-title', 'Chi tiết đơn')
@section('mobile-subtitle', 'Thông tin & thao tác giao hàng')

@section('content')
@php
    $normalizedStatusForBack = isset($order)
        ? \App\Support\OrderStatus::normalize((string) $order->status)
        : null;
    $backRoute = in_array($normalizedStatusForBack, [
        \App\Support\OrderStatus::DELIVERED,
        \App\Support\OrderStatus::COMPLETED,
    ], true)
        ? route('shipper.history')
        : route('shipper.orders');
@endphp

<div class="container-fluid ship-order-show-page">

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

        <a href="{{ $backRoute }}"
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

        @php
            $normalizedStatus = \App\Support\OrderStatus::normalize((string) $order->status);
            $statusLabel = \App\Support\OrderStatus::label((string) $order->status);
            $handoverContext = is_array($handoverContext ?? null) ? $handoverContext : null;
            $handoverPending = !empty($handoverContext);
            $pendingIssue = is_array($pendingIssue ?? null) ? $pendingIssue : null;
            $statusBadgeClass = match ($normalizedStatus) {
                'pending' => 'bg-secondary',
                'confirmed' => 'bg-info',
                'preparing' => 'bg-primary',
                'ready_for_delivery' => 'bg-warning text-dark',
                'ready_for_pickup' => 'bg-info',
                'shipper_picked_up' => 'bg-warning text-dark',
                'delivering' => 'bg-warning text-dark',
                'delivered' => 'bg-success',
                'completed' => 'bg-success',
                'cancelled' => 'bg-danger',
                default => 'bg-secondary',
            };
        @endphp

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

                                    <span class="badge {{ $statusBadgeClass }}" data-shipper-detail-status>
                                        {{ $statusLabel }}
                                    </span>

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

                                    {{ $order->customerName() ?: 'Khách hàng' }}

                                </div>

                            </div>


                            {{-- SỐ ĐIỆN THOẠI --}}
                            <div class="col-md-6">

                                <label class="text-muted">
                                    Số điện thoại
                                </label>

                                <div>

                                    @php $phone = $order->customerPhone(); @endphp
                                    @if(!empty($phone))

                                        <a href="tel:{{ $phone }}"
                                           class="text-decoration-none">

                                            <i class="fas fa-phone text-success me-1"></i>

                                            {{ $phone }}

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

                                    @if($order->getShippingAddress() !== 'Chưa cập nhật địa chỉ')

                                        <div>

                                            <i class="fas fa-location-dot text-danger me-2"></i>

                                            {{ $order->getShippingAddress() }}

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

                        @if(!empty($bundleTrip))
                            <div class="alert alert-info small">
                                <strong><i class="fas fa-layer-group me-1"></i>{{ $bundleLabel ?? 'Chuyến ghép thuận đường' }}</strong><br>
                                Đơn này đang đi chung một chuyến khác. Hệ thống tự quyết định thứ tự ghé quán/giao khách theo route tối ưu.
                            </div>
                        @endif

                        @if(empty($order->shipper_id) && in_array($normalizedStatus, ['confirmed', 'preparing', 'ready_for_delivery'], true))
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-satellite-dish me-2"></i>Đơn đang chờ hệ thống điều phối shipper. Shipper không tự chọn đơn.
                            </div>

                        @elseif(isset($shipperInfo) && $order->shipper_id == $shipperInfo->id)
                            <a href="{{ route('shipper.map', ['id' => $order->id]) }}" class="btn btn-primary w-100 mb-2">
                                <i class="fas fa-map-marked-alt me-2"></i>Mở dẫn đường
                            </a>

                            @if($pendingIssue)
                                <div class="alert alert-warning small mb-2">
                                    <strong>Sự cố đã gửi:</strong> {{ $pendingIssue['description'] ?? 'Đang chờ cửa hàng/admin xử lý.' }}
                                </div>
                            @endif

                            @if($handoverPending)
                                <div class="alert alert-primary small mb-2">
                                    <strong>Đang cứu chuyến:</strong> hàng đã rời quán. Hãy mở <strong>Dẫn đường</strong> để tới điểm bàn giao với shipper cũ. Sau khi GPS xác minh, nút <strong>Đã nhận bàn giao</strong> sẽ mở.
                                </div>
                                @if(empty($pendingIssue) && !empty($isAccepted))
                                    <button class="btn btn-outline-warning w-100" type="button" data-bs-toggle="modal" data-bs-target="#issueModal">
                                        <i class="fas fa-triangle-exclamation me-2"></i>Báo sự cố
                                    </button>
                                @endif
                            @elseif(in_array($normalizedStatus, ['confirmed', 'preparing'], true))
                                <div class="alert alert-info small mb-2">
                                    Quán đã xác nhận đơn. Bạn đã giữ chuyến này và có thể di chuyển tới cửa hàng để chờ bàn giao.
                                </div>
                            @elseif($normalizedStatus === 'ready_for_delivery')
                                <div class="alert alert-success small mb-2">
                                    Đơn đã sẵn sàng. Mở <strong>Dẫn đường</strong>; khi GPS ghi nhận bạn tới cửa hàng, nút <strong>Đã lấy hàng</strong> sẽ tự mở.
                                </div>
                                <button class="btn btn-outline-warning w-100" type="button" data-bs-toggle="modal" data-bs-target="#issueModal">
                                    <i class="fas fa-triangle-exclamation me-2"></i>Báo sự cố
                                </button>
                            @elseif($normalizedStatus === 'shipper_picked_up')
                                @php
                                    $isCurrentDeliveryStop = empty($bundleTrip)
                                        || (($bundleCurrentStop['type'] ?? null) === 'delivery'
                                            && (int) ($bundleCurrentStop['order_id'] ?? 0) === (int) $order->id);
                                @endphp
                                @if($isCurrentDeliveryStop)
                                    <div class="alert alert-success small mb-2">
                                        Bạn đã lấy hàng xong. Hệ thống sẽ tự chuyển sang chặng giao khi đơn này tới lượt, không cần vuốt Bắt đầu giao nữa.
                                    </div>
                                @else
                                    <div class="alert alert-light border small mb-2">
                                        <i class="fas fa-lock me-1"></i>Đơn này đang chờ trong chuyến ghép. Hãy hoàn tất điểm/đơn phía trước; hệ thống sẽ tự chuyển đơn này sang chặng giao khi tới lượt.
                                    </div>
                                    <a href="{{ route('shipper.map') }}" class="btn btn-primary w-100 mb-2"><i class="fas fa-location-arrow me-1"></i>Về điểm đang thực hiện</a>
                                @endif
                                <button class="btn btn-outline-warning w-100" type="button" data-bs-toggle="modal" data-bs-target="#issueModal">
                                    <i class="fas fa-triangle-exclamation me-2"></i>Báo sự cố
                                </button>
                            @elseif($normalizedStatus === 'delivering')
                                <div class="alert alert-info small mb-2">
                                    Xác nhận giao hàng trong màn <strong>Dẫn đường</strong> để hệ thống lưu GPS điểm giao thực tế.
                                </div>
                                <button class="btn btn-outline-warning w-100" type="button" data-bs-toggle="modal" data-bs-target="#issueModal">
                                    <i class="fas fa-triangle-exclamation me-2"></i>Báo sự cố
                                </button>
                            @elseif($normalizedStatus === 'delivered')
                                <div class="alert alert-success mb-0">
                                    <i class="fas fa-check-circle me-2"></i>Đã giao, đang chờ khách xác nhận.
                                </div>
                            @endif
                        @else
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-info-circle me-2"></i>Đơn hàng đang thuộc shipper khác.
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



@if(isset($shipperInfo) && $order->shipper_id == $shipperInfo->id && !empty($isAccepted) && in_array($normalizedStatus, ['confirmed','preparing','ready_for_delivery','shipper_picked_up','delivering'], true))
<div class="modal fade" id="issueModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="{{ route('shipper.orders.issue', $order->id) }}">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Báo sự cố</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="incident_type" value="customer_cancel">
                <div class="alert alert-warning small"><strong>Chỉ báo sự cố phía khách.</strong> Admin hoặc Super Admin sẽ xác nhận hủy và hướng dẫn bạn mang đồ về quán.</div>
                <label class="form-label fw-semibold">Sự cố <span class="text-danger">*</span></label>
                <select class="form-select mb-3" name="reason" id="issueReason" required>
                    <option value="">Chọn sự cố</option>
                    <option value="customer_unreachable">Không liên lạc được khách</option>
                    <option value="customer_refused">Gọi được nhưng khách không nhận hàng</option>
                </select>
                <textarea class="form-control" name="reason_detail" rows="3" maxlength="1000" placeholder="Mô tả thêm..."></textarea>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Đóng</button><button class="btn btn-warning">Gửi báo cáo</button></div>
        </form>
    </div>
</div>
@endif

@endif

</div>


<style>
.ship-order-show-page > .d-flex.justify-content-between{margin:2px 2px 10px!important;align-items:flex-start!important;gap:8px!important}
.ship-order-show-page > .d-flex.justify-content-between h3{font-size:18px!important;margin:0!important}.ship-order-show-page > .d-flex.justify-content-between p{font-size:10.5px!important;margin-top:3px!important}.ship-order-show-page > .d-flex.justify-content-between>a{display:none!important}
.ship-order-show-page .row.g-4{--bs-gutter-y:10px}.ship-order-show-page .card{margin-top:10px!important}.ship-order-show-page .card:first-child{margin-top:0!important}.ship-order-show-page .card-header{padding:12px 13px!important}.ship-order-show-page .card-header h5{font-size:13px!important}.ship-order-show-page .card-body{padding:13px!important}.ship-order-show-page label.text-muted{font-size:9.5px}.ship-order-show-page .fw-semibold,.ship-order-show-page .fw-bold{font-size:12px}.ship-order-show-page .fs-5{font-size:15px!important}.ship-order-show-page .badge{font-size:9.5px;padding:6px 8px!important}
.ship-order-show-page .table-responsive{border:0!important;background:transparent!important;overflow:visible!important}.ship-order-show-page table thead{display:none}.ship-order-show-page table,.ship-order-show-page tbody{display:block;width:100%}.ship-order-show-page table tr{display:grid;grid-template-columns:1fr auto;gap:5px 10px;background:#f8faf9;border:1px solid var(--ship-line);border-radius:14px;padding:10px;margin:8px 0}.ship-order-show-page table td{display:block!important;border:0!important;padding:0!important;font-size:10.5px!important}.ship-order-show-page table td:nth-child(1){display:none!important}.ship-order-show-page table td:nth-child(2){font-weight:800;grid-column:1/-1}.ship-order-show-page table td:nth-child(3)::before{content:'SL: ';color:var(--ship-muted)}.ship-order-show-page table td:nth-child(4),.ship-order-show-page table td:nth-child(5){text-align:right!important}.ship-order-show-page table td:nth-child(5){font-weight:850;color:var(--ship-green-dark)}
.ship-order-show-page .col-lg-4 .card:first-child{position:sticky;bottom:78px;z-index:15;box-shadow:0 12px 28px rgba(18,52,42,.14)!important}.ship-order-show-page .col-lg-4 .card:first-child .card-header{display:none}.ship-order-show-page .col-lg-4 .card:first-child .card-body{padding:10px!important}.ship-order-show-page .col-lg-4 .card:first-child .btn{font-size:11px;min-height:42px}.ship-order-show-page .col-lg-4 .card:first-child .alert{font-size:10px;padding:9px;margin-bottom:7px!important}.ship-order-show-page .modal-content{border:0;border-radius:22px}.ship-order-show-page .modal-dialog{margin:12px}.ship-order-show-page .modal-footer{display:grid;grid-template-columns:1fr 1fr}.ship-order-show-page .modal-footer .btn{width:100%;margin:0}
</style>

<script>
document.addEventListener('shipper:order-status-updated', event => {
    const payload = event.detail || {};
    if (Number(payload.order_id || 0) !== {{ (int) $order->id }}) return;

    const badge = document.querySelector('[data-shipper-detail-status]');
    if (!badge) return;

    const status = String(payload.status || '');
    const classes = {
        pending: 'bg-secondary',
        confirmed: 'bg-info',
        preparing: 'bg-primary',
        ready_for_delivery: 'bg-warning text-dark',
        ready_for_pickup: 'bg-info',
        shipper_picked_up: 'bg-warning text-dark',
        delivering: 'bg-warning text-dark',
        delivered: 'bg-success',
        completed: 'bg-success',
        cancelled: 'bg-danger',
    };

    badge.className = `badge ${classes[status] || 'bg-secondary'}`;
    badge.textContent = payload.status_label || status || 'Đã cập nhật';
});
</script>

@endsection
