<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ProductReviewController extends Controller
{
    public function store(Request $request, Product $product): RedirectResponse
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

        Review::create([
            'user_id' => $request->user()->id,
            'product_id' => $product->id,
            'order_id' => $eligibleOrderId,
            'rating' => (int) $validated['rating'],
            'comment' => filled($validated['comment'] ?? null) ? trim((string) $validated['comment']) : null,
            'status' => true,
        ]);

        return back()->with('success', 'Đánh giá của bạn đã được lưu.');
    }

    private function nextEligibleCompletedOrderId(int $userId, int $productId): ?int
    {
        if (! Schema::hasTable('orders') || ! Schema::hasTable('order_items')) {
            return null;
        }

        $query = Order::query()
            ->select('orders.id')
            ->join('order_items', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.user_id', $userId)
            ->where('order_items.product_id', $productId)
            ->whereNotExists(function ($subQuery) use ($userId, $productId) {
                $subQuery->selectRaw('1')
                    ->from('reviews')
                    ->whereColumn('reviews.order_id', 'orders.id')
                    ->where('reviews.user_id', $userId)
                    ->where('reviews.product_id', $productId);
            });

        if (Schema::hasColumn('orders', 'status')) {
            $query->where('orders.status', 'completed');
        }

        if (Schema::hasColumn('orders', 'created_at')) {
            $query->latest('orders.created_at');
        } else {
            $query->orderByDesc('orders.id');
        }

        return $query->value('orders.id');
    }
}
