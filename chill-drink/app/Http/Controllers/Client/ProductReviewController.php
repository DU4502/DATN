<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

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

        return back()->with('success', 'Đánh giá của bạn đã được lưu.');
    }

    private function nextEligibleCompletedOrderId(int $userId, int $productId): ?int
    {
        return \App\Models\Review::nextEligibleCompletedOrderId($userId, $productId);
    }
}
