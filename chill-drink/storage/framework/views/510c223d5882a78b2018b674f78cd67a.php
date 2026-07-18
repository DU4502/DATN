<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'src' => null,
    'alt' => '',
    'category' => null,
    'productId' => null,
    'sku' => null,
    'name' => null,
    'width' => 700,
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'src' => null,
    'alt' => '',
    'category' => null,
    'productId' => null,
    'sku' => null,
    'name' => null,
    'width' => 700,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>

<?php
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
?>

<img
    <?php echo e($attributes->merge(['class' => 'product-image'])); ?>

    src="<?php echo e($imageUrl); ?>"
    alt="<?php echo e($alt); ?>"
    loading="lazy"
    decoding="async"
    data-ui-sku="<?php echo e($sku); ?>"
    data-ui-category="<?php echo e($category); ?>"
    onerror="this.onerror=null;this.src='<?php echo e($safeFallback); ?>';"
>
<?php /**PATH C:\xampp\htdocs\DATN\chill-drink\resources\views/components/product-image.blade.php ENDPATH**/ ?>