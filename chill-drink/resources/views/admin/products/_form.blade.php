@csrf

@php
    $storedGalleryImages = collect(json_decode($product->getRawOriginal('gallery_images') ?: '[]', true) ?: [])
        ->filter()
        ->values();
@endphp

<div class="row g-4">
    <div class="col-lg-8">
        <div class="admin-card card border-0">
            <div class="card-body p-4">
                <div class="mb-3">
                    <label for="name" class="form-label">Tên sản phẩm</label>
                    <input id="name" type="text" name="name" value="{{ old('name', $product->name) }}" class="form-control @error('name') is-invalid @enderror" required autofocus>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="slug" class="form-label">Đường dẫn</label>
                    <input id="slug" type="text" name="slug" value="{{ old('slug', $product->slug) }}" class="form-control @error('slug') is-invalid @enderror" placeholder="Tự tạo từ tên nếu để trống">
                    @error('slug')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Mô tả</label>
                    <textarea id="description" name="description" rows="5" class="form-control @error('description') is-invalid @enderror">{{ old('description', $product->description) }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-0">
                    <label for="image" class="form-label">Ảnh sản phẩm</label>
                    <div class="d-flex align-items-center gap-3">
                        <div id="product-image-preview" class="admin-form-image-preview">
                            @if(!empty($product->image_url))
                                <img src="{{ $product->image_url }}" alt="{{ $product->name }}">
                            @else
                                <i class="bi bi-image"></i>
                            @endif
                        </div>
                        <div class="flex-grow-1">
                            <input id="image" type="file" name="image" class="form-control @error('image') is-invalid @enderror" accept="image/jpeg,image/jpg,image/png,image/webp" data-image-input data-preview-target="#product-image-preview">
                            <small class="text-secondary d-block mt-2">Ảnh JPG, PNG hoặc WEBP. Khung xem trước giữ nhỏ để form gọn hơn.</small>
                        </div>
                    </div>
                    @error('image')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mt-4">
                    <label for="gallery_images" class="form-label">Ảnh con / gallery chi tiết</label>
                    @if($storedGalleryImages->isNotEmpty())
                        <div class="d-flex flex-wrap gap-3 mb-3">
                            @foreach($storedGalleryImages as $galleryImage)
                                @php
                                    $galleryUrl = str_starts_with($galleryImage, 'http')
                                        ? $galleryImage
                                        : \Illuminate\Support\Facades\Storage::disk('public')->url($galleryImage);
                                @endphp
                                <label class="admin-form-image-preview position-relative" style="cursor: pointer;">
                                    <img src="{{ $galleryUrl }}" alt="Ảnh con {{ $loop->iteration }}">
                                    <span class="position-absolute bottom-0 start-0 end-0 bg-white bg-opacity-90 px-2 py-1 small text-danger fw-bold">
                                        <input type="checkbox" name="remove_gallery_images[]" value="{{ $galleryImage }}" class="form-check-input me-1">
                                        Xóa
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                    <input id="gallery_images" type="file" name="gallery_images[]" class="form-control @error('gallery_images.*') is-invalid @enderror" accept="image/jpeg,image/jpg,image/png,image/webp" multiple>
                    <small class="text-secondary d-block mt-2">Có thể chọn nhiều ảnh con. Các ảnh này sẽ hiện dưới ảnh chính ở trang chi tiết sản phẩm.</small>
                    @error('gallery_images.*')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="admin-card card border-0">
            <div class="card-body p-4">
                <div class="mb-3">
                    <label for="category_id" class="form-label">Danh mục</label>
                    <select id="category_id" name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                        <option value="">Chọn danh mục</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="price" class="form-label">Giá bán</label>
                    <input id="price" type="number" name="price" value="{{ old('price', $product->price) }}" class="form-control @error('price') is-invalid @enderror" min="0" step="1000" required>
                    @error('price')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="stock" class="form-label">Tồn kho</label>
                    <input id="stock" type="number" name="stock" value="{{ old('stock', $product->stock) }}" class="form-control @error('stock') is-invalid @enderror" min="0" step="1" required>
                    @error('stock')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-check form-switch mb-4">
                    <input type="hidden" name="status" value="0">
                    <input id="status" type="checkbox" name="status" value="1" class="form-check-input" @checked(old('status', $product->status ?? true))>
                    <label for="status" class="form-check-label">Đang bán</label>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
                    <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary rounded-pill">Quay lại</a>
                </div>
            </div>
        </div>
    </div>
</div>
