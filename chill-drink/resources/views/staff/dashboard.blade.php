@extends('layouts.staff')

@section('page-title', 'Tổng quát')
@section('hide-topbar-search', true)

@section('content')
<div class="mb-4">
    <h2 class="h3 fw-bold text-dark mb-1">Xin chào, {{ auth()->user()->name }}!</h2>
    <p class="text-secondary mb-0">
        <i class="bi bi-building me-1"></i>
        Chi nhánh: <strong>{{ auth()->user()->branch?->name ?? 'Chưa được gán chi nhánh' }}</strong>
    </p>
</div>

<!-- Thống kê nhanh -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="admin-card p-4 h-100 d-flex flex-column gap-2">
            <div class="d-flex align-items-center justify-content-between">
                <span class="text-secondary" style="font-size:0.8rem;font-weight:600;letter-spacing:.04em;">CÔNG VIỆC HIỆN TẠI</span>
                <span style="width:36px;height:36px;border-radius:10px;background:#e6f7f2;display:flex;align-items:center;justify-content:center;color:#00a870;"><i class="bi bi-receipt"></i></span>
            </div>
            <span class="fw-bold" style="font-size:1.8rem;color:#111827;line-height:1;">{{ $totalWork }}</span>
            <small class="text-secondary">đơn và đơn nhóm cần theo dõi</small>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="admin-card p-4 h-100 d-flex flex-column gap-2">
            <div class="d-flex align-items-center justify-content-between">
                <span class="text-secondary" style="font-size:0.8rem;font-weight:600;letter-spacing:.04em;">ĐƠN MỚI</span>
                <span style="width:36px;height:36px;border-radius:10px;background:#fff8e6;display:flex;align-items:center;justify-content:center;color:#d97706;"><i class="bi bi-hourglass-split"></i></span>
            </div>
            <span class="fw-bold" style="font-size:1.8rem;color:#d97706;line-height:1;">{{ $newOrders }}</span>
            <small class="text-secondary">đang chờ xác nhận</small>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="admin-card p-4 h-100 d-flex flex-column gap-2">
            <div class="d-flex align-items-center justify-content-between">
                <span class="text-secondary" style="font-size:0.8rem;font-weight:600;letter-spacing:.04em;">ĐANG CHUẨN BỊ</span>
                <span style="width:36px;height:36px;border-radius:10px;background:#edf7ff;display:flex;align-items:center;justify-content:center;color:#0284c7;"><i class="bi bi-cup-hot"></i></span>
            </div>
            <span class="fw-bold" style="font-size:1.8rem;color:#0284c7;line-height:1;">{{ $preparingOrders }}</span>
            <small class="text-secondary">đã xác nhận hoặc đang pha chế</small>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="admin-card p-4 h-100 d-flex flex-column gap-2">
            <div class="d-flex align-items-center justify-content-between">
                <span class="text-secondary" style="font-size:0.8rem;font-weight:600;letter-spacing:.04em;">CHỜ BÀN GIAO</span>
                <span style="width:36px;height:36px;border-radius:10px;background:#f1f0ff;display:flex;align-items:center;justify-content:center;color:#7c3aed;"><i class="bi bi-box-seam"></i></span>
            </div>
            <span class="fw-bold" style="font-size:1.8rem;color:#7c3aed;line-height:1;">{{ $readyForDeliveryOrders }}</span>
            <small class="text-secondary">đơn đang chờ Shipper lấy</small>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-4">
        <div class="admin-card p-4 h-100 d-flex flex-column gap-2">
            <span class="text-secondary" style="font-size:0.8rem;font-weight:600;">CHỜ KHÁCH LẤY</span>
            <span class="fw-bold text-success" style="font-size:1.8rem;line-height:1;">{{ $readyForPickupOrders }}</span>
            <small class="text-secondary">đơn tự lấy đã chuẩn bị xong</small>
        </div>
    </div>
    <div class="col-6 col-lg-4">
        <div class="admin-card p-4 h-100 d-flex flex-column gap-2">
            <span class="text-secondary" style="font-size:0.8rem;font-weight:600;">ĐƠN NHÓM</span>
            <span class="fw-bold" style="font-size:1.8rem;color:#7c3aed;line-height:1;">{{ $groupOrdersToHandle }}</span>
            <small class="text-secondary">đang mở hoặc chờ xử lý</small>
        </div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="admin-card p-4 h-100 d-flex flex-column gap-2">
            <span class="text-secondary" style="font-size:0.8rem;font-weight:600;">ĐƠN TRONG NGÀY</span>
            <span class="fw-bold" style="font-size:1.8rem;color:#111827;line-height:1;">{{ $todayOrders }}</span>
            <small class="text-secondary">tại chi nhánh của bạn</small>
        </div>
    </div>
</div>

<!-- Đơn hàng cần xử lý -->
<div class="admin-card mb-4" data-staff-dashboard-orders-card>
    <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
        <div>
            <h3 class="h5 fw-bold mb-1">Đơn hàng cần xử lý</h3>
            <small class="text-secondary">Ưu tiên đơn cũ nhất thuộc công việc tại quán</small>
        </div>
        <a href="{{ route('staff.orders.index') }}" class="btn btn-sm btn-outline-primary">Xem tất cả</a>
    </div>
    <div data-staff-dashboard-orders-list>
    @forelse($recentOrders as $order)
    @php
        $fulfillmentType = $order->fulfillment_type ?? 'delivery';
        $nextStatus = \App\Support\OrderStatus::storeNextStatus((string) $order->status, $fulfillmentType);
        $canCancel = $order->status === \App\Support\OrderStatus::PENDING;
    @endphp
    <div class="p-4 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3"
        data-staff-dashboard-order
        data-order-id="{{ $order->id }}"
        data-order-status="{{ $order->status }}"
        data-fulfillment-type="{{ $fulfillmentType }}">
        <div class="d-flex align-items-center gap-3">
            <div style="width:40px;height:40px;border-radius:10px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;color:#64748b;flex-shrink:0;">
                <i class="bi bi-bag"></i>
            </div>
            <div>
                <div class="fw-bold text-primary">{{ $order->displayCode() }}</div>
                <div class="text-secondary" style="font-size:0.82rem;">{{ $order->customerName() ?: 'Khách hàng' }}</div>
                <div class="text-secondary" style="font-size:0.75rem;">{{ $order->created_at?->format('H:i · d/m/Y') }}</div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="fw-bold text-primary">{{ number_format($order->total ?? 0, 0, ',', '.') }}đ</span>
            @if($nextStatus)
            <form action="{{ route('staff.orders.updateStatus', $order->id) }}" method="POST" class="mb-0" data-dashboard-status-form>
                @csrf
                @method('PUT')
                <input type="hidden" name="status" value="{{ $nextStatus }}" data-dashboard-status-input>
                <button type="submit" class="btn btn-sm btn-primary" style="background:#00a870;border-color:#00a870;" data-dashboard-status-button>
                    {{ \App\Support\OrderStatus::label($nextStatus) }} <i class="bi bi-arrow-right ms-1"></i>
                </button>
            </form>
            @else
            <span class="badge bg-success">{{ \App\Support\OrderStatus::label($order->status) }}</span>
            @endif
            @if($canCancel)
            <button type="button" class="btn btn-sm btn-outline-danger"
                data-bs-toggle="modal"
                data-bs-target="#cancelOrderModal"
                data-order-id="{{ $order->id }}"
                data-order-code="{{ $order->displayCode() }}">
                <i class="bi bi-x-circle me-1"></i>Hủy
            </button>
            @endif
        </div>
    </div>
    @empty
    <div class="p-5 text-center text-secondary" data-staff-dashboard-empty>
        <i class="bi bi-check-circle" style="font-size:2rem;color:#00a870;"></i>
        <p class="mt-2 mb-0 fw-semibold">Không có đơn hàng nào cần xử lý!</p>
    </div>
    @endforelse
    </div>
</div>

<!-- Modal hủy đơn hàng -->
<div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-labelledby="cancelOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="cancelOrderModalLabel">
                    <i class="bi bi-x-circle text-danger me-2"></i>Hủy đơn hàng
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <form id="cancelOrderForm" method="POST" action="">
                @csrf
                @method('PUT')
                <input type="hidden" name="status" value="cancelled">
                <div class="modal-body">
                    <p class="mb-3">Bạn đang hủy đơn hàng <strong id="cancelOrderCode"></strong>. Hành động này không thể hoàn tác.</p>
                    <div class="mb-3">
                        <label for="cancelReason" class="form-label fw-semibold">Lý do hủy <span class="text-danger">*</span></label>
                        <textarea id="cancelReason" name="cancellation_reason" class="form-control" rows="3"
                            placeholder="Nhập lý do hủy đơn hàng..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-x-circle me-1"></i>Xác nhận hủy
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var cancelModal = document.getElementById('cancelOrderModal');
    if (cancelModal) {
        cancelModal.addEventListener('show.bs.modal', function (event) {
            var btn = event.relatedTarget;
            var orderId = btn.getAttribute('data-order-id');
            var orderCode = btn.getAttribute('data-order-code');
            document.getElementById('cancelOrderCode').textContent = orderCode;
            document.getElementById('cancelOrderForm').action = '{{ url('staff/orders') }}/' + orderId + '/status';
            // Reset textarea
            document.getElementById('cancelReason').value = '';
        });
    }

    const statusLabels = {
        pending: 'Chờ xác nhận',
        confirmed: 'Đã xác nhận',
        preparing: 'Đang pha chế',
        ready_for_delivery: 'Sẵn sàng giao',
        shipper_picked_up: 'Shipper đã lấy hàng',
        delivering: 'Đang giao',
        delivered: 'Đã giao',
        ready_for_pickup: 'Sẵn sàng lấy',
        completed: 'Hoàn thành',
        cancelled: 'Đã hủy'
    };

    const escapeHtml = function (value) {
        const div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    };

    const createDashboardOrderRow = function (payload) {
        const orderId = Number(payload.order_id || payload.id) || 0;
        if (!orderId) return null;

        const nextStatus = payload.next_status || null;
        const updateUrl = payload.status_update_url || `{{ url('staff/orders') }}/${orderId}/status`;
        const row = document.createElement('div');
        row.className = 'p-4 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3';
        row.dataset.staffDashboardOrder = '';
        row.dataset.orderId = String(orderId);
        row.dataset.orderStatus = payload.status || '';
        row.dataset.fulfillmentType = payload.fulfillment_type || 'delivery';
        row.innerHTML = `
            <div class="d-flex align-items-center gap-3">
                <div style="width:40px;height:40px;border-radius:10px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;color:#64748b;flex-shrink:0;">
                    <i class="bi bi-bag"></i>
                </div>
                <div>
                    <div class="fw-bold text-primary">${escapeHtml(payload.order_code || `#${orderId}`)}</div>
                    <div class="text-secondary" style="font-size:0.82rem;">${escapeHtml(payload.customer_name || 'Khách hàng')}</div>
                    <div class="text-secondary" style="font-size:0.75rem;">${escapeHtml(payload.created_at || '')}</div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="fw-bold text-primary">${escapeHtml(payload.total_formatted || '')}</span>
                ${nextStatus ? `
                    <form action="${escapeHtml(updateUrl)}" method="POST" class="mb-0" data-dashboard-status-form>
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="status" value="${escapeHtml(nextStatus)}" data-dashboard-status-input>
                        <button type="submit" class="btn btn-sm btn-primary" style="background:#00a870;border-color:#00a870;" data-dashboard-status-button>
                            ${escapeHtml(statusLabels[nextStatus] || nextStatus)} <i class="bi bi-arrow-right ms-1"></i>
                        </button>
                    </form>
                ` : `<span class="badge bg-success">${escapeHtml(statusLabels[payload.status] || payload.status_label || payload.status || 'Đã cập nhật')}</span>`}
            </div>
        `;

        return row;
    };

    window.updateStaffDashboardOrder = function (payload) {
        payload = payload || {};
        const orderId = Number(payload.order_id || payload.id) || 0;
        if (!orderId) return;

        let row = document.querySelector(`[data-staff-dashboard-order][data-order-id="${orderId}"]`);
        if (!row) {
            row = createDashboardOrderRow(payload);
            const list = document.querySelector('[data-staff-dashboard-orders-list]');
            if (!row || !list) return;

            list.querySelector('[data-staff-dashboard-empty]')?.remove();
            list.prepend(row);
        }

        row.dataset.orderStatus = payload.status || row.dataset.orderStatus;
        const form = row.querySelector('[data-dashboard-status-form]');
        const button = row.querySelector('[data-dashboard-status-button]');
        const input = row.querySelector('[data-dashboard-status-input]');
        if (!form || !button || !input) return;

        const nextStatus = payload.next_status || null;
        if (nextStatus) {
            input.value = nextStatus;
            button.disabled = false;
            button.textContent = statusLabels[nextStatus] || nextStatus;
            const icon = document.createElement('i');
            icon.className = 'bi bi-arrow-right ms-1';
            button.appendChild(icon);
        } else {
            const badge = document.createElement('span');
            badge.className = 'badge bg-success';
            badge.textContent = statusLabels[payload.status] || payload.status_label || payload.status || 'Đã cập nhật';
            form.replaceWith(badge);
        }

        row.style.backgroundColor = 'rgba(13, 147, 115, 0.1)';
        window.setTimeout(() => { row.style.backgroundColor = ''; }, 1500);
    };

    document.addEventListener('order:status-updated', function (event) {
        window.updateStaffDashboardOrder(event.detail);
    });
});
</script>

<!-- Liên kết nhanh -->
<div class="row g-3">
    <div class="col-md-3">
        <a href="{{ route('staff.orders.index') }}" class="admin-card p-4 d-flex align-items-center gap-3 text-decoration-none" style="color:inherit;">
            <span style="width:48px;height:48px;border-radius:12px;background:#e6f7f2;display:flex;align-items:center;justify-content:center;color:#00a870;font-size:1.4rem;flex-shrink:0;"><i class="bi bi-receipt"></i></span>
            <div>
                <div class="fw-bold">Quản lý đơn hàng</div>
                <small class="text-secondary">Xem & cập nhật trạng thái</small>
            </div>
            <i class="bi bi-chevron-right ms-auto text-secondary"></i>
        </a>
    </div>
    <div class="col-md-3">
        <a href="{{ route('staff.group-orders.index') }}" class="admin-card p-4 d-flex align-items-center gap-3 text-decoration-none" style="color:inherit;">
            <span style="width:48px;height:48px;border-radius:12px;background:#f1f0ff;display:flex;align-items:center;justify-content:center;color:#7c3aed;font-size:1.4rem;flex-shrink:0;"><i class="bi bi-people-fill"></i></span>
            <div>
                <div class="fw-bold">Đơn nhóm</div>
                <small class="text-secondary">Xem & quản lý đơn nhóm</small>
            </div>
            <i class="bi bi-chevron-right ms-auto text-secondary"></i>
        </a>
    </div>
    <div class="col-md-3">
        <a href="{{ route('staff.chat.index') }}" class="admin-card p-4 d-flex align-items-center gap-3 text-decoration-none" style="color:inherit;">
            <span style="width:48px;height:48px;border-radius:12px;background:#fff8e6;display:flex;align-items:center;justify-content:center;color:#d97706;font-size:1.4rem;flex-shrink:0;"><i class="bi bi-chat-dots"></i></span>
            <div>
                <div class="fw-bold">Chat hỗ trợ</div>
                <small class="text-secondary">Trả lời khách hàng</small>
            </div>
            <i class="bi bi-chevron-right ms-auto text-secondary"></i>
        </a>
    </div>
    <div class="col-md-3">
        <a href="{{ route('staff.products.availability.index') }}" class="admin-card p-4 d-flex align-items-center gap-3 text-decoration-none" style="color:inherit;">
            <span style="width:48px;height:48px;border-radius:12px;background:#fee2e2;display:flex;align-items:center;justify-content:center;color:#dc2626;font-size:1.4rem;flex-shrink:0;"><i class="bi bi-box-seam"></i></span>
            <div>
                <div class="fw-bold">Tình trạng sản phẩm</div>
                <small class="text-secondary">Còn hàng hoặc tạm hết</small>
            </div>
            <i class="bi bi-chevron-right ms-auto text-secondary"></i>
        </a>
    </div>
</div>
@endsection
