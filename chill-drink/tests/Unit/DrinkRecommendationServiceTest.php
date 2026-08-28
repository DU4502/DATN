<?php

namespace Tests\Unit;

use App\Exceptions\WeatherUnavailableException;
use App\Models\Branch;
use App\Models\Product;
use App\Services\DrinkRecommendationService;
use App\Services\RecommendationCandidateService;
use App\Services\RecommendationScorer;
use App\Services\WeatherContext;
use App\Services\WeatherService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DrinkRecommendationServiceTest extends TestCase
{
    public function test_full_scores_are_combined_sorted_and_use_one_weather_context_for_all_products(): void
    {
        $branch = $this->branch();
        $hot = $this->candidate(20, 'hot', 100, 25);
        $cold = $this->candidate(10, 'cold', 20, 10);
        $both = $this->candidate(30, 'both', 0, 0);
        $unknown = $this->candidate(40, null, 0, 0);
        $weather = $this->weather(36);
        $service = $this->service(collect([$hot, $cold, $both, $unknown]), $weather, $branch);

        $result = $service->forBranch($branch);
        $rows = $result['recommendations'];

        $this->assertSame('weather', $result['mode']);
        $this->assertTrue($result['weather_available']);
        $this->assertSame($weather, $result['weather']);
        $this->assertSame([10, 20, 30, 40], $rows->pluck('product.id')->all());
        $this->assertSame([
            'weather_score' => 25,
            'temperature_score' => 35,
            'popularity_score' => 10,
            'final_score' => 70,
            'max_score' => 100,
            'sales_30d' => 20,
        ], collect($rows->first())->except('product')->all());
        $this->assertSame(50, $rows[1]['final_score']);
        $this->assertSame(50, $rows[2]['final_score']);
        $this->assertSame(20, $rows[3]['final_score']);

        foreach ($rows as $row) {
            $this->assertGreaterThanOrEqual(0, $row['final_score']);
            $this->assertLessThanOrEqual(100, $row['final_score']);
        }
    }

    public function test_same_weather_and_temperature_scores_are_ordered_by_popularity(): void
    {
        $branch = $this->branch();
        $popular = $this->candidate(2, 'cold', 50, 25);
        $lessPopular = $this->candidate(1, 'cold', 20, 10);
        $service = $this->service(collect([$lessPopular, $popular]), $this->weather(36), $branch);

        $result = $service->forBranch($branch);

        $this->assertSame([2, 1], $result['recommendations']->pluck('product.id')->all());
        $this->assertSame([85, 70], $result['recommendations']->pluck('final_score')->all());
    }

    public function test_rain_conflict_uses_the_score_formula_instead_of_overrides(): void
    {
        $branch = $this->branch();
        $hot = $this->candidate(1, 'hot', 20, 10);
        $cold = $this->candidate(2, 'cold', 30, 15);
        $both = $this->candidate(3, 'both', 0, 0);
        $service = $this->service(collect([$hot, $cold, $both]), $this->weather(31, true), $branch);

        $rows = $service->forBranch($branch)['recommendations'];

        $this->assertSame([2, 1, 3], $rows->pluck('product.id')->all());
        $this->assertSame([62, 58, 55], $rows->pluck('final_score')->all());
    }

    public function test_complete_tie_uses_product_id_ascending(): void
    {
        $branch = $this->branch();
        $service = $this->service(collect([
            $this->candidate(9, 'both', 10, 10),
            $this->candidate(3, 'both', 10, 10),
        ]), $this->weather(28), $branch);

        $result = $service->forBranch($branch);

        $this->assertSame([3, 9], $result['recommendations']->pluck('product.id')->all());
    }

    public function test_default_custom_and_safe_limits_apply_after_sorting(): void
    {
        $branch = $this->branch();
        $candidates = collect(range(1, 25))
            ->map(fn (int $id): array => $this->candidate($id, 'both', $id, $id));
        $service = $this->service($candidates, $this->weather(28), $branch, 4, 4);

        $default = $service->forBranch($branch);
        $custom = $service->forBranch($branch, 3);
        $nonPositive = $service->forBranch($branch, 0);
        $tooLarge = $service->forBranch($branch, 999);

        $this->assertCount(5, $default['recommendations']);
        $this->assertCount(3, $custom['recommendations']);
        $this->assertCount(1, $nonPositive['recommendations']);
        $this->assertCount(20, $tooLarge['recommendations']);
        $this->assertSame([25, 24, 23], $custom['recommendations']->pluck('product.id')->all());
    }

    public function test_empty_candidates_return_without_calling_weather(): void
    {
        $branch = $this->branch();
        $candidateService = Mockery::mock(RecommendationCandidateService::class);
        $candidateService->shouldReceive('forBranch')->once()->with($branch)->andReturn(collect());
        $weatherService = Mockery::mock(WeatherService::class);
        $weatherService->shouldNotReceive('currentForBranch');
        $service = new DrinkRecommendationService($weatherService, $candidateService, new RecommendationScorer);

        $result = $service->forBranch($branch);

        $this->assertSame('empty', $result['mode']);
        $this->assertFalse($result['weather_available']);
        $this->assertNull($result['weather']);
        $this->assertTrue($result['recommendations']->isEmpty());
    }

    #[DataProvider('weatherFailures')]
    public function test_weather_failures_use_popularity_fallback_without_fake_final_score(string $message): void
    {
        Log::spy();
        $branch = $this->branch();
        $service = $this->service(
            collect([$this->candidate(1, 'cold', 5, 25)]),
            new WeatherUnavailableException($message),
            $branch,
        );

        $result = $service->forBranch($branch);
        $row = $result['recommendations']->first();

        $this->assertSame('popularity_fallback', $result['mode']);
        $this->assertFalse($result['weather_available']);
        $this->assertNull($result['weather']);
        $this->assertSame(25, $row['fallback_score']);
        $this->assertSame(25, $row['max_score']);
        $this->assertArrayNotHasKey('final_score', $row);
        Log::shouldHaveReceived('warning')->once()->with(
            'Weather unavailable while building drink recommendations.',
            ['branch_id' => $branch->id],
        );
    }

    public static function weatherFailures(): array
    {
        return [
            'missing API key' => ['Weather is unavailable because the provider API key is missing.'],
            'provider HTTP error' => ['The weather provider returned HTTP 500.'],
            'missing branch coordinates' => ['Weather is unavailable because the branch coordinates are missing.'],
        ];
    }

    public function test_fallback_sorting_uses_popularity_sales_then_product_id(): void
    {
        Log::spy();
        $branch = $this->branch();
        $service = $this->service(collect([
            $this->candidate(8, 'hot', 20, 10),
            $this->candidate(4, 'cold', 20, 10),
            $this->candidate(2, 'both', 5, 20),
            $this->candidate(1, null, 100, 5),
        ]), new WeatherUnavailableException('Unavailable'), $branch);

        $rows = $service->forBranch($branch)['recommendations'];

        $this->assertSame([2, 4, 8, 1], $rows->pluck('product.id')->all());
        $this->assertSame([20, 10, 10, 5], $rows->pluck('fallback_score')->all());
    }

    private function service(
        Collection $candidates,
        WeatherContext|WeatherUnavailableException $weatherOutcome,
        Branch $branch,
        int $candidateCalls = 1,
        int $weatherCalls = 1,
    ): DrinkRecommendationService {
        $candidateService = Mockery::mock(RecommendationCandidateService::class);
        $candidateService->shouldReceive('forBranch')
            ->times($candidateCalls)
            ->with($branch)
            ->andReturn($candidates);

        $weatherService = Mockery::mock(WeatherService::class);
        $expectation = $weatherService->shouldReceive('currentForBranch')
            ->times($weatherCalls)
            ->with($branch);

        if ($weatherOutcome instanceof WeatherUnavailableException) {
            $expectation->andThrow($weatherOutcome);
        } else {
            $expectation->andReturn($weatherOutcome);
        }

        return new DrinkRecommendationService($weatherService, $candidateService, new RecommendationScorer);
    }

    private function branch(): Branch
    {
        $branch = new Branch(['name' => 'Recommendation branch']);
        $branch->id = 7;
        $branch->exists = true;

        return $branch;
    }

    private function candidate(int $id, ?string $servingTemperature, int $sales, int $popularity): array
    {
        $product = new Product(['serving_temperature' => $servingTemperature]);
        $product->id = $id;
        $product->exists = true;

        return [
            'product' => $product,
            'sales_30d' => $sales,
            'popularity_score' => $popularity,
        ];
    }

    private function weather(float $temperature, bool $isRaining = false): WeatherContext
    {
        return new WeatherContext(
            temperatureC: $temperature,
            conditionCode: $isRaining ? 1183 : 1000,
            conditionText: $isRaining ? 'Light rain' : 'Sunny',
            isRaining: $isRaining,
            precipitationMm: $isRaining ? 0.5 : 0,
            humidity: 65,
            feelsLikeC: null,
        );
    }
}
