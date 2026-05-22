@props([
    'src' => null,
    'alt' => '',
    'category' => null,
    'productId' => null,
    'sku' => null,
    'name' => null,
    'width' => 700,
])

@php
    $uiData = view()->shared('ui.product.bootstrapped', false)
        ? collect(view()->getShared())->only(['uiResolveProductImage', 'uiDefaultImage', 'uiCategoryImages', 'uiProductImageUrls'])->all()
        : require resource_path('views/partials/ui-product-data.php');
    $resolveImage = $uiData['uiResolveProductImage'];
    $imageUrl = $resolveImage($sku, $category, $name ?: $alt, (int) $width);

    if (! $imageUrl) {
        $imageUrl = $uiData['uiDefaultImage'] ?? 'https://images.unsplash.com/photo-1544145945-f90425340c7e?auto=format&fit=crop&w=700&q=80';
    }

    $categoryFallback = ($category && isset($uiData['uiCategoryImages'][$category]))
        ? preg_replace('/w=\d+/', 'w='.(int) $width, $uiData['uiCategoryImages'][$category]) ?? $uiData['uiCategoryImages'][$category]
        : ($uiData['uiDefaultImage'] ?? $imageUrl);

    $skuFallback = ($sku && ! empty($uiData['uiProductImageUrls'][$sku]))
        ? preg_replace('/w=\d+/', 'w='.(int) $width, $uiData['uiProductImageUrls'][$sku]) ?? $uiData['uiProductImageUrls'][$sku]
        : $categoryFallback;
@endphp

<img
    {{ $attributes->merge(['class' => 'product-image']) }}
    src="{{ $imageUrl }}"
    alt="{{ $alt }}"
    loading="lazy"
    decoding="async"
    data-ui-sku="{{ $sku }}"
    data-ui-category="{{ $category }}"
    onerror="this.onerror=null;this.src='{{ $categoryFallback }}';"
>
