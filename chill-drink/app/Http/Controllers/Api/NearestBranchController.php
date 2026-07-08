<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NearestBranchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'limit' => ['nullable', 'integer', 'between:1,5'],
        ]);

        $latitude = (float) $validated['latitude'];
        $longitude = (float) $validated['longitude'];
        $limit = (int) ($validated['limit'] ?? 3);

        $branches = Branch::query()
            ->availableForLocation()
            ->get()
            ->map(fn (Branch $branch) => [
                'id' => $branch->id,
                'name' => $branch->name,
                'code' => $branch->code,
                'address' => $branch->address,
                'phone' => $branch->phone,
                'latitude' => $branch->latitude,
                'longitude' => $branch->longitude,
                'distance_km' => round($branch->distanceTo($latitude, $longitude), 2),
            ])
            ->sortBy('distance_km')
            ->take($limit)
            ->values();

        return response()->json([
            'message' => $branches->isEmpty()
                ? 'Hiện chưa có chi nhánh hoạt động được cấu hình tọa độ.'
                : 'Đã tìm thấy chi nhánh gần vị trí của bạn.',
            'data' => $branches,
        ]);
    }
}
