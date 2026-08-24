@extends(auth()->user()?->preferredAdminLayout() ?? 'layouts.admin')

@section('page-title', 'Đơn hàng')

@section('content')
@php
    $isSuperAdmin = auth()->user()?->isSuperAdmin() ?? false;
    $isSuperAdminWorkspace = $isSuperAdmin && ! auth()->user()->isViewingAdminWorkspace();
    $orderIndexRouteName = $isSuperAdminWorkspace
        ? 'admin.super-admin.manage.orders.index'
        : 'admin.orders.index';
    $orderRecentRouteName = $isSuperAdminWorkspace
        ? 'admin.super-admin.manage.orders.recent'
        : 'admin.orders.recent';
    $orderUpdateRouteName = $isSuperAdminWorkspace
        ? 'admin.super-admin.manage.orders.updateStatus'
        : 'admin.orders.updateStatus';
@endphp
<style>
    .order-detail-row > td {
        background: #f8fbfa;
    }

    .order-detail-card {
        border: 1px solid rgba(17, 24, 39, 0.08);
        border-radius: 20px;
        background: #fff;
        overflow: hidden;
    }

    .order-detail-card-inner {
        padding: 1rem;
    }

    .order-detail-section {
        padding: 0.9rem 1rem;
        border: 1px solid rgba(17, 24, 39, 0.08);
        border-radius: 16px;
        background: #fff;
    }

    .order-detail-section-title {
        margin-bottom: 0.7rem;
        color: #6b7280;
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.05em;
    }

    .order-detail-item {
        padding: 0.85rem;
        border: 1px solid rgba(0, 0, 0, 0.08);
        border-radius: 14px;
        background: #fff;
    }

    .order-detail-item-thumb {
        width: 64px;
        height: 64px;
        object-fit: cover;
        border-radius: 12px;
        background: #f8f9fa;
        flex-shrink: 0;
    }

    .order-review-list {
        display: grid;
        gap: 0.65rem;
    }

    .order-review-item {
        padding: 0.75rem 0.85rem;
        border: 1px solid rgba(17, 24, 39, 0.08);
        border-radius: 14px;
        background: linear-gradient(180deg, #fafffd 0%, #ffffff 100%);
    }

    .order-review-stars {
        display: inline-flex;
        align-items: center;
        gap: 2px;
        color: #f59e0b;
        font-size: 0.78rem;
        white-space: nowrap;
    }

    .order-detail-summary {
        min-width: 220px;
    }

    @media (max-width: 991.98px) {
        .order-detail-card {
            padding: 0.85rem !important;
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

        .order-detail-card-inner {
            padding: 0.85rem;
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

    /* Custom order status texts matching new flow */
    .status-text-pending { color: #d97706 !important; }
    .status-text-confirmed { color: #0891b2 !important; }
    .status-text-preparing { color: #0284c7 !important; }
    .status-text-ready_for_delivery { color: #06b6d4 !important; }
    .status-text-delivering { color: #7c3aed !important; }
    .status-text-delivered { color: #14b8a6 !important; }
    .status-text-ready_for_pickup { color: #06b6d4 !important; }
    .status-text-completed { color: #16a34a !important; }
    .status-text-cancelled { color: #dc2626 !important; }

    .order-status-spinner {
        width: 1rem;
        height: 1rem;
        flex: 0 0 auto;
    }
</style>

<div class="mb-4">
    <h2 class="h3 fw-bold text-dark mb-1">Quản lý đơn hàng</h2>
    <p class="text-secondary mb-0">Xem và quản lý tất cả đơn hàng</p>
</div>

<div class="d-flex flex-wrap gap-2 mb-4 pb-4 border-bottom">
    @php
        $currentStatus = $filters['status'] ?? '';
    @endphp
    
    <a href="{{ route($orderIndexRouteName, array_merge(request()->query(), ['status' => ''])) }}" 
       class="status-pill status-pill-all {{ $currentStatus === '' ? 'active' : '' }}">
        Tất cả
    </a>
    
    @foreach(\App\Support\OrderStatus::adminLabels() as $value => $label)
        <a href="{{ route($orderIndexRouteName, array_merge(request()->query(), ['status' => $value])) }}" 
           class="status-pill status-pill-{{ $value }} {{ $currentStatus === $value ? 'active' : '' }}">
            {{ $label }}
        </a>
    @endforeach
</div>

<form method="GET" action="{{ route($orderIndexRouteName) }}">
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
            <a href="{{ route($orderIndexRouteName) }}" class="btn btn-outline-primary text-nowrap px-4">Làm mới</a>
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
                    $fulfillmentType = $order->fulfillment_type ?? 'delivery';
                    $statusStepOptions = \App\Support\OrderStatus::orderManagementOptions((string) $order->status, $fulfillmentType);
                    if (! in_array(\App\Support\OrderStatus::normalize((string) $order->status), [\App\Support\OrderStatus::PENDING, \App\Support\OrderStatus::CANCELLED], true)) {
                        unset($statusStepOptions[\App\Support\OrderStatus::CANCELLED]);
                    }
                    $nextStatus = \App\Support\OrderStatus::orderManagementNextStatus((string) $order->status, $fulfillmentType);
                @endphp
                <tr data-order-id="{{ $order->id }}">
                    <td class="fw-bold text-primary">{{ $order->displayCode() }}</td>
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
                        <form action="{{ route($orderUpdateRouteName, $order->id) }}" method="POST" data-order-status-form data-order-id="{{ $order->id }}">
                            @csrf
                            @method('PUT')
                            <div class="d-flex align-items-center gap-2 justify-content-center">
                                <select name="status"
                                        class="form-select form-select-sm"
                                        data-order-status-select
                                        data-current-status="{{ \App\Support\OrderStatus::normalize((string) $order->status) }}"
                                        @disabled(count($statusStepOptions) <= 1)>
                                    @foreach($statusStepOptions as $value => $label)
                                        <option value="{{ $value }}" @selected(\App\Support\OrderStatus::normalize((string) $order->status) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <span class="spinner-border spinner-border-sm text-primary d-none order-status-spinner" role="status" aria-label="Đang cập nhật" data-order-status-loading></span>
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
                        @php
                            $orderReviews = collect($order->reviews ?? []);
                            $shipmentIncident = $shipmentIncidents[(int) $order->id] ?? null;
                            $incidentResolveUrl = auth()->user()->isSuperAdmin() && !auth()->user()->isViewingAdminWorkspace()
                                ? route('admin.super-admin.manage.orders.shipper-incident.resolve', $order)
                                : route('admin.orders.shipper-incident.resolve', $order);
                        @endphp
                        <div class="order-detail-card p-3 p-lg-4 shadow-sm">
                            <div class="row g-3">
                                <!-- Cột Trái: Sản phẩm -->
                                <div class="col-lg-7">
                                    <div class="mb-3">
                                        <div class="admin-kicker mb-3 text-secondary fw-bold" style="letter-spacing: 0.05em;">SẢN PHẨM TRONG ĐƠN HÀNG</div>
                                    </div>
                                    <div class="d-grid gap-3">
                                        @foreach($order->orderItems as $item)
                                            <div class="order-detail-item">
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="flex-shrink-0">
                                                        <img src="{{ $item->product?->image_url }}" alt="{{ $item->product?->name ?? 'Sản phẩm' }}" class="order-detail-item-thumb">
                                                    </div>
                                                    <div class="flex-grow-1 min-w-0">
                                                        <h4 class="fw-bold mb-1 text-truncate" style="font-size: 0.92rem; color: var(--a-ink);">{{ $item->product?->name ?? 'Sản phẩm đã xóa' }}</h4>
                                                        <div class="text-muted small mb-1 text-truncate">Kích cỡ: {{ $item->productSize?->size?->name ?? 'Chưa chọn' }}</div>
                                                        <div class="text-muted small text-truncate">Đá: {{ (int) $item->ice_level }}% • Đường: {{ (int) $item->sugar_level }}%</div>
                                                    </div>
                                                    <div class="text-end d-flex flex-column align-items-end gap-1">
                                                        <div class="fw-bold" style="font-size: 0.92rem; color: var(--a-ink);">{{ number_format((int) $item->getSubtotal(), 0, ',', '.') }}đ</div>
                                                        <div class="text-muted small">{{ number_format((int) $item->unit_price, 0, ',', '.') }}đ/sp</div>
                                                        <span class="badge px-2 py-1 text-dark" style="background-color: #f1f3f5; font-size: 0.72rem; border-radius: 6px; font-weight: 500;">SL {{ (int) $item->quantity }}</span>
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
                                    
                                    <div class="mb-3 d-flex flex-column gap-2 text-secondary" style="font-size: 0.88rem;">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-telephone text-muted" style="font-size: 1rem;"></i>
                                            <span class="fw-semibold">{{ $order->customerPhone() ?: 'Chưa cập nhật' }}</span>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-envelope text-muted" style="font-size: 1rem;"></i>
                                            <span>{{ $order->customerEmail() ?: 'Chưa cập nhật' }}</span>
                                        </div>
                                        <div class="d-flex align-items-start gap-2">
                                            <i class="bi bi-geo-alt text-muted mt-1" style="font-size: 1rem;"></i>
                                            <span>{{ $order->getShippingAddress() }}</span>
                                        </div>
                                    </div>

                                    @if(($order->fulfillment_type ?? 'delivery') === 'delivery')
                                        <div class="mb-3">
                                            <div class="admin-kicker mb-2 text-secondary fw-bold" style="letter-spacing:.05em;">NGƯỜI GIAO HÀNG</div>
                                            @if($order->shipper)
                                                <div class="p-3 bg-light border rounded-4 d-flex align-items-start gap-3">
                                                    <div class="d-flex align-items-center justify-content-center flex-shrink-0 bg-white text-primary border" style="width:42px;height:42px;border-radius:13px;">
                                                        <i class="bi bi-bicycle"></i>
                                                    </div>
                                                    <div class="small flex-grow-1">
                                                        <div class="fw-bold text-dark">{{ $order->shipper->user?->name ?: $order->shipper->code ?: 'Shipper' }}</div>
                                                        <div class="text-secondary mt-1">Mã: <strong>{{ $order->shipper->code ?: '-' }}</strong> · {{ $order->shipper->phone ?: $order->shipper->user?->phone ?: 'Chưa có SĐT' }}</div>
                                                        <div class="text-secondary mt-1">{{ $order->shipper->vehicle_type ?: 'Chưa cập nhật xe' }} @if($order->shipper->license_plate)· <strong>{{ $order->shipper->license_plate }}</strong>@endif</div>
                                                    </div>
                                                    <span class="badge bg-warning text-dark">{{ strtoupper($order->shipper->status ?: 'assigned') }}</span>
                                                </div>
                                            @else
                                                <div class="small text-secondary p-3 bg-light border rounded-4">Chưa có shipper được gán.</div>
                                            @endif

                                            @if(\App\Support\OrderStatus::normalize((string) $order->status) === \App\Support\OrderStatus::DELIVERED && $order->delivered_at)
                                                <div class="alert alert-info py-2 px-3 mt-2 mb-0 small">
                                                    <i class="bi bi-hourglass-split me-1"></i>
                                                    Đang chờ khách xác nhận. Nếu khách không thao tác, hệ thống tự chuyển <strong>Hoàn thành</strong> lúc
                                                    <strong>{{ $order->delivered_at->copy()->addMinutes(\App\Services\DeliveredOrderCompletionService::AUTO_COMPLETE_AFTER_MINUTES)->format('H:i · d/m/Y') }}</strong>.
                                                </div>
                                            @endif
                                        </div>
                                    @endif

                                    @if($orderReviews->isNotEmpty())
                                        <div class="mb-3">
                                            <div class="admin-kicker mb-2 text-secondary fw-bold" style="letter-spacing:.05em;">ĐÁNH GIÁ</div>
                                            <div class="order-review-list">
                                                @foreach($orderReviews as $review)
                                                    <div class="order-review-item">
                                                        <div class="d-flex align-items-start justify-content-between gap-2">
                                                            <div class="d-flex align-items-center gap-2 min-w-0">
                                                                @if($review->product?->image_url)
                                                                    <img src="{{ $review->product?->image_url }}" alt="{{ $review->product?->name ?? 'Sản phẩm' }}" style="width:34px;height:34px;object-fit:cover;border-radius:10px;background:#f3f4f6;flex-shrink:0;">
                                                                @else
                                                                    <span class="admin-avatar" style="width:34px;height:34px;font-size:.72rem;flex-shrink:0;">{{ mb_substr($review->product?->name ?? 'R', 0, 1) }}</span>
                                                                @endif
                                                                <div class="min-w-0">
                                                                    <div class="fw-semibold text-dark text-truncate" style="font-size: 0.88rem;">{{ $review->product?->name ?? 'Sản phẩm đã xóa' }}</div>
                                                                    <div class="text-secondary small text-truncate">{{ $review->user?->name ?? 'Khách hàng' }} · {{ $review->created_at?->format('d/m/Y H:i') }}</div>
                                                                </div>
                                                            </div>
                                                            <span class="admin-rating">
                                                                <i class="bi bi-star-fill"></i>
                                                                {{ (int) $review->rating }}/5
                                                            </span>
                                                        </div>
                                                        @if($review->comment)
                                                            <div class="text-secondary small mt-2">{{ $review->comment }}</div>
                                                        @else
                                                            <div class="text-secondary small fst-italic mt-2">Không có nhận xét.</div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    @if($order->status === \App\Support\OrderStatus::CANCELLED && $order->cancellation_reason)
                                        <!-- Lý do hủy đơn -->
                                        <div class="alert alert-danger d-flex align-items-start gap-2 mb-3" style="border-radius: 12px; border-left: 4px solid #dc2626;">
                                            <i class="bi bi-exclamation-triangle-fill" style="font-size: 1.1rem;"></i>
                                            <div class="flex-grow-1">
                                                <div class="fw-bold mb-1">Lý do hủy đơn</div>
                                                <div class="small">{{ $order->cancellation_reason }}</div>
                                            </div>
                                        </div>
                                    @endif

                                    @if($shipmentIncident)
                                        <div class="alert alert-warning border-0 mb-3" style="border-radius:14px;box-shadow:0 8px 22px rgba(245,158,11,.12);">
                                            <div class="d-flex align-items-start gap-2">
                                                <i class="bi bi-exclamation-triangle-fill mt-1" style="font-size:1rem;"></i>
                                                <div class="flex-grow-1 min-w-0">
                                                    <div class="fw-bold">{{ ($shipmentIncident['incident_type'] ?? 'driver_issue') === 'customer_cancel' ? 'Khách xin hủy đơn cần duyệt' : 'Sự cố tài xế cần xử lý' }}</div>
                                                    <div class="small mt-1"><strong>{{ $shipmentIncident['shipper_name'] }}</strong> · {{ $shipmentIncident['description'] }}</div>
                                                    @if(!empty($shipmentIncident['reported_at_label']))
                                                        <div class="small text-secondary mt-1">Báo lúc {{ $shipmentIncident['reported_at_label'] }}</div>
                                                    @endif
                                                    <div class="small mt-2">{{ ($shipmentIncident['incident_type'] ?? 'driver_issue') === 'customer_cancel' ? 'Yêu cầu hủy chỉ xử lý nội bộ; khách không nhận thông báo về thao tác quản lý này.' : 'Đơn không bị hủy. Nếu đổi người, hệ thống tự tìm shipper rảnh gần điểm tiếp quản.' }}</div>
                                                    <div class="d-flex flex-wrap gap-2 mt-3">
                                                        @if(($shipmentIncident['incident_type'] ?? 'driver_issue') === 'customer_cancel')
                                                            <form action="{{ $incidentResolveUrl }}" method="POST" class="m-0">
                                                                @csrf
                                                                <input type="hidden" name="action" value="keep">
                                                                <button class="btn btn-sm btn-outline-success" type="submit">Tiếp tục giao</button>
                                                            </form>
                                                            <form action="{{ $incidentResolveUrl }}" method="POST" class="m-0" onsubmit="return confirm('Duyệt hủy đơn theo yêu cầu nội bộ này?');">
                                                                @csrf
                                                                <input type="hidden" name="action" value="cancel">
                                                                <button class="btn btn-sm btn-danger" type="submit">Duyệt hủy đơn</button>
                                                            </form>
                                                        @else
                                                            <form action="{{ $incidentResolveUrl }}" method="POST" class="m-0">
                                                                @csrf
                                                                <input type="hidden" name="action" value="keep">
                                                                <button class="btn btn-sm btn-outline-success" type="submit">Giữ shipper</button>
                                                            </form>
                                                            <form action="{{ $incidentResolveUrl }}" method="POST" class="m-0" onsubmit="return confirm('Xác nhận shipper hiện tại không thể tiếp tục và để hệ thống tự tìm người thay thế gần nhất?');">
                                                                @csrf
                                                                <input type="hidden" name="action" value="reassign">
                                                                <button class="btn btn-sm btn-warning" type="submit">Đổi shipper</button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Thẻ tóm tắt thanh toán -->
                                    <div class="order-detail-section order-detail-summary">
                                        <div class="d-flex flex-column gap-2" style="font-size: 0.84rem;">
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
                                            
                                            <div style="border-top: 1px dashed #e9ecef; margin: 0.5rem 0;"></div>
                                            
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <div class="text-muted" style="font-size: 0.68rem; letter-spacing: 0.05em; font-weight: 700;">TỔNG CỘNG</div>
                                                    <div class="fw-bold mt-1 text-primary" style="font-size: 1.35rem; line-height: 1;">{{ number_format((int) ($order->total_price ?? $order->total ?? 0), 0, ',', '.') }}đ</div>
                                                </div>
                                                <div class="text-end" style="font-size: 0.8rem;">
                                                    <div class="text-dark">Phương thức: <strong class="text-uppercase">{{ $order->payment_method }}</strong></div>
                                                    <div class="mt-1">
                                                        Trạng thái: <strong class="status-text-{{ \App\Support\OrderStatus::normalize((string) $order->status) }}">{{ strtoupper(\App\Support\OrderStatus::label((string) $order->status)) }}</strong>
                                                    </div>
                                                    @if($order->payment_method === 'cod' && $order->codReceivable)
                                                        <div class="mt-2">
                                                            @if($order->codReceivable->settlement_id)
                                                                <span class="badge bg-success"><i class="bi bi-cash-coin me-1"></i>COD đã đối soát công ty</span>
                                                            @else
                                                                <span class="badge bg-warning text-dark"><i class="bi bi-wallet2 me-1"></i>Shipper đang giữ {{ number_format((int)$order->codReceivable->amount,0,',','.') }}đ COD</span>
                                                            @endif
                                                        </div>
                                                    @endif
                                                    @if($order->status_changed_at)
                                                        @php
                                                            $changedByUser = $order->status_changed_by ? \App\Models\User::find($order->status_changed_by) : null;
                                                        @endphp
                                                                <div class="mt-1 text-muted" style="font-size:0.72rem;">
                                                                    <i class="bi bi-clock-history me-1"></i>{{ $order->status_changed_at->format('H:i · d/m/Y') }}
                                                                    @if($changedByUser)
                                                                        bởi <strong>{{ $changedByUser->name }}</strong>
                                                                    @endif
                                                                </div>
                                                    @endif

                                                    <div class="d-flex gap-2 align-items-stretch mt-3">
                                                        @if($nextStatus !== null)
                                                            <form action="{{ route($orderUpdateRouteName, $order->id) }}" method="POST" class="flex-grow-1 m-0">
                                                                @csrf
                                                                @method('PUT')
                                                                <input type="hidden" name="status" value="{{ $nextStatus }}">
                                                                <button type="submit" class="btn btn-primary w-100 px-3 py-2 text-center" style="background-color: #0b6b5f; border-color: #0b6b5f; border-radius: 12px; font-size: 0.85rem;">
                                                                    Chuyển bước tiếp theo
                                                                </button>
                                                            </form>
                                                        @endif
                                                        @if(\App\Support\OrderStatus::normalize((string) $order->status) === \App\Support\OrderStatus::PENDING)
                                                            <button type="button" class="btn btn-outline-danger px-3 py-2" style="border-radius: 12px; border: 1.5px solid var(--a-danger); color: var(--a-danger); background: transparent; font-size: 0.85rem; white-space: nowrap;" data-bs-toggle="modal" data-bs-target="#cancelOrderModal" data-order-id="{{ $order->id }}">
                                                                Hủy đơn
                                                            </button>
                                                        @endif
                                                    </div>
                                                </div>
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
        const recentOrdersUrl = @json(route($orderRecentRouteName));
        const statusUpdateUrlTemplate = @json(route($orderUpdateRouteName, ['id' => '__ORDER_ID__']));
        const hasActiveFilters = @json($hasActiveOrderFilters);
        const initialLatestId = @json($latestOrderId ?? 0);
        const initialLatestUpdatedAt = @json($latestOrderUpdatedAt ?? null);
        const adminBranchId = @json(auth()->user()?->branch_id);
        window.isSuperAdmin = @json($isSuperAdmin);
        window.isSuperAdminWorkspace = @json($isSuperAdminWorkspace);

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        const pendingStatusUpdates = new Set();

        function statusUpdateUrl(orderId) {
            return statusUpdateUrlTemplate.replace('__ORDER_ID__', encodeURIComponent(String(orderId)));
        }

        function showOrderStatusToast(message, type) {
            if (typeof window.showRealtimeToast === 'function') {
                window.showRealtimeToast(message, type === 'error' ? 'warning' : type);
                return;
            }

            const alert = document.createElement('div');
            alert.className = `alert alert-${type === 'success' ? 'success' : 'danger'} shadow-sm`;
            alert.style.cssText = 'position:fixed;top:80px;right:20px;z-index:10001;max-width:360px;border-radius:12px;';
            alert.textContent = message;
            document.body.appendChild(alert);
            window.setTimeout(() => alert.remove(), 4000);
        }

        function responseErrorMessage(payload, fallback) {
            const validationMessage = payload?.errors
                ? Object.values(payload.errors).flat().find(Boolean)
                : null;

            return validationMessage || payload?.message || fallback;
        }

        function updateOrderStatusRow(orderRow, select, payload) {
            const status = String(payload.status || '');
            const statusOptions = payload.status_options && Object.keys(payload.status_options).length
                ? payload.status_options
                : { [status]: payload.status_label || status };

            select.replaceChildren(...Object.entries(statusOptions).map(([value, label]) => {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = label;
                option.selected = value === status;
                return option;
            }));
            select.dataset.currentStatus = status;
            select.disabled = payload.can_update === false;
            orderRow.dataset.orderStatus = status;
            if (payload.updated_at) orderRow.dataset.orderUpdatedAt = payload.updated_at;

            const detailStatus = document
                .getElementById(`order-detail-${payload.id}`)
                ?.querySelector('[class*="status-text-"]');
            if (detailStatus) {
                detailStatus.className = payload.status_class || `status-text-${status}`;
                detailStatus.textContent = String(payload.status_label || status).toUpperCase();
            }

            orderRow.style.backgroundColor = 'rgba(13, 147, 115, 0.1)';
            window.setTimeout(() => {
                orderRow.style.backgroundColor = '';
            }, 1500);
        }

        function normalizeRealtimeOrderPayload(payload) {
            if (!payload || typeof payload !== 'object') return null;
            const orderId = payload.order_id ?? payload.id;
            if (!orderId) return null;

            return {
                ...payload,
                id: payload.id ?? orderId,
                order_id: orderId,
                status: String(payload.status || ''),
                status_label: payload.status_label || payload.status || '',
                status_class: payload.status_class || (payload.status ? `status-text-${payload.status}` : ''),
                status_options: payload.status_options || null,
                status_update_url: payload.status_update_url || statusUpdateUrl(orderId),
                updated_at: payload.updated_at || payload.generated_at || new Date().toISOString(),
            };
        }

        function applyRealtimeOrderPayload(rawPayload, options = {}) {
            const payload = normalizeRealtimeOrderPayload(rawPayload);
            if (!payload) return false;

            const orderRow = document.querySelector(`tr[data-order-id="${payload.order_id}"]`);
            if (!orderRow) {
                if (!hasActiveFilters && typeof window.prependAdminOrderRow === 'function') {
                    return window.prependAdminOrderRow(payload);
                }
                return false;
            }

            const statusSelect = orderRow.querySelector('[data-order-status-select]');
            if (statusSelect && payload.status) {
                if (payload.status_options) {
                    updateOrderStatusRow(orderRow, statusSelect, {
                        id: payload.order_id,
                        status: payload.status,
                        status_label: payload.status_label,
                        status_class: payload.status_class,
                        status_options: payload.status_options,
                        next_status: payload.next_status,
                        can_update: payload.can_update ?? Object.keys(payload.status_options).length > 1,
                        updated_at: payload.updated_at,
                    });
                } else {
                    const existingOption = [...statusSelect.options].find((option) => option.value === payload.status);
                    if (existingOption) statusSelect.value = payload.status;
                    statusSelect.dataset.currentStatus = payload.status;
                    orderRow.dataset.orderStatus = payload.status;
                    orderRow.style.backgroundColor = 'rgba(13, 147, 115, 0.1)';
                    window.setTimeout(() => {
                        orderRow.style.backgroundColor = '';
                    }, 1200);
                }
            }

            const cells = orderRow.children;
            const branchOffset = window.isSuperAdmin ? 1 : 0;
            if (payload.payment_status || payload.payment_method) {
                const paymentCell = cells[4 + branchOffset];
                if (paymentCell) paymentCell.innerHTML = paymentBadgeHtml(payload);
            }
            if (payload.total_formatted) {
                const totalCell = cells[5 + branchOffset];
                if (totalCell) totalCell.textContent = payload.total_formatted;
            }

            const detailRow = document.getElementById(`order-detail-${payload.order_id}`);
            if (detailRow && Array.isArray(payload.items)) {
                const wasOpen = !detailRow.classList.contains('d-none');
                detailRow.outerHTML = detailRowHtml(payload);
                if (wasOpen) {
                    document.getElementById(`order-detail-${payload.order_id}`)?.classList.remove('d-none');
                }
            }

            if (options.toast && payload.message) {
                showOrderStatusToast(payload.message, 'success');
            }

            return true;
        }

        async function submitOrderStatus(select) {
            const form = select.closest('[data-order-status-form]');
            const orderRow = select.closest('tr[data-order-id]');
            const orderId = String(form?.dataset.orderId || orderRow?.dataset.orderId || '');
            const previousStatus = String(select.dataset.currentStatus || '');
            const requestedStatus = String(select.value || '');

            if (!form || !orderRow || !orderId || requestedStatus === previousStatus || pendingStatusUpdates.has(orderId)) {
                select.value = previousStatus || requestedStatus;
                return;
            }

            // Hủy đơn luôn cần lý do. Mở cùng modal với nút "Hủy đơn" trong
            // phần chi tiết thay vì gửi request thiếu cancellation_reason.
            if (requestedStatus === 'cancelled') {
                select.value = previousStatus || requestedStatus;
                if (typeof window.openCancelOrderModal === 'function') {
                    window.openCancelOrderModal(orderId);
                } else {
                    showOrderStatusToast('Vui lòng mở chi tiết đơn để nhập lý do hủy.', 'error');
                }
                return;
            }

            const loading = form.querySelector('[data-order-status-loading]');
            const formData = new FormData(form);
            pendingStatusUpdates.add(orderId);
            select.disabled = true;
            loading?.classList.remove('d-none');

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });
                const payload = await response.json().catch(() => ({}));

                if (!response.ok || payload.success !== true || !payload.data) {
                    throw new Error(responseErrorMessage(payload, 'Không thể cập nhật trạng thái đơn hàng.'));
                }

                updateOrderStatusRow(orderRow, select, payload.data);
                showOrderStatusToast(payload.message || 'Cập nhật trạng thái đơn hàng thành công.', 'success');
            } catch (error) {
                select.value = previousStatus;
                select.dataset.currentStatus = previousStatus;
                showOrderStatusToast(error.message || 'Không thể cập nhật trạng thái đơn hàng.', 'error');
            } finally {
                pendingStatusUpdates.delete(orderId);
                loading?.classList.add('d-none');
                select.disabled = select.options.length <= 1;
            }
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

        function incidentAlertHtml(payload) {
            const incident = payload.shipment_incident;
            if (!incident) return '';

            const resolveUrl = payload.incident_resolve_url || '#';
            const isCustomerCancel = incident.incident_type === 'customer_cancel';
            return `
                <div class="alert alert-warning border-0 mb-4" style="border-radius:14px;box-shadow:0 8px 22px rgba(245,158,11,.12);">
                    <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                        <div class="flex-grow-1">
                            <div class="fw-bold">${isCustomerCancel ? 'Khách xin hủy đơn cần duyệt' : 'Sự cố tài xế cần xử lý'}</div>
                            <div class="small mt-1"><strong>${escapeHtml(incident.shipper_name || 'Shipper')}</strong> · ${escapeHtml(incident.description || 'Shipper báo sự cố.')}</div>
                            ${incident.reported_at_label ? `<div class="small text-secondary mt-1">Báo lúc ${escapeHtml(incident.reported_at_label)}</div>` : ''}
                            <div class="small mt-2">${isCustomerCancel ? 'Yêu cầu hủy chỉ xử lý nội bộ; khách không nhận thông báo về thao tác quản lý này.' : 'Đơn không bị hủy. Nếu đổi người, hệ thống tự tìm shipper rảnh gần điểm tiếp quản.'}</div>
                            <div class="d-flex flex-wrap gap-2 mt-3">
                                ${isCustomerCancel ? `
                                    <form action="${escapeHtml(resolveUrl)}" method="POST" class="m-0">
                                        <input type="hidden" name="_token" value="${escapeHtml(csrfToken)}">
                                        <input type="hidden" name="action" value="keep">
                                        <button class="btn btn-sm btn-outline-success" type="submit"><i class="bi bi-play-circle me-1"></i>Tiếp tục giao</button>
                                    </form>
                                    <form action="${escapeHtml(resolveUrl)}" method="POST" class="m-0" onsubmit="return confirm('Duyệt hủy đơn theo yêu cầu nội bộ này?');">
                                        <input type="hidden" name="_token" value="${escapeHtml(csrfToken)}">
                                        <input type="hidden" name="action" value="cancel">
                                        <button class="btn btn-sm btn-danger" type="submit"><i class="bi bi-x-circle me-1"></i>Duyệt hủy đơn</button>
                                    </form>
                                ` : `
                                    <form action="${escapeHtml(resolveUrl)}" method="POST" class="m-0">
                                        <input type="hidden" name="_token" value="${escapeHtml(csrfToken)}">
                                        <input type="hidden" name="action" value="keep">
                                        <button class="btn btn-sm btn-outline-success" type="submit"><i class="bi bi-check-circle me-1"></i>Giữ shipper hiện tại</button>
                                    </form>
                                    <form action="${escapeHtml(resolveUrl)}" method="POST" class="m-0" onsubmit="return confirm('Xác nhận để hệ thống tự tìm shipper thay thế gần nhất?');">
                                        <input type="hidden" name="_token" value="${escapeHtml(csrfToken)}">
                                        <input type="hidden" name="action" value="reassign">
                                        <button class="btn btn-sm btn-warning" type="submit"><i class="bi bi-arrow-repeat me-1"></i>Điều phối shipper khác</button>
                                    </form>
                                `}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        function nextStatusLabel(payload) {
            const labels = {
                pending: 'Chờ xác nhận',
                confirmed: 'Xác nhận đơn',
                preparing: 'Bắt đầu pha chế',
                ready_for_delivery: 'Sẵn sàng giao',
                ready_for_pickup: 'Sẵn sàng lấy',
                shipper_picked_up: 'Shipper đã lấy hàng',
                delivering: 'Đang giao',
                delivered: 'Đã giao',
                completed: 'Hoàn thành',
                cancelled: 'Đã hủy',
            };

            return labels[payload.next_status || ''] || 'Tiếp theo';
        }

        function statusStepHtml(payload) {
            const status = payload.status || 'pending';
            const updateUrl = payload.status_update_url || statusUpdateUrl(payload.order_id);
            const currentLabel = payload.status_label || 'Chờ xử lý';
            const providedOptions = payload.status_options && typeof payload.status_options === 'object'
                ? payload.status_options
                : null;
            const statusOptions = providedOptions && Object.keys(providedOptions).length > 0
                ? { ...providedOptions }
                : {
                    [status]: currentLabel,
                    ...(payload.next_status ? { [payload.next_status]: nextStatusLabel(payload) } : {}),
                };

            // Không để dữ liệu realtime cũ làm xuất hiện lại lựa chọn hủy sau khi
            // đơn đã được xác nhận. Hủy sau đó chỉ xử lý ở Sự cố giao vận.
            if (status !== 'pending' && status !== 'cancelled') {
                delete statusOptions.cancelled;
            }

            if (!Object.keys(statusOptions).length) {
                statusOptions[status] = currentLabel || nextStatusLabel({ next_status: status });
            }

            const optionsHtml = Object.entries(statusOptions).map(([value, label]) => `
                <option value="${escapeHtml(value)}" ${String(value) === String(status) ? 'selected' : ''}>${escapeHtml(label)}</option>
            `).join('');

            const hasAlternative = Object.keys(statusOptions).some((value) => String(value) !== String(status));
            const disabled = !hasAlternative;

            return `
                <form action="${escapeHtml(updateUrl)}" method="POST" class="d-flex align-items-center gap-2 justify-content-center" data-order-status-form data-order-id="${escapeHtml(payload.order_id)}">
                    <input type="hidden" name="_token" value="${escapeHtml(csrfToken)}">
                    <input type="hidden" name="_method" value="PUT">
                    <select name="status" class="form-select form-select-sm" data-order-status-select data-current-status="${escapeHtml(status)}" ${disabled ? 'disabled' : ''}>
                        ${optionsHtml}
                    </select>
                    <span class="spinner-border spinner-border-sm text-primary d-none order-status-spinner" role="status" aria-label="Đang cập nhật" data-order-status-loading></span>
                </form>
            `;
        }

        function shipperInfoHtml(payload) {
            const shipper = payload.shipper || null;
            const deliveredWaiting = payload.status === 'delivered' && payload.auto_complete_at;

            return `
                <div class="mb-4">
                    <div class="admin-kicker mb-2 text-secondary fw-bold" style="letter-spacing:.05em;">NGƯỜI GIAO HÀNG</div>
                    ${shipper ? `
                        <div class="p-3 bg-light border rounded-4 d-flex align-items-start gap-3">
                            <div class="d-flex align-items-center justify-content-center flex-shrink-0 bg-white text-primary border" style="width:42px;height:42px;border-radius:13px;"><i class="bi bi-bicycle"></i></div>
                            <div class="small flex-grow-1">
                                <div class="fw-bold text-dark">${escapeHtml(shipper.name || shipper.code || 'Shipper')}</div>
                                <div class="text-secondary mt-1">Mã: <strong>${escapeHtml(shipper.code || '-')}</strong> · ${escapeHtml(shipper.phone || 'Chưa có SĐT')}</div>
                                <div class="text-secondary mt-1">${escapeHtml(shipper.vehicle_type || 'Chưa cập nhật xe')}${shipper.license_plate ? ` · <strong>${escapeHtml(shipper.license_plate)}</strong>` : ''}</div>
                            </div>
                            <span class="badge bg-warning text-dark">${escapeHtml(String(shipper.status || 'assigned').toUpperCase())}</span>
                        </div>
                    ` : '<div class="small text-secondary p-3 bg-light border rounded-4">Chưa có shipper được gán.</div>'}
                    ${deliveredWaiting ? `
                        <div class="alert alert-info py-2 px-3 mt-2 mb-0 small">
                            <i class="bi bi-hourglass-split me-1"></i>Đang chờ khách xác nhận. Hệ thống tự chuyển <strong>Hoàn thành</strong> lúc <strong>${escapeHtml(payload.auto_complete_at)}</strong>.
                        </div>
                    ` : ''}
                </div>
            `;
        }

        function detailRowHtml(payload) {
            const items = Array.isArray(payload.items) ? payload.items : [];
            const reviews = Array.isArray(payload.reviews) ? payload.reviews : [];
            const lines = items.map((item) => `
                <div class="order-detail-item">
                    <div class="d-flex align-items-center gap-3">
                        <div class="flex-shrink-0">
                            <img src="${escapeHtml(item.image_url || '')}" alt="${escapeHtml(item.product_name || 'Sản phẩm')}" class="order-detail-item-thumb">
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <h4 class="fw-bold mb-1 text-truncate" style="font-size: 0.92rem; color: var(--a-ink);">${escapeHtml(item.product_name || 'Sản phẩm')}</h4>
                            <div class="text-muted small mb-1 text-truncate">Kích cỡ: ${escapeHtml(item.size_name || 'Chưa chọn')}</div>
                            <div class="text-muted small text-truncate">Đá: ${parseInt(item.ice_level || 0)}% • Đường: ${parseInt(item.sugar_level || 0)}%</div>
                        </div>
                        <div class="text-end d-flex flex-column align-items-end gap-1">
                            <div class="fw-bold" style="font-size: 0.92rem; color: var(--a-ink);">${escapeHtml(item.total_formatted || '')}</div>
                            <div class="text-muted small">${escapeHtml(item.unit_price_formatted || '')}/sp</div>
                            <span class="badge px-2 py-1 text-dark" style="background-color: #f1f3f5; font-size: 0.72rem; border-radius: 6px; font-weight: 500;">SL ${parseInt(item.quantity || 1)}</span>
                        </div>
                    </div>
                </div>
            `).join('');

            const reviewLines = reviews.map((review) => {
                const stars = Array.from({ length: 5 }, (_, index) => {
                    const starNumber = index + 1;
                    return `<i class="bi ${Number(review.rating || 0) >= starNumber ? 'bi-star-fill' : 'bi-star'}"></i>`;
                }).join('');

                return `
                    <div class="order-review-item">
                        <div class="d-flex align-items-start justify-content-between gap-2">
                            <div class="d-flex align-items-center gap-2 min-w-0">
                                ${review.product_image ? `<img src="${escapeHtml(review.product_image)}" alt="${escapeHtml(review.product_name || 'Sản phẩm')}" style="width:34px;height:34px;object-fit:cover;border-radius:10px;background:#f3f4f6;flex-shrink:0;">` : `<span class="admin-avatar" style="width:34px;height:34px;font-size:.72rem;flex-shrink:0;">${escapeHtml((review.product_name || 'R').charAt(0))}</span>`}
                                <div class="min-w-0">
                                    <div class="fw-semibold text-dark text-truncate" style="font-size: 0.88rem;">${escapeHtml(review.product_name || 'Sản phẩm đã xóa')}</div>
                                    <div class="text-secondary small text-truncate">${escapeHtml(review.user_name || 'Khách hàng')} · ${escapeHtml(review.created_at || '')}</div>
                                </div>
                            </div>
                            <span class="admin-rating">${stars}${Number(review.rating || 0)}/5</span>
                        </div>
                        <div class="text-secondary small mt-2">${escapeHtml(review.comment || 'Không có nhận xét.')}</div>
                    </div>
                `;
            }).join('');

            const statusKey = payload.status || 'pending';
            const statusLabel = (payload.status_label || 'Chờ xử lý').toUpperCase();

            return `
                <tr id="order-detail-${escapeHtml(payload.order_id)}" class="d-none order-detail-row">
                    <td colspan="9" class="border-0 pt-0">
                        <div class="order-detail-card p-3 p-lg-4 shadow-sm">
                            <div class="row g-3">
                                <!-- Cột Trái: Sản phẩm -->
                                <div class="col-lg-7">
                                    <div class="mb-3">
                                        <div class="admin-kicker mb-2 text-secondary fw-bold" style="letter-spacing: 0.05em;">SẢN PHẨM TRONG ĐƠN HÀNG</div>
                                    </div>
                                    <div class="d-grid gap-2">
                                        ${lines || '<div class="text-secondary p-3 border rounded-4 text-center">Chưa có dữ liệu sản phẩm.</div>'}
                                    </div>
                                </div>
                                <!-- Cột Phải: Thông tin khách hàng & Tổng hợp đơn hàng -->
                                <div class="col-lg-5">
                                    <div class="mb-3">
                                        <div class="admin-kicker mb-2 text-secondary fw-bold" style="letter-spacing: 0.05em;">THÔNG TIN KHÁCH HÀNG</div>
                                    </div>
                                    <div class="mb-3 d-flex flex-column gap-2 text-secondary" style="font-size: 0.88rem;">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-telephone text-muted" style="font-size: 1rem;"></i>
                                            <span class="fw-semibold">${escapeHtml(payload.customer_phone || 'Chưa cập nhật')}</span>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-envelope text-muted" style="font-size: 1rem;"></i>
                                            <span>${escapeHtml(payload.customer_email || 'Chưa cập nhật')}</span>
                                        </div>
                                        <div class="d-flex align-items-start gap-2">
                                            <i class="bi bi-geo-alt text-muted mt-1" style="font-size: 1rem;"></i>
                                            <span>${escapeHtml(payload.shipping_address || 'Chưa cập nhật địa chỉ')}</span>
                                        </div>
                                    </div>

                                    ${shipperInfoHtml(payload)}
                                    ${reviewLines ? `
                                        <div class="mb-3">
                                            <div class="order-detail-section-title">ĐÁNH GIÁ</div>
                                            <div class="order-review-list">${reviewLines}</div>
                                        </div>
                                    ` : ''}
                                    ${incidentAlertHtml(payload)}

                                    <!-- Thẻ tóm tắt thanh toán -->
                                    <div class="order-detail-section order-detail-summary">
                                        <div class="d-flex flex-column gap-2" style="font-size: 0.84rem;">
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
                                            
                                            <div style="border-top: 1px dashed #e9ecef; margin: 0.5rem 0;"></div>
                                            
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <div class="text-muted" style="font-size: 0.68rem; letter-spacing: 0.05em; font-weight: 700;">TỔNG CỘNG</div>
                                                    <div class="fw-bold mt-1 text-primary" style="font-size: 1.35rem; line-height: 1;">${escapeHtml(payload.total_formatted || '')}</div>
                                                </div>
                                                <div class="text-end" style="font-size: 0.8rem;">
                                                    <div class="text-dark">Phương thức: <strong class="text-uppercase">${escapeHtml(payload.payment_method || 'cod')}</strong></div>
                                                    <div class="mt-1">
                                                        Trạng thái: <strong class="status-text-${escapeHtml(statusKey)}">${escapeHtml(statusLabel)}</strong>
                                                    </div>
                                                    ${payload.status_changed_at ? `
                                                        <div class="mt-1 text-muted" style="font-size:0.72rem;">
                                                            <i class="bi bi-clock-history me-1"></i>${escapeHtml(payload.status_changed_at)}
                                                            ${payload.status_changed_by_name ? `bởi <strong>${escapeHtml(payload.status_changed_by_name)}</strong>` : ''}
                                                        </div>
                                                    ` : ''}
                                                </div>
                                            </div>

                                            <div class="d-flex gap-2 align-items-stretch mt-3 w-100">
                                                ${payload.next_status ? `
                                                    <form action="${escapeHtml(payload.status_update_url || statusUpdateUrl(payload.order_id))}" method="POST" class="flex-grow-1 m-0">
                                                        <input type="hidden" name="_token" value="${escapeHtml(csrfToken)}">
                                                        <input type="hidden" name="_method" value="PUT">
                                                        <input type="hidden" name="status" value="${escapeHtml(payload.next_status)}">
                                                        <button type="submit" class="btn btn-primary w-100 px-3 py-2 text-center" style="background-color: #0b6b5f; border-color: #0b6b5f; border-radius: 12px; font-size: 0.85rem;">
                                                            Chuyển bước tiếp theo
                                                        </button>
                                                    </form>
                                                ` : ''}
                                                ${payload.can_cancel ? `
                                                    <button type="button" class="btn btn-outline-danger px-3 py-2" data-bs-toggle="modal" data-bs-target="#cancelOrderModal" data-order-id="${escapeHtml(payload.order_id)}" style="border-radius: 12px; border: 1.5px solid var(--a-danger); color: var(--a-danger); background: transparent; font-size: 0.85rem; white-space: nowrap;">
                                                        Hủy đơn
                                                    </button>
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
                ${window.isSuperAdmin ? `
                <td>
                    ${payload.branch_name && payload.branch_name !== 'Chưa gán' ? `<span class="badge bg-light text-dark">${escapeHtml(payload.branch_name)}</span>` : `<span class="text-secondary small">-</span>`}
                </td>
                ` : ''}
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
                status_update_url: payload.status_update_url || statusUpdateUrl(payload.order_id),
                can_cancel: payload.can_cancel ?? (payload.status === 'pending'),
                shipping_address: payload.shipping_address || '',
                shipper: payload.shipper || null,
                delivered_at: payload.delivered_at || null,
                auto_complete_at: payload.auto_complete_at || null,
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

        document.addEventListener('change', function (event) {
            const statusSelect = event.target.closest('[data-order-status-select]');
            if (statusSelect) {
                submitOrderStatus(statusSelect);
            }
        });

        document.addEventListener('submit', function (event) {
            const statusForm = event.target.closest('[data-order-status-form]');
            if (!statusForm) {
                return;
            }

            event.preventDefault();
            const statusSelect = statusForm.querySelector('[data-order-status-select]');
            if (statusSelect) {
                submitOrderStatus(statusSelect);
            }
        });

        function handleNewOrders(orders) {
            let added = 0;

            orders.slice().reverse().forEach((payload) => {
                if (applyRealtimeOrderPayload(payload)) {
                    added += 1;
                }
            });
        }

        let pollRecentOrders = null;
        let latestOrderId = Number(initialLatestId || 0);
        let latestUpdatedAt = initialLatestUpdatedAt;
        let realtimeSyncTimer = null;
        let realtimeSyncInFlight = false;

        async function syncOrderDelta() {
            if (realtimeSyncInFlight) return;
            realtimeSyncInFlight = true;

            try {
                const url = new URL(recentOrdersUrl, window.location.origin);
                url.searchParams.set('after_id', String(hasActiveFilters ? 0 : latestOrderId));
                if (latestUpdatedAt) url.searchParams.set('updated_after', latestUpdatedAt);

                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    cache: 'no-store',
                });

                if (!response.ok) return;

                const data = await response.json();
                const orders = Array.isArray(data.orders) ? data.orders : [];
                orders
                    .slice()
                    .sort((a, b) => String(a.updated_at || '').localeCompare(String(b.updated_at || '')))
                    .forEach((payload) => applyRealtimeOrderPayload(payload));

                if (data.latest_updated_at) {
                    latestUpdatedAt = data.latest_updated_at;
                } else if (orders.length) {
                    latestUpdatedAt = orders
                        .map((order) => order.updated_at)
                        .filter(Boolean)
                        .sort()
                        .pop() || latestUpdatedAt;
                }
                if (data.latest_id) {
                    latestOrderId = Math.max(latestOrderId, Number(data.latest_id || 0));
                }
            } catch (error) {
                console.warn('Không thể đồng bộ trạng thái đơn hàng.', error);
            } finally {
                realtimeSyncInFlight = false;
            }
        }

        function queueOrderDeltaSync(delay = 120) {
            if (realtimeSyncTimer) window.clearTimeout(realtimeSyncTimer);
            realtimeSyncTimer = window.setTimeout(syncOrderDelta, delay);
        }

        document.addEventListener('order:created', function (event) {
            const payload = event.detail || {};
            if (!hasActiveFilters && payload.order_id) {
                applyRealtimeOrderPayload(payload);
            }
            queueOrderDeltaSync(80);
        });

        // Listen for order status updates (including cancellations)
        document.addEventListener('order:status-updated', function (event) {
            const payload = event.detail || {};
            const orderRow = document.querySelector(`tr[data-order-id="${payload.order_id}"]`);
            applyRealtimeOrderPayload(payload);

            // If order is cancelled and has reason, display it in detail section
            if (payload.status === 'cancelled' && payload.cancellation_reason) {
                const detailRow = document.getElementById(`order-detail-${payload.order_id}`);
                if (detailRow && !detailRow.classList.contains('d-none')) {
                    const customerInfoSection = detailRow.querySelector('.col-lg-5 .mb-4');
                    if (customerInfoSection) {
                        // Remove existing cancellation reason if any
                        const existingReason = customerInfoSection.parentElement.querySelector('.cancellation-reason-alert');
                        if (existingReason) {
                            existingReason.remove();
                        }
                        
                        // Add new cancellation reason
                        const reasonHtml = `
                            <div class="alert alert-danger d-flex align-items-start gap-2 mb-4 cancellation-reason-alert" style="border-radius: 12px; border-left: 4px solid #dc2626;">
                                <i class="bi bi-exclamation-triangle-fill" style="font-size: 1.2rem;"></i>
                                <div class="flex-grow-1">
                                    <div class="fw-bold mb-1">Lý do hủy đơn:</div>
                                    <div>${escapeHtml(payload.cancellation_reason)}</div>
                                </div>
                            </div>
                        `;
                        customerInfoSection.insertAdjacentHTML('afterend', reasonHtml);
                    }
                }
            }

            // Highlight the updated row
            if (orderRow) {
                orderRow.style.backgroundColor = 'rgba(13, 147, 115, 0.1)';
                setTimeout(() => {
                    orderRow.style.backgroundColor = '';
                }, 2500);
            }
        });

        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text ? String(text).replace(/[&<>"']/g, m => map[m]) : '';
        }

        if (!hasActiveFilters) {
            const liveBadge = document.getElementById('adminOrdersLiveBadge');
            liveBadge?.classList.remove('d-none');

            pollRecentOrders = async function () {
                await syncOrderDelta();
            };

        }

        window.setInterval(() => {
            if (!document.hidden) queueOrderDeltaSync(0);
        }, hasActiveFilters ? 1600 : 1200);

        function subscribeAdminOrderRealtime() {
            if (!window.Echo) return false;

            const channelName = window.isSuperAdmin
                ? 'admin-notifications'
                : (adminBranchId ? `admin-notifications.${adminBranchId}` : null);
            if (!channelName) return false;

            try {
                window.Echo.private(channelName)
                    .listen('.order.created', function (payload) {
                        if (!hasActiveFilters) {
                            applyRealtimeOrderPayload(payload);
                        }
                        queueOrderDeltaSync(60);
                    })
                    .listen('.order.status.updated', function (payload) {
                        applyRealtimeOrderPayload(payload);
                        queueOrderDeltaSync(60);
                    });
                return true;
            } catch (error) {
                console.warn('Không thể đăng ký realtime đơn hàng.', error);
                return false;
            }
        }

        if (!subscribeAdminOrderRealtime()) {
            window.setTimeout(subscribeAdminOrderRealtime, 900);
        }
    })();
</script>

<!-- Modal Hủy đơn hàng -->
<div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-labelledby="cancelOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; border: none;">
            <div class="modal-header" style="border-bottom: 1px solid #e9ecef;">
                <h5 class="modal-title fw-bold" id="cancelOrderModalLabel">
                    <i class="bi bi-x-circle text-danger me-2"></i>Hủy đơn hàng
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="cancelOrderForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <input type="hidden" name="status" value="cancelled">
                    <div class="mb-3">
                        <label for="cancellationReason" class="form-label fw-semibold">Lý do hủy đơn <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="cancellationReason" name="cancellation_reason" rows="4" placeholder="Vui lòng nhập lý do hủy đơn hàng để thông báo cho khách hàng..." required style="border-radius: 12px;"></textarea>
                        <div class="form-text">Lý do này sẽ được gửi đến khách hàng qua thông báo và hiển thị trong lịch sử đơn hàng.</div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #e9ecef;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 12px;">Đóng</button>
                    <button type="submit" class="btn btn-danger" style="border-radius: 12px;">
                        <i class="bi bi-x-circle me-1"></i>Xác nhận hủy đơn
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Handle cancel order modal
    document.addEventListener('DOMContentLoaded', function() {
        const cancelModal = document.getElementById('cancelOrderModal');
        const cancelForm = document.getElementById('cancelOrderForm');
        const cancelReasonTextarea = document.getElementById('cancellationReason');
        const updateUrlTemplate = @json(route($orderUpdateRouteName, ['id' => '__ORDER_ID__']));

        // Dùng chung cho dropdown trạng thái và các nút hủy trong phần chi tiết.
        window.openCancelOrderModal = function(orderId) {
            if (!cancelModal || !cancelForm || !cancelReasonTextarea || !orderId) {
                return;
            }

            cancelForm.setAttribute('action', updateUrlTemplate.replace('__ORDER_ID__', orderId));
            cancelForm.dataset.orderId = orderId;
            cancelReasonTextarea.value = '';

            if (window.bootstrap?.Modal) {
                window.bootstrap.Modal.getOrCreateInstance(cancelModal).show();
            }
        };
        
        if (cancelModal) {
            cancelModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const orderId = button?.getAttribute('data-order-id') || cancelForm.dataset.orderId;

                if (!orderId) {
                    return;
                }
                
                cancelForm.setAttribute('action', updateUrlTemplate.replace('__ORDER_ID__', orderId));
                cancelForm.dataset.orderId = orderId;
                cancelReasonTextarea.value = '';
            });
        }

    });
</script>

@endsection
