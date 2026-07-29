@extends(auth()->user()?->isSuperAdmin() ? 'layouts.super-admin' : 'layouts.admin')

@section('page-title', 'Quản lý trình chiếu')

@section('content')
@php
    $viewer = auth()->user();
@endphp

<section class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
    <div>
        <h2 class="h2 fw-bold mb-1">Quản lý trình chiếu chi nhánh</h2>
        @if($branch)
            <p class="text-secondary mb-0">Đang quản lý slide cho chi nhánh: <strong class="text-dark">{{ $branch->name }}</strong></p>
        @else
            <p class="text-secondary mb-0">Thiết lập slide hiển thị trang chủ của từng chi nhánh.</p>
        @endif
    </div>
    
    <div class="d-flex align-items-center gap-3">
        @if($viewer->isSuperAdmin() && $branches->isNotEmpty())
            <div class="d-flex align-items-center gap-2">
                <span class="admin-kicker mb-0 text-nowrap">Chọn chi nhánh:</span>
                <form action="{{ route('admin.slides.index') }}" method="GET" class="m-0">
                    <select name="branch_id" class="form-select" onchange="this.form.submit()" style="min-width: 220px;">
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ $selectedBranchId == $b->id ? 'selected' : '' }}>
                                {{ $b->name }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
        @endif
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createSlideModal">
            <i class="bi bi-plus-lg me-1"></i>Tạo mới
        </button>
        <a href="{{ route('admin.slides.trash', ['branch_id' => $selectedBranchId]) }}" class="btn btn-outline-secondary">
            <i class="bi bi-trash me-1"></i>Thùng rác
        </a>
    </div>
</section>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
    </div>
@endif

{{-- MODAL CREATE NEW SLIDE --}}
<div class="modal fade" id="createSlideModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-bottom-0 pb-0">
                <h4 class="modal-title fw-bold text-dark match-modal-title">Tạo Slide mới</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <form action="{{ route('admin.slides.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @if($viewer->isSuperAdmin())
                    <input type="hidden" name="branch_id" value="{{ $selectedBranchId }}">
                @endif
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Tên sản phẩm *</label>
                            <input type="text" name="product_name" class="form-control" placeholder="Ví dụ: TRÀ ĐÀO CAM SẢ" value="{{ old('product_name') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tiêu đề slide *</label>
                            <input type="text" name="title" class="form-control" placeholder="Ví dụ: HƯƠNG VỊ THANH NGỌT SẢNG KHOÁI" value="{{ old('title') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Giá thành *</label>
                            <input type="text" name="price" class="form-control" placeholder="Ví dụ: 85.000₫" value="{{ old('price') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Màu nền slide *</label>
                            <input type="color" name="bg_color" class="form-control form-control-color w-100" style="height: 40px; padding: 2px;" value="{{ old('bg_color', '#0d9373') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Thứ tự hiển thị *</label>
                            <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', '0') }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Nội dung mô tả *</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Ghi mô tả chi tiết của món nước uống này..." required>{{ old('description') }}</textarea>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Ảnh sản phẩm *</label>
                            <input type="file" name="image" class="form-control" required>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActiveCreateSwitch" checked>
                                <label class="form-check-label fw-bold text-dark" for="isActiveCreateSwitch">Bật hiển thị</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Hủy bỏ</button>
                    <button type="submit" class="btn btn-primary px-4">Tạo slide mới</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
    </div>
@endif

<div class="row g-4">
    {{-- LIST SLIDES --}}
    <div class="col-12">
        <div class="admin-card">
            <h3 class="h5 fw-bold mb-3">Danh sách Slide hiện có</h3>
            <div class="table-responsive">
                <table class="table admin-table align-middle">
                    <thead>
                        <tr>
                            <th>Ảnh</th>
                            <th>Món / Tiêu đề</th>
                            <th>Giá</th>
                            <th>Màu Nền / Mô Tả</th>
                            <th class="text-center">Thứ tự</th>
                            <th class="text-center">Trạng thái</th>
                            <th class="text-end">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($slides as $slide)
                            @php
                                $imgUrl = str_starts_with($slide->image, '/') ? $slide->image : asset('storage/' . $slide->image);
                            @endphp
                            <tr>
                                <td>
                                    <div class="rounded p-1 text-center" style="background-color: {{ $slide->bg_color }}; width: 62px; height: 62px;">
                                        <img src="{{ $imgUrl }}" alt="{{ $slide->product_name }}" style="width: 100%; height: 100%; object-fit: contain;">
                                    </div>
                                </td>
                                <td>
                                    <strong class="d-block text-dark">{{ $slide->product_name }}</strong>
                                    <small class="text-secondary d-block text-truncate" style="max-width: 180px;">{{ $slide->title }}</small>
                                </td>
                                <td class="text-primary fw-bold">{{ $slide->price }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="d-inline-block rounded-circle border" style="width: 14px; height: 14px; background-color: {{ $slide->bg_color }};"></span>
                                        <small class="text-secondary" style="font-family: monospace;">{{ $slide->bg_color }}</small>
                                    </div>
                                    <small class="text-muted d-block text-truncate" style="max-width: 220px;" title="{{ $slide->description }}">
                                        {{ $slide->description }}
                                    </small>
                                </td>
                                <td class="text-center fw-bold">{{ $slide->sort_order }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $slide->is_active ? 'badge-soft-primary' : 'badge-soft-muted' }}">
                                        {{ $slide->is_active ? 'Bật' : 'Tắt' }}
                                    </span>
                                </td>
                                <td class="text-end text-nowrap">
                                    {{-- Nút sửa --}}
                                    <button class="admin-action btn btn-link text-decoration-none" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editSlideModal-{{ $slide->id }}"
                                            title="Sửa Slide">
                                        <i class="bi bi-pencil"></i>
                                    </button>

                                    {{-- Form xóa --}}
                                    <form action="{{ route('admin.slides.destroy', $slide) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa slide này?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="admin-action btn btn-link text-decoration-none text-danger" title="Xóa Slide">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>

                            {{-- MODAL EDIT SLIDE --}}
                            <div class="modal fade" id="editSlideModal-{{ $slide->id }}" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                    <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                                        <div class="modal-header border-bottom-0 pb-0">
                                            <h4 class="modal-title fw-bold text-dark match-modal-title">Sửa Slide sản phẩm</h4>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                                        </div>
                                        <form action="{{ route('admin.slides.update', $slide) }}" method="POST" enctype="multipart/form-data">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-body p-4">
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label">Tên sản phẩm *</label>
                                                        <input type="text" name="product_name" class="form-control" value="{{ old('product_name', $slide->product_name) }}" required>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Tiêu đề slide *</label>
                                                        <input type="text" name="title" class="form-control" value="{{ old('title', $slide->title) }}" required>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Giá thành *</label>
                                                        <input type="text" name="price" class="form-control" value="{{ old('price', $slide->price) }}" placeholder="Ví dụ: 85.000₫" required>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Màu nền slide *</label>
                                                        <input type="color" name="bg_color" class="form-control form-control-color w-100" style="height: 40px; padding: 2px;" value="{{ old('bg_color', $slide->bg_color) }}" required>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Thứ tự hiển thị *</label>
                                                        <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $slide->sort_order) }}" required>
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label">Nội dung mô tả *</label>
                                                        <textarea name="description" class="form-control" rows="3" required>{{ old('description', $slide->description) }}</textarea>
                                                    </div>
                                                    <div class="col-md-8">
                                                        <label class="form-label">Thay ảnh sản phẩm mới (không chọn sẽ giữ cũ)</label>
                                                        <input type="file" name="image" class="form-control">
                                                    </div>
                                                    <div class="col-md-4 d-flex align-items-end">
                                                        <div class="form-check form-switch mb-2">
                                                            <input class="form-check-input" type="checkbox" name="is_active" id="activeEditSwitch-{{ $slide->id }}" value="1" {{ $slide->is_active ? 'checked' : '' }}>
                                                            <label class="form-check-label fw-bold text-dark" for="activeEditSwitch-{{ $slide->id }}">Bật hiển thị</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-top-0 pt-0">
                                                <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Hủy bỏ</button>
                                                <button type="submit" class="btn btn-primary px-4">Cập nhật slide</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-secondary py-5">
                                    <div class="fw-bold text-dark mb-1">Chưa có slide nào</div>
                                    <div>Hãy tạo slide cho chi nhánh để thiết lập slider trang chủ động.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
