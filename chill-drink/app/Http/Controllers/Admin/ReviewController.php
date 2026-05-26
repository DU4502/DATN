<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;

class ReviewController extends Controller
{
    public function index()
    {
        $hasReviewTable = Schema::hasTable('reviews');
        $reviews = new LengthAwarePaginator([], 0, 12, 1, [
            'path' => request()->url(),
            'pageName' => 'page',
        ]);
        $totalReviews = 0;
        $averageRating = 0;
        $weekReviews = 0;
        $monthReviews = 0;

        if ($hasReviewTable) {
            $reviewQuery = Review::with(['user', 'product.category']);

            if (Schema::hasColumn('reviews', 'created_at')) {
                $reviewQuery->latest();
            } else {
                $reviewQuery->orderByDesc('id');
            }

            $reviews = $reviewQuery->paginate(12);
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
            'reviews',
            'totalReviews',
            'averageRating',
            'weekReviews',
            'monthReviews'
        ));
    }
}
