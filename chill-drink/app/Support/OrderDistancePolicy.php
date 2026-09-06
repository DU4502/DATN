<?php

namespace App\Support;

use App\Models\Branch;
use App\Services\DeliveryRoutingService;

class OrderDistancePolicy
{
    public const MAX_DISTANCE_KM = 15.0;

    public static function isInsideServiceRadius(float $distanceKm): bool
    {
        return $distanceKm >= 0 && $distanceKm <= self::MAX_DISTANCE_KM;
    }

    /**
     * Khoảng cách nghiệp vụ chuẩn: CHỈ chấp nhận tuyến đường thật từ routing engine.
     * Nếu routing server lỗi/fallback Haversine thì trả null (fail closed) để checkout
     * không vô tình nhận một địa chỉ thực tế vượt quá 15 km.
     */
    public static function distanceFromBranch(Branch $branch, mixed $latitude, mixed $longitude): ?float
    {
        $route = self::verifiedRouteFromBranch($branch, $latitude, $longitude);

        return $route ? round(((float) ($route['distance_m'] ?? 0)) / 1000, 3) : null;
    }

    public static function verifiedRouteFromBranch(Branch $branch, mixed $latitude, mixed $longitude): ?array
    {
        $route = self::routeFromBranch($branch, $latitude, $longitude);

        if (! $route || (bool) ($route['fallback'] ?? true)) {
            return null;
        }

        if (! is_numeric($route['distance_m'] ?? null) || (float) $route['distance_m'] < 0) {
            return null;
        }

        return $route;
    }

    /**
     * Route thô vẫn được giữ cho map/hiển thị. Business rule phải dùng
     * verifiedRouteFromBranch()/distanceFromBranch().
     */
    public static function routeFromBranch(Branch $branch, mixed $latitude, mixed $longitude): ?array
    {
        if ($latitude === null || $longitude === null || $latitude === '' || $longitude === '') {
            return null;
        }

        if ($branch->latitude === null || $branch->longitude === null) {
            return null;
        }

        return app(DeliveryRoutingService::class)->route(
            (float) $branch->latitude,
            (float) $branch->longitude,
            (float) $latitude,
            (float) $longitude
        );
    }

    public static function message(): string
    {
        return 'Địa chỉ giao hàng phải cách chi nhánh phục vụ không quá 15 km theo lộ trình đường bộ.';
    }

    public static function routingUnavailableMessage(): string
    {
        return 'Chưa xác minh được lộ trình đường bộ lúc này. Vui lòng thử lại sau ít phút.';
    }
}
