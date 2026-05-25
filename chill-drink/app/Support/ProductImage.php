<?php

namespace App\Support;

class ProductImage
{
    private const BASE = 'https://images.unsplash.com';

    /** @var list<string> 30 ảnh đồ uống khác nhau */
    private const POOL = [
        'photo-1558857563-b371033873b8',
        'photo-1517701550927-30cf4ba1dba5',
        'photo-1553530666-ba11a7da3888',
        'photo-1622597467836-f3285f2131b8',
        'photo-1544145945-f90425340c7e',
        'photo-1515823064-d6e0c04616a7',
        'photo-1621506289937-a8e4df240d0b',
        'photo-1600271886742-f049cd451bba',
        'photo-1513558161293-cdaf765ed2fd',
        'photo-1551024709-8f23befc6f87',
        'photo-1517701604599-bb29b565090c',
        'photo-1570197788417-0e82375c9371',
        'photo-1495474472287-4d71bcdd2085',
        'photo-1461023058943-07fcbe16d930',
        'photo-1509042239860-f550ce710b93',
        'photo-1514432324607-a09d9b4aefdd',
        'photo-1497935586351-b708adc0898f',
        'photo-1525385133512-4f3bdd81f6f4',
        'photo-1556679343-c7306c1976bc',
        'photo-1546171753-97d7676a4602',
        'photo-1512568400870-06d1604bc67a',
        'photo-1558618666-fcd25c85cd64',
        'photo-1563822248540-fba0c2b3a06',
        'photo-1534353473418-4cfa6c56fd38',
        'photo-1523672990561-1a5a1d2d8f1f',
        'photo-1553906952-935eca5cdefd',
        'photo-1576092768241-decf85352c69',
        'photo-1548835820-29c03e13f4a3',
        'photo-1595475207225-428b62bda831',
        'photo-1437418740214-8fd970b81a12',
    ];

    /** @var array<string, string> */
    private const BY_CATEGORY = [
        'Trà Sữa' => 'photo-1558857563-b371033873b8',
        'Cà Phê' => 'photo-1517701550927-30cf4ba1dba5',
        'Sinh Tố' => 'photo-1553530666-ba11a7da3888',
        'Nước Ép' => 'photo-1622597467836-f3285f2131b8',
        'Trà Trái Cây' => 'photo-1556679343-c7306c1976bc',
        'Soda' => 'photo-1544145945-f90425340c7e',
    ];

    private const DEFAULT = 'photo-1515823064-d6e0c04616a7';

    public static function url(string $photoId, int $width = 700): string
    {
        return self::BASE.'/'.$photoId.'?auto=format&fit=crop&w='.$width.'&q=85';
    }

    public static function forProduct(?int $productId, ?string $slug = null, int $width = 700): string
    {
        $seed = $productId ?? crc32((string) $slug);
        $index = abs((int) $seed) % count(self::POOL);

        return self::url(self::POOL[$index], $width);
    }

    public static function resolve(?string $image, ?string $categoryName = null, ?int $seed = null, int $width = 700): string
    {
        if ($image && self::isUsableImage($image) && ! self::isDuplicateCategoryImage($image)) {
            if (str_starts_with($image, 'http')) {
                return $image;
            }

            return asset('storage/'.ltrim($image, '/'));
        }

        if ($seed !== null) {
            return self::forProduct($seed, null, $width);
        }

        return self::forCategory($categoryName, null, $width);
    }

    public static function secondary(?string $categoryName, ?int $productId = null, int $width = 700): string
    {
        if ($productId !== null) {
            $index = (abs($productId) + 11) % count(self::POOL);

            return self::url(self::POOL[$index], $width);
        }

        $key = self::normalizeCategory($categoryName);
        $photoId = self::BY_CATEGORY[$key] ?? self::DEFAULT;
        $index = (array_search($photoId, self::POOL, true) + 5) % count(self::POOL);

        return self::url(self::POOL[$index], $width);
    }

    public static function gallery(?string $image, ?string $categoryName = null, ?int $seed = null, int $width = 1000): array
    {
        $main = self::resolve($image, $categoryName, $seed, $width);
        $secondary = $seed !== null
            ? self::secondary($categoryName, $seed, $width)
            : self::secondary($categoryName, null, $width);

        return collect([$main, $secondary])
            ->unique()
            ->values()
            ->all();
    }

    public static function forCategory(?string $categoryName, ?int $seed = null, int $width = 700): string
    {
        if ($seed !== null) {
            return self::forProduct($seed, null, $width);
        }

        $key = self::normalizeCategory($categoryName);

        if ($key && isset(self::BY_CATEGORY[$key])) {
            return self::url(self::BY_CATEGORY[$key], $width);
        }

        return self::url(self::DEFAULT, $width);
    }

    public static function categoryMap(): array
    {
        return collect(self::BY_CATEGORY)
            ->mapWithKeys(fn ($photoId, $name) => [$name => self::url($photoId, 500)])
            ->all();
    }

    private static function normalizeCategory(?string $categoryName): ?string
    {
        if (! $categoryName) {
            return null;
        }

        foreach (array_keys(self::BY_CATEGORY) as $name) {
            if (mb_strtolower($name) === mb_strtolower($categoryName)) {
                return $name;
            }
        }

        return $categoryName;
    }

    private static function isUsableImage(string $image): bool
    {
        return ! str_contains($image, 'via.placeholder.com')
            && ! str_contains($image, 'placeholder.com');
    }

    /** Ảnh cũ gán theo danh mục (trùng) — cần thay bằng ảnh theo sản phẩm */
    private static function isDuplicateCategoryImage(string $image): bool
    {
        foreach (self::BY_CATEGORY as $photoId) {
            if (str_contains($image, $photoId)) {
                return true;
            }
        }

        return false;
    }
}
