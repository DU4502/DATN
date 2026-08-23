@php
    $quantity = max(1, (int) ($item['quantity'] ?? 1));
    $unitPrice = max(0, (int) ($item['price'] ?? 0));
    $lineTotal = $unitPrice * $quantity;
    $toppings = collect($item['toppings'] ?? [])->filter(fn ($topping) => is_array($topping) && trim((string) ($topping['name'] ?? '')) !== '');
    $toppingTotal = max(0, (int) ($item['topping_total'] ?? $toppings->sum(fn ($topping) => (int) ($topping['price'] ?? 0))));
    $sizeExtra = max(0, (int) ($item['size_extra'] ?? 0));
    $basePrice = max(0, (int) ($item['base_price'] ?? max(0, $unitPrice - $sizeExtra - $toppingTotal)));
@endphp

<div class="d-flex gap-3 align-items-start checkout-summary-item {{ !empty($extra) ? 'd-none' : '' }}" data-checkout-item="{{ $cartKey }}" @if(!empty($extra)) data-checkout-extra-item @endif>
    <img src="{{ $item['image'] ?? 'https://images.unsplash.com/photo-1544145945-f90425340c7e?auto=format&fit=crop&w=400&q=80' }}" alt="{{ $item['name'] }}" class="checkout-item-img">
    <div class="flex-grow-1 min-w-0">
        <div class="fw-bold">{{ $item['name'] }}</div>
        <div class="checkout-summary-meta">
            {{ $item['size_label'] ?? 'Kích cỡ M' }} · Số lượng: <span data-checkout-item-quantity-text>{{ $quantity }}</span>
        </div>
        <div class="checkout-summary-price-lines">
            @if($basePrice > 0)
                <div>Giá gốc: <span class="text-dark fw-semibold">{{ number_format($basePrice, 0, ',', '.') }}đ</span></div>
            @endif
            @if($sizeExtra > 0)
                <div>Size: <span class="text-dark fw-semibold">+{{ number_format($sizeExtra, 0, ',', '.') }}đ</span></div>
            @endif
            @if($toppings->isNotEmpty())
                <div class="fw-semibold text-dark mt-1">Topping:</div>
                @foreach($toppings as $topping)
                    <div>
                        - {{ $topping['name'] }}
                        <span class="text-success fw-semibold">+{{ number_format((int) ($topping['price'] ?? 0), 0, ',', '.') }}đ</span>
                    </div>
                @endforeach
                @if($toppingTotal > 0)
                    <div class="fw-semibold text-primary mt-1">Tổng topping: +{{ number_format($toppingTotal, 0, ',', '.') }}đ</div>
                @endif
            @endif
        </div>
        <div class="checkout-summary-meta">
            Đường {{ $item['sugar_level'] ?? 100 }}% · Đá {{ $item['ice_level'] ?? 100 }}%
        </div>
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
    <div class="text-end flex-shrink-0 ms-2">
        <div class="checkout-summary-unit-total">{{ number_format($unitPrice, 0, ',', '.') }}đ / ly</div>
        <strong class="text-nowrap checkout-summary-grand-total" data-checkout-item-subtotal>{{ number_format($lineTotal, 0, ',', '.') }}đ</strong>
    </div>
</div>
