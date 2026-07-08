@extends('layouts.admin')

@section('page-title', 'Đơn hàng')

@section('content')
<style>
    .order-detail-row > td {
        background: #f8fbfa;
    }

    .order-detail-card {
        border: 1px solid rgba(17, 24, 39, 0.08);
        border-radius: 24px;
        background: #fff;
        overflow: hidden;
    }

    .order-detail-summary {
        min-width: 220px;
    }

    @media (max-width: 991.98px) {
        .order-detail-card {
            padding: 1rem !important;
        }

        .order-detail-summary {
            min-width: 0;
        }
    }

    @media (max-width: 767.98px) {
        .order-detail-row > td {
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }
    }
    
    /* Custom status tabs matching image 2 */
    .status-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.5rem 1.25rem;
        border-radius: 50rem;
        font-weight: 500;
        font-size: 0.875rem;
        text-decoration: none;
        transition: all 0.2s ease;
        white-space: nowrap;
        background: #e9ecef; 
        color: #374151;
        border: none;
    }

    .status-pill:hover {
        background: #dee2e6;
        color: #111827;
    }

    .status-pill.active {
        background: #0D9373 !important;
        color: #ffffff !important;
        font-weight: 600;
    }

    /* Custom order status texts matching image 1 */
    .status-text-pending { color: #d97706 !important; }
    .status-text-in_progress { color: #0284c7 !important; }
    .status-text-shipper_accepted { color: #7c3aed !important; }
    .status-text-arrived { color: #0891b2 !important; }
    .status-text-completed { color: #16a34a !important; }
    .status-text-cancelled { color: #dc2626 !important; }
</style>

<div class="mb-4">
    <h2 class="h3 fw-bold text-dark mb-1">Quản lý đơn hàng</h2>
    <p class="text-secondary mb-0">Xem và quản lý tất cả đơn hàng</p>
</div>

<div class="d-flex flex-wrap gap-2 mb-4 pb-4 border-bottom">
    @php
        $currentStatus = $filters['status'] ?? '';
    @endphp
    
    <a href="{{ route('admin.orders.index', array_merge(request()->query(), ['status' => ''])) }}" 
       class="status-pill status-pill-all {{ $currentStatus === '' ? 'active' : '' }}">
        Tất cả
    </a>
    
    @foreach(\App\Support\OrderStatus::adminLabels() as $value => $label)
        <a href="{{ route('admin.orders.index', array_merge(request()->query(), ['status' => $value])) }}" 
           class="status-pill status-pill-{{ $value }} {{ $currentStatus === $value ? 'active' : '' }}">
            {{ $label }}
        </a>
    @endforeach
</div>

<form method="GET" action="{{ route('admin.orders.index') }}">
    <input type="hidden" name="status" value="{{ $currentStatus }}">
    <section class="row g-3 align-items-end mb-4">
        <div class="col-xl-2 col-md-6">
            <label class="admin-kicker mb-2 d-block">Tìm kiếm</label>
            <input class="admin-input" type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Mã đơn, tên hoặc email">
        </div>
        <div class="col-xl-2 col-md-6">
            <label class="admin-kicker mb-2 d-block">Thanh toán</label>
            <select class="admin-filter" name="payment_status">
                <option value="" @selected(($filters['payment_status'] ?? '') === '')>Tất cả thanh toán</option>
                <option value="pending" @selected(($filters['payment_status'] ?? '') === 'pending')>Chưa thanh toán</option>
                <option value="paid" @selected(($filters['payment_status'] ?? '') === 'paid')>Đã thanh toán</option>
                <option value="failed" @selected(($filters['payment_status'] ?? '') === 'failed')>Thất bại</option>
            </select>
        </div>
        <div class="col-xl-2 col-md-6">
            <label class="admin-kicker mb-2 d-block">Phương thức</label>
            <select class="admin-filter" name="payment_method">
                <option value="" @selected(($filters['payment_method'] ?? '') === '')>Tất cả phương thức</option>
                <option value="cod" @selected(($filters['payment_method'] ?? '') === 'cod')>COD</option>
                <option value="bank_transfer" @selected(($filters['payment_method'] ?? '') === 'bank_transfer')>Chuyển khoản</option>
                <option value="vnpay" @selected(($filters['payment_method'] ?? '') === 'vnpay')>VNPay</option>
            </select>
        </div>
        <div class="col-xl-2 col-md-6">
            <label class="admin-kicker mb-2 d-block">Loại giao</label>
            <select class="admin-filter" name="delivery">
                <option value="">Tất cả đơn</option>
                <option value="now" @selected(($filters['delivery'] ?? '') === 'now')>Giao ngay</option>
                <option value="scheduled" @selected(($filters['delivery'] ?? '') === 'scheduled')>Giao sau</option>
                <option value="today" @selected(($filters['delivery'] ?? '') === 'today')>Giao hôm nay</option>
                <option value="upcoming" @selected(($filters['delivery'] ?? '') === 'upcoming')>Sắp đến giờ (2h)</option>
            </select>
        </div>
        <div class="col-xl-4 col-md-12">
            <label class="admin-kicker mb-2 d-block">Khoảng ngày</label>
            <div class="d-flex flex-wrap flex-md-nowrap gap-2 align-items-center">
                <input class="admin-input flex-grow-1" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
                <span class="text-secondary fw-semibold">đến</span>
                <input class="admin-input flex-grow-1" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
            </div>
        </div>
        <div class="col-xl-12 d-flex gap-2 justify-content-end">
            <button class="btn btn-primary text-nowrap px-4" type="submit">Áp dụng lọc</button>
            <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-primary text-nowrap px-4">Làm mới</a>
        </div>
    </section>
</form>

<section class="admin-card">
    <div class="table-responsive">
        <table class="table admin-table align-middle">
            <thead>
                <tr>
                    <th>Mã đơn</th>
                    <th>Ngày đặt</th>
                    <th>Thời gian nhận</th>
                    <th>Khách hàng</th>
                    @if(auth()->user()->isSuperAdmin())
                        <th>Chi nhánh</th>
                    @endif
                    <th>Thanh toán</th>
                    <th class="text-end">Tổng tiền</th>
                    <th class="text-center">Trạng thái</th>
                    <th class="text-center">Hành động</th>
                </tr>
            </thead>
            <tbody id="adminOrdersTableBody">
                @forelse($orders as $order)
                @php
                    $detailId = 'order-detail-'.$order->id;
                    $statusStepOptions = \App\Support\OrderStatus::stepwiseOptions((string) $order->status);
                    $nextStatus = \App\Support\OrderStatus::nextStatus((string) $order->status);
                @endphp
                <tr data-order-id="{{ $order->id }}">
                    <td class="fw-bold text-primary">#{{ $order->id }}</td>
                    <td class="text-secondary">{{ optional($order->created_at)->format('d/m/Y H:i') }}</td>
                    <td>
                        @if($order->delivery_type === 'scheduled' && ($order->scheduled_delivery_time || $order->scheduled_at))
                            <span class="badge bg-info-subtle text-info-emphasis"><i class="bi bi-calendar-check me-1"></i>Giao sau · {{ ($order->scheduled_delivery_time ?? $order->scheduled_at)->format('H:i · d/m/Y') }}</span>
                            @if($order->delivery_note)<small class="d-block text-secondary mt-1" title="{{ $order->delivery_note }}">{{ \Illuminate\Support\Str::limit($order->delivery_note, 42) }}</small>@endif
                        @else
                            <span class="text-secondary small"><i class="bi bi-lightning-charge me-1"></i>Giao ngay</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="admin-avatar" style="width:34px;height:34px;font-size:.8rem;">{{ mb_substr($order->customerName() ?: 'K', 0, 1) }}</span>
                            <span>
                                <span class="fw-bold d-block">{{ $order->customerName() ?: 'Khách hàng' }}</span>
                                <small class="text-secondary">{{ $order->customerEmail() ?: '' }}</small>
                            </span>
                        </div>
                    </td>
                    @if(auth()->user()->isSuperAdmin())
                        <td>
                            @if($order->branch)
                                <span class="badge bg-light text-dark">{{ $order->branch->name }}</span>
                            @else
                                <span class="text-secondary small">-</span>
                            @endif
                        </td>
                    @endif
                    <td>
                        @if(isset($order->payment_status))
                            @if($order->payment_status === 'paid')
                                <span class="badge bg-success">
                                    <i class="bi bi-check-circle me-1"></i>Đã thanh toán
                                </span>
                            @elseif($order->payment_status === 'failed')
                                <span class="badge bg-danger">
                                    <i class="bi bi-x-circle me-1"></i>Thất bại
                                </span>
                            @else
                                <span class="badge bg-warning text-dark">
                                    <i class="bi bi-clock me-1"></i>Chưa thanh toán
                                </span>
                            @endif
                        @else
                            @if($order->payment_method === 'cod')
                                <span class="badge bg-secondary">
                                    <i class="bi bi-cash me-1"></i>COD
                                </span>
                            @else
                                <span class="badge bg-warning text-dark">
                                    <i class="bi bi-clock me-1"></i>Chưa thanh toán
                                </span>
                            @endif
                        @endif
                    </td>
                    <td class="text-end fw-bold text-primary">{{ number_format($order->total_price ?? $order->total ?? 0, 0, ',', '.') }}đ</td>
                    <td class="text-center">
                        <form action="{{ route('admin.orders.updateStatus', $order->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="d-flex align-items-center gap-2 justify-content-center">
                                <select name="status"
                                        class="form-select form-select-sm"
                                        onchange="this.form.submit()"
                                        @disabled($nextStatus === null)>
                                    @foreach($statusStepOptions as $value => $label)
                                        <option value="{{ $value }}" @selected($order->status === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </form>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-link text-primary text-decoration-none fw-semibold p-0" data-toggle-order-detail="{{ $order->id }}">
                            Chi tiết
                        </button>
                    </td>
                </tr>
                <tr id="{{ $detailId }}" class="d-none order-detail-row">
                    <td colspan="9" class="border-0 pt-0">
                        <div class="order-detail-card p-4 shadow-sm">
                            <div class="row g-4">
                                <!-- Cột Trái: Sản phẩm -->
                                <div class="col-lg-7">
                                    <div class="mb-3">
                                        <div class="admin-kicker mb-3 text-secondary fw-bold" style="letter-spacing: 0.05em;">SẢN PHẨM TRONG ĐƠN HÀNG</div>
                                    </div>
                                    <div class="d-grid gap-3">
                                        @foreach($order->orderItems as $item)
                                            <div class="p-3 bg-white" style="border: 1px solid rgba(0,0,0,0.08); border-radius: 16px;">
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="flex-shrink-0">
                                                        <img src="{{ $item->product?->image_url }}" alt="{{ $item->product?->name ?? 'Sản phẩm' }}" style="width:80px;height:80px;object-fit:cover;border-radius:12px;background:#f8f9fa;">
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h4 class="fw-bold mb-1" style="font-size: 0.95rem; color: var(--a-ink);">{{ $item->product?->name ?? 'Sản phẩm đã xóa' }}</h4>
                                                        <div class="text-muted small mb-1">Size: {{ $item->productSize?->size?->name ?? 'Chưa chọn' }}</div>
                                                        <div class="text-muted small">Đá: {{ (int) $item->ice_level }}% • Đường: {{ (int) $item->sugar_level }}%</div>
                                                    </div>
                                                    <div class="text-end d-flex flex-column align-items-end gap-1">
                                                        <div class="fw-bold" style="font-size: 0.95rem; color: var(--a-ink);">{{ number_format((int) $item->getSubtotal(), 0, ',', '.') }}đ</div>
                                                        <div class="text-muted small">{{ number_format((int) $item->unit_price, 0, ',', '.') }}đ/sp</div>
                                                        <span class="badge px-2 py-1 text-dark" style="background-color: #f1f3f5; font-size: 0.75rem; border-radius: 6px; font-weight: 500;">Số lượng: {{ (int) $item->quantity }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- Cột Phải: Thông tin khách hàng & Tổng hợp đơn hàng -->
                                <div class="col-lg-5">
                                    <div class="mb-3">
                                        <div class="admin-kicker mb-3 text-secondary fw-bold" style="letter-spacing: 0.05em;">THÔNG TIN KHÁCH HÀNG</div>
                                    </div>
                                    
                                    <div class="mb-4 d-flex flex-column gap-2 text-secondary" style="font-size: 0.9rem;">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-telephone text-muted" style="font-size: 1.1rem;"></i>
                                            <span class="fw-semibold">{{ $order->customerPhone() ?: 'Chưa cập nhật' }}</span>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-envelope text-muted" style="font-size: 1.1rem;"></i>
                                            <span>{{ $order->customerEmail() ?: 'Chưa cập nhật' }}</span>
                                        </div>
                                        <div class="d-flex align-items-start gap-2">
                                            <i class="bi bi-geo-alt text-muted mt-1" style="font-size: 1.1rem;"></i>
                                            <span>{{ $order->getShippingAddress() }}</span>
                                        </div>
                                    </div>

                                    <!-- Thẻ tóm tắt thanh toán -->
                                    <div class="p-3 bg-white" style="border: 1px solid rgba(0,0,0,0.08); border-radius: 16px;">
                                        <div class="d-flex flex-column gap-2" style="font-size: 0.875rem;">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-muted">Tạm tính</span>
                                                <span class="fw-semibold text-dark">{{ number_format((int) ($order->subtotal ?? 0), 0, ',', '.') }}đ</span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-muted">Phí vận chuyển</span>
                                                <span class="fw-semibold text-dark">{{ number_format((int) ($order->shipping_fee ?? 0), 0, ',', '.') }}đ</span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-muted">Giảm giá</span>
                                                <span class="fw-semibold text-danger">-{{ number_format((int) ($order->discount ?? 0), 0, ',', '.') }}đ</span>
                                            </div>
                                            
                                            <div style="border-top: 1px dashed #e9ecef; margin: 0.75rem 0;"></div>
                                            
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <div class="text-muted" style="font-size: 0.75rem; letter-spacing: 0.05em; font-weight: 600;">TỔNG CỘNG</div>
                                                    <div class="fw-bold mt-1 text-primary" style="font-size: 1.5rem; line-height: 1;">{{ number_format((int) ($order->total_price ?? $order->total ?? 0), 0, ',', '.') }}đ</div>
                                                </div>
                                                <div class="text-end" style="font-size: 0.85rem;">
                                                    <div class="text-dark">Phương thức: <strong class="text-uppercase">{{ $order->payment_method }}</strong></div>
                                                    <div class="mt-1">
                                                        Trạng thái: <strong class="status-text-{{ \App\Support\OrderStatus::normalize((string) $order->status) }}">{{ strtoupper(\App\Support\OrderStatus::label((string) $order->status)) }}</strong>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Các nút hành động -->
                                            <div class="d-flex gap-2 align-items-stretch mt-3 w-100">
                                                @if($nextStatus !== null)
                                                    <form action="{{ route('admin.orders.updateStatus', $order->id) }}" method="POST" class="flex-grow-1 m-0">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="status" value="{{ $nextStatus }}">
                                                        <button type="submit" class="btn btn-primary w-100 h-100 d-flex align-items-center justify-content-center gap-2 px-3 py-2 text-center" style="background-color: #0b6b5f; border-color: #0b6b5f; border-radius: 12px; font-size: 0.875rem;">
                                                            <span>Chuyển bước tiếp theo</span>
                                                            <i class="bi bi-arrow-right"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                                @if(!in_array($order->status, [\App\Support\OrderStatus::COMPLETED, \App\Support\OrderStatus::CANCELLED], true))
                                                    <form action="{{ route('admin.orders.updateStatus', $order->id) }}" method="POST" class="m-0" onsubmit="return confirm('Hủy đơn hàng #{{ $order->id }}?');">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="status" value="cancelled">
                                                        <button type="submit" class="btn btn-outline-danger h-100 px-3 py-2" style="border-radius: 12px; border: 1.5px solid var(--a-danger); color: var(--a-danger); background: transparent; font-size: 0.875rem; white-space: nowrap;">
                                                            Hủy đơn
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
 <tr>
    <td colspan="9" class="text-center text-secondary py-5">
        <div class="fw-bold text-dark mb-1">Chưa có đơn hàng</div>
        <div>Các đơn mới sẽ xuất hiện tại đây.</div>
    </td>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 p-4 border-top" style="background: var(--admin-soft-2);">
        <p class="text-secondary mb-0" id="adminOrdersCount">Đang hiển thị {{ $orders->count() }} đơn hàng</p>
        <span class="badge badge-soft-primary d-none" id="adminOrdersLiveBadge">
            <i class="bi bi-broadcast me-1"></i>Đang theo dõi đơn mới
        </span>
        {{ $orders->links('pagination::bootstrap-5') }}
    </div>
</section>

@php
    $hasActiveOrderFilters = ($filters['q'] ?? '') !== ''
        || ($filters['status'] ?? '') !== ''
        || ($filters['payment_method'] ?? '') !== ''
        || ($filters['payment_status'] ?? '') !== ''
        || ($filters['date_from'] ?? '') !== ''
        || ($filters['date_to'] ?? '') !== ''
        || ($filters['delivery'] ?? '') !== '';
@endphp

<script>
    (function () {
        const csrfToken = @json(csrf_token());
        const recentOrdersUrl = @json(route('admin.orders.recent'));
        const hasActiveFilters = @json($hasActiveOrderFilters);
        const initialLatestId = @json($latestOrderId ?? 0);

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function paymentBadgeHtml(payload) {
            if (payload.payment_status === 'paid') {
                return '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Đã thanh toán</span>';
            }

            if (payload.payment_status === 'failed') {
                return '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Thất bại</span>';
            }

            if (payload.payment_method === 'cod') {
                return '<span class="badge bg-secondary"><i class="bi bi-cash me-1"></i>COD</span>';
            }

            return '<span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i>Chưa thanh toán</span>';
        }

        function nextStatusLabel(payload) {
            const labels = {
                pending: 'Chờ xử lý',
                in_progress: 'Đang thực hiện',
                shipper_accepted: 'Shipper đã nhận đơn',
                arrived: 'Đơn hàng đã đến',
                completed: 'Hoàn thành',
                cancelled: 'Đã hủy',
            };

            return labels[payload.next_status || ''] || 'Tiếp theo';
        }

        function statusStepHtml(payload) {
            const status = payload.status || 'pending';
            const updateUrl = payload.status_update_url || '#';
            const nextStatus = payload.next_status || '';
            const currentLabel = payload.status_label || 'Chờ xử lý';

            const optionsHtml = `
                <option value="${escapeHtml(status)}" selected>${escapeHtml(currentLabel)}</option>
                ${nextStatus ? `<option value="${escapeHtml(nextStatus)}">${escapeHtml(nextStatusLabel(payload))}</option>` : ''}
            `;

            return `
                <form action="${escapeHtml(updateUrl)}" method="POST" class="d-flex align-items-center gap-2 justify-content-center">
                    <input type="hidden" name="_token" value="${escapeHtml(csrfToken)}">
                    <input type="hidden" name="_method" value="PUT">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()" ${nextStatus ? '' : 'disabled'}>
                        ${optionsHtml}
                    </select>
                </form>
            `;
        }

        function detailRowHtml(payload) {
            const items = Array.isArray(payload.items) ? payload.items : [];
            const lines = items.map((item) => `
                <div class="p-3 bg-white" style="border: 1px solid rgba(0,0,0,0.08); border-radius: 16px;">
                    <div class="d-flex align-items-center gap-3">
                        <div class="flex-shrink-0">
                            <img src="${escapeHtml(item.image_url || '')}" alt="${escapeHtml(item.product_name || 'Sản phẩm')}" style="width:80px;height:80px;object-fit:cover;border-radius:12px;background:#f8f9fa;">
                        </div>
                        <div class="flex-grow-1">
                            <h4 class="fw-bold mb-1" style="font-size: 0.95rem; color: var(--a-ink);">${escapeHtml(item.product_name || 'Sản phẩm')}</h4>
                            <div class="text-muted small mb-1">Size: ${escapeHtml(item.size_name || 'Chưa chọn')}</div>
                            <div class="text-muted small">Đá: ${parseInt(item.ice_level || 0)}% • Đường: ${parseInt(item.sugar_level || 0)}%</div>
                        </div>
                        <div class="text-end d-flex flex-column align-items-end gap-1">
                            <div class="fw-bold" style="font-size: 0.95rem; color: var(--a-ink);">${escapeHtml(item.total_formatted || '')}</div>
                            <div class="text-muted small">${escapeHtml(item.unit_price_formatted || '')}/sp</div>
                            <span class="badge px-2 py-1 text-dark" style="background-color: #f1f3f5; font-size: 0.75rem; border-radius: 6px; font-weight: 500;">Số lượng: ${parseInt(item.quantity || 1)}</span>
                        </div>
                    </div>
                </div>
            `).join('');

            const statusKey = payload.status || 'pending';
            const statusLabel = (payload.status_label || 'Chờ xử lý').toUpperCase();

            return `
                <tr id="order-detail-${escapeHtml(payload.order_id)}" class="d-none order-detail-row">
                    <td colspan="9" class="border-0 pt-0">
                        <div class="order-detail-card p-4 shadow-sm">
                            <div class="row g-4">
                                <!-- Cột Trái: Sản phẩm -->
                                <div class="col-lg-7">
                                    <div class="mb-3">
                                        <div class="admin-kicker mb-3 text-secondary fw-bold" style="letter-spacing: 0.05em;">SẢN PHẨM TRONG ĐƠN HÀNG</div>
                                    </div>
                                    <div class="d-grid gap-3">
                                        ${lines || '<div class="text-secondary p-3 border rounded-4 text-center">Chưa có dữ liệu sản phẩm.</div>'}
                                    </div>
                                </div>
                                <!-- Cột Phải: Thông tin khách hàng & Tổng hợp đơn hàng -->
                                <div class="col-lg-5">
                                    <div class="mb-3">
                                        <div class="admin-kicker mb-3 text-secondary fw-bold" style="letter-spacing: 0.05em;">THÔNG TIN KHÁCH HÀNG</div>
                                    </div>
                                    <div class="mb-4 d-flex flex-column gap-2 text-secondary" style="font-size: 0.9rem;">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-telephone text-muted" style="font-size: 1.1rem;"></i>
                                            <span class="fw-semibold">${escapeHtml(payload.customer_phone || 'Chưa cập nhật')}</span>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-envelope text-muted" style="font-size: 1.1rem;"></i>
                                            <span>${escapeHtml(payload.customer_email || 'Chưa cập nhật')}</span>
                                        </div>
                                        <div class="d-flex align-items-start gap-2">
                                            <i class="bi bi-geo-alt text-muted mt-1" style="font-size: 1.1rem;"></i>
                                            <span>${escapeHtml(payload.shipping_address || 'Chưa cập nhật địa chỉ')}</span>
                                        </div>
                                    </div>

                                    <!-- Thẻ tóm tắt thanh toán -->
                                    <div class="p-3 bg-white" style="border: 1px solid rgba(0,0,0,0.08); border-radius: 16px;">
                                        <div class="d-flex flex-column gap-2" style="font-size: 0.875rem;">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-muted">Tạm tính</span>
                                                <span class="fw-semibold text-dark">${escapeHtml(payload.subtotal_formatted || '')}</span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-muted">Phí vận chuyển</span>
                                                <span class="fw-semibold text-dark">${escapeHtml(payload.shipping_fee_formatted || '')}</span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-muted">Giảm giá</span>
                                                <span class="fw-semibold text-danger">-${escapeHtml(payload.discount_formatted || '')}</span>
                                            </div>
                                            
                                            <div style="border-top: 1px dashed #e9ecef; margin: 0.75rem 0;"></div>
                                            
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <div class="text-muted" style="font-size: 0.75rem; letter-spacing: 0.05em; font-weight: 600;">TỔNG CỘNG</div>
                                                    <div class="fw-bold mt-1 text-primary" style="font-size: 1.5rem; line-height: 1;">${escapeHtml(payload.total_formatted || '')}</div>
                                                </div>
                                                <div class="text-end" style="font-size: 0.85rem;">
                                                    <div class="text-dark">Phương thức: <strong class="text-uppercase">${escapeHtml(payload.payment_method || 'cod')}</strong></div>
                                                    <div class="mt-1">
                                                        Trạng thái: <strong class="status-text-${escapeHtml(statusKey)}">${escapeHtml(statusLabel)}</strong>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Các nút hành động -->
                                            <div class="d-flex gap-2 align-items-stretch mt-3 w-100">
                                                ${payload.next_status ? `
                                                    <form action="${escapeHtml(payload.status_update_url || '#')}" method="POST" class="flex-grow-1 m-0">
                                                        <input type="hidden" name="_token" value="${escapeHtml(csrfToken)}">
                                                        <input type="hidden" name="_method" value="PUT">
                                                        <input type="hidden" name="status" value="${escapeHtml(payload.next_status)}">
                                                        <button type="submit" class="btn btn-primary w-100 h-100 d-flex align-items-center justify-content-center gap-2 px-3 py-2 text-center" style="background-color: #0b6b5f; border-color: #0b6b5f; border-radius: 12px; font-size: 0.875rem;">
                                                            <span>Chuyển bước tiếp theo</span>
                                                            <i class="bi bi-arrow-right"></i>
                                                        </button>
                                                    </form>
                                                ` : ''}
                                                ${payload.can_cancel ? `
                                                    <form action="${escapeHtml(payload.status_update_url || '#')}" method="POST" class="m-0" onsubmit="return confirm('Hủy đơn hàng #${escapeHtml(payload.order_id)}?');">
                                                        <input type="hidden" name="_token" value="${escapeHtml(csrfToken)}">
                                                        <input type="hidden" name="_method" value="PUT">
                                                        <input type="hidden" name="status" value="cancelled">
                                                        <button type="submit" class="btn btn-outline-danger h-100 px-3 py-2" style="border-radius: 12px; border: 1.5px solid var(--a-danger); color: var(--a-danger); background: transparent; font-size: 0.875rem; white-space: nowrap;">
                                                            Hủy đơn
                                                        </button>
                                                    </form>
                                                ` : ''}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            `;
        }

        function toggleDetailRow(orderId) {
            const detailRow = document.getElementById(`order-detail-${orderId}`);
            if (!detailRow) {
                return;
            }

            detailRow.classList.toggle('d-none');
        }

        function tableStatusSelectHtml(payload) {
            return statusStepHtml(payload);
        }

        window.prependAdminOrderRow = function (payload) {
            const tableBody = document.getElementById('adminOrdersTableBody');

            if (!tableBody || !payload.order_id) {
                return false;
            }

            if (tableBody.querySelector(`[data-order-id="${payload.order_id}"]`)) {
                return false;
            }

            const emptyRow = tableBody.querySelector('td[colspan]');
            if (emptyRow) {
                emptyRow.closest('tr')?.remove();
            }

            const row = document.createElement('tr');
            row.dataset.orderId = String(payload.order_id);
            row.style.background = 'rgba(13, 147, 115, 0.08)';
            row.innerHTML = `
                <td class="fw-bold text-primary">#${escapeHtml(payload.order_id)}</td>
                <td class="text-secondary">${escapeHtml(payload.created_at || 'Vừa xong')}</td>
                <td>${payload.delivery_type === 'scheduled' && (payload.scheduled_delivery_time || payload.scheduled_at) ? `<span class="badge bg-info-subtle text-info-emphasis"><i class="bi bi-calendar-check me-1"></i>Giao sau · ${escapeHtml(payload.scheduled_delivery_time || payload.scheduled_at)}</span>${payload.delivery_note ? `<small class="d-block text-secondary mt-1" title="${escapeHtml(payload.delivery_note)}">${escapeHtml(String(payload.delivery_note).slice(0, 42))}</small>` : ''}` : '<span class="text-secondary small"><i class="bi bi-lightning-charge me-1"></i>Giao ngay</span>'}</td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <span class="admin-avatar" style="width:34px;height:34px;font-size:.8rem;">${escapeHtml((payload.customer_name || 'K').charAt(0))}</span>
                        <span>
                            <span class="fw-bold d-block">${escapeHtml(payload.customer_name || 'Khách hàng')}</span>
                            <small class="text-secondary">${escapeHtml(payload.customer_email || '')}</small>
                        </span>
                    </div>
                </td>
                <td>
                    <div class="fw-semibold">${escapeHtml(payload.customer_phone || 'Chưa cập nhật')}</div>
                </td>
                <td>${paymentBadgeHtml(payload)}</td>
                <td class="text-end fw-bold text-primary">${escapeHtml(payload.total_formatted || '')}</td>
                <td class="text-center">${tableStatusSelectHtml(payload)}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-link text-primary text-decoration-none fw-semibold p-0" data-toggle-order-detail="${escapeHtml(payload.order_id)}">Chi tiết</button>
                </td>
            `;

            tableBody.prepend(row);
            row.insertAdjacentHTML('afterend', detailRowHtml({
                order_id: payload.order_id,
                customer_name: payload.customer_name,
                customer_email: payload.customer_email,
                customer_phone: payload.customer_phone || '',
                payment_status_label: payload.payment_status_label || '',
                payment_method_label: payload.payment_method_label || '',
                note: payload.note || '',
                status: payload.status || 'pending',
                status_label: payload.status_label || '',
                total_formatted: payload.total_formatted || '',
                subtotal_formatted: payload.subtotal_formatted || '',
                shipping_fee_formatted: payload.shipping_fee_formatted || '',
                discount_formatted: payload.discount_formatted || '',
                next_status: payload.next_status || '',
                status_update_url: payload.status_update_url || '#',
                can_cancel: payload.can_cancel ?? true,
                shipping_address: payload.shipping_address || '',
                items: payload.items || [],
            }));

            const countElement = document.getElementById('adminOrdersCount');
            if (countElement) {
                const match = countElement.textContent.match(/(\d+)/);
                const currentCount = match ? parseInt(match[1], 10) : 0;
                countElement.textContent = `Đang hiển thị ${currentCount + 1} đơn hàng`;
            }

            window.setTimeout(() => {
                row.style.transition = 'background-color .8s ease';
                row.style.background = '';
            }, 2500);

            return true;
        };

        document.addEventListener('click', function (event) {
            const toggleButton = event.target.closest('[data-toggle-order-detail]');
            if (!toggleButton) {
                return;
            }

            toggleDetailRow(toggleButton.dataset.toggleOrderDetail);
        });

        function handleNewOrders(orders) {
            let added = 0;

            orders.slice().reverse().forEach((payload) => {
                if (window.prependAdminOrderRow(payload)) {
                    added += 1;
                }
            });

            if (added > 0 && typeof window.showRealtimeToast === 'function') {
                const message = added === 1
                    ? (orders[0].message || 'Có đơn hàng mới')
                    : `Có ${added} đơn hàng mới`;
                window.showRealtimeToast(message, 'success');
            }
        }

        document.addEventListener('order:created', function (event) {
            handleNewOrders([event.detail || {}]);
        });

        if (!hasActiveFilters) {
            let lastOrderId = initialLatestId;
            const liveBadge = document.getElementById('adminOrdersLiveBadge');
            liveBadge?.classList.remove('d-none');

            const pollRecentOrders = async function () {
                try {
                    const response = await fetch(`${recentOrdersUrl}?after_id=${lastOrderId}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });

                    if (!response.ok) {
                        return;
                    }

                    const data = await response.json();
                    const orders = Array.isArray(data.orders) ? data.orders : [];

                    if (orders.length > 0) {
                        handleNewOrders(orders);
                        lastOrderId = Math.max(lastOrderId, ...orders.map((order) => order.order_id));
                    } else if (data.latest_id) {
                        lastOrderId = Math.max(lastOrderId, data.latest_id);
                    }
                } catch (error) {
                    console.warn('Không thể tải đơn hàng mới.', error);
                }
            };

            window.setInterval(pollRecentOrders, 5000);
        }
    })();
</script>
@endsection