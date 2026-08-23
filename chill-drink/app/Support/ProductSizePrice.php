<?php

namespace App\Support;

class ProductSizePrice
{
    public static function unitPrice(int $basePrice, ?int $storedSizePrice, int $fallbackExtra = 0): int
    {
        $basePrice = max(0, $basePrice);
        $fallbackExtra = max(0, $fallbackExtra);

        if ($storedSizePrice === null) {
            return max(0, $basePrice + $fallbackExtra);
        }

        $storedSizePrice = max(0, $storedSizePrice);

        if ($storedSizePrice === 0) {
            return $basePrice;
        }

        if ($storedSizePrice < $basePrice) {
            return max(0, $basePrice + $storedSizePrice);
        }

        return $storedSizePrice;
    }

    public static function sizeExtra(int $basePrice, ?int $storedSizePrice, int $fallbackExtra = 0): int
    {
        return max(0, self::unitPrice($basePrice, $storedSizePrice, $fallbackExtra) - max(0, $basePrice));
    }
}
