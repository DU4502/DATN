<?php

namespace App\Support;

use App\Models\DeliveryFeeSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ShippingFee
{
    private const DEFAULT_DISTANCE = 3.5;

    private const SETTINGS_CACHE_KEY = 'delivery_fee_settings.current.v1';

    private const DEFAULT_SETTINGS = [
        'free_distance_km' => 5.0,
        'fast_surcharge' => 8000,
        'cup_tiers' => [
            ['min_cups' => 1, 'max_cups' => 5, 'price_per_km' => 5000],
            ['min_cups' => 6, 'max_cups' => 10, 'price_per_km' => 6000],
            ['min_cups' => 11, 'max_cups' => 15, 'price_per_km' => 7000],
            ['min_cups' => 16, 'max_cups' => null, 'price_per_km' => 8000],
        ],
    ];

    private const METHOD_META = [
        'standard' => [
            'label' => 'Giao tiêu chuẩn',
            'description' => 'Dự kiến 30-45 phút tùy khu vực.',
            'eta' => '30-45 phút',
        ],
        'fast' => [
            'label' => 'Giao nhanh',
            'description' => 'Ưu tiên chuẩn bị đơn, phù hợp khi cần gấp.',
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
            'detail' => 'gần chi nhánh (nội thành)',
            'keywords' => ['ba đình', 'ba dinh', 'đống đa', 'dong da', 'hai bà trưng', 'hai ba trung', 'tây hồ', 'tay ho', 'thanh hóa', 'thanh hoa', 'hạc thành', 'hac thanh', 'nông cống', 'nong cong', 'đông quang', 'dong quang', 'đông sơn', 'dong son', 'quảng thắng', 'quang thang', 'đông vinh', 'dong vinh'],
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
            'distance' => 13.5,
            'label' => 'Ngoại khu gần',
            'detail' => 'gần giới hạn phục vụ 15 km',
            'keywords' => ['hồ chí minh', 'ho chi minh', 'tp.hcm', 'tphcm', 'đà nẵng', 'da nang', 'cần thơ', 'can tho', 'khác', 'khac'],
        ],
    ];

    /**
     * Cấu hình phí giao hàng toàn hệ thống do Super Admin quản lý.
     */
    public static function settings(): array
    {
        try {
            return Cache::remember(self::SETTINGS_CACHE_KEY, now()->addMinutes(10), function (): array {
                if (! Schema::hasTable('delivery_fee_settings')) {
                    return self::DEFAULT_SETTINGS;
                }

                $row = DeliveryFeeSetting::query()->first();

                if (! $row) {
                    return self::DEFAULT_SETTINGS;
                }

                return self::normalizeSettings([
                    'free_distance_km' => $row->free_distance_km,
                    'fast_surcharge' => $row->fast_surcharge,
                    'cup_tiers' => $row->cup_tiers,
                ]);
            });
        } catch (Throwable) {
            return self::DEFAULT_SETTINGS;
        }
    }

    public static function clearSettingsCache(): void
    {
        Cache::forget(self::SETTINGS_CACHE_KEY);
    }

    public static function cupTiers(): array
    {
        return self::settings()['cup_tiers'];
    }

    public static function distanceOptions(): array
    {
        $freeKm = (float) self::settings()['free_distance_km'];
        $maxKm = (float) OrderDistancePolicy::MAX_DISTANCE_KM;
        $options = [];

        if ($freeKm > 0) {
            $options[] = [
                'value' => round($freeKm / 2, 1),
                'max' => $freeKm,
                'label' => '0 - '.self::formatDistance($freeKm).' km',
                'description' => 'Miễn phí giao hàng',
                'base_fee' => 0,
            ];
        }

        if ($freeKm < $maxKm) {
            $options[] = [
                'value' => round(($freeKm + $maxKm) / 2, 1),
                'max' => $maxKm,
                'label' => 'Trên '.self::formatDistance($freeKm).' - '.self::formatDistance($maxKm).' km',
                'description' => 'Tính theo km vượt ngưỡng và số lượng cốc',
                'base_fee' => null,
            ];
        }

        return $options;
    }

    public static function methods(): array
    {
        $settings = self::settings();

        return [
            'standard' => array_merge(self::METHOD_META['standard'], ['surcharge' => 0]),
            'fast' => array_merge(self::METHOD_META['fast'], ['surcharge' => (int) $settings['fast_surcharge']]),
        ];
    }

    public static function estimationRules(): array
    {
        return self::ESTIMATION_RULES;
    }

    public static function quoteForAddress(
        ?string $address,
        ?string $area,
        string $method = 'standard',
        int $cupCount = 1
    ): array {
        $estimate = self::estimateDistanceForAddress($address, $area);

        return array_merge(
            self::calculate($estimate['distance_km'], $method, $cupCount),
            [
                'estimate_label' => $estimate['label'],
                'estimate_detail' => $estimate['detail'],
            ]
        );
    }

    /**
     * Công thức mới:
     * - distance <= free_distance_km: miễn phí.
     * - phần distance vượt ngưỡng miễn phí * đơn giá/km theo bậc số cốc.
     * - phụ phí phương thức giao (nếu có) cộng sau cùng.
     */
    public static function calculate(
        float|int|string|null $distanceKm = null,
        string $method = 'standard',
        int|float|string|null $cupCount = 1
    ): array {
        // V27_BRANCH_SHIPPING_FEE_RUNTIME
        // Khi branch đã có cấu hình riêng, phí giao hàng lấy theo đúng branch đó.
        // Branch chưa cấu hình vẫn chạy nguyên logic cũ bên dưới để không phá dữ liệu hiện tại.
        try {
            $v27Args = func_get_args();
            $v27Distance = $distanceKm ?? ($v27Args[0] ?? null);
            $v27Method = $method ?? null;
            if (! is_string($v27Method) || $v27Method === '') {
                $v27Method = 'standard';
                foreach ($v27Args as $v27Arg) {
                    if (is_string($v27Arg) && in_array(strtolower($v27Arg), ['standard', 'fast', 'express', 'quick', 'priority'], true)) {
                        $v27Method = $v27Arg;
                        break;
                    }
                }
            }
            $v27BranchQuote = \App\Support\BranchShippingFee::quoteFromCurrentContext(
                $v27Distance,
                (string) $v27Method,
                $cupCount
            );
            if ($v27BranchQuote !== null) {
                return $v27BranchQuote;
            }
        } catch (\Throwable $v27BranchFeeError) {
            // Fallback xuống logic cũ nếu DB/migration chưa sẵn sàng.
        }
        $distance = self::normalizeDistance($distanceKm);
        $cups = max(1, (int) (is_numeric($cupCount) ? $cupCount : 1));
        $settings = self::settings();
        $freeDistance = min(max((float) $settings['free_distance_km'], 0), OrderDistancePolicy::MAX_DISTANCE_KM);
        $billableDistance = round(max(0, $distance - $freeDistance), 3);
        $cupTier = self::tierForCupCount($cups, $settings['cup_tiers']);
        $ratePerKm = max(0, (int) ($cupTier['price_per_km'] ?? 0));
        $baseFee = (int) round($billableDistance * $ratePerKm);
        $methods = self::methods();
        $methodConfig = $methods[$method] ?? $methods['standard'];
        $methodKey = array_key_exists($method, $methods) ? $method : 'standard';
        // "Miễn phí trong X km" nghĩa là tổng phí giao hàng = 0, kể cả khách chọn giao nhanh.
        // Phụ phí phương thức chỉ bắt đầu áp dụng khi đơn vượt ngưỡng miễn phí.
        $methodFee = $distance <= $freeDistance
            ? 0
            : max(0, (int) ($methodConfig['surcharge'] ?? 0));

        return [
            'distance_km' => $distance,
            'distance_label' => $distance <= $freeDistance
                ? 'Miễn phí trong '.self::formatDistance($freeDistance).' km'
                : self::formatDistance($billableDistance).' km tính phí',
            'method' => $methodKey,
            'method_label' => $methodConfig['label'],
            'method_eta' => $methodConfig['eta'],
            'cup_count' => $cups,
            'cup_tier_label' => self::tierLabel($cupTier),
            'free_distance_km' => $freeDistance,
            'billable_distance_km' => $billableDistance,
            'rate_per_km' => $ratePerKm,
            'base_fee' => $baseFee,
            'method_fee' => $methodFee,
            'total_fee' => $baseFee + $methodFee,
        ];
    }

    public static function formatCurrency(int|float $amount): string
    {
        return number_format((int) round($amount), 0, ',', '.') . 'đ';
    }

    public static function tierLabel(array $tier): string
    {
        $min = max(1, (int) ($tier['min_cups'] ?? 1));
        $max = isset($tier['max_cups']) && $tier['max_cups'] !== null
            ? max($min, (int) $tier['max_cups'])
            : null;

        return $max === null ? "Từ {$min} cốc" : "{$min} - {$max} cốc";
    }

    private static function tierForCupCount(int $cupCount, array $tiers): array
    {
        $normalized = self::normalizeCupTiers($tiers);

        foreach ($normalized as $tier) {
            $min = (int) $tier['min_cups'];
            $max = $tier['max_cups'] === null ? null : (int) $tier['max_cups'];

            if ($cupCount >= $min && ($max === null || $cupCount <= $max)) {
                return $tier;
            }
        }

        return $normalized[array_key_last($normalized)] ?? self::DEFAULT_SETTINGS['cup_tiers'][0];
    }

    private static function normalizeSettings(array $settings): array
    {
        return [
            'free_distance_km' => round(min(max((float) ($settings['free_distance_km'] ?? 5), 0), OrderDistancePolicy::MAX_DISTANCE_KM), 2),
            'fast_surcharge' => max(0, (int) ($settings['fast_surcharge'] ?? 8000)),
            'cup_tiers' => self::normalizeCupTiers(is_array($settings['cup_tiers'] ?? null) ? $settings['cup_tiers'] : []),
        ];
    }

    private static function normalizeCupTiers(array $tiers): array
    {
        $normalized = collect($tiers)
            ->map(function ($tier): ?array {
                if (! is_array($tier)) {
                    return null;
                }

                $min = max(1, (int) ($tier['min_cups'] ?? 1));
                $maxRaw = $tier['max_cups'] ?? null;
                $max = ($maxRaw === null || $maxRaw === '') ? null : max($min, (int) $maxRaw);

                return [
                    'min_cups' => $min,
                    'max_cups' => $max,
                    'price_per_km' => max(0, (int) ($tier['price_per_km'] ?? 0)),
                ];
            })
            ->filter()
            ->sortBy('min_cups')
            ->values()
            ->all();

        return $normalized !== [] ? $normalized : self::DEFAULT_SETTINGS['cup_tiers'];
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

        // Không clamp về 15 km: giá trị >15 phải được giữ nguyên để lớp policy
        // có thể chặn đúng thay vì che giấu dữ liệu/tuyến sai.
        return round(max($distance, 0), 3);
    }

    private static function formatDistance(float $distance): string
    {
        $formatted = number_format($distance, 2, ',', '.');

        return rtrim(rtrim($formatted, '0'), ',');
    }
}
