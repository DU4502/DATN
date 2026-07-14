@extends('layouts.client')

@section('title', 'Thanh toán')

@section('content')
@php
    $paymentLabels = [
        'cod' => 'Thanh toán khi nhận hàng',
        'vnpay' => 'VNPay / Quét QR',
    ];
@endphp

<style>
    .guest-payment-page {
        background: linear-gradient(180deg, #effcf9 0%, #ffffff 100%);
        padding: 2.5rem 0 4rem;
    }

    .guest-panel,
    .guest-summary {
        border: 1px solid rgba(0, 139, 122, 0.10);
        border-radius: 28px;
        background: rgba(255, 255, 255, 0.94);
        box-shadow: 0 24px 60px rgba(8, 42, 38, 0.07);
    }

    .payment-option input {
        position: absolute;
        opacity: 0;
    }

    .payment-card {
        border: 1.5px solid #d5eee8;
        border-radius: 20px;
        padding: 1.25rem;
        cursor: pointer;
        transition: all 0.18s ease;
    }

    .payment-option input:checked + .payment-card {
        border-color: #0d9373;
        box-shadow: 0 16px 34px rgba(0, 139, 122, 0.14);
    }

    .qr-hint {
        border: 1px dashed #b8e5dc;
        border-radius: 16px;
        background: #f6fffc;
        padding: 1rem 1.25rem;
    }
</style>

<section class="guest-payment-page">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <p class="section-kicker mb-2">Guest Checkout</p>
                <h1 class="display-6 fw-bold mb-0">Thanh toán & xác thực</h1>
            </div>
            <div class="text-secondary fw-semibold"><span class="text-primary">●</span> Bước 2/2</div>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="guest-panel p-4 p-md-5">
                    <form method="POST" action="{{ route('checkout.guest.process') }}">
                        @csrf

                        <p class="fw-semibold mb-3">Chọn phương thức thanh toán</p>

                        <div class="d-flex flex-column gap-3 mb-4">
                            @foreach($paymentOptions as $key => $option)
                                <label class="payment-option position-relative">
                                    <input type="radio" name="payment_method" value="{{ $key }}" @checked(old('payment_method', 'cod') === $key) required>
                                    <div class="payment-card d-flex align-items-center gap-3">
                                        <span class="checkout-step"><i class="bi {{ $option['icon'] }}"></i></span>
                                        <div>
                                            <strong>{{ $option['title'] }}</strong>
                                            <div class="small text-secondary">{{ $option['desc'] }}</div>
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>

                        <div class="qr-hint mb-4">
                            <strong><i class="bi bi-qr-code me-1"></i>VNPay / Quét QR</strong>
                            <p class="small text-secondary mb-0 mt-2">Sau khi xác nhận, bạn sẽ được chuyển đến cổng VNPay để quét mã QR hoặc thanh toán qua ngân hàng. Hệ thống tự cập nhật khi thanh toán thành công.</p>
                        </div>

                        @error('payment_method')
                            <div class="alert alert-danger">{{ $message }}</div>
                        @enderror

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="termsCheck" required>
                            <label class="form-check-label" for="termsCheck">Tôi đồng ý với điều khoản đặt hàng của Chill Drink.</label>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill">
                            Xác nhận đặt hàng <i class="bi bi-check2-circle ms-2"></i>
                        </button>

                        <a href="{{ route('checkout.guest.index') }}" class="btn btn-link w-100 mt-2">Quay lại nhập thông tin</a>
                    </form>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="guest-summary p-4 p-md-5">
                    <h2 class="h4 fw-bold mb-3">Thông tin đơn</h2>
                    <div class="small text-secondary mb-3">
                        <div><strong>{{ $guestInfo['guest_name'] ?? '' }}</strong></div>
                        <div>{{ $guestInfo['guest_phone'] ?? '' }}</div>
                        <div>{{ $guestInfo['guest_email'] ?? '' }}</div>
                        @if(($guestInfo['fulfillment_type'] ?? '') === 'pickup' && $branch)
                            <div class="mt-2"><i class="bi bi-shop me-1"></i>Lấy tại: {{ $branch->name }}</div>
                        @else
                            <div class="mt-2"><i class="bi bi-geo-alt me-1"></i>{{ $guestInfo['shipping_address_ui'] ?? '' }}, {{ $guestInfo['shipping_area_ui'] ?? '' }}</div>
                        @endif
                    </div>

                    <hr>

                    @foreach($cart as $item)
                        <div class="d-flex justify-content-between gap-3 mb-2">
                            <span>{{ $item['name'] ?? 'Sản phẩm' }} × {{ $item['quantity'] ?? 1 }}</span>
                            <span>{{ number_format(($item['price'] ?? 0) * ($item['quantity'] ?? 1), 0, ',', '.') }}đ</span>
                        </div>
                    @endforeach

                    <div class="d-flex justify-content-between mt-3">
                        <span class="text-secondary">Phí giao hàng</span>
                        <strong>{{ number_format($shippingFee, 0, ',', '.') }}đ</strong>
                    </div>
                    <div class="d-flex justify-content-between h4 fw-bold mt-3">
                        <span>Tổng cộng</span>
                        <span class="text-primary">{{ number_format($grandTotal, 0, ',', '.') }}đ</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
