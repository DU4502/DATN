@php
    $isEdit = isset($voucher) && $voucher;
    $startsAt = old('starts_at', optional($voucher?->starts_at)->format('Y-m-d\TH:i'));
    $expiresAt = old('expires_at', optional($voucher?->expires_at)->format('Y-m-d\TH:i'));
@endphp

@if($errors->any())
    <div class="alert alert-danger rounded-3">
        <div class="fw-bold mb-1">Vui lòng kiểm tra lại thông tin voucher.</div>
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-4">
    <div class="col-12">
        <label for="code" class="form-label fw-semibold">Mã giảm giá *</label>
        <input id="code" type="text" name="code" value="{{ old('code', $voucher->code ?? '') }}" class="admin-input text-uppercase" placeholder="VD: SUMMER2026" required>
        <small class="text-secondary">Chỉ dùng chữ, số, dấu gạch ngang hoặc gạch dưới.</small>
    </div>

    <div class="col-lg-6">
        <label for="type" class="form-label fw-semibold">Loại giảm giá *</label>
        <select id="type" name="type" class="admin-filter" required>
            @foreach($typeOptions as $value => $label)
                <option value="{{ $value }}" @selected(old('type', $voucher->type ?? 'fixed') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-lg-6">
        <label for="value" class="form-label fw-semibold">Giá trị giảm *</label>
        <input id="value" type="number" min="1" name="value" value="{{ old('value', $voucher->value ?? '') }}" class="admin-input" placeholder="VD: 50000 hoặc 15" required>
        <small class="text-secondary">Nếu là phần trăm, nhập từ 1 đến 100.</small>
    </div>

    <div class="col-lg-6">
        <label for="min_order" class="form-label fw-semibold">Đơn tối thiểu (VNĐ)</label>
        <input id="min_order" type="number" min="0" name="min_order" value="{{ old('min_order', $voucher->min_order ?? 0) }}" class="admin-input">
    </div>

    <div class="col-lg-6">
        <label for="max_discount" class="form-label fw-semibold">Giảm tối đa (VNĐ)</label>
        <input id="max_discount" type="number" min="0" name="max_discount" value="{{ old('max_discount', $voucher->max_discount ?? '') }}" class="admin-input" placeholder="Chỉ cần cho voucher phần trăm">
    </div>

    <div class="col-lg-6">
        <label for="usage_limit" class="form-label fw-semibold">Giới hạn sử dụng</label>
        <input id="usage_limit" type="number" min="0" name="usage_limit" value="{{ old('usage_limit', $voucher->usage_limit ?? 0) }}" class="admin-input">
        <small class="text-secondary">Nhập 0 nếu không giới hạn lượt dùng.</small>
    </div>

    <div class="col-lg-6">
        <label for="required_rank" class="form-label fw-semibold">Rank yêu cầu</label>
        <select id="required_rank" name="required_rank" class="admin-filter">
            <option value="">Tất cả</option>
            @foreach($rankOptions as $value => $label)
                <option value="{{ $value }}" @selected(old('required_rank', $voucher->required_rank ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-lg-6">
        <label for="point_cost" class="form-label fw-semibold">Điểm đổi</label>
        <input id="point_cost" type="number" min="0" name="point_cost" value="{{ old('point_cost', $voucher->point_cost ?? 0) }}" class="admin-input">
    </div>

    <div class="col-lg-6 d-flex align-items-end">
        <label class="d-inline-flex align-items-center gap-2 fw-semibold mb-2">
            <input type="hidden" name="is_redeemable" value="0">
            <input type="checkbox" name="is_redeemable" value="1" @checked(old('is_redeemable', $voucher->is_redeemable ?? false))>
            Có thể đổi bằng điểm
        </label>
    </div>

    <div class="col-lg-6">
        <label for="starts_at" class="form-label fw-semibold">Ngày bắt đầu</label>
        <input id="starts_at" type="datetime-local" name="starts_at" value="{{ $startsAt }}" class="admin-input">
        <small class="text-secondary">Nếu bỏ trống, mã có hiệu lực ngay khi tạo.</small>
    </div>

    <div class="col-lg-6">
        <label for="expires_at" class="form-label fw-semibold">Ngày hết hạn</label>
        <input id="expires_at" type="datetime-local" name="expires_at" value="{{ $expiresAt }}" class="admin-input">
    </div>

    <div class="col-12">
        <label for="description" class="form-label fw-semibold">Mô tả</label>
        <textarea id="description" name="description" rows="4" class="admin-input" placeholder="Mô tả điều kiện hoặc chương trình áp dụng">{{ old('description', $voucher->description ?? '') }}</textarea>
    </div>

    <div class="col-12">
        <label class="d-inline-flex align-items-center gap-2 fw-semibold">
            <input type="hidden" name="status" value="0">
            <input type="checkbox" name="status" value="1" @checked(old('status', $voucher->status ?? true))>
            Kích hoạt mã
        </label>
    </div>

    @if($isEdit)
        <div class="col-12">
            <div class="text-secondary small">
                Ngày tạo: {{ optional($voucher->created_at)->format('d/m/Y H:i') ?: '-' }}.
                Lượt đã dùng: {{ $voucher->usageText() }}.
            </div>
        </div>
    @endif
</div>

<div class="border-top mt-4 pt-4 d-flex flex-wrap gap-2">
    <button type="submit" class="btn btn-primary">
        {{ $isEdit ? 'Lưu thay đổi' : 'Thêm mã' }}
    </button>
    <a href="{{ route('admin.vouchers.index') }}" class="btn btn-outline-primary">Hủy</a>
</div>
