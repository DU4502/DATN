<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\DeliveryRoutingService;
use App\Support\OrderDistancePolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NearestBranchController extends Controller
{
    public function nearest(Request $request, DeliveryRoutingService $routing): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
        ]);

        $latitude = (float) $validated['latitude'];
        $longitude = (float) $validated['longitude'];

        $branches = Branch::availableForLocation()->get();

        if ($branches->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Không có chi nhánh nào khả dụng.',
            ], 404);
        }

        // Haversine chỉ lọc sơ bộ: nếu đường chim bay đã > 15 km thì
        // tuyến đường thực tế chắc chắn không thể <= 15 km.
        $candidates = $branches
            ->map(fn (Branch $branch) => [
                'branch' => $branch,
                'air_km' => $branch->distanceTo($latitude, $longitude),
            ])
            ->filter(fn (array $row) => $row['air_km'] <= OrderDistancePolicy::MAX_DISTANCE_KM)
            ->sortBy('air_km')
            ->values();

        $best = null;

        foreach ($candidates as $candidate) {
            /** @var Branch $branch */
            $branch = $candidate['branch'];
            $route = $routing->route(
                (float) $branch->latitude,
                (float) $branch->longitude,
                $latitude,
                $longitude
            );
            if ((bool) ($route['fallback'] ?? true)) {
                continue;
            }

            $distanceKm = round(((float) ($route['distance_m'] ?? 0)) / 1000, 3);

            if (! OrderDistancePolicy::isInsideServiceRadius($distanceKm)) {
                continue;
            }

            if ($best === null || $distanceKm < $best['distance_km']) {
                $best = [
                    'branch' => $branch,
                    'distance_km' => $distanceKm,
                    'duration_s' => (float) ($route['duration_s'] ?? 0),
                    'route_source' => (string) ($route['source'] ?? 'unknown'),
                    'route_fallback' => (bool) ($route['fallback'] ?? false),
                ];
            }
        }

        if (! $best) {
            return response()->json([
                'success' => false,
                'message' => $candidates->isNotEmpty()
                    ? OrderDistancePolicy::routingUnavailableMessage()
                    : OrderDistancePolicy::message(),
            ], $candidates->isNotEmpty() ? 503 : 422);
        }

        /** @var Branch $nearestBranch */
        $nearestBranch = $best['branch'];

        return response()->json([
            'success' => true,
            'message' => 'Đã tìm thấy chi nhánh gần nhất theo lộ trình đường bộ.',
            'data' => [
                'id' => $nearestBranch->id,
                'name' => $nearestBranch->name,
                'code' => $nearestBranch->code,
                'phone' => $nearestBranch->phone,
                'email' => $nearestBranch->email,
                'address' => $nearestBranch->address,
                'latitude' => $nearestBranch->latitude,
                'longitude' => $nearestBranch->longitude,
                'distance_km' => round($best['distance_km'], 2),
                'duration_s' => $best['duration_s'],
                'route_source' => $best['route_source'],
                'route_fallback' => $best['route_fallback'],
            ],
        ]);
    }

    public function list(Request $request, DeliveryRoutingService $routing): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
        ]);

        $latitude = (float) $validated['latitude'];
        $longitude = (float) $validated['longitude'];

        $result = Branch::availableForLocation()
            ->get()
            ->map(fn (Branch $branch) => [
                'branch' => $branch,
                'air_km' => $branch->distanceTo($latitude, $longitude),
            ])
            ->filter(fn (array $row) => $row['air_km'] <= OrderDistancePolicy::MAX_DISTANCE_KM)
            ->sortBy('air_km')
            ->map(function (array $row) use ($latitude, $longitude, $routing) {
                /** @var Branch $branch */
                $branch = $row['branch'];
                $route = $routing->route(
                    (float) $branch->latitude,
                    (float) $branch->longitude,
                    $latitude,
                    $longitude
                );
                if ((bool) ($route['fallback'] ?? true)) {
                    return null;
                }

                $distanceKm = round(((float) ($route['distance_m'] ?? 0)) / 1000, 3);

                return [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'code' => $branch->code,
                    'address' => $branch->address,
                    'phone' => $branch->phone,
                    'latitude' => $branch->latitude,
                    'longitude' => $branch->longitude,
                    'distance_km' => $distanceKm,
                    'duration_s' => (float) ($route['duration_s'] ?? 0),
                    'route_source' => (string) ($route['source'] ?? 'unknown'),
                    'route_fallback' => (bool) ($route['fallback'] ?? false),
                ];
            })
            ->filter()
            ->filter(fn (array $branch) => OrderDistancePolicy::isInsideServiceRadius((float) $branch['distance_km']))
            ->sortBy('distance_km')
            ->map(function (array $branch) {
                $branch['distance_km'] = round((float) $branch['distance_km'], 2);

                return $branch;
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}
