<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index(['status', 'slug'], 'idx_products_status_slug');
            $table->index(['status', 'category_id', 'created_at'], 'idx_products_status_category_created');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->index(['product_id', 'status', 'created_at'], 'idx_reviews_product_status_created');
            $table->index(['user_id', 'product_id', 'order_id'], 'idx_reviews_user_product_order');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->index(['user_id', 'status', 'created_at'], 'idx_orders_user_status_created');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->index(['product_id', 'order_id'], 'idx_order_items_product_order');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex('idx_order_items_product_order');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('idx_orders_user_status_created');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex('idx_reviews_user_product_order');
            $table->dropIndex('idx_reviews_product_status_created');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_status_category_created');
            $table->dropIndex('idx_products_status_slug');
        });
    }
};
