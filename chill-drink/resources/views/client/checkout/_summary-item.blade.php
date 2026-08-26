@php
    $quantity = max(1, (int) ($item['quantity'] ?? 1));
    $unitPrice = max(0, (int) ($item['price'] ?? 0));
    $lineTotal = $unitPrice * $quantity;
    $toppings = collect($item['toppings'] ?? [])->filter(fn ($topping) => is_array($topping) && trim((string) ($topping['name'] ?? '')) !== '');
    $toppingTotal = max(0, (int) ($item['topping_total'] ?? $toppings->sum(fn ($topping) => (int) ($topping['price'] ?? 0))));
    $sizeExtra = max(0, (int) ($item['size_extra'] ?? 0));
    $basePrice = max(0, (int) ($item['base_price'] ?? max(0, $unitPrice - $sizeExtra - $toppingTotal)));
@endphp

<div class="checkout-summary-item {{ !empty($extra) ? 'd-none' : '' }}" data-checkout-item="{{ $cartKey }}" @if(!empty($extra)) data-checkout-extra-item @endif>
    <img src="{{ $item['image'] ?? 'https://images.unsplash.com/photo-1544145945-f90425340c7e?auto=format&fit=crop&w=400&q=80' }}" alt="{{ $item['name'] }}" class="checkout-item-img">
    <div class="checkout-summary-content">
        <div class="checkout-summary-title-row">
            <div class="checkout-summary-name">{{ $item['name'] }}</div>
            <strong class="checkout-summary-line-total" data-checkout-item-subtotal>{{ number_format($lineTotal, 0, ',', '.') }}đ</strong>
        </div>
        <div class="checkout-summary-badges">
            @if(!empty($item['group_member_name']))
                <span class="checkout-summary-chip is-member"><i class="bi bi-person"></i>{{ $item['group_member_name'] }}</span>
            @endif
            <span class="checkout-summary-chip">{{ $item['size_label'] ?? 'Kích cỡ M' }}</span>
            <span class="checkout-summary-chip">Số lượng: <span data-checkout-item-quantity-text>{{ $quantity }}</span></span>
            <span class="checkout-summary-chip">Đường {{ $item['sugar_level'] ?? 100 }}%</span>
            <span class="checkout-summary-chip">Đá {{ $item['ice_level'] ?? 100 }}%</span>
        </div>
        @if($basePrice > 0 || $sizeExtra > 0 || $toppings->isNotEmpty())
        <div class="checkout-summary-price-lines">
            @if($basePrice > 0 && ($sizeExtra > 0 || $toppings->isNotEmpty()))
                <div>Giá gốc: <span class="text-dark fw-semibold">{{ number_format($basePrice, 0, ',', '.') }}đ</span></div>
            @else
                <div>Đơn giá: <span class="text-dark fw-semibold">{{ number_format($unitPrice, 0, ',', '.') }}đ</span></div>
            @endif
            @if($sizeExtra > 0)
                <div>Size: <span class="text-dark fw-semibold">+{{ number_format($sizeExtra, 0, ',', '.') }}đ</span></div>
            @endif
            @if($toppings->isNotEmpty())
                <div>Topping: <span class="text-dark fw-semibold">{{ $toppings->pluck('name')->implode(', ') }}</span>@if($toppingTotal > 0) <span class="text-success fw-semibold">+{{ number_format($toppingTotal, 0, ',', '.') }}đ</span>@endif</div>
            @endif
        </div>
        @endif
        @if(!empty($item['note']))
            <div class="checkout-summary-meta text-primary">
                <i class="bi bi-chat-left-text me-1"></i>{{ $item['note'] }}
            </div>
        @endif
        <div class="checkout-item-actions mt-2" data-checkout-qty-control data-checkout-update-url="{{ route('cart.update', $cartKey) }}" data-checkout-remove-url="{{ route('cart.remove', $cartKey) }}">
            <button type="button" data-checkout-cart-action="{{ route('cart.update', $cartKey) }}" data-method="PATCH" data-quantity="{{ max(1, $quantity - 1) }}" data-checkout-qty-minus aria-label="Giảm số lượng {{ $item['name'] }}" @disabled($quantity <= 1)><i class="bi bi-dash"></i></button>
            <input type="text" value="{{ $quantity }}" inputmode="numeric" pattern="[0-9]*" autocomplete="off" spellcheck="false" maxlength="2" aria-label="Số lượng {{ $item['name'] }}" data-checkout-item-quantity-input data-checkout-item-quantity-value>
            <button type="button" data-checkout-cart-action="{{ route('cart.update', $cartKey) }}" data-method="PATCH" data-quantity="{{ min(99, $quantity + 1) }}" data-checkout-qty-plus aria-label="Tăng số lượng {{ $item['name'] }}"><i class="bi bi-plus"></i></button>
            <button type="button" class="is-remove ms-1" data-checkout-cart-action="{{ route('cart.remove', $cartKey) }}" data-method="DELETE" data-confirm="Xóa món này khỏi đơn hàng?" data-checkout-qty-remove aria-label="Xóa {{ $item['name'] }}"><i class="bi bi-trash3"></i></button>
        </div>
    </div>
</div>
