<?php

namespace App\Services;

use App\Exceptions\WeatherUnavailableException;
use App\Models\Branch;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class WeatherService
{
    private const WEATHER_API_BASE_URL = 'https://api.weatherapi.com/v1';

    /**
     * WeatherAPI condition codes for rain, drizzle, and rain with thunder.
     *
     * @var list<int>
     */
    private const RAIN_CONDITION_CODES = [
        1063, 1072, 1087,
        1150, 1153, 1168, 1171,
        1180, 1183, 1186, 1189, 1192, 1195, 1198,
        1201, 1240, 1243, 1246, 1273, 1276,
    ];

    public function currentForBranch(Branch $branch): WeatherContext
    {
        $branchId = $branch->getKey();
        if ($branchId === null) {
            throw new WeatherUnavailableException('Weather is unavailable for an unsaved branch.');
        }

        if ($this->demoModeEnabled()) {
            return $this->demoWeatherContext();
        }

        [$latitude, $longitude] = $this->coordinatesFor($branch);
        $cacheKey = "weather:current:branch:{$branchId}";
        $cacheMinutes = max(1, (int) config('services.weather.cache_minutes', 15));

        return Cache::remember(
            $cacheKey,
            now()->addMinutes($cacheMinutes),
            fn (): WeatherContext => $this->requestCurrentWeather($latitude, $longitude),
        );
    }

    private function demoModeEnabled(): bool
    {
        return ! app()->environment('production')
            && filter_var(config('services.weather.demo_mode', false), FILTER_VALIDATE_BOOL);
    }

    private function demoWeatherContext(): WeatherContext
    {
        $scenario = strtolower(trim((string) config('services.weather.demo_scenario', 'normal')));
        if (! in_array($scenario, ['hot', 'rain', 'cold', 'normal'], true)) {
            $scenario = 'normal';
        }

        return match ($scenario) {
            'hot' => new WeatherContext(
                temperatureC: 36,
                conditionCode: 1000,
                conditionText: 'Hot and sunny',
                isRaining: false,
                precipitationMm: 0,
                humidity: 60,
                feelsLikeC: 39,
            ),
            'rain' => new WeatherContext(
                temperatureC: 24,
                conditionCode: 1183,
                conditionText: 'Rain',
                isRaining: true,
                precipitationMm: 4.2,
                humidity: 90,
                feelsLikeC: 25,
            ),
            'cold' => new WeatherContext(
                temperatureC: 17,
                conditionCode: 1006,
                conditionText: 'Cool',
                isRaining: false,
                precipitationMm: 0,
                humidity: 70,
                feelsLikeC: 16,
            ),
            default => new WeatherContext(
                temperatureC: 28,
                conditionCode: 1003,
                conditionText: 'Partly cloudy',
                isRaining: false,
                precipitationMm: 0,
                humidity: 68,
                feelsLikeC: 30,
            ),
        };
    }

    /**
     * @return array{float, float}
     */
    private function coordinatesFor(Branch $branch): array
    {
        if (! is_numeric($branch->latitude) || ! is_numeric($branch->longitude)) {
            throw new WeatherUnavailableException('Weather is unavailable because the branch coordinates are missing.');
        }

        $latitude = (float) $branch->latitude;
        $longitude = (float) $branch->longitude;

        if (! is_finite($latitude) || ! is_finite($longitude)
            || $latitude < -90 || $latitude > 90
            || $longitude < -180 || $longitude > 180) {
            throw new WeatherUnavailableException('Weather is unavailable because the branch coordinates are invalid.');
        }

        return [$latitude, $longitude];
    }

    private function requestCurrentWeather(float $latitude, float $longitude): WeatherContext
    {
        $provider = strtolower(trim((string) config('services.weather.provider', 'weatherapi')));
        if ($provider !== 'weatherapi') {
            throw new WeatherUnavailableException('The configured weather provider is not supported.');
        }

        $apiKey = trim((string) config('services.weather.key'));
        if ($apiKey === '') {
            throw new WeatherUnavailableException('Weather is unavailable because the provider API key is missing.');
        }

        try {
            $response = Http::acceptJson()
                ->timeout(max(1, (int) config('services.weather.timeout', 5)))
                ->get(self::WEATHER_API_BASE_URL.'/current.json', [
                    'key' => $apiKey,
                    'q' => $latitude.','.$longitude,
                    'aqi' => 'no',
                ]);
        } catch (ConnectionException) {
            // Do not chain the client exception: its message may contain the API key in the request URL.
            throw new WeatherUnavailableException('The weather provider could not be reached.');
        }

        if (! $response->successful()) {
            throw new WeatherUnavailableException("The weather provider returned HTTP {$response->status()}.");
        }

        $payload = $response->json();
        if (! is_array($payload) || ! is_array($payload['current'] ?? null)) {
            throw new WeatherUnavailableException('The weather provider returned a malformed response.');
        }

        return $this->normalize($payload['current']);
    }

    private function normalize(array $current): WeatherContext
    {
        $condition = $current['condition'] ?? null;
        if (! is_array($condition)
            || ! $this->isFiniteNumeric($current['temp_c'] ?? null)
            || ! $this->isIntegerNumeric($condition['code'] ?? null)
            || ! is_string($condition['text'] ?? null)
            || trim($condition['text']) === ''
            || ! $this->isFiniteNumeric($current['precip_mm'] ?? null)
            || ! $this->isIntegerNumeric($current['humidity'] ?? null)) {
            throw new WeatherUnavailableException('The weather provider returned incomplete current conditions.');
        }

        $humidity = (int) $current['humidity'];
        $precipitationMm = (float) $current['precip_mm'];
        if ($humidity < 0 || $humidity > 100 || $precipitationMm < 0) {
            throw new WeatherUnavailableException('The weather provider returned invalid current conditions.');
        }

        $feelsLike = $current['feelslike_c'] ?? null;
        if ($feelsLike !== null && ! $this->isFiniteNumeric($feelsLike)) {
            throw new WeatherUnavailableException('The weather provider returned invalid current conditions.');
        }

        $conditionCode = (int) $condition['code'];

        return new WeatherContext(
            temperatureC: (float) $current['temp_c'],
            conditionCode: $conditionCode,
            conditionText: trim($condition['text']),
            isRaining: $precipitationMm > 0 || in_array($conditionCode, self::RAIN_CONDITION_CODES, true),
            precipitationMm: $precipitationMm,
            humidity: $humidity,
            feelsLikeC: $feelsLike === null ? null : (float) $feelsLike,
        );
    }

    private function isFiniteNumeric(mixed $value): bool
    {
        return is_numeric($value) && is_finite((float) $value);
    }

    private function isIntegerNumeric(mixed $value): bool
    {
        return is_numeric($value) && (float) $value === (float) (int) $value;
    }
}
