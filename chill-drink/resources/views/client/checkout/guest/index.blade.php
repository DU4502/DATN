@extends('layouts.client')

@section('title', 'Thanh toán nhanh')

@section('content')
@php
    $guestInfo = $guestInfo ?? [];
    $deliveryType = old('delivery_type', $guestInfo['delivery_type'] ?? 'delivery');
@endphp

<style>
    .guest-checkout-page {
        background: linear-gradient(180deg, #effcf9 0%, #f7fffd 48%, #ffffff 100%);
        padding: 2.5rem 0 4rem;
    }

    .guest-panel,
    .guest-summary {
        border: 1px solid rgba(0, 139, 122, 0.10);
        border-radius: 28px;
        background: rgba(255, 255, 255, 0.94);
        box-shadow: 0 24px 60px rgba(8, 42, 38, 0.07);
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
        padding: 0.85rem 1rem;
    }

    .delivery-toggle .btn {
        border-radius: 999px;
    }

    .delivery-fields.is-hidden,
    .pickup-fields.is-hidden {
        display: none;
    }
</style>

<section class="guest-checkout-page">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <p class="section-kicker mb-2">Guest Checkout</p>
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

                        <div class="mb-3">
                            <label for="guest_name" class="form-label fw-semibold">Họ và tên *</label>
                            <input type="text" id="guest_name" name="guest_name" class="form-control guest-input @error('guest_name') is-invalid @enderror" value="{{ old('guest_name', $guestInfo['guest_name'] ?? '') }}" required autocomplete="name">
                            @error('guest_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="guest_phone" class="form-label fw-semibold">Số điện thoại *</label>
                            <input type="tel" id="guest_phone" name="guest_phone" class="form-control guest-input @error('guest_phone') is-invalid @enderror" value="{{ old('guest_phone', $guestInfo['guest_phone'] ?? '') }}" required autocomplete="tel" placeholder="09xx xxx xxx">
                            @error('guest_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-4">
                            <label for="guest_email" class="form-label fw-semibold">Email *</label>
                            <input type="email" id="guest_email" name="guest_email" class="form-control guest-input @error('guest_email') is-invalid @enderror" value="{{ old('guest_email', $guestInfo['guest_email'] ?? '') }}" required autocomplete="email">
                            <div class="form-text"><i class="bi bi-envelope-check me-1"></i>Nhận hóa đơn & cập nhật trạng thái đơn hàng qua email.</div>
                            @error('guest_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold d-block">Nhận hàng *</label>
                            <div class="btn-group delivery-toggle w-100" role="group">
                                <input type="radio" class="btn-check" name="delivery_type" id="deliveryTypeDelivery" value="delivery" @checked($deliveryType === 'delivery')>
                                <label class="btn btn-outline-primary" for="deliveryTypeDelivery"><i class="bi bi-truck me-1"></i>Giao đến địa chỉ</label>
                                <input type="radio" class="btn-check" name="delivery_type" id="deliveryTypePickup" value="pickup" @checked($deliveryType === 'pickup')>
                                <label class="btn btn-outline-primary" for="deliveryTypePickup"><i class="bi bi-shop me-1"></i>Lấy tại chi nhánh</label>
                            </div>
                        </div>

                        <div class="delivery-fields {{ $deliveryType === 'pickup' ? 'is-hidden' : '' }}" data-delivery-fields>
                            <div class="mb-3">
                                <label for="shipping_address_ui" class="form-label fw-semibold">Địa chỉ giao hàng *</label>
                                <input type="text" id="shipping_address_ui" name="shipping_address_ui" class="form-control guest-input @error('shipping_address_ui') is-invalid @enderror" value="{{ old('shipping_address_ui', $guestInfo['shipping_address_ui'] ?? '') }}">
                                @error('shipping_address_ui')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-4">
                                <label for="shipping_area_ui" class="form-label fw-semibold">Khu vực *</label>
                                <select id="shipping_area_ui" name="shipping_area_ui" class="form-select guest-input @error('shipping_area_ui') is-invalid @enderror">
                                    <option value="">Chọn khu vực</option>
                                    @foreach($shippingDistanceOptions as $option)
                                        <option value="{{ $option['label'] }}" @selected(old('shipping_area_ui', $guestInfo['shipping_area_ui'] ?? '') === $option['label'])>{{ $option['label'] }} — {{ $option['description'] }}</option>
                                    @endforeach
                                </select>
                                @error('shipping_area_ui')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="pickup-fields {{ $deliveryType === 'delivery' ? 'is-hidden' : '' }}" data-pickup-fields>
                            <div class="mb-4">
                                <label for="branch_id" class="form-label fw-semibold">Chọn chi nhánh *</label>
                                <select id="branch_id" name="branch_id" class="form-select guest-input @error('branch_id') is-invalid @enderror">
                                    <option value="">Chọn chi nhánh</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" @selected((string) old('branch_id', $guestInfo['branch_id'] ?? '') === (string) $branch->id)>{{ $branch->name }} — {{ $branch->address }}</option>
                                    @endforeach
                                </select>
                                @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="note" class="form-label fw-semibold">Ghi chú (tuỳ chọn)</label>
                            <textarea id="note" name="note" rows="3" class="form-control guest-input @error('note') is-invalid @enderror" placeholder="Ít đá, gọi trước 5 phút...">{{ old('note', $guestInfo['note'] ?? '') }}</textarea>
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
                    <h2 class="h4 fw-bold mb-4">Tóm tắt đơn</h2>
                    <div class="d-flex flex-column gap-3 mb-4">
                        @foreach($cart as $item)
                            <div class="d-flex justify-content-between gap-3">
                                <div>
                                    <strong>{{ $item['name'] ?? 'Sản phẩm' }}</strong>
                                    <div class="small text-secondary">× {{ $item['quantity'] ?? 1 }}</div>
                                </div>
                                <strong>{{ number_format(($item['price'] ?? 0) * ($item['quantity'] ?? 1), 0, ',', '.') }}đ</strong>
                            </div>
                        @endforeach
                    </div>
                    <div class="d-flex justify-content-between h5 fw-bold">
                        <span>Tạm tính</span>
                        <span class="text-primary">{{ number_format($subtotal, 0, ',', '.') }}đ</span>
                    </div>
                    <p class="small text-secondary mt-3 mb-0">Phí giao hàng sẽ được tính ở bước thanh toán.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const deliveryFields = document.querySelector('[data-delivery-fields]');
        const pickupFields = document.querySelector('[data-pickup-fields]');
        const deliveryInput = document.getElementById('deliveryTypeDelivery');
        const pickupInput = document.getElementById('deliveryTypePickup');

        function syncDeliveryMode() {
            const isPickup = pickupInput?.checked;
            deliveryFields?.classList.toggle('is-hidden', isPickup);
            pickupFields?.classList.toggle('is-hidden', !isPickup);
        }

        deliveryInput?.addEventListener('change', syncDeliveryMode);
        pickupInput?.addEventListener('change', syncDeliveryMode);
        syncDeliveryMode();
    });
</script>
@endsection
