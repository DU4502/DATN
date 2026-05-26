<?php

namespace App\Support;

class ShippingFee
{
    private const DEFAULT_DISTANCE = 3.5;

    private const DISTANCE_TIERS = [
        ['value' => 1.5, 'max' => 2.0, 'label' => '0 - 2 km', 'description' => 'Gần cửa hàng', 'base_fee' => 10000],
        ['value' => 3.5, 'max' => 5.0, 'label' => '2 - 5 km', 'description' => 'Trong khu vực lân cận', 'base_fee' => 15000],
        ['value' => 6.5, 'max' => 8.0, 'label' => '5 - 8 km', 'description' => 'Khu vực nội thành', 'base_fee' => 22000],
        ['value' => 10.0, 'max' => 12.0, 'label' => '8 - 12 km', 'description' => 'Xa hơn trung tâm', 'base_fee' => 30000],
        ['value' => 13.5, 'max' => 15.0, 'label' => '12 - 15 km', 'description' => 'Ngoại khu gần', 'base_fee' => 40000],
        ['value' => 18.0, 'max' => 20.0, 'label' => '15 - 20 km', 'description' => 'Ngoại khu xa', 'base_fee' => 50000],
    ];

    private const METHODS = [
        'standard' => [
            'label' => 'Giao tiêu chuẩn',
            'description' => 'Dự kiến 30-45 phút tùy khu vực.',
            'surcharge' => 0,
            'eta' => '30-45 phút',
        ],
        'fast' => [
            'label' => 'Giao nhanh',
            'description' => 'Ưu tiên chuẩn bị đơn, phù hợp khi cần gấp.',
            'surcharge' => 8000,
            'eta' => '20-30 phút',
        ],
    ];

    private const ESTIMATION_RULES = [
        [
            'distance' => 1.5,
            'label' => 'Gần cửa hàng',
            'detail' => 'khu vực trung tâm',
            'keywords' => ['hoàn kiếm', 'hoan kiem', 'tràng tiền', 'trang tien', 'phố cổ', 'pho co', 'cửa nam', 'cua nam'],
        ],
        [
            'distance' => 3.5,
            'label' => 'Khu vực gần',
            'detail' => 'nội thành gần cửa hàng',
            'keywords' => ['ba đình', 'ba dinh', 'đống đa', 'dong da', 'hai bà trưng', 'hai ba trung', 'tây hồ', 'tay ho'],
        ],
        [
            'distance' => 6.5,
            'label' => 'Khu vực nội thành',
            'detail' => 'nội thành mở rộng',
            'keywords' => ['cầu giấy', 'cau giay', 'thanh xuân', 'thanh xuan', 'long biên', 'long bien', 'hoàng mai', 'hoang mai', 'hà nội', 'ha noi'],
        ],
        [
            'distance' => 10.0,
            'label' => 'Khu vực xa hơn',
            'detail' => 'xa trung tâm',
            'keywords' => ['hà đông', 'ha dong', 'nam từ liêm', 'nam tu liem', 'bắc từ liêm', 'bac tu liem', 'gia lâm', 'gia lam'],
        ],
        [
            'distance' => 13.5,
            'label' => 'Ngoại khu gần',
            'detail' => 'ngoại thành gần',
            'keywords' => ['đông anh', 'dong anh', 'thanh trì', 'thanh tri', 'hoài đức', 'hoai duc', 'đan phượng', 'dan phuong'],
        ],
        [
            'distance' => 18.0,
            'label' => 'Khu vực xa',
            'detail' => 'cần xác nhận tuyến giao',
            'keywords' => ['hồ chí minh', 'ho chi minh', 'tp.hcm', 'tphcm', 'đà nẵng', 'da nang', 'cần thơ', 'can tho', 'thanh hóa', 'thanh hoa', 'khác', 'khac'],
        ],
    ];

    public static function distanceOptions(): array
    {
        return self::DISTANCE_TIERS;
    }

    public static function methods(): array
    {
        return self::METHODS;
    }

    public static function estimationRules(): array
    {
        return self::ESTIMATION_RULES;
    }

    public static function quoteForAddress(?string $address, ?string $area, string $method = 'standard'): array
    {
        $estimate = self::estimateDistanceForAddress($address, $area);

        return array_merge(
            self::calculate($estimate['distance_km'], $method),
            [
                'estimate_label' => $estimate['label'],
                'estimate_detail' => $estimate['detail'],
            ]
        );
    }

    public static function calculate(float|int|string|null $distanceKm = null, string $method = 'standard'): array
    {
        $distance = self::normalizeDistance($distanceKm);
        $tier = self::tierForDistance($distance);
        $methodConfig = self::METHODS[$method] ?? self::METHODS['standard'];
        $methodKey = array_key_exists($method, self::METHODS) ? $method : 'standard';

        return [
            'distance_km' => $distance,
            'distance_label' => $tier['label'],
            'method' => $methodKey,
            'method_label' => $methodConfig['label'],
            'method_eta' => $methodConfig['eta'],
            'base_fee' => $tier['base_fee'],
            'method_fee' => $methodConfig['surcharge'],
            'total_fee' => $tier['base_fee'] + $methodConfig['surcharge'],
        ];
    }

    public static function formatCurrency(int|float $amount): string
    {
        return number_format((int) $amount, 0, ',', '.') . 'đ';
    }

    private static function estimateDistanceForAddress(?string $address, ?string $area): array
    {
        $text = self::normalizeText(trim(($address ?? '') . ' ' . ($area ?? '')));

        if ($text !== '') {
            foreach (self::ESTIMATION_RULES as $rule) {
                foreach ($rule['keywords'] as $keyword) {
                    if (str_contains($text, self::normalizeText($keyword))) {
                        return [
                            'distance_km' => $rule['distance'],
                            'label' => $rule['label'],
                            'detail' => $rule['detail'],
                        ];
                    }
                }
            }
        }

        return [
            'distance_km' => self::DEFAULT_DISTANCE,
            'label' => $text === '' ? 'Chờ địa chỉ' : 'Ước tính mặc định',
            'detail' => $text === '' ? 'chưa có địa chỉ cụ thể' : 'cần nhân viên xác nhận lại',
        ];
    }

    private static function normalizeText(string $value): string
    {
        $value = mb_strtolower($value, 'UTF-8');
        $search = ['đ', 'á', 'à', 'ả', 'ã', 'ạ', 'ă', 'ắ', 'ằ', 'ẳ', 'ẵ', 'ặ', 'â', 'ấ', 'ầ', 'ẩ', 'ẫ', 'ậ', 'é', 'è', 'ẻ', 'ẽ', 'ẹ', 'ê', 'ế', 'ề', 'ể', 'ễ', 'ệ', 'í', 'ì', 'ỉ', 'ĩ', 'ị', 'ó', 'ò', 'ỏ', 'õ', 'ọ', 'ô', 'ố', 'ồ', 'ổ', 'ỗ', 'ộ', 'ơ', 'ớ', 'ờ', 'ở', 'ỡ', 'ợ', 'ú', 'ù', 'ủ', 'ũ', 'ụ', 'ư', 'ứ', 'ừ', 'ử', 'ữ', 'ự', 'ý', 'ỳ', 'ỷ', 'ỹ', 'ỵ'];
        $replace = ['d', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'y', 'y', 'y', 'y', 'y'];

        return str_replace($search, $replace, $value);
    }

    private static function normalizeDistance(float|int|string|null $distanceKm): float
    {
        $distance = is_numeric($distanceKm) ? (float) $distanceKm : self::DEFAULT_DISTANCE;

        return round(min(max($distance, 0.5), 20), 1);
    }

    private static function tierForDistance(float $distanceKm): array
    {
        foreach (self::DISTANCE_TIERS as $tier) {
            if ($distanceKm <= $tier['max']) {
                return $tier;
            }
        }

        return self::DISTANCE_TIERS[array_key_last(self::DISTANCE_TIERS)];
    }
}
