<?php

namespace App\Services;

use App\Exceptions\WeatherUnavailableException;
use App\Models\Branch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class DrinkRecommendationService
{
    public const DEFAULT_LIMIT = 5;

    public const MAX_LIMIT = 20;

    public const MAX_SCORE = 100;

    public function __construct(
        private readonly WeatherService $weatherService,
        private readonly RecommendationCandidateService $candidateService,
        private readonly RecommendationScorer $scorer,
    ) {}

    /**
     * @return array{
     *     weather: WeatherContext|null,
     *     weather_available: bool,
     *     mode: 'weather'|'popularity_fallback'|'empty',
     *     recommendations: Collection<int, array<string, mixed>>
     * }
     */
    public function forBranch(Branch $branch, int $limit = self::DEFAULT_LIMIT): array
    {
        $limit = max(1, min(self::MAX_LIMIT, $limit));
        $candidates = $this->candidateService->forBranch($branch);

        if ($candidates->isEmpty()) {
            return [
                'weather' => null,
                'weather_available' => false,
                'mode' => 'empty',
                'recommendations' => collect(),
            ];
        }

        try {
            $weather = $this->weatherService->currentForBranch($branch);
        } catch (WeatherUnavailableException) {
            Log::warning('Weather unavailable while building drink recommendations.', [
                'branch_id' => $branch->getKey(),
            ]);

            return [
                'weather' => null,
                'weather_available' => false,
                'mode' => 'popularity_fallback',
                'recommendations' => $this->fallbackRecommendations($candidates, $limit),
            ];
        }

        $recommendations = $candidates
            ->map(function (array $candidate) use ($weather): array {
                $score = $this->scorer->score($candidate['product'], $weather);

                return [
                    'product' => $candidate['product'],
                    'weather_score' => $score['weather_score'],
                    'temperature_score' => $score['temperature_score'],
                    'popularity_score' => $candidate['popularity_score'],
                    'final_score' => $score['subtotal'] + $candidate['popularity_score'],
                    'max_score' => self::MAX_SCORE,
                    'sales_30d' => $candidate['sales_30d'],
                ];
            })
            ->sort($this->weatherSorter(...))
            ->take($limit)
            ->values();

        return [
            'weather' => $weather,
            'weather_available' => true,
            'mode' => 'weather',
            'recommendations' => $recommendations,
        ];
    }

    private function fallbackRecommendations(Collection $candidates, int $limit): Collection
    {
        return $candidates
            ->map(fn (array $candidate): array => [
                'product' => $candidate['product'],
                'popularity_score' => $candidate['popularity_score'],
                'fallback_score' => $candidate['popularity_score'],
                'max_score' => RecommendationCandidateService::POPULARITY_MAX_SCORE,
                'sales_30d' => $candidate['sales_30d'],
            ])
            ->sort($this->fallbackSorter(...))
            ->take($limit)
            ->values();
    }

    private function weatherSorter(array $left, array $right): int
    {
        return $right['final_score'] <=> $left['final_score']
            ?: $right['popularity_score'] <=> $left['popularity_score']
            ?: $right['sales_30d'] <=> $left['sales_30d']
            ?: $left['product']->id <=> $right['product']->id;
    }

    private function fallbackSorter(array $left, array $right): int
    {
        return $right['popularity_score'] <=> $left['popularity_score']
            ?: $right['sales_30d'] <=> $left['sales_30d']
            ?: $left['product']->id <=> $right['product']->id;
    }
}
