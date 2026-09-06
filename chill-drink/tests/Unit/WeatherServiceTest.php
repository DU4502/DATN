<?php

namespace Tests\Unit;

use App\Exceptions\WeatherUnavailableException;
use App\Models\Branch;
use App\Services\WeatherContext;
use App\Services\WeatherService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WeatherServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.weather', [
            'provider' => 'weatherapi',
            'key' => 'test-weather-key',
            'cache_minutes' => 15,
            'timeout' => 5,
            'demo_mode' => false,
            'demo_scenario' => 'normal',
        ]);

        Cache::flush();
        Http::preventStrayRequests();
    }

    public function test_hot_demo_scenario_is_deterministic_without_api_key_or_http_request(): void
    {
        $weather = $this->demoWeather('hot');

        $this->assertSame(36.0, $weather->temperatureC);
        $this->assertFalse($weather->isRaining);
        $this->assertSame(0.0, $weather->precipitationMm);
        Http::assertNothingSent();
    }

    public function test_rain_demo_scenario_is_deterministic(): void
    {
        $weather = $this->demoWeather('rain');

        $this->assertSame(24.0, $weather->temperatureC);
        $this->assertTrue($weather->isRaining);
        $this->assertGreaterThan(0, $weather->precipitationMm);
        Http::assertNothingSent();
    }

    public function test_cold_demo_scenario_is_deterministic(): void
    {
        $weather = $this->demoWeather('cold');

        $this->assertSame(17.0, $weather->temperatureC);
        $this->assertFalse($weather->isRaining);
        Http::assertNothingSent();
    }

    public function test_normal_demo_scenario_is_deterministic(): void
    {
        $weather = $this->demoWeather('normal');

        $this->assertSame(28.0, $weather->temperatureC);
        $this->assertFalse($weather->isRaining);
        Http::assertNothingSent();
    }

    public function test_invalid_demo_scenario_falls_back_to_normal(): void
    {
        $weather = $this->demoWeather('storm123');

        $this->assertSame(28.0, $weather->temperatureC);
        $this->assertSame('Partly cloudy', $weather->conditionText);
        $this->assertFalse($weather->isRaining);
        Http::assertNothingSent();
    }

    public function test_switching_demo_scenario_bypasses_stale_production_cache(): void
    {
        config()->set('services.weather.demo_mode', true);
        config()->set('services.weather.demo_scenario', 'hot');
        $service = app(WeatherService::class);
        $branch = $this->branch(1);

        $hot = $service->currentForBranch($branch);
        config()->set('services.weather.demo_scenario', 'cold');
        $cold = $service->currentForBranch($branch);

        $this->assertSame(36.0, $hot->temperatureC);
        $this->assertSame(17.0, $cold->temperatureC);
        Http::assertNothingSent();
    }

    public function test_production_environment_ignores_demo_mode(): void
    {
        config()->set('services.weather.demo_mode', true);
        config()->set('services.weather.demo_scenario', 'hot');
        config()->set('services.weather.key');
        $this->app->detectEnvironment(fn (): string => 'production');

        try {
            app(WeatherService::class)->currentForBranch($this->branch(1));
            $this->fail('Expected WeatherUnavailableException was not thrown.');
        } catch (WeatherUnavailableException) {
            Http::assertNothingSent();
        }
    }

    public function test_successful_response_is_normalized_to_weather_context(): void
    {
        Http::fake(['*/current.json*' => Http::response($this->weatherResponse())]);

        $weather = app(WeatherService::class)->currentForBranch($this->branch(1));

        $this->assertInstanceOf(WeatherContext::class, $weather);
        $this->assertSame([
            'temperature_c' => 34.0,
            'condition_code' => 1000,
            'condition_text' => 'Sunny',
            'is_raining' => false,
            'precipitation_mm' => 0.0,
            'humidity' => 65,
            'feels_like_c' => 38.0,
        ], $weather->toArray());
    }

    public function test_rain_is_detected_from_condition_code_or_precipitation(): void
    {
        Http::fakeSequence()
            ->push($this->weatherResponse(code: 1183, text: 'Light rain', precipitation: 0))
            ->push($this->weatherResponse(code: 1000, text: 'Sunny', precipitation: 0.4));

        $service = app(WeatherService::class);

        $this->assertTrue($service->currentForBranch($this->branch(1))->isRaining);
        $this->assertTrue($service->currentForBranch($this->branch(2))->isRaining);
    }

    public function test_cache_hit_does_not_request_provider_again_within_ttl(): void
    {
        Http::fake(['*/current.json*' => Http::response($this->weatherResponse())]);
        $branch = $this->branch(1);
        $service = app(WeatherService::class);

        $first = $service->currentForBranch($branch);
        $second = $service->currentForBranch($branch);

        $this->assertSame($first, $second);
        Http::assertSentCount(1);
    }

    public function test_different_branches_use_separate_cache_entries_and_requests(): void
    {
        Http::fake(['*/current.json*' => Http::response($this->weatherResponse())]);
        $service = app(WeatherService::class);

        $service->currentForBranch($this->branch(10, 10.7769, 106.7009));
        $service->currentForBranch($this->branch(20, 10.8231, 106.6297));

        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request): bool => $request['q'] === '10.7769,106.7009');
        Http::assertSent(fn (Request $request): bool => $request['q'] === '10.8231,106.6297');
    }

    public function test_missing_coordinates_fail_without_external_request(): void
    {
        Http::fake();
        $branch = $this->branch(1);
        $branch->latitude = null;

        try {
            app(WeatherService::class)->currentForBranch($branch);
            $this->fail('Expected WeatherUnavailableException was not thrown.');
        } catch (WeatherUnavailableException) {
            Http::assertNothingSent();
        }
    }

    public function test_missing_api_key_fails_without_external_request(): void
    {
        Http::fake();
        config()->set('services.weather.key');

        try {
            app(WeatherService::class)->currentForBranch($this->branch(1));
            $this->fail('Expected WeatherUnavailableException was not thrown.');
        } catch (WeatherUnavailableException) {
            Http::assertNothingSent();
        }
    }

    public function test_provider_500_returns_weather_unavailable(): void
    {
        Http::fake(['*/current.json*' => Http::response(['error' => ['message' => 'Provider failure']], 500)]);

        $this->expectException(WeatherUnavailableException::class);

        app(WeatherService::class)->currentForBranch($this->branch(1));
    }

    public function test_provider_4xx_failure_is_not_cached_as_valid_weather(): void
    {
        Http::fakeSequence()
            ->push(['error' => ['message' => 'Unauthorized']], 401)
            ->push($this->weatherResponse());
        $service = app(WeatherService::class);
        $branch = $this->branch(1);

        try {
            $service->currentForBranch($branch);
            $this->fail('Expected WeatherUnavailableException was not thrown.');
        } catch (WeatherUnavailableException $exception) {
            $this->assertSame('The weather provider returned HTTP 401.', $exception->getMessage());
        }

        $this->assertSame(34.0, $service->currentForBranch($branch)->temperatureC);
        Http::assertSentCount(2);
    }

    public function test_invalid_provider_fails_without_external_request(): void
    {
        Http::fake();
        config()->set('services.weather.provider', 'unsupported-provider');

        try {
            app(WeatherService::class)->currentForBranch($this->branch(1));
            $this->fail('Expected WeatherUnavailableException was not thrown.');
        } catch (WeatherUnavailableException $exception) {
            $this->assertSame('The configured weather provider is not supported.', $exception->getMessage());
            Http::assertNothingSent();
        }
    }

    public function test_connection_failure_returns_safe_weather_unavailable_error(): void
    {
        Http::fake(['*/current.json*' => Http::failedConnection('Request URL contained test-weather-key')]);

        try {
            app(WeatherService::class)->currentForBranch($this->branch(1));
            $this->fail('Expected WeatherUnavailableException was not thrown.');
        } catch (WeatherUnavailableException $exception) {
            $this->assertSame('The weather provider could not be reached.', $exception->getMessage());
            $this->assertNull($exception->getPrevious());
            $this->assertStringNotContainsString('test-weather-key', $exception->getMessage());
        }
    }

    public function test_response_without_current_returns_weather_unavailable(): void
    {
        Http::fake(['*/current.json*' => Http::response(['location' => []])]);

        $this->expectException(WeatherUnavailableException::class);

        app(WeatherService::class)->currentForBranch($this->branch(1));
    }

    private function branch(int $id, float $latitude = 10.7769, float $longitude = 106.7009): Branch
    {
        $branch = new Branch([
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);
        $branch->id = $id;
        $branch->exists = true;

        return $branch;
    }

    private function demoWeather(string $scenario): WeatherContext
    {
        Http::fake();
        config()->set('services.weather.demo_mode', true);
        config()->set('services.weather.demo_scenario', $scenario);
        config()->set('services.weather.key');
        $branch = $this->branch(1);
        $branch->latitude = null;
        $branch->longitude = null;

        return app(WeatherService::class)->currentForBranch($branch);
    }

    private function weatherResponse(
        int $code = 1000,
        string $text = 'Sunny',
        float|int $precipitation = 0,
    ): array {
        return [
            'current' => [
                'temp_c' => 34,
                'condition' => [
                    'text' => $text,
                    'code' => $code,
                ],
                'precip_mm' => $precipitation,
                'humidity' => 65,
                'feelslike_c' => 38,
            ],
        ];
    }
}
