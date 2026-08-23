<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AddressObservation;
use App\Models\VerifiedAddressPoint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

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

        if ($learned = $this->findLearnedAddress($latitude, $longitude)) {
            return response()->json([
                'latitude' => $latitude,
                'longitude' => $longitude,
                ...$learned,
                'source' => 'learned_address',
            ]);
        }

        $nominatim = $this->reverseFromNominatim($latitude, $longitude);
        // A display_name alone is not enough for the checkout form: it cannot
        // fill the separate area/street/house-number inputs. Keep looking up
        // nearby OSM data when Nominatim did not return structured fields.
        $hasStructuredNominatimAddress = filled($nominatim['house_number'] ?? null)
            || filled($nominatim['road'] ?? null)
            || filled($nominatim['ward'] ?? null)
            || filled($nominatim['district'] ?? null)
            || filled($nominatim['province'] ?? null);
        $overpass = $hasStructuredNominatimAddress
            ? []
            : $this->reverseFromOverpass($latitude, $longitude);

        $houseNumber = $overpass['house_number'] ?? $nominatim['house_number'] ?? null;
        $road = $overpass['road'] ?? $nominatim['road'] ?? null;
        $ward = $nominatim['ward'] ?? $overpass['ward'] ?? null;
        $district = $nominatim['district'] ?? $overpass['district'] ?? null;
        $province = $nominatim['province'] ?? $overpass['province'] ?? null;

        $streetParts = array_values(array_filter([
            $houseNumber,
            $road,
        ], fn ($value) => filled($value)));

        $areaParts = array_values(array_unique(array_filter([
            $district,
            $province,
            $ward,
        ], fn ($value) => filled($value))));

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

    private function findLearnedAddress(float $latitude, float $longitude): ?array
    {
        $latitudeDelta = 120 / 111000;
        $longitudeDelta = 120 / (111000 * max(0.2, abs(cos(deg2rad($latitude)))));
        $candidates = collect();

        if (Schema::hasTable('verified_address_points')) {
            $candidates = $candidates->concat(
                VerifiedAddressPoint::query()
                    ->whereBetween('latitude', [$latitude - $latitudeDelta, $latitude + $latitudeDelta])
                    ->whereBetween('longitude', [$longitude - $longitudeDelta, $longitude + $longitudeDelta])
                    ->get()
                    ->map(fn (VerifiedAddressPoint $point) => $this->learnedPayload(
                        $point,
                        $latitude,
                        $longitude,
                        30 + ((int) $point->successful_delivery_count * 10) + ((float) $point->confidence * 10),
                    ))
            );
        }

        if (Schema::hasTable('address_observations')) {
            $candidates = $candidates->concat(
                AddressObservation::query()
                    ->whereNotNull('latitude')
                    ->whereNotNull('longitude')
                    ->whereBetween('latitude', [$latitude - $latitudeDelta, $latitude + $latitudeDelta])
                    ->whereBetween('longitude', [$longitude - $longitudeDelta, $longitude + $longitudeDelta])
                    ->latest('id')
                    ->limit(40)
                    ->get()
                    ->map(fn (AddressObservation $observation) => $this->learnedPayload(
                        $observation,
                        $latitude,
                        $longitude,
                        ($observation->status === 'delivery_success' ? 20 : 0) + ((float) $observation->confidence * 10),
                    ))
            );
        }

        return $candidates
            ->filter(fn (array $item) => ($item['distance_m'] ?? PHP_INT_MAX) <= 120 && filled($item['display_name']))
            ->sortBy('distance_m')
            ->sortByDesc('priority')
            ->first();
    }

    private function learnedPayload(object $point, float $latitude, float $longitude, float $priority): array
    {
        $houseNumber = trim((string) ($point->house_number ?? ''));
        $road = trim((string) ($point->road_name ?? ''));
        $displayName = trim((string) ($point->full_address ?? ''));

        if ($displayName === '') {
            $displayName = implode(', ', array_filter([
                $houseNumber,
                $road,
                $point->ward ?? null,
                $point->district ?? null,
                $point->province ?? null,
            ]));
        }

        $areaParts = array_filter([
            $point->province,
            $point->district,
            $point->ward,
        ]);
        if (empty($areaParts) && $displayName !== '') {
            $addressParts = array_values(array_filter(
                preg_split('/\s*,\s*/u', $displayName) ?: [],
                fn ($part) => trim((string) $part) !== '',
            ));
            if (count($addressParts) > 1) {
                array_shift($addressParts);
                if ($road !== '' && isset($addressParts[0])
                    && mb_strtolower(trim($addressParts[0])) === mb_strtolower($road)) {
                    array_shift($addressParts);
                }
                $areaParts = $addressParts;
            }
        }

        return [
            'house_number' => $houseNumber ?: null,
            'road' => $road ?: null,
            'ward' => $point->ward,
            'district' => $point->district,
            'province' => $point->province,
            'street' => implode(', ', array_filter([$houseNumber, $road])),
            'area' => implode(', ', $areaParts),
            'display_name' => $displayName ?: null,
            'distance_m' => $this->distanceMeters($latitude, $longitude, (float) $point->latitude, (float) $point->longitude),
            'priority' => $priority,
        ];
    }

    private function reverseFromNominatim(float $latitude, float $longitude): array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'ChillDrink/1.0 (reverse-geocode)',
                'Accept-Language' => 'vi',
            ])
                ->connectTimeout(3)
                ->timeout(8)
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
            $road = $address['road']
                ?? $address['pedestrian']
                ?? $address['footway']
                ?? $address['path']
                ?? $address['residential']
                ?? $address['hamlet']
                ?? $address['neighbourhood']
                ?? $address['suburb']
                ?? $address['village']
                ?? null;

            $ward = $this->pickVietnamWardName([
                $address['village'] ?? null,
                $address['suburb'] ?? null,
                $address['neighbourhood'] ?? null,
                $address['quarter'] ?? null,
                $address['hamlet'] ?? null,
                $address['residential'] ?? null,
            ]);

            $province = $address['state'] ?? $address['region'] ?? $address['province'] ?? null;
            $district = $address['city_district']
                ?? $address['district']
                ?? $address['county']
                ?? $address['town']
                ?? $address['municipality']
                ?? $address['city']
                ?? null;

            return [
                'house_number' => $address['house_number'] ?? $address['housenumber'] ?? null,
                'road' => $road,
                'ward' => $ward,
                'district' => $district,
                'province' => $province,
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
                ->connectTimeout(3)
                ->timeout(10)
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

    private function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        return $this->distanceKm($lat1, $lng1, $lat2, $lng2) * 1000;
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
