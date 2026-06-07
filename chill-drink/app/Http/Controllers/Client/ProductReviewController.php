<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ProductReviewController extends Controller
{
    public function store(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ], [
            'rating.required' => 'Vui lòng chọn số sao đánh giá.',
            'rating.between' => 'Số sao đánh giá không hợp lệ.',
            'comment.max' => 'Nội dung đánh giá tối đa 1000 ký tự.',
        ]);

        $eligibleOrderId = $this->nextEligibleCompletedOrderId($request->user()->id, $product->id);

        if (! $eligibleOrderId) {
            return back()->with('error', 'Mỗi lần mua chỉ được đánh giá một lần. Bạn cần có đơn hoàn tất chưa dùng để đánh giá sản phẩm này.');
        }

        $review = Review::create([
            'user_id' => $request->user()->id,
            'product_id' => $product->id,
            'order_id' => $eligibleOrderId,
            'rating' => (int) $validated['rating'],
            'comment' => filled($validated['comment'] ?? null) ? trim((string) $validated['comment']) : null,
            'status' => true,
        ]);

        $review->loadMissing('user');

        $summary = $this->summaryForProduct($product->id);
        $remainingReviews = $this->remainingReviewCount($request->user()->id, $product->id);
        $canReview = $remainingReviews > 0;
        $infoMessage = null;

        // After storing review, if all products in the order have been reviewed by the user,
        // mark any related review reminder notifications as read.
        try {
            $orderId = $eligibleOrderId;
            $order = Order::with('orderItems')->find($orderId);

            if ($order) {
                $productIds = $order->orderItems->pluck('product_id')->filter()->unique()->values()->all();

                $remaining = Review::query()
                    ->where('user_id', $request->user()->id)
                    ->where('order_id', $orderId)
                    ->whereIn('product_id', $productIds)
                    ->pluck('product_id')
                    ->unique()
                    ->count();

                $totalProducts = count($productIds);

                if ($totalProducts > 0 && $remaining >= $totalProducts) {
                    $request->user()->notifications()
                        ->where('type', \App\Notifications\ReviewAvailableNotification::class)
                        ->whereJsonContains('data->order_id', $orderId)
                        ->get()
                        ->each(function ($n) {
                            if (is_null($n->read_at)) {
                                $n->markAsRead();
                            }
                        });
                }
            }
        } catch (\Throwable $e) {
            // non-fatal; notification cleanup best-effort
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Đánh giá của bạn đã được lưu.',
                'review' => [
                    'id' => $review->id,
                    'user_name' => $review->user?->name ?? 'Khách hàng',
                    'initial' => mb_substr($review->user?->name ?? 'U', 0, 1),
                    'rating' => (int) $review->rating,
                    'comment' => $review->comment,
                    'created_at' => optional($review->created_at)->format('d/m/Y H:i'),
                ],
                'summary' => $summary,
                'remaining_reviews' => $remainingReviews,
                'can_review' => $canReview,
                'info_message' => $infoMessage,
            ]);
        }

        return back()->with('success', 'Đánh giá của bạn đã được lưu.');
    }

    private function nextEligibleCompletedOrderId(int $userId, int $productId): ?int
    {
        return \App\Models\Review::nextEligibleCompletedOrderId($userId, $productId);
    }

    private function summaryForProduct(int $productId): array
    {
        $baseQuery = Review::query()
            ->where('product_id', $productId)
            ->where('status', true);

        $count = (clone $baseQuery)->count();
        $average = $count > 0
            ? round((float) (clone $baseQuery)->avg('rating'), 1)
            : 0.0;

        $counts = array_fill(1, 5, 0);

        $baseQuery
            ->selectRaw('rating, COUNT(*) as total')
            ->groupBy('rating')
            ->pluck('total', 'rating')
            ->each(function ($total, $rating) use (&$counts) {
                $rating = (int) $rating;
                if ($rating >= 1 && $rating <= 5) {
                    $counts[$rating] = (int) $total;
                }
            });

        return [
            'count' => $count,
            'average' => $average,
            'counts' => $counts,
        ];
    }

    private function remainingReviewCount(int $userId, int $productId): int
    {
        return Order::query()
            ->join('order_items', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.user_id', $userId)
            ->where('order_items.product_id', $productId)
            ->when(
                Schema::hasColumn('orders', 'status'),
                fn($query) => $query->where('orders.status', 'completed')
            )
            ->whereNotExists(function ($subQuery) use ($userId, $productId) {
                $subQuery->selectRaw('1')
                    ->from('reviews')
                    ->whereColumn('reviews.order_id', 'orders.id')
                    ->where('reviews.user_id', $userId)
                    ->where('reviews.product_id', $productId);
            })
            ->distinct('orders.id')
            ->count('orders.id');
    }
}
