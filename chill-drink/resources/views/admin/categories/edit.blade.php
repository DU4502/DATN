@extends('layouts.admin')

@section('page-title', 'Sửa danh mục')
@section('hide-topbar-search', true)

@section('content')
<section class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
    <div>
        <p class="admin-kicker mb-1">Danh mục</p>
        <h2 class="h2 fw-bold mb-1">Sửa danh mục</h2>
        <p class="text-secondary mb-0">Cập nhật tên, mô tả, ảnh và trạng thái hiển thị của danh mục.</p>
    </div>
    <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-primary align-self-start align-self-lg-auto">
        <i class="bi bi-arrow-left me-1"></i>Quay lại
    </a>
</section>

<section class="admin-card p-4">
    <form action="{{ route('admin.categories.update', $category) }}" method="POST" enctype="multipart/form-data" class="row g-4">
        @csrf
        @method('PUT')

        <div class="col-lg-8">
            <div class="d-flex flex-column gap-4">
                <div>
                    <label for="name" class="admin-kicker mb-2 d-block">Tên danh mục</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        class="admin-input @error('name') is-invalid @enderror"
                        value="{{ old('name', $category->name) }}"
                        required
                    >
                    @error('name')
                        <div class="text-danger small mt-2">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label for="slug" class="admin-kicker mb-2 d-block">Slug</label>
                    <input
                        type="text"
                        id="slug"
                        name="slug"
                        class="admin-input @error('slug') is-invalid @enderror"
                        value="{{ old('slug', $category->slug) }}"
                        required
                    >
                    @error('slug')
                        <div class="text-danger small mt-2">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label for="description" class="admin-kicker mb-2 d-block">Mô tả</label>
                    <textarea
                        id="description"
                        name="description"
                        rows="4"
                        class="admin-input @error('description') is-invalid @enderror"
                    >{{ old('description', $category->description) }}</textarea>
                    @error('description')
                        <div class="text-danger small mt-2">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label for="status" class="admin-kicker mb-2 d-block">Trạng thái</label>
                    <select name="status" id="status" class="admin-filter">
                        <option value="1" @selected((string) old('status', (int) $category->status) === '1')>Hiển thị</option>
                        <option value="0" @selected((string) old('status', (int) $category->status) === '0')>Ẩn</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="admin-card p-4 h-100">
                <label for="image" class="admin-kicker mb-3 d-block">Ảnh danh mục</label>
                <div id="categoryImagePreview" class="admin-form-image-preview mb-3" style="width: 100%; height: 220px;">
                    @if($category->image)
                        <img src="{{ asset('storage/' . $category->image) }}" alt="{{ $category->name }}" style="object-fit: cover !important; padding: 0;">
                    @else
                        <span class="text-secondary fw-semibold">Chưa có ảnh</span>
                    @endif
                </div>
                <input
                    type="file"
                    id="image"
                    name="image"
                    accept="image/*"
                    class="form-control @error('image') is-invalid @enderror"
                    data-image-input
                    data-preview-target="#categoryImagePreview"
                >
                @error('image')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="text-secondary d-block mt-2">Chọn ảnh mới nếu muốn thay ảnh hiện tại.</small>
            </div>
        </div>

        <div class="col-12">
            <div class="d-flex flex-wrap justify-content-end gap-2 pt-3 border-top">
                <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-primary">Hủy</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check2-circle me-1"></i>Cập nhật
                </button>
            </div>
        </div>
    </form>
</section>

<script>
    document.getElementById('name')?.addEventListener('input', function () {
        const slugInput = document.getElementById('slug');

        if (!slugInput) {
            return;
        }

        slugInput.value = this.value
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/đ/g, 'd')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    });
</script>
@endsection
