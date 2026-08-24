@extends(auth()->user()->preferredAdminLayout())

@section('title', 'Sự cố giao vận')
@section('page-title', 'Sự cố giao vận')
@section('hide-topbar-search', true)

@section('content')
@php
    $rootMode = $isRootSuperAdmin ?? (auth()->user()->isSuperAdmin() && !auth()->user()->isViewingAdminWorkspace());
    $indexRoute = $rootMode
        ? route('admin.super-admin.manage.shipper-incidents.index')
        : route('admin.shipper-incidents.index');
@endphp

<style>
    .incident-stat { border:1px solid rgba(15,23,42,.08); border-radius:16px; background:#fff; }
    .incident-card { border:1px solid rgba(15,23,42,.08); border-radius:18px; background:#fff; box-shadow:0 8px 28px rgba(15,23,42,.05); }
    .incident-pulse { width:10px;height:10px;border-radius:50%;background:#ef4444;box-shadow:0 0 0 5px rgba(239,68,68,.12); }
    .incident-meta { font-size:.82rem;color:#64748b; }
    .incident-desc { background:#fff7ed;border:1px solid #fed7aa;border-radius:12px;padding:.8rem 1rem; }
</style>

<section class="d-grid gap-4">
    <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
        <div>
            <div class="text-secondary small mb-1">Vận hành giao vận</div>
            <h2 class="h4 fw-bold mb-1">Trung tâm sự cố giao vận</h2>
            <div class="text-secondary small">
                @if($rootMode)
                    Super Admin giám sát các sự cố do shipper báo ở toàn bộ chi nhánh. Admin từng chi nhánh vẫn là người xử lý chính tại chỗ.
                @else
                    Chỉ hiển thị các sự cố do shipper báo thuộc chi nhánh của bạn. Super Admin đồng thời nhận được để giám sát toàn hệ thống.
                @endif
            </div>
        </div>
        <div class="incident-stat px-4 py-3 d-flex align-items-center gap-3">
            <span class="incident-pulse" @if(($pendingCount ?? 0) === 0) style="background:#22c55e;box-shadow:0 0 0 5px rgba(34,197,94,.12);" @endif></span>
            <div>
                <div class="fw-bold fs-5">{{ (int) ($pendingCount ?? 0) }}</div>
                <div class="small text-secondary">Đang chờ xử lý</div>
            </div>
        </div>
    </div>

    <form method="GET" action="{{ $indexRoute }}" class="incident-card p-3">
        <div class="row g-2 align-items-end">
            <div class="col-lg-6">
                <label class="form-label small fw-semibold">Tìm sự cố</label>
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Mã đơn, shipper, chi nhánh, lý do...">
            </div>
            <div class="col-lg-3">
                <label class="form-label small fw-semibold">Trạng thái</label>
                <select name="status" class="form-select">
                    <option value="pending" @selected(($filters['status'] ?? 'pending') === 'pending')>Đang chờ xử lý</option>
                    <option value="resolved" @selected(($filters['status'] ?? '') === 'resolved')>Đã xử lý</option>
                    <option value="all" @selected(($filters['status'] ?? '') === 'all')>Tất cả</option>
                </select>
            </div>
            <div class="col-lg-3 d-flex gap-2">
                <button class="btn btn-primary flex-grow-1" type="submit"><i class="bi bi-funnel me-1"></i>Lọc</button>
                <a href="{{ $indexRoute }}" class="btn btn-outline-secondary">Làm mới</a>
            </div>
        </div>
    </form>

    <div id="incidentManagementList" class="d-grid gap-3">
        @forelse($incidents as $incident)
            @php
                $resolveUrl = $rootMode
                    ? route('admin.super-admin.manage.orders.shipper-incident.resolve', $incident['order_id'])
                    : route('admin.orders.shipper-incident.resolve', $incident['order_id']);
                $orderUrl = $rootMode
                    ? route('admin.super-admin.manage.orders.index', ['q' => $incident['order_code']])
                    : route('admin.orders.index', ['q' => $incident['order_code']]);
            @endphp
            <article class="incident-card p-4" data-incident-id="{{ $incident['incident_id'] }}">
                <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
                    <div class="d-flex align-items-start gap-3 min-w-0">
                        <div class="d-flex align-items-center justify-content-center flex-shrink-0" style="width:46px;height:46px;border-radius:14px;background:{{ $incident['is_pending'] ? '#fff7ed' : '#ecfdf5' }};color:{{ $incident['is_pending'] ? '#c2410c' : '#047857' }};font-size:1.15rem;">
                            <i class="bi {{ $incident['is_pending'] ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill' }}"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <a href="{{ $orderUrl }}" class="fw-bold text-decoration-none">{{ $incident['order_code'] }}</a>
                                <span class="badge {{ $incident['is_pending'] ? 'bg-warning text-dark' : 'bg-success' }}">
                                    {{ $incident['is_pending'] ? 'CẦN XỬ LÝ' : 'ĐÃ XỬ LÝ' }}
                                </span>
                                <span class="badge {{ ($incident['incident_type'] ?? 'driver_issue') === 'customer_cancel' ? 'bg-danger-subtle text-danger' : 'bg-info-subtle text-info-emphasis' }}">
                                    {{ ($incident['incident_type'] ?? 'driver_issue') === 'customer_cancel' ? 'SỰ CỐ PHÍA KHÁCH' : 'SỰ CỐ TÀI XẾ' }}
                                </span>
                                @if($rootMode)
                                    <span class="badge bg-light text-dark">{{ $incident['branch_name'] }}</span>
                                @endif
                            </div>
                            <div class="incident-meta mt-1">
                                Báo lúc <strong>{{ $incident['reported_at_label'] ?: '-' }}</strong>
                                · Khách: <strong>{{ $incident['customer_name'] }}</strong>
                            </div>
                        </div>
                    </div>
                    <a href="{{ $orderUrl }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-receipt me-1"></i>Xem đơn</a>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-lg-5">
                        <div class="small text-secondary fw-semibold mb-2">SHIPPER BÁO SỰ CỐ</div>
                        <div class="d-grid gap-1 small">
                            <div><i class="bi bi-person-badge me-2 text-primary"></i><strong>{{ $incident['shipper_name'] }}</strong> @if($incident['shipper_code'])· {{ $incident['shipper_code'] }}@endif</div>
                            <div><i class="bi bi-telephone me-2 text-primary"></i>{{ $incident['shipper_phone'] ?: 'Chưa có SĐT' }}</div>
                            <div><i class="bi bi-bicycle me-2 text-primary"></i>{{ $incident['vehicle_type'] ?: 'Chưa cập nhật xe' }} @if($incident['license_plate'])· <strong>{{ $incident['license_plate'] }}</strong>@endif</div>
                            <div><i class="bi bi-shop me-2 text-primary"></i>{{ $incident['branch_name'] }}</div>
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <div class="small text-secondary fw-semibold mb-2">NỘI DUNG</div>
                        <div class="incident-desc small">{{ $incident['description'] }}</div>
                    </div>
                </div>

                @if($incident['is_pending'])
                    <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mt-4 pt-3 border-top">
                        @if(($incident['incident_type'] ?? 'driver_issue') === 'customer_cancel')
                            <div class="small text-secondary">
                                Shipper báo sự cố phía khách. Admin/Super Admin xác nhận hủy tại đây và yêu cầu shipper mang đồ về quán.
                            </div>
                            <div class="d-flex gap-2 flex-wrap">
                                <form action="{{ $resolveUrl }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="action" value="keep">
                                    <button class="btn btn-outline-success" type="submit"><i class="bi bi-check-circle me-1"></i>Tiếp tục giao</button>
                                </form>
                                <form action="{{ $resolveUrl }}" method="POST" onsubmit="return confirm('Xác nhận hủy đơn theo yêu cầu sự cố của khách?');">
                                    @csrf
                                    <input type="hidden" name="action" value="cancel">
                                    <button class="btn btn-danger" type="submit"><i class="bi bi-box-arrow-in-left me-1"></i>Xác nhận hủy · mang về quán</button>
                                </form>
                            </div>
                        @else
                            <div class="small text-secondary">
                                Đơn không bị hủy. Nếu đổi người, hệ thống tự tìm shipper phù hợp và giữ nguyên trạng thái nghiệp vụ.
                            </div>
                            <div class="d-flex gap-2 flex-wrap">
                                <form action="{{ $resolveUrl }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="action" value="keep">
                                    <button class="btn btn-outline-success" type="submit"><i class="bi bi-check-circle me-1"></i>Giữ shipper hiện tại</button>
                                </form>
                                <form action="{{ $resolveUrl }}" method="POST" onsubmit="return confirm('Xác nhận shipper hiện tại không thể tiếp tục và để hệ thống tự điều phối người thay thế?');">
                                    @csrf
                                    <input type="hidden" name="action" value="reassign">
                                    <button class="btn btn-warning" type="submit"><i class="bi bi-arrow-repeat me-1"></i>Điều phối shipper khác</button>
                                </form>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="alert alert-success mb-0 mt-4 py-2 px-3 small">
                        <i class="bi bi-check-circle-fill me-1"></i>
                        {{ $incident['resolution_description'] ?: 'Sự cố đã được quản lý xử lý.' }}
                        @if($incident['resolved_at_label'])
                            <span class="text-secondary">· {{ $incident['resolved_at_label'] }}</span>
                        @endif
                    </div>
                @endif
            </article>
        @empty
            <div class="incident-card p-5 text-center">
                <i class="bi bi-shield-check text-success" style="font-size:2.2rem;"></i>
                <div class="fw-bold mt-3">Không có sự cố phù hợp</div>
                <div class="text-secondary small mt-1">Khi shipper báo sự cố, cảnh báo sẽ xuất hiện tại đây và trên chuông realtime.</div>
            </div>
        @endforelse
    </div>
</section>
@endsection
