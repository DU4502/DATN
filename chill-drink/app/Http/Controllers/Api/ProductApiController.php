<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
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

        $products = Product::query()
            ->select(['id', 'category_id', 'name', 'slug', 'sku', 'image', 'price', 'description', 'stock', 'status'])
            ->with(['category:id,name,slug'])
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

        $products->through(fn (Product $product) => $this->productPayload($product));

        return response()->json($products);
    }

    public function show(Product $product): JsonResponse
    {
        abort_unless((bool) $product->status, 404);

        $product->load([
            'category:id,name,slug',
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
                $this->productPayload($product),
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

    private function productPayload(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'price' => (int) $product->price,
            'stock' => (int) ($product->stock ?? 0),
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
