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
        ? collect(view()->getShared())->only(['uiResolveProductImage', 'uiDefaultImage', 'uiCategoryImages', 'uiProductImageUrls', 'uiPlaceholderImage'])->all()
        : require resource_path('views/partials/ui-product-data.php');
    $resolveImage = $uiData['uiResolveProductImage'];
    $placeholderImage = $uiData['uiPlaceholderImage'] ?? static fn (?string $label = null, ?string $category = null) => $uiData['uiDefaultImage'];
    $normalizeImage = static function (?string $value): ?string {
        if (! $value) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (str_starts_with($value, 'http') || str_starts_with($value, 'data:image') || str_starts_with($value, '/')) {
            return $value;
        }

        $path = ltrim($value, '/');

        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            return asset('storage/'.$path);
        }

        if (is_file(public_path($path))) {
            return asset($path);
        }

        return null;
    };
    $imageUrl = $normalizeImage($src) ?: $resolveImage($sku, $category, $name ?: $alt, (int) $width);

    if (! $imageUrl) {
        $imageUrl = $placeholderImage($name ?: $alt, $category);
    }

    $categoryFallback = ($category && isset($uiData['uiCategoryImages'][$category]))
        ? preg_replace('/w=\d+/', 'w='.(int) $width, $uiData['uiCategoryImages'][$category]) ?? $uiData['uiCategoryImages'][$category]
        : ($uiData['uiDefaultImage'] ?? $imageUrl);

    $skuFallback = ($sku && ! empty($uiData['uiProductImageUrls'][$sku]))
        ? preg_replace('/w=\d+/', 'w='.(int) $width, $uiData['uiProductImageUrls'][$sku]) ?? $uiData['uiProductImageUrls'][$sku]
        : $categoryFallback;
    $safeFallback = $placeholderImage($name ?: $alt, $category);
@endphp

<img
    {{ $attributes->merge(['class' => 'product-image']) }}
    src="{{ $imageUrl }}"
    alt="{{ $alt }}"
    loading="lazy"
    decoding="async"
    data-ui-sku="{{ $sku }}"
    data-ui-category="{{ $category }}"
    onerror="this.onerror=null;this.src='{{ $safeFallback }}';"
>
