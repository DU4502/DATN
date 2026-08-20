<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;

use App\Models\Product;
use App\Models\Category;
use App\Models\Favorite;
use App\Models\Branch;
use App\Support\OrderDistancePolicy;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Display homepage
     */
    public function index()
    {
        // Get featured products
        $featuredProducts = Product::where('status', true)
            ->latest()
            ->take(8)
            ->get();

        // Get all categories (include soft-deleted, hide only after force delete)
        $categories = Category::withTrashed()->orderBy('name')->get();

        $discoverProductSlugs = [
            'nuoc-ep-cam',
            'sinh-to-bo',
            'tra-dao-cam-sa',
            'ca-phe-u-lanh',
        ];

        $discoverProductsBySlug = Product::query()
            ->with('category')
            ->where('status', true)
            ->whereIn('slug', $discoverProductSlugs)
            ->get()
            ->keyBy('slug');

        $discoverProducts = collect($discoverProductSlugs)
            ->map(fn (string $slug) => $discoverProductsBySlug->get($slug))
            ->filter()
            ->values();

        $favoriteProductIds = auth()->check()
            ? Favorite::where('user_id', auth()->id())->pluck('product_id')
            : collect();

        $branchId = session('nearest_branch_id');
        $branch = $branchId ? Branch::find($branchId) : null;

        $slides = $branch ? $branch->slides()->where('is_active', true)->get() : collect();

        return view('client.home', compact(
            'featuredProducts',
            'categories',
            'discoverProducts',
            'favoriteProductIds',
            'slides',
            'branch'
        ));
    }

    public function selectNearestBranch(Request $request)
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
        ]);

        $latitude = (float) $validated['latitude'];
        $longitude = (float) $validated['longitude'];

        $branches = \App\Models\Branch::availableForLocation()->get();

        if ($branches->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Không có chi nhánh khả dụng.',
            ]);
        }

        $nearestBranch = null;
        $minDistance = INF;

        // Haversine chỉ lọc sơ bộ, quyết định cuối cùng dùng road-route.
        $candidates = $branches
            ->filter(fn (Branch $branch) => $branch->distanceTo($latitude, $longitude) <= OrderDistancePolicy::MAX_DISTANCE_KM)
            ->sortBy(fn (Branch $branch) => $branch->distanceTo($latitude, $longitude));

        foreach ($candidates as $b) {
            $distance = OrderDistancePolicy::distanceFromBranch($b, $latitude, $longitude);
            if ($distance !== null && OrderDistancePolicy::isInsideServiceRadius($distance) && $distance < $minDistance) {
                $minDistance = $distance;
                $nearestBranch = $b;
            }
        }

        if ($nearestBranch) {
            $oldBranchId = session('nearest_branch_id');
            session([
                'nearest_branch_id' => $nearestBranch->id,
                'user_lat'          => $latitude,
                'user_lng'          => $longitude,
            ]);

            // Lưu tọa độ vào DB nếu user đã đăng nhập
            if (auth()->check()) {
                auth()->user()->updateQuietly([
                    'latitude'  => $latitude,
                    'longitude' => $longitude,
                ]);
            }

            return response()->json([
                'success' => true,
                'changed' => $oldBranchId != $nearestBranch->id,
                'branch' => [
                    'id'   => $nearestBranch->id,
                    'name' => $nearestBranch->name,
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $candidates->isNotEmpty()
                ? OrderDistancePolicy::routingUnavailableMessage()
                : OrderDistancePolicy::message(),
        ], $candidates->isNotEmpty() ? 503 : 422);
    }
}
