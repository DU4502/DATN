<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ProductCatalog
{
    public const SKU_PREFIX = 'CD';

    /** @var array<string, string> */
    public const CATEGORY_CODES = [
        'Trà Sữa' => 'TS',
        'Cà Phê' => 'CF',
        'Sinh Tố' => 'ST',
        'Nước Ép' => 'NE',
        'Trà Trái Cây' => 'TC',
        'Soda' => 'SD',
        'Đá Xay' => 'DX',
        'Matcha' => 'MC',
    ];

    /** @var array<string, string> */
    public const CATEGORY_DESCRIPTIONS = [
        'Trà Sữa' => 'Trà sữa pha tươi, vị béo nhẹ, dễ uống mọi lúc trong ngày.',
        'Cà Phê' => 'Cà phê rang xay, thơm đậm, giữ trọn hương vị từ hạt cà phê.',
        'Sinh Tố' => 'Sinh tố trái cây tươi, xay mịn, mát lạnh và bổ dưỡng.',
        'Nước Ép' => 'Nước ép nguyên chất, không pha loãng, vị trái cây tự nhiên.',
        'Trà Trái Cây' => 'Trà trái cây thanh mát, hương thơm nhẹ, giải khát hiệu quả.',
        'Soda' => 'Soda có gas, vị chua ngọt sảng khoái, uống cực đã khi thức uống.',
        'Đá Xay' => 'Đồ uống đá xay mịn, mát lạnh, vị ngọt cân bằng và dễ uống.',
        'Matcha' => 'Matcha thơm vị trà xanh, béo nhẹ, hậu vị thanh và dễ uống.',
    ];

    /**
     * @var list<array{name: string, category: string, sku: string, slug: string, description: string, price: float}>
     */
    public const ITEMS = [
        ['name' => 'Trà Sữa Trân Châu Đường Đen', 'category' => 'Trà Sữa', 'sku' => 'CD-TS-001', 'slug' => 'tra-sua-tran-chau-duong-den', 'description' => 'Trà sữa đậm vị, trân châu đường đen dai mềm, ngọt vừa phải dễ uống.', 'price' => 45000],
        ['name' => 'Trà Sữa Khoai Môn', 'category' => 'Trà Sữa', 'sku' => 'CD-TS-002', 'slug' => 'tra-sua-khoai-mon', 'description' => 'Vị khoai môn béo thơm, sữa tươi mịn, ly trà sữa ấm áp cho mọi khẩu vị.', 'price' => 42000],
        ['name' => 'Trà Sữa Thái Xanh', 'category' => 'Trà Sữa', 'sku' => 'CD-TS-003', 'slug' => 'tra-sua-thai-xanh', 'description' => 'Trà Thái xanh thơm, sữa béo cân bằng, vị chua ngọt thanh mát.', 'price' => 40000],
        ['name' => 'Trà Sữa Oolong Kem Cheese', 'category' => 'Trà Sữa', 'sku' => 'CD-TS-004', 'slug' => 'tra-sua-oolong-kem-cheese', 'description' => 'Trà oolong thanh kết hợp lớp kem cheese béo mặn, hòa vị độc đáo.', 'price' => 55000],
        ['name' => 'Trà Sữa Socola', 'category' => 'Trà Sữa', 'sku' => 'CD-TS-005', 'slug' => 'tra-sua-socola', 'description' => 'Cacao đậm hòa sữa tươi, vị chocolate ngọt nhẹ, thơm béo dễ thích.', 'price' => 43000],
        ['name' => 'Cà Phê Sữa Đá', 'category' => 'Cà Phê', 'sku' => 'CD-CF-001', 'slug' => 'ca-phe-sua-da', 'description' => 'Cà phê phin đậm vị, sữa đặc béo ngậy, ly sữa đá quen thuộc mọi buổi sáng.', 'price' => 32000],
        ['name' => 'Cà Phê Đen Đá', 'category' => 'Cà Phê', 'sku' => 'CD-CF-002', 'slug' => 'ca-phe-den-da', 'description' => 'Cà phê đen nguyên chất, đắng nhẹ, thơm hạt rang, tỉnh táo suốt ngày dài.', 'price' => 25000],
        ['name' => 'Bạc Xỉu Đá', 'category' => 'Cà Phê', 'sku' => 'CD-CF-003', 'slug' => 'bac-xiu-da', 'description' => 'Nhiều sữa hơn cà phê, vị ngọt béo êm, phù hợp người thích uống nhẹ.', 'price' => 30000],
        ['name' => 'Cà Phê Muối', 'category' => 'Cà Phê', 'sku' => 'CD-CF-004', 'slug' => 'ca-phe-muoi', 'description' => 'Cà phê pha cùng lớp kem muối, vị mặn ngọt hài hòa, lạ miệng mà cuốn.', 'price' => 48000],
        ['name' => 'Cà Phê Ủ Lạnh', 'category' => 'Cà Phê', 'sku' => 'CD-CF-005', 'slug' => 'ca-phe-u-lanh', 'description' => 'Cà phê ủ lạnh 12 tiếng, vị êm, ít đắng, hương thơm tinh tế.', 'price' => 52000],
        ['name' => 'Sinh Tố Bơ', 'category' => 'Sinh Tố', 'sku' => 'CD-ST-001', 'slug' => 'sinh-to-bo', 'description' => 'Bơ sáp chín mọng xay nhuyễn, béo ngậy, bổ dưỡng và no lâu.', 'price' => 48000],
        ['name' => 'Sinh Tố Dâu', 'category' => 'Sinh Tố', 'sku' => 'CD-ST-002', 'slug' => 'sinh-to-dau', 'description' => 'Dâu tươi chín ngọt, xay mịn với sữa, vị chua ngọt thanh mát.', 'price' => 45000],
        ['name' => 'Sinh Tố Xoài', 'category' => 'Sinh Tố', 'sku' => 'CD-ST-003', 'slug' => 'sinh-to-xoai', 'description' => 'Xoài cát chín ngọt, thơm nồng, ly sinh tố nhiệt đới đậm đà.', 'price' => 42000],
        ['name' => 'Sinh Tố Việt Quất', 'category' => 'Sinh Tố', 'sku' => 'CD-ST-004', 'slug' => 'sinh-to-viet-quat', 'description' => 'Việt quất tươi chua nhẹ, giàu vitamin, vị mát lạnh sảng khoái.', 'price' => 50000],
        ['name' => 'Sinh Tố Chuối', 'category' => 'Sinh Tố', 'sku' => 'CD-ST-005', 'slug' => 'sinh-to-chuoi', 'description' => 'Chuối chín thơm bùi, béo nhẹ, năng lượng tốt cho buổi chiều.', 'price' => 38000],
        ['name' => 'Nước Ép Cam', 'category' => 'Nước Ép', 'sku' => 'CD-NE-001', 'slug' => 'nuoc-ep-cam', 'description' => 'Cam tươi ép nguyên chất, giàu vitamin C, vị chua ngọt tự nhiên.', 'price' => 35000],
        ['name' => 'Nước Ép Dưa Hấu', 'category' => 'Nước Ép', 'sku' => 'CD-NE-002', 'slug' => 'nuoc-ep-dua-hau', 'description' => 'Dưa hấu mọng nước, ép lạnh, giải nhiệt nhanh ngày nắng nóng.', 'price' => 30000],
        ['name' => 'Nước Ép Thơm', 'category' => 'Nước Ép', 'sku' => 'CD-NE-003', 'slug' => 'nuoc-ep-thom', 'description' => 'Thơm chín ngọt, hương nồng đặc trưng, thanh mát dễ uống.', 'price' => 36000],
        ['name' => 'Nước Ép Cà Rốt', 'category' => 'Nước Ép', 'sku' => 'CD-NE-004', 'slug' => 'nuoc-ep-ca-rot', 'description' => 'Cà rốt tươi ép xong uống liền, vị ngọt nhẹ, tốt cho sức khỏe.', 'price' => 32000],
        ['name' => 'Nước Ép Tắc', 'category' => 'Nước Ép', 'sku' => 'CD-NE-005', 'slug' => 'nuoc-ep-tac', 'description' => 'Tắc chua thanh, thêm chút đường phèn, giải khát cực đã.', 'price' => 28000],
        ['name' => 'Trà Đào Cam Sả', 'category' => 'Trà Trái Cây', 'sku' => 'CD-TC-001', 'slug' => 'tra-dao-cam-sa', 'description' => 'Trà đào thơm, cam sả tươi, vị chua ngọt mát, hương thơm dễ chịu.', 'price' => 40000],
        ['name' => 'Trà Vải', 'category' => 'Trà Trái Cây', 'sku' => 'CD-TC-002', 'slug' => 'tra-vai', 'description' => 'Trà vải ngọt thanh, miếng vải tươi giòn, mùa hè giải nhiệt tuyệt vời.', 'price' => 38000],
        ['name' => 'Trà Dâu', 'category' => 'Trà Trái Cây', 'sku' => 'CD-TC-003', 'slug' => 'tra-dau', 'description' => 'Trà xanh nhẹ pha dâu tươi, vị chua ngọt, màu hồng bắt mắt.', 'price' => 42000],
        ['name' => 'Trà Xoài', 'category' => 'Trà Trái Cây', 'sku' => 'CD-TC-004', 'slug' => 'tra-xoai', 'description' => 'Xoài chín ngọt kết hợp trà thanh, hương nhiệt đới rõ ràng.', 'price' => 40000],
        ['name' => 'Trà Nhiệt Đới', 'category' => 'Trà Trái Cây', 'sku' => 'CD-TC-005', 'slug' => 'tra-nhiet-doi', 'description' => 'Hỗn hợp trái cây nhiệt đới, trà mát, vị ngọt thanh đa tầng.', 'price' => 44000],
        ['name' => 'Soda Chanh Dây', 'category' => 'Soda', 'sku' => 'CD-SD-001', 'slug' => 'soda-chanh-day', 'description' => 'Soda có gas, chanh dây chua ngọt, bọt sủi tươi mát từng ngụm.', 'price' => 36000],
        ['name' => 'Soda Blue Curacao', 'category' => 'Soda', 'sku' => 'CD-SD-002', 'slug' => 'soda-blue-curacao', 'description' => 'Soda màu xanh biển, vị cam nhẹ, ly nước bắt mắt, refresh tức thì.', 'price' => 38000],
        ['name' => 'Soda Việt Quất', 'category' => 'Soda', 'sku' => 'CD-SD-003', 'slug' => 'soda-viet-quat', 'description' => 'Việt quất chua nhẹ, soda sủi bọt, vị mát lạnh khó cưỡng.', 'price' => 40000],
        ['name' => 'Soda Dưa Leo', 'category' => 'Soda', 'sku' => 'CD-SD-004', 'slug' => 'soda-dua-leo', 'description' => 'Dưa leo tươi mát, soda nhẹ nhàng, giải khát cực nhanh.', 'price' => 32000],
        ['name' => 'Soda Cam', 'category' => 'Soda', 'sku' => 'CD-SD-005', 'slug' => 'soda-cam', 'description' => 'Cam tươi vắt, soda có gas, vị chua ngọt sảng khoái cả ngày.', 'price' => 34000],
        ['name' => 'Matcha Đá Xay', 'category' => 'Đá Xay', 'sku' => 'CD-DX-001', 'slug' => 'matcha-da-xay', 'description' => 'Matcha đá xay mịn, phủ kem béo, vị trà xanh rõ và hậu ngọt dịu.', 'price' => 52000],
        ['name' => 'Chocolate Đá Xay', 'category' => 'Đá Xay', 'sku' => 'CD-DX-002', 'slug' => 'chocolate-da-xay', 'description' => 'Chocolate đậm vị xay cùng đá, mát lạnh, phù hợp khách thích vị ngọt béo.', 'price' => 50000],
    ];

    public static function findByName(string $name): ?array
    {
        foreach (self::ITEMS as $item) {
            if (mb_strtolower($item['name']) === mb_strtolower($name)) {
                return $item;
            }
        }

        return null;
    }

    public static function findBySku(string $sku): ?array
    {
        foreach (self::ITEMS as $item) {
            if ($item['sku'] === $sku) {
                return $item;
            }
        }

        return null;
    }

    public static function descriptionFor(string $name, ?string $categoryName = null): string
    {
        if ($item = self::findByName($name)) {
            return $item['description'];
        }

        $categoryName = self::normalizeCategoryName($categoryName);

        return self::CATEGORY_DESCRIPTIONS[$categoryName]
            ?? 'Đồ uống pha tươi mỗi ngày, vị cân bằng, dễ uống và giao nhanh tận nơi.';
    }

    public static function isPlaceholderDescription(?string $text): bool
    {
        if (! $text || mb_strlen(trim($text)) < 8) {
            return true;
        }

        $latinPatterns = [
            '/\b(lorem|ipsum|dolor|amet|consectetur|adipisci|elit|voluptat|repudiandae|dicta)\b/i',
            '/\b(ut|ab|cum|sed|qui|quo|aut|non|vel|eum|id|at)\s+(laborum|amet|dolor|animi|omnis)/i',
        ];

        foreach ($latinPatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        return preg_match('/[àáạảãâầấậẩăằắặẳèéẹẻẽêềếệểìíịỉĩòóọỏõôồốộổơờớợởùúụủũưừứựửỳýỵỷỹđ]/iu', $text) !== 1;
    }

    public static function categoryCode(?string $categoryName): string
    {
        if (! $categoryName) {
            return 'PR';
        }

        foreach (self::CATEGORY_CODES as $name => $code) {
            if (mb_strtolower($name) === mb_strtolower($categoryName)) {
                return $code;
            }
        }

        return 'PR';
    }

    private static function normalizeCategoryName(?string $categoryName): ?string
    {
        if (! $categoryName) {
            return null;
        }

        foreach (array_keys(self::CATEGORY_CODES) as $name) {
            if (mb_strtolower($name) === mb_strtolower($categoryName)) {
                return $name;
            }
        }

        return $categoryName;
    }

    public static function makeSlug(string $name): string
    {
        return Str::slug($name);
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = self::makeSlug($name);
        $slug = $base;
        $counter = 2;

        while (Product::query()
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    public static function nextSku(?string $categoryName): string
    {
        $code = self::categoryCode($categoryName);
        $prefix = self::SKU_PREFIX.'-'.$code.'-';

        if (! Schema::hasColumn('products', 'sku')) {
            return $prefix.'001';
        }

        $latest = Product::query()
            ->where('sku', 'like', $prefix.'%')
            ->orderByDesc('sku')
            ->value('sku');

        $next = 1;
        if ($latest && preg_match('/-(\d+)$/', $latest, $matches)) {
            $next = (int) $matches[1] + 1;
        }

        return $prefix.str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }

    public static function codesFor(string $name, ?string $categoryName, ?int $ignoreId = null): array
    {
        if ($item = self::findByName($name)) {
            return [
                'sku' => $item['sku'],
                'slug' => $item['slug'],
                'description' => $item['description'],
            ];
        }

        return [
            'sku' => self::nextSku($categoryName),
            'slug' => self::uniqueSlug($name, $ignoreId),
            'description' => self::descriptionFor($name, $categoryName),
        ];
    }
}
