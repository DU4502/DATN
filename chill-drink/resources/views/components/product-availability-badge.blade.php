@props(['product', 'branch'])

@php
    $availability = $product instanceof \App\Models\Product ? $product->availabilityAt($branch) : null;
    $branchName = $branch?->name ?? 'chi nhánh đã chọn';
@endphp

<span
    {{ $attributes->class([
        'badge',
        'text-bg-success' => $availability === true,
        'text-bg-danger' => $availability === false,
        'text-bg-secondary' => $availability === null,
    ]) }}
    data-product-availability="{{ $product->id ?? '' }}"
    data-branch-id="{{ $branch?->id }}"
    data-availability-badge
>
    @if($availability === true)
        <span class="availability-label-full">Còn hàng tại Chi nhánh {{ $branchName }}</span>
        <span class="availability-label-compact d-none">Còn hàng</span>
    @elseif($availability === false)
        <span class="availability-label-full">Hết hàng tại Chi nhánh {{ $branchName }}</span>
        <span class="availability-label-compact d-none">Hết hàng</span>
    @else
        <span class="availability-label-full">Chưa phục vụ tại Chi nhánh {{ $branchName }}</span>
        <span class="availability-label-compact d-none">Chưa phục vụ</span>
    @endif
</span>
