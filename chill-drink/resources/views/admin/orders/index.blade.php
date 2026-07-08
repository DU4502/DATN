@extends('layouts.admin')

@section('page-title', 'Đơn hàng')

@section('content')
<form method="GET" action="{{ route('admin.orders.index') }}">
    <section class="row g-3 align-items-end mb-4">
        <div class="col-md-2">
            <label class="admin-kicker mb-2 d-block">Trạng thái đơn</label>
            <select class="admin-filter" name="status">
                @foreach($statusOptions as $value => $label)
                <option value="{{ $value }}" @selected(($filters['status'] ?? '' )===$value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="admin-kicker mb-2 d-block">Tìm kiếm</label>
            <input class="admin-input" type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Mã đơn, tên hoặc email">
        </div>
        <div class="col-md-3">
            <label class="admin-kicker mb-2 d-block">Khoảng ngày</label>
            <div class="d-flex gap-2 align-items-center">
                <input class="admin-input" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
                <span class="text-secondary">đến</span>
                <input class="admin-input" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
            </div>
        </div>
        <div class="col-md-3">
            <label class="admin-kicker mb-2 d-block">Loại giao</label>
            <select class="admin-filter" name="delivery">
                <option value="">Tất cả đơn</option><option value="now" @selected(($filters['delivery'] ?? '') === 'now')>Giao ngay</option><option value="scheduled" @selected(($filters['delivery'] ?? '') === 'scheduled')>Giao sau</option><option value="today" @selected(($filters['delivery'] ?? '') === 'today')>Giao hôm nay</option><option value="upcoming" @selected(($filters['delivery'] ?? '') === 'upcoming')>Sắp đến giờ (2h)</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-primary flex-grow-1" type="submit">Áp dụng lọc</button>
            <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-primary">Làm mới</a>
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
                </tr>
            </thead>
            <tbody id="adminOrdersTableBody">
                @forelse($orders as $order)
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
                                @if($order->isGuest())
                                    <span class="small text-secondary">{{ $order->guest_phone }} · {{ $order->guest_email }}</span>
                                @else
                                    <small class="text-secondary">{{ $order->user->email ?? '' }}</small>
                                @endif
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
                        @php
                            $statusOptionsForOrder = \App\Support\OrderStatus::selectableOptions((string) $order->status);
                        @endphp
                        <form action="{{ route('admin.orders.updateStatus', $order->id) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <select name="status"
                                    class="form-select form-select-sm"
                                    onchange="this.form.submit()"
                                    @disabled(count($statusOptionsForOrder) <= 1)>
                                @foreach($statusOptionsForOrder as $value => $label)
                                    <option value="{{ $value }}" @selected($order->status === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </form>
                    </td>
                    
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-secondary py-5">
                        <div class="fw-bold text-dark mb-1">Chưa có đơn hàng</div>
                        <div>Các đơn mới sẽ xuất hiện tại đây.</div>
                    </td>
                </tr>
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
        || ($filters['date_from'] ?? '') !== ''
        || ($filters['date_to'] ?? '') !== '';
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

        const orderStatusLabels = @json(\App\Support\OrderStatus::labels());

        function statusSelectHtml(payload) {
            const status = payload.status || 'pending';
            const updateUrl = payload.status_update_url || '#';
            const options = payload.status_options || { pending: orderStatusLabels.pending || 'Chờ xử lý' };
            const optionEntries = Object.entries(options);
            const disabled = optionEntries.length <= 1 ? 'disabled' : '';

            const optionsHtml = optionEntries.map(([value, label]) => {
                const selected = value === status ? 'selected' : '';
                return `<option value="${escapeHtml(value)}" ${selected}>${escapeHtml(label)}</option>`;
            }).join('');

            return `
                <form action="${escapeHtml(updateUrl)}" method="POST">
                    <input type="hidden" name="_token" value="${escapeHtml(csrfToken)}">
                    <input type="hidden" name="_method" value="PUT">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()" ${disabled}>
                        ${optionsHtml}
                    </select>
                </form>
            `;
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
                <td>${payload.scheduled_at ? `<span class="badge bg-info-subtle text-info-emphasis"><i class="bi bi-calendar-check me-1"></i>${escapeHtml(payload.scheduled_at)}</span>` : '<span class="text-secondary small">Nhận sớm nhất</span>'}</td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <span class="admin-avatar" style="width:34px;height:34px;font-size:.8rem;">${escapeHtml((payload.customer_name || 'K').charAt(0))}</span>
                        <span>
                            <span class="fw-bold d-block">${escapeHtml(payload.customer_name || 'Khách hàng')}</span>
                            <small class="text-secondary">${escapeHtml(payload.customer_email || '')}</small>
                        </span>
                    </div>
                </td>
                <td>${paymentBadgeHtml(payload)}</td>
                <td class="text-end fw-bold text-primary">${escapeHtml(payload.total_formatted || '')}</td>
                <td class="text-center">${statusSelectHtml(payload)}</td>
            `;

            tableBody.prepend(row);

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
