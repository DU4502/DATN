<?php

namespace App\Services;

readonly class WeatherContext
{
    public function __construct(
        public float $temperatureC,
        public int $conditionCode,
        public string $conditionText,
        public bool $isRaining,
        public float $precipitationMm,
        public int $humidity,
        public ?float $feelsLikeC,
    ) {}

    /**
     * @return array{
     *     temperature_c: float,
     *     condition_code: int,
     *     condition_text: string,
     *     is_raining: bool,
     *     precipitation_mm: float,
     *     humidity: int,
     *     feels_like_c: float|null
     * }
     */
    public function toArray(): array
    {
        return [
            'temperature_c' => $this->temperatureC,
            'condition_code' => $this->conditionCode,
            'condition_text' => $this->conditionText,
            'is_raining' => $this->isRaining,
            'precipitation_mm' => $this->precipitationMm,
            'humidity' => $this->humidity,
            'feels_like_c' => $this->feelsLikeC,
        ];
    }
}
