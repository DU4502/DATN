@extends('layouts.admin')

@section('page-title', 'Chỉnh sửa sản phẩm')

@section('content')
<div class="admin-card card border-0">
    <div class="card-header bg-white py-3">
        <a href="{{ route('admin.products.index') }}" class="btn btn-sm btn-outline-secondary mb-3">
            ← Quay lại danh sách
        </a>
        <h2 class="h5 fw-bold mb-0">Chỉnh sửa sản phẩm</h2>
    </div>
    <div class="card-body p-4">
        <form action="{{ route('admin.products.update', $product->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            <div class="row">
                <div class="col-md-8">
                    <!-- Tên sản phẩm -->
                    <div class="mb-4">
                        <label for="name" class="form-label fw-semibold">Tên sản phẩm <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name', $product->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Danh mục -->
                    <div class="mb-4">
                        <label for="category_id" class="form-label fw-semibold">Danh mục <span class="text-danger">*</span></label>
                        <select class="form-select @error('category_id') is-invalid @enderror" 
                                id="category_id" name="category_id" required>
                            <option value="">-- Chọn danh mục --</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Mô tả -->
                    <div class="mb-4">
                        <label for="description" class="form-label fw-semibold">Mô tả sản phẩm</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" name="description" rows="4">{{ old('description', $product->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-4">
                    <!-- Ảnh sản phẩm -->
                    <div class="mb-4">
                        <label for="image" class="form-label fw-semibold">Ảnh sản phẩm</label>
                        @if($product->image)
                            <div class="mb-3">
                                <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}" 
                                     class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                            </div>
                        @endif
                        <input type="file" class="form-control @error('image') is-invalid @enderror" 
                               id="image" name="image" accept="image/jpeg,image/jpg,image/png,image/webp">
                        <small class="text-secondary">Định dạng: JPEG, JPG, PNG, WEBP. Tối đa 2MB. Để trống nếu không muốn đổi ảnh.</small>
                        @error('image')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Giá -->
                    <div class="mb-4">
                        <label for="price" class="form-label fw-semibold">Giá (VNĐ) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('price') is-invalid @enderror" 
                               id="price" name="price" value="{{ old('price', $product->price) }}" min="0" required>
                        @error('price')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Tồn kho -->
                    <div class="mb-4">
                        <label for="stock" class="form-label fw-semibold">Tồn kho <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('stock') is-invalid @enderror" 
                               id="stock" name="stock" value="{{ old('stock', $product->stock) }}" min="0" required>
                        @error('stock')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Trạng thái -->
                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="status" name="status" 
                                   value="1" {{ old('status', $product->status) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="status">
                                Hiển thị sản phẩm
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">Hủy</a>
                <button type="submit" class="btn btn-primary">Cập nhật sản phẩm</button>
            </div>
        </form>
    </div>
</div>
@endsection