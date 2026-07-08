<div class="d-flex gap-3 align-items-center checkout-summary-item {{ !empty($extra) ? 'd-none' : '' }}" data-checkout-item="{{ $cartKey }}" @if(!empty($extra)) data-checkout-extra-item @endif>
    <img src="{{ $item['image'] ?? 'https://images.unsplash.com/photo-1544145945-f90425340c7e?auto=format&fit=crop&w=400&q=80' }}" alt="{{ $item['name'] }}" class="checkout-item-img">
    <div class="flex-grow-1 min-w-0">
        <div class="fw-bold">{{ $item['name'] }}</div>
        <div class="text-secondary small">{{ $item['size_label'] ?? 'Size M' }} · Số lượng: <span data-checkout-item-quantity-text>{{ $item['quantity'] }}</span></div>
        @if(!empty($item['toppings']))
            <div class="text-primary small fw-semibold">Topping: {{ collect($item['toppings'])->pluck('name')->filter()->implode(', ') }}</div>
        @endif
        <div class="text-secondary small">Đường {{ $item['sugar_level'] ?? 100 }}% · Đá {{ $item['ice_level'] ?? 100 }}%</div>
        <div class="checkout-item-actions mt-2">
            <button type="button" data-checkout-cart-action="{{ route('cart.update', $cartKey) }}" data-method="PATCH" data-quantity="{{ max(1, (int) $item['quantity'] - 1) }}" aria-label="Giảm số lượng {{ $item['name'] }}" @disabled((int) $item['quantity'] <= 1)><i class="bi bi-dash"></i></button>
            <strong data-checkout-item-quantity>{{ $item['quantity'] }}</strong>
            <button type="button" data-checkout-cart-action="{{ route('cart.update', $cartKey) }}" data-method="PATCH" data-quantity="{{ min(99, (int) $item['quantity'] + 1) }}" aria-label="Tăng số lượng {{ $item['name'] }}"><i class="bi bi-plus"></i></button>
            <button type="button" class="is-remove ms-1" data-checkout-cart-action="{{ route('cart.remove', $cartKey) }}" data-method="DELETE" data-confirm="Xóa món này khỏi đơn hàng?" aria-label="Xóa {{ $item['name'] }}"><i class="bi bi-trash3"></i></button>
        </div>
    </div>
    <strong class="text-nowrap" data-checkout-item-subtotal>{{ number_format($item['price'] * $item['quantity'], 0, ',', '.') }}đ</strong>
</div>
