@extends('layouts.admin')

@section('page-title', 'Thêm sản phẩm')

@section('content')
<div class="admin-card card border-0">
    <div class="card-header bg-white py-3">
        <a href="{{ route('admin.products.index') }}" class="btn btn-sm btn-outline-secondary mb-3">
            ← Quay lại danh sách
        </a>
        <h2 class="h5 fw-bold mb-0">Thêm sản phẩm mới</h2>
    </div>
    <div class="card-body p-4">
        <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="row">
                <div class="col-md-8">
                    <div class="mb-4">
                        <label for="name" class="form-label fw-semibold">Tên sản phẩm <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                            id="name" name="name" value="{{ old('name') }}" required>
                        @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="category_id" class="form-label fw-semibold">Danh mục <span class="text-danger">*</span></label>
                        <select class="form-select @error('category_id') is-invalid @enderror"
                            id="category_id" name="category_id" required>
                            <option value="">-- Chọn danh mục --</option>
                            @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('category_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="description" class="form-label fw-semibold">Mô tả sản phẩm</label>
                        <textarea class="form-control @error('description') is-invalid @enderror"
                            id="description" name="description" rows="4">{{ old('description') }}</textarea>
                        @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mb-4">
                        <label for="image" class="form-label fw-semibold">Ảnh sản phẩm</label>
                        <input type="file" class="form-control @error('image') is-invalid @enderror"
                            id="image" name="image" accept="image/jpeg,image/jpg,image/png,image/webp">
                        <small class="text-secondary">Định dạng: JPEG, JPG, PNG, WEBP. Tối đa 2MB.</small>
                        @error('image')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="price" class="form-label fw-semibold">Giá (VNĐ) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('price') is-invalid @enderror"
                            id="price" name="price" value="{{ old('price') }}" min="0" required>
                        @error('price')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="stock" class="form-label fw-semibold">Tồn kho <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('stock') is-invalid @enderror"
                            id="stock" name="stock" value="{{ old('stock', 0) }}" min="0" required>
                        @error('stock')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="status" name="status"
                                value="1" {{ old('status', true) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="status">
                                Hiển thị sản phẩm
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">Hủy</a>
                <button type="submit" class="btn btn-primary">Thêm sản phẩm</button>
            </div>
        </form>
    </div>
</div>
@endsection