<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Services\RecommendationScorer;
use App\Services\WeatherContext;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RecommendationScorerTest extends TestCase
{
    private RecommendationScorer $scorer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->scorer = new RecommendationScorer;
    }

    public function test_cold_drink_scores_very_high_at_36_degrees_without_rain(): void
    {
        $score = $this->score('cold', 36);

        $this->assertSame([
            'weather_score' => 25,
            'temperature_score' => 35,
            'subtotal' => 60,
            'max_score' => 75,
        ], $score);
    }

    public function test_hot_drink_scores_clearly_lower_than_cold_at_36_degrees(): void
    {
        $hot = $this->score('hot', 36);
        $cold = $this->score('cold', 36);

        $this->assertSame(25, $hot['subtotal']);
        $this->assertGreaterThan($hot['subtotal'], $cold['subtotal']);
    }

    public function test_hot_drink_scores_high_at_17_degrees_without_rain(): void
    {
        $score = $this->score('hot', 17);

        $this->assertSame(35, $score['temperature_score']);
        $this->assertSame(55, $score['subtotal']);
    }

    public function test_cold_drink_scores_lower_than_hot_at_17_degrees(): void
    {
        $hot = $this->score('hot', 17);
        $cold = $this->score('cold', 17);

        $this->assertSame(30, $cold['subtotal']);
        $this->assertGreaterThan($cold['subtotal'], $hot['subtotal']);
    }

    public function test_both_is_flexible_at_22_degrees(): void
    {
        $score = $this->score('both', 22);

        $this->assertSame(30, $score['temperature_score']);
        $this->assertSame(55, $score['subtotal']);
    }

    public function test_rain_and_heat_scores_are_added_independently(): void
    {
        $hot = $this->score('hot', 31, true);
        $cold = $this->score('cold', 31, true);
        $both = $this->score('both', 31, true);

        $this->assertSame([40, 8, 48], [$hot['weather_score'], $hot['temperature_score'], $hot['subtotal']]);
        $this->assertSame([15, 32, 47], [$cold['weather_score'], $cold['temperature_score'], $cold['subtotal']]);
        $this->assertSame([30, 25, 55], [$both['weather_score'], $both['temperature_score'], $both['subtotal']]);
        $this->assertGreaterThan($cold['weather_score'], $hot['weather_score']);
    }

    public function test_both_scores_well_at_28_degrees_without_rain(): void
    {
        $score = $this->score('both', 28);

        $this->assertSame([25, 30, 55], [$score['weather_score'], $score['temperature_score'], $score['subtotal']]);
    }

    public function test_null_serving_temperature_gets_neutral_low_score_without_crashing(): void
    {
        $score = $this->score(null, 28);

        $this->assertSame([10, 10, 20], [$score['weather_score'], $score['temperature_score'], $score['subtotal']]);
    }

    public function test_invalid_serving_temperature_fails_safe_as_unknown(): void
    {
        $score = $this->score('iced', 31, true);

        $this->assertSame([10, 10, 20], [$score['weather_score'], $score['temperature_score'], $score['subtotal']]);
    }

    #[DataProvider('temperatureBoundaries')]
    public function test_temperature_boundaries_have_no_gap_or_overlap(float $temperature, int $expectedHotScore): void
    {
        $score = $this->score('hot', $temperature);

        $this->assertSame($expectedHotScore, $score['temperature_score']);
    }

    public static function temperatureBoundaries(): array
    {
        return [
            'just below 20' => [19.99, 35],
            'exactly 20' => [20.00, 30],
            'just below 25' => [24.99, 30],
            'exactly 25' => [25.00, 18],
            'just below 30' => [29.99, 18],
            'exactly 30' => [30.00, 8],
            'just below 35' => [34.99, 8],
            'exactly 35' => [35.00, 5],
        ];
    }

    public function test_subtotal_always_stays_between_zero_and_phase_three_maximum(): void
    {
        foreach ([null, 'hot', 'cold', 'both', 'invalid'] as $servingTemperature) {
            foreach ([17.0, 22.0, 28.0, 31.0, 36.0] as $temperature) {
                foreach ([false, true] as $isRaining) {
                    $score = $this->score($servingTemperature, $temperature, $isRaining);

                    $this->assertGreaterThanOrEqual(0, $score['subtotal']);
                    $this->assertLessThanOrEqual(RecommendationScorer::MAX_SCORE, $score['subtotal']);
                }
            }
        }
    }

    private function score(?string $servingTemperature, float $temperature, bool $isRaining = false): array
    {
        $product = new Product(['serving_temperature' => $servingTemperature]);
        $weather = new WeatherContext(
            temperatureC: $temperature,
            conditionCode: $isRaining ? 1183 : 1000,
            conditionText: $isRaining ? 'Light rain' : 'Sunny',
            isRaining: $isRaining,
            precipitationMm: $isRaining ? 0.5 : 0,
            humidity: 65,
            feelsLikeC: null,
        );

        return $this->scorer->score($product, $weather);
    }
}
