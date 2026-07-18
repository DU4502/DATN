<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;

use App\Models\Product;
use App\Models\Category;
use App\Models\Favorite;
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

        // Get all categories
        $categories = Category::orderBy('name')->get();

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
        $branch = null;
        if ($branchId) {
            $branch = \App\Models\Branch::find($branchId);
        }
        if (!$branch) {
            $branch = \App\Models\Branch::first();
        }

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

        foreach ($branches as $b) {
            $distance = $b->distanceTo($latitude, $longitude);
            if ($distance < $minDistance) {
                $minDistance = $distance;
                $nearestBranch = $b;
            }
        }

        if ($nearestBranch) {
            $oldBranchId = session('nearest_branch_id');
            session(['nearest_branch_id' => $nearestBranch->id]);
            return response()->json([
                'success' => true,
                'changed' => $oldBranchId !== $nearestBranch->id,
                'branch' => [
                    'id' => $nearestBranch->id,
                    'name' => $nearestBranch->name,
                ]
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Không tìm thấy chi nhánh gần nhất.']);
    }
}
