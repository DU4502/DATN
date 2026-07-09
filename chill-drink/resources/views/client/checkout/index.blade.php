@extends('layouts.client')

@section('title', 'Thanh Toán')

@section('content')
@php
    $total = (int) ($subtotal ?? collect($cart)->sum(fn($item) => $item['price'] * $item['quantity']));
    $shippingDistanceOptions = $shippingDistanceOptions ?? \App\Support\ShippingFee::distanceOptions();
    $shippingMethods = $shippingMethods ?? \App\Support\ShippingFee::methods();
    $branches = $branches ?? \App\Models\Branch::where('status', true)->orderBy('name')->get();
    $user = auth()->user();
    $primaryAddress = trim((string) ($user->address ?? ''));
    $primaryArea = trim((string) ($user->area ?? ''));
    $primaryAddressText = trim(collect([$primaryAddress, $primaryArea])->filter()->implode(', '));
    
    // Get user coordinates
    $userLatitude = $userLatitude ?? null;
    $userLongitude = $userLongitude ?? null;
    $hasUserLocation = !empty($userLatitude) && !empty($userLongitude);
    $fulfillmentType = old('fulfillment_type', 'delivery');
    $selectedShippingMethod = old('shipping_method_ui', 'standard');
    $shippingQuote = \App\Support\ShippingFee::quoteForAddress(
        old('shipping_address_ui', $primaryAddress),
        old('shipping_area_ui', $primaryArea),
        $selectedShippingMethod
    );
    $shippingFee = $fulfillmentType === 'pickup' ? 0 : $shippingQuote['total_fee'];
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
    $isShippingVoucher = fn ($voucher) => \Illuminate\Support\Str::contains(\Illuminate\Support\Str::upper((string) $voucher->code), ['SHIP', 'FREE']);
    $shippingVouchers = $availableVouchers->filter($isShippingVoucher)->values();
    $discountVouchers = $availableVouchers->reject($isShippingVoucher)->values();
    $voucherDisplayGroups = collect([
        'Voucher freeship' => $shippingVouchers,
        'Voucher giảm giá' => $discountVouchers,
    ]);
    $selectedVoucherCode = strtoupper(trim((string) old('voucher_code', '')));
    $selectedShippingVoucherCode = strtoupper(trim((string) old('shipping_voucher_code', '')));
    $selectedVoucher = $availableVouchers->first(fn ($voucher) => ! $isShippingVoucher($voucher) && $voucher->code === $selectedVoucherCode && $canUseCheckoutVoucher($voucher));
    $selectedShippingVoucher = $availableVouchers->first(fn ($voucher) => $isShippingVoucher($voucher) && $voucher->code === $selectedShippingVoucherCode && $canUseCheckoutVoucher($voucher));
    $orderDiscount = $selectedVoucher ? $selectedVoucher->discountFor((int) $total) : 0;
    $shippingDiscount = $selectedShippingVoucher ? min($shippingFee, $selectedShippingVoucher->discountFor((int) $total)) : 0;
    $discount = $orderDiscount + $shippingDiscount;
    $selectedVoucherLabels = collect([$selectedVoucher, $selectedShippingVoucher])->filter()->map(fn ($voucher) => $voucher->code . ' - ' . $voucher->formattedValue())->implode(' + ');
    $grandTotal = max(0, $total + $shippingFee - $discount);
@endphp

<style>
    :root {
        --drink-primary: var(--c-primary, #008b7a);
        --drink-primary-dark: var(--c-primary-dark, #006f62);
        --drink-border: var(--c-border, #d5eee8);
        --drink-muted: var(--c-muted, #6b7280);
        --drink-soft: var(--c-primary-light, #edf9f6);
        --drink-ink: var(--c-ink, #111827);
    }

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

    .branch-select-shell {
        position: relative;
    }

    .branch-select-shell .form-select {
        padding-right: 3rem;
        background-image: none;
    }

    .branch-select-chevron {
        position: absolute;
        top: 50%;
        right: 1rem;
        transform: translateY(-50%);
        color: var(--drink-primary);
        pointer-events: none;
        font-size: 1rem;
    }

    .branch-select-shell.is-disabled .form-select {
        background-color: #f5f7f7;
        cursor: not-allowed;
        opacity: 0.8;
    }

    .branch-select-shell.is-disabled .branch-select-chevron {
        color: #94a3b8;
    }

    .branch-select-note {
        margin-top: 0.55rem;
        font-size: 0.85rem;
        color: #b45309;
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
    .checkout-item-actions { display: flex; align-items: center; gap: .35rem; }
    .checkout-item-actions button { display: grid; place-items: center; width: 28px; height: 28px; padding: 0; border: 1px solid var(--drink-border); border-radius: 50%; color: var(--drink-primary); background: #fff; }
    .checkout-item-actions button:hover { background: var(--drink-soft); }
    .checkout-item-actions button:disabled { cursor: not-allowed; opacity: .4; }
    .checkout-item-actions button.is-remove { color: #dc3545; border-color: #f1c5cb; }
    .delivery-choice { display: block; height: 100%; padding: 1rem; border: 1.5px solid var(--drink-border); border-radius: 16px; cursor: pointer; }
    .delivery-choice:has(input:checked) { border-color: var(--drink-primary); background: var(--drink-soft); box-shadow: 0 0 0 3px rgba(13,147,115,.1); }
    .scheduled-delivery-fields { display: none; padding: 1rem; border-radius: 16px; background: #f7fbfa; border: 1px solid var(--drink-border); }
    .scheduled-delivery-fields.is-visible { display: block; }

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

    .voucher-modal .voucher-footer-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .voucher-modal .voucher-footer-actions .btn {
        min-width: 0;
    }

    .voucher-modal .voucher-clear-btn {
        min-width: 0;
        color: #ef4444;
        border-color: rgba(239, 68, 68, 0.2);
        background: #fff5f5;
    }

    .voucher-modal .voucher-clear-btn:hover {
        color: #dc2626;
        border-color: rgba(220, 38, 38, 0.32);
        background: #fff1f2;
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

    .voucher-ticket.is-shipping .voucher-ticket-brand {
        background: linear-gradient(135deg, #50c7b8, #0d9373);
    }

    .voucher-ticket.is-discount .voucher-ticket-brand {
        background: linear-gradient(135deg, #8fd8ce, #56bfb0);
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

    .voucher-kind {
        display: inline-flex;
        align-items: center;
        padding: 0.1rem 0.45rem;
        border-radius: 999px;
        color: var(--drink-primary);
        background: #e8f8f4;
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
        appearance: none;
        -webkit-appearance: none;
        flex: 0 0 auto;
        margin: auto 1rem auto 0;
        position: relative;
    }

    .voucher-ticket.active .voucher-radio,
    .voucher-radio.active {
        border-color: var(--drink-primary, #008b7a) !important;
        background-color: var(--drink-primary, #008b7a) !important;
        background: var(--drink-primary, #008b7a) !important;
        box-shadow: 0 0 0 4px rgba(13, 147, 115, 0.14);
    }

    .voucher-ticket.active .voucher-radio::after,
    .voucher-radio.active::after {
        content: "";
        position: absolute;
        inset: 6px;
        border-radius: 50%;
        background: #ffffff;
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
        max-height: calc(100vh - 2rem);
        display: flex;
        flex-direction: column;
        border: 0;
        border-radius: 4px;
        box-shadow: 0 22px 55px rgba(0, 0, 0, 0.22);
        overflow: hidden;
    }

    .address-modal .modal-header,
    .address-modal .modal-footer {
        padding: 1.4rem 1.8rem;
        flex: 0 0 auto;
    }

    .address-modal .modal-footer {
        position: relative;
        bottom: 0;
        z-index: 5;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-shrink: 0;
        min-height: 88px;
        background: #ffffff;
        box-shadow: 0 -10px 24px rgba(8, 42, 38, 0.08);
    }

    .address-modal .modal-body {
        flex: 1 1 auto;
        min-height: 0;
        padding: 1.2rem 1.8rem;
        overflow-y: auto;
        overscroll-behavior: contain;
    }

    .address-modal .modal-footer .btn-address-primary {
        margin-left: auto;
    }

    .address-form-modal .modal-footer {
        border-top: 1px solid #eef2f1 !important;
    }

    .address-form-modal .modal-dialog {
        height: calc(100vh - 2rem);
        margin-top: 1rem;
        margin-bottom: 1rem;
    }

    .address-form-modal .modal-content {
        height: 100%;
    }

    .address-form-modal .modal-footer .btn {
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        visibility: visible !important;
        opacity: 1 !important;
    }

    @media (max-width: 575.98px) {
        .address-modal .modal-footer {
            align-items: stretch;
            flex-direction: column-reverse;
        }

        .address-modal .modal-footer .btn,
        .address-modal .modal-footer .btn-address-primary {
            width: 100%;
            margin-left: 0;
        }
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
        border-color: var(--drink-primary, #008b7a);
        color: var(--drink-primary, #008b7a);
        background: #f4fffc;
    }

    .btn-address-primary {
        border-radius: 2px;
        background: var(--drink-primary, #008b7a) !important;
        border-color: var(--drink-primary, #008b7a) !important;
        color: #ffffff !important;
        min-width: 170px;
        min-height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        opacity: 1 !important;
        visibility: visible !important;
        box-shadow: 0 10px 22px rgba(0, 139, 122, 0.18);
    }

    .btn-address-primary:hover,
    .btn-address-primary:focus,
    .btn-address-primary:active {
        background: var(--drink-primary-dark, #006f62) !important;
        border-color: var(--drink-primary-dark, #006f62) !important;
        color: #ffffff !important;
        box-shadow: 0 12px 26px rgba(0, 107, 95, 0.24) !important;
    }

    .btn-address-link {
        color: var(--drink-primary, #008b7a);
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

    .delivery-fields.d-none,
    .pickup-fields.d-none {
        display: none !important;
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
                    <!-- Delivery Type Selector -->
                    <div class="checkout-panel p-4 p-md-5 mb-4">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <span class="checkout-step"><i class="bi bi-truck"></i></span>
                            <div>
                                <h2 class="h4 fw-bold mb-1">Phương thức nhận hàng</h2>
                                <p class="text-secondary mb-0">Chọn cách nhận đơn hàng của bạn.</p>
                            </div>
                        </div>

                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="fulfillment_type" id="deliveryTypeDelivery" value="delivery" @checked($fulfillmentType === 'delivery')>
                            <label class="btn btn-outline-primary flex-grow-1" for="deliveryTypeDelivery">
                                <i class="bi bi-truck me-2"></i>Giao đến địa chỉ
                            </label>

                            <input type="radio" class="btn-check" name="fulfillment_type" id="deliveryTypePickup" value="pickup" @checked($fulfillmentType === 'pickup')>
                            <label class="btn btn-outline-primary flex-grow-1" for="deliveryTypePickup">
                                <i class="bi bi-shop me-2"></i>Lấy tại chi nhánh
                            </label>
                        </div>

                        @error('fulfillment_type')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="checkout-panel checkout-address-panel mb-4 delivery-fields {{ $fulfillmentType === 'pickup' ? 'd-none' : '' }}" data-delivery-fields>
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

                            @if($errors->has('shipping_address_ui'))
                                <div class="text-danger small mt-3">
                                    {{ $errors->first('shipping_address_ui') }}
                                </div>
                            @endif

                            @if(empty($user->phone))
                                <div class="alert alert-warning border-0 rounded-4 mt-4 mb-0">
                                    Bạn chưa có số điện thoại. Có thể cập nhật trong mục địa chỉ để đơn hàng rõ ràng hơn.
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Branch Selector (Always Required) -->
                    <div class="checkout-panel p-4 p-md-5 mb-4">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <span class="checkout-step"><i class="bi bi-shop"></i></span>
                            <div>
                                <h2 class="h4 fw-bold mb-1">Chọn chi nhánh</h2>
                                <p class="text-secondary mb-0">Chọn chi nhánh xử lý đơn hàng của bạn.</p>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="branch_id" class="form-label fw-semibold">Chi nhánh <span class="text-danger">*</span></label>
                            <div class="branch-select-shell">
                                <select id="branch_id" name="branch_id" class="form-select checkout-input @error('branch_id') is-invalid @enderror"
                                    data-branches='{{ json_encode($branchesJson ?? []) }}'
                                    data-user-latitude="{{ $userLatitude }}"
                                    data-user-longitude="{{ $userLongitude }}">
                                    <option value="">Chọn chi nhánh</option>
                                    @foreach($branches as $branch)
                                        @php
                                            $branchDistance = null;
                                            if ($hasUserLocation && !empty($branch->latitude) && !empty($branch->longitude)) {
                                                // Calculate distance using Haversine formula
                                                $lat1 = deg2rad($userLatitude);
                                                $lon1 = deg2rad($userLongitude);
                                                $lat2 = deg2rad($branch->latitude);
                                                $lon2 = deg2rad($branch->longitude);

                                                $dlat = $lat2 - $lat1;
                                                $dlon = $lon2 - $lon1;

                                                $a = sin($dlat/2) * sin($dlat/2) + cos($lat1) * cos($lat2) * sin($dlon/2) * sin($dlon/2);
                                                $c = 2 * asin(sqrt($a));
                                                $radius = 6371; // Earth radius in kilometers
                                                $branchDistance = $c * $radius;
                                            }
                                        @endphp
                                        <option value="{{ $branch->id }}"
                                            @selected((string) old('branch_id') === (string) $branch->id)
                                            data-latitude="{{ $branch->latitude ?? '' }}"
                                            data-longitude="{{ $branch->longitude ?? '' }}"
                                            data-distance="{{ $branchDistance ?? '' }}">
                                            {{ $branch->name }}
                                            @if($branch->address) — {{ $branch->address }}@endif
                                            @if(!empty($branchDistance))
                                                — {{ number_format($branchDistance, 1) }} km
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                <i class="bi bi-chevron-down branch-select-chevron" aria-hidden="true"></i>
                            </div>
                            <div class="branch-select-note d-none" data-branch-select-note>
                                Cập nhật địa chỉ nhận hàng trước để chọn chi nhánh phù hợp.
                            </div>
                            @error('branch_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="checkout-panel p-4 p-md-5 mb-4 d-none" aria-hidden="true">
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
                        <input type="hidden" name="shipping_voucher_code" id="selectedShippingVoucherCode" value="{{ $selectedShippingVoucherCode }}">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <span class="voucher-icon"><i class="bi bi-ticket-perforated"></i></span>
                                <div>
                                    <h2 class="h5 fw-bold mb-1">Chill Drink Voucher</h2>
                                    <div class="voucher-selected-text" id="selectedVoucherText">
                                        {{ $selectedVoucherLabels ? 'Đã chọn: ' . $selectedVoucherLabels : 'Chưa chọn voucher' }}
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

                        @php
                            $deliveryType = old('delivery_type', 'now');
                        @endphp
                        <div class="row g-3 mb-3">
                            <div class="col-md-6"><label class="delivery-choice"><input class="form-check-input me-2" type="radio" name="delivery_type" value="now" @checked($deliveryType === 'now')><strong>Giao ngay</strong><span class="d-block text-secondary small ms-4">Xử lý ngay sau khi đặt hàng.</span></label></div>
                            <div class="col-md-6"><label class="delivery-choice"><input class="form-check-input me-2" type="radio" name="delivery_type" value="scheduled" @checked($deliveryType === 'scheduled')><strong>Đặt giao sau</strong><span class="d-block text-secondary small ms-4">Chọn ngày giờ muốn nhận.</span></label></div>
                        </div>
                        <div class="scheduled-delivery-fields {{ $deliveryType === 'scheduled' ? 'is-visible' : '' }} mb-3" data-scheduled-delivery-fields>
                            <label for="scheduled_delivery_time" class="form-label fw-semibold">Ngày và giờ nhận hàng</label>
                            <input type="datetime-local" id="scheduled_delivery_time" name="scheduled_delivery_time" min="{{ now()->addMinutes(30)->format('Y-m-d\TH:i') }}" max="{{ now()->addDays(7)->format('Y-m-d\TH:i') }}" value="{{ old('scheduled_delivery_time') }}" class="form-control checkout-input @error('scheduled_delivery_time') is-invalid @enderror">
                            @error('scheduled_delivery_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Chuẩn bị tối thiểu 30 phút · Nhận trong giờ mở cửa 07:00–22:00 · Tối đa 7 ngày.</div>
                            <label for="delivery_note" class="form-label fw-semibold mt-3">Ghi chú giao hàng</label>
                            <input id="delivery_note" name="delivery_note" maxlength="1000" value="{{ old('delivery_note') }}" class="form-control checkout-input" placeholder="Ví dụ: Giao đúng 10:30 giúp mình">
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
                                <p class="text-secondary mb-0"><span data-checkout-item-count>{{ count($cart) }}</span> món trong giỏ</p>
                                <a href="{{ route('products.index') }}" class="small fw-semibold text-primary text-decoration-none"><i class="bi bi-plus-circle me-1"></i>Thêm món khác</a>
                            </div>
                            <span class="payment-icon"><i class="bi bi-receipt"></i></span>
                        </div>

                        <div class="vstack gap-3 mb-4">
                            @foreach(collect($cart)->take(3) as $cartKey => $item)
                                @include('client.checkout._summary-item', ['extra' => false])
                            @endforeach

                            @if(count($cart) > 3)
                                @foreach(collect($cart)->skip(3) as $cartKey => $item)
                                    @include('client.checkout._summary-item', ['extra' => true])
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
                                <span data-checkout-subtotal>{{ number_format($total, 0, ',', '.') }}đ</span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-secondary">Phí vận chuyển</span>
                                <span class="text-primary fw-semibold" id="summaryShippingFee">{{ number_format($shippingFee, 0, ',', '.') }}đ</span>
                            </div>
                            <div class="d-flex justify-content-between mb-3 small">
                                <span class="text-secondary">Khoảng cách</span>
                                <span id="summaryShippingDistance">Phí giao hàng cố định</span>
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

<div class="modal fade address-modal address-form-modal" id="addressEditModal" tabindex="-1" aria-labelledby="addressEditTitle" aria-hidden="true">
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
                        @include('admin.partials.location-picker', [
                            'pickerId' => 'checkout-edit-location-picker',
                            'label' => 'Vị trí đã xác nhận',
                            'hint' => 'Chọn pin trực tiếp trên bản đồ để lưu vị trí nhận hàng.',
                            'latValue' => $userLatitude ?? null,
                            'lngValue' => $userLongitude ?? null,
                            'defaultLat' => 16.047079,
                            'defaultLng' => 108.206230,
                            'defaultZoom' => 5,
                        ])
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
                <button type="button" class="btn btn-address-primary" id="saveEditedAddress">Lưu địa chỉ</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade address-modal address-form-modal" id="addressAddModal" tabindex="-1" aria-labelledby="addressAddTitle" aria-hidden="true">
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
                        @include('admin.partials.location-picker', [
                            'pickerId' => 'checkout-new-location-picker',
                            'label' => 'Vị trí mới',
                            'hint' => 'Chọn pin trực tiếp trên bản đồ để lưu vị trí nhận hàng.',
                            'latValue' => old('latitude'),
                            'lngValue' => old('longitude'),
                            'defaultLat' => 16.047079,
                            'defaultLng' => 108.206230,
                            'defaultZoom' => 5,
                        ])
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
                <button type="button" class="btn btn-address-primary" id="saveNewAddress">Lưu địa chỉ</button>
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

                <!-- Received Vouchers Section -->
                <div class="mb-3" id="receivedVouchersSection" style="display: none;">
                    <div class="voucher-group-title">Voucher đã nhận</div>
                    <div class="text-secondary small mb-2">Những voucher bạn đã nhận và có thể sử dụng</div>
                    <div class="vstack gap-3" id="receivedVouchersList"></div>
                    <hr class="my-3">
                </div>

                <div class="mb-3">
                    <div class="voucher-group-title">Mã có thể áp dụng</div>
                    <div class="text-secondary">Có thể chọn 1 voucher freeship và 1 voucher giảm giá</div>
                </div>

                <div class="vstack gap-4">
                    @if($availableVouchers->isNotEmpty())
                        @foreach($voucherDisplayGroups as $groupTitle => $groupVouchers)
                            @continue($groupVouchers->isEmpty())
                            <section>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="voucher-kind">{{ $groupTitle }}</span>
                                    <span class="small text-secondary">{{ $groupVouchers->count() }} mã</span>
                                </div>
                                <div class="vstack gap-3">
                                    @foreach($groupVouchers as $voucher)
                                        @php
                                            $voucherIsShipping = $isShippingVoucher($voucher);
                                            $voucherDiscount = $voucher->discountFor((int) $total);
                                            $usageLimit = (int) ($voucher->usage_limit ?? 0);
                                            $usagePercent = $usageLimit > 0 ? min(100, (int) round(($voucher->used_count / max(1, $usageLimit)) * 100)) : 22;
                                            $voucherValueText = $voucherIsShipping
                                                ? 'Freeship tối đa ' . $voucher->formattedValue()
                                                : 'Giảm ' . $voucher->formattedValue();
                                            $voucherLabel = $voucher->code . ' - ' . $voucherValueText;
                                            $voucherIcon = $voucherIsShipping
                                                ? 'bi-truck'
                                                : ($voucher->is_redeemable ? 'bi-gift' : ($voucher->type === 'percent' ? 'bi-percent' : 'bi-ticket-perforated'));
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
                                            class="voucher-ticket {{ $voucherIsShipping ? 'is-shipping' : 'is-discount' }} {{ (($voucherIsShipping ? $selectedShippingVoucherCode : $selectedVoucherCode) === $voucher->code) && $voucherUsable ? 'active' : '' }} {{ $voucherUsable ? '' : 'is-disabled' }}"
                                            @if($voucherUsable) data-voucher-card @endif
                                            data-voucher-code="{{ $voucher->code }}"
                                            data-voucher-label="{{ $voucherLabel }}"
                                            data-voucher-discount="{{ $voucherDiscount }}"
                                            data-voucher-type="{{ $voucherIsShipping ? 'shipping' : 'discount' }}"
                                            data-voucher-disabled="{{ $voucherUsable ? '0' : '1' }}"
                                        >
                                            <div class="voucher-ticket-brand">
                                                <span class="brand-circle"><i class="bi {{ $voucherIcon }}"></i></span>
                                                <strong>{{ $voucher->code }}</strong>
                                            </div>
                                            <div class="voucher-ticket-body">
                                                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                                    <span class="voucher-limit">{{ $voucher->usage_limit > 0 ? 'Số lượng có hạn' : 'Không giới hạn' }}</span>
                                                    <span class="voucher-kind">{{ $voucherIsShipping ? 'Freeship' : 'Giảm giá' }}</span>
                                                    <span class="fw-semibold text-secondary">{{ $voucherValueText }}</span>
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
                                                    <span class="voucher-only mb-2">
                                                        {{ $voucherIsShipping ? 'Giảm phí vận chuyển' : 'Giảm đơn hàng' }}
                                                        {{ number_format($voucherDiscount, 0, ',', '.') }}đ
                                                    </span>
                                                @else
                                                    <span class="voucher-only mb-2">{{ $disabledReason }}</span>
                                                @endif
                                                <div class="voucher-progress mt-2 mb-1"><span style="width: {{ $usagePercent }}%"></span></div>
                                                <div class="small text-secondary">
                                                    HSD: {{ optional($voucher->expires_at)->format('d/m/Y H:i') ?: 'Không giới hạn' }}
                                                </div>
                                            </div>
                                            <button
                                                type="button"
                                                class="voucher-radio {{ (($voucherIsShipping ? $selectedShippingVoucherCode : $selectedVoucherCode) === $voucher->code) && $voucherUsable ? 'active' : '' }}"
                                                aria-label="Chọn voucher {{ $voucher->code }}"
                                                aria-pressed="{{ (($voucherIsShipping ? $selectedShippingVoucherCode : $selectedVoucherCode) === $voucher->code) && $voucherUsable ? 'true' : 'false' }}"
                                                @disabled(! $voucherUsable)
                                            ></button>
                                        </div>
                                    @endforeach
                                </div>
                            </section>
                        @endforeach
                    @else
                        <div class="voucher-warning">
                            <i class="bi bi-info-circle me-1"></i> Hiện chưa có voucher đang hoạt động.
                        </div>
                    @endif
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Trở lại</button>
                <div class="voucher-footer-actions">
                    <button type="button" class="btn btn-outline-danger px-4 voucher-clear-btn" id="clearVoucherSelection">
                        <i class="bi bi-x-circle me-2"></i>Bỏ chọn tất cả
                    </button>
                    <button type="button" class="btn btn-primary px-4" id="confirmVoucher">
                        <i class="bi bi-check2 me-2"></i>Áp dụng voucher
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@include('admin.partials.location-picker-script')

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
        const selectedShippingVoucherCode = document.getElementById('selectedShippingVoucherCode');
        const selectedVoucherText = document.getElementById('selectedVoucherText');
        const summaryVoucherText = document.getElementById('summaryVoucherText');
        const voucherCodeInput = document.getElementById('voucherCodeInput');
        const shippingConfig = {
            subtotal: {{ (int) $total }},
            discount: {{ (int) $discount }},
            fixedShippingFee: {{ (int) $shippingFee }},
        };
        const shippingTiers = @json($shippingDistanceOptions);
        const shippingDistanceLabel = document.getElementById('shippingDistanceLabel');
        const shippingEstimateDetail = document.getElementById('shippingEstimateDetail');
        const shippingInlineFee = document.getElementById('shippingInlineFee');
        const shippingEta = document.getElementById('shippingEta');
        const summaryShippingFee = document.getElementById('summaryShippingFee');
        const summaryShippingDistance = document.getElementById('summaryShippingDistance');
        const summaryGrandTotal = document.getElementById('summaryGrandTotal');
        const branchSelectShell = document.querySelector('.branch-select-shell');
        const branchSelectNote = document.querySelector('[data-branch-select-note]');

        let selectedAddressId = @json($selectedAddressId ?? 'primary');
        let pendingVouchers = {
            shipping: {
                code: document.querySelector('[data-voucher-card][data-voucher-type="shipping"].active')?.dataset.voucherCode || '',
                label: document.querySelector('[data-voucher-card][data-voucher-type="shipping"].active')?.dataset.voucherLabel || '',
                discount: Number(document.querySelector('[data-voucher-card][data-voucher-type="shipping"].active')?.dataset.voucherDiscount || 0),
            },
            discount: {
                code: document.querySelector('[data-voucher-card][data-voucher-type="discount"].active')?.dataset.voucherCode || '',
                label: document.querySelector('[data-voucher-card][data-voucher-type="discount"].active')?.dataset.voucherLabel || '',
                discount: Number(document.querySelector('[data-voucher-card][data-voucher-type="discount"].active')?.dataset.voucherDiscount || {{ (int) $discount }}),
            }
        };
        let addressBook = @json($addressBook ?? []);
        const addressSaveUrls = {
            primary: @json(route('checkout.addresses.primary.update')),
            store: @json(route('checkout.addresses.store')),
            updateBase: @json(url('/checkout/addresses')),
        };
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        let confirmedLocation = {
            latitude: Number.parseFloat(@json($userLatitude ?? null) || '') || null,
            longitude: Number.parseFloat(@json($userLongitude ?? null) || '') || null,
        };

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

        function hasConfirmedLocation() {
            return Number.isFinite(confirmedLocation.latitude) && Number.isFinite(confirmedLocation.longitude);
        }

        window.updateBranchSelectorState = function updateBranchSelectorState() {
            const branchSelect = document.getElementById('branch_id');
            const isPickup = document.getElementById('deliveryTypePickup')?.checked === true;

            if (!branchSelect) {
                return;
            }

            branchSelect.disabled = false;
            branchSelect.required = true;

            if (branchSelectShell) {
                branchSelectShell.classList.remove('is-disabled');
            }

            if (branchSelectNote) {
                branchSelectNote.classList.add('d-none');
            }
        }

        function getPickerCoordinates(scope) {
            const pickerId = scope === 'edit'
                ? 'checkout-edit-location-picker'
                : 'checkout-new-location-picker';
            const picker = document.querySelector(`[data-location-picker="${pickerId}"]`);
            const latitude = Number.parseFloat(picker?.querySelector('[data-location-lat]')?.value || '');
            const longitude = Number.parseFloat(picker?.querySelector('[data-location-lng]')?.value || '');

            if (Number.isFinite(latitude) && Number.isFinite(longitude)) {
                return {
                    latitude,
                    longitude,
                };
            }

            return {
                latitude: null,
                longitude: null,
            };
        }

        function showAddressToast(message, type = 'success') {
            const toast = document.createElement('div');
            const palette = type === 'success'
                ? { bg: '#10b981', border: '#059669' }
                : { bg: '#ef4444', border: '#dc2626' };

            toast.textContent = message;
            toast.style.cssText = `
                position: fixed;
                right: 20px;
                bottom: 20px;
                z-index: 1080;
                max-width: min(92vw, 360px);
                padding: 12px 16px;
                border-radius: 12px;
                color: #fff;
                background: ${palette.bg};
                border: 1px solid ${palette.border};
                box-shadow: 0 14px 30px rgba(0, 0, 0, 0.16);
                font-weight: 600;
            `;

            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 2600);
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

            const shippingFee = Number(shippingConfig.fixedShippingFee || 0);
            const grandTotal = shippingConfig.subtotal + shippingFee - Number(shippingConfig.discount || 0);

            if (shippingDistanceLabel) {
                shippingDistanceLabel.textContent = 'Cố định';
            }
            if (shippingEstimateDetail) {
                shippingEstimateDetail.textContent = 'Tạm thời chưa tính theo kilomet';
            }
            if (shippingInlineFee) {
                shippingInlineFee.textContent = formatVnd(shippingFee);
            }
            if (shippingEta) {
                shippingEta.textContent = methodInput.dataset.methodEta || '';
            }
            summaryShippingFee.textContent = formatVnd(shippingFee);
            summaryShippingDistance.textContent = 'Phí giao hàng cố định';
            summaryGrandTotal.textContent = formatVnd(grandTotal);
        }

        function renderBranchOptions(userLat = null, userLon = null) {
            const branchSelect = document.getElementById('branch_id');

            if (!branchSelect) {
                return;
            }

            const lat = Number.parseFloat(userLat);
            const lon = Number.parseFloat(userLon);

            if (!Number.isFinite(lat) || !Number.isFinite(lon)) {
                branchSelect.dataset.userLatitude = '';
                branchSelect.dataset.userLongitude = '';
                branchSelect.innerHTML = '<option value="">Chọn chi nhánh</option>';
                branchSelect.value = '';
                return;
            }

            branchSelect.dataset.userLatitude = String(lat);
            branchSelect.dataset.userLongitude = String(lon);

            const currentValue = branchSelect.disabled ? '' : (branchSelect.value || @json(old('branch_id', '')));
            const branchesData = JSON.parse(branchSelect.dataset.branches || '[]');
            const branchesWithDistance = branchesData.map((branch) => {
                const branchLat = Number.parseFloat(branch.latitude);
                const branchLon = Number.parseFloat(branch.longitude);

                if (Number.isFinite(branchLat) && Number.isFinite(branchLon)) {
                    return {
                        ...branch,
                        distance: calculateDistance(lat, lon, branchLat, branchLon),
                    };
                }

                return {
                    ...branch,
                    distance: null,
                };
            });

            branchesWithDistance.sort((a, b) => {
                if (a.distance === null && b.distance === null) return 0;
                if (a.distance === null) return 1;
                if (b.distance === null) return -1;
                return a.distance - b.distance;
            });

            branchSelect.innerHTML = '<option value="">Chọn chi nhánh</option>';

            branchesWithDistance.forEach((branch) => {
                const option = document.createElement('option');
                option.value = branch.id;
                option.dataset.latitude = branch.latitude || '';
                option.dataset.longitude = branch.longitude || '';
                option.dataset.distance = branch.distance !== null ? branch.distance.toFixed(2) : '';

                let label = branch.name;
                if (branch.address) {
                    label += ' — ' + branch.address;
                }
                if (branch.distance !== null) {
                    label += ' — ' + branch.distance.toFixed(1) + ' km';
                }

                option.textContent = label;
                branchSelect.appendChild(option);
            });

            if (currentValue) {
                branchSelect.value = currentValue;
            }
        }

        function syncAddressBook(payload) {
            if (Array.isArray(payload?.address_book)) {
                addressBook = payload.address_book;
            }

            if (payload?.address?.id) {
                const normalizedId = payload.address.id;
                const index = addressBook.findIndex((item) => item.id === normalizedId);

                if (index >= 0) {
                    addressBook[index] = { ...addressBook[index], ...payload.address };
                } else {
                    addressBook.push(payload.address);
                }
            }

            if (payload?.selected_address_id) {
                selectedAddressId = payload.selected_address_id;
            }

            renderAddressList();
            const activeAddress = getAddressById(selectedAddressId);
            if (activeAddress) {
                applyAddress(activeAddress);
            }

            const payloadLatitude = Number.parseFloat(payload?.address?.latitude);
            const payloadLongitude = Number.parseFloat(payload?.address?.longitude);
            if (Number.isFinite(payloadLatitude) && Number.isFinite(payloadLongitude)) {
                confirmedLocation = {
                    latitude: payloadLatitude,
                    longitude: payloadLongitude,
                };
                renderBranchOptions(payloadLatitude, payloadLongitude);
            } else {
                confirmedLocation = {
                    latitude: null,
                    longitude: null,
                };
                renderBranchOptions();
            }

            updateBranchSelectorState();
        }

        function getAddressById(id) {
            return addressBook.find((item) => item.id === id) || addressBook[0] || null;
        }

        function applyAddress(address) {
            if (!address) {
                return;
            }

            selectedAddressId = address.id;
            selectedReceiver.textContent = address.name || 'Chưa cập nhật';
            selectedPhone.textContent = address.phone || 'Chưa cập nhật';
            selectedAddressText.textContent = compactAddress([address.street, address.area]) || 'Chưa có địa chỉ. Bấm Thay đổi để thêm địa chỉ nhận hàng.';
            selectedDefaultBadge.classList.toggle('d-none', !address.isDefault);
            shippingAddressInput.value = address.street || '';
            shippingAreaInput.value = address.area || '';
            const addressLatitude = Number.parseFloat(address.latitude);
            const addressLongitude = Number.parseFloat(address.longitude);
            if (Number.isFinite(addressLatitude) && Number.isFinite(addressLongitude)) {
                confirmedLocation = {
                    latitude: addressLatitude,
                    longitude: addressLongitude,
                };
                renderBranchOptions(addressLatitude, addressLongitude);
            } else {
                confirmedLocation = {
                    latitude: null,
                    longitude: null,
                };
                renderBranchOptions();
            }
            renderAddressList();
            updateShippingSummary();
            updateBranchSelectorState();
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
            const address = getAddressById(id);
            fillEditModal(address);
            selectedAddressId = id;
            const picker = document.querySelector('[data-location-picker="checkout-edit-location-picker"]');
            const addressLatitude = Number.parseFloat(address?.latitude);
            const addressLongitude = Number.parseFloat(address?.longitude);
            if (Number.isFinite(addressLatitude) && Number.isFinite(addressLongitude)) {
                confirmedLocation = {
                    latitude: addressLatitude,
                    longitude: addressLongitude,
                };
                renderBranchOptions(addressLatitude, addressLongitude);
                window.ChillDrinkLocationPicker?.set(picker, addressLatitude, addressLongitude, 'Đã tải vị trí đã lưu.');
            } else {
                confirmedLocation = {
                    latitude: null,
                    longitude: null,
                };
                renderBranchOptions();
                window.ChillDrinkLocationPicker?.clear(picker);
            }
            updateBranchSelectorState();
            addressListModal.hide();
            addressEditModal.show();
        }

        function openAddModal() {
            document.getElementById('newAddressName').value = @json($user->name);
            document.getElementById('newAddressPhone').value = @json($user->phone ?? '');
            document.getElementById('newAddressArea').value = '';
            document.getElementById('newAddressStreet').value = '';
            document.getElementById('newAddressDefault').checked = false;
            setTypeActive('new', 'Nhà Riêng');
            const picker = document.querySelector('[data-location-picker="checkout-new-location-picker"]');
            confirmedLocation = {
                latitude: null,
                longitude: null,
            };
            renderBranchOptions();
            updateBranchSelectorState();
            window.ChillDrinkLocationPicker?.clear(picker);
            addressListModal.hide();
            addressAddModal.show();
        }

        function setVoucherActive(card) {
            if (!card) {
                return;
            }

            const type = card.dataset.voucherType || 'discount';
            document.querySelectorAll(`[data-voucher-card][data-voucher-type="${type}"]`).forEach((item) => {
                item.classList.remove('active');
                item.querySelector('.voucher-radio')?.classList.remove('active');
                item.querySelector('.voucher-radio')?.setAttribute('aria-pressed', 'false');
            });
            card.classList.add('active');
            card.querySelector('.voucher-radio')?.classList.add('active');
            card.querySelector('.voucher-radio')?.setAttribute('aria-pressed', 'true');
            pendingVouchers[type] = {
                code: card.dataset.voucherCode || '',
                label: card.dataset.voucherLabel || '',
                discount: Number(card.dataset.voucherDiscount || 0)
            };
            voucherCodeInput.value = pendingVouchers[type].code;
        }

        function clearVoucherSelection() {
            document.querySelectorAll('[data-voucher-card]').forEach((item) => {
                item.classList.remove('active');
                item.querySelector('.voucher-radio')?.classList.remove('active');
                item.querySelector('.voucher-radio')?.setAttribute('aria-pressed', 'false');
            });

            pendingVouchers = {
                shipping: { code: '', label: '', discount: 0 },
                discount: { code: '', label: '', discount: 0 }
            };
            voucherCodeInput.value = '';
            commitVoucherSelection();
        }

        function commitVoucherSelection() {
            selectedVoucherCode.value = pendingVouchers.discount.code || '';
            selectedShippingVoucherCode.value = pendingVouchers.shipping.code || '';
            const labels = [pendingVouchers.shipping.label, pendingVouchers.discount.label].filter(Boolean);
            selectedVoucherText.textContent = labels.length ? `Đã chọn: ${labels.join(' + ')}` : 'Chưa chọn voucher';
            shippingConfig.discount = Number(pendingVouchers.discount.discount || 0) + Math.min(shippingConfig.fixedShippingFee, Number(pendingVouchers.shipping.discount || 0));
            summaryVoucherText.textContent = shippingConfig.discount > 0
                ? `-${formatVnd(shippingConfig.discount)}`
                : (labels.length ? 'Sẽ kiểm tra khi đặt hàng' : 'Chưa áp dụng');
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

            if (voucherCard && !event.target.closest('a')) {
                setVoucherActive(voucherCard);
            }

            const cartActionBtn = event.target.closest('[data-checkout-cart-action]');
            if (cartActionBtn) {
                event.preventDefault();
                
                const confirmMsg = cartActionBtn.dataset.confirm;
                if (confirmMsg && !confirm(confirmMsg)) {
                    return;
                }

                const url = cartActionBtn.dataset.checkoutCartAction;
                const method = cartActionBtn.dataset.method || 'POST';
                const quantity = cartActionBtn.dataset.quantity;

                const payload = {
                    _token: csrfToken,
                    _method: method,
                };
                if (quantity !== undefined) {
                    payload.quantity = quantity;
                }

                cartActionBtn.disabled = true;

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload)
                }).then(async (res) => {
                    if (res.ok || res.redirected) {
                        window.location.reload();
                    } else {
                        const data = await res.json().catch(() => ({}));
                        alert(data.message || 'Có lỗi xảy ra, vui lòng thử lại.');
                        cartActionBtn.disabled = false;
                    }
                }).catch(() => {
                    alert('Lỗi kết nối, vui lòng thử lại.');
                    cartActionBtn.disabled = false;
                });
            }
        });

        document.addEventListener('location-picker:change', function (event) {
            const picker = event.target;
            if (!picker || !picker.matches || !picker.matches('[data-location-picker]')) {
                return;
            }

            const latitude = Number.parseFloat(event.detail?.latitude);
            const longitude = Number.parseFloat(event.detail?.longitude);

            if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
                return;
            }

            confirmedLocation = {
                latitude,
                longitude,
            };

            renderBranchOptions(latitude, longitude);
            updateBranchSelectorState();
        });

        document.getElementById('saveEditedAddress')?.addEventListener('click', async function () {
            const address = getAddressById(selectedAddressId);
            const name = document.getElementById('editAddressName').value.trim();
            const phone = document.getElementById('editAddressPhone').value.trim();
            const area = document.getElementById('editAddressArea').value.trim();
            const street = document.getElementById('editAddressStreet').value.trim();
            const resolvedLocation = getPickerCoordinates('edit');

            const payload = {
                name,
                phone,
                area,
                street,
                label: getTypeValue('edit'),
                latitude: Number.isFinite(resolvedLocation?.latitude) ? resolvedLocation.latitude : null,
                longitude: Number.isFinite(resolvedLocation?.longitude) ? resolvedLocation.longitude : null,
                is_default: document.getElementById('editAddressDefault').checked ? 1 : 0,
            };

            try {
                const response = await fetch(
                    address.id === 'primary'
                        ? addressSaveUrls.primary
                        : `${addressSaveUrls.updateBase}/${encodeURIComponent(String(address.id).replace(/^address-/, ''))}`,
                    {
                        method: address.id === 'primary' ? 'PATCH' : 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify(payload),
                    }
                );

                const data = await response.json().catch(() => ({}));

                if (response.ok) {
                    try {
                        syncAddressBook(data);
                    } catch (syncError) {
                        console.error(syncError);
                    }
                } else {
                    console.error(
                        data?.message
                            || Object.values(data?.errors || {})?.flat()?.[0]
                            || 'Không thể lưu địa chỉ.'
                    );
                }
            } catch (error) {
                console.error(error);
            } finally {
                addressEditModal.hide();
            }
        });

        document.getElementById('saveNewAddress')?.addEventListener('click', async function () {
            const name = document.getElementById('newAddressName').value.trim();
            const phone = document.getElementById('newAddressPhone').value.trim();
            const area = document.getElementById('newAddressArea').value.trim();
            const street = document.getElementById('newAddressStreet').value.trim();
            const resolvedLocation = getPickerCoordinates('new');

            const payload = {
                name,
                phone,
                area,
                street,
                label: getTypeValue('new'),
                latitude: Number.isFinite(resolvedLocation?.latitude) ? resolvedLocation.latitude : null,
                longitude: Number.isFinite(resolvedLocation?.longitude) ? resolvedLocation.longitude : null,
                is_default: document.getElementById('newAddressDefault').checked ? 1 : 0,
            };

            try {
                const response = await fetch(addressSaveUrls.store, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify(payload),
                });

                const data = await response.json().catch(() => ({}));

                if (response.ok) {
                    try {
                        syncAddressBook(data);
                    } catch (syncError) {
                        console.error(syncError);
                    }
                } else {
                    console.error(
                        data?.message
                            || Object.values(data?.errors || {})?.flat()?.[0]
                            || 'Không thể lưu địa chỉ mới.'
                    );
                }
            } catch (error) {
                console.error(error);
            } finally {
                addressAddModal.hide();
            }
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

            const manualType = /SHIP|FREE/.test(code) ? 'shipping' : 'discount';
            document.querySelectorAll(`[data-voucher-card][data-voucher-type="${manualType}"]`).forEach((item) => {
                item.classList.remove('active');
                item.querySelector('.voucher-radio')?.classList.remove('active');
                item.querySelector('.voucher-radio')?.setAttribute('aria-pressed', 'false');
            });
            pendingVouchers[manualType] = {
                code,
                label: `${code} - Mã nhập thủ công`,
                discount: 0,
            };
            commitVoucherSelection();
        });

        document.getElementById('confirmVoucher')?.addEventListener('click', function () {
            commitVoucherSelection();
        });

        document.getElementById('clearVoucherSelection')?.addEventListener('click', function () {
            clearVoucherSelection();
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

        // Load received vouchers
        async function loadReceivedVouchers() {
            try {
                const guestIdentifier = sessionStorage.getItem('guest_identifier');
                const response = await fetch('/api/vouchers/received', {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        ...(guestIdentifier && { 'X-Guest-Identifier': guestIdentifier }),
                    },
                });

                const data = await response.json();
                const receivedVouchersSection = document.getElementById('receivedVouchersSection');
                const receivedVouchersList = document.getElementById('receivedVouchersList');

                if (data.vouchers && data.vouchers.length > 0) {
                    receivedVouchersSection.style.display = 'block';
                    receivedVouchersList.innerHTML = '';

                    data.vouchers.forEach(voucher => {
                        const voucherHtml = `
                            <div class="voucher-ticket ${/SHIP|FREE/.test(voucher.code) ? 'is-shipping' : 'is-discount'}" data-voucher-card data-voucher-type="${/SHIP|FREE/.test(voucher.code) ? 'shipping' : 'discount'}" data-voucher-code="${escapeHtml(voucher.code)}" data-voucher-label="${escapeHtml(voucher.description ? `${voucher.code} - ${voucher.description}` : voucher.code)}" data-voucher-discount="0">
                                <div class="voucher-ticket-brand">
                                    <span class="brand-circle"><i class="bi bi-gift"></i></span>
                                    <strong>${escapeHtml(voucher.code)}</strong>
                                </div>
                                <div class="voucher-ticket-body">
                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                        <span class="voucher-kind">Đã nhận</span>
                                        <span class="fw-semibold text-secondary">${escapeHtml(voucher.value)}</span>
                                    </div>
                                    <div class="text-secondary small">
                                        ${escapeHtml(voucher.description || 'Voucher')}
                                    </div>
                                    <span class="voucher-only mt-2 mb-2">
                                        Bạn đã nhận voucher này
                                    </span>
                                </div>
                                <button type="button" class="voucher-radio" aria-label="Chọn voucher ${escapeHtml(voucher.code)}"></button>
                            </div>
                        `;
                        receivedVouchersList.innerHTML += voucherHtml;
                    });

                    // Re-attach voucher click handlers
                    document.querySelectorAll('[data-voucher-card]').forEach((card) => {
                        card.addEventListener('click', function (event) {
                            if (event.target.closest('.voucher-radio')) {
                                setVoucherActive(this);
                            }
                        });
                    });
                } else {
                    receivedVouchersSection.style.display = 'none';
                }
            } catch (error) {
                console.error('Error loading received vouchers:', error);
            }
        }

        // Load received vouchers when modal is shown
        const voucherModalElement = document.getElementById('voucherModal');
        if (voucherModalElement) {
            voucherModalElement.addEventListener('show.bs.modal', loadReceivedVouchers);
        }

        renderAddressList();
        applyAddress(getAddressById(selectedAddressId));
        renderBranchOptions(confirmedLocation.latitude, confirmedLocation.longitude);
        updateShippingSummary();
    });

    // Delivery type toggle
    document.addEventListener('DOMContentLoaded', function () {
        const deliveryFields = document.querySelector('[data-delivery-fields]');
        const pickupFields = document.querySelector('[data-pickup-fields]');
        const deliveryTypeDelivery = document.getElementById('deliveryTypeDelivery');
        const deliveryTypePickup = document.getElementById('deliveryTypePickup');
        const branchIdSelect = document.getElementById('branch_id');
        const shippingAddressInput = document.getElementById('shipping_address_ui');

        function syncDeliveryMode() {
            const isPickup = deliveryTypePickup?.checked;
            
            if (deliveryFields) {
                deliveryFields.classList.toggle('d-none', isPickup);
            }
            if (pickupFields) {
                pickupFields.classList.toggle('d-none', !isPickup);
            }

            // Update required attributes
            if (shippingAddressInput) {
                shippingAddressInput.required = !isPickup;
            }
            if (branchIdSelect) {
                branchIdSelect.required = isPickup;
            }

            // Update shipping fee display
            const shippingInlineFee = document.getElementById('shippingInlineFee');
            if (shippingInlineFee) {
                if (isPickup) {
                    shippingInlineFee.textContent = '0đ';
                } else {
                    // Recalculate shipping fee
                    const methodFee = parseInt(document.querySelector('input[name="shipping_method_ui"]')?.dataset.methodFee || '0');
                    shippingInlineFee.textContent = (methodFee || 0).toLocaleString('vi-VN') + 'đ';
                }
            }

            if (typeof window.updateBranchSelectorState === 'function') {
                window.updateBranchSelectorState();
            }
        }

        deliveryTypeDelivery?.addEventListener('change', syncDeliveryMode);
        deliveryTypePickup?.addEventListener('change', syncDeliveryMode);

        syncDeliveryMode();
        if (typeof window.updateBranchSelectorState === 'function') {
            window.updateBranchSelectorState();
        }
        
        // Scheduled delivery toggle
        const scheduledFields = document.querySelector('[data-scheduled-delivery-fields]');
        document.querySelectorAll('input[name="delivery_type"]').forEach(input => {
            input.addEventListener('change', (e) => {
                if (scheduledFields) {
                    scheduledFields.classList.toggle('is-visible', e.target.value === 'scheduled');
                }
            });
        });
    });

    // Haversine formula to calculate distance between two coordinates
    function calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371; // Earth's radius in kilometers
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = 
            Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
            Math.sin(dLon / 2) * Math.sin(dLon / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c;
    }
    
    // Initialize branch labels based on user coordinates
    function initializeBranchSorting() {
        const branchSelect = document.getElementById('branch_id');

        if (!branchSelect) {
            return;
        }

        const userLat = Number.parseFloat(branchSelect.dataset.userLatitude || '');
        const userLon = Number.parseFloat(branchSelect.dataset.userLongitude || '');

        if (!Number.isFinite(userLat) || !Number.isFinite(userLon)) {
            return;
        }

        const branchesData = JSON.parse(branchSelect.dataset.branches || '[]');
        const currentValue = branchSelect.disabled ? '' : (branchSelect.value || @json(old('branch_id', '')));

        const branchesWithDistance = branchesData.map((branch) => {
            const branchLat = Number.parseFloat(branch.latitude);
            const branchLon = Number.parseFloat(branch.longitude);

            if (Number.isFinite(branchLat) && Number.isFinite(branchLon)) {
                return {
                    ...branch,
                    distance: calculateDistance(userLat, userLon, branchLat, branchLon),
                };
            }

            return {
                ...branch,
                distance: null,
            };
        });

        branchesWithDistance.sort((a, b) => {
            if (a.distance === null && b.distance === null) return 0;
            if (a.distance === null) return 1;
            if (b.distance === null) return -1;
            return a.distance - b.distance;
        });

        branchSelect.innerHTML = '<option value="">Chọn chi nhánh</option>';

        branchesWithDistance.forEach((branch) => {
            const option = document.createElement('option');
            option.value = branch.id;
            option.dataset.latitude = branch.latitude || '';
            option.dataset.longitude = branch.longitude || '';
            option.dataset.distance = branch.distance !== null ? branch.distance.toFixed(2) : '';

            let label = branch.name;
            if (branch.address) {
                label += ' — ' + branch.address;
            }
            if (branch.distance !== null) {
                label += ' — ' + branch.distance.toFixed(1) + ' km';
            }

            option.textContent = label;
            branchSelect.appendChild(option);
        });

        if (currentValue) {
            branchSelect.value = currentValue;
        }
    }
    
    // Run initialization when page loads
    document.addEventListener('DOMContentLoaded', function() {
        initializeBranchSorting();
    });
</script>
@endsection