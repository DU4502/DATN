@extends('layouts.staff')

@section('page-title', 'Đơn hàng')
@section('search-placeholder', 'Tìm mã đơn, tên khách...')
@section('topbar-search-action', route('staff.orders.index'))

@section('content')
<style>
    .status-pill { display:inline-flex;align-items:center;justify-content:center;padding:.5rem 1.25rem;border-radius:50rem;font-weight:500;font-size:.875rem;text-decoration:none;transition:all .2s ease;white-space:nowrap;background:#e9ecef;color:#374151;border:none; }
    .status-pill:hover { background:#dee2e6;color:#111827; }
    .status-pill.active { background:#0D9373 !important;color:#fff !important;font-weight:600; }
    .status-text-pending { color:#d97706 !important; }
    .status-text-confirmed { color:#0891b2 !important; }
    .status-text-preparing { color:#0284c7 !important; }
    .status-text-ready_for_delivery { color:#06b6d4 !important; }
    .status-text-delivering { color:#7c3aed !important; }
    .status-text-delivered { color:#14b8a6 !important; }
    .status-text-ready_for_pickup { color:#06b6d4 !important; }
    .status-text-completed { color:#16a34a !important; }
    .status-text-cancelled { color:#dc2626 !important; }
    .order-detail-row > td { background:#f8fbfa; }
    .order-detail-card { border:1px solid rgba(17,24,39,.08);border-radius:24px;background:#fff;overflow:hidden; }
</style>

<div class="mb-4">
    <h2 class="h3 fw-bold text-dark mb-1">Quản lý đơn hàng</h2>
    <p class="text-secondary mb-0">
        Chi nhánh: <strong>{{ auth()->user()->branch?->name ?? 'Chưa được gán' }}</strong>
    </p>
</div>

<!-- Tabs trạng thái -->
<div class="d-flex flex-wrap gap-2 mb-4 pb-4 border-bottom">
    @php $currentStatus = $filters['status'] ?? ''; @endphp
    <a href="{{ route('staff.orders.index', array_merge(request()->query(), ['status' => ''])) }}"
       class="status-pill {{ $currentStatus === '' ? 'active' : '' }}">Tất cả</a>
    @foreach(\App\Support\OrderStatus::adminLabels() as $value => $label)
        <a href="{{ route('staff.orders.index', array_merge(request()->query(), ['status' => $value])) }}"
           class="status-pill {{ $currentStatus === $value ? 'active' : '' }}">{{ $label }}</a>
    @endforeach
</div>

<!-- Bộ lọc -->
<form method="GET" action="{{ route('staff.orders.index') }}">
    <input type="hidden" name="status" value="{{ $currentStatus }}">
    <section class="row g-3 align-items-end mb-4">
        <div class="col-md-3">
            <label class="admin-kicker mb-2 d-block">Tìm kiếm</label>
            <input class="admin-input" type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Mã đơn, tên hoặc email">
        </div>
        <div class="col-md-2">
            <label class="admin-kicker mb-2 d-block">Thanh toán</label>
            <select class="admin-filter" name="payment_status">
                <option value="" @selected(($filters['payment_status'] ?? '') === '')>Tất cả</option>
                <option value="pending" @selected(($filters['payment_status'] ?? '') === 'pending')>Chưa thanh toán</option>
                <option value="paid" @selected(($filters['payment_status'] ?? '') === 'paid')>Đã thanh toán</option>
                <option value="failed" @selected(($filters['payment_status'] ?? '') === 'failed')>Thất bại</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="admin-kicker mb-2 d-block">Khoảng ngày</label>
            <div class="d-flex gap-2 align-items-center">
                <input class="admin-input flex-grow-1" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
                <span class="text-secondary fw-semibold">đến</span>
                <input class="admin-input flex-grow-1" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
            </div>
        </div>
        <div class="col-md-3 d-flex gap-2 justify-content-end">
            <button class="btn btn-primary px-4" type="submit">Lọc</button>
            <a href="{{ route('staff.orders.index') }}" class="btn btn-outline-primary px-4">Làm mới</a>
        </div>
    </section>
</form>

<!-- Bảng đơn hàng -->
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
                    <th class="text-center">Cập nhật gần nhất</th>
                    <th class="text-center">Thời gian đổi</th>
                    <th class="text-center">Hành động</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                @php
                    $detailId        = 'order-detail-' . $order->id;
                    $fulfillmentType = $order->fulfillment_type ?? 'delivery';
                    $statusStepOpts  = \App\Support\OrderStatus::storeStepwiseOptions((string) $order->status, $fulfillmentType);
                    $nextStatus      = \App\Support\OrderStatus::storeNextStatus((string) $order->status, $fulfillmentType);
                    $changedBy       = $order->statusChangedBy;
                    $historyCount    = $order->statusHistories ? $order->statusHistories->count() : 0;
                @endphp
                <tr data-order-id="{{ $order->id }}" data-order-status="{{ \App\Support\OrderStatus::normalize((string) $order->status) }}">
                    <td class="fw-bold text-primary">{{ $order->displayCode() }}</td>
                    <td class="text-secondary small">{{ $order->created_at?->format('d/m/Y H:i') }}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="admin-avatar" style="width:32px;height:32px;font-size:.8rem;">{{ mb_substr($order->customerName() ?: 'K', 0, 1) }}</span>
                            <div>
                                <span class="fw-semibold d-block" style="font-size:.85rem;">{{ $order->customerName() ?: 'Khách hàng' }}</span>
                                <small class="text-secondary">{{ $order->customerPhone() ?: '' }}</small>
                            </div>
                        </div>
                    </td>
                    <td>
                        @if($order->payment_status === 'paid')
                            <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Đã TT</span>
                        @elseif($order->payment_status === 'failed')
                            <span class="badge bg-danger">Thất bại</span>
                        @else
                            <span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i>Chưa TT</span>
                        @endif
                    </td>
                    <td class="text-end fw-bold text-primary">{{ number_format($order->total ?? 0, 0, ',', '.') }}đ</td>
                    <td class="text-center">
                        <form action="{{ route('staff.orders.updateStatus', $order->id) }}" method="POST" data-staff-order-status-form data-order-id="{{ $order->id }}">
                            @csrf @method('PUT')
                            <select name="status" class="form-select form-select-sm"
                                    data-staff-order-status-select
                                    data-current-status="{{ \App\Support\OrderStatus::normalize((string) $order->status) }}"
                                    @disabled($nextStatus === null)>
                                <option value="{{ $order->status }}" selected>{{ \App\Support\OrderStatus::label((string) $order->status) }} (Hiện tại)</option>
                                @foreach($statusStepOpts as $value => $label)
                                    @if($value !== $order->status)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endif
                                @endforeach
                            </select>
                            <span class="spinner-border spinner-border-sm text-primary d-none mt-2" role="status" aria-label="Đang cập nhật" data-staff-order-status-loading></span>
                        </form>
                    </td>
                    <td class="text-center" data-staff-status-changed-by>
                        @if($changedBy)
                            <div class="d-inline-flex align-items-center gap-1" style="font-size: .8rem; white-space: nowrap;">
                                <i class="bi bi-person text-secondary"></i>
                                <span class="fw-semibold text-dark">{{ $changedBy->name }}</span>
                                @if($changedBy->isAdmin() || $changedBy->isSuperAdmin())
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: .62rem; padding: 2px 5px;">QL</span>
                                @elseif($changedBy->isStaffOnly())
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" style="font-size: .62rem; padding: 2px 5px;">NV</span>
                                @elseif($changedBy->isShipper())
                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle" style="font-size: .62rem; padding: 2px 5px;">TX</span>
                                @endif
                            </div>
                        @else
                            <span class="text-secondary small">—</span>
                        @endif
                    </td>
                    <td class="text-center" data-staff-status-changed-at>
                        @if($order->status_changed_at)
                            <span class="text-secondary small">{{ $order->status_changed_at->format('d/m H:i') }}</span>
                        @else
                            <span class="text-secondary small">—</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-link text-primary text-decoration-none fw-semibold p-0"
                                data-toggle-order-detail="{{ $order->id }}">Chi tiết</button>
                    </td>
                </tr>
                <!-- Dòng chi tiết -->
                <tr id="{{ $detailId }}" class="d-none order-detail-row">
                    <td colspan="9" class="border-0 pt-0">
                        <div class="order-detail-card p-4">
                            <div class="row g-4">
                                <div class="col-lg-7">
                                    <div class="admin-kicker mb-3 text-secondary fw-bold">SẢN PHẨM</div>
                                    <div class="d-grid gap-3">
                                        @foreach($order->orderItems as $item)
                                        <div class="p-3 bg-white" style="border:1px solid rgba(0,0,0,.08);border-radius:16px;">
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="{{ $item->product?->image_url }}" style="width:70px;height:70px;object-fit:cover;border-radius:10px;background:#f8f9fa;flex-shrink:0;">
                                                <div class="flex-grow-1">
                                                    <strong>{{ $item->product?->name ?? 'Sản phẩm đã xóa' }}</strong>
                                                    <div class="text-secondary small">Size: {{ $item->productSize?->size?->name ?? '?' }} · Đá {{ (int)$item->ice_level }}% · Đường {{ (int)$item->sugar_level }}%</div>
                                                    @if($item->toppingLines->isNotEmpty())
                                                        <div class="text-secondary small">Topping: {{ $item->toppingLines->pluck('topping.name')->filter()->implode(', ') }}</div>
                                                    @endif
                                                    @if(filled($item->item_note))
                                                        <div class="text-primary small"><i class="bi bi-chat-left-text me-1"></i>{{ $item->item_note }}</div>
                                                    @endif
                                                </div>
                                                <div class="text-end">
                                                    <div class="fw-bold">{{ number_format($item->getSubtotal(), 0, ',', '.') }}đ</div>
                                                    <small class="text-secondary">×{{ (int)$item->quantity }}</small>
                                                </div>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="col-lg-5">
                                    <div class="admin-kicker mb-3 text-secondary fw-bold">THÔNG TIN</div>
                                    <div class="mb-3 d-flex flex-column gap-2 text-secondary small">
                                        <div><i class="bi bi-telephone me-2"></i>{{ $order->customerPhone() ?: 'Chưa cập nhật' }}</div>
                                        <div><i class="bi bi-envelope me-2"></i>{{ $order->customerEmail() ?: 'Chưa cập nhật' }}</div>
                                        <div><i class="bi bi-geo-alt me-2"></i>{{ $order->getShippingAddress() }}</div>
                                    </div>

                                    @if($order->status_changed_at)
                                    <div class="alert alert-info border-0 py-2 px-3 mb-3" style="border-radius:10px;font-size:.82rem;">
                                        <i class="bi bi-clock-history me-1"></i>
                                        Trạng thái cập nhật lúc <strong>{{ $order->status_changed_at->format('H:i · d/m/Y') }}</strong>
                                        @if($changedBy) bởi <strong>{{ $changedBy->name }}</strong>@endif
                                    </div>
                                    @endif

                                    @if($order->status === \App\Support\OrderStatus::CANCELLED && $order->cancellation_reason)
                                    <div class="alert alert-danger border-0 py-2 px-3 mb-3" style="border-radius:10px;font-size:.82rem;">
                                        <i class="bi bi-exclamation-triangle me-1"></i>
                                        Lý do hủy: {{ $order->cancellation_reason }}
                                    </div>
                                    @endif

                                    <!-- Tổng hợp -->
                                    <div class="p-3 bg-white" style="border:1px solid rgba(0,0,0,.08);border-radius:16px;">
                                        <div class="d-flex flex-column gap-2" style="font-size:.875rem;">
                                            <div class="d-flex justify-content-between">
                                                <span class="text-secondary">Tạm tính</span>
                                                <span class="fw-semibold">{{ number_format((int)($order->subtotal ?? 0), 0, ',', '.') }}đ</span>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span class="text-secondary">Phí ship</span>
                                                <span class="fw-semibold">{{ number_format((int)($order->shipping_fee ?? 0), 0, ',', '.') }}đ</span>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span class="text-secondary">Giảm giá</span>
                                                <span class="fw-semibold text-danger">-{{ number_format((int)($order->discount ?? 0), 0, ',', '.') }}đ</span>
                                            </div>
                                            <hr class="my-1">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="fw-bold">Tổng</span>
                                                <span class="fw-bold text-primary" style="font-size:1.2rem;">{{ number_format((int)($order->total ?? 0), 0, ',', '.') }}đ</span>
                                            </div>
                                        </div>

                                        <!-- Nút hành động nhanh -->
                                        @if($nextStatus !== null)
                                        <div class="mt-3">
                                            <form action="{{ route('staff.orders.updateStatus', $order->id) }}" method="POST" data-staff-order-status-form data-order-id="{{ $order->id }}">
                                                @csrf @method('PUT')
                                                <input type="hidden" name="status" value="{{ $nextStatus }}">
                                                <button type="submit" class="btn btn-primary w-100" style="background:#00a870;border-color:#00a870;border-radius:10px;">
                                                    Chuyển: {{ \App\Support\OrderStatus::label($nextStatus) }} <i class="bi bi-arrow-right ms-1"></i>
                                                </button>
                                            </form>
                                        </div>
                                        @endif

                                        @if($order->status === \App\Support\OrderStatus::PENDING)
                                        <div class="mt-2">
                                            <button type="button" class="btn btn-outline-danger w-100" style="border-radius:10px;"
                                                    data-bs-toggle="modal" data-bs-target="#cancelOrderModal{{ $order->id }}">
                                                Hủy đơn
                                            </button>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>

                @if($order->status === \App\Support\OrderStatus::PENDING)
                <!-- Modal hủy đơn -->
                <div class="modal fade" id="cancelOrderModal{{ $order->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content" style="border-radius:16px;">
                            <form action="{{ route('staff.orders.updateStatus', $order->id) }}" method="POST" data-staff-order-status-form data-order-id="{{ $order->id }}">
                                @csrf @method('PUT')
                                <input type="hidden" name="status" value="cancelled">
                                <div class="modal-header border-0">
                                    <h5 class="modal-title fw-bold">Hủy đơn {{ $order->displayCode() }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <label class="form-label fw-semibold">Lý do hủy <span class="text-danger">*</span></label>
                                    <textarea name="cancellation_reason" class="form-control" rows="3"
                                              placeholder="Vui lòng nhập lý do hủy đơn..." required
                                              style="border-radius:10px;"></textarea>
                                </div>
                                <div class="modal-footer border-0">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                                    <button type="submit" class="btn btn-danger" style="border-radius:8px;">Xác nhận hủy đơn</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @endif

                @empty
                <tr>
                    <td colspan="9" class="text-center text-secondary py-5">
                        <div class="fw-bold text-dark mb-1">Chưa có đơn hàng</div>
                        <div>Các đơn mới sẽ xuất hiện tại đây.</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="d-flex justify-content-between align-items-center p-4 border-top" style="background:var(--admin-soft-2,#f8f9fa);">
        <p class="text-secondary mb-0">Đang hiển thị {{ $orders->count() }} đơn hàng</p>
        {{ $orders->links('pagination::bootstrap-5') }}
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const pendingStatusUpdates = new Set();
    const statusLabels = @json(\App\Support\OrderStatus::actionLabels());
    const csrfToken = @json(csrf_token());

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, char => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        })[char]);
    }

    function showToast(message, type = 'info') {
        if (typeof window.showRealtimeToast === 'function') {
            window.showRealtimeToast(message, type === 'error' ? 'warning' : type);
            return;
        }
        alert(message);
    }

    function normalizePayload(payload) {
        if (!payload || typeof payload !== 'object') return null;
        const orderId = payload.order_id || payload.id;
        const data = payload.data && typeof payload.data === 'object' ? payload.data : payload;
        const status = String(data.status || payload.status || '');
        if (!orderId || !status) return null;

        return {
            ...payload,
            ...data,
            id: data.id || orderId,
            order_id: orderId,
            status,
            status_label: data.status_label || payload.status_label || status,
            status_options: data.status_options || payload.status_options || {[status]: data.status_label || payload.status_label || status},
            next_status: data.next_status || payload.next_status || null,
            can_update: data.can_update ?? payload.can_update,
            status_changed_at: data.status_changed_at || payload.status_changed_at || null,
            status_changed_by_name: data.status_changed_by_name || payload.status_changed_by_name || null,
        };
    }

    function applyStatusPayload(rawPayload) {
        const payload = normalizePayload(rawPayload);
        if (!payload) return false;

        const row = document.querySelector(`tr[data-order-id="${payload.order_id}"]`);
        if (!row) return false;

        const select = row.querySelector('[data-staff-order-status-select]');
        if (select) {
            select.replaceChildren(...Object.entries(payload.status_options || {[payload.status]: payload.status_label}).map(([value, label]) => {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = label;
                option.selected = value === payload.status;
                return option;
            }));
            select.value = payload.status;
            select.dataset.currentStatus = payload.status;
            select.disabled = payload.can_update === false || select.options.length <= 1;
        }

        row.dataset.orderStatus = payload.status;

        const changedBy = row.querySelector('[data-staff-status-changed-by]');
        if (changedBy) {
            changedBy.innerHTML = payload.status_changed_by_name
                ? `<span class="badge bg-light text-dark" style="font-size:.72rem;"><i class="bi bi-person me-1"></i>${escapeHtml(payload.status_changed_by_name)}</span>`
                : '<span class="text-secondary small">—</span>';
        }

        const changedAt = row.querySelector('[data-staff-status-changed-at]');
        if (changedAt) {
            changedAt.innerHTML = payload.status_changed_at
                ? `<span class="text-secondary small">${escapeHtml(payload.status_changed_at)}</span>`
                : '<span class="text-secondary small">—</span>';
        }

        const detailRow = document.getElementById(`order-detail-${payload.order_id}`);
        const quickForm = detailRow?.querySelector('[data-staff-order-status-form] input[type="hidden"][name="status"]')?.closest('form');
        if (quickForm) {
            const hidden = quickForm.querySelector('input[name="status"]');
            const button = quickForm.querySelector('button[type="submit"]');
            if (payload.next_status) {
                hidden.value = payload.next_status;
                if (button) {
                    button.disabled = false;
                    button.innerHTML = `Chuyển: ${escapeHtml(statusLabels[payload.next_status] || payload.next_status)} <i class="bi bi-arrow-right ms-1"></i>`;
                }
            } else if (button) {
                button.disabled = true;
                button.textContent = 'Không còn bước chuyển';
            }
        }

        row.style.backgroundColor = 'rgba(13, 147, 115, 0.1)';
        window.setTimeout(() => { row.style.backgroundColor = ''; }, 1500);
        return true;
    }

    function responseErrorMessage(payload, fallback) {
        return payload?.errors ? Object.values(payload.errors).flat().find(Boolean) || fallback : (payload?.message || fallback);
    }

    async function submitStatusForm(form, sourceSelect = null) {
        const orderId = String(form.dataset.orderId || '');
        if (!orderId || pendingStatusUpdates.has(orderId)) return;

        const select = sourceSelect || form.querySelector('[data-staff-order-status-select]');
        const previousStatus = select?.dataset.currentStatus || '';
        const requestedStatus = select?.value || form.querySelector('input[name="status"]')?.value || '';
        if (select && requestedStatus === previousStatus) return;

        if (select && requestedStatus === 'cancelled') {
            select.value = previousStatus;
            showToast('Vui lòng mở chi tiết đơn để nhập lý do hủy.', 'error');
            return;
        }

        const loading = form.querySelector('[data-staff-order-status-loading]');
        pendingStatusUpdates.add(orderId);
        if (select) select.disabled = true;
        loading?.classList.remove('d-none');

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok || payload.success !== true) {
                throw new Error(responseErrorMessage(payload, 'Không thể cập nhật trạng thái đơn hàng.'));
            }
            applyStatusPayload(payload);
            showToast(payload.message || 'Đã cập nhật trạng thái đơn hàng.', 'success');
            form.closest('.modal') && bootstrap.Modal.getInstance(form.closest('.modal'))?.hide();
        } catch (error) {
            if (select && previousStatus) {
                select.value = previousStatus;
                select.dataset.currentStatus = previousStatus;
            }
            showToast(error.message || 'Không thể cập nhật trạng thái đơn hàng.', 'error');
        } finally {
            pendingStatusUpdates.delete(orderId);
            loading?.classList.add('d-none');
            if (select) select.disabled = select.options.length <= 1;
        }
    }

    document.querySelectorAll('[data-toggle-order-detail]').forEach(btn => {
        btn.addEventListener('click', function () {
            const id   = this.dataset.toggleOrderDetail;
            const row  = document.getElementById('order-detail-' + id);
            if (row) {
                row.classList.toggle('d-none');
                this.textContent = row.classList.contains('d-none') ? 'Chi tiết' : 'Ẩn';
            }
        });
    });

    document.addEventListener('change', event => {
        const select = event.target.closest('[data-staff-order-status-select]');
        if (select) submitStatusForm(select.closest('[data-staff-order-status-form]'), select);
    });

    document.addEventListener('submit', event => {
        const form = event.target.closest('[data-staff-order-status-form]');
        if (!form) return;
        event.preventDefault();
        submitStatusForm(form);
    });

    document.addEventListener('order:status-updated', event => {
        const applied = applyStatusPayload(event.detail || {});
        if (applied && event.detail?.status_label) {
            showToast(`Đơn ${event.detail.order_code || event.detail.order_id} đã chuyển sang: ${event.detail.status_label}.`, 'info');
        }
    });
});
</script>
@endsection
