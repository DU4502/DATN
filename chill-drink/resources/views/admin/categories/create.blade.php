@extends('layouts.admin')

@section('page-title', 'Thêm danh mục mới')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 class="h4 fw-bold mb-1">Thêm danh mục mới</h2>
            <p class="text-secondary mb-0">Tạo nhóm sản phẩm mới cho cửa hàng của bạn.</p>
        </div>
        <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Quay lại danh sách
        </a>
    </div>

    <div class="row">
        <div class="col-md-8 col-lg-6">
            <div class="card border-0 shadow-sm admin-card">
                <div class="card-body p-4">
                    <form action="{{ route('admin.categories.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-4">
                            <label for="name" class="form-label fw-bold text-dark">Tên danh mục <span class="text-danger">*</span></label>
                            <input type="text"
                                   class="form-control @error('name') is-invalid @enderror"
                                   id="name"
                                   name="name"
                                   value="{{ old('name') }}"
                                   placeholder="Ví dụ: Cà phê, Trà sữa..."
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted d-block mt-1">Slug sẽ được tự động tạo từ tên này.</small>
                        </div>

                        <div class="mb-4">
                            <label for="description" class="form-label fw-bold text-dark">Mô tả</label>
                            <textarea
                                id="description"
                                name="description"
                                rows="3"
                                class="form-control @error('description') is-invalid @enderror"
                                placeholder="Mô tả ngắn về nhóm đồ uống này"
                            >{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="image" class="form-label fw-bold text-dark">Ảnh danh mục</label>
                            <input
                                id="image"
                                type="file"
                                name="image"
                                accept="image/*"
                                class="form-control @error('image') is-invalid @enderror"
                            >
                            @error('image')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted d-block mt-1">Hỗ trợ JPG, PNG, WEBP, GIF. Dung lượng tối đa 2MB.</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark d-block">Trạng thái hiển thị</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input"
                                       type="checkbox"
                                       id="status"
                                       name="status"
                                       value="1"
                                       {{ old('status', '1') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label text-secondary" for="status">
                                    Cho phép hiển thị danh mục này ngoài cửa hàng
                                </label>
                            </div>
                        </div>

                        <hr class="my-4 text-faded">

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="reset" class="btn btn-light px-4">Hủy nhập</button>
                            <button type="submit" class="btn btn-primary px-5 fw-bold">
                                <i class="bi bi-save me-1"></i> Lưu danh mục
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="alert alert-info border-0 shadow-sm">
                <h5 class="h6 fw-bold"><i class="bi bi-info-circle-fill me-2"></i> Lưu ý:</h5>
                <ul class="small mb-0 ps-3">
                    <li class="mb-2"><b>Tên danh mục:</b> Nên ngắn gọn (dưới 50 ký tự) và mang tính bao quát.</li>
                    <li class="mb-2"><b>Slug:</b> Hệ thống tự sinh URL thân thiện ví dụ "Ca phê" -> "ca-phe".</li>
                    <li><b>Trạng thái:</b> Nếu bạn tắt trạng thái, khách hàng sẽ không thấy danh mục này và các sản phẩm bên trong nó.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
