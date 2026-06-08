@extends('layouts.client')

@section('title', 'Thanh Toán')

@section('content')
@php
    $total = (int) ($subtotal ?? collect($cart)->sum(fn($item) => $item['price'] * $item['quantity']));
    $shippingDistanceOptions = $shippingDistanceOptions ?? \App\Support\ShippingFee::distanceOptions();
    $shippingMethods = $shippingMethods ?? \App\Support\ShippingFee::methods();
    $user = auth()->user();
    $primaryAddress = trim((string) ($user->address ?? ''));
    $primaryArea = trim((string) ($user->area ?? ''));
    $primaryAddressText = trim(collect([$primaryAddress, $primaryArea])->filter()->implode(', '));
    $selectedShippingMethod = old('shipping_method_ui', 'standard');
    $shippingQuote = \App\Support\ShippingFee::quoteForAddress(
        old('shipping_address_ui', $primaryAddress),
        old('shipping_area_ui', $primaryArea),
        $selectedShippingMethod
    );
    $shippingFee = $shippingQuote['total_fee'];
    $availableVouchers = collect($availableVouchers ?? []);
    $loyaltyContext = $loyaltyContext ?? ['rank' => 'bronze', 'points' => 0];
    $rankOrder = ['bronze' => 1, 'silver' => 2, 'gold' => 3, 'diamond' => 4];
    $canUseCheckoutVoucher = function ($voucher) use ($total, $loyaltyContext, $rankOrder) {
        $hasMinimumOrder = (int) $total >= (int) $voucher->min_order;
        $hasRank = ! $voucher->required_rank
            || (($rankOrder[$loyaltyContext['rank'] ?? 'bronze'] ?? 1) >= ($rankOrder[$voucher->required_rank] ?? 1));
        $hasPoints = ! $voucher->is_redeemable
            || (int) $voucher->point_cost <= 0
            || (int) ($loyaltyContext['points'] ?? 0) >= (int) $voucher->point_cost;

        return $voucher->discountFor((int) $total) > 0 && $hasMinimumOrder && $hasRank && $hasPoints;
    };
    $selectedVoucherCode = strtoupper(trim((string) old('voucher_code', '')));
    $selectedVoucher = $availableVouchers->first(fn ($voucher) => $voucher->code === $selectedVoucherCode && $canUseCheckoutVoucher($voucher));
    $discount = $selectedVoucher ? $selectedVoucher->discountFor((int) $total) : 0;
    $selectedVoucherLabel = $selectedVoucher ? $selectedVoucher->code . ' - ' . $selectedVoucher->formattedValue() : '';
    $grandTotal = max(0, $total + $shippingFee - $discount);
    $paymentOptions = [
        'cod' => [
            'title' => 'Thanh toán khi nhận hàng',
            'desc' => 'Trả tiền mặt sau khi nhận đồ uống.',
            'icon' => 'bi-cash-coin',
        ],
        'bank_transfer' => [
            'title' => 'Chuyển khoản ngân hàng',
            'desc' => 'Nhân viên xác nhận sau khi nhận chuyển khoản.',
            'icon' => 'bi-bank',
        ],
        'momo' => [
            'title' => 'Ví Momo',
            'desc' => 'Thanh toán nhanh qua ví điện tử Momo.',
            'icon' => 'bi-phone',
        ],
        'vnpay' => [
            'title' => 'VNPay',
            'desc' => 'Hỗ trợ thẻ ATM, QR và ngân hàng nội địa.',
            'icon' => 'bi-credit-card',
        ],
    ];
@endphp

<style>
    .checkout-page {
        background:
            radial-gradient(circle at 12% 8%, rgba(0, 139, 122, 0.08), transparent 30%),
            linear-gradient(180deg, #f6fffc 0%, #ffffff 44%, #f7fbfa 100%);
    }

    .checkout-hero {
        background:
            radial-gradient(circle at 88% 24%, rgba(0, 139, 122, 0.13), transparent 34%),
            linear-gradient(135deg, #f2fffb 0%, #ffffff 54%, #e9fbf7 100%);
        border: 1px solid var(--drink-border);
        border-radius: 28px;
        box-shadow: 0 22px 50px rgba(8, 42, 38, 0.08);
    }

    .checkout-step {
        width: 48px;
        height: 48px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: #008b7a !important;
        color: #ffffff !important;
        border: 2px solid rgba(255, 255, 255, 0.86);
        box-shadow: 0 14px 30px rgba(0, 107, 95, 0.24);
        flex: 0 0 auto;
        font-size: 1.15rem;
    }

    .checkout-step i,
    .payment-icon i,
    .shipping-auto-icon i,
    .voucher-icon i {
        display: block;
        line-height: 1;
    }

    .checkout-panel {
        border: 1px solid var(--drink-border);
        border-radius: 24px;
        background: rgba(255, 255, 255, 0.96);
        box-shadow: 0 18px 44px rgba(8, 42, 38, 0.06);
    }

    .checkout-input {
        border-color: var(--drink-border);
        border-radius: 16px;
        padding: 0.85rem 1rem;
        background: #fbfffe;
    }

    .checkout-input:focus {
        border-color: var(--drink-primary);
        box-shadow: 0 0 0 0.2rem rgba(0, 139, 122, 0.12);
    }

    .payment-option {
        position: relative;
        cursor: pointer;
    }

    .payment-option input {
        position: absolute;
        inset: 0;
        opacity: 0;
        cursor: pointer;
    }

    .payment-card {
        min-height: 104px;
        border: 1.5px solid var(--drink-border);
        border-radius: 20px;
        background: #ffffff;
        transition: transform 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
    }

    .payment-option:hover .payment-card,
    .payment-option input:checked + .payment-card {
        transform: translateY(-3px);
        border-color: var(--drink-primary);
        box-shadow: 0 16px 34px rgba(0, 139, 122, 0.14);
    }

    .payment-icon {
        width: 44px;
        height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 15px;
        background: var(--drink-soft);
        color: var(--drink-primary);
        font-size: 1.2rem;
        flex: 0 0 auto;
    }

    .checkout-hero .checkout-step {
        width: 54px;
        height: 54px;
        background: linear-gradient(135deg, #008b7a, #006f62) !important;
        color: #ffffff !important;
        opacity: 1;
        font-size: 1.28rem;
    }

    .checkout-item-img {
        width: 64px;
        height: 64px;
        border-radius: 16px;
        object-fit: cover;
        background: var(--drink-soft);
        flex: 0 0 auto;
    }

    .summary-card {
        position: sticky;
        top: 96px;
        border-color: rgba(0, 139, 122, 0.16);
        box-shadow: 0 24px 62px rgba(8, 42, 38, 0.09);
    }

    .delivery-line {
        position: relative;
        padding-left: 2rem;
    }

    .delivery-line::before {
        content: "";
        position: absolute;
        left: 0.48rem;
        top: 1.5rem;
        bottom: 0.2rem;
        width: 2px;
        background: var(--drink-border);
    }

    .delivery-dot {
        position: absolute;
        left: 0;
        top: 0.25rem;
        width: 1rem;
        height: 1rem;
        border-radius: 50%;
        background: var(--drink-primary);
        box-shadow: 0 0 0 6px var(--drink-soft);
    }

    .shipping-option {
        position: relative;
        cursor: pointer;
    }

    .shipping-option input {
        position: absolute;
        inset: 0;
        opacity: 0;
        cursor: pointer;
    }

    .shipping-card {
        border: 1.5px solid var(--drink-border);
        border-radius: 18px;
        background: #ffffff;
        transition: transform 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
    }

    .shipping-option:hover .shipping-card,
    .shipping-option input:checked + .shipping-card {
        transform: translateY(-2px);
        border-color: var(--drink-primary);
        box-shadow: 0 14px 30px rgba(0, 139, 122, 0.12);
    }

    .shipping-auto-card {
        border: 1px solid var(--drink-border);
        border-radius: 18px;
        background: linear-gradient(135deg, #f7fffd, #ffffff);
    }

    .shipping-auto-icon {
        width: 42px;
        height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        background: var(--drink-primary);
        color: #ffffff;
        flex: 0 0 auto;
    }

    .voucher-box {
        border: 1px dashed rgba(0, 139, 122, 0.34);
        border-radius: 20px;
        background: linear-gradient(135deg, #f7fffd, #ffffff);
    }

    .checkout-voucher-panel {
        border: 1px solid var(--drink-border);
        border-radius: 18px;
        background: #ffffff;
        box-shadow: 0 12px 28px rgba(8, 42, 38, 0.05);
    }

    .voucher-icon {
        color: var(--drink-primary);
        font-size: 1.45rem;
    }

    .voucher-select-link {
        border: 0;
        background: transparent;
        color: var(--drink-primary);
        font-weight: 800;
        padding: 0;
    }

    .voucher-selected-text {
        color: var(--drink-muted);
        font-size: 0.92rem;
    }

    .voucher-modal .modal-content {
        border: 0;
        border-radius: 22px;
        overflow: hidden;
        box-shadow: 0 26px 70px rgba(8, 42, 38, 0.24);
    }

    .voucher-modal .modal-header,
    .voucher-modal .modal-footer {
        padding: 1.4rem 1.8rem;
    }

    .voucher-modal .modal-body {
        padding: 1.2rem 1.8rem;
        max-height: min(62vh, 560px);
        overflow-y: auto;
        background: #f7fbfa;
        overscroll-behavior: contain;
    }

    .voucher-modal .modal-footer {
        position: sticky;
        bottom: 0;
        z-index: 2;
        justify-content: space-between;
        gap: 1rem;
        background: #ffffff;
        box-shadow: 0 -12px 28px rgba(8, 42, 38, 0.07);
    }

    .voucher-modal .modal-footer .btn {
        min-width: 156px;
        border-radius: 999px !important;
        font-weight: 800;
    }

    .voucher-search-box {
        background: #ffffff;
        padding: 1rem;
        border: 1px solid var(--drink-border);
        border-radius: 18px;
    }

    .voucher-search-box .form-control {
        min-height: 46px;
        border-radius: 14px;
        background: #ffffff;
        border-color: var(--drink-border);
        box-shadow: none;
    }

    .voucher-apply-btn {
        min-width: 116px;
        min-height: 46px;
        border-radius: 14px;
        background: var(--drink-primary);
        color: #ffffff;
        border-color: var(--drink-primary);
        font-weight: 800;
    }

    .voucher-group-title {
        color: var(--drink-ink);
        font-size: 1.1rem;
        font-weight: 800;
    }

    .voucher-ticket {
        position: relative;
        display: flex;
        min-height: 128px;
        border: 1px solid rgba(0, 139, 122, 0.14);
        border-radius: 18px;
        background: #ffffff;
        overflow: hidden;
        box-shadow: 0 12px 28px rgba(8, 42, 38, 0.06);
        transition: border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
    }

    .voucher-ticket.active {
        border-color: var(--drink-primary);
        box-shadow: 0 18px 34px rgba(0, 139, 122, 0.13);
        transform: translateY(-1px);
    }

    .voucher-ticket[data-voucher-card] {
        cursor: pointer;
    }

    .voucher-ticket.is-disabled {
        opacity: 0.58;
        cursor: not-allowed;
        box-shadow: none;
    }

    .voucher-ticket::before,
    .voucher-ticket::after {
        content: "";
        position: absolute;
        left: 132px;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: #f7fbfa;
        border: 1px solid rgba(0, 139, 122, 0.14);
        z-index: 2;
    }

    .voucher-ticket::before {
        top: -9px;
    }

    .voucher-ticket::after {
        bottom: -9px;
    }

    .voucher-ticket-brand {
        width: 140px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        gap: 0.6rem;
        background: linear-gradient(135deg, #8fd8ce, #56bfb0);
        color: #ffffff;
        text-align: center;
        flex: 0 0 auto;
    }

    .voucher-ticket-brand .brand-circle {
        width: 58px;
        height: 58px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.18);
        font-size: 1.55rem;
    }

    .voucher-ticket-body {
        flex: 1;
        padding: 1rem 1.1rem;
        min-width: 0;
    }

    .voucher-limit {
        display: inline-flex;
        align-items: center;
        padding: 0.1rem 0.45rem;
        border-radius: 3px;
        color: #ffffff;
        background: #ffb351;
        font-size: 0.78rem;
        font-weight: 800;
    }

    .voucher-only {
        display: inline-flex;
        align-items: center;
        padding: 0.12rem 0.45rem;
        border: 1px solid var(--drink-primary);
        color: var(--drink-primary);
        font-size: 0.78rem;
        font-weight: 700;
    }

    .voucher-progress {
        height: 4px;
        border-radius: 999px;
        overflow: hidden;
        background: #f0d4d0;
    }

    .voucher-progress span {
        display: block;
        width: 42%;
        height: 100%;
        background: var(--drink-primary);
    }

    .voucher-radio {
        width: 22px;
        height: 22px;
        border: 1.8px solid #c8d0ce;
        border-radius: 50%;
        background: #ffffff;
        flex: 0 0 auto;
        margin: auto 1rem auto 0;
        position: relative;
    }

    .voucher-ticket.active .voucher-radio {
        border-color: var(--drink-primary);
    }

    .voucher-ticket.active .voucher-radio::after {
        content: "";
        position: absolute;
        inset: 5px;
        border-radius: 50%;
        background: var(--drink-primary);
    }

    .voucher-radio:disabled {
        opacity: 0.65;
        cursor: not-allowed;
    }

    .voucher-warning {
        background: #fff8e8;
        color: #9b4a1d;
        border: 1px solid #ffe2b8;
        border-radius: 16px;
        padding: 0.75rem 1rem;
        font-weight: 600;
    }

    .location-card {
        border: 1px solid var(--drink-border);
        border-radius: 16px;
        background: #f7fffd;
    }

    .checkout-address-panel {
        overflow: hidden;
    }

    .address-panel-head {
        border-bottom: 1px solid var(--drink-border);
        background: linear-gradient(135deg, #ffffff, #f2fffb);
    }

    .selected-address-row,
    .address-choice-row {
        display: flex;
        gap: 1rem;
        align-items: flex-start;
    }

    .address-choice-row {
        padding: 1.2rem 0;
        border-bottom: 1px solid #eeeeee;
    }

    .address-choice-row:last-child {
        border-bottom: 0;
    }

    .address-radio {
        width: 20px;
        height: 20px;
        margin-top: 0.25rem;
        border: 2px solid #b9c7c4;
        border-radius: 50%;
        position: relative;
        flex: 0 0 auto;
    }

    .address-radio.active {
        border-color: var(--drink-primary);
    }

    .address-radio.active::after {
        content: "";
        position: absolute;
        inset: 4px;
        border-radius: 50%;
        background: var(--drink-primary);
    }

    .address-selected-mark {
        width: 22px;
        height: 22px;
        margin-top: 0.2rem;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--drink-primary);
        color: #ffffff;
        font-size: 0.78rem;
        flex: 0 0 auto;
    }

    .address-person {
        color: var(--drink-ink);
        font-size: 1.05rem;
        font-weight: 800;
    }

    .address-phone-divider {
        width: 1px;
        height: 20px;
        display: inline-block;
        margin: 0 0.75rem;
        vertical-align: middle;
        background: #d7dfdd;
    }

    .address-line {
        color: var(--drink-muted);
        line-height: 1.55;
    }

    .address-badge {
        display: inline-flex;
        align-items: center;
        margin-top: 0.55rem;
        padding: 0.12rem 0.45rem;
        border: 1px solid var(--drink-primary);
        color: var(--drink-primary);
        font-size: 0.78rem;
        font-weight: 700;
    }

    .address-modal .modal-content {
        border: 0;
        border-radius: 4px;
        box-shadow: 0 22px 55px rgba(0, 0, 0, 0.22);
    }

    .address-modal .modal-header,
    .address-modal .modal-footer {
        padding: 1.4rem 1.8rem;
    }

    .address-modal .modal-body {
        padding: 1.2rem 1.8rem;
    }

    .address-modal-title {
        font-size: 1.35rem;
        font-weight: 700;
    }

    .address-modal-field {
        border-radius: 2px;
        border-color: #d8d8d8;
        background: #ffffff;
        font-weight: 500;
    }

    .address-modal-field:focus {
        border-color: var(--drink-primary);
        box-shadow: 0 0 0 0.18rem rgba(0, 139, 122, 0.12);
    }

    .address-map-shell {
        min-height: 150px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #e6eeee;
        background:
            linear-gradient(32deg, transparent 49%, rgba(0, 139, 122, 0.08) 50%, transparent 51%),
            linear-gradient(145deg, transparent 49%, rgba(0, 139, 122, 0.06) 50%, transparent 51%),
            #f6fbfa;
        color: var(--drink-muted);
    }

    .address-type-btn {
        min-width: 118px;
        border: 1px solid #dddddd;
        border-radius: 2px;
        background: #ffffff;
        color: #333333;
        font-weight: 600;
        box-shadow: none;
    }

    .address-type-btn.active,
    .address-type-btn:hover {
        border-color: var(--drink-primary);
        color: var(--drink-primary);
        background: #f4fffc;
    }

    .btn-address-primary {
        border-radius: 2px;
        background: var(--drink-primary);
        border-color: var(--drink-primary);
        color: #ffffff;
        min-width: 170px;
    }

    .btn-address-primary:hover {
        background: var(--drink-primary-dark);
        border-color: var(--drink-primary-dark);
        color: #ffffff;
    }

    .btn-address-link {
        color: var(--drink-primary);
        border: 0;
        background: transparent;
        font-weight: 700;
        padding: 0;
    }

    .address-empty {
        border: 1px dashed var(--drink-border);
        background: #fbfffe;
        color: var(--drink-muted);
        padding: 1rem;
    }
</style>

<section class="checkout-page py-5">
    <div class="container">
        @if(session('error'))
            <div class="alert alert-danger rounded-4 border-0">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger rounded-4 border-0">
                <div class="fw-bold mb-1">Vui lòng kiểm tra lại thông tin thanh toán.</div>
                <div>{{ $errors->first() }}</div>
            </div>
        @endif

        <form method="POST" action="{{ route('checkout.process') }}">
            @csrf
            <div class="row g-4 align-items-start">
                <div class="col-lg-7">
                    <div class="checkout-panel checkout-address-panel mb-4">
                        <div class="address-panel-head d-flex flex-wrap justify-content-between align-items-center gap-3 px-4 py-3">
                            <div class="d-flex align-items-center gap-3">
                                <span class="checkout-step"><i class="bi bi-geo-alt"></i></span>
                                <div>
                                    <h2 class="h4 fw-bold mb-1">Địa chỉ nhận hàng</h2>
                                    <p class="text-secondary mb-0">Chọn hoặc thêm địa chỉ theo bố cục gọn như sàn thương mại.</p>
                                </div>
                            </div>
                            <button type="button" class="btn-address-link" data-bs-toggle="modal" data-bs-target="#addressListModal">
                                Thay đổi
                            </button>
                        </div>

                        <div class="p-4">
                            <input
                                id="shipping_address_ui"
                                name="shipping_address_ui"
                                type="hidden"
                                value="{{ old('shipping_address_ui', $primaryAddress) }}"
                            >
                            <input
                                id="shipping_area_ui"
                                name="shipping_area_ui"
                                type="hidden"
                                value="{{ old('shipping_area_ui', $primaryArea) }}"
                            >

                            <div class="selected-address-row">
                                <span class="address-selected-mark"><i class="bi bi-check-lg"></i></span>
                                <div class="flex-grow-1">
                                    <div class="address-person mb-1">
                                        <span id="selectedReceiver">{{ $user->name }}</span>
                                        <span class="address-phone-divider"></span>
                                        <span id="selectedPhone">{{ $user->phone ?: 'Chưa cập nhật' }}</span>
                                    </div>
                                    <div class="address-line" id="selectedAddressText">
                                        {{ $primaryAddressText ?: 'Chưa có địa chỉ. Bấm Thay đổi để thêm địa chỉ nhận hàng.' }}
                                    </div>
                                    <span class="address-badge" id="selectedDefaultBadge">Mặc định</span>
                                </div>
                                <button type="button" class="btn-address-link" data-open-address-edit>Cập nhật</button>
                            </div>

                            @if($errors->has('shipping_address_ui') || $errors->has('shipping_area_ui'))
                                <div class="text-danger small mt-3">
                                    {{ $errors->first('shipping_address_ui') ?: $errors->first('shipping_area_ui') }}
                                </div>
                            @endif

                            @if(empty($user->phone))
                                <div class="alert alert-warning border-0 rounded-4 mt-4 mb-0">
                                    Bạn chưa có số điện thoại. Có thể cập nhật trong mục địa chỉ để đơn hàng rõ ràng hơn.
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="checkout-panel p-4 p-md-5 mb-4">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <span class="checkout-step"><i class="bi bi-truck"></i></span>
                            <div>
                                <h2 class="h4 fw-bold mb-1">Phương thức giao hàng</h2>
                                <p class="text-secondary mb-0">Phí tính tự động theo địa chỉ nhận.</p>
                            </div>
                        </div>

                        <input
                            type="hidden"
                            name="shipping_method_ui"
                            value="standard"
                            data-method-label="{{ $shippingMethods['standard']['label'] }}"
                            data-method-fee="{{ $shippingMethods['standard']['surcharge'] }}"
                            data-method-eta="{{ $shippingMethods['standard']['eta'] }}"
                        >

                        <div class="shipping-auto-card p-3 p-md-4">
                            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                                <div class="d-flex gap-3">
                                    <span class="shipping-auto-icon"><i class="bi bi-geo-alt"></i></span>
                                    <div>
                                        <div class="fw-bold"><i class="bi bi-truck me-2 text-primary"></i>Giao tiêu chuẩn</div>
                                        <div class="text-secondary small">
                                            <span id="shippingEstimateDetail">{{ $shippingQuote['estimate_label'] }} · {{ $shippingQuote['estimate_detail'] }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="text-secondary small">Phí dự kiến</div>
                                    <div class="h5 text-primary fw-bold mb-0" id="shippingInlineFee">{{ number_format($shippingFee, 0, ',', '.') }}đ</div>
                                </div>
                            </div>
                            <div class="border-top mt-3 pt-3 d-flex flex-wrap justify-content-between gap-2 small">
                                <span class="text-secondary">Hệ thống tự tính sau khi bạn chọn hoặc cập nhật địa chỉ nhận hàng.</span>
                                <span class="fw-semibold" id="shippingDistanceLabel">{{ $shippingQuote['distance_label'] }}</span>
                            </div>
                        </div>
                        @error('shipping_method_ui')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="checkout-voucher-panel p-4 mb-4">
                        <input type="hidden" name="voucher_code" id="selectedVoucherCode" value="{{ $selectedVoucherCode }}">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <span class="voucher-icon"><i class="bi bi-ticket-perforated"></i></span>
                                <div>
                                    <h2 class="h5 fw-bold mb-1">Chill Drink Voucher</h2>
                                    <div class="voucher-selected-text" id="selectedVoucherText">
                                        {{ $selectedVoucherLabel ? 'Đã chọn: ' . $selectedVoucherLabel : 'Chưa chọn voucher' }}
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="voucher-select-link" data-bs-toggle="modal" data-bs-target="#voucherModal">
                                Chọn voucher
                            </button>
                        </div>
                        @error('voucher_code')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="checkout-panel p-4 p-md-5 mb-4">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <span class="checkout-step"><i class="bi bi-wallet2"></i></span>
                            <div>
                                <h2 class="h4 fw-bold mb-1">Phương thức thanh toán</h2>
                                <p class="text-secondary mb-0">Chọn cách thanh toán phù hợp với bạn.</p>
                            </div>
                        </div>

                        <div class="row g-3">
                            @foreach($paymentOptions as $value => $option)
                                <div class="col-md-6">
                                    <label class="payment-option d-block h-100">
                                        <input
                                            type="radio"
                                            name="payment_method"
                                            value="{{ $value }}"
                                            {{ old('payment_method', 'cod') === $value ? 'checked' : '' }}
                                            required
                                        >
                                        <div class="payment-card p-3 d-flex gap-3 h-100">
                                            <span class="payment-icon"><i class="bi {{ $option['icon'] }}"></i></span>
                                            <span>
                                                <span class="fw-bold d-block mb-1">{{ $option['title'] }}</span>
                                                <span class="text-secondary small">{{ $option['desc'] }}</span>
                                            </span>
                                        </div>
                                    </label>
                                </div>
                            @endforeach
                        </div>

                        @error('payment_method')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="checkout-panel p-4 p-md-5">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <span class="checkout-step"><i class="bi bi-chat-left-text"></i></span>
                            <div>
                                <h2 class="h4 fw-bold mb-1">Ghi chú đơn hàng</h2>
                                <p class="text-secondary mb-0">Thêm yêu cầu về đường, đá hoặc thời gian nhận hàng nếu cần.</p>
                            </div>
                        </div>

                        <textarea
                            id="note"
                            name="note"
                            rows="5"
                            class="form-control checkout-input @error('note') is-invalid @enderror"
                            placeholder="Ví dụ: ít đá, giao trước 15 phút, gọi trước khi giao..."
                        >{{ old('note') }}</textarea>
                        @error('note')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="checkout-panel summary-card p-4 p-md-5">
                        <div class="d-flex align-items-center justify-content-between gap-3 mb-4">
                            <div>
                                <h2 class="h4 fw-bold mb-1">Đơn hàng của bạn</h2>
                                <p class="text-secondary mb-0">{{ count($cart) }} món trong giỏ</p>
                            </div>
                            <span class="payment-icon"><i class="bi bi-receipt"></i></span>
                        </div>

                        <div class="vstack gap-3 mb-4">
                            @foreach(collect($cart)->take(3) as $item)
                                <div class="d-flex gap-3 align-items-center">
                                    <img
                                        src="{{ $item['image'] ?? 'https://images.unsplash.com/photo-1544145945-f90425340c7e?auto=format&fit=crop&w=400&q=80' }}"
                                        alt="{{ $item['name'] }}"
                                        class="checkout-item-img"
                                    >
                                    <div class="flex-grow-1">
                                        <div class="fw-bold">{{ $item['name'] }}</div>
                                        <div class="text-secondary small">
                                            {{ $item['size_label'] ?? 'Size M' }} · Số lượng: {{ $item['quantity'] }}
                                        </div>
                                    </div>
                                    <strong>{{ number_format($item['price'] * $item['quantity'], 0, ',', '.') }}đ</strong>
                                </div>
                            @endforeach

                            @if(count($cart) > 3)
                                @foreach(collect($cart)->skip(3) as $item)
                                    <div class="d-flex gap-3 align-items-center d-none" data-checkout-extra-item>
                                        <img
                                            src="{{ $item['image'] ?? 'https://images.unsplash.com/photo-1544145945-f90425340c7e?auto=format&fit=crop&w=400&q=80' }}"
                                            alt="{{ $item['name'] }}"
                                            class="checkout-item-img"
                                        >
                                        <div class="flex-grow-1">
                                            <div class="fw-bold">{{ $item['name'] }}</div>
                                            <div class="text-secondary small">
                                                {{ $item['size_label'] ?? 'Size M' }} · Số lượng: {{ $item['quantity'] }}
                                            </div>
                                        </div>
                                        <strong>{{ number_format($item['price'] * $item['quantity'], 0, ',', '.') }}đ</strong>
                                    </div>
                                @endforeach

                                <button
                                    type="button"
                                    class="btn btn-outline-primary btn-sm rounded-pill align-self-start px-3"
                                    data-toggle-checkout-items
                                    data-total-items="{{ count($cart) }}"
                                >
                                    Xem tất cả {{ count($cart) }} món
                                </button>
                            @endif
                        </div>

                        <div class="border-top pt-4">
                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-secondary">Tạm tính</span>
                                <span>{{ number_format($total, 0, ',', '.') }}đ</span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-secondary">Phí vận chuyển</span>
                                <span class="text-primary fw-semibold" id="summaryShippingFee">{{ number_format($shippingFee, 0, ',', '.') }}đ</span>
                            </div>
                            <div class="d-flex justify-content-between mb-3 small">
                                <span class="text-secondary">Khoảng cách</span>
                                <span id="summaryShippingDistance">{{ $shippingQuote['distance_label'] }} · {{ $shippingQuote['method_label'] }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-secondary">Voucher</span>
                                <span id="summaryVoucherText">{{ $discount > 0 ? '-' . number_format($discount, 0, ',', '.') . 'đ' : 'Chưa áp dụng' }}</span>
                            </div>
                            <div class="d-flex justify-content-between h4 fw-bold mb-4">
                                <span>Tổng cộng</span>
                                <span class="text-primary" id="summaryGrandTotal">{{ number_format($grandTotal, 0, ',', '.') }}đ</span>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="bi bi-check2-circle me-2"></i>Đặt hàng
                        </button>
                        <a href="{{ route('cart.index') }}" class="btn btn-outline-primary w-100 mt-3">Quay lại giỏ hàng</a>

                        <div class="delivery-line mt-4">
                            <span class="delivery-dot"></span>
                            <div class="fw-bold">Xác nhận đơn</div>
                            <p class="text-secondary small mb-3">Hệ thống ghi nhận đơn sau khi bạn bấm đặt hàng.</p>

                            <span class="delivery-dot" style="top: 5.3rem;"></span>
                            <div class="fw-bold">Chuẩn bị đồ uống</div>
                            <p class="text-secondary small mb-3">Nhân viên pha chế theo đúng ghi chú của bạn.</p>

                            <span class="delivery-dot" style="top: 10.35rem;"></span>
                            <div class="fw-bold">Giao tới bạn</div>
                            <p class="text-secondary small mb-0">Đồ uống được giao nhanh và giữ mát khi đến nơi.</p>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<div class="modal fade address-modal" id="addressListModal" tabindex="-1" aria-labelledby="addressListTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h2 class="address-modal-title mb-0" id="addressListTitle">Địa chỉ của tôi</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <div id="addressList"></div>
            </div>
            <div class="modal-footer border-top justify-content-end">
                <button type="button" class="btn btn-address-primary" data-open-address-add>
                    <i class="bi bi-plus-lg me-2"></i>Thêm địa chỉ mới
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade address-modal" id="addressEditModal" tabindex="-1" aria-labelledby="addressEditTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h2 class="address-modal-title mb-0" id="addressEditTitle">Chỉnh sửa địa chỉ</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body pt-0">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small text-secondary mb-1" for="editAddressName">Họ và tên</label>
                        <input id="editAddressName" type="text" class="form-control address-modal-field" value="{{ $user->name }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-secondary mb-1" for="editAddressPhone">Số điện thoại</label>
                        <input id="editAddressPhone" type="text" class="form-control address-modal-field" value="{{ $user->phone }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label small text-secondary mb-1" for="editAddressArea">Tỉnh/Thành phố, Quận/Huyện</label>
                        <input id="editAddressArea" type="text" class="form-control address-modal-field" value="{{ $primaryArea }}" placeholder="Ví dụ: Thanh Hóa, Phường Quảng Phú">
                    </div>
                    <div class="col-12">
                        <label class="form-label small text-secondary mb-1" for="editAddressStreet">Địa chỉ cụ thể</label>
                        <textarea id="editAddressStreet" rows="3" class="form-control address-modal-field" placeholder="Số nhà, tên đường, thôn/xóm...">{{ $primaryAddress }}</textarea>
                    </div>
                    <div class="col-12">
                        <div class="address-map-shell" id="editAddressMapShell">
                            <button type="button" class="btn btn-outline-primary rounded-1" data-locate-address="edit">
                                <i class="bi bi-crosshair me-2"></i>Thêm vị trí
                            </button>
                        </div>
                        <div class="form-text" id="editAddressStatus">Có thể bấm thêm vị trí để tự điền địa chỉ từ trình duyệt.</div>
                    </div>
                    <div class="col-12">
                        <div class="mb-2 fw-semibold">Loại địa chỉ:</div>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn address-type-btn active" data-address-type="Nhà Riêng" data-address-scope="edit">Nhà Riêng</button>
                            <button type="button" class="btn address-type-btn" data-address-type="Văn Phòng" data-address-scope="edit">Văn Phòng</button>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-check text-secondary">
                            <input id="editAddressDefault" class="form-check-input" type="checkbox" checked>
                            <span class="form-check-label">Đặt làm địa chỉ mặc định</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-link text-dark text-decoration-none" data-return-address-list>Trở lại</button>
                <button type="button" class="btn btn-address-primary" id="saveEditedAddress">Hoàn thành</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade address-modal" id="addressAddModal" tabindex="-1" aria-labelledby="addressAddTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h2 class="address-modal-title mb-0" id="addressAddTitle">Địa chỉ mới</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body pt-0">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small text-secondary mb-1" for="newAddressName">Họ và tên</label>
                        <input id="newAddressName" type="text" class="form-control address-modal-field" placeholder="Họ và tên" value="{{ $user->name }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-secondary mb-1" for="newAddressPhone">Số điện thoại</label>
                        <input id="newAddressPhone" type="text" class="form-control address-modal-field" placeholder="Số điện thoại" value="{{ $user->phone }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label small text-secondary mb-1" for="newAddressArea">Tỉnh/Thành phố, Quận/Huyện</label>
                        <input id="newAddressArea" type="text" class="form-control address-modal-field" placeholder="Tỉnh/Thành phố, Quận/Huyện">
                    </div>
                    <div class="col-12">
                        <label class="form-label small text-secondary mb-1" for="newAddressStreet">Địa chỉ cụ thể</label>
                        <textarea id="newAddressStreet" rows="3" class="form-control address-modal-field" placeholder="Địa chỉ cụ thể"></textarea>
                    </div>
                    <div class="col-12">
                        <div class="address-map-shell" id="newAddressMapShell">
                            <button type="button" class="btn btn-outline-primary rounded-1" data-locate-address="new">
                                <i class="bi bi-crosshair me-2"></i>Thêm vị trí
                            </button>
                        </div>
                        <div class="form-text" id="newAddressStatus">Có thể bấm thêm vị trí để tự điền địa chỉ từ trình duyệt.</div>
                    </div>
                    <div class="col-12">
                        <div class="mb-2 fw-semibold">Loại địa chỉ:</div>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn address-type-btn active" data-address-type="Nhà Riêng" data-address-scope="new">Nhà Riêng</button>
                            <button type="button" class="btn address-type-btn" data-address-type="Văn Phòng" data-address-scope="new">Văn Phòng</button>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-check text-secondary">
                            <input id="newAddressDefault" class="form-check-input" type="checkbox">
                            <span class="form-check-label">Đặt làm địa chỉ mặc định</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-link text-dark text-decoration-none" data-return-address-list>Trở lại</button>
                <button type="button" class="btn btn-address-primary" id="saveNewAddress">Hoàn thành</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade voucher-modal" id="voucherModal" tabindex="-1" aria-labelledby="voucherModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h2 class="address-modal-title mb-0" id="voucherModalTitle">Chọn Chill Drink Voucher</h2>
                <div class="ms-auto d-flex align-items-center gap-2 text-secondary">
                    <span>Hỗ trợ</span>
                    <i class="bi bi-question-circle"></i>
                </div>
                <button type="button" class="btn-close ms-3" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <div class="voucher-search-box d-flex flex-column flex-md-row align-items-md-center gap-3 mb-3">
                    <label for="voucherCodeInput" class="fw-semibold text-secondary flex-shrink-0">Mã Voucher</label>
                    <input id="voucherCodeInput" type="text" class="form-control" placeholder="Mã Chill Drink Voucher" value="{{ $selectedVoucherCode }}">
                    <button type="button" class="btn voucher-apply-btn" id="voucherManualApply">Áp dụng</button>
                </div>

                <div class="mb-2">
                    <div class="voucher-group-title">Mã có thể áp dụng</div>
                    <div class="text-secondary">Có thể chọn 1 voucher cho đơn hàng này</div>
                </div>

                <div class="vstack gap-3">
                    @forelse($availableVouchers as $voucher)
                        @php
                            $voucherDiscount = $voucher->discountFor((int) $total);
                            $usageLimit = (int) ($voucher->usage_limit ?? 0);
                            $usagePercent = $usageLimit > 0 ? min(100, (int) round(($voucher->used_count / max(1, $usageLimit)) * 100)) : 22;
                            $voucherLabel = $voucher->code . ' - ' . $voucher->formattedValue();
                            $voucherIcon = $voucher->is_redeemable ? 'bi-gift' : ($voucher->type === 'percent' ? 'bi-percent' : 'bi-ticket-perforated');
                            $hasMinimumOrder = (int) $total >= (int) $voucher->min_order;
                            $hasRank = ! $voucher->required_rank || (($rankOrder[$loyaltyContext['rank'] ?? 'bronze'] ?? 1) >= ($rankOrder[$voucher->required_rank] ?? 1));
                            $hasPoints = ! $voucher->is_redeemable || (int) $voucher->point_cost <= 0 || (int) ($loyaltyContext['points'] ?? 0) >= (int) $voucher->point_cost;
                            $voucherUsable = $voucherDiscount > 0 && $hasMinimumOrder && $hasRank && $hasPoints;
                            $disabledReason = ! $hasMinimumOrder
                                ? 'Cần đơn từ ' . number_format((int) $voucher->min_order, 0, ',', '.') . 'đ'
                                : (! $hasRank
                                    ? 'Cần rank ' . $voucher->rankLabel()
                                    : (! $hasPoints ? 'Cần ' . number_format((int) $voucher->point_cost, 0, ',', '.') . ' điểm' : null));
                        @endphp
                        <div
                            class="voucher-ticket {{ $selectedVoucherCode === $voucher->code && $voucherUsable ? 'active' : '' }} {{ $voucherUsable ? '' : 'is-disabled' }}"
                            @if($voucherUsable) data-voucher-card @endif
                            data-voucher-code="{{ $voucher->code }}"
                            data-voucher-label="{{ $voucherLabel }}"
                            data-voucher-discount="{{ $voucherDiscount }}"
                            data-voucher-disabled="{{ $voucherUsable ? '0' : '1' }}"
                        >
                            <div class="voucher-ticket-brand">
                                <span class="brand-circle"><i class="bi {{ $voucherIcon }}"></i></span>
                                <strong>{{ $voucher->code }}</strong>
                            </div>
                            <div class="voucher-ticket-body">
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                    <span class="voucher-limit">{{ $voucher->usage_limit > 0 ? 'Số lượng có hạn' : 'Không giới hạn' }}</span>
                                    <span class="fw-semibold text-secondary">Giảm {{ $voucher->formattedValue() }}</span>
                                    @if($voucher->max_discount)
                                        <span class="fw-semibold text-secondary">tối đa {{ number_format($voucher->max_discount, 0, ',', '.') }}đ</span>
                                    @endif
                                </div>
                                <div class="text-secondary mb-2">
                                    Đơn tối thiểu {{ number_format((int) $voucher->min_order, 0, ',', '.') }}đ
                                    @if($voucher->required_rank)
                                        · Rank {{ $voucher->rankLabel() }}
                                    @endif
                                    @if($voucher->is_redeemable && $voucher->point_cost > 0)
                                        · {{ number_format($voucher->point_cost, 0, ',', '.') }} điểm
                                    @endif
                                </div>
                                @if($voucherUsable)
                                    <span class="voucher-only mb-2">Giảm {{ number_format($voucherDiscount, 0, ',', '.') }}đ cho đơn hiện tại</span>
                                @else
                                    <span class="voucher-only mb-2">{{ $disabledReason }}</span>
                                @endif
                                <div class="voucher-progress mt-2 mb-1"><span style="width: {{ $usagePercent }}%"></span></div>
                                <div class="small text-secondary">
                                    HSD: {{ optional($voucher->expires_at)->format('d/m/Y H:i') ?: 'Không giới hạn' }}
                                </div>
                            </div>
                            <button type="button" class="voucher-radio" aria-label="Chọn voucher {{ $voucher->code }}" @disabled(! $voucherUsable)></button>
                        </div>
                    @empty
                        <div class="voucher-warning">
                            <i class="bi bi-info-circle me-1"></i> Hiện chưa có voucher đang hoạt động.
                        </div>
                    @endforelse
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Trở lại</button>
                <button type="button" class="btn btn-primary px-4" id="confirmVoucher">
                    <i class="bi bi-check2 me-2"></i>Áp dụng voucher
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const shippingAddressInput = document.getElementById('shipping_address_ui');
        const shippingAreaInput = document.getElementById('shipping_area_ui');
        const selectedReceiver = document.getElementById('selectedReceiver');
        const selectedPhone = document.getElementById('selectedPhone');
        const selectedAddressText = document.getElementById('selectedAddressText');
        const selectedDefaultBadge = document.getElementById('selectedDefaultBadge');
        const addressList = document.getElementById('addressList');

        const addressListModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('addressListModal'));
        const addressEditModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('addressEditModal'));
        const addressAddModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('addressAddModal'));
        const voucherModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('voucherModal'));
        const selectedVoucherCode = document.getElementById('selectedVoucherCode');
        const selectedVoucherText = document.getElementById('selectedVoucherText');
        const summaryVoucherText = document.getElementById('summaryVoucherText');
        const voucherCodeInput = document.getElementById('voucherCodeInput');
        const shippingConfig = {
            subtotal: {{ (int) $total }},
            discount: {{ (int) $discount }},
        };
        const shippingTiers = @json($shippingDistanceOptions);
        const shippingRules = @json(\App\Support\ShippingFee::estimationRules());
        const shippingDistanceLabel = document.getElementById('shippingDistanceLabel');
        const shippingEstimateDetail = document.getElementById('shippingEstimateDetail');
        const shippingInlineFee = document.getElementById('shippingInlineFee');
        const shippingEta = document.getElementById('shippingEta');
        const summaryShippingFee = document.getElementById('summaryShippingFee');
        const summaryShippingDistance = document.getElementById('summaryShippingDistance');
        const summaryGrandTotal = document.getElementById('summaryGrandTotal');

        let selectedAddressId = 'primary';
        let pendingVoucher = {
            code: document.querySelector('[data-voucher-card].active')?.dataset.voucherCode || '',
            label: document.querySelector('[data-voucher-card].active')?.dataset.voucherLabel || '',
            discount: Number(document.querySelector('[data-voucher-card].active')?.dataset.voucherDiscount || {{ (int) $discount }}),
        };
        const addressBook = [
            {
                id: 'primary',
                name: @json($user->name),
                phone: @json($user->phone ?: 'Chưa cập nhật'),
                street: @json($primaryAddress),
                area: @json($primaryArea),
                type: 'Nhà Riêng',
                isDefault: true,
            },
        ];

        function compactAddress(parts) {
            return parts.filter(Boolean).join(', ');
        }

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
            })[char]);
        }

        function formatVnd(amount) {
            return `${Math.max(0, Number(amount) || 0).toLocaleString('vi-VN')}đ`;
        }

        function normalizeAddressText(value) {
            return String(value || '')
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/đ/g, 'd');
        }

        function estimateDistanceFromAddress() {
            const text = normalizeAddressText(`${shippingAddressInput.value} ${shippingAreaInput.value}`);

            if (!text.trim()) {
                return {
                    distance: 3.5,
                    label: 'Chờ địa chỉ',
                    detail: 'chưa có địa chỉ cụ thể',
                };
            }

            for (const rule of shippingRules) {
                const matched = (rule.keywords || []).some((keyword) => text.includes(normalizeAddressText(keyword)));

                if (matched) {
                    return rule;
                }
            }

            return {
                distance: 3.5,
                label: 'Ước tính mặc định',
                detail: 'cần nhân viên xác nhận lại',
            };
        }

        function tierForDistance(distance) {
            return shippingTiers.find((tier) => Number(distance) <= Number(tier.max)) || shippingTiers[shippingTiers.length - 1];
        }

        function updateShippingSummary() {
            const methodInput = document.querySelector('input[name="shipping_method_ui"]:checked')
                || document.querySelector('input[name="shipping_method_ui"]');

            if (!methodInput) {
                return;
            }

            const estimate = estimateDistanceFromAddress();
            const tier = tierForDistance(estimate.distance);
            const distanceFee = Number(tier.base_fee || 0);
            const methodFee = Number(methodInput.dataset.methodFee || 0);
            const shippingFee = distanceFee + methodFee;
            const grandTotal = shippingConfig.subtotal + shippingFee - Number(shippingConfig.discount || 0);
            const distanceLabel = tier.label || '';
            const methodLabel = methodInput.dataset.methodLabel || '';

            shippingDistanceLabel.textContent = distanceLabel;
            shippingEstimateDetail.textContent = `${estimate.label} · ${estimate.detail}`;
            shippingInlineFee.textContent = formatVnd(shippingFee);
            if (shippingEta) {
                shippingEta.textContent = methodInput.dataset.methodEta || '';
            }
            summaryShippingFee.textContent = formatVnd(shippingFee);
            summaryShippingDistance.textContent = `${distanceLabel} · ${methodLabel}`;
            summaryGrandTotal.textContent = formatVnd(grandTotal);
        }

        function getAddressById(id) {
            return addressBook.find((item) => item.id === id) || addressBook[0];
        }

        function applyAddress(address) {
            selectedAddressId = address.id;
            selectedReceiver.textContent = address.name || 'Chưa cập nhật';
            selectedPhone.textContent = address.phone || 'Chưa cập nhật';
            selectedAddressText.textContent = compactAddress([address.street, address.area]) || 'Chưa có địa chỉ. Bấm Thay đổi để thêm địa chỉ nhận hàng.';
            selectedDefaultBadge.classList.toggle('d-none', !address.isDefault);
            shippingAddressInput.value = address.street || '';
            shippingAreaInput.value = address.area || '';
            renderAddressList();
            updateShippingSummary();
        }

        function renderAddressList() {
            if (!addressList) {
                return;
            }

            const rows = addressBook.map((address) => {
                const isActive = address.id === selectedAddressId;
                const fullAddress = compactAddress([address.street, address.area]) || 'Chưa có địa chỉ cụ thể';

                return `
                    <div class="address-choice-row" data-address-row="${address.id}">
                        <button type="button" class="address-radio ${isActive ? 'active' : ''}" data-select-address="${address.id}" aria-label="Chọn địa chỉ"></button>
                        <div class="flex-grow-1">
                            <div class="address-person mb-1">
                                <span>${escapeHtml(address.name || 'Chưa cập nhật')}</span>
                                <span class="address-phone-divider"></span>
                                <span class="fw-semibold text-secondary">${escapeHtml(address.phone || 'Chưa cập nhật')}</span>
                            </div>
                            <div class="address-line">${escapeHtml(fullAddress)}</div>
                            ${address.isDefault ? '<span class="address-badge">Mặc định</span>' : ''}
                        </div>
                        <button type="button" class="btn-address-link" data-edit-address="${address.id}">Cập nhật</button>
                    </div>
                `;
            }).join('');

            addressList.innerHTML = rows || '<div class="address-empty">Bạn chưa có địa chỉ nào. Hãy thêm địa chỉ mới để đặt hàng.</div>';
        }

        function setTypeActive(scope, type) {
            document.querySelectorAll(`[data-address-scope="${scope}"]`).forEach((button) => {
                button.classList.toggle('active', button.dataset.addressType === type);
            });
        }

        function getTypeValue(scope) {
            return document.querySelector(`[data-address-scope="${scope}"].active`)?.dataset.addressType || 'Nhà Riêng';
        }

        function fillEditModal(address) {
            document.getElementById('editAddressName').value = address.name || '';
            document.getElementById('editAddressPhone').value = address.phone || '';
            document.getElementById('editAddressArea').value = address.area || '';
            document.getElementById('editAddressStreet').value = address.street || '';
            document.getElementById('editAddressDefault').checked = !!address.isDefault;
            setTypeActive('edit', address.type || 'Nhà Riêng');
        }

        function openEditModal(id = selectedAddressId) {
            fillEditModal(getAddressById(id));
            selectedAddressId = id;
            addressListModal.hide();
            addressEditModal.show();
        }

        function openAddModal() {
            document.getElementById('newAddressName').value = @json($user->name);
            document.getElementById('newAddressPhone').value = @json($user->phone ?? '');
            document.getElementById('newAddressArea').value = '';
            document.getElementById('newAddressStreet').value = '';
            document.getElementById('newAddressDefault').checked = false;
            document.getElementById('newAddressStatus').textContent = 'Có thể bấm thêm vị trí để tự điền địa chỉ từ trình duyệt.';
            setTypeActive('new', 'Nhà Riêng');
            addressListModal.hide();
            addressAddModal.show();
        }

        function setVoucherActive(card) {
            if (!card) {
                return;
            }

            document.querySelectorAll('[data-voucher-card]').forEach((item) => item.classList.remove('active'));
            card.classList.add('active');
            pendingVoucher = {
                code: card.dataset.voucherCode || '',
                label: card.dataset.voucherLabel || '',
                discount: Number(card.dataset.voucherDiscount || 0),
            };
            voucherCodeInput.value = pendingVoucher.code;
        }

        function commitVoucherSelection() {
            selectedVoucherCode.value = pendingVoucher.code || '';
            selectedVoucherText.textContent = pendingVoucher.label ? `Đã chọn: ${pendingVoucher.label}` : 'Chưa chọn voucher';
            shippingConfig.discount = Number(pendingVoucher.discount || 0);
            summaryVoucherText.textContent = shippingConfig.discount > 0
                ? `-${formatVnd(shippingConfig.discount)}`
                : (pendingVoucher.code ? 'Sẽ kiểm tra khi đặt hàng' : 'Chưa áp dụng');
            updateShippingSummary();
            voucherModal.hide();
        }

        document.addEventListener('click', function (event) {
            const selectButton = event.target.closest('[data-select-address]');
            const editButton = event.target.closest('[data-edit-address]');
            const openEditButton = event.target.closest('[data-open-address-edit]');
            const openAddButton = event.target.closest('[data-open-address-add]');
            const returnButton = event.target.closest('[data-return-address-list]');
            const typeButton = event.target.closest('[data-address-type]');
            const locateButton = event.target.closest('[data-locate-address]');
            const voucherCard = event.target.closest('[data-voucher-card]');

            if (selectButton) {
                applyAddress(getAddressById(selectButton.dataset.selectAddress));
                addressListModal.hide();
            }

            if (editButton) {
                openEditModal(editButton.dataset.editAddress);
            }

            if (openEditButton) {
                openEditModal();
            }

            if (openAddButton) {
                openAddModal();
            }

            if (returnButton) {
                addressEditModal.hide();
                addressAddModal.hide();
                addressListModal.show();
            }

            if (typeButton) {
                setTypeActive(typeButton.dataset.addressScope, typeButton.dataset.addressType);
            }

            if (locateButton) {
                locateAddress(locateButton.dataset.locateAddress);
            }

            if (voucherCard && !event.target.closest('a')) {
                setVoucherActive(voucherCard);
            }
        });

        document.getElementById('saveEditedAddress')?.addEventListener('click', function () {
            const address = getAddressById(selectedAddressId);
            address.name = document.getElementById('editAddressName').value.trim();
            address.phone = document.getElementById('editAddressPhone').value.trim();
            address.area = document.getElementById('editAddressArea').value.trim();
            address.street = document.getElementById('editAddressStreet').value.trim();
            address.type = getTypeValue('edit');
            address.isDefault = document.getElementById('editAddressDefault').checked;

            if (address.isDefault) {
                addressBook.forEach((item) => {
                    item.isDefault = item.id === address.id;
                });
            }

            applyAddress(address);
            addressEditModal.hide();
        });

        document.getElementById('saveNewAddress')?.addEventListener('click', function () {
            const address = {
                id: `new-${Date.now()}`,
                name: document.getElementById('newAddressName').value.trim(),
                phone: document.getElementById('newAddressPhone').value.trim(),
                area: document.getElementById('newAddressArea').value.trim(),
                street: document.getElementById('newAddressStreet').value.trim(),
                type: getTypeValue('new'),
                isDefault: document.getElementById('newAddressDefault').checked,
            };

            if (address.isDefault) {
                addressBook.forEach((item) => item.isDefault = false);
            }

            addressBook.push(address);
            applyAddress(address);
            addressAddModal.hide();
        });

        document.getElementById('voucherManualApply')?.addEventListener('click', function () {
            const code = voucherCodeInput.value.trim().toUpperCase();

            if (!code) {
                voucherCodeInput.focus();
                return;
            }

            const matchedCard = Array.from(document.querySelectorAll('[data-voucher-code]'))
                .find((item) => item.dataset.voucherCode === code);

            if (matchedCard) {
                if (matchedCard.dataset.voucherDisabled === '1') {
                    voucherCodeInput.focus();
                    return;
                }

                setVoucherActive(matchedCard);
                commitVoucherSelection();
                return;
            }

            document.querySelectorAll('[data-voucher-card]').forEach((item) => item.classList.remove('active'));
            pendingVoucher = {
                code,
                label: `${code} - Mã nhập thủ công`,
                discount: 0,
            };
            commitVoucherSelection();
        });

        document.getElementById('confirmVoucher')?.addEventListener('click', function () {
            commitVoucherSelection();
        });

        document.querySelectorAll('input[name="shipping_method_ui"]').forEach((input) => {
            input.addEventListener('change', updateShippingSummary);
        });

        document.querySelector('[data-toggle-checkout-items]')?.addEventListener('click', function () {
            const extraItems = document.querySelectorAll('[data-checkout-extra-item]');
            const isOpening = Array.from(extraItems).some((item) => item.classList.contains('d-none'));

            extraItems.forEach((item) => item.classList.toggle('d-none', !isOpening));
            this.textContent = isOpening ? 'Thu gọn' : `Xem tất cả ${this.dataset.totalItems} món`;
        });

        async function reverseGeocode(lat, lng, scope) {
            const status = document.getElementById(scope === 'edit' ? 'editAddressStatus' : 'newAddressStatus');
            const streetInput = document.getElementById(scope === 'edit' ? 'editAddressStreet' : 'newAddressStreet');
            const areaInput = document.getElementById(scope === 'edit' ? 'editAddressArea' : 'newAddressArea');
            const mapShell = document.getElementById(scope === 'edit' ? 'editAddressMapShell' : 'newAddressMapShell');

            status.textContent = 'Đã lấy vị trí, đang chuyển thành địa chỉ...';
            mapShell.innerHTML = `
                <div class="text-center">
                    <div class="fs-2 text-primary mb-1"><i class="bi bi-geo-alt-fill"></i></div>
                    <div class="fw-bold">Vị trí đã xác nhận</div>
                    <a class="text-primary fw-semibold" href="https://www.google.com/maps?q=${lat},${lng}" target="_blank" rel="noopener">Mở Google Maps</a>
                </div>
            `;

            try {
                const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}&accept-language=vi`);
                const data = await response.json();
                const address = data.address || {};
                const streetLine = compactAddress([
                    address.house_number,
                    address.road || address.pedestrian || address.footway,
                    address.neighbourhood || address.suburb,
                ]);
                const areaLine = compactAddress([
                    address.quarter || address.ward || address.suburb || address.village,
                    address.city_district || address.district || address.town,
                    address.city || address.state,
                ]);

                streetInput.value = streetLine || data.display_name || `${lat}, ${lng}`;
                areaInput.value = areaLine || data.display_name || `${lat}, ${lng}`;
                status.textContent = 'Đã tự điền địa chỉ. Bạn có thể chỉnh lại trước khi hoàn thành.';
            } catch (error) {
                streetInput.value = `Vị trí hiện tại: ${lat}, ${lng}`;
                areaInput.value = `Vị trí hiện tại: ${lat}, ${lng}`;
                status.textContent = 'Đã lấy vị trí nhưng chưa đổi được thành địa chỉ chữ. Bạn có thể chỉnh lại thủ công.';
            }
        }

        function locateAddress(scope) {
            const status = document.getElementById(scope === 'edit' ? 'editAddressStatus' : 'newAddressStatus');

            if (!navigator.geolocation) {
                status.textContent = 'Trình duyệt của bạn không hỗ trợ định vị.';
                return;
            }

            status.textContent = 'Đang xin quyền vị trí...';
            navigator.geolocation.getCurrentPosition(function (position) {
                reverseGeocode(position.coords.latitude.toFixed(6), position.coords.longitude.toFixed(6), scope);
            }, function () {
                status.textContent = 'Bạn chưa cấp quyền vị trí hoặc trình duyệt không lấy được vị trí.';
            }, {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0,
            });
        }

        renderAddressList();
        applyAddress(getAddressById(selectedAddressId));
        updateShippingSummary();
    });
</script>
@endsection
