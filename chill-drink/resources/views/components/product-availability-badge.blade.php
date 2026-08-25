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
        Còn hàng tại Chi nhánh {{ $branchName }}
    @elseif($availability === false)
        Hết hàng tại Chi nhánh {{ $branchName }}
    @else
        Chưa phục vụ tại Chi nhánh {{ $branchName }}
    @endif
</span>
