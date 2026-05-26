@csrf

<div class="row g-4">
    <div class="col-lg-8">
        <div class="admin-card card border-0">
            <div class="card-body p-4">
                <div class="mb-3">
                    <label for="name" class="form-label">Tên sản phẩm</label>
                    <input id="name" type="text" name="name" value="{{ old('name', $product->name) }}" class="form-control @error('name') is-invalid @enderror" autofocus>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="slug" class="form-label">Đường dẫn</label>
                    <input id="slug" type="text" name="slug" value="{{ old('slug', $product->slug) }}" class="form-control @error('slug') is-invalid @enderror" placeholder="Để trống để tự tạo từ tên">
                    @error('slug')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Mô tả</label>
                    <textarea id="description" name="description" rows="6" class="form-control @error('description') is-invalid @enderror">{{ old('description', $product->description) }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-0">
                    <label for="image" class="form-label">Ảnh sản phẩm</label>
                    @if(!empty($product->image))
                        <div class="mb-2">
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="rounded border" style="max-height: 140px;">
                        </div>
                    @endif
                    <input id="image" type="file" name="image" class="form-control @error('image') is-invalid @enderror" accept="image/jpeg,image/jpg,image/png,image/webp">
                    <small class="text-secondary">Định dạng: JPEG, JPG, PNG, WEBP. Tối đa 2MB.</small>
                    @error('image')
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
                    <select id="category_id" name="category_id" class="form-select @error('category_id') is-invalid @enderror">
                        <option value="">Chọn danh mục</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="price" class="form-label">Giá bán</label>
                    <input id="price" type="number" name="price" value="{{ old('price', $product->price) }}" class="form-control @error('price') is-invalid @enderror">
                    @error('price')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="stock" class="form-label">Tồn kho</label>
                    <input id="stock" type="number" name="stock" value="{{ old('stock', $product->stock) }}" class="form-control @error('stock') is-invalid @enderror">
                    @error('stock')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-check form-switch mb-4">
                    <input type="hidden" name="status" value="0">
                    <input id="status" type="checkbox" name="status" value="1" class="form-check-input" @checked((bool) old('status', $product->status ?? true))>
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
