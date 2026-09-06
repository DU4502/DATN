@extends('layouts.client')

@section('title', 'Thanh Toán')

@section('content')
@php
    $total = (int) ($subtotal ?? collect($cart)->sum(fn($item) => $item['price'] * $item['quantity']));
    $cartQuantity = max(1, (int) collect($cart)->sum(fn($item) => (int) ($item['quantity'] ?? 1)));
    $shippingDistanceOptions = $shippingDistanceOptions ?? \App\Support\ShippingFee::distanceOptions();
    $shippingMethods = $shippingMethods ?? \App\Support\ShippingFee::methods();
    $branches = $branches ?? \App\Models\Branch::where('status', true)->orderBy('name')->get();
    $user = auth()->user();
    $primaryAddressRaw = trim((string) ($user->address ?? ''));
    $primaryAddressHouseNumber = null;
    $primaryAddressStreet = $primaryAddressRaw;
    if ($primaryAddressRaw !== '' && preg_match('/^(?:so\s*)?(\d+[a-zA-Z]?(?:\/\d+[a-zA-Z]?)*)(?:\s+|-|,)+(.*)$/iu', $primaryAddressRaw, $primaryAddressMatch)) {
        $primaryAddressHouseNumber = trim((string) ($primaryAddressMatch[1] ?? '')) ?: null;
        $primaryAddressStreet = trim((string) ($primaryAddressMatch[2] ?? '')) ?: '';
    }
    $primaryAddress = trim(collect([$primaryAddressHouseNumber, $primaryAddressStreet])->filter()->implode(' '));
    $primaryArea = trim((string) ($user->area ?? ''));
    $primaryAddressText = trim(collect([$primaryAddress, $primaryArea])->filter()->implode(', '));
    
    // Get user coordinates
    $userLatitude = $userLatitude ?? null;
    $userLongitude = $userLongitude ?? null;
    $selectedCheckoutAddress = collect($addressBook ?? [])->firstWhere('id', $selectedAddressId ?? 'primary');
    $addressLat = $selectedCheckoutAddress['latitude'] ?? $userLatitude;
    $addressLng = $selectedCheckoutAddress['longitude'] ?? $userLongitude;
    $hasAddressCoords = !empty($addressLat) && !empty($addressLng);
    $selectedBranch = $branches->first();
    $fulfillmentType = old('fulfillment_type', 'delivery');
    $selectedShippingMethod = old('shipping_method_ui', 'standard');

    if ($fulfillmentType === 'pickup') {
        $shippingFee = 0;
        $shippingQuote = [
            'total_fee' => 0,
            'distance_km' => 0,
            'distance_label' => 'Nhận tại quán',
            'estimate_label' => 'Tự nhận tại chi nhánh',
            'estimate_detail' => 'Nhận trực tiếp tại cửa hàng',
        ];
    } elseif ($hasAddressCoords && $selectedBranch && !empty($selectedBranch->latitude) && !empty($selectedBranch->longitude)) {
        $dist = $selectedBranch->distanceTo((float) $addressLat, (float) $addressLng);
        $shippingQuote = array_merge([
            'estimate_label' => $dist <= 3.5 ? 'Khu vực gần' : 'Khu vực xa hơn',
            'estimate_detail' => 'cách chi nhánh ~' . number_format($dist, 1, ',', '.') . ' km',
            'distance_label' => number_format($dist, 1, ',', '.') . ' km',
        ], \App\Support\ShippingFee::calculate($dist, $selectedShippingMethod, $cartQuantity));
        $shippingFee = $shippingQuote['total_fee'];
    } else {
        $shippingQuote = \App\Support\ShippingFee::quoteForAddress(
            old('shipping_address_ui', $primaryAddress),
            old('shipping_area_ui', $primaryArea),
            $selectedShippingMethod,
            $cartQuantity
        );
        $shippingFee = $shippingQuote['total_fee'];
    }
    $availableVouchers = collect($availableVouchers ?? []);
    $receivedVouchers = collect($receivedVouchers ?? []);
    $ownedVoucherModels = $receivedVouchers->pluck('voucher')->filter();
    $selectableVouchers = $availableVouchers->concat($ownedVoucherModels)->unique('id')->values();
    $selectedCheckoutAddress = collect($addressBook ?? [])->firstWhere('id', $selectedAddressId ?? 'primary');
    $selectedCheckoutPhone = trim((string) ($selectedCheckoutAddress['phone'] ?? $user->phone ?? ''));
    $checkoutPhoneReady = $selectedCheckoutPhone !== '' && $selectedCheckoutPhone !== 'Chưa cập nhật';
    $isShippingVoucher = fn ($voucher) => \Illuminate\Support\Str::contains(\Illuminate\Support\Str::upper((string) $voucher->code), ['SHIP', 'FREE']) || (isset($voucher->discount_target) && $voucher->discount_target === 'shipping');
    $canUseCheckoutVoucher = function ($voucher) use ($total, $shippingFee, $fulfillmentType, $isShippingVoucher) {
        $hasMinimumOrder = (int) $total >= (int) $voucher->min_order;

        if ($isShippingVoucher($voucher)) {
            if ($fulfillmentType === 'pickup' || $shippingFee <= 0) {
                return false;
            }
        }

        return $voucher->discountFor((int) $total) > 0 && $hasMinimumOrder;
    };
    $shippingVouchers = $availableVouchers->filter($isShippingVoucher)->values();
    $discountVouchers = $availableVouchers->reject($isShippingVoucher)->values();
    $voucherDisplayGroups = collect([
        'Phiếu miễn phí vận chuyển' => $shippingVouchers,
        'Phiếu giảm giá' => $discountVouchers,
    ]);
    $selectedVoucherCode = strtoupper(trim((string) old('voucher_code', '')));
    $selectedShippingVoucherCode = strtoupper(trim((string) old('shipping_voucher_code', '')));
    $selectedVoucher = $selectableVouchers->first(fn ($voucher) => ! $isShippingVoucher($voucher) && $voucher->code === $selectedVoucherCode && $canUseCheckoutVoucher($voucher));
    $selectedShippingVoucher = $selectableVouchers->first(fn ($voucher) => $isShippingVoucher($voucher) && $voucher->code === $selectedShippingVoucherCode && $canUseCheckoutVoucher($voucher));
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
        width: 54px;
        height: 54px;
        border-radius: 13px;
        object-fit: cover;
        background: var(--drink-soft);
        flex: 0 0 auto;
    }
    .checkout-item-actions { display: flex; align-items: center; gap: .28rem; margin-top: .4rem !important; }
    .checkout-item-actions button { display: grid; place-items: center; width: 26px; height: 26px; padding: 0; border: 1px solid var(--drink-border); border-radius: 50%; color: var(--drink-primary); background: #fff; font-size: .72rem; }
    .checkout-item-actions input { width: 32px; height: 26px; border: 1px solid var(--drink-border); border-radius: 999px; background: #fff; text-align: center; font-size: .78rem; font-weight: 700; color: var(--drink-ink); padding: 0 .15rem; }
    .checkout-item-actions button:hover { background: var(--drink-soft); }
    .checkout-item-actions button:disabled { cursor: not-allowed; opacity: .4; }
    .checkout-item-actions button.is-remove { color: #dc3545; border-color: #f1c5cb; }
    .checkout-summary-item.is-unavailable { border-color: rgba(220, 53, 69, .28); background: #fff7f7; }
    .checkout-summary-item.is-unavailable .checkout-item-img { filter: grayscale(.55); opacity: .68; }
    .checkout-summary-chip.is-unavailable { color: #b42334; background: #ffe8eb; }
    .delivery-choice { display: block; height: 100%; padding: 1rem; border: 1.5px solid var(--drink-border); border-radius: 16px; cursor: pointer; }
    .delivery-choice:has(input:checked) { border-color: var(--drink-primary); background: var(--drink-soft); box-shadow: 0 0 0 3px rgba(13,147,115,.1); }
    .scheduled-delivery-fields { display: none; padding: 1rem; border-radius: 16px; background: #f7fbfa; border: 1px solid var(--drink-border); }
    .scheduled-delivery-fields.is-visible { display: block; }

    .summary-card {
        position: static;
        height: auto;
        max-height: none;
        display: flex;
        flex-direction: column;
        overflow: visible;
        border-color: rgba(0, 139, 122, 0.16);
        box-shadow: 0 24px 62px rgba(8, 42, 38, 0.09);
    }

    .summary-card-head {
        flex: 0 0 auto;
        margin-bottom: .85rem !important;
    }

    .checkout-add-more {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        width: fit-content;
        min-height: 34px;
        margin-top: .35rem;
        padding: .32rem .58rem;
        border: 1px solid rgba(13, 147, 115, .22);
        border-radius: 999px;
        color: var(--drink-primary-dark);
        background: var(--drink-soft);
        font-size: .72rem;
        font-weight: 800;
        line-height: 1.15;
        text-decoration: none;
        transition: color .18s ease, background .18s ease, border-color .18s ease, transform .18s ease;
    }

    .checkout-add-more:hover {
        border-color: var(--drink-primary);
        color: #fff;
        background: var(--drink-primary);
        transform: translateY(-1px);
    }

    .checkout-add-more__icon {
        display: grid;
        place-items: center;
        width: 21px;
        height: 21px;
        flex: 0 0 21px;
        border-radius: 50%;
        color: #fff;
        background: var(--drink-primary);
    }

    .checkout-add-more:hover .checkout-add-more__icon {
        color: var(--drink-primary);
        background: #fff;
    }

    .checkout-add-more__arrow {
        color: currentColor;
        opacity: .68;
    }

    .summary-card-items {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        overscroll-behavior: contain;
        scrollbar-gutter: stable;
        padding-right: 0.45rem;
        margin-right: 0;
    }

    .summary-card-items .vstack {
        gap: 0 !important;
        margin-bottom: .75rem !important;
    }

    .summary-card-footer {
        flex: 0 0 auto;
        border-top: 1px solid var(--drink-border);
        margin-top: 0.75rem;
        padding-top: 0.9rem;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.72), #ffffff 48%);
    }

    .summary-card .checkout-summary-item {
        display: grid !important;
        grid-template-columns: 64px minmax(0, 1fr);
        gap: 0.85rem !important;
        padding: 0.85rem;
        border: 1px solid #e8f1ef;
        border-radius: 18px;
        background: linear-gradient(145deg, #ffffff, #f8fcfb);
    }

    .summary-card .checkout-summary-item:first-child { padding-top: .25rem; }

    .checkout-summary-name {
        overflow: hidden;
        font-size: .82rem;
        line-height: 1.25;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .summary-card .checkout-summary-item:last-child {
        margin-bottom: 0;
    }

    .checkout-summary-content {
        min-width: 0;
    }

    .checkout-summary-title-row {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.75rem;
    }

    .checkout-summary-name {
        min-width: 0;
        overflow: hidden;
        color: var(--drink-ink);
        font-size: 0.96rem;
        font-weight: 800;
        line-height: 1.35;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .checkout-summary-line-total {
        flex: 0 0 auto;
        color: var(--drink-primary);
        font-size: 0.96rem;
        font-weight: 850;
        line-height: 1.35;
        white-space: nowrap;
    }

    .checkout-summary-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
        margin-top: 0.4rem;
    }

    .checkout-summary-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        min-height: 24px;
        padding: 0.2rem 0.5rem;
        border-radius: 999px;
        background: #edf7f4;
        color: #42605a;
        font-size: 0.72rem;
        font-weight: 700;
        line-height: 1;
    }

    .checkout-summary-chip.is-member {
        background: #fff4e8;
        color: #a95712;
    }

    .checkout-summary-toggle {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.45rem;
        width: 100%;
        min-height: 34px;
        margin-top: 0.15rem;
        border: 1px solid rgba(13, 147, 115, 0.24);
        border-radius: 12px;
        color: var(--drink-primary-dark);
        background: var(--drink-soft);
        font-size: 0.72rem;
        font-weight: 800;
        transition: background 0.18s ease, border-color 0.18s ease;
    }

    .checkout-summary-toggle:hover {
        border-color: var(--drink-primary);
        background: #dff5ef;
    }

    .checkout-summary-toggle i {
        transition: transform 0.18s ease;
    }

    .checkout-summary-toggle[aria-expanded="true"] i {
        transform: rotate(180deg);
    }

    .checkout-summary-meta {
        margin-top: 0.08rem;
        font-size: 0.72rem;
        color: var(--drink-muted);
        line-height: 1.35;
    }

    .checkout-summary-price-lines {
        margin-top: 0.5rem;
        padding-top: 0.5rem;
        border-top: 1px dashed #dbe9e6;
        font-size: 0.76rem;
        line-height: 1.45;
        color: var(--drink-muted);
    }

    .summary-costs {
        display: grid;
        gap: 0.65rem;
    }

    .summary-cost-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        color: var(--drink-ink);
        font-size: 0.92rem;
    }

    .summary-cost-row > :last-child {
        text-align: right;
    }

    .summary-total-row {
        margin-top: 0.15rem;
        padding: 0.8rem 0.9rem;
        border-radius: 16px;
        background: #edf9f5;
    }

    .summary-total-row strong {
        font-size: 1.3rem;
    }

    .summary-item-count {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.55rem;
        border-radius: 999px;
        background: #edf9f5;
        color: var(--drink-primary);
        font-size: 0.76rem;
        font-weight: 800;
    }

    .summary-add-more {
        display: inline-flex;
        align-items: center;
        margin-top: 0.35rem;
        padding: 0;
        border: 0;
        background: transparent;
    }

    @media (max-width: 575.98px) {
        .summary-card .checkout-summary-item {
            grid-template-columns: 54px minmax(0, 1fr);
            padding: 0.7rem;
        }

        .checkout-item-img {
            width: 54px;
            height: 54px;
            border-radius: 14px;
        }

        .checkout-summary-title-row {
            display: block;
        }

        .checkout-summary-line-total {
            margin-top: 0.15rem;
        }
    }

    @media (max-width: 991.98px) {
        .summary-card {
            position: static;
            height: auto;
            max-height: none;
            overflow: visible;
        }

        .summary-card-items {
            overflow: visible;
            padding-right: 0;
            margin-right: 0;
        }
    }

    @media (min-width: 992px) {
        .checkout-page .col-lg-5 {
            position: relative;
            align-self: stretch;
            min-height: 1px;
        }

        .summary-card {
            position: static;
            width: 100%;
        }
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
        max-height: min(760px, calc(100dvh - 2rem));
        border: 0;
        border-radius: 22px;
        overflow: hidden;
        box-shadow: 0 26px 70px rgba(8, 42, 38, 0.24);
    }

    .voucher-modal .modal-dialog {
        width: min(880px, calc(100vw - 2rem));
        max-width: 880px;
        margin: 1rem auto;
    }

    .voucher-modal .modal-header,
    .voucher-modal .modal-footer {
        padding: .9rem 1.1rem;
    }

    .voucher-modal .modal-body {
        padding: .85rem 1.1rem;
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
        padding: .7rem;
        border: 1px solid var(--drink-border);
        border-radius: 18px;
    }

    .voucher-search-box .form-control {
        min-height: 40px;
        border-radius: 12px;
        background: #ffffff;
        border-color: var(--drink-border);
        box-shadow: none;
    }

    .voucher-apply-btn {
        min-width: 104px;
        min-height: 40px;
        border-radius: 12px;
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
        min-height: 108px;
        padding-right: 3.25rem;
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
        left: 104px;
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
        width: 112px;
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
        width: 46px;
        height: 46px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.18);
        font-size: 1.2rem;
    }

    .voucher-ticket-body {
        flex: 1;
        padding: .75rem 1rem .75rem .9rem;
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
        position: absolute;
        top: 50%;
        right: 1rem;
        transform: translateY(-50%);
        width: 22px;
        height: 22px;
        border: 1.8px solid #c8d0ce;
        border-radius: 50%;
        background: #ffffff;
        appearance: none;
        -webkit-appearance: none;
        flex: 0 0 auto;
        margin: 0;
        z-index: 4;
        cursor: pointer;
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
        border-radius: 20px;
        box-shadow: 0 22px 55px rgba(0, 0, 0, 0.22);
        overflow: hidden;
    }

    .address-modal .modal-dialog {
        width: min(760px, calc(100vw - 2rem));
        max-width: 760px;
        margin: 1rem auto;
    }

    .address-modal .modal-header,
    .address-modal .modal-footer {
        padding: .9rem 1.1rem;
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
        min-height: 64px;
        background: #ffffff;
        box-shadow: 0 -10px 24px rgba(8, 42, 38, 0.08);
    }

    .address-modal .modal-body {
        flex: 1 1 auto;
        min-height: 0;
        padding: .85rem 1.1rem;
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
        height: auto;
    }

    .address-form-modal .modal-content {
        height: auto;
        max-height: min(780px, calc(100dvh - 2rem));
    }

    .address-form-modal .modal-footer .btn {
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        visibility: visible !important;
        opacity: 1 !important;
    }

    @media (max-width: 575.98px) {
        .voucher-modal .modal-dialog,
        .address-modal .modal-dialog {
            width: calc(100vw - 1rem);
            margin: .5rem auto;
        }

        .voucher-modal .modal-content,
        .address-modal .modal-content,
        .address-form-modal .modal-content {
            max-height: calc(100dvh - 1rem);
            border-radius: 16px;
        }

        .voucher-modal .modal-header,
        .voucher-modal .modal-body,
        .voucher-modal .modal-footer {
            padding: .7rem .8rem;
        }

        .voucher-modal .modal-header .text-secondary {
            display: none !important;
        }

        .voucher-modal .modal-footer {
            gap: .5rem;
        }

        .voucher-modal .modal-footer .btn {
            min-width: 0;
            min-height: 40px;
            padding: .5rem .75rem;
            font-size: .78rem;
        }

        .voucher-search-box {
            gap: .5rem !important;
            padding: .6rem;
        }

        .voucher-ticket {
            min-height: 98px;
            padding-right: 2.6rem;
        }

        .voucher-ticket-brand {
            width: 82px;
            font-size: .72rem;
        }

        .voucher-ticket::before,
        .voucher-ticket::after {
            left: 74px;
        }

        .voucher-ticket-brand .brand-circle {
            width: 38px;
            height: 38px;
            font-size: 1rem;
        }

        .voucher-ticket-body {
            padding: .6rem .65rem;
            font-size: .75rem;
        }

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

    @media (max-width: 767.98px) {
        .checkout-page {
            padding: .75rem 0 1.75rem !important;
        }

        .checkout-page .container {
            --bs-gutter-x: 1rem;
        }

        .checkout-page .row.g-4 {
            --bs-gutter-y: .75rem;
        }

        .checkout-panel,
        .checkout-voucher-panel,
        .checkout-hero {
            padding: .85rem !important;
            margin-bottom: .75rem !important;
            border-radius: 14px;
        }

        .checkout-panel > .d-flex.align-items-center.gap-3.mb-4,
        .checkout-panel > .address-panel-head,
        .checkout-voucher-panel .d-flex.gap-3 {
            gap: .6rem !important;
            margin-bottom: .65rem !important;
        }

        .checkout-step,
        .checkout-hero .checkout-step,
        .payment-icon,
        .voucher-icon,
        .shipping-auto-icon {
            width: 34px;
            height: 34px;
            flex-basis: 34px;
            border-radius: 10px;
            font-size: .85rem;
        }

        .checkout-panel h2,
        .checkout-voucher-panel h2 {
            margin-bottom: .1rem !important;
            font-size: .98rem !important;
            line-height: 1.25;
        }

        .checkout-panel h2 + p,
        .checkout-voucher-panel .voucher-selected-text {
            display: -webkit-box;
            overflow: hidden;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 1;
            font-size: .7rem;
            line-height: 1.35;
        }

        .checkout-panel > .btn-group .btn {
            min-height: 42px;
            padding: .4rem .25rem;
            font-size: .75rem;
            line-height: 1.2;
        }

        .checkout-address-panel .address-panel-head {
            padding: 0 !important;
        }

        .checkout-address-panel > .p-4 {
            padding: .65rem 0 0 !important;
        }

        .selected-address-row {
            gap: .5rem;
            padding: .65rem !important;
            border-radius: 11px;
            font-size: .76rem;
        }

        .selected-address-row .address-selected-mark {
            width: 26px;
            height: 26px;
            flex-basis: 26px;
        }

        .selected-address-row .address-line {
            font-size: .7rem;
            line-height: 1.35;
        }

        .checkout-input {
            min-height: 40px;
            padding: .45rem .7rem;
            border-radius: 11px;
            font-size: .84rem;
        }

        [data-find-nearest-branch] {
            padding: .35rem .55rem;
            font-size: .68rem;
        }

        .branch-select-note,
        .checkout-panel .form-text,
        .checkout-panel .invalid-feedback {
            margin-top: .2rem;
            font-size: .68rem;
            line-height: 1.35;
        }

        .checkout-voucher-panel > .d-flex {
            flex-wrap: nowrap !important;
            gap: .45rem !important;
        }

        .checkout-voucher-panel > .d-flex > .d-flex {
            min-width: 0;
            flex: 1 1 auto;
        }

        .voucher-select-link {
            flex: 0 0 auto;
            font-size: .75rem;
        }

        .checkout-panel .row.g-3 {
            --bs-gutter-x: .55rem;
            --bs-gutter-y: .55rem;
        }

        .checkout-panel .payment-option,
        .checkout-panel .delivery-choice {
            font-size: .75rem;
        }

        .checkout-panel .payment-option .payment-card {
            min-height: 82px;
            gap: .45rem !important;
            padding: .55rem !important;
            border-radius: 11px;
        }

        .checkout-panel .payment-option .text-secondary,
        .checkout-panel .delivery-choice .text-secondary {
            display: -webkit-box;
            overflow: hidden;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            font-size: .65rem;
            line-height: 1.3;
        }

        .checkout-panel .payment-option .col-md-6,
        .checkout-panel .row.g-3 > .col-md-6 {
            width: 50%;
        }

        .delivery-choice {
            min-height: 76px;
            padding: .6rem;
            border-radius: 11px;
        }

        .scheduled-delivery-fields {
            padding: .65rem;
            border-radius: 11px;
        }

        .checkout-panel textarea.checkout-input {
            min-height: 72px;
        }

        .summary-card {
            height: auto;
            max-height: none;
            padding: .85rem !important;
        }

        .summary-card-head {
            margin-bottom: .65rem !important;
        }

        .summary-card-head h2 {
            font-size: 1rem !important;
        }

        .summary-card-head p,
        .summary-card-head a:not(.checkout-add-more) {
            font-size: .7rem !important;
        }

        .summary-card-items .vstack {
            gap: .6rem !important;
            margin-bottom: .65rem !important;
        }

        .summary-card .checkout-summary-item {
            gap: .55rem !important;
            padding-bottom: .6rem;
        }

        .checkout-item-img {
            width: 48px;
            height: 48px;
            border-radius: 10px;
        }

        .checkout-summary-meta,
        .checkout-summary-price-lines {
            font-size: .68rem;
            line-height: 1.3;
        }

        .checkout-summary-unit-total,
        .checkout-summary-grand-total {
            font-size: .78rem;
        }

        .summary-card-footer {
            margin-top: .65rem;
            padding-top: .65rem;
        }

        .summary-card-footer > .border-top {
            padding-top: .65rem !important;
        }

        .summary-card-footer .d-flex.justify-content-between {
            margin-bottom: .45rem !important;
            font-size: .78rem;
        }

        .summary-card-footer .h4 {
            margin-block: .65rem !important;
            font-size: 1rem !important;
        }

        .summary-card-footer .mt-3 {
            margin-top: .6rem !important;
        }

        .address-form-modal .modal-dialog {
            height: auto;
            margin: .5rem;
        }

        .address-modal .modal-header,
        .address-modal .modal-body,
        .address-modal .modal-footer {
            padding: .8rem !important;
        }

        .address-modal-title {
            font-size: 1.05rem;
        }

        .address-type-btn {
            min-width: 0;
            flex: 1 1 0;
        }
    }

    @media (min-width: 768px) and (max-width: 991.98px) {
        .checkout-page > .container > form > .row > .col-lg-7 { width: 58.333333%; }
        .checkout-page > .container > form > .row > .col-lg-5 { width: 41.666667%; }
        .checkout-page > .container > form > .row { --bs-gutter-x: 1rem; }
        .checkout-panel,
        .checkout-voucher-panel { padding: 1.25rem !important; border-radius: 18px; }
        .summary-card { height: auto; max-height: none; }
    }

    @media (max-width: 575.98px) {
        .summary-card-head {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: center !important;
            gap: .6rem !important;
        }

        .summary-card-head .payment-icon {
            width: 36px;
            height: 36px;
            flex-basis: 36px;
        }

        .checkout-add-more {
            min-height: 36px;
            margin-top: .35rem;
            padding: .35rem .6rem;
            font-size: .72rem;
        }

        .checkout-add-more__icon {
            width: 22px;
            height: 22px;
            flex-basis: 22px;
        }

        .summary-card .checkout-summary-item {
            display: grid !important;
            grid-template-columns: 48px minmax(0, 1fr) auto;
            align-items: start !important;
            gap: .55rem !important;
        }

        .summary-card .checkout-summary-item > .flex-grow-1 {
            min-width: 0;
        }

        .summary-card .checkout-summary-item > .text-end {
            margin-left: 0 !important;
        }

        .checkout-summary-toggle {
            min-height: 38px;
            border-radius: 10px;
            font-size: .72rem;
        }
    }

    @media (max-width: 419.98px) {
        .summary-card .checkout-summary-item {
            grid-template-columns: 42px minmax(0, 1fr);
        }

        .summary-card .checkout-item-img {
            width: 42px;
            height: 42px;
        }

        .summary-card .checkout-summary-item > .text-end {
            grid-column: 2;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            text-align: left !important;
        }

        .checkout-summary-unit-total,
        .checkout-summary-grand-total {
            margin-top: 0;
            font-size: .72rem;
        }
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
                            <input
                                id="shipping_area_ui"
                                name="shipping_area_ui"
                                type="hidden"
                                value="{{ old('shipping_area_ui', $primaryArea) }}"
                            >
                            <input
                                id="checkout_latitude"
                                name="latitude"
                                type="hidden"
                                value="{{ old('latitude') }}"
                            >
                            <input
                                id="checkout_longitude"
                                name="longitude"
                                type="hidden"
                                value="{{ old('longitude') }}"
                            >
                            <input
                                id="address_location_confirmed"
                                name="address_location_confirmed"
                                type="hidden"
                                value="{{ old('address_location_confirmed') === '1' ? '1' : '' }}"
                            >
                            <input
                                id="shipping_phone_ui"
                                name="shipping_phone_ui"
                                type="hidden"
                                value="{{ old('shipping_phone_ui', $checkoutPhoneReady ? $selectedCheckoutPhone : '') }}"
                            >

                            <div class="selected-address-row">
                                <span class="address-selected-mark"><i class="bi bi-check-lg"></i></span>
                                <div class="flex-grow-1">
                                    <div class="address-person mb-1">
                                        <span id="selectedReceiver">{{ $user->name }}</span>
                                        <span class="address-phone-divider {{ $checkoutPhoneReady ? '' : 'd-none' }}" id="selectedPhoneDivider"></span>
                                        <span id="selectedPhone">{{ $checkoutPhoneReady ? $selectedCheckoutPhone : '' }}</span>
                                    </div>
                                    <div class="address-line" id="selectedAddressText">
                                        {{ $primaryAddressText ?: 'Chưa có địa chỉ. Bấm Thay đổi để thêm địa chỉ nhận hàng.' }}
                                    </div>
                                    <span class="address-badge" id="selectedDefaultBadge">Mặc định</span>
                                </div>
                                <button
                                    type="button"
                                    class="btn-address-link"
                                    data-bs-toggle="modal"
                                    data-bs-target="#addressEditModal"
                                    data-address-id="{{ $selectedAddressId }}"
                                >Cập nhật</button>
                            </div>
                            <div class="text-warning small mt-2 d-none" data-address-house-number-warning></div>

                            @if($errors->has('shipping_address_ui'))
                                <div class="text-danger small mt-3">
                                    {{ $errors->first('shipping_address_ui') }}
                                </div>
                            @endif
                        </div>
                    </div>

                    @if ($isGroupCheckout && $groupCheckoutBranch)
                        {{-- Đơn nhóm đã chốt chi nhánh ở bước tạo phòng. Hiển thị thông tin rõ ràng và giữ select ẩn để tính phí. --}}
                        <div class="checkout-panel p-3 p-md-4 mb-4 rounded-4" style="background: rgba(13, 147, 115, 0.06); border: 1px solid #0D9373;">
                            <div class="d-flex align-items-center gap-3">
                                <span class="checkout-step" style="background: #0D9373; color: white;"><i class="bi bi-people-fill"></i></span>
                                <div>
                                    <div class="small fw-bold text-primary text-uppercase mb-0">Đơn nhóm: {{ $groupCheckoutGroup->name ?? 'Chill Drink Together' }}</div>
                                    <h2 class="h5 fw-bold mb-1 text-dark">Chuẩn bị bởi: {{ $groupCheckoutBranch->name }}</h2>
                                    <p class="text-secondary small mb-0"><i class="bi bi-geo-alt me-1"></i>{{ $groupCheckoutBranch->address ?: 'Địa chỉ chi nhánh phục vụ' }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="d-none" aria-hidden="true">
                            <select id="branch_id" name="branch_id" class="checkout-input" required
                                data-branches='[]'
                                data-user-latitude="{{ $userLatitude }}"
                                data-user-longitude="{{ $userLongitude }}">
                                <option value="{{ $groupCheckoutBranch->id }}" selected
                                    data-latitude="{{ $groupCheckoutBranch->latitude ?? '' }}"
                                    data-longitude="{{ $groupCheckoutBranch->longitude ?? '' }}">
                                    {{ $groupCheckoutBranch->name }}
                                    @if($groupCheckoutBranch->address) — {{ $groupCheckoutBranch->address }}@endif
                                </option>
                            </select>
                        </div>
                    @else
                    <!-- Branch Selector (Always Required) -->
                    <div class="checkout-panel p-4 p-md-5 mb-4">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <span class="checkout-step"><i class="bi bi-shop"></i></span>
                            <div>
                                <h2 class="h4 fw-bold mb-1">Chi nhánh phục vụ gần bạn</h2>
                                <p class="text-secondary mb-0">Chọn chi nhánh xử lý đơn hàng của bạn hoặc để hệ thống gợi ý chi nhánh gần nhất.</p>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                <label for="branch_id" class="form-label fw-semibold mb-0">Chi nhánh <span class="text-danger">*</span></label>
                                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill" data-find-nearest-branch>
                                    <i class="bi bi-crosshair me-1"></i>Tìm chi nhánh gần nhất
                                </button>
                            </div>
                            <div class="branch-select-shell">
                                <select id="branch_id" name="branch_id" class="form-select checkout-input @error('branch_id') is-invalid @enderror"
                                    data-branches='{{ json_encode($branchesJson ?? []) }}'
                                    data-user-latitude="{{ $userLatitude }}"
                                    data-user-longitude="{{ $userLongitude }}">
                                    <option value="">Chọn chi nhánh</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}"
                                            @selected((string) old('branch_id', session('group_branch_id') ?? session('nearest_branch_id')) === (string) $branch->id)
                                            data-latitude="{{ $branch->latitude ?? '' }}"
                                            data-longitude="{{ $branch->longitude ?? '' }}"
                                            data-distance="">
                                            {{ $branch->name }}
                                            @if($branch->address) — {{ $branch->address }}@endif
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
                    @endif

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
                                    <h2 class="h5 fw-bold mb-1">Phiếu ưu đãi Chill Drink</h2>
                                    <div class="voucher-selected-text" id="selectedVoucherText">
                                        {{ $selectedVoucherLabels ? 'Đã chọn: ' . $selectedVoucherLabels : 'Chưa chọn phiếu ưu đãi' }}
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

                        @php
                            $deliveryType = old('delivery_type', $checkoutDeliveryType ?? 'now');
                            $scheduledLeadMinutes = \App\Support\ScheduledDelivery::minimumBookingLeadMinutes($fulfillmentType);
                            $selectedPaymentMethod = old('payment_method', $deliveryType === 'scheduled' ? 'vnpay' : 'cod');
                            if ($deliveryType === 'scheduled' && $selectedPaymentMethod === 'cod') {
                                $selectedPaymentMethod = 'vnpay';
                            }
                        @endphp

                        <div class="row g-3">
                            @foreach($paymentOptions as $value => $option)
                                <div class="col-md-6">
                                    <label class="payment-option d-block h-100">
                                        <input
                                            type="radio"
                                            name="payment_method"
                                            value="{{ $value }}"
                                            {{ $selectedPaymentMethod === $value ? 'checked' : '' }}
                                            data-payment-method="{{ $value }}"
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
                        <div class="alert alert-info small mt-3 mb-0 {{ $deliveryType === 'scheduled' ? '' : 'd-none' }}" data-scheduled-payment-notice>
                            <i class="bi bi-info-circle me-1"></i>
                            Đơn đặt giao sau cần thanh toán trước, nên hệ thống sẽ dùng VNPay thay cho thanh toán khi nhận hàng.
                        </div>
                    </div>

                    <div class="checkout-panel p-4 p-md-5">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <span class="checkout-step"><i class="bi bi-chat-left-text"></i></span>
                            <div>
                                <h2 class="h4 fw-bold mb-1">Ghi chú đơn hàng</h2>
                                <p class="text-secondary mb-0">Thêm yêu cầu về đường, đá hoặc mốc nhận hàng nếu cần.</p>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6"><label class="delivery-choice"><input class="form-check-input me-2" type="radio" name="delivery_type" value="now" @checked($deliveryType === 'now')><strong>Giao ngay</strong><span class="d-block text-secondary small ms-4">Xử lý ngay sau khi đặt hàng.</span></label></div>
                            <div class="col-md-6"><label class="delivery-choice"><input class="form-check-input me-2" type="radio" name="delivery_type" value="scheduled" @checked($deliveryType === 'scheduled')><strong>Đặt giao sau</strong><span class="d-block text-secondary small ms-4">Chọn giờ nhận trong hôm nay.</span></label></div>
                        </div>
                        <div class="scheduled-delivery-fields {{ $deliveryType === 'scheduled' ? 'is-visible' : '' }} mb-3" data-scheduled-delivery-fields>
                            <label for="scheduled_delivery_time" class="form-label fw-semibold">Ngày và giờ nhận hàng</label>
                            <input type="datetime-local" id="scheduled_delivery_time" name="scheduled_delivery_time" min="{{ now()->addMinutes($scheduledLeadMinutes)->addMinute()->startOfMinute()->format('Y-m-d\TH:i') }}" max="{{ today()->setTime(22, 0)->format('Y-m-d\TH:i') }}" value="{{ old('scheduled_delivery_time') }}" class="form-control checkout-input @error('scheduled_delivery_time') is-invalid @enderror">
                            @error('scheduled_delivery_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text" data-scheduled-rule-text>Đặt trước tối thiểu {{ $scheduledLeadMinutes }} phút · Nhận trong giờ mở cửa 07:00–22:00 · Chỉ áp dụng trong hôm nay.</div>
                            <label for="delivery_note" class="form-label fw-semibold mt-3">Ghi chú thời gian giao</label>
                            <input id="delivery_note" name="delivery_note" maxlength="1000" value="{{ old('delivery_note') }}" class="form-control checkout-input" placeholder="Ví dụ: Giao đúng 10:30 giúp mình">
                        </div>
                        <label for="note" class="form-label fw-semibold">Ghi chú giao hàng <span class="text-secondary fw-normal">(không bắt buộc)</span></label>
                        <textarea
                            id="note"
                            name="note"
                            rows="5"
                            class="form-control checkout-input @error('note') is-invalid @enderror"
                            placeholder="Ví dụ: để phòng bảo vệ, gọi số khác, gần cổng chợ, nhà màu xanh..."
                        >{{ old('note') }}</textarea>
                        @error('note')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="checkout-panel summary-card p-4">
                        <div class="summary-card-head d-flex align-items-center justify-content-between gap-3 mb-3">
                            <div class="min-w-0">
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <h2 class="h4 fw-bold mb-0">Đơn hàng của bạn</h2>
                                    <span class="summary-item-count"><span data-checkout-item-count>{{ count($cart) }}</span>&nbsp;món</span>
                                </div>
                                @if($isGroupCheckout && $groupCheckoutGroup)
                                    <button type="submit"
                                            form="groupCheckoutEditForm"
                                            class="summary-add-more small fw-semibold text-primary text-nowrap"
                                            aria-label="Mở lại phòng nhóm để thêm món">
                                        <i class="bi bi-plus-circle me-1"></i>Thêm món nhóm
                                    </button>
                                @else
                                    <a href="{{ route('products.index', ['from' => 'checkout']) }}"
                                       class="summary-add-more small fw-semibold text-primary text-decoration-none text-nowrap"
                                       aria-label="Chọn thêm đồ uống vào đơn hàng">
                                        <i class="bi bi-plus-circle me-1"></i>Thêm món khác
                                    </a>
                                @endif
                            </div>
                            <span class="payment-icon"><i class="bi bi-receipt"></i></span>
                        </div>

                        <div class="summary-card-items">
                            <div class="vstack gap-2" data-checkout-summary-list>
                                @foreach($cart as $cartKey => $item)
                                    @include('client.checkout._summary-item', ['extra' => $loop->index >= 3])
                                @endforeach
                            </div>
                            <button type="button"
                                    class="checkout-summary-toggle {{ count($cart) <= 3 ? 'd-none' : '' }}"
                                    data-checkout-summary-toggle
                                    data-expanded="false"
                                    aria-expanded="false">
                                <span data-checkout-summary-toggle-label>Xem thêm {{ max(0, count($cart) - 3) }} món</span>
                                <i class="bi bi-chevron-down" aria-hidden="true"></i>
                            </button>
                        </div>

                        <div class="summary-card-footer">
                            <div class="summary-costs">
                                <div class="summary-cost-row">
                                    <span class="text-secondary">Tạm tính</span>
                                    <span data-checkout-subtotal>{{ number_format($total, 0, ',', '.') }}đ</span>
                                </div>
                                <div class="summary-cost-row">
                                    <span class="text-secondary">Phí vận chuyển</span>
                                    <span class="text-primary fw-semibold" id="summaryShippingFee">{{ number_format($shippingFee, 0, ',', '.') }}đ</span>
                                </div>
                                <div class="summary-cost-row small">
                                    <span class="text-secondary">Khoảng cách</span>
                                    <span id="summaryShippingDistance">Phí giao hàng cố định</span>
                                </div>
                                <div class="summary-cost-row">
                                    <span class="text-secondary">Phiếu ưu đãi</span>
                                    <span id="summaryVoucherText">{{ $discount > 0 ? '-' . number_format($discount, 0, ',', '.') . 'đ' : 'Chưa áp dụng' }}</span>
                                </div>
                                <div class="summary-cost-row summary-total-row">
                                    <span class="fw-bold">Tổng cộng</span>
                                    <strong class="text-primary" id="summaryGrandTotal">{{ number_format($grandTotal, 0, ',', '.') }}đ</strong>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg w-100 mt-3" id="placeOrderButton">
                                <i class="bi bi-check2-circle me-2"></i>Đặt hàng
                            </button>
                            <div class="alert alert-danger small mt-3 mb-0 d-none" id="checkoutAvailabilityWarning" role="alert" tabindex="-1">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                <span data-checkout-availability-message></span>
                                <a href="{{ route('cart.index') }}" class="alert-link d-block mt-1">Quay lại giỏ hàng để cập nhật</a>
                            </div>
                            <div
                                class="alert alert-warning small mt-3 mb-0 {{ $checkoutPhoneReady && $primaryAddress !== '' ? 'd-none' : '' }}"
                                id="checkoutContactWarning"
                                role="alert"
                            >
                                <i class="bi bi-exclamation-circle me-1"></i>
                                Vui lòng cập nhật địa chỉ nhận hàng và số điện thoại trước khi đặt hàng.
                            </div>
                            <a href="{{ route('cart.index') }}" class="btn btn-outline-primary w-100 mt-3">Quay lại giỏ hàng</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

@if($isGroupCheckout && $groupCheckoutGroup)
    <form id="groupCheckoutEditForm" method="POST" action="{{ route('group-orders.edit-checkout', $groupCheckoutGroup->code) }}" class="d-none">
        @csrf
    </form>
@endif

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
                        <input id="editAddressPhone" type="tel" class="form-control address-modal-field {{ $checkoutPhoneReady ? '' : 'is-invalid' }}" value="{{ $checkoutPhoneReady ? $selectedCheckoutPhone : '' }}" required autocomplete="tel" minlength="10" inputmode="numeric">
                        <div class="invalid-feedback" data-phone-feedback>Số điện thoại không đúng.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label small text-secondary mb-1" for="editAddressArea">Tỉnh/Thành phố, Quận/Huyện</label>
                        <input id="editAddressArea" type="text" class="form-control address-modal-field" value="{{ $primaryArea }}" placeholder="Ví dụ: Thanh Hóa, Phường Quảng Phú">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-secondary mb-1" for="editAddressHouseNumber">Số nhà</label>
                        <input id="editAddressHouseNumber" type="text" class="form-control address-modal-field" value="" placeholder="Ví dụ: 12/3">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label small text-secondary mb-1" for="editAddressStreet">Đường, thôn, hẻm...</label>
                        <input id="editAddressStreet" type="text" class="form-control address-modal-field" placeholder="Tên đường, thôn/xóm...">
                    </div>
                    <div class="col-12">
                        @include('admin.partials.location-picker', [
                            'pickerId' => 'checkout-edit-location-picker',
                            'label' => 'Vị trí đã xác nhận',
                            'hint' => 'Chọn pin trực tiếp trên bản đồ để lưu vị trí nhận hàng.',
                            'latValue' => old('address_location_confirmed') === '1' ? old('latitude') : null,
                            'lngValue' => old('address_location_confirmed') === '1' ? old('longitude') : null,
                            'defaultLat' => 16.047079,
                            'defaultLng' => 108.206230,
                            'defaultZoom' => 5,
                            'showTerritoryLabels' => true,
                            'autoFillHouseTarget' => '#editAddressHouseNumber',
                            'autoFillStreetTarget' => '#editAddressStreet',
                            'autoFillAreaTarget' => '#editAddressArea',
                            'showSearch' => true,
                            'searchPlaceholder' => 'Tìm số nhà, tên đường, phường/xã...',
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
                <button type="button" class="btn btn-address-primary" id="saveEditedAddress" @disabled(! $checkoutPhoneReady)>Lưu địa chỉ</button>
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
                        <input id="newAddressPhone" type="tel" class="form-control address-modal-field {{ $checkoutPhoneReady ? '' : 'is-invalid' }}" placeholder="Số điện thoại" value="{{ $checkoutPhoneReady ? $selectedCheckoutPhone : '' }}" required autocomplete="tel" minlength="10" inputmode="numeric">
                        <div class="invalid-feedback" data-phone-feedback>Số điện thoại không đúng.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label small text-secondary mb-1" for="newAddressArea">Tỉnh/Thành phố, Quận/Huyện</label>
                        <input id="newAddressArea" type="text" class="form-control address-modal-field" placeholder="Tỉnh/Thành phố, Quận/Huyện">
                    </div>
                    <div class="col-12">
                        <label class="form-label small text-secondary mb-1" for="newAddressHouseNumber">Số nhà</label>
                        <input id="newAddressHouseNumber" type="text" class="form-control address-modal-field" placeholder="Ví dụ: 12/3">
                    </div>
                    <div class="col-12">
                        <label class="form-label small text-secondary mb-1" for="newAddressStreet">Đường, thôn, hẻm...</label>
                        <input id="newAddressStreet" type="text" class="form-control address-modal-field" placeholder="Tên đường, thôn/xóm...">
                    </div>
                    <div class="col-12">
                        @include('admin.partials.location-picker', [
                            'pickerId' => 'checkout-new-location-picker',
                            'label' => 'Vị trí mới',
                            'hint' => 'Chọn pin trực tiếp trên bản đồ để lưu vị trí nhận hàng.',
                            'latValue' => old('address_location_confirmed') === '1' ? old('latitude') : null,
                            'lngValue' => old('address_location_confirmed') === '1' ? old('longitude') : null,
                            'defaultLat' => 16.047079,
                            'defaultLng' => 108.206230,
                            'defaultZoom' => 5,
                            'showTerritoryLabels' => true,
                            'autoFillHouseTarget' => '#newAddressHouseNumber',
                            'autoFillStreetTarget' => '#newAddressStreet',
                            'autoFillAreaTarget' => '#newAddressArea',
                            'showSearch' => true,
                            'searchPlaceholder' => 'Tìm số nhà, tên đường, phường/xã...',
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
                <button type="button" class="btn btn-address-primary" id="saveNewAddress" @disabled(! $checkoutPhoneReady)>Lưu địa chỉ</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade voucher-modal" id="voucherModal" tabindex="-1" aria-labelledby="voucherModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h2 class="address-modal-title mb-0" id="voucherModalTitle">Chọn phiếu ưu đãi Chill Drink</h2>
                <div class="ms-auto d-flex align-items-center gap-2 text-secondary">
                    <span>Hỗ trợ</span>
                    <i class="bi bi-question-circle"></i>
                </div>
                <button type="button" class="btn-close ms-3" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <div class="voucher-search-box d-flex flex-column flex-md-row align-items-md-center gap-3 mb-3">
                    <label for="voucherCodeInput" class="fw-semibold text-secondary flex-shrink-0">Mã ưu đãi</label>
                    <div class="flex-grow-1">
                        <input id="voucherCodeInput" type="text" class="form-control" placeholder="Nhập mã ưu đãi Chill Drink" value="{{ $selectedVoucherCode }}" maxlength="50" pattern="[A-Za-z0-9_-]+" autocomplete="off" autocapitalize="characters" spellcheck="false" aria-describedby="voucherCodeFeedback">
                        <div id="voucherCodeFeedback" class="small mt-1 d-none" role="status" aria-live="polite"></div>
                    </div>
                    <button type="button" class="btn voucher-apply-btn" id="voucherManualApply">Áp dụng</button>
                </div>

                <!-- Received Vouchers Section -->
                <div class="mb-4" id="receivedVouchersSection" style="{{ $receivedVouchers->isNotEmpty() ? '' : 'display: none;' }}">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="voucher-kind" style="background: linear-gradient(135deg, #fef3c7, #fde68a); color: #92400e; border: 1px solid #fbbf24;">
                            <i class="bi bi-star-fill me-1"></i>Phiếu ưu đãi đã nhận
                        </span>
                        <span class="small text-secondary" id="receivedVouchersCount">{{ $receivedVouchers->count() }} mã</span>
                    </div>
                    <div class="text-secondary small mb-3">Những voucher bạn đã nhận và có thể sử dụng ngay</div>
                    <div class="vstack gap-3 mb-3" id="receivedVouchersList">
                        @foreach($receivedVouchers as $userVoucher)
                            @php
                                $voucher = $userVoucher->voucher;
                                if (!$voucher) continue;
                                $isSupportVoucher = $voucher->isPersonalSupportVoucher();
                                
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
                                $voucherUsable = $voucherDiscount > 0 && $hasMinimumOrder;
                                $disabledReason = ! $hasMinimumOrder
                                    ? 'Cần đơn từ ' . number_format((int) $voucher->min_order, 0, ',', '.') . 'đ'
                                    : null;
                                if ($voucherIsShipping) {
                                    if ($fulfillmentType === 'pickup') {
                                        $voucherUsable = false;
                                        $disabledReason = 'Đơn nhận tại quán không áp dụng freeship';
                                    } elseif ($shippingFee <= 0) {
                                        $voucherUsable = false;
                                        $disabledReason = 'Phí vận chuyển 0đ (không cần áp dụng)';
                                    }
                                }
                            @endphp
                            <div
                                class="voucher-ticket {{ $voucherIsShipping ? 'is-shipping' : 'is-discount' }} {{ (($voucherIsShipping ? $selectedShippingVoucherCode : $selectedVoucherCode) === $voucher->code) && $voucherUsable ? 'active' : '' }} {{ $voucherUsable ? '' : 'is-disabled' }}"
                                data-voucher-card
                                data-voucher-code="{{ $voucher->code }}"
                                data-voucher-label="{{ $voucherLabel }}"
                                data-voucher-discount="{{ $voucherDiscount }}"
                                data-voucher-type="{{ $voucherIsShipping ? 'shipping' : 'discount' }}"
                                data-voucher-disabled="{{ $voucherUsable ? '0' : '1' }}"
                                data-min-order="{{ (int) $voucher->min_order }}"
                                data-rate-type="{{ $voucher->type }}"
                                data-voucher-value="{{ (float) $voucher->value }}"
                                data-max-discount="{{ (int) ($voucher->max_discount ?? 0) }}"
                                data-point-cost="{{ (int) ($voucher->point_cost ?? 0) }}"
                                data-is-redeemable="{{ $voucher->is_redeemable ? '1' : '0' }}"
                                data-is-received="1"
                                style="position: relative;"
                            >
                                <div class="voucher-ticket-brand" style="position: relative;">
                                    <span class="brand-circle"><i class="bi {{ $voucherIcon }}"></i></span>
                                    <strong>{{ $voucher->code }}</strong>
                                    @if($isSupportVoucher)
                                        <span style="position: absolute; bottom: .35rem; left: .45rem; background: #d1fae5; color: #047857; font-size: .62rem; padding: .12rem .35rem; border-radius: 4px; font-weight: 800;">HỖ TRỢ ĐƠN HÀNG</span>
                                    @endif
                                    <!-- Badge "Đã nhận" -->
                                    <span style="position: absolute; top: 0.5rem; right: -0.25rem; background: #fbbf24; color: #78350f; font-size: 0.65rem; padding: 0.15rem 0.35rem; border-radius: 4px; font-weight: 800; transform: rotate(8deg); box-shadow: 0 2px 6px rgba(251, 191, 36, 0.3);">
                                        ĐÃ NHẬN
                                    </span>
                                </div>
                                <div class="voucher-ticket-body">
                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                        <span class="voucher-limit">{{ $voucher->usage_limit > 0 ? 'Số lượng có hạn' : 'Không giới hạn' }}</span>
                                        <span class="voucher-kind">{{ $isSupportVoucher ? 'Voucher hỗ trợ' : ($voucherIsShipping ? 'Freeship' : 'Giảm giá') }}</span>
                                        <span class="fw-semibold text-secondary">{{ $voucherValueText }}</span>
                                        @if($voucher->max_discount)
                                            <span class="fw-semibold text-secondary">tối đa {{ number_format($voucher->max_discount, 0, ',', '.') }}đ</span>
                                        @endif
                                    </div>
                                    <div class="text-secondary mb-2">
                                        Đơn tối thiểu {{ number_format((int) $voucher->min_order, 0, ',', '.') }}đ
                                        @if($voucher->is_redeemable && $voucher->point_cost > 0)
                                            · Đã đổi bằng {{ number_format($voucher->point_cost, 0, ',', '.') }} điểm
                                        @endif
                                    </div>
                                    @if($voucherUsable)
                                        <span class="voucher-only mb-2" data-voucher-badge>
                                            {{ $voucherIsShipping ? 'Giảm phí vận chuyển' : 'Giảm đơn hàng' }}
                                            {{ number_format($voucherDiscount, 0, ',', '.') }}đ
                                        </span>
                                    @else
                                        <span class="voucher-only mb-2" data-voucher-badge>{{ $disabledReason }}</span>
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
                    <hr class="my-4">
                </div>

                <div class="mb-3">
                    <div class="voucher-group-title">Mã có thể áp dụng</div>
                    <div class="text-secondary">Có thể chọn 1 phiếu miễn phí vận chuyển và 1 phiếu giảm giá</div>
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
                                            $voucherUsable = $voucherDiscount > 0 && $hasMinimumOrder;
                                            $disabledReason = ! $hasMinimumOrder
                                                ? 'Cần đơn từ ' . number_format((int) $voucher->min_order, 0, ',', '.') . 'đ'
                                                : null;
                                            if ($voucherIsShipping) {
                                                if ($fulfillmentType === 'pickup') {
                                                    $voucherUsable = false;
                                                    $disabledReason = 'Đơn nhận tại quán không áp dụng freeship';
                                                } elseif ($shippingFee <= 0) {
                                                    $voucherUsable = false;
                                                    $disabledReason = 'Phí vận chuyển 0đ (không cần áp dụng)';
                                                }
                                            }
                                        @endphp
                                        <div
                                            class="voucher-ticket {{ $voucherIsShipping ? 'is-shipping' : 'is-discount' }} {{ (($voucherIsShipping ? $selectedShippingVoucherCode : $selectedVoucherCode) === $voucher->code) && $voucherUsable ? 'active' : '' }} {{ $voucherUsable ? '' : 'is-disabled' }}"
                                            data-voucher-card
                                            data-voucher-code="{{ $voucher->code }}"
                                            data-voucher-label="{{ $voucherLabel }}"
                                            data-voucher-discount="{{ $voucherDiscount }}"
                                            data-voucher-type="{{ $voucherIsShipping ? 'shipping' : 'discount' }}"
                                            data-voucher-disabled="{{ $voucherUsable ? '0' : '1' }}"
                                            data-min-order="{{ (int) $voucher->min_order }}"
                                            data-rate-type="{{ $voucher->type }}"
                                            data-voucher-value="{{ (float) $voucher->value }}"
                                            data-max-discount="{{ (int) ($voucher->max_discount ?? 0) }}"
                                            data-point-cost="{{ (int) ($voucher->point_cost ?? 0) }}"
                                            data-is-redeemable="{{ $voucher->is_redeemable ? '1' : '0' }}"
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
                                                    @if($voucher->is_redeemable && $voucher->point_cost > 0)
                                                        · {{ number_format($voucher->point_cost, 0, ',', '.') }} điểm
                                                    @endif
                                                </div>
                                                @if($voucherUsable)
                                                    <span class="voucher-only mb-2" data-voucher-badge>
                                                        {{ $voucherIsShipping ? 'Giảm phí vận chuyển' : 'Giảm đơn hàng' }}
                                                        {{ number_format($voucherDiscount, 0, ',', '.') }}đ
                                                    </span>
                                                @else
                                                    <span class="voucher-only mb-2" data-voucher-badge>{{ $disabledReason }}</span>
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

                    @auth
                        <div class="mt-4 p-3 rounded-3 d-flex align-items-center justify-content-between flex-wrap gap-2" style="background: rgba(13, 147, 115, 0.08); border: 1px dashed #0D9373;">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-gift-fill text-primary fs-5"></i>
                                <span class="small fw-semibold text-dark">Bạn đang có điểm thưởng Chill Drink?</span>
                            </div>
                            <a href="{{ route('loyalty.index') }}" target="_blank" class="small fw-bold text-primary text-decoration-none">
                                Đổi voucher ưu đãi ngay <i class="bi bi-arrow-up-right-square ms-1"></i>
                            </a>
                        </div>
                    @endauth
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
        const checkoutSummaryList = document.querySelector('[data-checkout-summary-list]');
        const checkoutSummaryToggle = document.querySelector('[data-checkout-summary-toggle]');

        function syncCheckoutSummaryItems() {
            if (!checkoutSummaryList || !checkoutSummaryToggle) return;

            const rows = Array.from(checkoutSummaryList.querySelectorAll('[data-checkout-item]'));
            const extraCount = Math.max(0, rows.length - 3);
            const expanded = checkoutSummaryToggle.dataset.expanded === 'true' && extraCount > 0;

            rows.forEach((row, index) => {
                row.classList.toggle('d-none', !expanded && index >= 3);
            });

            checkoutSummaryToggle.classList.toggle('d-none', extraCount === 0);
            checkoutSummaryToggle.dataset.expanded = expanded ? 'true' : 'false';
            checkoutSummaryToggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            const label = checkoutSummaryToggle.querySelector('[data-checkout-summary-toggle-label]');
            if (label) label.textContent = expanded ? 'Thu gọn' : `Xem thêm ${extraCount} món`;
        }

        checkoutSummaryToggle?.addEventListener('click', () => {
            checkoutSummaryToggle.dataset.expanded = checkoutSummaryToggle.dataset.expanded === 'true' ? 'false' : 'true';
            syncCheckoutSummaryItems();
        });

        window.syncCheckoutSummaryItems = syncCheckoutSummaryItems;
        syncCheckoutSummaryItems();

        const shippingAddressInput = document.getElementById('shipping_address_ui');
        const shippingAreaInput = document.getElementById('shipping_area_ui');
        const shippingPhoneInput = document.getElementById('shipping_phone_ui');
        const checkoutLatitudeInput = document.getElementById('checkout_latitude');
        const checkoutLongitudeInput = document.getElementById('checkout_longitude');
        const addressLocationConfirmedInput = document.getElementById('address_location_confirmed');
        const editAddressHouseNumber = document.getElementById('editAddressHouseNumber');
        const newAddressHouseNumber = document.getElementById('newAddressHouseNumber');
        const fulfillmentDeliveryInput = document.getElementById('deliveryTypeDelivery');
        const selectedReceiver = document.getElementById('selectedReceiver');
        const selectedPhone = document.getElementById('selectedPhone');
        const selectedPhoneDivider = document.getElementById('selectedPhoneDivider');
        const selectedAddressText = document.getElementById('selectedAddressText');
        const selectedDefaultBadge = document.getElementById('selectedDefaultBadge');
        const addressList = document.getElementById('addressList');
        const placeOrderButton = document.getElementById('placeOrderButton');
        const checkoutContactWarning = document.getElementById('checkoutContactWarning');
        const noteInput = document.getElementById('note');
        const addressHouseNumberWarning = document.querySelector('[data-address-house-number-warning]');
        const noteRequiredIndicator = document.querySelector('[data-note-required-indicator]');
        const editAddressPhone = document.getElementById('editAddressPhone');
        const newAddressPhone = document.getElementById('newAddressPhone');
        const saveEditedAddressButton = document.getElementById('saveEditedAddress');
        const saveNewAddressButton = document.getElementById('saveNewAddress');

        const addressListModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('addressListModal'));
        const addressEditModalElement = document.getElementById('addressEditModal');
        const addressAddModalElement = document.getElementById('addressAddModal');
        const addressEditModal = bootstrap.Modal.getOrCreateInstance(addressEditModalElement);
        const addressAddModal = bootstrap.Modal.getOrCreateInstance(addressAddModalElement);
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
            cupCount: {{ (int) $cartQuantity }},
        };
        const shippingTiers = @json($shippingDistanceOptions);
        const maxOrderDistanceKm = {{ json_encode(\App\Support\OrderDistancePolicy::MAX_DISTANCE_KM) }};
        const shippingDistanceLabel = document.getElementById('shippingDistanceLabel');
        const shippingEstimateDetail = document.getElementById('shippingEstimateDetail');
        const shippingInlineFee = document.getElementById('shippingInlineFee');
        const shippingEta = document.getElementById('shippingEta');
        const summaryShippingFee = document.getElementById('summaryShippingFee');
        const summaryShippingDistance = document.getElementById('summaryShippingDistance');
        const summaryGrandTotal = document.getElementById('summaryGrandTotal');
        const branchSelectShell = document.querySelector('.branch-select-shell');
        const branchSelectNote = document.querySelector('[data-branch-select-note]');
        const isGroupCheckout = @json($isGroupCheckout ?? false);
        const checkoutDeviceDriftRadiusM = 100;
        const checkoutDeviceMaxAccuracyM = 250;

        const addressStoreEndpoint = @json(route('checkout.addresses.store'));
        const addressUpdateEndpoint = @json(url('/checkout/addresses'));
        const nearestBranchEndpoint = @json(route('api.branches.nearest'));
        const branchesListEndpoint = @json(route('api.branches.list'));
        const deliveryQuoteEndpoint = @json(route('api.delivery.quote'));
        const addressLookupEndpoint = @json(route('api.address-lookup'));
        const checkoutAvailabilityEndpoint = @json(route('checkout.availability'));
        const scheduledDeliveryFields = document.querySelector('[data-scheduled-delivery-fields]');
        const scheduledPaymentNotice = document.querySelector('[data-scheduled-payment-notice]');
        const scheduledDeliveryInput = document.getElementById('scheduled_delivery_time');
        const scheduledRuleText = document.querySelector('[data-scheduled-rule-text]');
        const codPaymentInput = document.querySelector('input[name="payment_method"][value="cod"]');
        const prepaidPaymentInput = document.querySelector('input[name="payment_method"][value="vnpay"]');

        function syncCheckoutBranchWithHeader(branchSelect = document.getElementById('branch_id')) {
            const selectedOption = branchSelect?.options[branchSelect.selectedIndex];

            if (!selectedOption?.value) {
                return;
            }

            const branchName = selectedOption.textContent.split('—')[0].trim();
            const syncRequest = window.syncStorefrontBranch?.(selectedOption.value, branchName);
            syncRequest?.catch((error) => console.error('Không thể đồng bộ chi nhánh trên header.', error));
        }

        const unavailableCheckoutProducts = new Map();
        let checkoutAvailabilitySequence = 0;

        function renderCheckoutAvailability() {
            document.querySelectorAll('[data-checkout-product-id]').forEach((row) => {
                const productId = String(row.dataset.checkoutProductId || '');
                const unavailable = unavailableCheckoutProducts.has(productId);
                const quantityControl = row.querySelector('[data-checkout-qty-control]');
                const quantityInput = row.querySelector('[data-checkout-item-quantity-input]');

                row.classList.toggle('is-unavailable', unavailable);
                row.querySelector('[data-checkout-unavailable-badge]')?.classList.toggle('d-none', !unavailable);

                if (quantityInput) {
                    quantityInput.disabled = unavailable;
                }

                if (quantityControl) {
                    if (unavailable) {
                        quantityControl.querySelector('[data-checkout-qty-minus]')?.setAttribute('disabled', 'disabled');
                        quantityControl.querySelector('[data-checkout-qty-plus]')?.setAttribute('disabled', 'disabled');
                    } else {
                        updateCheckoutControlState(quantityControl, clampCheckoutQuantity(quantityInput?.value || 1));
                    }
                }
            });

            const unavailableNames = [...unavailableCheckoutProducts.values()];
            const hasUnavailableProducts = unavailableNames.length > 0;
            const warning = document.getElementById('checkoutAvailabilityWarning');
            const message = warning?.querySelector('[data-checkout-availability-message]');

            placeOrderButton?.toggleAttribute('disabled', hasUnavailableProducts);
            warning?.classList.toggle('d-none', !hasUnavailableProducts);

            if (message && hasUnavailableProducts) {
                message.textContent = `${unavailableNames.join(', ')} đã tạm hết hàng tại chi nhánh đang chọn. Vui lòng cập nhật giỏ hàng hoặc đổi chi nhánh.`;
            }
        }

        async function refreshCheckoutAvailability() {
            const branchId = document.getElementById('branch_id')?.value;

            if (!branchId) {
                return;
            }

            const sequence = ++checkoutAvailabilitySequence;

            try {
                const url = new URL(checkoutAvailabilityEndpoint, window.location.origin);
                url.searchParams.set('branch_id', branchId);
                const response = await fetch(url.toString(), {
                    headers: { Accept: 'application/json' },
                    cache: 'no-store',
                });
                const payload = await response.json();

                if (sequence !== checkoutAvailabilitySequence || !response.ok || payload.success !== true) {
                    return;
                }

                unavailableCheckoutProducts.clear();
                (payload.unavailable || []).forEach((product) => {
                    unavailableCheckoutProducts.set(String(product.product_id), product.name || 'Sản phẩm');
                });
                renderCheckoutAvailability();
            } catch (error) {
                console.warn('Không thể kiểm tra trạng thái sản phẩm tại chi nhánh.', error);
            }
        }

        document.addEventListener('product:availability-applied', (event) => {
            const availability = event.detail || {};
            const branchId = String(document.getElementById('branch_id')?.value || '');

            if (!branchId || String(availability.branch_id || '') !== branchId) {
                return;
            }

            const productId = String(availability.product_id || '');
            if (!productId) {
                return;
            }

            const row = document.querySelector(`[data-checkout-product-id="${CSS.escape(productId)}"]`);
            if (!row) {
                return;
            }

            if (availability.is_available) {
                unavailableCheckoutProducts.delete(productId);
            } else {
                const productName = availability.product_name
                    || row.querySelector('.checkout-summary-name')?.textContent?.trim()
                    || 'Sản phẩm';
                unavailableCheckoutProducts.set(productId, productName);
                window.showRealtimeToast?.(`${productName} vừa tạm hết hàng tại chi nhánh đang chọn.`, 'warning');
            }

            renderCheckoutAvailability();
        });

        function syncScheduledPaymentRule() {
            const scheduledInput = document.querySelector('input[name="delivery_type"][value="scheduled"]');
            const isScheduled = Boolean(scheduledInput?.checked);

            scheduledDeliveryFields?.classList.toggle('is-visible', isScheduled);
            scheduledPaymentNotice?.classList.toggle('d-none', !isScheduled);

            if (codPaymentInput) {
                codPaymentInput.disabled = isScheduled;
                codPaymentInput.closest('.payment-option')?.classList.toggle('opacity-50', isScheduled);
            }

            if (isScheduled && codPaymentInput?.checked && prepaidPaymentInput) {
                prepaidPaymentInput.checked = true;
            }

            const fulfillment = document.querySelector('input[name="fulfillment_type"]:checked')?.value || 'delivery';
            const minimumLead = fulfillment === 'pickup'
                ? @json(\App\Support\ScheduledDelivery::minimumBookingLeadMinutes('pickup'))
                : @json(\App\Support\ScheduledDelivery::minimumBookingLeadMinutes('delivery'));
            if (scheduledDeliveryInput) {
                const earliest = new Date(Date.now() + minimumLead * 60 * 1000);
                earliest.setSeconds(0, 0);
                earliest.setMinutes(earliest.getMinutes() + 1);
                const localIso = new Date(earliest.getTime() - earliest.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
                scheduledDeliveryInput.min = localIso;
            }
            if (scheduledRuleText) {
                scheduledRuleText.textContent = `Đặt trước tối thiểu ${minimumLead} phút · Nhận trong giờ mở cửa 07:00–22:00 · Chỉ áp dụng trong hôm nay.`;
            }
        }

        document.querySelectorAll('input[name="delivery_type"]').forEach(input => input.addEventListener('change', syncScheduledPaymentRule));
        document.querySelectorAll('input[name="fulfillment_type"]').forEach(input => input.addEventListener('change', syncScheduledPaymentRule));
        syncScheduledPaymentRule();

        function hasHouseNumber(value) {
            const text = String(value || '').trim();

            return /(?:\b(?:số|so|nhà|nha|ngõ|ngo|hẻm|hem|ngách|ngach|kiệt|kiet)\s*[:#.-]?\s*\d+[a-z]?(?:[/-]\d+[a-z]?)*(?![.,]\d)\b|\b\d+[a-z]?(?:[/-]\d+[a-z]?)*(?![.,]\d)\b)/iu.test(text);
        }

        function renderAddressPanelWarning(message, shouldScroll = false) {
            if (!addressHouseNumberWarning) {
                return;
            }

            addressHouseNumberWarning.innerHTML = `<i class="bi bi-exclamation-circle me-1"></i>${message}`;
            addressHouseNumberWarning.classList.remove('d-none');

            if (shouldScroll) {
                addressHouseNumberWarning.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        function showAddressHouseNumberWarning(message, shouldScroll = false) {
            renderAddressPanelWarning(persistentAddressWarningMessage || message, shouldScroll);
        }

        function hideAddressHouseNumberWarning() {
            if (persistentAddressWarningMessage) {
                renderAddressPanelWarning(persistentAddressWarningMessage);
                return;
            }

            addressHouseNumberWarning?.classList.add('d-none');
            if (addressHouseNumberWarning) {
                addressHouseNumberWarning.textContent = '';
            }
        }

        function setPersistentAddressWarning(message, shouldScroll = false) {
            persistentAddressWarningMessage = String(message || '').trim();
            if (!persistentAddressWarningMessage) {
                return;
            }

            renderAddressPanelWarning(persistentAddressWarningMessage, shouldScroll);
        }

        function clearPersistentAddressWarning() {
            persistentAddressWarningMessage = '';
            hideAddressHouseNumberWarning();
        }

        function syncNoteRequirement(isRequired) {
            noteRequiredIndicator?.classList.toggle('d-none', !isRequired);
        }

        function syncAddressHouseNumberNotice(shouldScroll = false) {
            if (!fulfillmentDeliveryInput?.checked) {
                hideAddressHouseNumberWarning();
                syncNoteRequirement(false);
                return;
            }

            hideAddressHouseNumberWarning();
            syncNoteRequirement(false);
        }
        window.syncAddressHouseNumberNotice = syncAddressHouseNumberNotice;

        function clearAddressHouseNumberWarning() {
            noteInput?.setCustomValidity('');
            noteInput?.classList.remove('is-invalid');
        }

        function haversineDistanceMeters(lat1, lng1, lat2, lng2) {
            const earthRadius = 6371000;
            const latDelta = (lat2 - lat1) * Math.PI / 180;
            const lngDelta = (lng2 - lng1) * Math.PI / 180;
            const startLat = lat1 * Math.PI / 180;
            const endLat = lat2 * Math.PI / 180;
            const a = Math.sin(latDelta / 2) ** 2
                + Math.cos(startLat) * Math.cos(endLat) * Math.sin(lngDelta / 2) ** 2;

            return earthRadius * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        }

        function formatDistanceForCheckout(distanceMeters) {
            const distance = Math.max(0, Number(distanceMeters) || 0);

            if (distance >= 1000) {
                return `${(distance / 1000).toFixed(1)} km`;
            }

            return `${Math.round(distance)} m`;
        }
        function clampCheckoutQuantity(value) {
            const normalized = Number.parseInt(String(value || '').replace(/[^\d]/g, ''), 10);
            return Number.isFinite(normalized) ? Math.min(99, Math.max(1, normalized)) : 1;
        }

        function updateCheckoutControlState(control, quantity) {
            const minusButton = control?.querySelector('[data-checkout-qty-minus]');
            const plusButton = control?.querySelector('[data-checkout-qty-plus]');

            if (minusButton) {
                minusButton.disabled = quantity <= 1;
            }

            if (plusButton) {
                plusButton.disabled = quantity >= 99;
            }
        }

        function bindCheckoutQuantityControls() {
            document.querySelectorAll('[data-checkout-qty-control]').forEach((control) => {
                const input = control.querySelector('[data-checkout-item-quantity-input]');
                const minusButton = control.querySelector('[data-checkout-qty-minus]');
                const plusButton = control.querySelector('[data-checkout-qty-plus]');

                if (!input || !minusButton || !plusButton) {
                    return;
                }

                const row = control.closest('[data-checkout-item]');
                let syncTimer = null;

                const clamp = (value) => clampCheckoutQuantity(value);

                const render = (nextQuantity) => {
                    const quantity = clamp(nextQuantity);
                    input.value = String(quantity);
                    updateCheckoutControlState(control, quantity);
                    return quantity;
                };

                const stopRepeat = () => {
                    if (minusButton._repeatTimer) {
                        window.clearTimeout(minusButton._repeatTimer);
                        minusButton._repeatTimer = null;
                    }
                    if (minusButton._repeatInterval) {
                        window.clearInterval(minusButton._repeatInterval);
                        minusButton._repeatInterval = null;
                    }
                    if (plusButton._repeatTimer) {
                        window.clearTimeout(plusButton._repeatTimer);
                        plusButton._repeatTimer = null;
                    }
                    if (plusButton._repeatInterval) {
                        window.clearInterval(plusButton._repeatInterval);
                        plusButton._repeatInterval = null;
                    }
                };

                const scheduleSync = (quantity, immediate = false) => {
                    clearTimeout(syncTimer);
                    syncTimer = window.setTimeout(() => {
                        syncTimer = null;
                        syncCheckoutQuantity({
                            url: control.dataset.checkoutUpdateUrl || '',
                            method: 'PATCH',
                            quantity,
                            row,
                        });
                    }, immediate ? 0 : 220);
                };

                const startRepeat = (delta, button) => {
                    stopRepeat();
                    const nextQuantity = render(clamp(input.value || 1) + delta);
                    scheduleSync(nextQuantity, true);
                    button._repeatTimer = window.setTimeout(() => {
                        button._repeatInterval = window.setInterval(() => {
                            const repeatedQuantity = render(clamp(input.value || 1) + delta);
                            scheduleSync(repeatedQuantity, true);
                        }, 75);
                    }, 260);
                };

                const pressStart = (event, delta, button) => {
                    event.preventDefault();
                    event.stopPropagation();
                    startRepeat(delta, button);
                };

                minusButton.addEventListener('pointerdown', (event) => pressStart(event, -1, minusButton));
                plusButton.addEventListener('pointerdown', (event) => pressStart(event, 1, plusButton));

                [minusButton, plusButton].forEach((button) => {
                    button.addEventListener('pointerup', stopRepeat);
                    button.addEventListener('pointercancel', stopRepeat);
                    button.addEventListener('lostpointercapture', stopRepeat);
                });

                input.addEventListener('click', () => {
                    input.select();
                });

                input.addEventListener('focus', () => {
                    input.select();
                });

                input.addEventListener('input', () => {
                    const digitsOnly = String(input.value || '').replace(/[^\d]/g, '').slice(0, 2);
                    if (input.value !== digitsOnly) {
                        input.value = digitsOnly;
                    }

                    if (digitsOnly === '') {
                        return;
                    }

                    const quantity = render(digitsOnly);
                    scheduleSync(quantity);
                });

                input.addEventListener('blur', () => {
                    if (String(input.value || '').trim() === '') {
                        const quantity = render(1);
                        scheduleSync(quantity, true);
                        return;
                    }

                    const quantity = render(input.value);
                    scheduleSync(quantity, true);
                });

                input.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        stopRepeat();
                        const quantity = render(input.value || 1);
                        scheduleSync(quantity, true);
                        return;
                    }

                    if (event.key === 'ArrowUp') {
                        event.preventDefault();
                        stopRepeat();
                        const quantity = render(clamp(input.value || 1) + 1);
                        scheduleSync(quantity, true);
                    } else if (event.key === 'ArrowDown') {
                        event.preventDefault();
                        stopRepeat();
                        const quantity = render(clamp(input.value || 1) - 1);
                        scheduleSync(quantity, true);
                    }
                });

                render(input.value || 1);
            });
        }

        function applyCheckoutUpdate(payload, method, row, cartKey) {
            if (method === 'DELETE') {
                row?.remove();
                window.syncCheckoutSummaryItems?.();
            } else {
                const item = payload.items?.[cartKey];
                if (row && item) {
                    const quantityValue = row.querySelector('[data-checkout-item-quantity]');
                    const quantityText = row.querySelector('[data-checkout-item-quantity-text]');
                    const quantityInput = row.querySelector('[data-checkout-item-quantity-input]');
                    const subtotalValue = row.querySelector('[data-checkout-item-subtotal]');

                    if (quantityValue) quantityValue.textContent = item.quantity;
                    if (quantityText) quantityText.textContent = item.quantity;
                    if (quantityInput) quantityInput.value = String(item.quantity);
                    if (subtotalValue) subtotalValue.textContent = item.subtotal_formatted;
                    updateCheckoutControlState(row.querySelector('[data-checkout-qty-control]'), Number(item.quantity || 0));
                }
            }

            document.querySelector('[data-checkout-item-count]').textContent = payload.count;
            document.querySelector('[data-checkout-subtotal]').textContent = payload.total_formatted;
            shippingConfig.subtotal = Number(payload.total || 0);
            shippingConfig.cupCount = Math.max(1, Number(payload.quantity_count || 1));

            // Tự động kiểm tra và tính toán lại giá trị voucher đã chọn theo đơn giá mới
            if (typeof refreshVoucherCards === 'function') {
                refreshVoucherCards();
            }
            updateShippingSummary();

            if (payload.count === 0) {
                window.location.href = @json(route('cart.index'));
            }
        }

        async function syncCheckoutQuantity({ url, method = 'PATCH', quantity, row, button, confirmMessage }) {
            if (!url) {
                return;
            }

            const control = row?.querySelector('[data-checkout-qty-control]');
            const normalizedQuantity = method === 'DELETE' ? null : clampCheckoutQuantity(quantity);

            if (button?.disabled) {
                return;
            }

            if (confirmMessage && !window.confirm(confirmMessage)) {
                return;
            }

            if (control && control._checkoutSyncing) {
                control._checkoutSyncPending = { url, method, quantity: normalizedQuantity ?? quantity, row, button, confirmMessage };
                return;
            }

            if (control) {
                control._checkoutSyncing = true;
            }

            const input = control?.querySelector('[data-checkout-item-quantity-input]');

            if (control) {
                clearTimeout(control._checkoutSyncTimer);
                control._checkoutSyncTimer = null;
            }

            if (input && normalizedQuantity !== null) {
                input.value = String(normalizedQuantity);
                updateCheckoutControlState(control, normalizedQuantity);
            }

            if (button) {
                button.disabled = true;
            }

            try {
                const body = new FormData();
                body.append('_token', csrfToken);
                body.append('_method', method);
                if (normalizedQuantity !== null) {
                    body.append('quantity', String(normalizedQuantity));
                }

                const response = await fetch(url, {
                    method: 'POST',
                    body,
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error('cart_update_failed');
                }

                const payload = await response.json();
                applyCheckoutUpdate(payload, method, row, row?.dataset.checkoutItem || '');
            } finally {
                if (button) {
                    button.disabled = false;
                }

                if (control) {
                    control._checkoutSyncing = false;
                    const pending = control._checkoutSyncPending;
                    control._checkoutSyncPending = null;

                    if (pending) {
                        syncCheckoutQuantity(pending);
                    }
                }
            }
        }
        document.addEventListener('click', async function (event) {
            const button = event.target.closest('[data-checkout-qty-remove], [data-checkout-cart-action][data-method="DELETE"]');
            if (!button || button.disabled) return;

            event.preventDefault();
            event.stopPropagation();

            const row = button.closest('[data-checkout-item]');
            await syncCheckoutQuantity({
                url: button.dataset.checkoutCartAction,
                method: button.dataset.method || 'PATCH',
                quantity: button.dataset.quantity,
                row,
                button,
                confirmMessage: button.dataset.confirm,
            });
        });

        bindCheckoutQuantityControls();
        let selectedAddressId = @json($selectedAddressId ?? 'primary');
        const voucherState = card => ({
            code: card?.dataset.voucherCode || '',
            label: card?.dataset.voucherLabel || '',
            discount: Number(card?.dataset.voucherDiscount || 0),
        });
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
        window.shippingConfig = shippingConfig;
        const loyaltyPoints = {{ (int) ($loyaltyContext['points'] ?? 0) }};

        function refreshVoucherCards() {
            const subtotal = Number(shippingConfig.subtotal || 0);
            const isPickup = document.getElementById('deliveryTypePickup')?.checked === true;
            const currentShippingFee = isPickup ? 0 : Number(shippingConfig.fixedShippingFee || 0);

            document.querySelectorAll('[data-voucher-card]').forEach((card) => {
                const type = card.dataset.voucherType || 'discount';
                const minOrder = Number(card.dataset.minOrder || 0);
                const rateType = card.dataset.rateType || 'fixed';
                const val = Number(card.dataset.voucherValue || 0);
                const maxDiscount = Number(card.dataset.maxDiscount || 0);
                const pointCost = Number(card.dataset.pointCost || 0);
                const isRedeemable = card.dataset.isRedeemable === '1';
                const isReceived = card.dataset.isReceived === '1';

                let calculatedDiscount = 0;
                if (rateType === 'percent') {
                    const raw = Math.round((subtotal * val) / 100);
                    calculatedDiscount = maxDiscount > 0 ? Math.min(raw, maxDiscount) : raw;
                } else {
                    calculatedDiscount = val;
                }

                const hasMinimumOrder = subtotal >= minOrder;
                const hasPoints = isReceived || !isRedeemable || pointCost <= 0 || loyaltyPoints >= pointCost;

                let isUsable = calculatedDiscount > 0 && hasMinimumOrder && hasPoints;
                let disabledReason = '';

                if (!hasMinimumOrder) {
                    isUsable = false;
                    disabledReason = `Cần đơn từ ${formatVnd(minOrder)}`;
                } else if (!hasPoints) {
                    isUsable = false;
                    disabledReason = `Cần ${formatVnd(pointCost).replace('đ', '')} điểm`;
                }

                if (type === 'shipping') {
                    if (isPickup) {
                        isUsable = false;
                        disabledReason = 'Đơn nhận tại quán không áp dụng freeship';
                    } else if (currentShippingFee <= 0) {
                        isUsable = false;
                        disabledReason = 'Phí vận chuyển 0đ (không cần áp dụng)';
                    } else {
                        calculatedDiscount = Math.min(calculatedDiscount, currentShippingFee);
                    }
                }

                card.dataset.voucherDiscount = calculatedDiscount;
                card.dataset.voucherDisabled = isUsable ? '0' : '1';
                card.classList.toggle('is-disabled', !isUsable);

                const radio = card.querySelector('.voucher-radio');
                if (radio) {
                    radio.disabled = !isUsable;
                }

                if (!isUsable && card.classList.contains('active')) {
                    card.classList.remove('active');
                    if (radio) {
                        radio.classList.remove('active');
                        radio.setAttribute('aria-pressed', 'false');
                    }
                    if (pendingVouchers[type]?.code === card.dataset.voucherCode) {
                        pendingVouchers[type] = voucherState(null);
                        if (type === 'shipping') {
                            selectedShippingVoucherCode.value = '';
                        } else {
                            selectedVoucherCode.value = '';
                        }
                    }
                } else if (isUsable && card.classList.contains('active')) {
                    if (pendingVouchers[type]?.code === card.dataset.voucherCode) {
                        pendingVouchers[type].discount = calculatedDiscount;
                    }
                }

                const badge = card.querySelector('[data-voucher-badge]');
                if (badge) {
                    if (isUsable) {
                        badge.textContent = `${type === 'shipping' ? 'Giảm phí vận chuyển' : 'Giảm đơn hàng'} ${formatVnd(calculatedDiscount)}`;
                    } else {
                        badge.textContent = disabledReason;
                    }
                }
            });

            // Đồng bộ lại tổng giảm giá và text hiển thị sau khi tính toán lại
            const shippingFee = Number(shippingConfig.fixedShippingFee || 0);
            const discountPart = Number(pendingVouchers?.discount?.discount || 0);
            const shippingPart = Math.min(shippingFee, Number(pendingVouchers?.shipping?.discount || 0));
            shippingConfig.discount = discountPart + shippingPart;

            const activeLabels = [pendingVouchers?.shipping?.label, pendingVouchers?.discount?.label].filter(Boolean);
            if (selectedVoucherText) {
                if (activeLabels.length > 0) {
                    selectedVoucherText.textContent = `Đã chọn: ${activeLabels.join(' + ')}`;
                } else {
                    selectedVoucherText.textContent = 'Chưa chọn phiếu ưu đãi';
                }
            }

            if (summaryVoucherText) {
                summaryVoucherText.textContent = shippingConfig.discount > 0
                    ? `-${formatVnd(shippingConfig.discount)}`
                    : 'Chưa áp dụng';
            }
        }
        window.refreshVoucherCards = refreshVoucherCards;
        let addressBook = @json($addressBook ?? []);
        const addressSaveUrls = {
            primary: @json(route('checkout.addresses.primary.update')),
            store: @json(route('checkout.addresses.store')),
            updateBase: @json(url('/checkout/addresses')),
        };
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const restoredLocationConfirmed = @json(old('address_location_confirmed') === '1');
        const restoredConfirmedLocation = restoredLocationConfirmed ? {
            latitude: Number.parseFloat(@json(old('latitude')) || '') || null,
            longitude: Number.parseFloat(@json(old('longitude')) || '') || null,
        } : {
            latitude: null,
            longitude: null,
        };
        let confirmedLocation = { ...restoredConfirmedLocation };
        let checkoutAddressLocationSequence = 0;
        let checkoutDeviceLocation = {
            latitude: null,
            longitude: null,
            accuracy: null,
        };
        let checkoutDeviceLocationRequested = false;
        let checkoutRecentOrderDrift = null;
        let checkoutRecentOrderDriftAcknowledged = false;
        let checkoutDriftPromptShown = false;
        let persistentAddressWarningMessage = '';
        let checkoutEditPickerHydratedAddressId = null;
        let checkoutEditPickerAutoPrimed = false;
        let checkoutEditPickerPendingAddress = null;
        let checkoutEditPickerPendingOptions = {};
        let checkoutNewPickerDraftInitialized = false;
        let checkoutNewPickerAutoPrimed = false;

        function compactAddress(parts) {
            return parts.filter(Boolean).join(', ');
        }

        function splitHouseNumberAndStreet(value) {
            const text = String(value || '').trim();
            if (!text) {
                return { house_number: '', street: '' };
            }

            const match = text.match(/^(?:so\s*)?(\d+[a-zA-Z]?(?:\/\d+[a-zA-Z]?)*)(?:\s+|-|,)+(.*)$/iu);
            if (!match) {
                return { house_number: '', street: text };
            }

            return {
                house_number: match[1] || '',
                street: (match[2] || '').trim() || '',
            };
        }

        function composeAddressLine(houseNumber, street) {
            return [
                String(houseNumber || '').trim(),
                String(street || '').trim(),
            ].filter(Boolean).join(' ');
        }

        function isValidCheckoutPhone(value) {
            const phone = String(value || '').trim();
            return phone !== '' && phone !== 'Chưa cập nhật' && /^0\d{9,10}$/.test(phone);
        }

        function syncAddressPhoneInput(input, button = null, touched = false) {
            if (!input) {
                return false;
            }

            const isValid = isValidCheckoutPhone(input.value);
            const feedback = input.parentElement?.querySelector('[data-phone-feedback]');
            input.classList.toggle('is-invalid', touched && !isValid);
            if (!isValid) {
                input.setCustomValidity('Số điện thoại không đúng.');
                if (feedback) {
                    feedback.textContent = 'Số điện thoại không đúng.';
                }
            } else {
                input.setCustomValidity('');
            }

            if (button) {
                button.disabled = !isValid;
            }

            return isValid;
        }

        function syncCheckoutPhoneState() {
            const phoneValue = String(shippingPhoneInput?.value || '').trim();
            const hasPhone = isValidCheckoutPhone(phoneValue);
            const hasDeliveryAddress = !fulfillmentDeliveryInput?.checked
                || String(shippingAddressInput?.value || '').trim() !== '';

            checkoutContactWarning?.classList.toggle('d-none', hasPhone && hasDeliveryAddress);
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

        function parseCheckoutCoordinate(value) {
            const coordinate = Number.parseFloat(value);
            return Number.isFinite(coordinate) ? coordinate : null;
        }

        function getAddressCoordinates(address) {
            return {
                latitude: parseCheckoutCoordinate(address?.latitude),
                longitude: parseCheckoutCoordinate(address?.longitude),
            };
        }

        function hasCheckoutDeviceLocation() {
            return Number.isFinite(checkoutDeviceLocation.latitude) && Number.isFinite(checkoutDeviceLocation.longitude);
        }

        function isCheckoutPickerVisible(container) {
            if (!container) {
                return false;
            }

            const modal = container.closest('.modal');
            return !modal || modal.classList.contains('show');
        }

        function clearCheckoutRecentOrderDriftNotice() {
            checkoutRecentOrderDrift = null;
            clearPersistentAddressWarning();
        }

        function acknowledgeCheckoutRecentOrderDrift() {
            checkoutRecentOrderDriftAcknowledged = true;
            clearCheckoutRecentOrderDriftNotice();
        }

        function previewCheckoutDeviceLocationOnPicker(container, message) {
            if (!container || !hasCheckoutDeviceLocation() || !isCheckoutPickerVisible(container)) {
                return false;
            }

            window.ChillDrinkLocationPicker?.preview(
                container,
                checkoutDeviceLocation.latitude,
                checkoutDeviceLocation.longitude,
                message || 'Đây là vị trí hiện tại của thiết bị. Hãy xác nhận lại nếu muốn dùng cho đơn hàng.'
            );

            return true;
        }

        function pickerHasSelectedCoordinates(container) {
            if (!container) {
                return false;
            }

            const latitude = Number.parseFloat(container.querySelector('[data-location-lat]')?.value || '');
            const longitude = Number.parseFloat(container.querySelector('[data-location-lng]')?.value || '');

            return Number.isFinite(latitude) && Number.isFinite(longitude);
        }

        function setCheckoutPickerStatus(container, message) {
            const statusEl = container?.querySelector('[data-location-status]');
            if (statusEl && message) {
                statusEl.textContent = message;
            }
        }

        function syncCheckoutDeviceLocationPreview() {
            if (!hasCheckoutDeviceLocation()) {
                return false;
            }

            let synced = false;

            document.querySelectorAll('[data-location-picker="checkout-edit-location-picker"], [data-location-picker="checkout-new-location-picker"]').forEach((container) => {
                const previewMessage = container?.dataset?.checkoutDevicePreviewMessage || '';

                if (!previewMessage || pickerHasSelectedCoordinates(container)) {
                    return;
                }

                if (!isCheckoutPickerVisible(container)) {
                    return;
                }

                if (previewCheckoutDeviceLocationOnPicker(container, previewMessage)) {
                    synced = true;
                }
            });

            return synced;
        }

        function markCheckoutDeviceLocationUnavailable(message = 'Không thể lấy vị trí hiện tại. Hãy chọn trực tiếp trên bản đồ.') {
            document.querySelectorAll('[data-location-picker="checkout-edit-location-picker"], [data-location-picker="checkout-new-location-picker"]').forEach((container) => {
                if (!container?.dataset?.checkoutDevicePreviewMessage || pickerHasSelectedCoordinates(container)) {
                    return;
                }

                if (!isCheckoutPickerVisible(container)) {
                    return;
                }

                setCheckoutPickerStatus(container, message);
            });
        }

        function primeCheckoutDeviceLocationOnPicker(container, message, waitingMessage = 'Đang lấy vị trí hiện tại của thiết bị...') {
            if (!container) {
                return false;
            }

            container.dataset.checkoutDevicePreviewMessage = message || 'Đây là vị trí hiện tại của thiết bị. Hãy xác nhận lại nếu muốn dùng cho đơn hàng.';

            if (!isCheckoutPickerVisible(container)) {
                setCheckoutPickerStatus(container, waitingMessage);
                return false;
            }

            if (pickerHasSelectedCoordinates(container)) {
                return true;
            }

            if (previewCheckoutDeviceLocationOnPicker(container, container.dataset.checkoutDevicePreviewMessage)) {
                return true;
            }

            setCheckoutPickerStatus(container, waitingMessage);
            return false;
        }

        function applyCheckoutPickerSavedCoordinates(container, coordinates, message = 'Đã tải vị trí đã lưu cho địa chỉ này.') {
            if (!container) {
                return false;
            }

            const latitude = Number.parseFloat(coordinates?.latitude);
            const longitude = Number.parseFloat(coordinates?.longitude);

            if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
                return false;
            }

            window.ChillDrinkLocationPicker?.set(container, latitude, longitude, message);
            return true;
        }

        function hydrateEditPickerForAddress(address, options = {}) {
            const picker = document.querySelector('[data-location-picker="checkout-edit-location-picker"]');
            if (!picker) {
                return;
            }

            const addressId = address?.id ?? null;
            const coordinates = getAddressCoordinates(address);
            checkoutEditPickerHydratedAddressId = addressId;
            checkoutEditPickerAutoPrimed = true;

            // Địa chỉ đã lưu thì dùng luôn tọa độ cũ. GPS hiện tại chỉ để
            // tham khảo, không được ghi đè và không bắt người dùng ghim lại.
            if (Number.isFinite(coordinates.latitude) && Number.isFinite(coordinates.longitude)) {
                picker.dataset.checkoutDevicePreviewMessage = '';
                window.ChillDrinkLocationPicker?.set(
                    picker,
                    coordinates.latitude,
                    coordinates.longitude,
                    'Đã tải vị trí đã lưu. Chỉ cần ghim lại nếu bạn muốn đổi địa chỉ.'
                );
                markAddressLocationConfirmed(coordinates.latitude, coordinates.longitude);
                return;
            }

            picker.dataset.checkoutDevicePreviewMessage = '';
            window.ChillDrinkLocationPicker?.clear(
                picker,
                'Địa chỉ này chưa có vị trí đã lưu. Hãy chọn pin một lần để lưu lại.'
            );
        }

        function hydrateNewPickerDraft() {
            const picker = document.querySelector('[data-location-picker="checkout-new-location-picker"]');
            if (!picker) {
                return;
            }

            if (!checkoutNewPickerDraftInitialized) {
                document.getElementById('newAddressName').value = @json($user->name);
                document.getElementById('newAddressPhone').value = @json($checkoutPhoneReady ? $selectedCheckoutPhone : '');
                document.getElementById('newAddressArea').value = '';
                document.getElementById('newAddressHouseNumber').value = '';
                document.getElementById('newAddressStreet').value = '';
                const searchInput = picker.querySelector('[data-location-search-input]');
                if (searchInput) {
                    searchInput.value = '';
                }
                document.getElementById('newAddressDefault').checked = false;
                setTypeActive('new', 'Nhà Riêng');
                syncAddressPhoneInput(newAddressPhone, saveNewAddressButton, !isValidCheckoutPhone(newAddressPhone?.value));
                checkoutNewPickerDraftInitialized = true;
            }

            checkoutNewPickerAutoPrimed = true;
            window.ChillDrinkLocationPicker?.clear(
                picker,
                'Đang chuẩn bị vị trí hiện tại...'
            );
            primeCheckoutDeviceLocationOnPicker(
                picker,
                'Đây là vị trí hiện tại của thiết bị. Hãy xác nhận hoặc chỉnh lại pin trước khi lưu.'
            );
            requestCheckoutDeviceLocation();
        }

        function isCheckoutAddressModalOpen() {
            return ['addressEditModal', 'addressAddModal', 'addressListModal'].some((id) => {
                const element = document.getElementById(id);
                return element?.classList.contains('show');
            });
        }

        function maybeRequireAddressRefreshAgainstSavedAddress() {
            if (!fulfillmentDeliveryInput?.checked || checkoutRecentOrderDriftAcknowledged || !hasCheckoutDeviceLocation()) {
                return false;
            }

            if (!hasConfirmedLocation()) {
                return false;
            }

            const reference = {
                latitude: Number(confirmedLocation.latitude),
                longitude: Number(confirmedLocation.longitude),
            };
            const selectedAddress = getAddressById(selectedAddressId);
            const referenceLabel = compactAddress([
                selectedAddress?.name,
                composeAddressLine(selectedAddress?.house_number, selectedAddress?.street),
                selectedAddress?.area,
            ]) || 'địa chỉ đã lưu';
            const accuracy = Number(checkoutDeviceLocation.accuracy);

            if (!Number.isFinite(reference.latitude) || !Number.isFinite(reference.longitude)) {
                return false;
            }

            if (Number.isFinite(accuracy) && accuracy > checkoutDeviceMaxAccuracyM) {
                return false;
            }

            const distanceMeters = haversineDistanceMeters(
                checkoutDeviceLocation.latitude,
                checkoutDeviceLocation.longitude,
                reference.latitude,
                reference.longitude
            );
            const thresholdMeters = checkoutDeviceDriftRadiusM;

            if (distanceMeters <= thresholdMeters) {
                clearCheckoutRecentOrderDriftNotice();
                return false;
            }

            checkoutRecentOrderDrift = {
                distance_m: distanceMeters,
                threshold_m: thresholdMeters,
                reference_order_code: referenceLabel,
                reference_address_text: referenceLabel,
                placed_at_label: '',
            };

            if (addressLocationConfirmedInput) {
                addressLocationConfirmedInput.value = '';
            }

            const warningMessage = `Vị trí hiện tại lệch ${formatDistanceForCheckout(distanceMeters)} so với ${checkoutRecentOrderDrift.reference_order_code}. Vui lòng cập nhật lại địa chỉ và xác nhận lại vị trí trên bản đồ trước khi đặt đơn.`;
            setPersistentAddressWarning(warningMessage, !checkoutDriftPromptShown);

            if (!checkoutDriftPromptShown) {
                showAddressToast('Phát hiện vị trí hiện tại khác xa địa chỉ đã lưu. Hãy kiểm tra lại địa chỉ giao hàng.', 'error');
                checkoutDriftPromptShown = true;
                if (!isCheckoutAddressModalOpen()) {
                    window.setTimeout(() => openEditModal(selectedAddressId, { triggeredByDeviceDrift: true }), 180);
                }
            }

            return true;
        }

        function requestCheckoutDeviceLocation(force = false) {
            if (!navigator.geolocation || checkoutDeviceLocationRequested || (!force && hasCheckoutDeviceLocation())) {
                return;
            }

            checkoutDeviceLocationRequested = true;
            navigator.geolocation.getCurrentPosition((position) => {
                checkoutDeviceLocationRequested = false;
                checkoutDeviceLocation = {
                    latitude: Number(position.coords.latitude),
                    longitude: Number(position.coords.longitude),
                    accuracy: Number(position.coords.accuracy),
                };

                syncCheckoutDeviceLocationPreview();
                maybeRequireAddressRefreshAgainstSavedAddress();
            }, () => {
                checkoutDeviceLocationRequested = false;
                checkoutDeviceLocation = {
                    latitude: null,
                    longitude: null,
                    accuracy: null,
                };
                markCheckoutDeviceLocationUnavailable();
            }, {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0,
            });
        }

        function cacheAddressCoordinates(addressId, latitude, longitude) {
            if (!addressId) {
                return;
            }

            const lat = parseCheckoutCoordinate(latitude);
            const lng = parseCheckoutCoordinate(longitude);

            if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                return;
            }

            addressBook = addressBook.map((item) => {
                if (item.id !== addressId) {
                    return item;
                }

                return {
                    ...item,
                    latitude: lat,
                    longitude: lng,
                };
            });
        }

        function markAddressLocationConfirmed(latitude, longitude, shouldRenderBranches = true) {
            const lat = Number.parseFloat(latitude);
            const lng = Number.parseFloat(longitude);

            if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                return;
            }

            confirmedLocation = { latitude: lat, longitude: lng };
            if (checkoutLatitudeInput) checkoutLatitudeInput.value = String(lat);
            if (checkoutLongitudeInput) checkoutLongitudeInput.value = String(lng);
            if (addressLocationConfirmedInput) addressLocationConfirmedInput.value = '1';

            if (shouldRenderBranches) {
                renderBranchOptions(lat, lng);
            }
        }

        function clearAddressLocationConfirmation(shouldRenderBranches = true) {
            confirmedLocation = { latitude: null, longitude: null };
            if (checkoutLatitudeInput) checkoutLatitudeInput.value = '';
            if (checkoutLongitudeInput) checkoutLongitudeInput.value = '';
            if (addressLocationConfirmedInput) addressLocationConfirmedInput.value = '';

            if (shouldRenderBranches) {
                renderBranchOptions();
            }
        }

        async function lookupKnownAddress(street, area = '') {
            const query = compactAddress([street, area]).trim();
            if (query.length < 3) {
                return null;
            }

            const url = new URL(addressLookupEndpoint, window.location.origin);
            url.searchParams.set('q', query);
            url.searchParams.set('limit', '1');
            if (hasConfirmedLocation()) {
                url.searchParams.set('latitude', String(confirmedLocation.latitude));
                url.searchParams.set('longitude', String(confirmedLocation.longitude));
            }

            const response = await fetch(url.toString(), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const payload = await response.json().catch(() => ({}));

            if (!response.ok || !Array.isArray(payload?.data) || payload.data.length === 0) {
                return null;
            }

            const match = payload.data[0];
            const latitude = Number.parseFloat(match.latitude);
            const longitude = Number.parseFloat(match.longitude);

            if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
                return null;
            }

            return {
                latitude,
                longitude,
                address: match.full_address || match.name || query,
                canAutofillCoordinates: match.can_autofill_coordinates !== false,
            };
        }

        async function ensureCheckoutLocationForAddress(address) {
            const sequence = ++checkoutAddressLocationSequence;
            const addressId = address?.id ?? null;
            const coordinates = getAddressCoordinates(address);

            if (Number.isFinite(coordinates.latitude) && Number.isFinite(coordinates.longitude)) {
                markAddressLocationConfirmed(coordinates.latitude, coordinates.longitude);
                updateShippingSummary();
                updateBranchSelectorState();
                return true;
            }

            clearAddressLocationConfirmation(false);
            renderBranchOptions();
            updateShippingSummary();
            updateBranchSelectorState();

            const addressLine = composeAddressLine(address?.house_number, address?.street) || String(address?.street || '').trim();
            const area = String(address?.area || '').trim();

            if (!addressLine && !area) {
                return false;
            }

            try {
                const match = await lookupKnownAddress(addressLine, area);

                if (sequence !== checkoutAddressLocationSequence || selectedAddressId !== addressId) {
                    return false;
                }

                const latitude = parseCheckoutCoordinate(match?.latitude);
                const longitude = parseCheckoutCoordinate(match?.longitude);

                if (!Number.isFinite(latitude) || !Number.isFinite(longitude) || match?.canAutofillCoordinates === false) {
                    return false;
                }

                cacheAddressCoordinates(addressId, latitude, longitude);
                markAddressLocationConfirmed(latitude, longitude);
                updateShippingSummary();
                updateBranchSelectorState();
                return true;
            } catch (error) {
                console.error('Auto checkout location lookup failed:', error);
                return false;
            }
        }

        window.updateBranchSelectorState = function updateBranchSelectorState() {
            const branchSelect = document.getElementById('branch_id');
            const isPickup = document.getElementById('deliveryTypePickup')?.checked === true;

            if (!branchSelect) {
                return;
            }

            if (isGroupCheckout) {
                branchSelect.disabled = false;
                branchSelect.required = true;
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

        let deliveryQuoteSequence = 0;

        window.updateShippingSummary = async function updateShippingSummary() {
            const sequence = ++deliveryQuoteSequence;
            const methodInput = document.querySelector('input[name="shipping_method_ui"]:checked')
                || document.querySelector('input[name="shipping_method_ui"]');

            if (!methodInput) {
                return;
            }

            const branchSelect = document.getElementById('branch_id');
            const selectedOption = branchSelect?.options[branchSelect.selectedIndex];
            const isPickup = document.getElementById('deliveryTypePickup')?.checked === true;

            if (isPickup) {
                const shippingFee = 0;
                shippingConfig.fixedShippingFee = 0;
                if (pendingVouchers?.shipping?.code) {
                    pendingVouchers.shipping = voucherState(null);
                    selectedShippingVoucherCode.value = '';
                    shippingConfig.discount = Number(pendingVouchers?.discount?.discount || 0);
                    if (summaryVoucherText) {
                        summaryVoucherText.textContent = shippingConfig.discount > 0 ? `-${formatVnd(shippingConfig.discount)}` : (selectedVoucherCode?.value ? 'Đã chọn mã giảm giá' : 'Chưa áp dụng');
                    }
                }
                const grandTotal = Math.max(0, shippingConfig.subtotal - Number(shippingConfig.discount || 0));
                if (shippingDistanceLabel) shippingDistanceLabel.textContent = 'Tự nhận';
                if (shippingEstimateDetail) shippingEstimateDetail.textContent = `Nhận tại cửa hàng · ${selectedOption?.value ? selectedOption.textContent.split(' — ')[0] : 'Chưa chọn chi nhánh'}`;
                if (shippingInlineFee) shippingInlineFee.textContent = formatVnd(0);
                if (shippingEta) shippingEta.textContent = '';
                summaryShippingFee.textContent = formatVnd(0);
                summaryShippingDistance.textContent = 'Tự nhận tại chi nhánh';
                summaryGrandTotal.textContent = formatVnd(grandTotal);
                if (typeof refreshVoucherCards === 'function') refreshVoucherCards();
                return;
            }

            const userLat = Number(confirmedLocation.latitude);
            const userLon = Number(confirmedLocation.longitude);
            const branchId = selectedOption?.value || '';

            if (!Number.isFinite(userLat) || !Number.isFinite(userLon) || !branchId) {
                const fallbackFee = Number(shippingConfig.fixedShippingFee || 0);
                if (fallbackFee <= 0 && pendingVouchers?.shipping?.code) {
                    pendingVouchers.shipping = voucherState(null);
                    selectedShippingVoucherCode.value = '';
                    shippingConfig.discount = Number(pendingVouchers?.discount?.discount || 0);
                    if (summaryVoucherText) {
                        summaryVoucherText.textContent = shippingConfig.discount > 0 ? `-${formatVnd(shippingConfig.discount)}` : (selectedVoucherCode?.value ? 'Đã chọn mã giảm giá' : 'Chưa áp dụng');
                    }
                }
                if (shippingDistanceLabel) shippingDistanceLabel.textContent = 'Chờ địa chỉ';
                if (shippingEstimateDetail) shippingEstimateDetail.textContent = 'Ước tính · Vui lòng chọn địa chỉ và chi nhánh';
                if (shippingInlineFee) shippingInlineFee.textContent = formatVnd(fallbackFee);
                if (shippingEta) shippingEta.textContent = '';
                summaryShippingFee.textContent = formatVnd(fallbackFee);
                summaryShippingDistance.textContent = 'Chưa có lộ trình đường bộ';
                summaryGrandTotal.textContent = formatVnd(Math.max(0, shippingConfig.subtotal + fallbackFee - Number(shippingConfig.discount || 0)));
                if (typeof refreshVoucherCards === 'function') refreshVoucherCards();
                return;
            }

            if (shippingDistanceLabel) shippingDistanceLabel.textContent = 'Đang tính tuyến...';
            if (shippingEstimateDetail) shippingEstimateDetail.textContent = 'Đang lấy quãng đường thực tế';

            try {
                const url = new URL(deliveryQuoteEndpoint, window.location.origin);
                url.searchParams.set('branch_id', branchId);
                url.searchParams.set('latitude', String(userLat));
                url.searchParams.set('longitude', String(userLon));
                url.searchParams.set('method', methodInput.value || 'standard');
                url.searchParams.set('cup_count', String(Math.max(1, Number(shippingConfig.cupCount || 1))));

                const response = await fetch(url, { headers: { 'Accept': 'application/json' }, cache: 'no-store' });
                const payload = await response.json();
                if (sequence !== deliveryQuoteSequence) return;
                if (!response.ok || !payload.success) throw new Error(payload.message || 'Không tính được tuyến giao hàng.');

                const distance = Number(payload.distance_km);
                const shippingFee = Number(payload.shipping?.total_fee || 0);
                shippingConfig.fixedShippingFee = shippingFee;
                if (shippingFee <= 0 && pendingVouchers?.shipping?.code) {
                    pendingVouchers.shipping = voucherState(null);
                    selectedShippingVoucherCode.value = '';
                }
                shippingConfig.discount = Number(pendingVouchers?.discount?.discount || 0) + Math.min(shippingFee, Number(pendingVouchers?.shipping?.discount || 0));
                if (summaryVoucherText) {
                    summaryVoucherText.textContent = shippingConfig.discount > 0 ? `-${formatVnd(shippingConfig.discount)}` : 'Chưa áp dụng';
                }
                const durationSeconds = Number(payload.duration_s || 0);
                const routeNote = payload.route_fallback ? ' · tuyến tạm tính' : ' · theo đường thực tế';

                if (!payload.inside_service_radius) {
                    if (shippingDistanceLabel) shippingDistanceLabel.textContent = `${distance.toFixed(1)} km`;
                    if (shippingEstimateDetail) shippingEstimateDetail.textContent = `Ngoài phạm vi · tối đa ${Number(payload.max_distance_km || maxOrderDistanceKm).toFixed(0)} km`;
                    if (shippingInlineFee) shippingInlineFee.textContent = '--';
                    if (shippingEta) shippingEta.textContent = '';
                    summaryShippingFee.textContent = '--';
                    summaryShippingDistance.textContent = `Khoảng cách đường bộ: ${distance.toFixed(1)} km`;
                    summaryGrandTotal.textContent = formatVnd(Math.max(0, shippingConfig.subtotal - Number(shippingConfig.discount || 0)));
                    if (typeof refreshVoucherCards === 'function') refreshVoucherCards();
                    return;
                }

                const grandTotal = Math.max(0, shippingConfig.subtotal + shippingFee - Number(shippingConfig.discount || 0));
                if (shippingDistanceLabel) shippingDistanceLabel.textContent = `${distance.toFixed(1)} km`;
                if (shippingEstimateDetail) {
                    const tierLabel = payload.shipping?.cup_tier_label ? ` · ${payload.shipping.cup_tier_label}` : '';
                    const rateLabel = Number(payload.shipping?.rate_per_km || 0) > 0 ? ` · ${formatVnd(payload.shipping.rate_per_km)}/km` : '';
                    shippingEstimateDetail.textContent = `${payload.shipping?.distance_label || 'Giao hàng'}${tierLabel}${rateLabel}${routeNote}`;
                }
                if (shippingInlineFee) shippingInlineFee.textContent = formatVnd(shippingFee);
                if (shippingEta) shippingEta.textContent = durationSeconds > 0 ? `Di chuyển khoảng ${Math.max(1, Math.round(durationSeconds / 60))} phút` : '';
                summaryShippingFee.textContent = formatVnd(shippingFee);
                summaryShippingDistance.textContent = `Khoảng cách đường bộ: ${distance.toFixed(1)} km`;
                summaryGrandTotal.textContent = formatVnd(grandTotal);

                if (selectedOption) {
                    selectedOption.dataset.distance = String(distance);
                }
                if (typeof refreshVoucherCards === 'function') refreshVoucherCards();
            } catch (error) {
                if (sequence !== deliveryQuoteSequence) return;
                if (shippingDistanceLabel) shippingDistanceLabel.textContent = 'Chưa tính được';
                if (shippingEstimateDetail) shippingEstimateDetail.textContent = error.message || 'Không thể lấy tuyến đường lúc này';
                if (shippingEta) shippingEta.textContent = '';
                if (typeof refreshVoucherCards === 'function') refreshVoucherCards();
            }
        };

        let branchOptionsSequence = 0;
        async function renderBranchOptions(userLat = null, userLon = null) {
            const sequence = ++branchOptionsSequence;
            const branchSelect = document.getElementById('branch_id');

            if (!branchSelect) {
                return;
            }

            if (isGroupCheckout) {
                branchSelect.disabled = false;
                branchSelect.required = true;
                window.updateShippingSummary?.();
                syncCheckoutBranchWithHeader(branchSelect);
                void refreshCheckoutAvailability();
                return;
            }

            const lat = Number.parseFloat(userLat);
            const lon = Number.parseFloat(userLon);
            const hasValidCoords = Number.isFinite(lat) && Number.isFinite(lon);

            if (hasValidCoords) {
                branchSelect.dataset.userLatitude = String(lat);
                branchSelect.dataset.userLongitude = String(lon);
            } else {
                branchSelect.dataset.userLatitude = '';
                branchSelect.dataset.userLongitude = '';
            }

            const currentValue = branchSelect.disabled ? '' : (branchSelect.value || @json(old('branch_id', '')));
            const localBranches = JSON.parse(branchSelect.dataset.branches || '[]');

            const writeOptions = (branches, roadMode = false) => {
                branchSelect.innerHTML = '<option value="">Chọn chi nhánh</option>';

                if (hasValidCoords && branches.length === 0) {
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
                    const roadDistance = branch.distance_km ?? branch.distance ?? null;
                    option.dataset.distance = roadDistance !== null ? Number(roadDistance).toFixed(2) : '';

                    let label = branch.name || 'Chi nhánh';
                    if (branch.address) label += ' — ' + branch.address;
                    if (roadDistance !== null && Number.isFinite(Number(roadDistance))) {
                        label += ` — ${Number(roadDistance).toFixed(1)} km${roadMode ? ' đường bộ' : ''}`;
                    }
                    option.textContent = label;
                    branchSelect.appendChild(option);
                });

                if (currentValue && Array.from(branchSelect.options).some((option) => option.value === String(currentValue))) {
                    branchSelect.value = String(currentValue);
                } else if (!branchSelect.value && branches.length === 1) {
                    branchSelect.value = String(branches[0].id);
                }
            };

            if (!hasValidCoords) {
                writeOptions(localBranches, false);
                if (branchSelectNote) {
                    branchSelectNote.classList.remove('d-none');
                    branchSelectNote.textContent = 'Vui lòng xác định vị trí giao hàng để tính quãng đường đường bộ.';
                }
                window.updateShippingSummary?.();
                syncCheckoutBranchWithHeader(branchSelect);
                void refreshCheckoutAvailability();
                return;
            }

            branchSelect.innerHTML = '<option value="">Đang tính tuyến đường...</option>';
            branchSelect.disabled = true;

            try {
                const url = new URL(branchesListEndpoint, window.location.origin);
                url.searchParams.set('latitude', String(lat));
                url.searchParams.set('longitude', String(lon));
                const response = await fetch(url, { headers: { 'Accept': 'application/json' }, cache: 'no-store' });
                const payload = await response.json();
                if (!response.ok || !payload.success) throw new Error(payload.message || 'Không tải được chi nhánh.');
                if (sequence !== branchOptionsSequence) return;

                writeOptions(Array.isArray(payload.data) ? payload.data : [], true);
                if (branchSelectNote) {
                    branchSelectNote.classList.remove('d-none');
                    branchSelectNote.textContent = 'Chỉ hiển thị chi nhánh cách địa chỉ giao hàng không quá 15 km theo lộ trình đường bộ.';
                }
            } catch (error) {
                if (sequence !== branchOptionsSequence) return;
                // UI fallback chỉ để vẫn chọn được chi nhánh; backend vẫn kiểm tra road-route khi đặt đơn.
                writeOptions(localBranches, false);
                if (branchSelectNote) {
                    branchSelectNote.classList.remove('d-none');
                    branchSelectNote.textContent = 'Chưa tải được tuyến đường. Hệ thống sẽ kiểm tra lại chính xác khi đặt đơn.';
                }
            } finally {
                if (sequence === branchOptionsSequence) {
                    branchSelect.disabled = false;
                    window.updateShippingSummary?.();
                    syncCheckoutBranchWithHeader(branchSelect);
                    void refreshCheckoutAvailability();
                }
            }
        }

        window.renderCheckoutBranchOptions = renderBranchOptions;
        window.refreshCheckoutBranches = async (branches) => {
            const select = document.getElementById('branch_id');
            if (!select || isGroupCheckout) return;
            const previous = select.value;
            select.dataset.branches = JSON.stringify(Array.isArray(branches) ? branches : []);
            await renderBranchOptions(select.dataset.userLatitude, select.dataset.userLongitude);
            if (previous !== select.value) select.dispatchEvent(new Event('change', { bubbles: true }));
        };

        document.querySelector('[data-find-nearest-branch]')?.addEventListener('click', function () {
            const button = this;
            const branchSelect = document.getElementById('branch_id');

            if (!branchSelect) {
                return;
            }

            const originalText = button.innerHTML;
            const selectNearestBranch = async (latitude, longitude) => {
                await renderBranchOptions(latitude, longitude);

                // API đã sắp xếp theo quãng đường đường bộ tăng dần, nên
                // chi nhánh hợp lệ đầu tiên chính là chi nhánh gần nhất.
                const nearestBranch = Array.from(branchSelect.options).find((option) => (
                    option.value && !option.disabled
                ));

                if (!nearestBranch) {
                    throw new Error('Không tìm thấy chi nhánh phù hợp trong phạm vi phục vụ.');
                }

                branchSelect.value = nearestBranch.value;
                await window.updateShippingSummary?.();
                syncCheckoutBranchWithHeader(branchSelect);
                void refreshCheckoutAvailability();

                return nearestBranch;
            };

            const runNearestBranchSearch = async (latitude, longitude) => {
                button.disabled = true;
                button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Đang tìm';

                try {
                    const nearestBranch = await selectNearestBranch(latitude, longitude);
                    const branchName = nearestBranch.textContent.split(' — ')[0].trim();
                    showAddressToast(`Đã tự chọn chi nhánh gần nhất: ${branchName}.`);
                } catch (error) {
                    showAddressToast(error.message || 'Không tìm thấy chi nhánh phù hợp.', 'error');
                } finally {
                    button.disabled = false;
                    button.innerHTML = originalText;
                }
            };

            if (hasConfirmedLocation()) {
                void runNearestBranchSearch(confirmedLocation.latitude, confirmedLocation.longitude);
                return;
            }

            if (!navigator.geolocation) {
                showAddressToast('Trình duyệt không hỗ trợ lấy vị trí. Vui lòng cập nhật địa chỉ nhận hàng.', 'error');
                return;
            }

            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Đang lấy vị trí';

            navigator.geolocation.getCurrentPosition((position) => {
                const latitude = position.coords.latitude;
                const longitude = position.coords.longitude;
                // Chỉ dùng vị trí thiết bị để gợi ý chi nhánh; không ghi đè
                // tọa độ giao hàng nếu người dùng chưa xác nhận địa chỉ này.
                void runNearestBranchSearch(latitude, longitude);
            }, () => {
                showAddressToast('Không thể lấy vị trí hiện tại. Vui lòng cho phép quyền vị trí hoặc chọn chi nhánh thủ công.', 'error');
                button.disabled = false;
                button.innerHTML = originalText;
            }, {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 60000,
            });
        });

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
            } else {
                clearAddressLocationConfirmation();
            }

            updateBranchSelectorState();
        }

        function getAddressById(id) {
            return addressBook.find((item) => item.id === id) || addressBook[0] || null;
        }

        function applyAddress(address, options = {}) {
            if (!address) {
                return;
            }

            const preserveCurrentLocation = options.preserveCurrentLocation === true;
            selectedAddressId = address.id;
            selectedReceiver.textContent = address.name || 'Chưa cập nhật';
            const addressHasPhone = isValidCheckoutPhone(address.phone);
            selectedPhone.textContent = addressHasPhone ? address.phone : '';
            selectedPhoneDivider?.classList.toggle('d-none', !addressHasPhone);
            const addressLine = composeAddressLine(address.house_number, address.street);
            selectedAddressText.textContent = compactAddress([addressLine, address.area]) || 'Chưa có địa chỉ. Bấm Thay đổi để thêm địa chỉ nhận hàng.';
            selectedDefaultBadge.classList.toggle('d-none', !address.isDefault);
            shippingAddressInput.value = addressLine || address.street || '';
            shippingAreaInput.value = address.area || '';
            clearAddressHouseNumberWarning();
            syncAddressHouseNumberNotice();
            if (shippingPhoneInput) {
                shippingPhoneInput.value = address.phone || '';
            }
            renderAddressList();

            if (preserveCurrentLocation && hasConfirmedLocation()) {
                renderBranchOptions(confirmedLocation.latitude, confirmedLocation.longitude);
            } else {
                void ensureCheckoutLocationForAddress(address);
            }

            updateShippingSummary();
            syncCheckoutPhoneState();
            updateBranchSelectorState();
            maybeRequireAddressRefreshAgainstSavedAddress();
        }

        function renderAddressList() {
            if (!addressList) {
                return;
            }

            const rows = addressBook.map((address) => {
                const isActive = address.id === selectedAddressId;
                const fullAddress = compactAddress([
                    composeAddressLine(address.house_number, address.street),
                    address.area,
                ]) || 'Chưa có địa chỉ cụ thể';
                const phoneText = isValidCheckoutPhone(address.phone) ? address.phone : '';

                return `
                    <div class="address-choice-row" data-address-row="${address.id}">
                        <button type="button" class="address-radio ${isActive ? 'active' : ''}" data-select-address="${address.id}" aria-label="Chọn địa chỉ"></button>
                        <div class="flex-grow-1">
                            <div class="address-person mb-1">
                                <span>${escapeHtml(address.name || 'Chưa cập nhật')}</span>
                                ${phoneText ? '<span class="address-phone-divider"></span><span class="fw-semibold text-secondary">' + escapeHtml(phoneText) + '</span>' : ''}
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
            if (!address) {
                return;
            }

            document.getElementById('editAddressName').value = address.name || '';
            document.getElementById('editAddressPhone').value = isValidCheckoutPhone(address.phone) ? address.phone : '';
            document.getElementById('editAddressArea').value = address.area || '';
            document.getElementById('editAddressHouseNumber').value = address.house_number || '';
            document.getElementById('editAddressStreet').value = address.street || '';
            const searchInput = document.querySelector('[data-location-picker="checkout-edit-location-picker"] [data-location-search-input]');
            if (searchInput) {
                searchInput.value = '';
            }
            document.getElementById('editAddressDefault').checked = !!address.isDefault;
            setTypeActive('edit', address.type || 'Nhà Riêng');
            syncAddressPhoneInput(editAddressPhone, saveEditedAddressButton, !isValidCheckoutPhone(address.phone));
        }

        function openEditModal(id = selectedAddressId, options = {}) {
            const address = getAddressById(id);
            if (!address) {
                showAddressToast('Không tìm thấy địa chỉ để cập nhật.', 'error');
                return;
            }

            fillEditModal(address);
            selectedAddressId = address.id;
            const savedCoordinates = getAddressCoordinates(address);
            if (Number.isFinite(savedCoordinates.latitude) && Number.isFinite(savedCoordinates.longitude)) {
                // Mở rồi đóng popup cũng không được làm mất tọa độ đã lưu.
                markAddressLocationConfirmed(savedCoordinates.latitude, savedCoordinates.longitude);
            } else {
                clearAddressLocationConfirmation();
            }
            checkoutEditPickerHydratedAddressId = null;
            checkoutEditPickerAutoPrimed = false;
            checkoutEditPickerPendingAddress = address;
            checkoutEditPickerPendingOptions = { ...options };
            addressEditModalElement.dataset.checkoutAddressId = String(address.id || id);
            window.ChillDrinkLocationPicker?.clear(
                document.querySelector('[data-location-picker="checkout-edit-location-picker"]'),
                'Đang chuẩn bị vị trí hiện tại...'
            );
            updateBranchSelectorState();
            addressListModal.hide();
            addressEditModal.show();
        }

        function openAddModal() {
            clearAddressLocationConfirmation();
            checkoutNewPickerDraftInitialized = false;
            checkoutNewPickerAutoPrimed = false;
            window.ChillDrinkLocationPicker?.clear(
                document.querySelector('[data-location-picker="checkout-new-location-picker"]'),
                'Đang chuẩn bị vị trí hiện tại...'
            );
            updateBranchSelectorState();
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

        function bindVoucherCard(card) {
            if (!card || card.dataset.voucherBound === '1') {
                return;
            }

            card.dataset.voucherBound = '1';
            card.setAttribute('tabindex', '0');
            card.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    setVoucherActive(card);
                }
            });
        }

        function bindVoucherCards(root = document) {
            root.querySelectorAll('[data-voucher-card]').forEach(bindVoucherCard);
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
            selectedVoucherText.textContent = labels.length ? `Đã chọn: ${labels.join(' + ')}` : 'Chưa chọn phiếu ưu đãi';
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

            if (voucherCard && !event.target.closest('a') && voucherCard.dataset.voucherDisabled !== '1') {
                event.preventDefault();
                setVoucherActive(voucherCard);
            }

        });

        addressEditModalElement?.addEventListener('show.bs.modal', (event) => {
            const triggerId = event.relatedTarget?.dataset?.addressId
                || addressEditModalElement.dataset.checkoutAddressId
                || selectedAddressId;
            const address = getAddressById(triggerId);

            if (!address) {
                return;
            }

            fillEditModal(address);
            selectedAddressId = address.id;
            checkoutEditPickerHydratedAddressId = null;
            checkoutEditPickerAutoPrimed = false;
            checkoutEditPickerPendingAddress = address;
            checkoutEditPickerPendingOptions = {};
            updateBranchSelectorState();
        });

        addressEditModalElement?.addEventListener('shown.bs.modal', () => {
            const address = checkoutEditPickerPendingAddress || getAddressById(selectedAddressId);
            hydrateEditPickerForAddress(address, checkoutEditPickerPendingOptions || {});
            checkoutEditPickerPendingAddress = null;
            checkoutEditPickerPendingOptions = {};
            window.ChillDrinkLocationPicker?.refresh(addressEditModalElement);
            syncCheckoutDeviceLocationPreview();
        });

        addressAddModalElement?.addEventListener('shown.bs.modal', () => {
            hydrateNewPickerDraft();
            window.ChillDrinkLocationPicker?.refresh(addressAddModalElement);
            syncCheckoutDeviceLocationPreview();
        });

        editAddressPhone?.addEventListener('input', () => syncAddressPhoneInput(editAddressPhone, saveEditedAddressButton, true));
        editAddressPhone?.addEventListener('blur', () => syncAddressPhoneInput(editAddressPhone, saveEditedAddressButton, true));
        newAddressPhone?.addEventListener('input', () => syncAddressPhoneInput(newAddressPhone, saveNewAddressButton, true));
        newAddressPhone?.addEventListener('blur', () => syncAddressPhoneInput(newAddressPhone, saveNewAddressButton, true));

        [
            ['edit', 'editAddressArea', 'editAddressHouseNumber', 'editAddressStreet'],
            ['new', 'newAddressArea', 'newAddressHouseNumber', 'newAddressStreet'],
        ].forEach(([scope, ...fieldIds]) => {
            fieldIds.forEach((fieldId) => {
                document.getElementById(fieldId)?.addEventListener('input', (event) => {
                    if (!event.isTrusted) {
                        return;
                    }

                    if (scope === 'edit') {
                        // Sửa tên đường/số nhà không làm mất tọa độ đã lưu.
                        // Người dùng chỉ đổi tọa độ khi chủ động chọn pin/GPS mới.
                        return;
                    }

                    const picker = document.querySelector(`[data-location-picker="checkout-${scope}-location-picker"]`);
                    window.ChillDrinkLocationPicker?.invalidate(picker, 'Địa chỉ vừa thay đổi. Vui lòng kiểm tra lại vị trí trên bản đồ trước khi lưu.');
                    clearAddressLocationConfirmation();
                });
            });
        });

        shippingAddressInput?.addEventListener('input', () => {
            clearAddressHouseNumberWarning();
            syncAddressHouseNumberNotice();
            clearAddressLocationConfirmation();
        });

        shippingAddressInput?.addEventListener('blur', () => {
            syncAddressHouseNumberNotice();
        });

        noteInput?.addEventListener('input', () => {
            if (String(noteInput.value || '').trim()) {
                clearAddressHouseNumberWarning();
                hideAddressHouseNumberWarning();
            }
        });

        placeOrderButton?.closest('form')?.addEventListener('submit', function (event) {
            if (unavailableCheckoutProducts.size > 0) {
                event.preventDefault();
                const warning = document.getElementById('checkoutAvailabilityWarning');
                warning?.classList.remove('d-none');
                warning?.focus({ preventScroll: true });
                warning?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }

            const hasPhone = isValidCheckoutPhone(shippingPhoneInput?.value || '');
            const missingDeliveryAddress = Boolean(
                fulfillmentDeliveryInput?.checked
                && !String(shippingAddressInput?.value || '').trim()
            );

            if (!hasPhone || missingDeliveryAddress) {
                event.preventDefault();
                checkoutContactWarning?.classList.remove('d-none');
                if (checkoutContactWarning) {
                    checkoutContactWarning.innerHTML = `<i class="bi bi-exclamation-circle me-1"></i>${missingDeliveryAddress
                        ? 'Vui lòng thêm địa chỉ nhận hàng và số điện thoại để tiếp tục đặt đơn nhóm.'
                        : 'Vui lòng cập nhật số điện thoại hợp lệ để tiếp tục đặt đơn nhóm.'}`;
                    checkoutContactWarning.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                window.setTimeout(() => openEditModal(), 250);
                return;
            }

            if (fulfillmentDeliveryInput?.checked && addressLocationConfirmedInput?.value !== '1') {
                event.preventDefault();
                if (checkoutRecentOrderDrift) {
                    setPersistentAddressWarning(persistentAddressWarningMessage || 'Vui lòng cập nhật lại địa chỉ vì vị trí hiện tại đang khác xa địa chỉ đã lưu.', true);
                    openEditModal(selectedAddressId, { triggeredByDeviceDrift: true });
                } else {
                    showAddressHouseNumberWarning('Vui lòng cập nhật địa chỉ và xác nhận lại vị trí trên bản đồ cho đơn hàng này.', true);
                    addressListModal.show();
                }
            }

            clearAddressHouseNumberWarning();
        });

        document.getElementById('saveEditedAddress')?.addEventListener('click', async function () {
            const address = getAddressById(selectedAddressId);
            const name = document.getElementById('editAddressName').value.trim();
            const phone = document.getElementById('editAddressPhone').value.trim();
            const area = document.getElementById('editAddressArea').value.trim();
            const houseNumber = document.getElementById('editAddressHouseNumber').value.trim();
            const street = document.getElementById('editAddressStreet').value.trim();
            let resolvedLocation = getPickerCoordinates('edit');
            if (
                (!Number.isFinite(resolvedLocation?.latitude) || !Number.isFinite(resolvedLocation?.longitude))
                && address
            ) {
                // Dự phòng cho trường hợp bản đồ chưa mount xong: giữ tọa độ
                // của địa chỉ đã lưu thay vì bắt người dùng ghim lại.
                resolvedLocation = getAddressCoordinates(address);
            }
            const button = this;
            const originalText = button.innerHTML;
            let saved = false;

            if (!Number.isFinite(resolvedLocation?.latitude) || !Number.isFinite(resolvedLocation?.longitude)) {
                showAddressToast('Vui lòng chọn vị trí trên bản đồ, tìm địa chỉ hoặc lấy vị trí hiện tại.', 'error');
                return;
            }

            if (!syncAddressPhoneInput(editAddressPhone, saveEditedAddressButton, true)) {
                editAddressPhone?.focus();
                return;
            }

            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Đang lưu...';

            const payload = {
                name,
                phone,
                area,
                house_number: houseNumber,
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

                if (response.ok && data?.success !== false) {
                    try {
                        acknowledgeCheckoutRecentOrderDrift();
                        syncAddressBook(data);
                    } catch (syncError) {
                        console.error(syncError);
                    }
                    saved = true;
                    showAddressToast(data?.message || 'Đã lưu địa chỉ.');
                } else {
                    const errorMessage = Object.values(data?.errors || {})?.flat()?.[0] || data?.message || 'Không thể lưu địa chỉ.';
                    if (data?.errors?.phone) {
                        syncAddressPhoneInput(editAddressPhone, saveEditedAddressButton, true);
                        editAddressPhone?.focus();
                    }
                    showAddressToast(errorMessage, 'error');
                    console.error(errorMessage);
                    return;
                }
            } catch (error) {
                showAddressToast(error?.message || 'Không thể lưu địa chỉ lúc này.', 'error');
                console.error(error);
                return;
            } finally {
                button.disabled = false;
                button.innerHTML = originalText;
                if (saved) {
                    addressEditModal.hide();
                }
            }
        });

        document.getElementById('saveNewAddress')?.addEventListener('click', async function () {
            const name = document.getElementById('newAddressName').value.trim();
            const phone = document.getElementById('newAddressPhone').value.trim();
            const area = document.getElementById('newAddressArea').value.trim();
            const houseNumber = document.getElementById('newAddressHouseNumber').value.trim();
            const street = document.getElementById('newAddressStreet').value.trim();
            let resolvedLocation = getPickerCoordinates('new');
            const button = this;
            const originalText = button.innerHTML;
            let saved = false;

            if (!Number.isFinite(resolvedLocation?.latitude) || !Number.isFinite(resolvedLocation?.longitude)) {
                showAddressToast('Vui lòng chọn vị trí trên bản đồ, tìm địa chỉ hoặc lấy vị trí hiện tại.', 'error');
                return;
            }

            if (!syncAddressPhoneInput(newAddressPhone, saveNewAddressButton, true)) {
                newAddressPhone?.focus();
                return;
            }

            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Đang lưu...';

            const payload = {
                name,
                phone,
                area,
                house_number: houseNumber,
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

                if (response.ok && data?.success !== false) {
                    try {
                        acknowledgeCheckoutRecentOrderDrift();
                        syncAddressBook(data);
                    } catch (syncError) {
                        console.error(syncError);
                    }
                    saved = true;
                    showAddressToast(data?.message || 'Đã lưu địa chỉ mới.');
                } else {
                    const errorMessage = Object.values(data?.errors || {})?.flat()?.[0] || data?.message || 'Không thể lưu địa chỉ mới.';
                    if (data?.errors?.phone) {
                        syncAddressPhoneInput(newAddressPhone, saveNewAddressButton, true);
                        newAddressPhone?.focus();
                    }
                    showAddressToast(errorMessage, 'error');
                    console.error(errorMessage);
                    return;
                }
            } catch (error) {
                showAddressToast(error?.message || 'Không thể lưu địa chỉ mới lúc này.', 'error');
                console.error(error);
                return;
            } finally {
                button.disabled = false;
                button.innerHTML = originalText;
                if (saved) {
                    addressAddModal.hide();
                }
            }
        });

        const voucherManualApply = document.getElementById('voucherManualApply');
        const voucherCodeFeedback = document.getElementById('voucherCodeFeedback');
        const voucherCodePattern = /^[A-Z0-9_-]+$/;

        function setVoucherCodeFeedback(message = '', type = 'error') {
            if (!voucherCodeFeedback) return;
            voucherCodeFeedback.textContent = message;
            voucherCodeFeedback.classList.toggle('d-none', !message);
            voucherCodeFeedback.classList.toggle('text-danger', Boolean(message) && type === 'error');
            voucherCodeFeedback.classList.toggle('text-success', Boolean(message) && type === 'success');
            voucherCodeInput?.classList.toggle('is-invalid', Boolean(message) && type === 'error');
            voucherCodeInput?.classList.toggle('is-valid', Boolean(message) && type === 'success');
        }

        voucherCodeInput?.addEventListener('input', () => {
            const normalized = voucherCodeInput.value.toUpperCase().replace(/[^A-Z0-9_-]/g, '').slice(0, 50);
            if (voucherCodeInput.value !== normalized) voucherCodeInput.value = normalized;
            setVoucherCodeFeedback();
        });

        voucherCodeInput?.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                voucherManualApply?.click();
            }
        });

        voucherManualApply?.addEventListener('click', async function () {
            const code = voucherCodeInput.value.trim().toUpperCase();

            if (!code) {
                setVoucherCodeFeedback('Vui lòng nhập mã voucher.');
                voucherCodeInput.focus();
                return;
            }

            if (!voucherCodePattern.test(code)) {
                setVoucherCodeFeedback('Mã chỉ được gồm chữ, số, dấu gạch ngang hoặc gạch dưới.');
                voucherCodeInput.focus();
                return;
            }

            this.disabled = true;
            setVoucherCodeFeedback('Đang kiểm tra mã...', 'success');

            try {
                const response = await fetch(@json(route('checkout.voucher.validate')), {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        code,
                        fulfillment_type: document.querySelector('input[name="fulfillment_type"]:checked')?.value || 'delivery',
                        shipping_fee: Math.max(0, Math.round(Number(shippingConfig.fixedShippingFee || 0))),
                    }),
                });
                const data = await response.json().catch(() => ({}));

                if (!response.ok || !data.valid) {
                    const validationMessage = Object.values(data.errors || {}).flat()[0];
                    const errorMessage = response.status === 429
                        ? 'Bạn đã thử quá nhiều mã. Vui lòng chờ một phút rồi thử lại.'
                        : (validationMessage || data.message || 'Mã voucher không hợp lệ.');
                    throw new Error(errorMessage);
                }

                const voucher = data.voucher || {};
                const matchedCard = Array.from(document.querySelectorAll('[data-voucher-code]'))
                    .find((item) => item.dataset.voucherCode === voucher.code);

                if (matchedCard && matchedCard.dataset.voucherDisabled !== '1') {
                    setVoucherActive(matchedCard);
                } else {
                    const type = voucher.type === 'shipping' ? 'shipping' : 'discount';
                    document.querySelectorAll(`[data-voucher-card][data-voucher-type="${type}"]`).forEach((item) => {
                        item.classList.remove('active');
                        item.querySelector('.voucher-radio')?.classList.remove('active');
                        item.querySelector('.voucher-radio')?.setAttribute('aria-pressed', 'false');
                    });
                    pendingVouchers[type] = {
                        code: voucher.code,
                        label: voucher.label,
                        discount: Number(voucher.discount || 0),
                    };
                }

                voucherCodeInput.value = voucher.code;
                setVoucherCodeFeedback(data.message || 'Mã voucher hợp lệ.', 'success');
                commitVoucherSelection();
            } catch (error) {
                setVoucherCodeFeedback(error.message || 'Không thể kiểm tra mã voucher lúc này.');
                voucherCodeInput.focus();
            } finally {
                this.disabled = false;
            }
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

        document.getElementById('branch_id')?.addEventListener('change', (event) => {
            window.updateShippingSummary?.();
            syncCheckoutBranchWithHeader(event.currentTarget);
            void refreshCheckoutAvailability();
        });

        bindVoucherCards(document);
        if (typeof refreshVoucherCards === 'function') {
            refreshVoucherCards();
        }
        renderAddressList();
        const initialAddress = getAddressById(selectedAddressId);
        if (initialAddress) {
            applyAddress(initialAddress, {
                preserveCurrentLocation: restoredLocationConfirmed
                    && Number.isFinite(restoredConfirmedLocation.latitude)
                    && Number.isFinite(restoredConfirmedLocation.longitude),
            });
        } else if (
            restoredLocationConfirmed
            && Number.isFinite(restoredConfirmedLocation.latitude)
            && Number.isFinite(restoredConfirmedLocation.longitude)
        ) {
            renderBranchOptions(restoredConfirmedLocation.latitude, restoredConfirmedLocation.longitude);
        } else {
            renderBranchOptions();
        }
        updateShippingSummary();
        void refreshCheckoutAvailability();
        syncCheckoutPhoneState();
        requestCheckoutDeviceLocation();

        const mainCheckoutForm = document.querySelector('form[action*="checkout/process"]');
        const placeOrderBtn = document.getElementById('placeOrderButton');
        if (mainCheckoutForm && placeOrderBtn) {
            mainCheckoutForm.addEventListener('submit', function (e) {
                if (placeOrderBtn.dataset.submitting === 'true') {
                    e.preventDefault();
                    return false;
                }
                placeOrderBtn.dataset.submitting = 'true';
                placeOrderBtn.classList.add('disabled');
                placeOrderBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Đang xử lý đơn hàng...';
            });
        }
    });

    // Delivery type toggle
    document.addEventListener('DOMContentLoaded', function () {
        const deliveryFields = document.querySelector('[data-delivery-fields]');
        const deliveryTypeDelivery = document.getElementById('deliveryTypeDelivery');
        const deliveryTypePickup = document.getElementById('deliveryTypePickup');
        const branchIdSelect = document.getElementById('branch_id');
        const shippingAddressInput = document.getElementById('shipping_address_ui');

        function syncDeliveryMode() {
            const isPickup = deliveryTypePickup?.checked;
            
            if (deliveryFields) {
                deliveryFields.classList.toggle('d-none', isPickup);
            }
            // Update required attributes
            if (shippingAddressInput) {
                shippingAddressInput.required = !isPickup;
            }
            if (branchIdSelect) {
                branchIdSelect.required = isPickup;
            }

            if (isPickup) {
                clearCheckoutRecentOrderDriftNotice();
            } else {
                requestCheckoutDeviceLocation();
                maybeRequireAddressRefreshAgainstSavedAddress();
            }

            // Update shipping fee display
            if (typeof window.updateShippingSummary === 'function') {
                window.updateShippingSummary();
            }

            if (typeof window.updateBranchSelectorState === 'function') {
                window.updateBranchSelectorState();
            }

            if (typeof window.syncAddressHouseNumberNotice === 'function') {
                window.syncAddressHouseNumberNotice();
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

</script>
<script>
    (function () {
        const controls = new WeakMap();
        const moneyFormatter = new Intl.NumberFormat('vi-VN');

        function clampQuantity(value) {
            const normalized = Number.parseInt(String(value || '').replace(/[^\d]/g, ''), 10);
            return Number.isFinite(normalized) ? Math.min(99, Math.max(1, normalized)) : 1;
        }

        function parseMoney(value) {
            const normalized = String(value || '').replace(/[^\d]/g, '');
            return normalized ? Number(normalized) : 0;
        }

        function formatMoney(value) {
            return `${moneyFormatter.format(Math.max(0, Math.round(value)))}đ`;
        }

        function getControl(control) {
            if (!controls.has(control)) {
                controls.set(control, {
                    inFlight: false,
                    pending: null,
                    repeatTimer: null,
                    repeatInterval: null,
                    pointerActive: false,
                    syncTimer: null,
                });
            }

            return controls.get(control);
        }

        function stopRepeat(control) {
            const state = getControl(control);

            if (state.repeatTimer) {
                window.clearTimeout(state.repeatTimer);
                state.repeatTimer = null;
            }

            if (state.repeatInterval) {
                window.clearInterval(state.repeatInterval);
                state.repeatInterval = null;
            }

            state.pointerActive = false;
        }

        function render(control, quantity) {
            const state = getControl(control);
            const input = control.querySelector('[data-checkout-item-quantity-input]');
            const minusButton = control.querySelector('[data-checkout-qty-minus]');
            const plusButton = control.querySelector('[data-checkout-qty-plus]');
            const nextQuantity = clampQuantity(quantity);

            state.quantity = nextQuantity;

            if (input) {
                input.value = String(nextQuantity);
            }

            if (minusButton) {
                minusButton.disabled = nextQuantity <= 1;
            }

            if (plusButton) {
                plusButton.disabled = nextQuantity >= 99;
            }

            return nextQuantity;
        }

        function updateSummary(payload) {
            const subtotalEl = document.querySelector('[data-checkout-subtotal]');
            if (subtotalEl && payload.total_formatted) {
                subtotalEl.textContent = payload.total_formatted;
            }

            const rowCountEl = document.querySelector('[data-checkout-item-count]');
            if (rowCountEl && typeof payload.count !== 'undefined') {
                rowCountEl.textContent = payload.count;
            }

            const shippingFeeEl = document.getElementById('summaryShippingFee');
            const shippingDistanceEl = document.getElementById('summaryShippingDistance');
            const grandTotalEl = document.getElementById('summaryGrandTotal');
            const currentSubtotal = parseMoney(subtotalEl?.textContent);
            if (window.shippingConfig) {
                window.shippingConfig.subtotal = currentSubtotal;
                window.shippingConfig.cupCount = Math.max(1, Number(payload.quantity_count || 1));
            }

            if (typeof window.refreshVoucherCards === 'function') {
                window.refreshVoucherCards();
            }

            if (typeof window.updateShippingSummary === 'function') {
                window.updateShippingSummary();
            }

            const shippingDistanceEl = document.getElementById('summaryShippingDistance');
            if (shippingDistanceEl && !shippingDistanceEl.textContent.trim()) {
                shippingDistanceEl.textContent = 'Đã cập nhật số lượng';
            }
        }

        function applyPayload(control, payload) {
            const row = control.closest('[data-checkout-item]');
            const cartKey = row?.dataset.checkoutItem || '';
            const item = payload?.items?.[cartKey];

            if (row && item) {
                const quantityText = row.querySelector('[data-checkout-item-quantity-text]');
                const input = row.querySelector('[data-checkout-item-quantity-input]');
                const subtotal = row.querySelector('[data-checkout-item-subtotal]');

                if (quantityText) {
                    quantityText.textContent = item.quantity;
                }

                if (input) {
                    input.value = String(item.quantity);
                }

                if (subtotal) {
                    subtotal.textContent = item.subtotal_formatted || formatMoney(Number(item.price || 0) * Number(item.quantity || 1));
                }
            }

            updateSummary(payload);
        }

        async function commit(control, quantity, method = 'PATCH') {
            const state = getControl(control);
            const url = control.dataset.checkoutUpdateUrl || '';
            const row = control.closest('[data-checkout-item]');
            const normalizedQuantity = method === 'DELETE' ? null : clampQuantity(quantity);

            if (!url) {
                return;
            }

            if (state.inFlight) {
                state.pending = { quantity: normalizedQuantity ?? quantity, method };
                return;
            }

            state.inFlight = true;

            try {
                const body = new FormData();
                body.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
                body.append('_method', method);

                if (normalizedQuantity !== null) {
                    body.append('quantity', String(normalizedQuantity));
                }

                const response = await fetch(url, {
                    method: 'POST',
                    body,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error('cart_update_failed');
                }

                const payload = await response.json();
                applyPayload(control, payload);

                if (payload.count === 0) {
                    window.location.href = @json(route('cart.index'));
                    return;
                }

                if (method === 'DELETE') {
                    row?.remove();
                }
            } catch (error) {
                console.error('Không thể cập nhật số lượng ở checkout.', error);
            } finally {
                state.inFlight = false;

                if (state.pending && state.pending.quantity !== state.quantity) {
                    const pending = state.pending;
                    state.pending = null;
                    window.setTimeout(() => commit(control, pending.quantity, pending.method), 0);
                } else {
                    state.pending = null;
                }
            }
        }

        function startRepeat(control, button, delta) {
            const state = getControl(control);
            stopRepeat(control);

            const nextQuantity = render(control, (state.quantity || clampQuantity(control.querySelector('[data-checkout-item-quantity-input]')?.value || 1)) + delta);
            commit(control, nextQuantity);

            state.repeatTimer = window.setTimeout(() => {
                state.repeatInterval = window.setInterval(() => {
                    const repeatedQuantity = render(control, (getControl(control).quantity || clampQuantity(control.querySelector('[data-checkout-item-quantity-input]')?.value || 1)) + delta);
                    commit(control, repeatedQuantity);
                }, 75);
            }, 260);

            state.pointerActive = true;
        }

        document.addEventListener('pointerdown', function (event) {
            const button = event.target.closest('[data-checkout-qty-minus], [data-checkout-qty-plus]');
            if (!button) {
                return;
            }

            const control = button.closest('[data-checkout-qty-control]');
            if (!control) {
                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();
            const delta = button.matches('[data-checkout-qty-minus]') ? -1 : 1;
            startRepeat(control, button, delta);
        }, true);

        document.addEventListener('mousedown', function (event) {
            const button = event.target.closest('[data-checkout-qty-minus], [data-checkout-qty-plus]');
            if (!button) {
                return;
            }

            if (window.PointerEvent) {
                return;
            }

            const control = button.closest('[data-checkout-qty-control]');
            if (!control) {
                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();
            const delta = button.matches('[data-checkout-qty-minus]') ? -1 : 1;
            startRepeat(control, button, delta);
        }, true);

        document.addEventListener('touchstart', function (event) {
            const button = event.target.closest('[data-checkout-qty-minus], [data-checkout-qty-plus]');
            if (!button) {
                return;
            }

            if (window.PointerEvent) {
                return;
            }

            const control = button.closest('[data-checkout-qty-control]');
            if (!control) {
                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();
            const delta = button.matches('[data-checkout-qty-minus]') ? -1 : 1;
            startRepeat(control, button, delta);
        }, { capture: true, passive: false });

        ['pointerup', 'pointercancel', 'mouseup', 'touchend', 'lostpointercapture'].forEach((type) => {
            document.addEventListener(type, function (event) {
                const button = event.target.closest('[data-checkout-qty-minus], [data-checkout-qty-plus]');
                const control = button?.closest('[data-checkout-qty-control]');
                if (!control) {
                    return;
                }

                stopRepeat(control);
            }, true);
        });

        document.addEventListener('click', function (event) {
            const button = event.target.closest('[data-checkout-qty-minus], [data-checkout-qty-plus]');
            if (!button) {
                return;
            }

            const control = button.closest('[data-checkout-qty-control]');
            if (!control) {
                return;
            }

            const state = getControl(control);
            if (state.pointerActive) {
                event.preventDefault();
                event.stopImmediatePropagation();
                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();

            const current = clampQuantity(control.querySelector('[data-checkout-item-quantity-input]')?.value || state.quantity || 1);
            const nextQuantity = render(control, current + (button.matches('[data-checkout-qty-minus]') ? -1 : 1));
            commit(control, nextQuantity);
        }, true);

        document.addEventListener('input', function (event) {
            const input = event.target.closest('[data-checkout-item-quantity-input]');
            if (!input) {
                return;
            }

            const control = input.closest('[data-checkout-qty-control]');
            if (!control) {
                return;
            }

            event.stopImmediatePropagation();
            const digitsOnly = String(input.value || '').replace(/[^\d]/g, '').slice(0, 2);
            if (input.value !== digitsOnly) {
                input.value = digitsOnly;
            }

            if (digitsOnly === '') {
                return;
            }

            const quantity = render(control, digitsOnly);
            const state = getControl(control);
            clearTimeout(state.syncTimer);
            state.syncTimer = window.setTimeout(() => commit(control, quantity), 220);
        }, true);

        document.addEventListener('change', function (event) {
            const input = event.target.closest('[data-checkout-item-quantity-input]');
            if (!input) {
                return;
            }

            const control = input.closest('[data-checkout-qty-control]');
            if (!control) {
                return;
            }

            event.stopImmediatePropagation();
            const quantity = render(control, input.value || 1);
            commit(control, quantity);
        }, true);

        document.addEventListener('blur', function (event) {
            const input = event.target.closest('[data-checkout-item-quantity-input]');
            if (!input) {
                return;
            }

            const control = input.closest('[data-checkout-qty-control]');
            if (!control) {
                return;
            }

            const quantity = render(control, input.value || 1);
            commit(control, quantity);
        }, true);

        document.addEventListener('focus', function (event) {
            const input = event.target.closest('[data-checkout-item-quantity-input]');
            if (!input) {
                return;
            }

            window.setTimeout(() => input.select(), 0);
        }, true);

        document.addEventListener('keydown', function (event) {
            const input = event.target.closest('[data-checkout-item-quantity-input]');
            if (!input) {
                return;
            }

            const control = input.closest('[data-checkout-qty-control]');
            if (!control) {
                return;
            }

            if (event.key === 'Enter') {
                event.preventDefault();
                const quantity = render(control, input.value || 1);
                commit(control, quantity);
                return;
            }

            if (event.key === 'ArrowUp' || event.key === 'ArrowDown') {
                event.preventDefault();
                const current = clampQuantity(input.value || 1);
                const quantity = render(control, current + (event.key === 'ArrowUp' ? 1 : -1));
                commit(control, quantity);
            }
        }, true);

        document.querySelectorAll('[data-checkout-qty-control]').forEach((control) => {
            render(control, control.querySelector('[data-checkout-item-quantity-input]')?.value || 1);
        });
    })();
</script>
@include('components.branch-availability-sync')
@endsection
