@extends(auth()->user()?->isSuperAdmin() ? 'layouts.super-admin' : 'layouts.admin')

@section('page-title', 'Quản lý nhân viên')

@section('content')
<style>
    .staff-page { --sp-amber: #d97706; --sp-amber-soft: #fef3c7; --sp-green: #0d9373; --sp-green-soft: #e7f7f2; --sp-ink: #111827; --sp-muted: #6b7280; --sp-border: #e5e7eb; }
    .staff-avatar { width:38px;height:38px;border-radius:50%;background:var(--sp-amber-soft);color:var(--sp-amber);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.8rem;flex-shrink:0; }
    .staff-badge { display:inline-flex;align-items:center;gap:.3rem;border-radius:999px;padding:.25rem .6rem;font-size:.68rem;font-weight:700; }
    .staff-badge-active { background:#dcfce7;color:#166534; }
    .staff-badge-locked { background:#fee2e2;color:#991b1b; }
    .staff-badge-branch { background:var(--sp-amber-soft);color:#92400e; }
    .btn-staff-primary { background:#d97706;border:none;color:#fff;border-radius:8px;padding:.45rem 1rem;font-size:.8rem;font-weight:700;display:inline-flex;align-items:center;gap:.4rem; }
    .btn-staff-primary:hover { background:#b45309;color:#fff; }
    .staff-table th { font-size:.68rem;text-transform:uppercase;letter-spacing:.06em;font-weight:700;color:var(--sp-muted);background:#f9fafb;padding:.65rem .85rem;border-bottom:2px solid var(--sp-border); }
    .staff-table td { padding:.72rem .85rem;border-bottom:1px solid var(--sp-border);vertical-align:middle;font-size:.8rem; }
    .staff-table tr:last-child td { border-bottom:0; }
    .staff-table tr:hover td { background:#fafafa; }
    .staff-filter { border:1px solid var(--sp-border);border-radius:8px;padding:.42rem .7rem;font-size:.78rem;color:var(--sp-ink);background:#fff;outline:0; }
    .staff-filter:focus { border-color:var(--sp-amber);box-shadow:0 0 0 3px rgba(217,119,6,.1); }
    .delivery-fee-card { border:1px solid #ccefe5; background:linear-gradient(135deg,#ffffff 0%,#f3fffb 100%); }
    .delivery-fee-icon { width:42px;height:42px;border-radius:12px;background:var(--sp-green-soft);color:var(--sp-green);display:flex;align-items:center;justify-content:center;font-size:1.15rem;flex:0 0 auto; }
    .delivery-fee-kpi { border:1px solid #dceee9;border-radius:10px;background:#fff;padding:.75rem .85rem;height:100%; }
    .delivery-fee-kpi .value { font-size:1.05rem;font-weight:800;color:var(--sp-ink); }
    .delivery-tier-table th { font-size:.68rem;text-transform:uppercase;letter-spacing:.04em;color:var(--sp-muted);background:#f8fbfa; }
    .delivery-tier-table td,.delivery-tier-table th { padding:.6rem .65rem;vertical-align:middle;border-color:#e5efec; }
    .delivery-tier-range { min-width:120px;font-size:.78rem;font-weight:700;color:#0f766e; }
    .delivery-tier-input { min-width:110px;font-size:.8rem; }
    .delivery-fee-preview { border:1px dashed #a7d9cc;border-radius:10px;background:#f7fffc;padding:.8rem; }
    .delivery-fee-preview strong { color:#047857; }
    .delivery-fee-toggle { border:1px solid #d7e3df;border-radius:999px;background:#fff;color:#4b5563;padding:.35rem .7rem;font-size:.72rem;font-weight:700;display:inline-flex;align-items:center;gap:.4rem; }
    .delivery-fee-toggle:hover { background:#f8fafc;color:#111827; }
    .delivery-fee-toggle i:last-child { transition:transform .18s ease; }
    .delivery-fee-card.is-open .delivery-fee-toggle i:last-child { transform:rotate(180deg); }
    .delivery-fee-summary { font-size:.72rem;color:var(--sp-muted); }
</style>

<div class="staff-page">
    @include('admin.staff.partials.branch-shipping-fee-panel')

    {{-- Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <p class="text-secondary small mb-1"><i class="bi bi-person-badge me-1 text-warning"></i>Quản lý nhân viên</p>
            <h1 class="h4 fw-bold mb-0" style="color:var(--sp-ink);">Danh sách nhân viên</h1>
            <p class="text-secondary small mt-1 mb-0">Quản lý tài khoản nhân viên
                {{ auth()->user()->isSuperAdmin() ? 'toàn hệ thống' : 'chi nhánh của bạn' }}
            </p>
        </div>
        <button type="button" class="btn-staff-primary btn" data-bs-toggle="modal" data-bs-target="#createStaffModal">
            <i class="bi bi-person-plus"></i> Thêm nhân viên
        </button>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert alert-success d-flex align-items-center gap-2 rounded-3 mb-3" style="font-size:.82rem;">
            <i class="bi bi-check-circle-fill"></i>{{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger d-flex align-items-center gap-2 rounded-3 mb-3" style="font-size:.82rem;">
            <i class="bi bi-exclamation-circle-fill"></i>{{ session('error') }}
        </div>
    @endif

    {{-- Stat cards --}}
    <div class="row g-3 mb-4">
        <div class="col-4">
            <div class="admin-card p-3 d-flex align-items-center gap-3 h-100">
                <span style="width:40px;height:40px;border-radius:10px;background:var(--sp-amber-soft);color:var(--sp-amber);display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;"><i class="bi bi-people"></i></span>
                <div>
                    <div class="fw-bold" style="font-size:1.4rem;color:var(--sp-ink);">{{ $stats['total'] }}</div>
                    <div class="text-secondary" style="font-size:.72rem;">Tổng nhân viên</div>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="admin-card p-3 d-flex align-items-center gap-3 h-100">
                <span style="width:40px;height:40px;border-radius:10px;background:#dcfce7;color:#166534;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;"><i class="bi bi-person-check"></i></span>
                <div>
                    <div class="fw-bold" style="font-size:1.4rem;color:#166534;">{{ $stats['active'] }}</div>
                    <div class="text-secondary" style="font-size:.72rem;">Đang hoạt động</div>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="admin-card p-3 d-flex align-items-center gap-3 h-100">
                <span style="width:40px;height:40px;border-radius:10px;background:#fee2e2;color:#991b1b;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;"><i class="bi bi-lock"></i></span>
                <div>
                    <div class="fw-bold" style="font-size:1.4rem;color:#991b1b;">{{ $stats['locked'] }}</div>
                    <div class="text-secondary" style="font-size:.72rem;">Đã khóa</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Cài đặt phí giao hàng: cùng khu vực quản lý nhân viên/shipper --}}
    @php
        $deliveryFeeSettings = $deliveryFeeSettings ?? \App\Support\ShippingFee::settings();
        $deliveryFeeBag = $errors->deliveryFeeSettings;
        $deliveryFeeTiers = collect($deliveryFeeSettings['cup_tiers'] ?? []);
        if ($deliveryFeeBag->any() && is_array(old('tier_price'))) {
            $oldMax = array_values(old('tier_max', []));
            $oldPrice = array_values(old('tier_price', []));
            $nextMin = 1;
            $deliveryFeeTiers = collect($oldPrice)->map(function ($price, $index) use (&$nextMin, $oldMax) {
                $rawMax = $oldMax[$index] ?? null;
                $max = ($rawMax === null || $rawMax === '') ? null : (int) $rawMax;
                $row = [
                    'min_cups' => $nextMin,
                    'max_cups' => $max,
                    'price_per_km' => (int) $price,
                ];
                if ($max !== null) $nextMin = $max + 1;
                return $row;
            });
        }
        $deliveryFreeKm = (float) old('free_distance_km', $deliveryFeeSettings['free_distance_km'] ?? 5);
        $deliveryFastSurcharge = (int) old('fast_surcharge', $deliveryFeeSettings['fast_surcharge'] ?? 8000);
    @endphp
    <div class="admin-card delivery-fee-card p-3 p-lg-4 mb-4" id="delivery-fee-settings" data-delivery-fee-card>
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
            <div class="d-flex align-items-start gap-3">
                <span class="delivery-fee-icon"><i class="bi bi-truck"></i></span>
                <div>
                    <div class="fw-bold" style="color:var(--sp-ink);font-size:1rem;">Cài đặt phí giao hàng</div>
                    <div class="text-secondary" style="font-size:.76rem;max-width:720px;">
                        Dùng quãng đường <strong>đường bộ thực tế</strong>. Phần nằm trong ngưỡng miễn phí không tính tiền; chỉ số km vượt ngưỡng mới nhân với đơn giá/km theo tổng số cốc.
                    </div>
                    <div class="delivery-fee-summary mt-1">
                        Miễn phí {{ rtrim(rtrim(number_format($deliveryFreeKm, 2, ',', '.'), '0'), ',') }} km ·
                        phạm vi {{ (int) \App\Support\OrderDistancePolicy::MAX_DISTANCE_KM }} km ·
                        giao nhanh +{{ number_format($deliveryFastSurcharge, 0, ',', '.') }}đ
                    </div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                @if(auth()->user()->isSuperAdmin())
                    <span class="staff-badge" style="background:#dcfce7;color:#166534;"><i class="bi bi-shield-check"></i> Super Admin được sửa</span>
                @else
                    <span class="staff-badge" style="background:#f3f4f6;color:#4b5563;"><i class="bi bi-eye"></i> Chỉ xem</span>
                @endif
                <button type="button" class="delivery-fee-toggle" data-delivery-fee-toggle aria-expanded="false">
                    <i class="bi bi-sliders2"></i>
                    <span data-delivery-fee-toggle-label>Xem</span>
                    <i class="bi bi-chevron-down"></i>
                </button>
            </div>
        </div>

        <div data-delivery-fee-body hidden>
            <div class="row g-2 mb-3">
                <div class="col-md-4">
                    <div class="delivery-fee-kpi">
                        <div class="text-secondary" style="font-size:.7rem;">Miễn phí đầu tuyến</div>
                        <div class="value">{{ rtrim(rtrim(number_format($deliveryFreeKm, 2, ',', '.'), '0'), ',') }} km</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="delivery-fee-kpi">
                        <div class="text-secondary" style="font-size:.7rem;">Phạm vi giao tối đa</div>
                        <div class="value">{{ (int) \App\Support\OrderDistancePolicy::MAX_DISTANCE_KM }} km</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="delivery-fee-kpi">
                        <div class="text-secondary" style="font-size:.7rem;">Phụ phí giao nhanh</div>
                        <div class="value">{{ number_format($deliveryFastSurcharge, 0, ',', '.') }}đ</div>
                    </div>
                </div>
            </div>

            @if($deliveryFeeBag->any())
                <div class="alert alert-danger py-2 px-3" style="font-size:.76rem;">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    {{ $deliveryFeeBag->first() }}
                </div>
            @endif

            @if(auth()->user()->isSuperAdmin())
            <form method="POST" action="{{ route('admin.super-admin.manage.staff.delivery-fee-settings.update') }}" data-delivery-fee-form>
                @csrf
                @method('PUT')
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Miễn phí ship trong bao nhiêu km?</label>
                        <div class="input-group input-group-sm">
                            <input type="number" name="free_distance_km" value="{{ $deliveryFreeKm }}" min="0" max="15" step="0.1" class="form-control" required data-free-km>
                            <span class="input-group-text">km</span>
                        </div>
                        <div class="form-text">Ví dụ 5 km: khách cách ≤ 5 km sẽ có phí giao tiêu chuẩn = 0đ.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Phụ phí giao nhanh</label>
                        <div class="input-group input-group-sm">
                            <input type="number" name="fast_surcharge" value="{{ $deliveryFastSurcharge }}" min="0" step="1000" class="form-control" required>
                            <span class="input-group-text">đ</span>
                        </div>
                        <div class="form-text">Cộng thêm sau phí theo km; giao tiêu chuẩn không có phụ phí.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Phạm vi nhận đơn</label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" value="≤ {{ (int) \App\Support\OrderDistancePolicy::MAX_DISTANCE_KM }} km đường bộ" disabled>
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        </div>
                        <div class="form-text">Giữ nguyên giới hạn 15 km đã chuẩn hóa ở luồng giao hàng.</div>
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                    <div>
                        <div class="fw-bold" style="font-size:.84rem;">Đơn giá theo số lượng cốc</div>
                        <div class="text-secondary" style="font-size:.7rem;">Mỗi đơn chỉ rơi vào đúng một bậc. Bậc cuối để trống giới hạn = không giới hạn.</div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-success" data-add-delivery-tier style="font-size:.74rem;"><i class="bi bi-plus-lg me-1"></i>Thêm bậc</button>
                </div>

                <div class="table-responsive border rounded-3 mb-3">
                    <table class="table delivery-tier-table mb-0" data-delivery-tier-table>
                        <thead>
                            <tr>
                                <th>Bậc số lượng</th>
                                <th>Đến ... cốc</th>
                                <th>Giá / km vượt ngưỡng</th>
                                <th class="text-center" style="width:64px;">Xóa</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($deliveryFeeTiers as $tier)
                            <tr data-delivery-tier-row>
                                <td class="delivery-tier-range" data-tier-range>{{ \App\Support\ShippingFee::tierLabel($tier) }}</td>
                                <td>
                                    <input type="number" name="tier_max[]" value="{{ $tier['max_cups'] ?? '' }}" min="1" step="1" class="form-control form-control-sm delivery-tier-input" placeholder="Không giới hạn" data-tier-max>
                                </td>
                                <td>
                                    <div class="input-group input-group-sm delivery-tier-input">
                                        <input type="number" name="tier_price[]" value="{{ (int) ($tier['price_per_km'] ?? 0) }}" min="0" step="500" class="form-control" required data-tier-price>
                                        <span class="input-group-text">đ/km</span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-remove-delivery-tier title="Xóa bậc"><i class="bi bi-trash3"></i></button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="row g-3 align-items-stretch">
                    <div class="col-lg-8">
                        <div class="delivery-fee-preview h-100">
                            <div class="fw-bold mb-2" style="font-size:.78rem;"><i class="bi bi-calculator me-1"></i>Thử nhanh công thức</div>
                            <div class="row g-2 align-items-end">
                                <div class="col-5">
                                    <label class="form-label mb-1" style="font-size:.68rem;">Khách cách quán</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" value="7.5" min="0" max="15" step="0.1" class="form-control" data-preview-distance>
                                        <span class="input-group-text">km</span>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <label class="form-label mb-1" style="font-size:.68rem;">Số cốc</label>
                                    <input type="number" value="8" min="1" step="1" class="form-control form-control-sm" data-preview-cups>
                                </div>
                                <div class="col-3 text-end">
                                    <div class="text-secondary" style="font-size:.66rem;">Phí tiêu chuẩn</div>
                                    <strong data-preview-fee>--</strong>
                                </div>
                            </div>
                            <div class="text-secondary mt-2" style="font-size:.68rem;" data-preview-formula></div>
                        </div>
                    </div>
                    <div class="col-lg-4 d-flex align-items-end justify-content-lg-end">
                        <button type="submit" class="btn btn-success w-100" style="font-size:.8rem;font-weight:700;">
                            <i class="bi bi-save2 me-1"></i>Lưu & áp dụng ngay
                        </button>
                    </div>
                </div>
            </form>
            @else
                <div class="table-responsive border rounded-3">
                    <table class="table delivery-tier-table mb-0">
                        <thead><tr><th>Số lượng</th><th>Đơn giá/km vượt ngưỡng</th></tr></thead>
                        <tbody>
                            @foreach($deliveryFeeTiers as $tier)
                                <tr><td>{{ \App\Support\ShippingFee::tierLabel($tier) }}</td><td class="fw-semibold">{{ number_format((int) ($tier['price_per_km'] ?? 0), 0, ',', '.') }}đ/km</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="text-secondary mt-2" style="font-size:.72rem;"><i class="bi bi-lock me-1"></i>Chỉ Super Admin có quyền thay đổi chính sách phí giao hàng toàn hệ thống.</div>
            @endif
        </div>
    </div>

    {{-- Filter --}}
    <form method="GET" action="{{ route('admin.staff.index') }}" class="admin-card p-3 mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-5">
                <input type="search" name="q" value="{{ $search }}" class="staff-filter w-100"
                       placeholder="Tìm theo tên hoặc email...">
            </div>
            <div class="col-md-3">
                <select name="status" class="staff-filter w-100">
                    <option value="all" @selected($status === 'all')>Tất cả trạng thái</option>
                    <option value="active" @selected($status === 'active')>Hoạt động</option>
                    <option value="locked" @selected($status === 'locked')>Đã khóa</option>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1" style="font-size:.8rem;">
                    <i class="bi bi-funnel me-1"></i>Lọc
                </button>
                <a href="{{ route('admin.staff.index') }}" class="btn btn-outline-secondary" style="font-size:.8rem;">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </div>
    </form>

    {{-- Table --}}
    <div class="admin-card overflow-hidden">
        @if($staffUsers->isEmpty())
            <div class="text-center py-5 text-secondary">
                <i class="bi bi-person-badge" style="font-size:3rem;opacity:.2;"></i>
                <p class="mt-3 mb-1 fw-semibold">Chưa có nhân viên nào</p>
                <p class="small">Nhấn <strong>Thêm nhân viên</strong> để tạo tài khoản nhân viên mới.</p>
            </div>
        @else
        <div class="table-responsive">
            <table class="table staff-table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Nhân viên</th>
                        <th>Chi nhánh</th>
                        <th class="text-center">Trạng thái</th>
                        <th class="text-center">Ngày tạo</th>
                        <th class="text-center">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($staffUsers as $staff)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="staff-avatar">{{ mb_strtoupper(mb_substr($staff->name, 0, 1)) }}</div>
                                <div>
                                    <div class="fw-semibold" style="color:var(--sp-ink);">{{ $staff->name }}</div>
                                    <small class="text-secondary">{{ $staff->email }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($staff->branch)
                                <span class="staff-badge staff-badge-branch">
                                    <i class="bi bi-building"></i>{{ $staff->branch->name }} · Home
                                </span>
                            @else
                                <span class="text-secondary small"><i class="bi bi-dash"></i> Chưa gán</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($staff->is_active)
                                <span class="staff-badge staff-badge-active"><i class="bi bi-check-circle-fill"></i>Hoạt động</span>
                            @else
                                <span class="staff-badge staff-badge-locked"><i class="bi bi-lock-fill"></i>Đã khóa</span>
                            @endif
                        </td>
                        <td class="text-center text-secondary" style="font-size:.75rem;">
                            {{ $staff->created_at?->format('d/m/Y') ?? '—' }}
                        </td>
                        <td class="text-center">
                            <div class="d-flex gap-1 justify-content-center flex-wrap">
                                {{-- Sửa --}}
                                <button type="button" class="btn btn-sm btn-outline-secondary" style="font-size:.75rem;width:35px;height:30px;padding:0;border-radius:100%;display:inline-flex;align-items:center;justify-content:center;"
                                        data-bs-toggle="modal" data-bs-target="#editStaffModal{{ $staff->id }}" title="Sửa">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                {{-- Khóa/Mở --}}
                                <form action="{{ route('admin.staff.toggle-status', $staff) }}" method="POST" class="d-inline">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-sm {{ $staff->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                            style="font-size:.75rem;width:35px;height:10px;padding:0;border-radius:100%;display:inline-flex;align-items:center;justify-content:center;"
                                            onclick="return confirm('{{ $staff->is_active ? 'Khóa' : 'Mở khóa' }} nhân viên {{ $staff->name }}?')"
                                            title="{{ $staff->is_active ? 'Khóa' : 'Mở khóa' }}">
                                        <i class="bi bi-{{ $staff->is_active ? 'lock' : 'unlock' }}"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-3 border-top">{{ $staffUsers->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>
</div>

{{-- Modals sửa nhân viên (đặt ngoài table để tránh lỗi DOM) --}}
@foreach($staffUsers as $staff)
@php $editBag = 'editStaff' . $staff->id; @endphp
<div class="modal fade" id="editStaffModal{{ $staff->id }}" tabindex="-1" aria-hidden="true"
     data-auto-open="{{ $errors->{$editBag}->any() ? 'true' : 'false' }}">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="{{ route('admin.staff.update', $staff) }}" style="border:0;border-radius:10px;">
            @csrf @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title fw-bold fs-6"><i class="bi bi-person-badge me-2 text-warning"></i>Sửa nhân viên</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Họ và tên</label>
                    <input type="text" name="name"
                           class="form-control @error('name', $editBag) is-invalid @enderror"
                           value="{{ $errors->{$editBag}->any() ? old('name') : $staff->name }}" required>
                    @error('name', $editBag)<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Email</label>
                    <input type="email" name="email"
                           class="form-control @error('email', $editBag) is-invalid @enderror"
                           value="{{ $errors->{$editBag}->any() ? old('email') : $staff->email }}" required>
                    @error('email', $editBag)<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Home branch</label>
                    @if(auth()->user()->isSuperAdmin())
                        @php $transferState = $branchTransferStates[(int)$staff->id] ?? ['allowed' => true, 'reason' => null]; @endphp
                        @if($transferState['allowed'])
                            <select name="branch_id" class="form-select @error('branch_id', $editBag) is-invalid @enderror" required>
                                @if($staff->branch && ! $staff->branch->status && ! $branches->contains('id', $staff->branch->id))
                                    <option value="{{ $staff->branch->id }}" selected>{{ $staff->branch->name }} (Ngừng hoạt động - hiện tại)</option>
                                @endif
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}" @selected($staff->branch_id == $b->id)>{{ $b->name }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">Đổi home branch chỉ có hiệu lực khi shipper không còn đơn hoặc chuyến ghép đang hoạt động.</div>
                            @error('branch_id', $editBag)<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @else
                            <input type="hidden" name="branch_id" value="{{ $staff->branch_id }}">
                            <input type="text" class="form-control" value="{{ $staff->branch?->name ?? 'Chưa gán' }}" disabled>
                            <div class="form-text text-danger"><i class="bi bi-lock me-1"></i>{{ $transferState['reason'] }}</div>
                        @endif
                    @else
                    <input type="text" class="form-control" value="{{ $staff->branch?->name ?? 'Chưa gán' }}" disabled>
                    <div class="form-text text-secondary"><i class="bi bi-lock me-1"></i>Shipper cố định theo chi nhánh. Chỉ Super Admin mới được điều chuyển.</div>
                    @endif
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label small fw-bold">Mật khẩu mới</label>
                        <input type="password" name="password"
                               class="form-control @error('password', $editBag) is-invalid @enderror">
                        @error('password', $editBag)<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">Xác nhận mật khẩu</label>
                        <input type="password" name="password_confirmation" class="form-control">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn-staff-primary btn btn-sm">
                    <i class="bi bi-save"></i> Lưu thay đổi
                </button>
            </div>
        </form>
    </div>
</div>
@endforeach

{{-- Modal tạo nhân viên --}}
<div class="modal fade" id="createStaffModal" tabindex="-1" aria-hidden="true"
     data-auto-open="{{ $errors->createStaff->any() ? 'true' : 'false' }}">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="{{ route('admin.staff.store') }}" style="border:0;border-radius:10px;">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title fw-bold fs-6"><i class="bi bi-person-plus me-2 text-warning"></i>Thêm nhân viên mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning d-flex gap-2 align-items-start" style="font-size:.76rem;border-radius:8px;">
                    <i class="bi bi-info-circle-fill mt-1 flex-shrink-0"></i>
                    <div>Nhân viên có quyền: <strong>Chat hỗ trợ</strong>, <strong>đổi trạng thái đơn hàng</strong> và <strong>đổi trạng thái đơn nhóm</strong> trong chi nhánh được gán.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Họ và tên <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name', 'createStaff') is-invalid @enderror"
                           value="{{ old('name') }}" required placeholder="Nguyễn Văn A">
                    @error('name', 'createStaff')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control @error('email', 'createStaff') is-invalid @enderror"
                           value="{{ old('email') }}" required placeholder="nhanvien@chilldrink.com">
                    @error('email', 'createStaff')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                @if(auth()->user()->isSuperAdmin())
                <div class="mb-3">
                    <label class="form-label small fw-bold">Home branch <span class="text-danger">*</span></label>
                    <select name="branch_id" class="form-select @error('branch_id', 'createStaff') is-invalid @enderror" required>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" @selected(old('branch_id') == $b->id)>{{ $b->name }}</option>
                        @endforeach
                    </select>
                    @error('branch_id', 'createStaff')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">Shipper chỉ nhận/ghép đơn của home branch này và luôn quay về đây sau khi giao xong.</div>
                </div>
                @else
                    @if(auth()->user()->branch)
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Chi nhánh</label>
                            <input type="text" class="form-control" value="{{ auth()->user()->branch->name }}" disabled>
                            <div class="form-text">Nhân viên sẽ được gán vào chi nhánh của bạn.</div>
                        </div>
                    @endif
                @endif
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label small fw-bold">Mật khẩu <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control @error('password', 'createStaff') is-invalid @enderror" required>
                        @error('password', 'createStaff')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">Xác nhận mật khẩu</label>
                        <input type="password" name="password_confirmation" class="form-control" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn-staff-primary btn btn-sm">
                    <i class="bi bi-person-badge"></i> Tạo nhân viên
                </button>
            </div>
        </form>
    </div>
</div>

@if($errors->createStaff->any())
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('createStaffModal');
        if (modal) bootstrap.Modal.getOrCreateInstance(modal).show();
    });
</script>
@endif

{{-- Fallback: mở modal nào có data-auto-open="true" (bao gồm editStaffModal khi update fail) --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.modal[data-auto-open="true"]').forEach(function (modal) {
        bootstrap.Modal.getOrCreateInstance(modal).show();
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const deliveryFeeCard = document.querySelector('[data-delivery-fee-card]');
    const deliveryFeeBody = deliveryFeeCard?.querySelector('[data-delivery-fee-body]');
    const deliveryFeeToggle = deliveryFeeCard?.querySelector('[data-delivery-fee-toggle]');
    const deliveryFeeToggleLabel = deliveryFeeCard?.querySelector('[data-delivery-fee-toggle-label]');
    if (!deliveryFeeCard || !deliveryFeeBody || !deliveryFeeToggle || !deliveryFeeToggleLabel) return;

    const shouldOpenInitially = @json($deliveryFeeBag->any());

    function setDeliveryFeeOpen(open) {
        deliveryFeeCard.classList.toggle('is-open', open);
        deliveryFeeBody.hidden = !open;
        deliveryFeeToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        deliveryFeeToggleLabel.textContent = open ? 'Thu gọn' : 'Xem';
    }

    deliveryFeeToggle.addEventListener('click', function () {
        setDeliveryFeeOpen(!deliveryFeeCard.classList.contains('is-open'));
    });

    setDeliveryFeeOpen(shouldOpenInitially);
});
</script>

@if(auth()->user()->isSuperAdmin())
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('[data-delivery-fee-form]');
    if (!form) return;

    const tbody = form.querySelector('[data-delivery-tier-table] tbody');
    const addButton = form.querySelector('[data-add-delivery-tier]');
    const freeKmInput = form.querySelector('[data-free-km]');
    const previewDistance = form.querySelector('[data-preview-distance]');
    const previewCups = form.querySelector('[data-preview-cups]');
    const previewFee = form.querySelector('[data-preview-fee]');
    const previewFormula = form.querySelector('[data-preview-formula]');

    const money = value => `${Math.max(0, Math.round(Number(value) || 0)).toLocaleString('vi-VN')}đ`;

    function rows() {
        return Array.from(tbody.querySelectorAll('[data-delivery-tier-row]'));
    }

    function recalcRanges() {
        let min = 1;
        const currentRows = rows();

        currentRows.forEach((row, index) => {
            const maxInput = row.querySelector('[data-tier-max]');
            const maxRaw = String(maxInput?.value || '').trim();
            const max = maxRaw === '' ? null : Math.max(min, parseInt(maxRaw, 10) || min);
            const label = max === null ? `Từ ${min} cốc` : `${min} - ${max} cốc`;
            row.querySelector('[data-tier-range]').textContent = label;
            if (max !== null) min = max + 1;

            // Chỉ bậc cuối được phép là "không giới hạn".
            if (maxInput) maxInput.placeholder = index === currentRows.length - 1 ? 'Không giới hạn' : `≥ ${min - 1}`;
        });

        currentRows.forEach(row => {
            const remove = row.querySelector('[data-remove-delivery-tier]');
            if (remove) remove.disabled = currentRows.length <= 1;
        });

        updatePreview();
    }

    function rateForCups(cups) {
        let min = 1;
        const currentRows = rows();
        for (let index = 0; index < currentRows.length; index++) {
            const row = currentRows[index];
            const maxRaw = String(row.querySelector('[data-tier-max]')?.value || '').trim();
            const max = maxRaw === '' ? null : Number(maxRaw);
            const rate = Number(row.querySelector('[data-tier-price]')?.value || 0);
            if (cups >= min && (max === null || cups <= max)) return { rate, min, max };
            if (max !== null) min = max + 1;
        }
        return { rate: 0, min: 1, max: null };
    }

    function updatePreview() {
        const distance = Math.max(0, Number(previewDistance?.value || 0));
        const freeKm = Math.max(0, Number(freeKmInput?.value || 0));
        const cups = Math.max(1, parseInt(previewCups?.value || '1', 10));
        const billable = Math.max(0, distance - freeKm);
        const tier = rateForCups(cups);
        const fee = billable * tier.rate;

        if (previewFee) previewFee.textContent = money(fee);
        if (previewFormula) {
            if (billable <= 0) {
                previewFormula.textContent = `${distance.toFixed(1)} km ≤ ${freeKm.toFixed(1)} km miễn phí → 0đ.`;
            } else {
                previewFormula.textContent = `(${distance.toFixed(1)} - ${freeKm.toFixed(1)}) km × ${money(tier.rate)}/km = ${money(fee)} · ${cups} cốc.`;
            }
        }
    }

    addButton?.addEventListener('click', function () {
        const currentRows = rows();
        const last = currentRows[currentRows.length - 1];
        if (!last) return;

        let previousMax = 0;
        if (currentRows.length > 1) {
            const previous = currentRows[currentRows.length - 2];
            previousMax = Number(previous.querySelector('[data-tier-max]')?.value || 0);
        }
        const lastPrice = Number(last.querySelector('[data-tier-price]')?.value || 0);
        const suggestedMax = Math.max(previousMax + 5, 5);

        const row = document.createElement('tr');
        row.setAttribute('data-delivery-tier-row', '');
        row.innerHTML = `
            <td class="delivery-tier-range" data-tier-range></td>
            <td><input type="number" name="tier_max[]" value="${suggestedMax}" min="1" step="1" class="form-control form-control-sm delivery-tier-input" data-tier-max></td>
            <td><div class="input-group input-group-sm delivery-tier-input"><input type="number" name="tier_price[]" value="${lastPrice}" min="0" step="500" class="form-control" required data-tier-price><span class="input-group-text">đ/km</span></div></td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger" data-remove-delivery-tier title="Xóa bậc"><i class="bi bi-trash3"></i></button></td>`;

        tbody.insertBefore(row, last);
        recalcRanges();
    });

    tbody.addEventListener('input', recalcRanges);
    tbody.addEventListener('click', function (event) {
        const remove = event.target.closest('[data-remove-delivery-tier]');
        if (!remove || rows().length <= 1) return;
        remove.closest('[data-delivery-tier-row]')?.remove();
        recalcRanges();
    });

    [freeKmInput, previewDistance, previewCups].forEach(input => input?.addEventListener('input', updatePreview));
    recalcRanges();

    @if($deliveryFeeBag->any())
        document.querySelector('[data-delivery-fee-card]')?.classList.add('is-open');
        document.querySelector('[data-delivery-fee-body]')?.removeAttribute('hidden');
        document.getElementById('delivery-fee-settings')?.scrollIntoView({ block: 'start' });
    @endif
});
</script>
@endif

{{-- SHIPPER_HOME_BRANCH_V26_UI: Super Admin có thể đổi Home Branch ngay, kể cả shipper đang trên đường về chi nhánh cũ. --}}
@if(auth()->user()?->isSuperAdmin())
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('select[name="branch_id"]').forEach(function (select) {
        if (!select.closest('.modal')) return;
        select.disabled = false;
        select.removeAttribute('disabled');
        select.removeAttribute('readonly');
    });

    document.querySelectorAll('.text-danger, .invalid-feedback, .form-text').forEach(function (node) {
        const text = (node.textContent || '').toLowerCase();
        if (text.includes('quay về chi nhánh') && text.includes('điều chuyển')) {
            node.remove();
        }
    });
});
</script>
@endif
@endsection
