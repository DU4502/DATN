<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ProductAvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 12);
        $search = trim((string) ($validated['search'] ?? ''));

        $branch = app(ProductAvailabilityService::class)->currentBranch();
        $products = Product::query()
            ->select(['id', 'category_id', 'name', 'slug', 'sku', 'image', 'price', 'description', 'status'])
            ->with(['category:id,name,slug'])
            ->with(['branchStatuses' => fn ($query) => $query->when($branch, fn ($statusQuery) => $statusQuery->where('branch_id', $branch->id))])
            ->withCount([
                'reviews as approved_reviews_count' => fn ($query) => $query->where('status', true),
            ])
            ->withAvg([
                'reviews as approved_reviews_avg_rating' => fn ($query) => $query->where('status', true),
            ], 'rating')
            ->where('status', true)
            ->when(
                ! empty($validated['category_id']),
                fn ($query) => $query->where('category_id', (int) $validated['category_id'])
            )
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('name', 'like', '%'.$search.'%')
                        ->orWhere('slug', 'like', '%'.$search.'%');
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $products->through(fn (Product $product) => $this->productPayload($product, $branch));

        return response()->json($products);
    }

    public function show(Product $product): JsonResponse
    {
        abort_unless((bool) $product->status, 404);

        $branch = app(ProductAvailabilityService::class)->currentBranch();
        $product->load([
            'category:id,name,slug',
            'branchStatuses' => fn ($query) => $query->when($branch, fn ($statusQuery) => $statusQuery->where('branch_id', $branch->id)),
            'reviews' => fn ($query) => $query
                ->select(['id', 'user_id', 'product_id', 'order_id', 'rating', 'comment', 'status', 'created_at'])
                ->where('status', true)
                ->with('user:id,name')
                ->latest(),
        ])->loadCount([
            'reviews as approved_reviews_count' => fn ($query) => $query->where('status', true),
        ])->loadAvg([
            'reviews as approved_reviews_avg_rating' => fn ($query) => $query->where('status', true),
        ], 'rating');

        return response()->json([
            'data' => array_merge(
                $this->productPayload($product, $branch),
                [
                    'gallery_images' => $product->gallery_images,
                    'reviews' => $product->reviews->map(fn ($review) => [
                        'id' => $review->id,
                        'user_name' => $review->user?->name,
                        'rating' => (int) $review->rating,
                        'comment' => $review->comment,
                        'created_at' => optional($review->created_at)?->toISOString(),
                    ])->values(),
                ]
            ),
        ]);
    }

    private function productPayload(Product $product, $branch = null): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'price' => (int) $product->price,
            'branch' => $branch ? ['id' => $branch->id, 'name' => $branch->name] : null,
            'is_available' => $product->availabilityAt($branch),
            'description' => $product->display_description,
            'image_url' => $product->image_url,
            'category' => $product->category ? [
                'id' => $product->category->id,
                'name' => $product->category->name,
                'slug' => $product->category->slug,
            ] : null,
            'review_count' => (int) ($product->approved_reviews_count ?? 0),
            'average_rating' => round((float) ($product->approved_reviews_avg_rating ?? 0), 1),
        ];
    }
}
