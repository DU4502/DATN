<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    /**
     * Tìm chi nhánh gần nhất theo vị trí khách hàng
     */
    public function nearest(Request $request)
    {
        $request->validate([
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $latitude = (float) $request->latitude;
        $longitude = (float) $request->longitude;

        // Lấy các chi nhánh đang hoạt động
        $branches = Branch::availableForLocation()->get();

        if ($branches->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Không có chi nhánh nào khả dụng.'
            ], 404);
        }

        // Tính khoảng cách
        $nearestBranch = null;
        $minDistance = PHP_FLOAT_MAX;

        foreach ($branches as $branch) {

            $distance = $branch->distanceTo($latitude, $longitude);

            if ($distance < $minDistance) {
                $minDistance = $distance;
                $nearestBranch = $branch;
            }
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
            ]
        ]);
    }

    /**
     * Danh sách chi nhánh kèm khoảng cách
     */
    public function list(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $latitude = (float) $request->latitude;
        $longitude = (float) $request->longitude;

        $branches = Branch::availableForLocation()->get();

        $result = $branches->map(function ($branch) use ($latitude, $longitude) {

            return [
                'id' => $branch->id,
                'name' => $branch->name,
                'code' => $branch->code,
                'address' => $branch->address,
                'phone' => $branch->phone,
                'distance_km' => round(
                    $branch->distanceTo($latitude, $longitude),
                    2
                ),
            ];
        })->sortBy('distance_km')->values();

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }
}
