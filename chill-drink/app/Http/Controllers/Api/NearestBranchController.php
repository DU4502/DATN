<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Support\OrderDistancePolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NearestBranchController extends Controller
{
    public function nearest(Request $request): JsonResponse
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

        $nearestBranch = null;
        $minDistance = INF;

        foreach ($branches as $branch) {
            $distance = $branch->distanceTo($latitude, $longitude);

            if (OrderDistancePolicy::isInsideServiceRadius($distance) && $distance < $minDistance) {
                $minDistance = $distance;
                $nearestBranch = $branch;
            }
        }

        if (! $nearestBranch) {
            return response()->json([
                'success' => false,
                'message' => OrderDistancePolicy::message(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã tìm thấy chi nhánh gần nhất.',
            'data' => [
                'id' => $nearestBranch->id,
                'name' => $nearestBranch->name,
                'code' => $nearestBranch->code,
                'phone' => $nearestBranch->phone,
                'email' => $nearestBranch->email,
                'address' => $nearestBranch->address,
                'latitude' => $nearestBranch->latitude,
                'longitude' => $nearestBranch->longitude,
                'distance_km' => round($minDistance, 2),
            ],
        ]);
    }

    public function list(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
        ]);

        $latitude = (float) $validated['latitude'];
        $longitude = (float) $validated['longitude'];

        $result = Branch::availableForLocation()
            ->get()
            ->map(function (Branch $branch) use ($latitude, $longitude) {
                $distance = $branch->distanceTo($latitude, $longitude);

                return [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'code' => $branch->code,
                    'address' => $branch->address,
                    'phone' => $branch->phone,
                    'distance_km' => $distance,
                ];
            })
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
