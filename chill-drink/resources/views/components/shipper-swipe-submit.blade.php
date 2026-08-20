@props([
    'label' => 'Vuốt để xác nhận',
    'buttonId' => null,
    'disabled' => false,
    'tone' => 'green',
])

<div class="ship-swipe-confirm {{ $disabled ? 'is-disabled' : '' }}"
     data-swipe-submit
     data-tone="{{ $tone }}"
     aria-disabled="{{ $disabled ? 'true' : 'false' }}">
    <div class="ship-swipe-fill" data-swipe-fill></div>
    <div class="ship-swipe-label" data-swipe-label>{{ $label }}</div>
    <div class="ship-swipe-knob" data-swipe-knob aria-hidden="true">
        <i class="fa-solid fa-angles-right"></i>
    </div>
    <button type="submit"
            @if($buttonId) id="{{ $buttonId }}" @endif
            class="ship-swipe-native-submit"
            {{ $disabled ? 'disabled' : '' }}>{{ $label }}</button>
</div>
