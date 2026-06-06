<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->removeDuplicateReviews();
        $this->removeDuplicateProductSizes();

        Schema::table('reviews', function (Blueprint $table) {
            $table->unique(['user_id', 'product_id', 'order_id'], 'uniq_reviews_user_product_order');
        });

        Schema::table('product_sizes', function (Blueprint $table) {
            $table->unique(['product_id', 'size_id'], 'uniq_product_sizes_product_size');
        });

        Schema::table('coupons', function (Blueprint $table) {
            $table->index(['status', 'starts_at', 'expires_at', 'created_at'], 'idx_coupons_active_window');
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropIndex('idx_coupons_active_window');
        });

        Schema::table('product_sizes', function (Blueprint $table) {
            $table->dropUnique('uniq_product_sizes_product_size');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropUnique('uniq_reviews_user_product_order');
        });
    }

    private function removeDuplicateReviews(): void
    {
        $duplicateIds = DB::table('reviews as current')
            ->join('reviews as keeper', function ($join) {
                $join->on('current.user_id', '=', 'keeper.user_id')
                    ->on('current.product_id', '=', 'keeper.product_id')
                    ->on('current.order_id', '=', 'keeper.order_id')
                    ->whereColumn('current.id', '>', 'keeper.id');
            })
            ->pluck('current.id');

        if ($duplicateIds->isNotEmpty()) {
            DB::table('reviews')->whereIn('id', $duplicateIds)->delete();
        }
    }

    private function removeDuplicateProductSizes(): void
    {
        $duplicateIds = DB::table('product_sizes as current')
            ->join('product_sizes as keeper', function ($join) {
                $join->on('current.product_id', '=', 'keeper.product_id')
                    ->on('current.size_id', '=', 'keeper.size_id')
                    ->whereColumn('current.id', '>', 'keeper.id');
            })
            ->pluck('current.id');

        if ($duplicateIds->isNotEmpty()) {
            DB::table('product_sizes')->whereIn('id', $duplicateIds)->delete();
        }
    }
};
