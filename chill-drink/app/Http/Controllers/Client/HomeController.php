<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Favorite;
use App\Models\Product;
use App\Services\DrinkRecommendationService;
use App\Services\ProductAvailabilityService;
use App\Support\OrderDistancePolicy;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HomeController extends Controller
{
    /**
     * Display homepage
     */
    public function index(DrinkRecommendationService $recommendationService)
    {
        $branch = app(ProductAvailabilityService::class)->currentBranch();
        $recommendationResult = $branch
            ? $recommendationService->forBranch($branch)
            : [
                'weather' => null,
                'weather_available' => false,
                'mode' => 'empty',
                'recommendations' => collect(),
            ];
        $recommendationResult['message'] = $this->recommendationMessage($recommendationResult);

        $recommendations = $recommendationResult['recommendations'] ?? collect();
        if ($recommendations instanceof \Illuminate\Support\Collection && $recommendations->isNotEmpty()) {
            (new \Illuminate\Database\Eloquent\Collection($recommendations->pluck('product')->filter()))->loadMissing('toppings');
        }

        // Get all categories (include soft-deleted, hide only after force delete)
        $categories = Category::withTrashed()->orderBy('name')->get();

        $discoverProductSlugs = [
            'nuoc-ep-cam',
            'sinh-to-bo',
            'tra-dao-cam-sa',
            'ca-phe-u-lanh',
        ];

        $discoverProductsBySlug = Product::query()
            ->with([
                'category',
                'sizes',
                'toppings',
                'branchStatuses' => fn ($query) => $query->when($branch, fn ($statusQuery) => $statusQuery->where('branch_id', $branch->id)),
            ])
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

        $slides = $branch ? $branch->slides()->where('is_active', true)->get() : collect();

        $featuredVouchers = \App\Models\Voucher::query()
            ->where('status', true)
            ->where('show_on_products', true)
            ->where(function ($query) {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            })
            ->where(function ($query) {
                $query->where('usage_limit', '<=', 0)
                    ->orWhereRaw('used_count < usage_limit');
            })
            ->orderBy('created_at', 'desc')
            ->limit(4)
            ->get();

        return view('client.home', compact(
            'recommendationResult',
            'categories',
            'discoverProducts',
            'favoriteProductIds',
            'slides',
            'branch',
            'featuredVouchers'
        ));
    }

    private function recommendationMessage(array $result): ?string
    {
        if (($result['mode'] ?? null) === 'popularity_fallback') {
            return 'Những món đang được yêu thích tại chi nhánh này.';
        }

        $weather = $result['weather'] ?? null;
        if (($result['mode'] ?? null) !== 'weather' || ! $weather) {
            return null;
        }

        return match (true) {
            $weather->isRaining => 'Trời đang mưa, Chill Drink đã chọn một vài món phù hợp cho bạn.',
            $weather->temperatureC >= 35 => 'Hôm nay khá nóng! Thử một món mát lạnh nhé.',
            $weather->temperatureC >= 30 => 'Thời tiết khá nóng, đây là vài gợi ý phù hợp.',
            $weather->temperatureC < 20 => 'Hôm nay khá mát, Chill Drink đã chọn một vài món phù hợp cho bạn.',
            default => 'Thời tiết hôm nay khá dễ chịu, đây là vài gợi ý dành cho bạn.',
        };
    }

    /**
     * Change the branch used across the customer storefront.
     */
    public function selectBranch(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => [
                'required',
                'integer',
                Rule::exists('branches', 'id')->where(fn ($query) => $query->where('status', true)),
            ],
        ], [
            'branch_id.required' => 'Vui lòng chọn chi nhánh.',
            'branch_id.exists' => 'Chi nhánh đã chọn không còn hoạt động.',
        ]);

        $branch = Branch::query()->where('status', true)->findOrFail((int) $validated['branch_id']);

        session([
            'nearest_branch_id' => $branch->id,
            'branch_selection_mode' => 'manual',
        ]);

        $branchName = str_starts_with($branch->name, 'Chi nhánh')
            ? $branch->name
            : "Chi nhánh {$branch->name}";

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'branch' => [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'display_name' => $branchName,
                ],
            ]);
        }

        return back()->with('success', "Đã chuyển sang {$branchName}.");
    }

    public function selectNearestBranch(Request $request)
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
                'branch_selection_mode' => 'automatic',
                'user_lat' => $latitude,
                'user_lng' => $longitude,
            ]);

            // Lưu tọa độ vào DB nếu user đã đăng nhập
            if (auth()->check()) {
                auth()->user()->updateQuietly([
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ]);
            }

            return response()->json([
                'success' => true,
                'changed' => $oldBranchId != $nearestBranch->id,
                'branch' => [
                    'id' => $nearestBranch->id,
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
