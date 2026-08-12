<?php

use App\Support\ProductCatalog;

/**
 * Dữ liệu ảnh giao diện — mỗi sản phẩm có ảnh, fallback theo danh mục.
 * Gọi: extract(require resource_path('views/partials/ui-product-data.php'));
 */
if (! view()->shared('ui.product.bootstrapped', false)) {
    $uiCategoryImages = [
        'Trà Sữa' => asset('images/products/tra-sua-tran-chau-duong-den.webp'),
        'Cà Phê' => asset('images/products/ca-phe-sua-da.png'),
        'Sinh Tố' => asset('images/products/sinh-to-xoai.png'),
        'Nước Ép' => asset('images/products/nuoc-ep-cam.png'),
        'Trà Trái Cây' => asset('images/products/tra-dao-cam-sa.png'),
        'Soda' => asset('images/products/soda-blue-curacao.png'),
        'Đá Xay' => asset('images/sinhtoxoai.png'),
        'Matcha' => asset('images/matcha.png'),
    ];

    /** Mỗi danh mục nhiều ảnh — gán lần lượt cho từng SKU */
    $uiCategoryImagePools = [
        'Trà Sữa' => [
            asset('images/products/tra-sua-tran-chau-duong-den.webp'),
            asset('images/products/tra-sua-thai-xanh.jpg'),
            asset('images/products/tra-sua-khoai-mon.jpg'),
            asset('images/products/tra-sua-socola.jpg'),
            asset('images/products/tra-sua-oolong-kem-cheese.jpg'),
            asset('images/trasua.png'),
        ],
        'Cà Phê' => [
            asset('images/products/ca-phe-sua-da.png'),
            asset('images/products/ca-phe-den-da.png'),
            asset('images/products/bac-xiu-da.jpg'),
            asset('images/products/ca-phe-muoi.jpg'),
            asset('images/products/ca-phe-u-lanh.png'),
            asset('images/cafe.png'),
        ],
        'Sinh Tố' => [
            asset('images/products/sinh-to-xoai.png'),
            asset('images/products/sinh-to-bo.png'),
            asset('images/products/sinh-to-dau.png'),
            asset('images/products/sinh-to-viet-quat.png'),
            asset('images/products/sinh-to-chuoi.jpg'),
            asset('images/sinhtoxoai.png'),
        ],
        'Nước Ép' => [
            asset('images/products/nuoc-ep-cam.png'),
            asset('images/products/nuoc-ep-dua-hau.jpg'),
            asset('images/products/nuoc-ep-ca-rot.jpg'),
            asset('images/products/nuoc-ep-tac.jpg'),
            asset('images/products/nuoc-ep-thom.jpg'),
            asset('images/products/nuoc-ep-cam.png'),
        ],
        'Trà Trái Cây' => [
            asset('images/products/tra-dao-cam-sa.png'),
            asset('images/products/tra-dau.jpg'),
            asset('images/products/tra-vai.png'),
            asset('images/products/tra-xoai.png'),
            asset('images/products/tra-nhiet-doi.png'),
            asset('images/products/tra-dao-cam-sa.png'),
        ],
        'Soda' => [
            asset('images/products/soda-blue-curacao.png'),
            asset('images/products/soda-cam.webp'),
            asset('images/products/soda-chanh-day.jpg'),
            asset('images/products/soda-dua-leo.jpg'),
            asset('images/products/soda-viet-quat.webp'),
            asset('images/products/soda-blue-curacao.png'),
        ],
        'Đá Xay' => [
            asset('images/sinhtoxoai.png'),
            asset('images/products/sinh-to-xoai.png'),
            asset('images/products/sinh-to-dau.png'),
        ],
        'Matcha' => [
            asset('images/matcha.png'),
            asset('images/products/tra-sua-thai-xanh.jpg'),
            asset('images/products/tra-sua-oolong-kem-cheese.jpg'),
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

    $uiGetProductGallery = static function (?string $sku, ?string $category, ?string $name = null, int $limit = 4, ?string $uploadedImage = null) use ($uiProductImageUrls, $uiResolveProductImage, $uiPlaceholderImage): array {
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

        return [$primary];
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
