@extends(auth()->user()?->isSuperAdmin() ? 'layouts.super-admin' : 'layouts.admin')

@section('title', 'Thêm sản phẩm mới')
@section('page-title', 'Thêm sản phẩm')

@section('content')
<style>
    .form-card {
        background: #ffffff;
        border-radius: 14px;
        border: 1px solid #e5e7eb;
        overflow: hidden;
        margin-bottom: 1.5rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    }

    .form-card-header {
        background: #f0f7ff;
        padding: 0.85rem 1.25rem;
        border-bottom: 1px solid #e0edfb;
        display: flex;
        align-items: center;
        gap: 0.6rem;
        color: #1e3a8a;
        font-weight: 700;
        font-size: 0.95rem;
    }

    .form-card-body {
        padding: 1.25rem;
    }

    .custom-check-green:checked {
        background-color: #0D9373 !important;
        border-color: #0D9373 !important;
    }

    .size-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 0.85rem 1rem;
        transition: all 0.2s ease;
    }

    .size-card.active {
        border-color: #cbd5e1;
    }

    .size-input-group .input-group-text {
        background: #f0f7ff;
        border-color: #dbeafe;
        color: #3b82f6;
        font-weight: 600;
        font-size: 0.85rem;
    }

    .size-input-group .form-control {
        background: #f0f7ff;
        border-color: #dbeafe;
        font-size: 0.88rem;
    }

    .size-input-group .form-control::placeholder {
        color: #94a3b8;
    }

    .topping-chip {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 0.6rem 0.85rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: all 0.2s ease;
        cursor: pointer;
    }

    .topping-chip:hover {
        border-color: #0D9373;
        background: #f0fdf4;
    }

    .topping-chip .form-check-input:checked~.topping-label {
        font-weight: 700;
        color: #064e3b;
    }

    .dropzone-box {
        border: 2px dashed #cbd5e1;
        background: #f8fafc;
        border-radius: 12px;
        padding: 1.25rem 1rem;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s ease;
        position: relative;
        min-height: 140px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .dropzone-box:hover {
        border-color: #0D9373;
        background: #f0fdf4;
    }

    .thumb-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 0.6rem;
        margin-top: 0.75rem;
    }

    .thumb-slot {
        width: 72px;
        height: 72px;
        aspect-ratio: 1;
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #94a3b8;
        font-size: 1.2rem;
        cursor: pointer;
        position: relative;
        overflow: hidden;
        transition: all 0.2s ease;
        flex-shrink: 0;
    }

    .thumb-slot:hover {
        border-color: #0D9373;
        color: #0D9373;
        background: #f0fdf4;
    }

    .thumb-slot img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .delete-img-btn {
        position: absolute;
        top: 3px;
        right: 3px;
        width: 20px;
        height: 20px;
        background: #ef4444;
        color: #ffffff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
        cursor: pointer;
        border: none;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        z-index: 10;
        transition: transform 0.15s ease;
    }

    .delete-img-btn:hover {
        transform: scale(1.15);
        background: #dc2626;
    }
</style>

<div class="mb-4">
    <a href="{{ route('admin.products.index') }}" class="text-secondary text-decoration-none small fw-semibold">
        ← Quay lại danh sách
    </a>
    <h1 class="h4 fw-bold mb-0 mt-1" style="color: #0f172a;">Thêm sản phẩm mới</h1>
</div>

<form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data" id="createProductForm">
    @csrf
    <input type="hidden" name="branch_statuses_submitted" value="1">

    @if ($errors->any())
    <div class="alert alert-danger rounded-3 mb-4 shadow-sm">
        <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Vui lòng kiểm tra các lỗi sau:</div>
        <ul class="mb-0 ps-3">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="row g-4">
        <!-- Cột Trái -->
        <div class="col-lg-8">
            <!-- 1. Thông tin sản phẩm -->
            <div class="form-card">
                <div class="form-card-header">
                    <i class="bi bi-info-circle text-primary fs-5"></i>
                    <span>Thông tin sản phẩm</span>
                </div>
                <div class="form-card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label small fw-bold">Tên sản phẩm <span class="text-danger">*</span></label>
                            <input type="text" class="form-control rounded-3 @error('name') is-invalid @enderror"
                                id="name" name="name" value="{{ old('name') }}" placeholder="Ví dụ: Trà Sữa Ô Long Nướng..." required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="category_id" class="form-label small fw-bold">Danh mục <span class="text-danger">*</span></label>
                            <select class="form-select rounded-3 @error('category_id') is-invalid @enderror"
                                id="category_id" name="category_id" required>
                                <option value="">-- Chọn danh mục --</option>
                                @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="description" class="form-label small fw-bold">Mô tả ngắn</label>
                        <textarea class="form-control rounded-3 @error('description') is-invalid @enderror"
                            id="description" name="description" rows="2" placeholder="Mô tả hương vị, thành phần...">{{ old('description') }}</textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

            <!-- 2. Kích thước & Giá (Chỉ hiển thị Size M và Size L với Validation trực tiếp) -->
            <div class="form-card">
                <div class="form-card-header">
                    <i class="bi bi-aspect-ratio text-primary fs-5"></i>
                    <span>Kích thước & Giá cộng thêm</span>
                </div>
                <div class="form-card-body">
                    <div class="row g-3">
                        @foreach($allSizes as $size)
                        @if(strtoupper(trim($size->name)) !== 'S')
                        @php $sName = strtoupper(trim($size->name)); @endphp
                        <div class="col-sm-6">
                            <div class="size-card active">
                                <div class="form-check mb-2.5">
                                    <input class="form-check-input custom-check-green size-checkbox" type="checkbox" name="sizes[]" value="{{ $size->id }}" id="size_{{ $size->id }}" data-size-name="{{ $sName }}" {{ old('sizes') ? (in_array($size->id, old('sizes', [])) ? 'checked' : '') : 'checked' }}>
                                    <label class="form-check-label fw-bold text-dark fs-6" for="size_{{ $size->id }}">
                                        Size {{ $size->name }}
                                    </label>
                                </div>
                                <div class="input-group input-group-sm size-input-group">
                                    <input type="number"
                                        name="size_prices[{{ $size->id }}]"
                                        id="size_price_input_{{ $sName }}"
                                        class="form-control size-price-field rounded-start-2"
                                        data-size-name="{{ $sName }}"
                                        placeholder="Giá cộng thêm Size {{ $size->name }}"
                                        min="0"
                                        step="1000"
                                        value="{{ old('size_prices.'.$size->id, $sName === 'M' ? 5000 : 10000) }}">
                                    <span class="input-group-text rounded-end-2">đ</span>
                                </div>
                                <div class="text-danger small mt-1 fw-bold size-error-msg" id="size_error_{{ $sName }}" style="display:none;"></div>
                            </div>
                        </div>
                        @endif
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- 3. Topping khả dụng (Mặc định CHƯA TÍCH) -->
            <div class="form-card">
                <div class="form-card-header">
                    <i class="bi bi-stars text-success fs-5"></i>
                    <span class="text-success">Topping khả dụng</span>
                </div>
                <div class="form-card-body">
                    <div class="row g-2">
                        @forelse($allToppings as $topping)
                        <div class="col-6 col-md-3">
                            <label class="topping-chip w-100 mb-0" for="topping_{{ $topping->id }}">
                                <div class="d-flex align-items-center gap-2">
                                    <input class="form-check-input custom-check-green mt-0" type="checkbox" name="toppings[]" value="{{ $topping->id }}" id="topping_{{ $topping->id }}" {{ old('toppings') && in_array($topping->id, old('toppings', [])) ? 'checked' : '' }}>
                                    <span class="topping-label small text-truncate" style="max-width: 85px;">{{ $topping->name }}</span>
                                </div>
                                <span class="text-success small fw-bold">+{{ (int)$topping->price >= 1000 ? (int)($topping->price/1000).'k' : number_format($topping->price).'đ' }}</span>
                            </label>
                        </div>
                        @empty
                        <div class="col-12 text-secondary small py-2">Chưa có Topping nào. Hãy thêm Topping ở trang Quản lý Topping.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="form-card">
                <div class="form-card-header">
                    <i class="bi bi-shop text-primary fs-5"></i>
                    <span>Trạng thái sản phẩm theo chi nhánh</span>
                </div>
                <div class="form-card-body">
                    <p class="text-secondary small">Chỉ các chi nhánh được chọn mới phục vụ sản phẩm này.</p>
                    <div class="row g-2">
                        @foreach($branches as $branch)
                            @php
                                $oldStatuses = old('branch_statuses', []);
                                $selected = array_key_exists((string) $branch->id, $oldStatuses)
                                    || (! auth()->user()->isSuperAdmin() && (int) auth()->user()->branch_id === (int) $branch->id && old('branch_statuses') === null);
                                $available = (bool) ($oldStatuses[$branch->id] ?? true);
                            @endphp
                            <div class="col-md-6">
                                <div class="border rounded-3 p-3" data-branch-status-row>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input custom-check-green" type="checkbox" id="branch_{{ $branch->id }}" data-branch-status-toggle @checked($selected)>
                                        <label class="form-check-label fw-bold" for="branch_{{ $branch->id }}">{{ $branch->name }}</label>
                                    </div>
                                    <select class="form-select form-select-sm" name="branch_statuses[{{ $branch->id }}]" data-branch-status-select @disabled(! $selected)>
                                        <option value="1" @selected($available)>Còn hàng</option>
                                        <option value="0" @selected(! $available)>Hết hàng</option>
                                    </select>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Cột Phải -->
        <div class="col-lg-4">
            <!-- 1. Giá & Trạng thái -->
            <div class="form-card">
                <div class="form-card-header">
                    <i class="bi bi-cash-stack text-success fs-5"></i>
                    <span>Giá & Trạng thái</span>
                </div>
                <div class="form-card-body">
                    <div class="mb-3">
                        <label for="price" class="form-label small fw-bold">Giá bán cơ sở (VNĐ) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" class="form-control rounded-start-3 @error('price') is-invalid @enderror"
                                id="price" name="price" value="{{ old('price') }}" min="0" step="1000" placeholder="VD: 35000" required>
                            <span class="input-group-text rounded-end-3 bg-light fw-bold text-secondary">VNĐ</span>
                        </div>
                        @error('price') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="p-3 rounded-3 d-flex align-items-center justify-content-between" style="background: #f0f7ff; border: 1px solid #dbeafe;">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-eye text-primary"></i>
                            <span class="fw-bold small text-dark">Hiển thị món này</span>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input type="hidden" name="status" value="0">
                            <input class="form-check-input custom-check-green" type="checkbox" role="switch" id="status" name="status"
                                value="1" {{ old('status', true) ? 'checked' : '' }} style="cursor: pointer; width: 2.5em; height: 1.25em;">
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Hình ảnh -->
            <div class="form-card">
                <div class="form-card-header">
                    <i class="bi bi-image text-success fs-5"></i>
                    <span>Hình ảnh</span>
                </div>
                <div class="form-card-body">
                    <!-- Ảnh đại diện chính -->
                    <div class="mb-3">
                        <input type="file" class="d-none @error('image') is-invalid @enderror"
                            id="image" name="image" accept="image/jpeg,image/jpg,image/png,image/webp">

                        <div class="dropzone-box w-100" id="mainImageDropzone" style="border: 2px dashed #cbd5e1; background: #f8fafc; border-radius: 12px; padding: 1.5rem; text-align: center; cursor: pointer; min-height: 140px;">
                            <div id="mainImageEmptyState" class="d-flex flex-column align-items-center justify-content-center py-2">
                                <i class="bi bi-cloud-arrow-up" style="font-size: 2.5rem; color: #0D9373;"></i>
                                <span class="fw-bold text-dark mt-1" style="font-size: 0.95rem;">Ảnh đại diện</span>
                                <span class="text-secondary small" style="font-size: 0.8rem;">Max 2MB</span>
                            </div>
                            <div id="mainImagePreviewState" class="d-none w-100 h-100 position-relative d-flex align-items-center justify-content-center">
                                <img id="mainImageTag" src="" alt="Preview" style="max-height: 120px; max-width: 100%; object-fit: contain;" class="rounded">
                                <button type="button" class="delete-img-btn" id="deleteMainImageBtn" title="Xóa ảnh đại diện">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                        </div>
                        @error('image') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <!-- Ảnh con / Gallery slide -->
                    <div>
                        <input type="file"
                            class="d-none @error('gallery_images.*') is-invalid @enderror"
                            id="gallery_images"
                            name="gallery_images[]"
                            accept="image/jpeg,image/jpg,image/png,image/webp"
                            multiple>

                        <div class="small fw-bold text-dark mb-1">Ảnh gallery slide chi tiết</div>
                        <div class="thumb-grid mb-2" id="galleryThumbGrid"></div>
                        <small class="text-muted d-block" style="font-size: 0.75rem;">Bấm `+` để thêm nhiều ảnh khác nhau cho slide sản phẩm.</small>
                        @error('gallery_images.*') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

            <!-- Nút Action -->
            <div class="d-flex flex-column gap-2">
                <button type="submit" class="btn btn-primary rounded-pill py-2.5 fw-bold w-100 d-flex align-items-center justify-content-center gap-2" style="background:#0D9373; border-color:#0D9373;">
                    <i class="bi bi-check-circle-fill"></i>Thêm sản phẩm
                </button>
                <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary rounded-pill py-2 w-100 text-center">
                    Hủy bỏ
                </a>
            </div>
        </div>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('[data-branch-status-row]').forEach(function(row) {
            const toggle = row.querySelector('[data-branch-status-toggle]');
            const select = row.querySelector('[data-branch-status-select]');
            toggle?.addEventListener('change', function() {
                select.disabled = !toggle.checked;
            });
        });

        const inputM = document.getElementById('size_price_input_M');
        const inputL = document.getElementById('size_price_input_L');
        const checkM = document.querySelector('.size-checkbox[data-size-name="M"]');
        const checkL = document.querySelector('.size-checkbox[data-size-name="L"]');
        const errorL = document.getElementById('size_error_L');

        // Tự động tích chọn Checkbox khi Admin gõ giá cộng thêm vào ô nhập
        document.querySelectorAll('.size-price-field').forEach(function(priceInput) {
            const sName = priceInput.dataset.sizeName;
            const checkbox = document.querySelector(`.size-checkbox[data-size-name="${sName}"]`);
            const card = priceInput.closest('.size-card');

            const handlePriceChange = function() {
                if (!checkbox) return;
                const val = priceInput.value.trim();
                if (val !== '') {
                    checkbox.checked = true;
                    if (card) card.classList.add('active');
                }
            };

            priceInput.addEventListener('input', handlePriceChange);
            priceInput.addEventListener('change', handlePriceChange);
        });

        document.querySelectorAll('.size-checkbox').forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                const card = checkbox.closest('.size-card');
                if (card) {
                    card.classList.toggle('active', checkbox.checked);
                }
            });
        });

        function validateSizePricesLive() {
            if (!inputM || !inputL || !errorL) return true;

            const isMChecked = !checkM || checkM.checked;
            const isLChecked = !checkL || checkL.checked;

            if (!isMChecked || !isLChecked) {
                errorL.style.display = 'none';
                inputL.classList.remove('is-invalid');
                return true;
            }

            const valM = parseInt(inputM.value) || 0;
            const valL = parseInt(inputL.value) || 0;

            // Cập nhật thuộc tính min & step cho Size L nhảy theo đơn vị 1000đ
            const minL = valM + 1000;
            inputL.min = minL;
            inputL.step = 1000;

            if (inputL.value !== '' && valL < minL) {
                errorL.textContent = `⚠️ Giá Size L phải lớn hơn Size M theo bước 1.000đ (tối thiểu ${minL.toLocaleString('vi-VN')}đ)`;
                errorL.style.display = 'block';
                inputL.classList.add('is-invalid');
                return false;
            } else {
                errorL.style.display = 'none';
                inputL.classList.remove('is-invalid');
                return true;
            }
        }

        if (inputM && inputL) {
            inputM.addEventListener('input', validateSizePricesLive);
            inputL.addEventListener('input', validateSizePricesLive);
            inputM.addEventListener('change', validateSizePricesLive);
            inputL.addEventListener('change', validateSizePricesLive);
            checkM?.addEventListener('change', validateSizePricesLive);
            checkL?.addEventListener('change', validateSizePricesLive);

            document.getElementById('createProductForm')?.addEventListener('submit', function(e) {
                if (!validateSizePricesLive()) {
                    e.preventDefault();
                    e.stopPropagation();
                    inputL.focus();
                }
            });
        }

        // 1. Ảnh đại diện chính
        const mainDropzone = document.getElementById('mainImageDropzone');
        const imageInput = document.getElementById('image');
        const emptyState = document.getElementById('mainImageEmptyState');
        const previewState = document.getElementById('mainImagePreviewState');
        const mainImageTag = document.getElementById('mainImageTag');
        const deleteMainImageBtn = document.getElementById('deleteMainImageBtn');

        if (mainDropzone && imageInput) {
            mainDropzone.addEventListener('click', function(e) {
                if (e.target.closest('#deleteMainImageBtn')) return;
                imageInput.click();
            });

            imageInput.addEventListener('change', function() {
                const file = imageInput.files && imageInput.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        mainImageTag.src = e.target.result;
                        emptyState.classList.add('d-none');
                        previewState.classList.remove('d-none');
                    };
                    reader.readAsDataURL(file);
                }
            });

            deleteMainImageBtn?.addEventListener('click', function(e) {
                e.stopPropagation();
                imageInput.value = '';
                mainImageTag.src = '';
                previewState.classList.add('d-none');
                emptyState.classList.remove('d-none');
            });
        }

        // 2. Gallery
        let galleryFiles = [];
        const galleryInput = document.getElementById('gallery_images');
        const gridContainer = document.getElementById('galleryThumbGrid');

        function renderGalleryGrid() {
            if (!gridContainer) return;
            gridContainer.innerHTML = '';

            const addBtn = document.createElement('div');
            addBtn.className = 'thumb-slot';
            addBtn.style.cssText = 'background:#f0fdf4; border: 1.5px solid #bbf7d0; color:#0D9373; cursor:pointer;';
            addBtn.title = 'Bấm để thêm ảnh slide';
            addBtn.innerHTML = '<i class="bi bi-plus-lg fs-4"></i>';
            addBtn.onclick = () => galleryInput.click();
            gridContainer.appendChild(addBtn);

            galleryFiles.forEach((fileObj, index) => {
                const slot = document.createElement('div');
                slot.className = 'thumb-slot';
                slot.style.cssText = 'border: 1px solid #0D9373; position:relative;';

                const img = document.createElement('img');
                img.src = fileObj.url;
                slot.appendChild(img);

                const delBtn = document.createElement('button');
                delBtn.type = 'button';
                delBtn.className = 'delete-img-btn';
                delBtn.title = 'Xóa ảnh này';
                delBtn.innerHTML = '<i class="bi bi-x"></i>';
                delBtn.onclick = (e) => {
                    e.stopPropagation();
                    galleryFiles.splice(index, 1);
                    updateFileInput();
                    renderGalleryGrid();
                };
                slot.appendChild(delBtn);
                gridContainer.appendChild(slot);
            });

            const totalSlots = 1 + galleryFiles.length;
            if (totalSlots < 4) {
                for (let i = totalSlots; i < 4; i++) {
                    const slot = document.createElement('div');
                    slot.className = 'thumb-slot';
                    slot.style.cssText = 'background:#f8fafc; border: 1px dashed #cbd5e1; color:#94a3b8; cursor:pointer;';
                    slot.innerHTML = '<i class="bi bi-image fs-4"></i>';
                    slot.onclick = () => galleryInput.click();
                    gridContainer.appendChild(slot);
                }
            }
        }

        function updateFileInput() {
            if (!galleryInput) return;
            const dt = new DataTransfer();
            galleryFiles.forEach(item => {
                if (item.file) {
                    dt.items.add(item.file);
                }
            });
            galleryInput.files = dt.files;
        }

        if (galleryInput) {
            galleryInput.addEventListener('change', function() {
                const newFiles = Array.from(galleryInput.files || []);
                newFiles.forEach(file => {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        galleryFiles.push({
                            file: file,
                            url: e.target.result
                        });
                        updateFileInput();
                        renderGalleryGrid();
                    };
                    reader.readAsDataURL(file);
                });
            });
        }

        renderGalleryGrid();
    });
</script>
@endsection
