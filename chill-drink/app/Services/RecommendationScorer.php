<?php

namespace App\Services;

use App\Models\Product;

class RecommendationScorer
{
    public const MAX_SCORE = 75;

    private const WEATHER_SCORES = [
        'raining' => [
            'hot' => 40,
            'both' => 30,
            'cold' => 15,
            'unknown' => 10,
        ],
        'dry' => [
            'hot' => 20,
            'both' => 25,
            'cold' => 25,
            'unknown' => 10,
        ],
    ];

    /**
     * @return array{weather_score: int, temperature_score: int, subtotal: int, max_score: int}
     */
    public function score(Product $product, WeatherContext $weather): array
    {
        $servingTemperature = $this->normalizeServingTemperature($product->serving_temperature);
        $weatherScore = self::WEATHER_SCORES[$weather->isRaining ? 'raining' : 'dry'][$servingTemperature];
        $temperatureScore = $this->temperatureScore($servingTemperature, $weather->temperatureC);

        return [
            'weather_score' => $weatherScore,
            'temperature_score' => $temperatureScore,
            'subtotal' => $weatherScore + $temperatureScore,
            'max_score' => self::MAX_SCORE,
        ];
    }

    private function normalizeServingTemperature(mixed $value): string
    {
        if (! is_string($value)) {
            return 'unknown';
        }

        $value = strtolower(trim($value));

        return in_array($value, ['hot', 'cold', 'both'], true) ? $value : 'unknown';
    }

    private function temperatureScore(string $servingTemperature, float $temperatureC): int
    {
        $scores = match (true) {
            $temperatureC >= 35 => ['hot' => 5, 'cold' => 35, 'both' => 25, 'unknown' => 10],
            $temperatureC >= 30 => ['hot' => 8, 'cold' => 32, 'both' => 25, 'unknown' => 10],
            $temperatureC >= 25 => ['hot' => 18, 'cold' => 25, 'both' => 30, 'unknown' => 10],
            $temperatureC >= 20 => ['hot' => 30, 'cold' => 18, 'both' => 30, 'unknown' => 10],
            default => ['hot' => 35, 'cold' => 5, 'both' => 25, 'unknown' => 10],
        };

        return $scores[$servingTemperature];
    }
}
