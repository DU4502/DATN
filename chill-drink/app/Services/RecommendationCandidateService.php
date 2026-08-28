<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\OrderItem;
use App\Models\Product;
use App\Support\OrderStatus;
use Illuminate\Support\Collection;

class RecommendationCandidateService
{
    public const POPULARITY_MAX_SCORE = 25;

    /**
     * @return Collection<int, array{product: Product, sales_30d: int, popularity_score: int}>
     */
    public function forBranch(Branch $branch): Collection
    {
        $candidates = Product::query()
            ->where('status', true)
            ->whereHas('branchStatuses', fn ($query) => $query
                ->where('branch_id', $branch->id)
                ->where('is_available', true))
            ->with([
                'category',
                'branchStatuses' => fn ($query) => $query->where('branch_id', $branch->id),
                'sizes',
            ])
            ->withCount(['reviews' => fn ($query) => $query->where('status', true)])
            ->withAvg(['reviews' => fn ($query) => $query->where('status', true)], 'rating')
            ->orderBy('products.id')
            ->get();

        if ($candidates->isEmpty()) {
            return collect();
        }

        $salesByProduct = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('order_items.product_id', $candidates->modelKeys())
            ->where('orders.branch_id', $branch->id)
            ->where('orders.status', OrderStatus::COMPLETED)
            ->where('orders.created_at', '>=', now()->subDays(30))
            ->selectRaw('order_items.product_id, SUM(order_items.quantity) as sales_30d')
            ->groupBy('order_items.product_id')
            ->pluck('sales_30d', 'order_items.product_id')
            ->map(fn ($quantity): int => (int) $quantity);

        $maxSales = (int) ($salesByProduct->max() ?? 0);

        return $candidates->map(function (Product $product) use ($salesByProduct, $maxSales): array {
            $sales = (int) $salesByProduct->get($product->getKey(), 0);
            $popularityScore = $maxSales === 0
                ? 0
                : (int) round(($sales / $maxSales) * self::POPULARITY_MAX_SCORE);

            return [
                'product' => $product,
                'sales_30d' => $sales,
                'popularity_score' => max(0, min(self::POPULARITY_MAX_SCORE, $popularityScore)),
            ];
        })->values();
    }
}
