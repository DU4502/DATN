<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use GuzzleHttp\TransferStats;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ResolveMapLinkController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $mapLink = trim((string) $request->input('map_link', $request->input('url', '')));

        if ($mapLink === '') {
            return response()->json([
                'message' => 'Thiếu link Google Maps.',
            ], 422);
        }

        $coordinates = $this->coordinatesFromMapLink($mapLink);

        if (! $coordinates) {
            return response()->json([
                'message' => 'Không đọc được tọa độ từ link Google Maps.',
            ], 422);
        }

        return response()->json([
            'latitude' => $coordinates['latitude'],
            'longitude' => $coordinates['longitude'],
            'source' => $coordinates['source'] ?? 'resolved',
        ]);
    }

    private function coordinatesFromMapLink(string $mapLink): ?array
    {
        $directCoordinates = $this->extractCoordinatesFromString($mapLink);
        if ($directCoordinates) {
            return $directCoordinates + ['source' => 'direct'];
        }

        $resolvedUrl = $this->resolveFinalMapUrl($mapLink);
        if ($resolvedUrl) {
            $resolvedCoordinates = $this->extractCoordinatesFromString($resolvedUrl);
            if ($resolvedCoordinates) {
                return $resolvedCoordinates + ['source' => 'resolved'];
            }
        }

        return null;
    }

    private function extractCoordinatesFromString(string $value): ?array
    {
        $patterns = [
            '/@(-?\d+(?:\.\d+)?),\s*(-?\d+(?:\.\d+)?)(?:,|$)/',
            '/[?&](?:q|query|ll|center|destination)=(-?\d+(?:\.\d+)?),\s*(-?\d+(?:\.\d+)?)(?:&|$)/',
            '/\/place\/[^\/]+\/@(-?\d+(?:\.\d+)?),\s*(-?\d+(?:\.\d+)?)(?:,|\/|$)/',
            '/\/maps\/search\/(-?\d+(?:\.\d+)?),\+?(-?\d+(?:\.\d+)?)(?:[?\/]|$)/',
            '/!3d(-?\d+(?:\.\d+)?)!4d(-?\d+(?:\.\d+)?)/',
        ];

        foreach ($patterns as $pattern) {
            if (! preg_match($pattern, $value, $matches)) {
                continue;
            }

            $latitude = isset($matches[1]) ? (float) $matches[1] : null;
            $longitude = isset($matches[2]) ? (float) $matches[2] : null;

            if ($latitude === null || $longitude === null) {
                continue;
            }

            return [
                'latitude' => $latitude,
                'longitude' => $longitude,
            ];
        }

        return null;
    }

    private function resolveFinalMapUrl(string $mapLink): ?string
    {
        if (! filter_var($mapLink, FILTER_VALIDATE_URL)) {
            return null;
        }

        $effectiveUrl = null;

        try {
            Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (ChillDrink; map-link-resolver)',
                'Accept-Language' => 'vi-VN,vi;q=0.9,en;q=0.8',
            ])
                ->withOptions([
                    'allow_redirects' => [
                        'track_redirects' => true,
                    ],
                    'on_stats' => function (TransferStats $stats) use (&$effectiveUrl): void {
                        $uri = $stats->getEffectiveUri();
                        $effectiveUrl = $uri ? (string) $uri : null;
                    },
                ])
                ->timeout(10)
                ->acceptJson()
                ->get($mapLink);
        } catch (\Throwable) {
            return null;
        }

        return $effectiveUrl;
    }
}
