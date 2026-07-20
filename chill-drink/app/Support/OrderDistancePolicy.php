<?php

namespace App\Support;

use App\Models\Branch;

class OrderDistancePolicy
{
    public const MAX_DISTANCE_KM = 15.0;

    public static function isInsideServiceRadius(float $distanceKm): bool
    {
        return $distanceKm < self::MAX_DISTANCE_KM;
    }

    public static function distanceFromBranch(Branch $branch, mixed $latitude, mixed $longitude): ?float
    {
        if ($latitude === null || $longitude === null || $latitude === '' || $longitude === '') {
            return null;
        }

        if ($branch->latitude === null || $branch->longitude === null) {
            return null;
        }

        return $branch->distanceTo((float) $latitude, (float) $longitude);
    }

    public static function message(): string
    {
        return 'Địa chỉ giao hàng phải cách chi nhánh phục vụ dưới 15 km.';
    }
}
