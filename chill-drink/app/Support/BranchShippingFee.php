<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BranchShippingFee
{
    public const MAX_DISTANCE_KM = 15.0;

    public const DEFAULT_FREE_KM = 5.0;

    public const DEFAULT_FAST_SURCHARGE = 8000;

    public const DEFAULT_TIERS = [
        ['max_cups' => 5, 'per_km_fee' => 5000],
        ['max_cups' => 10, 'per_km_fee' => 6000],
        ['max_cups' => 15, 'per_km_fee' => 7000],
        ['max_cups' => null, 'per_km_fee' => 8000],
    ];

    private static array $settingsCache = [];

    public static function defaults(): array
    {
        return [
            'free_km' => self::DEFAULT_FREE_KM,
            'max_distance_km' => self::MAX_DISTANCE_KM,
            'fast_surcharge' => self::DEFAULT_FAST_SURCHARGE,
            'tiers' => self::DEFAULT_TIERS,
        ];
    }

    /**
     * Trả về cấu hình đã lưu của chi nhánh. Nếu chi nhánh chưa được cấu hình,
     * trả null để code ShippingFee cũ tiếp tục hoạt động như trước.
     */
    public static function settingsForBranch(int $branchId): ?array
    {
        if ($branchId <= 0 || ! Schema::hasTable('branch_shipping_fee_settings')) {
            return null;
        }

        if (array_key_exists($branchId, self::$settingsCache)) {
            return self::$settingsCache[$branchId];
        }

        $row = DB::table('branch_shipping_fee_settings')
            ->where('branch_id', $branchId)
            ->first();

        if (! $row) {
            return self::$settingsCache[$branchId] = null;
        }

        $tiers = json_decode((string) ($row->tiers ?? '[]'), true);
        if (! is_array($tiers) || $tiers === []) {
            $tiers = self::DEFAULT_TIERS;
        }

        $settings = [
            'branch_id' => $branchId,
            'free_km' => round((float) ($row->free_km ?? self::DEFAULT_FREE_KM), 2),
            'max_distance_km' => self::MAX_DISTANCE_KM,
            'fast_surcharge' => max(0, (int) ($row->fast_surcharge ?? self::DEFAULT_FAST_SURCHARGE)),
            'tiers' => self::normalizeTiers($tiers),
        ];

        return self::$settingsCache[$branchId] = $settings;
    }

    public static function forgetBranch(int $branchId): void
    {
        unset(self::$settingsCache[$branchId]);
    }

    /**
     * Hook dùng bởi App\Support\ShippingFee::calculate().
     * Chỉ override khi request hiện tại xác định được branch_id VÀ chi nhánh đã có cấu hình riêng.
     */
    public static function quoteFromCurrentContext(float|int|string|null $distanceKm, string $method = 'standard'): ?array
    {
        $branchId = self::resolveBranchId();
        if (! $branchId) {
            return null;
        }

        $settings = self::settingsForBranch($branchId);
        if ($settings === null) {
            return null;
        }

        $distance = is_numeric($distanceKm) ? (float) $distanceKm : 0.0;
        $distance = round(max(0.0, min($distance, self::MAX_DISTANCE_KM)), 2);
        $cupCount = max(1, self::resolveCupCount());

        $tier = self::tierForCupCount($settings['tiers'], $cupCount);
        $chargeableKm = max(0.0, $distance - (float) $settings['free_km']);
        $baseFee = (int) round($chargeableKm * (int) $tier['per_km_fee']);

        $methodKey = strtolower(trim($method));
        $isFast = in_array($methodKey, ['fast', 'express', 'quick', 'priority'], true);
        $methodFee = $isFast ? (int) $settings['fast_surcharge'] : 0;

        return [
            'distance_km' => $distance,
            'distance_label' => rtrim(rtrim(number_format($distance, 2, '.', ''), '0'), '.') . ' km',
            'method' => $isFast ? 'fast' : 'standard',
            'method_label' => $isFast ? 'Giao nhanh' : 'Giao tiêu chuẩn',
            'method_eta' => $isFast ? 'Ưu tiên' : 'Tiêu chuẩn',
            'base_fee' => $baseFee,
            'method_fee' => $methodFee,
            'total_fee' => $baseFee + $methodFee,
            'branch_id' => $branchId,
            'free_km' => (float) $settings['free_km'],
            'max_distance_km' => self::MAX_DISTANCE_KM,
            'chargeable_km' => round($chargeableKm, 2),
            'per_km_fee' => (int) $tier['per_km_fee'],
            'cup_count' => $cupCount,
        ];
    }

    public static function preview(int $branchId, float $distanceKm, int $cupCount, string $method = 'standard'): array
    {
        $settings = self::settingsForBranch($branchId) ?? array_merge(self::defaults(), ['branch_id' => $branchId]);
        $distance = round(max(0.0, min($distanceKm, self::MAX_DISTANCE_KM)), 2);
        $cupCount = max(1, $cupCount);
        $tier = self::tierForCupCount($settings['tiers'], $cupCount);
        $chargeableKm = max(0.0, $distance - (float) $settings['free_km']);
        $baseFee = (int) round($chargeableKm * (int) $tier['per_km_fee']);
        $isFast = in_array(strtolower(trim($method)), ['fast', 'express', 'quick', 'priority'], true);
        $fastFee = $isFast ? (int) $settings['fast_surcharge'] : 0;

        return [
            'distance_km' => $distance,
            'cup_count' => $cupCount,
            'free_km' => (float) $settings['free_km'],
            'chargeable_km' => round($chargeableKm, 2),
            'per_km_fee' => (int) $tier['per_km_fee'],
            'base_fee' => $baseFee,
            'fast_surcharge' => $fastFee,
            'total_fee' => $baseFee + $fastFee,
        ];
    }

    public static function normalizeTiers(array $tiers): array
    {
        $normalized = collect($tiers)
            ->map(function ($tier): array {
                $tier = is_array($tier) ? $tier : (array) $tier;
                $maxCups = $tier['max_cups'] ?? null;

                return [
                    'max_cups' => $maxCups === null || $maxCups === '' ? null : max(1, (int) $maxCups),
                    'per_km_fee' => max(0, (int) ($tier['per_km_fee'] ?? 0)),
                ];
            })
            ->values()
            ->all();

        return $normalized !== [] ? $normalized : self::DEFAULT_TIERS;
    }

    private static function tierForCupCount(array $tiers, int $cupCount): array
    {
        foreach (self::normalizeTiers($tiers) as $tier) {
            if ($tier['max_cups'] === null || $cupCount <= (int) $tier['max_cups']) {
                return $tier;
            }
        }

        return self::normalizeTiers($tiers)[array_key_last(self::normalizeTiers($tiers))];
    }

    private static function resolveBranchId(): ?int
    {
        try {
            $candidates = [
                request()?->input('branch_id'),
                request()?->query('branch_id'),
                session('guest_checkout.branch_id'),
                session('checkout_branch_id'),
                session('selected_branch_id'),
            ];

            foreach ($candidates as $candidate) {
                if (is_numeric($candidate) && (int) $candidate > 0) {
                    return (int) $candidate;
                }
            }
        } catch (\Throwable) {
            // Không có HTTP/session context: giữ nguyên logic ShippingFee cũ.
        }

        return null;
    }

    private static function resolveCupCount(): int
    {
        try {
            foreach (['shipping_cup_count', 'cup_count', 'cups'] as $key) {
                $value = request()?->input($key);
                if (is_numeric($value) && (int) $value > 0) {
                    return (int) $value;
                }
            }

            $cart = session('cart', []);
            if (! is_array($cart) || $cart === []) {
                return 1;
            }

            $selectedKeys = session('checkout_cart_keys');
            if (is_array($selectedKeys) && $selectedKeys !== []) {
                $selectedLookup = array_fill_keys(array_map('strval', $selectedKeys), true);
                $cart = array_filter(
                    $cart,
                    static fn ($item, $key) => isset($selectedLookup[(string) $key]),
                    ARRAY_FILTER_USE_BOTH
                );
            }

            $sum = 0;
            foreach ($cart as $item) {
                if (is_array($item)) {
                    $sum += max(0, (int) ($item['quantity'] ?? $item['qty'] ?? 0));
                } elseif (is_object($item)) {
                    $sum += max(0, (int) ($item->quantity ?? $item->qty ?? 0));
                }
            }

            return max(1, $sum);
        } catch (\Throwable) {
            return 1;
        }
    }
}
