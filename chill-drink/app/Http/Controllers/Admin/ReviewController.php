<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $hasReviewTable = Schema::hasTable('reviews');
        $hasReviewStatusColumn = $hasReviewTable && Schema::hasColumn('reviews', 'status');
        $statusFilter = $request->string('status')->trim()->toString();
        $reviews = new LengthAwarePaginator([], 0, 12, 1, [
            'path' => request()->url(),
            'pageName' => 'page',
        ]);
        $totalReviews = 0;
        $averageRating = 0;
        $weekReviews = 0;
        $monthReviews = 0;

        if ($hasReviewTable) {
            $reviewQuery = Review::with(['user', 'product.category', 'order'])
                ->when($request->filled('rating'), fn ($q) => $q->where('rating', (int) $request->rating))
                ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', (int) $request->product_id))
                ->when($hasReviewStatusColumn && $statusFilter === 'visible', fn ($q) => $q->where('status', true))
                ->when($hasReviewStatusColumn && $statusFilter === 'hidden', fn ($q) => $q->where('status', false))
                ->when($request->filled('q'), function ($q) use ($request) {
                    $search = $request->q;
                    $q->where(function ($inner) use ($search) {
                        $inner->where('comment', 'like', "%{$search}%")
                            ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
                    });
                });

            if (Schema::hasColumn('reviews', 'created_at')) {
                $reviewQuery->latest();
            } else {
                $reviewQuery->orderByDesc('id');
            }

            $reviews = $reviewQuery->paginate(12)->withQueryString();
            $totalReviews = Review::count();
            $averageRating = round((float) Review::avg('rating'), 1);

            if (Schema::hasColumn('reviews', 'created_at')) {
                $weekReviews = Review::whereBetween('created_at', [
                    Carbon::now()->startOfWeek(Carbon::MONDAY),
                    Carbon::now()->endOfWeek(Carbon::SUNDAY),
                ])->count();
                $monthReviews = Review::whereBetween('created_at', [
                    Carbon::now()->startOfMonth(),
                    Carbon::now()->endOfMonth(),
                ])->count();
            }
        }

        return view('admin.reviews.index', compact(
            'hasReviewTable',
            'hasReviewStatusColumn',
            'reviews',
            'totalReviews',
            'averageRating',
            'weekReviews',
            'monthReviews'
        ));
    }

    public function destroy(Review $review): RedirectResponse
    {
        $review->delete();
        return redirect()->back()->with('success', 'Đã xóa đánh giá thành công.');
    }

    public function toggleStatus(Request $request, Review $review)
    {
        if (Schema::hasColumn('reviews', 'status')) {
            $review->update(['status' => ! (bool) $review->status]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'visible' => (bool) $review->status,
                'review' => [
                    'id' => $review->id,
                    'status' => (bool) $review->status,
                ],
                'message' => 'Đã cập nhật trạng thái hiển thị đánh giá.',
            ]);
        }

        return redirect()->back()->with('success', 'Đã cập nhật trạng thái hiển thị đánh giá.');
    }
}
