@extends('layouts.client')

@section('title', 'Thanh toán nhanh')

@section('content')
@php
    $guestInfo = $guestInfo ?? [];
    $deliveryType = old('fulfillment_type', $guestInfo['fulfillment_type'] ?? 'delivery');
    $cartQuantity = collect($cart)->sum(fn ($item) => (int) ($item['quantity'] ?? 1));
@endphp

<style>
    .guest-checkout-page {
        background: linear-gradient(180deg, #effcf9 0%, #f7fffd 48%, #ffffff 100%);
        padding: 1.25rem 0 2rem;
    }

    .guest-checkout-page .display-6 {
        font-size: clamp(2rem, 3vw, 2.55rem);
    }

    .guest-checkout-page .form-label {
        margin-bottom: 0.35rem;
        font-size: 0.9rem;
    }

    .guest-panel,
    .guest-summary {
        border: 1px solid rgba(0, 139, 122, 0.10);
        border-radius: 28px;
        background: rgba(255, 255, 255, 0.94);
        box-shadow: 0 24px 60px rgba(8, 42, 38, 0.07);
    }

    @media (min-width: 768px) {
        .guest-panel.p-md-5,
        .guest-summary.p-md-5 {
            padding: 2rem !important;
        }
    }

    .guest-panel .mb-4 {
        margin-bottom: 1rem !important;
    }

    .guest-panel .mb-3 {
        margin-bottom: 0.8rem !important;
    }

    .guest-summary {
        position: sticky;
        top: 105px;
        overflow: hidden;
    }

    .guest-summary::before {
        content: '';
        display: block;
        height: 6px;
        margin: -1.5rem -1.5rem 1rem;
        background: linear-gradient(90deg, #0d9373, #1fba8a, #f59e0b);
    }

    @media (min-width: 768px) {
        .guest-summary::before {
            margin: -2rem -2rem 1rem;
        }
    }

    .guest-summary-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.38rem 0.65rem;
        border-radius: 999px;
        background: #ecfdf5;
        color: #047857;
        font-size: 0.78rem;
        font-weight: 800;
    }

    .guest-summary-item {
        padding: 0.7rem 0;
        border-bottom: 1px solid #e5f3ef;
    }

    .guest-summary-item:last-child {
        border-bottom: 0;
    }

    .guest-item-thumb {
        width: 38px;
        height: 38px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        background: linear-gradient(135deg, #e8fff7, #fef7df);
        color: #0d9373;
        box-shadow: inset 0 0 0 1px rgba(13, 147, 115, 0.10);
    }

    .guest-summary-line {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.55rem 0;
        border-top: 1px dashed #d5eee8;
        color: #64748b;
        font-size: 0.94rem;
    }

    .guest-summary-total {
        margin-top: 0.75rem;
        padding: 0.85rem 1rem;
        border-radius: 18px;
        background: linear-gradient(135deg, #053f36, #0d9373);
        color: #fff;
        box-shadow: 0 16px 34px rgba(13, 147, 115, 0.22);
    }

    .guest-summary-total .amount {
        font-size: 1.35rem;
        font-weight: 900;
    }

    .guest-summary-note {
        margin-top: 0.75rem;
        padding: 0.75rem;
        border-radius: 16px;
        background: #fffbeb;
        color: #92400e;
        font-size: 0.82rem;
        line-height: 1.45;
    }

    .guest-summary-status {
        margin-top: 0.75rem;
        display: grid;
        gap: 0.5rem;
    }

    .guest-summary-status-row {
        display: flex;
        gap: 0.7rem;
        align-items: flex-start;
        padding: 0.65rem 0.75rem;
        border-radius: 14px;
        background: #f8fffd;
        border: 1px solid #dff5ef;
    }

    .guest-summary-status-row i {
        color: #0d9373;
        margin-top: 0.1rem;
    }

    .guest-branch-section {
        padding-top: 0.25rem;
    }

    .guest-branch-section-icon {
        width: 46px;
        height: 46px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 46px;
        color: #fff;
        background: linear-gradient(135deg, #0d9373, #11b98d);
        box-shadow: 0 14px 28px rgba(13, 147, 115, 0.18);
    }

    @media (max-width: 991.98px) {
        .guest-summary {
            position: static;
        }
    }

    .guest-step {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9rem;
        color: #6b7280;
        font-weight: 600;
    }

    .guest-step-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #0d9373;
    }

    .guest-step-dot.is-muted {
        background: #d1d5db;
    }

    .guest-input {
        border-radius: 16px;
        border-color: #d5eee8;
        padding: 0.65rem 0.9rem;
        min-height: 44px;
    }

    .delivery-toggle .btn {
        border-radius: 999px;
        padding: 0.55rem 0.75rem;
    }

    .location-refresh-btn {
        min-width: 44px;
        border-radius: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
    }

    .location-search-btn {
        border-radius: 16px;
        white-space: nowrap;
        padding: 0.55rem 0.75rem;
    }

    .guest-address-modal .modal-content {
        border: 0;
        border-radius: 24px;
        overflow: hidden;
    }

    .guest-address-modal .modal-header {
        background: linear-gradient(135deg, #effcf9, #ffffff);
        border-bottom-color: #e5f3ef;
    }

    .guest-address-search-wrap {
        position: relative;
    }

    .guest-address-suggestions {
        position: absolute;
        z-index: 1060;
        top: calc(100% + 6px);
        left: 0;
        right: 0;
        max-height: min(320px, 45vh);
        overflow-y: auto;
        overscroll-behavior: contain;
        border: 1px solid #d5eee8;
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.15);
        scrollbar-width: thin;
        scrollbar-color: #0d9488 #eefaf7;
    }

    .guest-address-suggestions::-webkit-scrollbar {
        width: 8px;
    }

    .guest-address-suggestions::-webkit-scrollbar-track {
        background: #eefaf7;
        border-radius: 999px;
    }

    .guest-address-suggestions::-webkit-scrollbar-thumb {
        background: #0d9488;
        border-radius: 999px;
    }

    .guest-address-suggestion {
        display: block;
        width: 100%;
        padding: 0.75rem 0.9rem;
        border: 0;
        border-bottom: 1px solid #eef2f7;
        background: #fff;
        text-align: left;
    }

    .guest-address-suggestion:last-child { border-bottom: 0; }
    .guest-address-suggestion:hover,
    .guest-address-suggestion:focus-visible {
        background: #ecfaf6;
        outline: none;
    }

    .guest-address-suggestion-title { color: #0f172a; font-weight: 800; font-size: 0.88rem; }
    .guest-address-suggestion-subtitle { color: #64748b; font-size: 0.76rem; }

    .guest-address-map {
        position: relative;
        height: 300px;
        overflow: hidden;
        border: 1px solid #d5eee8;
        border-radius: 16px;
        background: linear-gradient(135deg, #eefaf7, #f8fbfa);
    }

    .guest-address-map.is-loading::after {
        content: 'Đang tải bản đồ...';
        position: absolute;
        inset: 0;
        z-index: 500;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #0d9373;
        font-size: 0.85rem;
        font-weight: 700;
        background: linear-gradient(135deg, rgba(238, 250, 247, 0.96), rgba(255, 255, 255, 0.92));
        pointer-events: none;
    }

    .delivery-fields.is-hidden,
    .pickup-fields.is-hidden {
        display: none;
    }
</style>

<section class="guest-checkout-page" data-guest-checkout data-should-prompt-location="{{ ($shouldPromptLocation ?? false) ? '1' : '0' }}" data-reverse-geocode-url="{{ route('api.reverse-geocode') }}">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h1 class="display-6 fw-bold mb-0">Thanh toán nhanh</h1>
            </div>
            <div class="guest-step">
                <span class="guest-step-dot"></span> Bước 1/2
                <span class="guest-step-dot is-muted"></span>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="guest-panel p-4 p-md-5">
                    <form method="POST" action="{{ route('checkout.guest.info.store') }}" id="guestInfoForm">
                        @csrf

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="guest_name" class="form-label fw-semibold">Họ và tên *</label>
                                <input type="text" id="guest_name" name="guest_name" class="form-control guest-input @error('guest_name') is-invalid @enderror" value="{{ old('guest_name', $guestInfo['guest_name'] ?? '') }}" required autocomplete="name">
                                @error('guest_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label for="guest_phone" class="form-label fw-semibold">Số điện thoại *</label>
                                <input
                                    type="tel"
                                    id="guest_phone"
                                    name="guest_phone"
                                    class="form-control guest-input @error('guest_phone') is-invalid @enderror"
                                    value="{{ old('guest_phone', $guestInfo['guest_phone'] ?? '') }}"
                                    required
                                    autocomplete="tel-national"
                                    inputmode="numeric"
                                    maxlength="11"
                                    pattern="^(?:0(?:3|5|7|8|9)[0-9]{8}|84(?:3|5|7|8|9)[0-9]{8})$"
                                    placeholder="0912345678"
                                    title="Nhập số di động Việt Nam hợp lệ, ví dụ 0912345678 hoặc 84912345678."
                                    data-server-error="{{ $errors->has('guest_phone') ? '1' : '0' }}"
                                >
                                <div class="invalid-feedback" data-guest-phone-feedback>
                                    @error('guest_phone'){{ $message }}@else Số điện thoại phải là số di động Việt Nam hợp lệ.@enderror
                                </div>
                            </div>

                            <div class="col-12">
                                <label for="guest_email" class="form-label fw-semibold">Địa chỉ email *</label>
                                <input type="email" id="guest_email" name="guest_email" class="form-control guest-input @error('guest_email') is-invalid @enderror" value="{{ old('guest_email', $guestInfo['guest_email'] ?? '') }}" required autocomplete="email">
                                <div class="form-text"><i class="bi bi-envelope-check me-1"></i>Nhận hóa đơn & cập nhật trạng thái đơn hàng qua email.</div>
                                @error('guest_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold d-block">Nhận hàng *</label>
                            <div class="btn-group delivery-toggle w-100" role="group">
                                <input type="radio" class="btn-check" name="fulfillment_type" id="deliveryTypeDelivery" value="delivery" @checked($deliveryType !== 'pickup')>
                                <label class="btn btn-outline-primary" for="deliveryTypeDelivery"><i class="bi bi-truck me-1"></i>Giao đến địa chỉ</label>
                                <input type="radio" class="btn-check" name="fulfillment_type" id="deliveryTypePickup" value="pickup" @checked($deliveryType === 'pickup')>
                                <label class="btn btn-outline-primary" for="deliveryTypePickup"><i class="bi bi-shop me-1"></i>Lấy tại chi nhánh</label>
                            </div>
                            <input type="hidden" name="delivery_type" value="now">
                        </div>

                        <div class="delivery-fields {{ $deliveryType === 'pickup' ? 'is-hidden' : '' }}" data-delivery-fields>
                            <div class="mb-3">
                                <label for="shipping_address_ui" class="form-label fw-semibold">Địa chỉ giao hàng *</label>
                                <div class="d-flex gap-2 align-items-stretch">
                                    <input type="text" id="shipping_address_ui" name="shipping_address_ui" class="form-control guest-input @error('shipping_address_ui') is-invalid @enderror flex-grow-1" value="{{ old('shipping_address_ui', $guestInfo['shipping_address_ui'] ?? '') }}">
                                    <button type="button" class="btn btn-outline-primary location-search-btn" data-address-picker-open title="Tìm địa chỉ trên bản đồ">
                                        <i class="bi bi-search"></i><span class="d-none d-sm-inline ms-1">Tìm địa chỉ</span>
                                    </button>
                                    <button type="button" class="btn btn-outline-primary location-refresh-btn" data-location-refresh title="Lấy lại tọa độ vị trí" aria-label="Lấy lại tọa độ vị trí">
                                        <i class="bi bi-crosshair"></i>
                                    </button>
                                </div>
                                <div class="form-text d-none" data-location-hint></div>
                                <div class="form-text text-warning d-none" data-address-house-number-warning>
                                    <i class="bi bi-exclamation-circle me-1"></i>Nếu khu vực có số nhà, bạn nên nhập thêm số nhà để giao hàng chính xác hơn.
                                </div>
                                @error('shipping_address_ui')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <input type="hidden" id="shipping_area_ui" name="shipping_area_ui" value="{{ old('shipping_area_ui', $guestInfo['shipping_area_ui'] ?? '') }}">
                            <input type="hidden" id="guest_latitude" name="latitude" value="{{ old('latitude', $guestInfo['latitude'] ?? '') }}">
                            <input type="hidden" id="guest_longitude" name="longitude" value="{{ old('longitude', $guestInfo['longitude'] ?? '') }}">

                        </div>

                        <div class="mb-4 guest-branch-section">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <span class="guest-branch-section-icon" aria-hidden="true">
                                    <i class="bi bi-shop"></i>
                                </span>
                                <div>
                                    <h2 class="h4 fw-bold mb-1">Chi nhánh phục vụ gần bạn</h2>
                                    <p class="text-secondary mb-0">Chọn chi nhánh xử lý đơn hàng của bạn hoặc để hệ thống gợi ý chi nhánh gần nhất.</p>
                                </div>
                            </div>

                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                <label for="branch_id" class="form-label fw-semibold mb-0">Chọn chi nhánh *</label>
                                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill" data-find-nearest-branch>
                                    <i class="bi bi-crosshair me-1"></i>Tìm chi nhánh gần nhất
                                </button>
                            </div>
                            <select id="branch_id" name="branch_id" class="form-select guest-input @error('branch_id') is-invalid @enderror" data-branches='@json($branchesData ?? [])' required>
                                <option value="">Chọn chi nhánh</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected((string) old('branch_id', $guestInfo['branch_id'] ?? '') === (string) $branch->id)>{{ $branch->name }} — {{ $branch->address }}</option>
                                @endforeach
                            </select>
                            <div class="form-text d-none" data-branch-select-note></div>
                            @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-4">
                            <label for="note" class="form-label fw-semibold">
                                Ghi chú giao hàng <span class="text-danger d-none" data-note-required-indicator>*</span>
                            </label>
                            <textarea id="note" name="note" rows="2" class="form-control guest-input @error('note') is-invalid @enderror" placeholder="Ví dụ: để phòng bảo vệ, gọi số khác, gần cổng chợ, nhà màu xanh...">{{ old('note', $guestInfo['note'] ?? '') }}</textarea>
                            @error('note')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill">
                            Tiếp tục thanh toán <i class="bi bi-arrow-right ms-2"></i>
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="guest-summary p-4 p-md-5">
                    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
                        <div>
                            <p class="section-kicker mb-2">Đơn hàng của bạn</p>
                            <h2 class="h4 fw-bold mb-0">Tóm tắt đơn</h2>
                        </div>
                        <span class="guest-summary-badge">
                            <i class="bi bi-bag-check"></i>{{ $cartQuantity }} món
                        </span>
                    </div>

                    <div class="mb-3">
                        @foreach($cart as $item)
                            <div class="guest-summary-item d-flex justify-content-between gap-3">
                                <div class="d-flex gap-3">
                                    <span class="guest-item-thumb"><i class="bi bi-cup-straw"></i></span>
                                    <div>
                                        <strong class="d-block">{{ $item['name'] ?? 'Sản phẩm' }}</strong>
                                        <div class="small text-secondary">
                                            {{ number_format($item['price'] ?? 0, 0, ',', '.') }}đ × {{ $item['quantity'] ?? 1 }}
                                        </div>
                                    </div>
                                </div>
                                <strong class="text-nowrap">{{ number_format(($item['price'] ?? 0) * ($item['quantity'] ?? 1), 0, ',', '.') }}đ</strong>
                            </div>
                        @endforeach
                    </div>

                    <div class="guest-summary-line">
                        <span>Tạm tính món</span>
                        <strong class="text-dark">{{ number_format($subtotal, 0, ',', '.') }}đ</strong>
                    </div>
                    <div class="guest-summary-line">
                        <span>Phí giao hàng</span>
                        <strong class="text-dark">Tính ở bước sau</strong>
                    </div>
                    <div class="guest-summary-line">
                        <span>Nhận hàng</span>
                        <strong class="text-dark" data-summary-fulfillment>{{ $deliveryType === 'pickup' ? 'Lấy tại chi nhánh' : 'Giao đến địa chỉ' }}</strong>
                    </div>

                    <div class="guest-summary-total d-flex justify-content-between align-items-end gap-3">
                        <div>
                            <div class="small opacity-75">Tạm tính hiện tại</div>
                            <div class="fw-semibold">Chưa gồm phí giao hàng</div>
                        </div>
                        <div class="amount text-nowrap">{{ number_format($subtotal, 0, ',', '.') }}đ</div>
                    </div>

                    <div class="guest-summary-status">
                        <div class="guest-summary-status-row">
                            <i class="bi bi-geo-alt-fill"></i>
                            <div>
                                <strong class="d-block">Địa chỉ</strong>
                                <span class="small text-secondary" data-summary-address>Chưa xác nhận địa chỉ giao hàng.</span>
                            </div>
                        </div>
                        <div class="guest-summary-status-row">
                            <i class="bi bi-shop"></i>
                            <div>
                                <strong class="d-block">Chi nhánh xử lý</strong>
                                <span class="small text-secondary" data-summary-branch>Chọn chi nhánh trong phạm vi không quá 15 km đường bộ để tiếp tục.</span>
                            </div>
                        </div>
                    </div>

                    <div class="guest-summary-note">
                        <i class="bi bi-info-circle me-1"></i>
                        Sau khi xác nhận thông tin, bạn sẽ chọn phương thức thanh toán và kiểm tra phí giao hàng cuối cùng.
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="modal fade guest-address-modal" id="guestAddressModal" tabindex="-1" aria-labelledby="guestAddressModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header px-4 py-3">
                <div>
                    <p class="section-kicker mb-1">ĐỊA CHỈ GIAO HÀNG</p>
                    <h2 class="modal-title h5 fw-bold mb-0" id="guestAddressModalLabel">Tìm địa chỉ trên bản đồ</h2>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body p-4">
                <p class="small text-secondary mb-3">Nhập số nhà, tên đường, phường/xã hoặc tỉnh rồi chọn một gợi ý.</p>
                <div class="guest-address-search-wrap mb-3">
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-primary"></i></span>
                        <input type="search" id="guestAddressSearch" class="form-control guest-input border-start-0" placeholder="Ví dụ: Quốc lộ 47, Thanh Hóa" autocomplete="off">
                    </div>
                    <div id="guestAddressSuggestions" class="guest-address-suggestions d-none"></div>
                </div>
                <div id="guestAddressMap" class="guest-address-map mb-3"></div>
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="small text-secondary" id="guestAddressMapStatus">Chọn một gợi ý hoặc chạm vào bản đồ để xác nhận.</div>
                    <button type="button" class="btn btn-primary rounded-pill px-4" id="guestAddressApply">Dùng vị trí này</button>
                </div>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
@include('partials.vietnam-territory-labels')

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const checkoutRoot = document.querySelector('[data-guest-checkout]');
        const deliveryFields = document.querySelector('[data-delivery-fields]');
        const pickupFields = document.querySelector('[data-pickup-fields]');
        const deliveryInput = document.getElementById('deliveryTypeDelivery');
        const pickupInput = document.getElementById('deliveryTypePickup');
        const branchSelect = document.getElementById('branch_id');
        const branchSelectNote = document.querySelector('[data-branch-select-note]');
        const guestPhoneInput = document.getElementById('guest_phone');
        const shippingAddressInput = document.getElementById('shipping_address_ui');
        const shippingAreaInput = document.getElementById('shipping_area_ui');
        const noteInput = document.getElementById('note');
        const addressHouseNumberWarning = document.querySelector('[data-address-house-number-warning]');
        const noteRequiredIndicator = document.querySelector('[data-note-required-indicator]');
        const summaryFulfillment = document.querySelector('[data-summary-fulfillment]');
        const summaryAddress = document.querySelector('[data-summary-address]');
        const summaryBranch = document.querySelector('[data-summary-branch]');
        const locationRefreshButton = document.querySelector('[data-location-refresh]');
        const findNearestBranchButton = document.querySelector('[data-find-nearest-branch]');
        const locationHint = document.querySelector('[data-location-hint]');
        const addressPickerOpenButton = document.querySelector('[data-address-picker-open]');
        const addressModalElement = document.getElementById('guestAddressModal');
        const addressSearchInput = document.getElementById('guestAddressSearch');
        const addressSuggestions = document.getElementById('guestAddressSuggestions');
        const addressMapStatus = document.getElementById('guestAddressMapStatus');
        const addressApplyButton = document.getElementById('guestAddressApply');
        const addressMapElement = document.getElementById('guestAddressMap');
        const shouldPromptLocation = checkoutRoot?.dataset.shouldPromptLocation === '1';
        const reverseGeocodeUrl = checkoutRoot?.dataset.reverseGeocodeUrl || '/api/reverse-geocode';
        const addressLookupUrl = '/api/address-lookup';
        const branchesListEndpoint = @json(route('api.branches.list'));
        const maxOrderDistanceKm = {{ json_encode(\App\Support\OrderDistancePolicy::MAX_DISTANCE_KM) }};
        const guestLatitudeInput = document.getElementById('guest_latitude');
        const guestLongitudeInput = document.getElementById('guest_longitude');
        const branchesData = (() => {
            try {
                return JSON.parse(branchSelect?.dataset.branches || '[]');
            } catch (error) {
                return [];
            }
        })();
        let guestLatitude = Number.parseFloat(guestLatitudeInput?.value || '');
        let guestLongitude = Number.parseFloat(guestLongitudeInput?.value || '');
        if (!Number.isFinite(guestLatitude)) guestLatitude = null;
        if (!Number.isFinite(guestLongitude)) guestLongitude = null;
        let locationPrompted = false;
        let addressMap = null;
        let addressMarker = null;
        let draftLatitude = null;
        let draftLongitude = null;
        let draftAddress = '';
        let draftArea = '';

        function escapeAddressHtml(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function normalizeVietnamesePhone(value) {
            const digits = String(value || '').replace(/\D+/g, '');

            if (digits.startsWith('84') && digits.length === 11) {
                return `0${digits.slice(2)}`;
            }

            return digits;
        }

        function isValidVietnameseMobilePhone(value) {
            return /^(?:0(?:3|5|7|8|9)\d{8}|84(?:3|5|7|8|9)\d{8})$/.test(String(value || ''));
        }

        function hasHouseNumber(value) {
            const text = String(value || '').trim();

            return /(?:\b(?:số|so|nhà|nha|ngõ|ngo|hẻm|hem|ngách|ngach|kiệt|kiet)\s*[:#.-]?\s*\d+[a-z]?(?:[/-]\d+[a-z]?)*(?![.,]\d)\b|\b\d+[a-z]?(?:[/-]\d+[a-z]?)*(?![.,]\d)\b)/iu.test(text);
        }

        function showAddressHouseNumberWarning(message, shouldScroll = false) {
            if (!addressHouseNumberWarning) {
                return;
            }

            addressHouseNumberWarning.innerHTML = `<i class="bi bi-exclamation-circle me-1"></i>${message}`;
            addressHouseNumberWarning.classList.remove('d-none');

            if (shouldScroll) {
                addressHouseNumberWarning.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        function syncNoteRequirement(isRequired) {
            noteRequiredIndicator?.classList.toggle('d-none', !isRequired);
        }

        function syncAddressHouseNumberNotice(shouldScroll = false) {
            if (!addressHouseNumberWarning || !deliveryInput?.checked) {
                addressHouseNumberWarning?.classList.add('d-none');
                syncNoteRequirement(false);
                return;
            }

            const addressText = String(shippingAddressInput?.value || '').trim();
            const noteValue = String(noteInput?.value || '').trim();

            if (addressText && !hasHouseNumber(addressText)) {
                syncNoteRequirement(true);
                if (shouldScroll && !noteValue) {
                    showAddressHouseNumberWarning(
                        'Yêu cầu ghi chú vì địa chỉ chưa ghi rõ số nhà/địa chỉ nhà. Hãy ghi mốc nhận hàng để shipper dễ tìm.',
                        true
                    );
                    return;
                }

                addressHouseNumberWarning.classList.add('d-none');
                addressHouseNumberWarning.textContent = '';
                return;
            }

            addressHouseNumberWarning.classList.add('d-none');
            addressHouseNumberWarning.textContent = '';
            syncNoteRequirement(false);
        }

        function clearAddressHouseNumberWarning() {
            if (noteInput) {
                noteInput.setCustomValidity('');
                noteInput.classList.remove('is-invalid');
            }
        }

        function syncGuestSummary() {
            const isPickup = pickupInput?.checked;
            const addressText = String(shippingAddressInput?.value || '').trim();
            const selectedBranch = branchSelect?.selectedOptions?.[0];
            const selectedBranchText = selectedBranch?.value ? selectedBranch.textContent.trim() : '';

            if (summaryFulfillment) {
                summaryFulfillment.textContent = isPickup ? 'Lấy tại chi nhánh' : 'Giao đến địa chỉ';
            }

            if (summaryAddress) {
                summaryAddress.textContent = isPickup
                    ? 'Bạn sẽ nhận đơn trực tiếp tại chi nhánh đã chọn.'
                    : (addressText || 'Chưa xác nhận địa chỉ giao hàng.');
            }

            if (summaryBranch) {
                summaryBranch.textContent = selectedBranchText || 'Chọn chi nhánh trong phạm vi không quá 15 km đường bộ để tiếp tục.';
            }
        }

        function syncGuestPhoneInput(touched = false) {
            if (!guestPhoneInput) {
                return true;
            }

            const normalized = normalizeVietnamesePhone(guestPhoneInput.value).slice(0, 11);
            if (guestPhoneInput.value !== normalized) {
                guestPhoneInput.value = normalized;
            }

            const isValid = isValidVietnameseMobilePhone(normalized);
            const feedback = guestPhoneInput.parentElement?.querySelector('[data-guest-phone-feedback]');
            const hasServerError = guestPhoneInput.dataset.serverError === '1';

            guestPhoneInput.setCustomValidity(isValid ? '' : 'Số điện thoại Việt Nam không đúng định dạng.');
            guestPhoneInput.classList.toggle('is-invalid', (touched || hasServerError) && !isValid);

            if (feedback && !hasServerError) {
                feedback.textContent = 'Số điện thoại phải là số di động Việt Nam hợp lệ.';
            }

            return isValid;
        }

        function normalizeAddressSuggestion(feature) {
            const properties = feature?.properties || {};
            const coordinates = feature?.geometry?.coordinates || [];
            const lng = Number.parseFloat(coordinates[0]);
            const lat = Number.parseFloat(coordinates[1]);
            const street = [properties.housenumber, properties.street, properties.name]
                .filter(Boolean).join(' ').trim();
            const area = [properties.district, properties.city, properties.state, properties.country]
                .filter(Boolean).join(', ').trim();
            const displayName = [street, area].filter(Boolean).join(', ');

            return {
                lat,
                lng,
                street,
                area,
                title: street || properties.name || displayName || 'Địa chỉ được gợi ý',
                subtitle: area || displayName,
                displayName,
            };
        }

        function setDraftLocation(latitude, longitude, address = '', area = '', message = '') {
            const lat = Number.parseFloat(latitude);
            const lng = Number.parseFloat(longitude);

            if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                return;
            }

            draftLatitude = lat;
            draftLongitude = lng;
            draftAddress = address || draftAddress;
            draftArea = area || draftArea;

            if (addressMap && addressMarker) {
                addressMarker.setLatLng([lat, lng]);
                addressMap.setView([lat, lng], Math.max(addressMap.getZoom(), 15), { animate: true });
            }

            if (addressMapStatus && message) {
                addressMapStatus.textContent = message;
            }
        }

        function initAddressMap() {
            if (addressMap || !addressMapElement || typeof L === 'undefined') {
                addressMap?.invalidateSize();
                return;
            }

            const hasLocation = Number.isFinite(guestLatitude) && Number.isFinite(guestLongitude);
            const center = hasLocation ? [guestLatitude, guestLongitude] : [19.8, 105.75];
            addressMapElement.classList.add('is-loading');
            addressMap = L.map(addressMapElement, {
                scrollWheelZoom: true,
                touchZoom: true,
                tap: true,
                zoomControl: false,
                center,
                zoom: hasLocation ? 15 : 7,
            });
            const tileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors',
                updateWhenIdle: true,
                keepBuffer: 2,
            }).addTo(addressMap);
            window.ChillDrinkVietnamTerritoryLabels?.addToLeaflet(addressMap);
            tileLayer.once('load', () => addressMapElement.classList.remove('is-loading'));
            window.setTimeout(() => addressMapElement.classList.remove('is-loading'), 5000);
            addressMarker = L.marker(center, { draggable: true }).addTo(addressMap);

            addressMap.on('click', (event) => {
                setDraftLocation(event.latlng.lat, event.latlng.lng, '', '', 'Đã chọn vị trí trên bản đồ. Bấm “Dùng vị trí này” để áp dụng.');
            });
            addressMarker.on('dragend', () => {
                const position = addressMarker.getLatLng();
                setDraftLocation(position.lat, position.lng, '', '', 'Đã cập nhật vị trí. Bấm “Dùng vị trí này” để áp dụng.');
            });
            [0, 100, 250, 500, 900].forEach((delay) => {
                setTimeout(() => addressMap?.invalidateSize({ pan: false }), delay);
            });
        }

        function hideAddressSuggestions() {
            addressSuggestions?.classList.add('d-none');
            if (addressSuggestions) {
                addressSuggestions.innerHTML = '';
            }
        }

        function renderAddressSuggestions(items) {
            if (!addressSuggestions) {
                return;
            }

            const hasCurrentLocation = Number.isFinite(guestLatitude) && Number.isFinite(guestLongitude);
            const rankedItems = items.map((item, index) => ({
                ...item,
                searchIndex: index,
                distance: hasCurrentLocation
                    ? calculateDistance(guestLatitude, guestLongitude, item.lat, item.lng)
                    : null,
            }));

            if (hasCurrentLocation) {
                rankedItems.sort((a, b) => {
                    // Keep search relevance as the first signal; use distance
                    // to break close relevance ties and avoid unrelated jumps.
                    const relevanceGroupA = Math.floor(a.searchIndex / 3);
                    const relevanceGroupB = Math.floor(b.searchIndex / 3);
                    if (relevanceGroupA !== relevanceGroupB) {
                        return relevanceGroupA - relevanceGroupB;
                    }

                    return a.distance - b.distance;
                });
            }

            if (!rankedItems.length) {
                addressSuggestions.innerHTML = '<div class="guest-address-suggestion-title px-3 py-2">Không tìm thấy gợi ý phù hợp.</div>';
                addressSuggestions.classList.remove('d-none');
                return;
            }

            addressSuggestions.innerHTML = rankedItems.map((item, index) => `
                <button type="button" class="guest-address-suggestion" data-address-suggestion="${index}">
                    <div class="guest-address-suggestion-title">${escapeAddressHtml(item.title)}</div>
                    <div class="guest-address-suggestion-subtitle">${escapeAddressHtml(item.subtitle || item.displayName)}</div>
                </button>
            `).join('');
            addressSuggestions.classList.remove('d-none');

            addressSuggestions.querySelectorAll('[data-address-suggestion]').forEach((button) => {
                button.addEventListener('click', () => {
                    const item = rankedItems[Number(button.dataset.addressSuggestion)];
                    if (!item) return;

                    addressSearchInput.value = item.displayName || item.title;
                    setDraftLocation(item.lat, item.lng, item.street || item.displayName, item.area, 'Đã chọn địa chỉ. Bấm “Dùng vị trí này” để áp dụng.');
                    hideAddressSuggestions();
                });
            });
        }

        function normalizeNominatimSuggestion(result) {
            const address = result?.address || {};
            const street = [address.house_number, address.road || address.pedestrian]
                .filter(Boolean).join(' ').trim();
            const area = [
                address.suburb,
                address.village,
                address.town,
                address.city,
                address.county,
                address.state,
            ].filter(Boolean).join(', ').trim();
            const displayName = result?.display_name || [street, area].filter(Boolean).join(', ');

            return {
                lat: Number.parseFloat(result?.lat),
                lng: Number.parseFloat(result?.lon),
                street,
                area,
                title: address.road || address.pedestrian || address.name || street || displayName,
                subtitle: area || displayName,
                displayName,
            };
        }

        function normalizeInternalAddressSuggestion(item) {
            const lat = Number.parseFloat(item?.latitude);
            const lng = Number.parseFloat(item?.longitude);
            const displayName = item?.full_address || item?.name || '';
            const street = item?.full_address || item?.name || '';

            return {
                lat,
                lng,
                street,
                area: '',
                title: item?.name || displayName || 'Địa chỉ Chill Drink đã ghi nhận',
                subtitle: item?.full_address || 'Dữ liệu địa chỉ đã lưu trong hệ thống',
                displayName,
                source: item?.source || 'internal',
            };
        }

        function mergeAddressSuggestions(groups) {
            const seen = new Set();
            return groups.flat().filter((item) => {
                if (!Number.isFinite(item.lat) || !Number.isFinite(item.lng)) {
                    return false;
                }

                const key = `${item.title}|${item.lat.toFixed(4)}|${item.lng.toFixed(4)}`.toLowerCase();
                if (seen.has(key)) {
                    return false;
                }

                seen.add(key);
                return true;
            }).slice(0, 16);
        }

        async function searchAddressProviders(query) {
            const cleanedQuery = query.replace(/\b(số|so|nhà|nha)\b/gi, ' ').replace(/\s+/g, ' ').trim();
            const internalUrl = new URL(addressLookupUrl, window.location.origin);
            internalUrl.searchParams.set('q', query);
            internalUrl.searchParams.set('limit', '8');
            if (Number.isFinite(guestLatitude) && Number.isFinite(guestLongitude)) {
                internalUrl.searchParams.set('latitude', String(guestLatitude));
                internalUrl.searchParams.set('longitude', String(guestLongitude));
            }

            const photonUrl = new URL('https://photon.komoot.io/api/');
            photonUrl.searchParams.set('q', query);
            photonUrl.searchParams.set('limit', '10');

            const nominatimUrl = new URL('https://nominatim.openstreetmap.org/search');
            nominatimUrl.searchParams.set('format', 'jsonv2');
            nominatimUrl.searchParams.set('addressdetails', '1');
            nominatimUrl.searchParams.set('accept-language', 'vi');
            nominatimUrl.searchParams.set('countrycodes', 'vn');
            nominatimUrl.searchParams.set('limit', '10');
            nominatimUrl.searchParams.set('q', cleanedQuery || query);

            const [internalResult, photonResult, nominatimResult] = await Promise.allSettled([
                fetch(internalUrl.toString(), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                }).then((response) => response.ok ? response.json() : { data: [] }),
                fetch(photonUrl.toString()).then((response) => response.ok ? response.json() : { features: [] }),
                fetch(nominatimUrl.toString()).then((response) => response.ok ? response.json() : []),
            ]);

            const internalItems = internalResult.status === 'fulfilled'
                ? (internalResult.value.data || []).map(normalizeInternalAddressSuggestion)
                : [];
            const photonItems = photonResult.status === 'fulfilled'
                ? (photonResult.value.features || []).map(normalizeAddressSuggestion)
                : [];
            const nominatimItems = nominatimResult.status === 'fulfilled'
                ? (nominatimResult.value || []).map(normalizeNominatimSuggestion)
                : [];

            return mergeAddressSuggestions([internalItems, photonItems, nominatimItems]);
        }

        let addressSearchTimer = null;
        addressSearchInput?.addEventListener('input', () => {
            window.clearTimeout(addressSearchTimer);
            const query = addressSearchInput.value.trim();
            if (query.length < 3) {
                hideAddressSuggestions();
                return;
            }

            addressSearchTimer = window.setTimeout(async () => {
                try {
                    const items = await searchAddressProviders(query);
                    renderAddressSuggestions(items);
                } catch (error) {
                    console.error('Guest address search failed:', error);
                    renderAddressSuggestions([]);
                }
            }, 450);
        });

        addressPickerOpenButton?.addEventListener('click', () => {
            const modal = bootstrap.Modal.getOrCreateInstance(addressModalElement);
            if (Number.isFinite(guestLatitude) && Number.isFinite(guestLongitude)) {
                draftLatitude = guestLatitude;
                draftLongitude = guestLongitude;
                draftAddress = shippingAddressInput?.value || '';
                draftArea = shippingAreaInput?.value || '';
            }
            modal.show();
        });

        addressModalElement?.addEventListener('shown.bs.modal', () => {
            initAddressMap();
            if (Number.isFinite(draftLatitude) && Number.isFinite(draftLongitude)) {
                setDraftLocation(draftLatitude, draftLongitude, draftAddress, draftArea);
            }
            [0, 150, 400, 800].forEach((delay) => {
                setTimeout(() => addressMap?.invalidateSize({ pan: false }), delay);
            });
        });
        addressModalElement?.addEventListener('hidden.bs.modal', hideAddressSuggestions);
        document.addEventListener('click', (event) => {
            if (!addressSearchInput?.contains(event.target) && !addressSuggestions?.contains(event.target)) {
                hideAddressSuggestions();
            }
        });

        addressApplyButton?.addEventListener('click', () => {
            if (!Number.isFinite(draftLatitude) || !Number.isFinite(draftLongitude)) {
                addressMapStatus.textContent = 'Vui lòng chọn một gợi ý hoặc một điểm trên bản đồ.';
                return;
            }

            guestLatitude = draftLatitude;
            guestLongitude = draftLongitude;
            if (guestLatitudeInput) guestLatitudeInput.value = String(guestLatitude);
            if (guestLongitudeInput) guestLongitudeInput.value = String(guestLongitude);
            if (shippingAddressInput && draftAddress) shippingAddressInput.value = draftAddress;
            if (shippingAreaInput && draftArea) shippingAreaInput.value = draftArea;
            clearAddressHouseNumberWarning();
            syncAddressHouseNumberNotice();
            renderBranchOptions(guestLatitude, guestLongitude);
            setLocationHint('Đã cập nhật địa chỉ theo vị trí bạn chọn.');
            bootstrap.Modal.getOrCreateInstance(addressModalElement).hide();
        });

        function syncDeliveryMode() {
            const isPickup = pickupInput?.checked;
            deliveryFields?.classList.toggle('is-hidden', isPickup);
            pickupFields?.classList.toggle('is-hidden', !isPickup);

            // Địa chỉ giao hàng chỉ required khi chọn "Giao đến địa chỉ"
            const addressInput = document.getElementById('shipping_address_ui');
            if (addressInput) {
                addressInput.required = !isPickup;
            }
        }

        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371;
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a =
                Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                Math.sin(dLon / 2) * Math.sin(dLon / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            return R * c;
        }

        async function renderBranchOptions(latitude = null, longitude = null) {
            if (!branchSelect) return;

            const currentValue = branchSelect.value || '';
            const hasLocation = Number.isFinite(latitude) && Number.isFinite(longitude);

            const writeOptions = (branches, roadMode = false) => {
                branchSelect.innerHTML = '<option value="">Chọn chi nhánh</option>';

                if (hasLocation && branches.length === 0) {
                    const option = document.createElement('option');
                    option.value = '';
                    option.disabled = true;
                    option.textContent = 'Không có chi nhánh nào trong 15 km đường bộ';
                    branchSelect.appendChild(option);
                }

                branches.forEach((branch) => {
                    const option = document.createElement('option');
                    option.value = branch.id;
                    option.dataset.latitude = branch.latitude || '';
                    option.dataset.longitude = branch.longitude || '';
                    const distance = branch.distance_km ?? null;
                    option.dataset.distance = distance !== null ? Number(distance).toFixed(2) : '';

                    let label = branch.name || 'Chi nhánh';
                    if (branch.address) label += ' — ' + branch.address;
                    if (distance !== null && Number.isFinite(Number(distance))) {
                        label += ` — ${Number(distance).toFixed(1)} km${roadMode ? ' đường bộ' : ''}`;
                    }
                    option.textContent = label;
                    branchSelect.appendChild(option);
                });

                if (currentValue && Array.from(branchSelect.options).some((option) => option.value === String(currentValue))) {
                    branchSelect.value = String(currentValue);
                }
            };

            if (!hasLocation) {
                writeOptions(branchesData, false);
                if (branchSelectNote) {
                    branchSelectNote.classList.remove('d-none');
                    branchSelectNote.textContent = 'Vui lòng xác định vị trí giao hàng để tính quãng đường đường bộ.';
                }
                updateSummary();
                return;
            }

            branchSelect.innerHTML = '<option value="">Đang tính tuyến đường...</option>';
            branchSelect.disabled = true;

            try {
                const url = new URL(branchesListEndpoint, window.location.origin);
                url.searchParams.set('latitude', String(latitude));
                url.searchParams.set('longitude', String(longitude));
                const response = await fetch(url, { headers: { 'Accept': 'application/json' }, cache: 'no-store' });
                const payload = await response.json();
                if (!response.ok || !payload.success) throw new Error(payload.message || 'Không tải được chi nhánh.');

                writeOptions(Array.isArray(payload.data) ? payload.data : [], true);
                if (branchSelectNote) {
                    branchSelectNote.classList.remove('d-none');
                    branchSelectNote.textContent = 'Chỉ hiển thị chi nhánh cách địa chỉ giao hàng không quá 15 km theo lộ trình đường bộ.';
                }
            } catch (error) {
                writeOptions(branchesData, false);
                if (branchSelectNote) {
                    branchSelectNote.classList.remove('d-none');
                    branchSelectNote.textContent = 'Chưa tải được tuyến đường. Hệ thống sẽ kiểm tra chính xác khi tiếp tục.';
                }
            } finally {
                branchSelect.disabled = false;
                updateSummary();
            }
        }

        function setLocationHint(message) {
            if (locationHint) {
                locationHint.classList.toggle('d-none', !message);
                locationHint.innerHTML = `<i class="bi bi-geo-alt me-1"></i>${message}`;
            }
        }

        function formatAddressFromGeo(data) {
            const parts = [
                data?.street,
                data?.ward,
                data?.district,
                data?.province,
            ].filter(Boolean);

            return parts.join(', ').trim();
        }

        async function reverseGeocode(latitude, longitude) {
            const url = new URL(reverseGeocodeUrl, window.location.origin);
            url.searchParams.set('latitude', String(latitude));
            url.searchParams.set('longitude', String(longitude));

            const response = await fetch(url.toString(), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(data?.message || 'Không thể xác định địa chỉ từ vị trí hiện tại.');
            }

            return data;
        }

        async function requestCurrentLocation(options = {}) {
            const force = Boolean(options.force);
            const selectNearest = Boolean(options.selectNearest);

            if ((!force && locationPrompted) || pickupInput?.checked) {
                return Promise.resolve();
            }

            locationPrompted = true;

            if (!navigator.geolocation) {
                setLocationHint('Trình duyệt không hỗ trợ lấy vị trí. Bạn có thể nhập địa chỉ thủ công.');
                return Promise.resolve();
            }

            setLocationHint('Đang lấy vị trí hiện tại...');

            return new Promise((resolve) => {
                navigator.geolocation.getCurrentPosition(async (position) => {
                    try {
                        const latitude = position.coords.latitude;
                        const longitude = position.coords.longitude;
                        guestLatitude = latitude;
                        guestLongitude = longitude;
                        if (guestLatitudeInput) guestLatitudeInput.value = String(latitude);
                        if (guestLongitudeInput) guestLongitudeInput.value = String(longitude);
                        renderBranchOptions(latitude, longitude);

                        if (selectNearest && branchSelect && !branchSelect.value) {
                            const firstBranch = Array.from(branchSelect.options).find((option) => option.value);
                            if (firstBranch) {
                                branchSelect.value = firstBranch.value;
                                branchSelect.dispatchEvent(new Event('change', { bubbles: true }));
                            }
                        }

                        const data = await reverseGeocode(latitude, longitude);
                        const addressText = formatAddressFromGeo(data)
                            || data?.display_name
                            || `${latitude.toFixed(6)}, ${longitude.toFixed(6)}`;
                        const areaText = data?.area || data?.ward || data?.province || '';

                        if (shippingAddressInput) {
                            shippingAddressInput.value = addressText;
                            clearAddressHouseNumberWarning();
                            syncAddressHouseNumberNotice();
                        }

                        if (shippingAreaInput) {
                            shippingAreaInput.value = areaText;
                        }

                        setDraftLocation(latitude, longitude, addressText, areaText);

                        setLocationHint('Địa chỉ đã được tự điền theo vị trí hiện tại. Bạn có thể chỉnh lại nếu cần.');
                    } catch (error) {
                        console.error('Reverse geocode guest checkout failed:', error);
                        guestLatitude = position.coords.latitude;
                        guestLongitude = position.coords.longitude;
                        if (guestLatitudeInput) guestLatitudeInput.value = String(guestLatitude);
                        if (guestLongitudeInput) guestLongitudeInput.value = String(guestLongitude);
                        renderBranchOptions(guestLatitude, guestLongitude);
                        if (selectNearest && branchSelect && !branchSelect.value) {
                            const firstBranch = Array.from(branchSelect.options).find((option) => option.value);
                            if (firstBranch) {
                                branchSelect.value = firstBranch.value;
                                branchSelect.dispatchEvent(new Event('change', { bubbles: true }));
                            }
                        }
                        if (shippingAddressInput && !shippingAddressInput.value.trim()) {
                            shippingAddressInput.value = `${position.coords.latitude.toFixed(6)}, ${position.coords.longitude.toFixed(6)}`;
                            clearAddressHouseNumberWarning();
                            syncAddressHouseNumberNotice();
                        }
                        setDraftLocation(
                            position.coords.latitude,
                            position.coords.longitude,
                            shippingAddressInput?.value || '',
                            shippingAreaInput?.value || ''
                        );
                        setLocationHint('Đã lấy được vị trí nhưng chưa xác định được địa chỉ chi tiết. Bạn có thể nhập thủ công.');
                    } finally {
                        resolve();
                    }
                }, () => {
                    setLocationHint('Không lấy được vị trí. Bạn có thể nhập địa chỉ thủ công.');
                    resolve();
                }, {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0,
                });
            });
        }

        deliveryInput?.addEventListener('change', function () {
            syncDeliveryMode();
            syncAddressHouseNumberNotice();
            syncGuestSummary();

            if (shouldPromptLocation && !pickupInput?.checked) {
                requestCurrentLocation();
            }
        });
        pickupInput?.addEventListener('change', function () {
            syncDeliveryMode();
            syncAddressHouseNumberNotice();
            syncGuestSummary();
            if (!pickupInput.checked && shouldPromptLocation) {
                requestCurrentLocation();
            }
        });
        if (branchSelect) {
            const validateBranchSelect = () => {
                branchSelect.setCustomValidity(branchSelect.value ? '' : 'Vui lòng chọn chi nhánh trong phạm vi không quá 15 km đường bộ');
            };

            branchSelect.addEventListener('invalid', validateBranchSelect);
            branchSelect.addEventListener('change', function () {
                validateBranchSelect();
                syncGuestSummary();
            });
            validateBranchSelect();
        }
        guestPhoneInput?.addEventListener('input', function () {
            syncGuestPhoneInput(false);
        });
        guestPhoneInput?.addEventListener('blur', function () {
            syncGuestPhoneInput(true);
        });
        shippingAddressInput?.addEventListener('input', function () {
            clearAddressHouseNumberWarning();
            syncAddressHouseNumberNotice();
            syncGuestSummary();
        });
        shippingAddressInput?.addEventListener('blur', function () {
            syncAddressHouseNumberNotice();
            syncGuestSummary();
        });
        noteInput?.addEventListener('input', function () {
            if (String(noteInput.value || '').trim()) {
                clearAddressHouseNumberWarning();
                addressHouseNumberWarning?.classList.add('d-none');
                return;
            }

            noteInput.setCustomValidity('');
            noteInput.classList.remove('is-invalid');
        });
        document.getElementById('guestInfoForm')?.addEventListener('submit', function (event) {
            if (!syncGuestPhoneInput(true)) {
                event.preventDefault();
                guestPhoneInput?.reportValidity();
                return;
            }

            if (deliveryInput?.checked && shippingAddressInput && !hasHouseNumber(shippingAddressInput.value)) {
                const noteValue = String(noteInput?.value || '').trim();

                if (!noteValue) {
                    event.preventDefault();
                    clearAddressHouseNumberWarning();
                    if (noteInput) {
                        noteInput.setCustomValidity('Yêu cầu ghi chú vì địa chỉ chưa ghi rõ số nhà/địa chỉ nhà. Vui lòng ghi mốc nhận hàng, ví dụ để phòng bảo vệ, gọi số khác hoặc mô tả địa chỉ cụ thể.');
                        noteInput.classList.add('is-invalid');
                        noteInput.placeholder = 'Ví dụ: để phòng bảo vệ, gọi số khác, gần cổng chợ, nhà màu xanh...';
                        noteInput.focus();
                        noteInput.reportValidity();
                    }
                    syncAddressHouseNumberNotice(true);
                    return;
                }

                clearAddressHouseNumberWarning();
                syncAddressHouseNumberNotice();
            }
        });
        syncAddressHouseNumberNotice();
        syncGuestSummary();
        syncGuestPhoneInput(false);
        findNearestBranchButton?.addEventListener('click', function () {
            const originalText = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Đang tìm';

            Promise.resolve(requestCurrentLocation({ force: true, selectNearest: true }))
                .finally(() => {
                    this.disabled = false;
                    this.innerHTML = originalText;
                });
        });
        locationRefreshButton?.addEventListener('click', function () {
            if (pickupInput?.checked) {
                pickupInput.checked = false;
                if (deliveryInput) {
                    deliveryInput.checked = true;
                }
                syncDeliveryMode();
            }

            requestCurrentLocation({ force: true });
        });
        syncDeliveryMode();
        renderBranchOptions();

        if (shouldPromptLocation && !pickupInput?.checked) {
            requestCurrentLocation();
        }
    });
</script>
@endsection
