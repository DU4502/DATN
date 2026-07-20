@extends(auth()->user()?->isSuperAdmin() ? 'layouts.super-admin' : 'layouts.admin')

@section('page-title', 'Chi nhánh')

@section('content')
@php extract(require resource_path('views/partials/ui-product-data.php')); @endphp

<section class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
    <div>
        <h2 class="h2 fw-bold mb-1">Quản lý chi nhánh</h2>
        <p class="text-secondary mb-0">Quản lý danh sách chi nhánh của hệ thống.</p>
    </div>
    <button class="btn btn-primary align-self-start align-self-lg-auto" type="button" data-bs-toggle="modal" data-bs-target="#createBranchModal">
        <i class="bi bi-plus-circle me-1"></i>Thêm chi nhánh
    </button>
</section>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
    </div>
@endif

@if(request()->boolean('create_branch'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('createBranchModal');
            if (!modal || !window.bootstrap) {
                return;
            }

            bootstrap.Modal.getOrCreateInstance(modal).show();
        });
    </script>
@endif

<!-- Search & Filter Form -->
<form method="GET" action="{{ route('admin.branches.index') }}" class="mb-4">
    <div class="row g-3 align-items-end">
        <div class="col-md-6">
            <label class="admin-kicker mb-2 d-block">Tìm kiếm chi nhánh</label>
            <input class="admin-input" type="text" name="search" value="{{ $search }}" placeholder="Tìm theo tên, mã, email...">
        </div>
        <div class="col-md-3">
            <label class="admin-kicker mb-2 d-block">Trạng thái</label>
            <select class="admin-input" name="status">
                <option value="all" @selected($status === 'all')>Tất cả trạng thái</option>
                <option value="active" @selected($status === 'active')>Hoạt động</option>
                <option value="inactive" @selected($status === 'inactive')>Vô hiệu hóa</option>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button class="btn btn-primary flex-grow-1" type="submit">
                <i class="bi bi-search me-1"></i>Tìm kiếm
            </button>
            @if($search || $status !== 'all')
                <a href="{{ route('admin.branches.index') }}" class="btn btn-outline-primary">
                    <i class="bi bi-x-circle"></i>
                </a>
            @endif
        </div>
    </div>
</form>

<!-- Stats Cards -->
<section class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="admin-card admin-metric">
            <div class="d-flex justify-content-between align-items-start">
                <span class="admin-icon-dot"><i class="bi bi-shop"></i></span>
                <span class="badge badge-soft-muted">Tổng</span>
            </div>
            <div class="mt-4">
                <p class="admin-kicker mb-1">Tổng chi nhánh</p>
                <p class="admin-value mb-0">{{ $branches->total() }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="admin-card admin-metric">
            <div class="d-flex justify-content-between align-items-start">
                <span class="admin-icon-dot"><i class="bi bi-check-circle"></i></span>
                <span class="badge badge-soft-muted">Hoạt động</span>
            </div>
            <div class="mt-4">
                <p class="admin-kicker mb-1">Chi nhánh hoạt động</p>
                <p class="admin-value mb-0">{{ $branches->where('status', true)->count() }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="admin-card admin-metric">
            <div class="d-flex justify-content-between align-items-start">
                <span class="admin-icon-dot"><i class="bi bi-ban"></i></span>
                <span class="badge badge-soft-muted">Vô hiệu</span>
            </div>
            <div class="mt-4">
                <p class="admin-kicker mb-1">Chi nhánh vô hiệu hóa</p>
                <p class="admin-value mb-0">{{ $branches->where('status', false)->count() }}</p>
            </div>
        </div>
    </div>
</section>

<!-- Branches Table -->
<section class="admin-card">
    <div class="table-responsive">
        <table class="table admin-table align-middle">
            <thead>
                <tr>
                    <th>Tên chi nhánh</th>
                    <th>Mã</th>
                    <th>Email</th>
                    <th>Điện thoại</th>
                    <th>Địa chỉ</th>
                    <th>Trạng thái</th>
                    <th>Tạo lúc</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($branches as $branch)
                    <tr>
                        <td>
                            <strong>{{ $branch->name }}</strong>
                        </td>
                        <td>
                            <code class="text-muted">{{ $branch->code }}</code>
                        </td>
                        <td>
                            @if($branch->email)
                                <a href="mailto:{{ $branch->email }}" class="text-decoration-none">{{ $branch->email }}</a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($branch->phone)
                                <a href="tel:{{ $branch->phone }}" class="text-decoration-none">{{ $branch->phone }}</a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <small class="text-secondary" title="{{ $branch->address }}">
                                {{ $branch->address ? Str::limit($branch->address, 30) : '—' }}
                            </small>
                        </td>
                        <td>
                            @if($branch->status)
                                <span class="badge bg-success">Hoạt động</span>
                            @else
                                <span class="badge bg-danger">Vô hiệu hóa</span>
                            @endif
                        </td>
                        <td>
                            <small class="text-muted">{{ optional($branch->created_at)->format('d/m/Y H:i') ?? '-' }}</small>
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <form method="POST" action="{{ route('admin.branches.toggle-status', ['branch' => $branch->id], false) }}" style="display: inline;">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-{{ $branch->status ? 'warning' : 'success' }}" title="{{ $branch->status ? 'Vô hiệu hóa' : 'Kích hoạt' }}">
                                        <i class="bi bi-{{ $branch->status ? 'lock' : 'unlock' }}"></i>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.branches.destroy', ['branch' => $branch->id], false) }}" style="display: inline;" onsubmit="return confirm('Bạn chắc chắn muốn xóa chi nhánh này?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Xóa">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="bi bi-inbox" style="font-size: 2rem; opacity: 0.5;"></i>
                            <p class="mt-2 mb-0">Không có chi nhánh nào. Hãy thêm chi nhánh mới!</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($branches->hasPages())
        <nav aria-label="Page navigation" class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
            <small class="text-muted">
                Hiển thị {{ $branches->firstItem() ?? 0 }}-{{ $branches->lastItem() ?? 0 }} / {{ $branches->total() }}
            </small>
            <ul class="pagination mb-0">
                @if($branches->onFirstPage())
                    <li class="page-item disabled"><span class="page-link">← Trước</span></li>
                @else
                    <li class="page-item"><a class="page-link" href="{{ $branches->previousPageUrl() }}">← Trước</a></li>
                @endif

                @foreach($branches->getUrlRange(1, $branches->lastPage()) as $page => $url)
                    @if($page === $branches->currentPage())
                        <li class="page-item active"><span class="page-link">{{ $page }}</span></li>
                    @else
                        <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                    @endif
                @endforeach

                @if($branches->hasMorePages())
                    <li class="page-item"><a class="page-link" href="{{ $branches->nextPageUrl() }}">Sau →</a></li>
                @else
                    <li class="page-item disabled"><span class="page-link">Sau →</span></li>
                @endif
            </ul>
        </nav>
    @endif
</section>

<!-- Create Branch Modal -->
<div class="modal fade" id="createBranchModal" tabindex="-1" aria-labelledby="createBranchModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="createBranchModalLabel">Thêm chi nhánh mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <form method="POST" action="{{ route('admin.branches.store', [], false) }}">
                @csrf
                <input type="hidden" name="form_type" value="branch">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="admin-kicker mb-2 d-block">Tên chi nhánh <span class="text-danger">*</span></label>
                        <input class="admin-input @error('name', 'createBranch') is-invalid @enderror" type="text" name="name" value="{{ old('name') }}" placeholder="Nhập tên chi nhánh" required>
                        @error('name', 'createBranch')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="admin-kicker mb-2 d-block">Mã chi nhánh <span class="text-danger">*</span></label>
                        <input class="admin-input @error('code', 'createBranch') is-invalid @enderror" type="text" name="code" value="{{ old('code') }}" placeholder="Ví dụ: HN01, HCM01" required>
                        @error('code', 'createBranch')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="admin-kicker mb-2 d-block">Email</label>
                                <input class="admin-input @error('email', 'createBranch') is-invalid @enderror" type="email" name="email" value="{{ old('email') }}" placeholder="branch@example.com">
                                @error('email', 'createBranch')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="admin-kicker mb-2 d-block">Điện thoại</label>
                                <input class="admin-input @error('phone', 'createBranch') is-invalid @enderror" type="text" name="phone" value="{{ old('phone') }}" placeholder="0123456789">
                                @error('phone', 'createBranch')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="admin-kicker mb-2 d-block">Địa chỉ</label>
                        <textarea class="admin-input @error('address', 'createBranch') is-invalid @enderror" name="address" rows="2" placeholder="Nhập địa chỉ chi nhánh">{{ old('address') }}</textarea>
                        @error('address', 'createBranch')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    @include('admin.partials.location-picker', [
                        'pickerId' => 'create-branch-location-picker',
                        'label' => 'Vị trí chi nhánh',
                        'hint' => 'Nhấn vào bản đồ để đặt vị trí, hoặc bấm lấy vị trí hiện tại.',
                        'latValue' => old('latitude'),
                        'lngValue' => old('longitude'),
                        'addressTarget' => 'textarea[name="address"]',
                    ])

                    <div class="form-check my-3">
                        <input class="form-check-input" type="checkbox" name="status" value="1" id="createStatus" checked>
                        <label class="form-check-label fw-bold text-dark" for="createStatus">
                            Kích hoạt chi nhánh ngay
                        </label>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold" style="background:#0D9373; border-color:#0D9373;">
                        <i class="bi bi-check-circle me-1"></i>Thêm chi nhánh
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if(old('form_type') === 'branch')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('createBranchModal')).show();
    });
</script>
@endif

@include('admin.partials.location-picker-script')

@endsection
