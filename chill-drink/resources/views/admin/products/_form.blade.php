@csrf
@php
    $sizes = $sizes ?? collect();
    $sizePriceMap = $sizePriceMap ?? [];
    $basePrice = (float) old('price', $product->price ?? 0);
@endphp

<div class="row g-4">
    <div class="col-lg-8">
        <div class="admin-card card border-0">
            <div class="card-body p-4">
                <div class="mb-3">
                    <label for="name" class="form-label">Tên sản phẩm</label>
                    <input id="name" type="text" name="name" value="{{ old('name', $product->name) }}" class="form-control" autofocus>
                </div>

                <div class="mb-3">
                    <label for="slug" class="form-label">Đường dẫn</label>
                    <input id="slug" type="text" name="slug" value="{{ old('slug', $product->slug) }}" class="form-control" placeholder="Để trống để tự tạo từ tên">
                </div>

                <div class="mb-0">
                    <label for="description" class="form-label">Mô tả</label>
                    <textarea id="description" name="description" rows="6" class="form-control">{{ old('description', $product->description) }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="admin-card card border-0">
            <div class="card-body p-4">
                <div class="mb-3">
                    <label for="category_id" class="form-label">Danh mục</label>
                    <select id="category_id" name="category_id" class="form-select">
                        <option value="">Chọn danh mục</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label for="price" class="form-label">Giá bán</label>
<<<<<<< Updated upstream
                    <input id="price" type="number" name="price" value="{{ old('price', $product->price) }}" class="form-control">
                </div>

                <div class="mb-3">
                    <label for="stock" class="form-label">Tồn kho</label>
                    <input id="stock" type="number" name="stock" value="{{ old('stock', $product->stock) }}" class="form-control">
                </div>
=======
                    <input id="price" type="number" name="price" step="1000" min="0" value="{{ old('price', $product->price) }}" class="form-control @error('price') is-invalid @enderror">
                    @error('price')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                @if($sizes->isNotEmpty())
                    <div class="mb-3">
                        <label class="form-label d-block mb-2">Giá theo size</label>
                        <div class="row g-2">
                            @foreach($sizes as $size)
                                @php
                                    $normalizedSizeCode = mb_strtoupper(trim((string) preg_replace('/^size\s*/i', '', (string) $size->name)));
                                    $fallbackPrice = match ($normalizedSizeCode) {
                                        'M' => (int) $basePrice + 5000,
                                        'L' => (int) $basePrice + 10000,
                                        default => (int) $basePrice,
                                    };
                                    $sizeValue = old("size_prices.$size->id", $sizePriceMap[$size->id] ?? $fallbackPrice);
                                    $sizeName = trim((string) $size->name);
                                    $sizeLabel = str_starts_with(mb_strtolower($sizeName), 'size') ? $sizeName : 'Size '.$sizeName;
                                @endphp
                                <div class="col-12">
                                    <label for="size_price_{{ $size->id }}" class="form-label small mb-1">{{ $sizeLabel }}</label>
                                    <input
                                        id="size_price_{{ $size->id }}"
                                        type="number"
                                        min="0"
                                        step="1000"
                                        name="size_prices[{{ $size->id }}]"
                                        value="{{ $sizeValue }}"
                                        class="form-control @error("size_prices.$size->id") is-invalid @enderror"
                                    >
                                    @error("size_prices.$size->id")
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endforeach
                        </div>
                        @error('size_prices')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                @endif
>>>>>>> Stashed changes

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
