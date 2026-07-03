<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'product_id',
        'order_id',
        'rating',
        'comment',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the review
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the product that owns the review
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Determine the next eligible completed order id for a user and product.
     * Reused by ProductReviewController to check review eligibility.
     */
    public static function nextEligibleCompletedOrderId(int $userId, int $productId): ?int
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('orders') || ! \Illuminate\Support\Facades\Schema::hasTable('order_items')) {
            return null;
        }

        $query = \App\Models\Order::query()
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

        if (\Illuminate\Support\Facades\Schema::hasColumn('orders', 'status')) {
            $query->where('orders.status', 'completed');
        }

        // intentionally no debug logging in production/test

        if (\Illuminate\Support\Facades\Schema::hasColumn('orders', 'created_at')) {
            $query->latest('orders.created_at');
        } else {
            $query->orderByDesc('orders.id');
        }

        return $query->value('orders.id');
    }
}
