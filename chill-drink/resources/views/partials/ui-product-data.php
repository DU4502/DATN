<?php

use App\Support\ProductCatalog;

/**
 * Dữ liệu ảnh giao diện — mỗi sản phẩm có ảnh, fallback theo danh mục.
 * Gọi: extract(require resource_path('views/partials/ui-product-data.php'));
 */
if (! view()->shared('ui.product.bootstrapped', false)) {
    $uiCategoryImages = [
        'Trà Sữa' => 'https://images.unsplash.com/photo-1558857563-b371033873b8?auto=format&fit=crop&w=700&q=80',
        'Cà Phê' => 'https://images.unsplash.com/photo-1517701550927-30cf4ba1dba5?auto=format&fit=crop&w=700&q=80',
        'Sinh Tố' => 'https://images.unsplash.com/photo-1621506289937-a8e4df240d0b?auto=format&fit=crop&w=700&q=80',
        'Nước Ép' => 'https://images.unsplash.com/photo-1600271886742-f049cd451bba?auto=format&fit=crop&w=700&q=80',
        'Trà Trái Cây' => 'https://images.unsplash.com/photo-1556679343-c7306c1976bc?auto=format&fit=crop&w=700&q=80',
        'Soda' => 'https://images.unsplash.com/photo-1544145945-f90425340c7e?auto=format&fit=crop&w=700&q=80',
        'Đá Xay' => 'https://images.unsplash.com/photo-1572490122747-3968b75cc699?auto=format&fit=crop&w=700&q=80',
        'Matcha' => 'https://images.unsplash.com/photo-1515823064-d6e0c04616a7?auto=format&fit=crop&w=700&q=80',
    ];

    /** Mỗi danh mục nhiều ảnh — gán lần lượt cho từng SKU */
    $uiCategoryImagePools = [
        'Trà Sữa' => [
            'https://images.unsplash.com/photo-1558857563-b371033873b8?auto=format&fit=crop&w=700&q=80',
            'https://images.unsplash.com/photo-1564890379475-df9a556ad051?auto=format&fit=crop&w=700&q=80',
            'https://images.unsplash.com/photo-1515823064-d6e0c04616a7?auto=format&fit=crop&w=700&q=80',
            'https://images.unsplash.com/photo-1621506289937-a8e4df240d0b?auto=format&fit=crop&w=700&q=80',
            'https://images.unsplash.com/photo-1525385133512-4f3bdd81f6f4?auto=format&fit=crop&w=700&q=80',
            'https://images.unsplash.com/photo-1513558161293-cdaf765ed2fd?auto=format&fit=crop&w=700&q=80',
        ],
        'Cà Phê' => [
            'https://images.unsplash.com/photo-1517701550927-30cf4ba1dba5?auto=format&fit=crop&w=700&q=80',
            'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=700&q=80',
            'https://images.unsplash.com/photo-1509042239860-f550ce710b93?auto=format&fit=crop&w=700&q=80',
            'https://images.unsplash.com/photo-1461023058943-07fcbe16d930?auto=format&fit=crop&w=700&q=80',
            'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?auto=format&fit=crop&w=700&q=80',
            'https://images.unsplash.com/photo-1442512595331-e89e73853f31?auto=format&fit=crop&w=700&q=80',
        ],
        'Sinh Tố' => [
            'https://images.unsplash.com/photo-1621506289937-a8e4df240d0b?auto=format&fit=crop&w=700&q=80',
            'https://images.unsplash.com/photo-1553530666-ba11a7da3888?auto=format&fit=crop&w=700&q=80',
            'https://images.unsplash.com/photo-1559180900-446972b811b9?auto=format&fit=crop&w=700&q=80',
            'https://images.unsplash.com/photo-1595475207225-428b62bda831?auto=format&fit=crop&w=700&q=80',
            'https://images.unsplash.com/photo-1570197788417-0e82375c9371?auto=format&fit=crop&w=700&q=80',
            'https://images.unsplash.com/photo-1502741224143-90386d7f8c82?auto=format&fit=crop&w=700&q=80',
        ],
        'Nước Ép' => [
            'https://images.unsplash.com/photo-1600271886742-f049cd451bba?auto=format&fit=crop&w=700&q=80',
            'https://images.unsplash.com/photo-1595475207225-428b62bda831?auto=format&fit=crop&w=700&q=80',
            'https://images.unsplash.com/photo-1570197788417-0e82375c9371?auto=format&fit=crop&w=700&q=80',
            'https://images.unsplash.com/photo-1613478263727-4d922f818f94?auto=format&fit=crop&w=700&q=80',
            'https://images.unsplash.com/photo-1546171753-97d7676a4602?auto=format&fit=crop&w=700&q=80',
            'https://images.unsplash.com/photo-1523371683702-5f0a2b1f1c35?auto=format&fit=crop&w=700&q=80',
        ],
        'Trà Trái Cây' => [
            'https://images.unsplash.com/photo-1556679343-c7306c1976bc?auto=format&fit=crop&w=700&q=80',
            'https://images.unsplash.com/photo-1513558161293-cdaf765ed2fd?auto=format&fit=crop&w=700&q=80',
            'https://images.unsplash.com/photo-1551024709-8f23befc6f87?auto=format&fit=crop&w=700&q=80',
            'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?auto=format&fit=crop&w=700&q=80',
            'https://images.unsplash.com/photo-1622597467836-f3285f2131b8?auto=format&fit=crop&w=700&q=80',
            'https://images.unsplash.com/photo-1497534446932-c925b458314e?auto=format&fit=crop&w=700&q=80',
        ],
        'Soda' => [
            'https://images.unsplash.com/photo-1544145945-f90425340c7e?auto=format&fit=crop&w=700&q=80',
            'https://images.unsplash.com/photo-1553906952-935eca5cdefd?auto=format&fit=crop&w=700&q=80',
            'https://images.unsplash.com/photo-1546171753-97d7676a4602?auto=format&fit=crop&w=700&q=80',
            'https://images.unsplash.com/photo-1576092768241-decf85352c69?auto=format&fit=crop&w=700&q=80',
            'https://images.unsplash.com/photo-1534353473418-4cfa6c56fd38?auto=format&fit=crop&w=700&q=80',
            'https://images.unsplash.com/photo-1488900128323-21503983a07e?auto=format&fit=crop&w=700&q=80',
        ],
        'Đá Xay' => [
            'https://images.unsplash.com/photo-1572490122747-3968b75cc699?auto=format&fit=crop&w=700&q=80',
            'https://images.unsplash.com/photo-1565299585323-38d6b0865b47?auto=format&fit=crop&w=700&q=80',
            'https://images.unsplash.com/photo-1551024709-8f23befc6f87?auto=format&fit=crop&w=700&q=80',
        ],
        'Matcha' => [
            'https://images.unsplash.com/photo-1515823064-d6e0c04616a7?auto=format&fit=crop&w=700&q=80',
            'https://images.unsplash.com/photo-1536256263959-770b48d82b0a?auto=format&fit=crop&w=700&q=80',
            'https://images.unsplash.com/photo-1563822249548-9a72b6353cd1?auto=format&fit=crop&w=700&q=80',
        ],
    ];

    $uiProductImageUrls = [];
    $uiProductImagesByName = [];
    $uiDisplaySkus = [];
    $uiCategoryCounters = [];

    $uiPlaceholderImage = static function (?string $label = null, ?string $category = null): string {
        $label = trim((string) ($label ?: $category ?: 'Chill Drink'));
        $words = preg_split('/\s+/u', $label) ?: [];
        $initials = collect($words)
            ->filter()
            ->take(2)
            ->map(fn (string $word) => mb_strtoupper(mb_substr($word, 0, 1)))
            ->implode('');
        $initials = $initials ?: 'CD';

        $svg = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="900" height="900" viewBox="0 0 900 900"><defs><linearGradient id="g" x1="0" x2="1" y1="0" y2="1"><stop stop-color="#e9fbf7"/><stop offset="1" stop-color="#b8eadf"/></linearGradient></defs><rect width="900" height="900" fill="url(#g)"/><circle cx="450" cy="365" r="132" fill="#008b7a" opacity=".95"/><path d="M407 315h118l-18 176c-4 35-28 59-61 59s-57-24-61-59l-18-176h40zm18 34 14 138c1 12 9 20 21 20s20-8 21-20l14-138h-70z" fill="#fff"/><text x="450" y="675" text-anchor="middle" font-family="Arial, sans-serif" font-size="52" font-weight="700" fill="#073a35">%s</text></svg>',
            htmlspecialchars($initials, ENT_QUOTES, 'UTF-8')
        );

        return 'data:image/svg+xml;charset=UTF-8,'.rawurlencode($svg);
    };

    foreach (ProductCatalog::ITEMS as $item) {
        $sku = $item['sku'];
        $category = $item['category'];
        $uiDisplaySkus[] = $sku;

        $pool = $uiCategoryImagePools[$category] ?? [$uiCategoryImages[$category] ?? $uiCategoryImages['Soda']];
        $index = $uiCategoryCounters[$category] ?? 0;
        $uiCategoryCounters[$category] = $index + 1;

        $url = $pool[$index % count($pool)];
        $uiProductImageUrls[$sku] = $url;
        $uiProductImagesByName[$item['name']] = $url;
    }

    $uiHomeFeaturedSkus = [
        'CD-TS-001', 'CD-CF-001', 'CD-ST-001', 'CD-NE-001',
        'CD-TC-001', 'CD-SD-001', 'CD-TS-002', 'CD-CF-002',
    ];

    $uiDefaultImage = $uiCategoryImages['Soda'];

    $uiResizeImage = static function (string $url, int $width = 700): string {
        if (str_starts_with($url, 'data:image')) {
            return $url;
        }

        if (str_contains($url, 'w=')) {
            return preg_replace('/w=\d+/', 'w='.$width, $url) ?? $url;
        }

        return $url.(str_contains($url, '?') ? '&' : '?').'w='.$width.'&q=80&auto=format&fit=crop';
    };

    $uiImageVariant = static function (string $url, int $width = 1000, int $variant = 0) use ($uiResizeImage): string {
        if (str_starts_with($url, 'data:image')) {
            return $url;
        }

        $resized = $uiResizeImage($url, $width);
        $separator = str_contains($resized, '?') ? '&' : '?';

        return match ($variant) {
            1 => $resized.$separator.'h=820&crop=entropy',
            2 => $resized.$separator.'h=1000&crop=focalpoint',
            3 => $resized.$separator.'h=720&fit=crop',
            default => $resized,
        };
    };

    $uiSkuCategoryPrefixes = [
        'TS' => 'Trà Sữa',
        'CF' => 'Cà Phê',
        'ST' => 'Sinh Tố',
        'NE' => 'Nước Ép',
        'TC' => 'Trà Trái Cây',
        'SD' => 'Soda',
        'DX' => 'Đá Xay',
        'MT' => 'Matcha',
    ];

    $uiResolveProductImage = static function (?string $sku, ?string $category, ?string $name, int $width = 700) use ($uiProductImageUrls, $uiProductImagesByName, $uiCategoryImages, $uiSkuCategoryPrefixes, $uiResizeImage, $uiDefaultImage): string {
        if ($sku && isset($uiProductImageUrls[$sku])) {
            return $uiResizeImage($uiProductImageUrls[$sku], $width);
        }

        if ($name) {
            foreach ($uiProductImagesByName as $productName => $url) {
                if (mb_strtolower(trim($productName)) === mb_strtolower(trim($name))) {
                    return $uiResizeImage($url, $width);
                }
            }
        }

        if ($sku && preg_match('/^CD-([A-Z]{2})-\d{3}$/', strtoupper($sku), $matches)) {
            $categoryFromSku = $uiSkuCategoryPrefixes[$matches[1]] ?? null;
            if ($categoryFromSku && isset($uiCategoryImages[$categoryFromSku])) {
                return $uiResizeImage($uiCategoryImages[$categoryFromSku], $width);
            }
        }

        if ($category && isset($uiCategoryImages[$category])) {
            return $uiResizeImage($uiCategoryImages[$category], $width);
        }

        return $uiResizeImage($uiDefaultImage, $width);
    };

    $uiProductVisible = static function (?string $sku) use ($uiDisplaySkus): bool {
        return $sku && in_array($sku, $uiDisplaySkus, true);
    };

    $uiGetProductGallery = static function (?string $sku, ?string $category, ?string $name = null, int $limit = 4, ?string $uploadedImage = null) use ($uiProductImageUrls, $uiResolveProductImage, $uiImageVariant, $uiPlaceholderImage): array {
        $primary = $uploadedImage;

        if (! $primary && $sku && isset($uiProductImageUrls[$sku])) {
            $primary = $uiProductImageUrls[$sku];
        }

        if (! $primary) {
            $primary = $uiResolveProductImage($sku, $category, $name, 1000);
        }

        if (! $primary) {
            $primary = $uiPlaceholderImage($name, $category);
        }

        $images = [
            $uiImageVariant($primary, 1000, 0),
            $uiImageVariant($primary, 1000, 1),
            $uiImageVariant($primary, 1000, 2),
            $uiImageVariant($primary, 1000, 3),
        ];

        $images[] = $uiPlaceholderImage($name, $category);

        return array_values(array_slice(array_unique($images), 0, $limit));
    };

    view()->share([
        'uiDisplaySkus' => $uiDisplaySkus,
        'uiHomeFeaturedSkus' => $uiHomeFeaturedSkus,
        'uiCategoryImages' => $uiCategoryImages,
        'uiCategoryImagePools' => $uiCategoryImagePools,
        'uiProductImageUrls' => $uiProductImageUrls,
        'uiProductImagesByName' => $uiProductImagesByName,
        'uiDefaultImage' => $uiDefaultImage,
        'uiResolveProductImage' => $uiResolveProductImage,
        'uiGetProductGallery' => $uiGetProductGallery,
        'uiPlaceholderImage' => $uiPlaceholderImage,
        'uiProductVisible' => $uiProductVisible,
        'ui.product.bootstrapped' => true,
    ]);
}

return collect(view()->getShared())->only([
    'uiDisplaySkus',
    'uiHomeFeaturedSkus',
    'uiCategoryImages',
    'uiCategoryImagePools',
    'uiProductImageUrls',
    'uiProductImagesByName',
    'uiDefaultImage',
    'uiResolveProductImage',
    'uiGetProductGallery',
    'uiPlaceholderImage',
    'uiProductVisible',
])->all();
