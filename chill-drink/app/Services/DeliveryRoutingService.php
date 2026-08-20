<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class DeliveryRoutingService
{
    /**
     * Tính tuyến đường từ hai tọa độ cho map giao hàng hiện tại.
     *
     * Map UI không bị thay thế. Service này chỉ nhận/tạo dữ liệu tuyến bằng tọa độ.
     * ROUTING_BASE_URL có thể trỏ tới routing server riêng khi triển khai.
     */
    public function route(float $fromLat, float $fromLng, float $toLat, float $toLng, array $options = []): array
    {
        $cacheKey = $this->cacheKey($fromLat, $fromLng, $toLat, $toLng, $options);

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($fromLat, $fromLng, $toLat, $toLng, $options) {
            $preferLocalRoads = (bool) ($options['prefer_local_roads'] ?? false);

            if ($preferLocalRoads) {
                $preferred = $this->requestRoute($fromLat, $fromLng, $toLat, $toLng, [
                    'exclude' => trim((string) config('services.delivery_routing.navigation_exclude', 'motorway')),
                    'snapping' => 'any',
                ]);

                if ($preferred) {
                    return $preferred;
                }
            }

            $standard = $this->requestRoute($fromLat, $fromLng, $toLat, $toLng);
            if ($standard) {
                return $standard;
            }

            return $this->fallback($fromLat, $fromLng, $toLat, $toLng);
        });
    }


    /**
     * Tính tuyến đi qua nhiều điểm theo đúng thứ tự đã truyền vào.
     * Dùng cho chuyến ghép: điểm hiện tại -> pickup/dropoff ...
     * @param array<int,array{latitude:float,longitude:float}> $points
     */
    public function routeThrough(array $points): array
    {
        $points = collect($points)
            ->filter(fn ($point) => is_array($point)
                && isset($point['latitude'], $point['longitude'])
                && is_numeric($point['latitude'])
                && is_numeric($point['longitude']))
            ->map(fn ($point) => [
                'latitude' => (float) $point['latitude'],
                'longitude' => (float) $point['longitude'],
            ])
            ->values()
            ->all();

        if (count($points) < 2) {
            return [
                'source' => 'invalid_points',
                'fallback' => true,
                'distance_m' => 0.0,
                'duration_s' => 0.0,
                'legs' => [],
                'geometry' => [],
                'steps' => [],
                'alternatives_count' => 0,
            ];
        }

        if (count($points) === 2) {
            return $this->route(
                $points[0]['latitude'],
                $points[0]['longitude'],
                $points[1]['latitude'],
                $points[1]['longitude']
            );
        }

        $profile = (string) config('services.delivery_routing.profile', 'driving');
        $coordsKey = collect($points)
            ->flatMap(fn ($p) => [round($p['latitude'], 5), round($p['longitude'], 5)])
            ->implode(':');
        $cacheKey = 'delivery_route_through:v2:'.$profile.':'.$coordsKey;

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($points, $profile) {
            $baseUrl = rtrim((string) config('services.delivery_routing.base_url', 'https://router.project-osrm.org'), '/');
            $coordinates = collect($points)
                ->map(fn ($p) => $p['longitude'].','.$p['latitude'])
                ->implode(';');

            try {
                $response = Http::acceptJson()
                    ->timeout((int) config('services.delivery_routing.timeout', 6))
                    ->retry(1, 150)
                    ->get("{$baseUrl}/route/v1/{$profile}/{$coordinates}", [
                        'overview' => 'full',
                        'geometries' => 'geojson',
                        'steps' => 'true',
                        'alternatives' => 'false',
                    ]);

                if ($response->successful() && $response->json('code') === 'Ok') {
                    $route = collect($response->json('routes', []))->first();
                    if (is_array($route) && isset($route['distance'], $route['duration'], $route['geometry']['coordinates'])) {
                        return [
                            'source' => 'routing_server',
                            'fallback' => false,
                            'distance_m' => (float) $route['distance'],
                            'duration_s' => (float) $route['duration'],
                            'legs' => $this->legs($route),
                            'geometry' => collect($route['geometry']['coordinates'])
                                ->map(fn ($point) => [(float) $point[1], (float) $point[0]])
                                ->values()
                                ->all(),
                            'steps' => $this->steps($route),
                            'alternatives_count' => 0,
                        ];
                    }
                }
            } catch (Throwable $exception) {
                report($exception);
            }

            // Fallback vẫn cộng từng chặng để giữ đúng thứ tự waypoint.
            $distance = 0.0;
            $duration = 0.0;
            $geometry = [];
            $legRows = [];
            for ($i = 0; $i < count($points) - 1; $i++) {
                $leg = $this->route(
                    $points[$i]['latitude'],
                    $points[$i]['longitude'],
                    $points[$i + 1]['latitude'],
                    $points[$i + 1]['longitude']
                );
                $distance += (float) ($leg['distance_m'] ?? 0);
                $duration += (float) ($leg['duration_s'] ?? 0);
                $legRows[] = [
                    'distance_m' => (float) ($leg['distance_m'] ?? 0),
                    'duration_s' => (float) ($leg['duration_s'] ?? 0),
                ];
                foreach (($leg['geometry'] ?? []) as $point) {
                    if (! $geometry || end($geometry) !== $point) {
                        $geometry[] = $point;
                    }
                }
            }

            return [
                'source' => 'segment_fallback',
                'fallback' => true,
                'distance_m' => $distance,
                'duration_s' => $duration,
                'legs' => $legRows,
                'geometry' => $geometry,
                'steps' => [],
                'alternatives_count' => 0,
            ];
        });
    }

    /**
     * Khoảng cách thô từ routing service. Hàm này có thể trả coordinate fallback
     * khi routing server lỗi, vì map vẫn cần dữ liệu để hiển thị.
     *
     * KHÔNG dùng trực tiếp cho phạm vi giao/phí ship/dispatch cuối cùng; các quyết
     * định nghiệp vụ phải kiểm tra fallback=false (OrderDistancePolicy làm việc này).
     */
    public function distanceKm(float $fromLat, float $fromLng, float $toLat, float $toLng): float
    {
        return round(((float) ($this->route($fromLat, $fromLng, $toLat, $toLng)['distance_m'] ?? 0)) / 1000, 3);
    }

    private function cacheKey(float $fromLat, float $fromLng, float $toLat, float $toLng, array $options = []): string
    {
        $profile = (string) config('services.delivery_routing.profile', 'driving');
        $coords = [
            round($fromLat, 5),
            round($fromLng, 5),
            round($toLat, 5),
            round($toLng, 5),
        ];
        $optionsKey = empty($options) ? 'standard' : md5(json_encode($this->normalizeOptions($options), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return 'delivery_route:v2:' . $profile . ':' . $optionsKey . ':' . implode(':', $coords);
    }

    /**
     * Tính route thật từ OSRM. Trả null nếu server không trả route hợp lệ.
     *
     * @param array<string,string> $query
     */
    private function requestRoute(float $fromLat, float $fromLng, float $toLat, float $toLng, array $query = []): ?array
    {
        $baseUrl = rtrim((string) config('services.delivery_routing.base_url', 'https://router.project-osrm.org'), '/');
        $profile = (string) config('services.delivery_routing.profile', 'driving');

        try {
            $response = Http::acceptJson()
                ->timeout((int) config('services.delivery_routing.timeout', 6))
                ->retry(1, 150)
                ->get("{$baseUrl}/route/v1/{$profile}/{$fromLng},{$fromLat};{$toLng},{$toLat}", array_merge([
                    'overview' => 'full',
                    'geometries' => 'geojson',
                    'steps' => 'true',
                    'alternatives' => 'true',
                ], $query));

            if (! ($response->successful() && $response->json('code') === 'Ok')) {
                return null;
            }

            $routes = collect($response->json('routes', []))
                ->filter(fn ($route) => is_array($route) && isset($route['distance'], $route['duration'], $route['geometry']['coordinates']))
                ->sortBy(fn ($route) => (float) $route['distance'])
                ->values();

            if ($routes->isEmpty()) {
                return null;
            }

            $shortest = $routes->first();
            $preferLocalRoads = ! empty($query['exclude']);

            return [
                'source' => 'routing_server',
                'fallback' => false,
                'distance_m' => (float) $shortest['distance'],
                'duration_s' => (float) $shortest['duration'],
                'legs' => $this->legs($shortest),
                'geometry' => collect($shortest['geometry']['coordinates'])
                    ->map(fn ($point) => [(float) $point[1], (float) $point[0]])
                    ->values()
                    ->all(),
                'steps' => $this->steps($shortest),
                'alternatives_count' => $routes->count(),
                'routing_strategy' => $preferLocalRoads ? 'local_roads_first' : 'standard',
                'preference_label' => $preferLocalRoads ? 'Ưu tiên đường nhỏ' : null,
            ];
        } catch (Throwable $exception) {
            report($exception);
        }

        return null;
    }

    /**
     * Chuẩn hóa options để tạo cache key ổn định.
     *
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    private function normalizeOptions(array $options): array
    {
        ksort($options);

        return collect($options)
            ->map(function ($value) {
                if (is_bool($value)) {
                    return $value ? 1 : 0;
                }

                if (is_array($value)) {
                    ksort($value);
                    return $value;
                }

                return $value;
            })
            ->all();
    }

    /**
     * Giữ duration/distance của từng leg giữa các waypoint. P9 dùng dữ liệu này
     * để tính ETA tới từng stop ghép mà không phải gọi routing server thêm lần nữa.
     */
    private function legs(array $route): array
    {
        return collect($route['legs'] ?? [])
            ->map(fn (array $leg) => [
                'distance_m' => (float) ($leg['distance'] ?? 0),
                'duration_s' => (float) ($leg['duration'] ?? 0),
            ])
            ->values()
            ->all();
    }

    private function steps(array $route): array
    {
        return collect($route['legs'] ?? [])
            ->flatMap(fn ($leg) => $leg['steps'] ?? [])
            ->map(function (array $step) {
                $maneuver = $step['maneuver'] ?? [];
                $location = $maneuver['location'] ?? null;

                return [
                    'name' => trim((string) ($step['name'] ?? '')),
                    'distance_m' => (float) ($step['distance'] ?? 0),
                    'duration_s' => (float) ($step['duration'] ?? 0),
                    'type' => (string) ($maneuver['type'] ?? ''),
                    'modifier' => (string) ($maneuver['modifier'] ?? ''),
                    'location' => is_array($location) && count($location) >= 2
                        ? [(float) $location[1], (float) $location[0]]
                        : null,
                ];
            })
            ->filter(fn ($step) => $step['distance_m'] > 0 || $step['type'] !== '')
            ->values()
            ->all();
    }

    private function fallback(float $fromLat, float $fromLng, float $toLat, float $toLng): array
    {
        $distance = $this->distanceMeters($fromLat, $fromLng, $toLat, $toLng);
        // Ước lượng xe máy nội đô 22 km/h khi routing server tạm thời không phản hồi.
        $duration = $distance > 0 ? ($distance / (22000 / 3600)) : 0;

        return [
            'source' => 'coordinate_fallback',
            'fallback' => true,
            'distance_m' => $distance,
            'duration_s' => $duration,
            'legs' => [[
                'distance_m' => $distance,
                'duration_s' => $duration,
            ]],
            'geometry' => [[$fromLat, $fromLng], [$toLat, $toLng]],
            'steps' => [],
            'alternatives_count' => 0,
        ];
    }

    private function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);
        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(max(0, 1 - $a)));
    }
}
