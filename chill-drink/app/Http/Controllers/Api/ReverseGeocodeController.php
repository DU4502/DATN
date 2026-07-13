<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ReverseGeocodeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $latitude = $request->input('latitude', $request->input('lat'));
        $longitude = $request->input('longitude', $request->input('lon'));

        if (! is_numeric($latitude) || ! is_numeric($longitude)) {
            return response()->json([
                'message' => 'Thiếu hoặc sai tọa độ.',
            ], 422);
        }

        $latitude = (float) $latitude;
        $longitude = (float) $longitude;

        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            return response()->json([
                'message' => 'Tọa độ không hợp lệ.',
            ], 422);
        }

        $nominatim = $this->reverseFromNominatim($latitude, $longitude);
        $overpass = $this->reverseFromOverpass($latitude, $longitude);

        $houseNumber = $overpass['house_number'] ?? $nominatim['house_number'] ?? null;
        $road = $overpass['road'] ?? $nominatim['road'] ?? null;
        $ward = $nominatim['ward'] ?? $overpass['ward'] ?? null;
        $district = $nominatim['district'] ?? $overpass['district'] ?? null;
        $province = $nominatim['province'] ?? $overpass['province'] ?? null;

        $streetParts = array_values(array_filter([
            $houseNumber,
            $road,
        ], fn ($value) => filled($value)));

        $areaParts = array_values(array_filter([
            $ward,
            $province,
        ], fn ($value) => filled($value)));

        return response()->json([
            'latitude' => $latitude,
            'longitude' => $longitude,
            'house_number' => $houseNumber,
            'road' => $road,
            'ward' => $ward,
            'district' => $district,
            'province' => $province,
            'street' => implode(', ', $streetParts),
            'area' => implode(', ', $areaParts),
            'display_name' => $nominatim['display_name'] ?? $overpass['display_name'] ?? null,
            'source' => $overpass['source'] ?? $nominatim['source'] ?? 'unknown',
        ]);
    }

    private function reverseFromNominatim(float $latitude, float $longitude): array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'ChillDrink/1.0 (reverse-geocode)',
                'Accept-Language' => 'vi',
            ])
                ->timeout(12)
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'format' => 'jsonv2',
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'addressdetails' => 1,
                    'zoom' => 19,
                    'accept-language' => 'vi',
                ]);

            if (! $response->ok()) {
                return [];
            }

            $payload = $response->json();
            $address = is_array($payload['address'] ?? null) ? $payload['address'] : [];
            $displayName = is_string($payload['display_name'] ?? null) ? $payload['display_name'] : null;
            $road = $address['road'] ?? $address['pedestrian'] ?? $address['footway'] ?? $address['residential'] ?? null;

            $ward = $this->pickVietnamWardName([
                $address['city'] ?? null,
                $address['suburb'] ?? null,
                $address['village'] ?? null,
                $address['neighbourhood'] ?? null,
                $address['quarter'] ?? null,
                $address['city_district'] ?? null,
                $address['borough'] ?? null,
                $address['district'] ?? null,
                $address['municipality'] ?? null,
            ]);

            return [
                'house_number' => $address['house_number'] ?? $address['housenumber'] ?? null,
                'road' => $road,
                'ward' => $ward,
                'district' => $address['county'] ?? $address['city'] ?? $address['town'] ?? $address['state_district'] ?? null,
                'province' => $address['state'] ?? $address['region'] ?? null,
                'display_name' => $displayName,
                'source' => 'nominatim',
            ];
        } catch (\Throwable) {
            return [];
        }
    }

    private function reverseFromOverpass(float $latitude, float $longitude): array
    {
        $query = <<<QL
[out:json][timeout:20];
(
  node(around:120,{$latitude},{$longitude})["addr:housenumber"];
  way(around:120,{$latitude},{$longitude})["addr:housenumber"];
  relation(around:120,{$latitude},{$longitude})["addr:housenumber"];
  node(around:120,{$latitude},{$longitude})["addr:street"];
  way(around:120,{$latitude},{$longitude})["addr:street"];
  relation(around:120,{$latitude},{$longitude})["addr:street"];
  way(around:120,{$latitude},{$longitude})["highway"];
);
out center tags;
QL;

        try {
            $response = Http::asForm()
                ->withHeaders([
                    'User-Agent' => 'ChillDrink/1.0 (reverse-geocode)',
                ])
                ->timeout(20)
                ->post('https://overpass-api.de/api/interpreter', [
                    'data' => $query,
                ]);

            if (! $response->ok()) {
                return [];
            }

            $elements = $response->json('elements');
            if (! is_array($elements) || empty($elements)) {
                return [];
            }

            $closest = null;
            $closestDistance = PHP_FLOAT_MAX;

            foreach ($elements as $element) {
                $elLat = $element['lat'] ?? $element['center']['lat'] ?? null;
                $elLon = $element['lon'] ?? $element['center']['lon'] ?? null;

                if (! is_numeric($elLat) || ! is_numeric($elLon)) {
                    continue;
                }

                $distance = $this->distanceKm($latitude, $longitude, (float) $elLat, (float) $elLon);
                if ($distance < $closestDistance) {
                    $closestDistance = $distance;
                    $closest = $element;
                }
            }

            if (! is_array($closest)) {
                return [];
            }

            $tags = $closest['tags'] ?? [];
            $road = $tags['addr:street'] ?? null;
            $ward = $this->pickVietnamWardName([
                $tags['addr:city'] ?? null,
                $tags['addr:suburb'] ?? null,
                $tags['addr:village'] ?? null,
                $tags['addr:neighbourhood'] ?? null,
                $tags['addr:quarter'] ?? null,
                $tags['addr:hamlet'] ?? null,
            ]);

            return [
                'house_number' => $tags['addr:housenumber'] ?? null,
                'road' => $road,
                'ward' => $ward,
                'district' => $tags['addr:district'] ?? $tags['addr:city'] ?? null,
                'province' => $tags['addr:province'] ?? $tags['addr:state'] ?? null,
                'display_name' => trim(implode(', ', array_filter([
                    $tags['addr:housenumber'] ?? null,
                    $road,
                ]))) ?: null,
                'source' => 'overpass',
            ];
        } catch (\Throwable) {
            return [];
        }
    }

    private function distanceKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371;

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lonDelta / 2) ** 2;

        return 2 * $earthRadius * asin(min(1, sqrt($a)));
    }

    private function pickVietnamWardName(array $candidates): ?string
    {
        $prefixedWard = null;
        $fallback = null;

        foreach ($candidates as $candidate) {
            $candidate = is_string($candidate) ? trim($candidate) : '';
            if ($candidate === '') {
                continue;
            }

            if ($fallback === null) {
                $fallback = $candidate;
            }

            if (preg_match('/^(Phường|Xã|Thị trấn)\b/ui', $candidate)) {
                return $candidate;
            }

            if ($prefixedWard === null && preg_match('/^(P\.|P:|X\.|X:|TT\.|TT:)\s*/ui', $candidate)) {
                $prefixedWard = $candidate;
            }
        }

        return $prefixedWard ?? $fallback;
    }

}
