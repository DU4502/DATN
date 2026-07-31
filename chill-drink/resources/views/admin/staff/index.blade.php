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
</style>

<div class="staff-page">
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
                                    <i class="bi bi-building"></i>{{ $staff->branch->name }}
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
                                {{-- Xóa --}}
                                <form action="{{ route('admin.staff.destroy', $staff) }}" method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                            style="font-size:.75rem;width:35px;height:10px;padding:0;border-radius:100%;display:inline-flex;align-items:center;justify-content:center;"
                                            onclick="return confirm('Xóa vĩnh viễn nhân viên {{ $staff->name }}?')"
                                            title="Xóa">
                                        <i class="bi bi-trash"></i>
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
                    <label class="form-label small fw-bold">Chi nhánh</label>
                    @if(auth()->user()->isSuperAdmin())
                    <select name="branch_id" class="form-select">
                        <option value="">-- Chưa gán --</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" @selected($staff->branch_id == $b->id)>{{ $b->name }}</option>
                        @endforeach
                    </select>
                    @else
                    <input type="text" class="form-control" value="{{ $staff->branch?->name ?? 'Chưa gán' }}" disabled>
                    <div class="form-text text-secondary"><i class="bi bi-lock me-1"></i>Chỉ Super Admin mới có thể thay đổi chi nhánh.</div>
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
                    <label class="form-label small fw-bold">Chi nhánh phụ trách</label>
                    <select name="branch_id" class="form-select">
                        <option value="">-- Chưa gán chi nhánh --</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" @selected(old('branch_id') == $b->id)>{{ $b->name }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">Nhân viên chỉ thấy đơn hàng của chi nhánh được gán.</div>
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
@endsection
