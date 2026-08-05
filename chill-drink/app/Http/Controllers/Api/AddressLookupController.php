<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AddressObservation;
use App\Models\Landmark;
use App\Models\VerifiedAddressPoint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AddressLookupController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $query = trim((string) ($validated['q'] ?? ''));
        $latitude = isset($validated['latitude']) ? (float) $validated['latitude'] : null;
        $longitude = isset($validated['longitude']) ? (float) $validated['longitude'] : null;
        $limit = (int) ($validated['limit'] ?? 10);
        $parsedQuery = $this->parseQuery($query);

        $verified = Schema::hasTable('verified_address_points')
            ? VerifiedAddressPoint::query()
                ->when($query !== '', fn ($builder) => $builder->where(function ($subQuery) use ($query, $parsedQuery) {
                    $subQuery->where('full_address', 'like', "%{$query}%")
                        ->orWhere('road_name', 'like', "%{$query}%")
                        ->orWhere('house_number', 'like', "{$query}%");

                    foreach ($parsedQuery['terms'] as $term) {
                        $subQuery->orWhere('full_address', 'like', "%{$term}%")
                            ->orWhere('road_name', 'like', "%{$term}%");
                    }
                }))
                ->limit(50)
                ->get()
                ->map(fn (VerifiedAddressPoint $point) => $this->rankResult([
                    'source' => 'verified_address',
                    'id' => $point->id,
                    'name' => $point->full_address,
                    'full_address' => $point->full_address,
                    'house_number' => $point->house_number,
                    'road_name' => $point->road_name,
                    'latitude' => $point->latitude,
                    'longitude' => $point->longitude,
                    'normalized_key' => $point->normalized_key,
                    'confidence' => $point->confidence,
                    'verification_level' => $point->verification_level,
                    'successful_delivery_count' => $point->successful_delivery_count,
                ], $parsedQuery))
            : collect();

        $observations = Schema::hasTable('address_observations')
            ? AddressObservation::query()
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->when($query !== '', fn ($builder) => $builder->where(function ($subQuery) use ($query, $parsedQuery) {
                    $subQuery->where('full_address', 'like', "%{$query}%")
                        ->orWhere('road_name', 'like', "%{$query}%")
                        ->orWhere('house_number', 'like', "{$query}%")
                        ->orWhere('normalized_key', 'like', "%{$parsedQuery['normalized']}%");

                    foreach ($parsedQuery['terms'] as $term) {
                        $subQuery->orWhere('full_address', 'like', "%{$term}%")
                            ->orWhere('road_name', 'like', "%{$term}%")
                            ->orWhere('normalized_key', 'like', "%{$term}%");
                    }
                }))
                ->latest('id')
                ->limit(50)
                ->get()
                ->map(fn (AddressObservation $observation) => $this->rankResult([
                    'source' => 'address_observation',
                    'id' => $observation->id,
                    'name' => $observation->full_address,
                    'full_address' => $observation->full_address,
                    'house_number' => $observation->house_number,
                    'road_name' => $observation->road_name,
                    'latitude' => $observation->latitude,
                    'longitude' => $observation->longitude,
                    'normalized_key' => $observation->normalized_key,
                    'confidence' => $observation->confidence,
                    'verification_level' => $observation->status,
                    'successful_delivery_count' => $observation->status === 'delivery_success' ? 1 : 0,
                ], $parsedQuery))
            : collect();

        $landmarks = Schema::hasTable('landmarks')
            ? Landmark::query()
                ->where('status', 'active')
                ->when($query !== '' && ! $parsedQuery['number_only'], fn ($builder) => $builder->where(function ($subQuery) use ($query, $parsedQuery) {
                    $subQuery->where('name', 'like', "%{$query}%")
                        ->orWhere('address_text', 'like', "%{$query}%");

                    foreach ($parsedQuery['terms'] as $term) {
                        $subQuery->orWhere('name', 'like', "%{$term}%")
                            ->orWhere('address_text', 'like', "%{$term}%");
                    }
                }))
                ->when($query !== '' && $parsedQuery['number_only'] && ($latitude === null || $longitude === null), fn ($builder) => $builder->whereRaw('1 = 0'))
                ->limit(50)
                ->get()
                ->map(fn (Landmark $landmark) => $this->rankResult([
                    'source' => 'landmark',
                    'id' => $landmark->id,
                    'name' => $landmark->name,
                    'full_address' => $landmark->address_text,
                    'house_number' => null,
                    'road_name' => null,
                    'latitude' => $landmark->latitude,
                    'longitude' => $landmark->longitude,
                    'normalized_key' => null,
                    'confidence' => $landmark->verification_level === 'needs_manual_review' ? 0.20 : 0.50,
                    'verification_level' => $landmark->verification_level,
                    'successful_delivery_count' => $landmark->successful_delivery_count,
                ], $parsedQuery))
            : collect();

        $results = $verified
            ->concat($observations)
            ->concat($landmarks)
            ->filter(fn (array $item) => $this->passesQueryThreshold($item, $query, $parsedQuery))
            ->map(function (array $item) use ($latitude, $longitude) {
                $item['distance_m'] = ($latitude !== null && $longitude !== null)
                    ? round($this->distanceMeters($latitude, $longitude, (float) $item['latitude'], (float) $item['longitude']))
                    : null;
                $item['source_rank'] = match ($item['source']) {
                    'verified_address' => 0,
                    'address_observation' => 1,
                    default => 2,
                };

                return $item;
            })
            ->sortBy([
                ['score', 'desc'],
                ['source_rank', 'asc'],
                ['successful_delivery_count', 'desc'],
                ['confidence', 'desc'],
                ['distance_m', 'asc'],
            ])
            ->values()
            ->unique(fn (array $item) => $this->duplicateKey($item))
            ->values()
            ->take($limit)
            ->map(function (array $item) {
                unset($item['source_rank']);

                return $item;
            });

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    private function parseQuery(string $query): array
    {
        $normalized = $this->normalizeText($query);
        preg_match('/\b\d+[a-z]?(?:\/\d+[a-z]?)*\b/u', $normalized, $numberMatch);
        $houseNumber = $numberMatch[0] ?? null;
        $withoutNumber = $houseNumber
            ? trim(preg_replace('/\b'.preg_quote($houseNumber, '/').'\b/u', ' ', $normalized, 1))
            : $normalized;

        $terms = collect(preg_split('/\s+/u', $withoutNumber) ?: [])
            ->filter(fn (string $term) => mb_strlen($term) >= 2 && ! in_array($term, ['duong', 'pho', 'ngo', 'ngach', 'hem', 'so', 'nha', 'thanh', 'hoa'], true))
            ->values()
            ->all();

        return [
            'raw' => $query,
            'normalized' => $normalized,
            'house_number' => $houseNumber,
            'terms' => $terms,
            'number_only' => $houseNumber !== null && empty($terms),
        ];
    }

    private function rankResult(array $item, array $parsedQuery): array
    {
        $haystack = $this->normalizeText(implode(' ', array_filter([
            $item['name'] ?? null,
            $item['full_address'] ?? null,
            $item['road_name'] ?? null,
            $item['house_number'] ?? null,
        ])));

        $score = 0;
        $matchedTerms = 0;

        $exactPhraseMatch = $parsedQuery['normalized'] !== '' && Str::contains($haystack, $parsedQuery['normalized']);

        if ($exactPhraseMatch) {
            $score += 80;
        }

        foreach ($parsedQuery['terms'] as $term) {
            if ($this->containsNormalizedToken($haystack, $term)) {
                $score += 18;
                $matchedTerms++;
            }
        }

        if ($parsedQuery['house_number'] !== null) {
            if (($item['house_number'] ?? null) === $parsedQuery['house_number']) {
                $score += 45;
                $item['match_type'] = 'exact_house_number';
            } elseif ($matchedTerms > 0) {
                $score += 10;
                $item['match_type'] = 'route_anchor';
            } else {
                $item['match_type'] = 'nearby_anchor';
            }
        } else {
            $item['match_type'] = $matchedTerms > 0 ? 'route_match' : 'nearby_anchor';
        }

        if ($score > 0) {
            if (($item['source'] ?? '') === 'verified_address') {
                $score += 30;
            } elseif (($item['source'] ?? '') === 'address_observation') {
                $score += 18;
            }
        }

        $item['requested_house_number'] = $parsedQuery['house_number'];
        $item['matched_terms'] = $matchedTerms;
        $item['exact_phrase_match'] = $exactPhraseMatch;
        $item['score'] = $score;
        $item['can_autofill_coordinates'] = $score >= 18 && ! $parsedQuery['number_only'];

        return $item;
    }

    private function passesQueryThreshold(array $item, string $query, array $parsedQuery): bool
    {
        if ($query === '') {
            return true;
        }

        if ((int) ($item['score'] ?? 0) <= 0) {
            return false;
        }

        if (($item['exact_phrase_match'] ?? false) || ($item['match_type'] ?? '') === 'exact_house_number') {
            return true;
        }

        $termCount = count($parsedQuery['terms']);
        if ($termCount <= 1) {
            return (int) ($item['matched_terms'] ?? 0) >= 1;
        }

        return (int) ($item['matched_terms'] ?? 0) >= min(2, $termCount);
    }

    private function duplicateKey(array $item): string
    {
        if (! empty($item['normalized_key'])) {
            return 'address:'.$item['normalized_key'];
        }

        $label = $this->normalizeText(implode(' ', array_filter([
            $item['full_address'] ?? null,
            $item['name'] ?? null,
        ])));

        if ($label !== '') {
            return ($item['source'] ?? 'unknown').':'.$label;
        }

        return ($item['source'] ?? 'unknown').':'.round((float) ($item['latitude'] ?? 0), 5).','.round((float) ($item['longitude'] ?? 0), 5);
    }

    private function normalizeText(?string $value): string
    {
        $value = Str::lower(trim((string) $value));
        $value = strtr($value, [
            'à' => 'a', 'á' => 'a', 'ạ' => 'a', 'ả' => 'a', 'ã' => 'a',
            'â' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ậ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a',
            'ă' => 'a', 'ằ' => 'a', 'ắ' => 'a', 'ặ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a',
            'è' => 'e', 'é' => 'e', 'ẹ' => 'e', 'ẻ' => 'e', 'ẽ' => 'e',
            'ê' => 'e', 'ề' => 'e', 'ế' => 'e', 'ệ' => 'e', 'ể' => 'e', 'ễ' => 'e',
            'ì' => 'i', 'í' => 'i', 'ị' => 'i', 'ỉ' => 'i', 'ĩ' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ọ' => 'o', 'ỏ' => 'o', 'õ' => 'o',
            'ô' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ộ' => 'o', 'ổ' => 'o', 'ỗ' => 'o',
            'ơ' => 'o', 'ờ' => 'o', 'ớ' => 'o', 'ợ' => 'o', 'ở' => 'o', 'ỡ' => 'o',
            'ù' => 'u', 'ú' => 'u', 'ụ' => 'u', 'ủ' => 'u', 'ũ' => 'u',
            'ư' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ự' => 'u', 'ử' => 'u', 'ữ' => 'u',
            'ỳ' => 'y', 'ý' => 'y', 'ỵ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y',
            'đ' => 'd',
        ]);

        return trim(preg_replace('/[^a-z0-9\/]+/u', ' ', $value));
    }

    private function containsNormalizedToken(string $haystack, string $term): bool
    {
        return preg_match('/(?:^|\s)'.preg_quote($term, '/').'(?=\s|$)/u', $haystack) === 1;
    }

    private function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);
        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
