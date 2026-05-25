<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->integer('order_id');
            $table->integer('product_id');
            $table->integer('product_size_id');
            $table->integer('ice_level')->default(100)->comment('Đá %');
            $table->integer('sugar_level')->default(100)->comment('Đường %');
            $table->integer('quantity')->default(1);
            $table->integer('unit_price');
            $table->integer('total_price');
            $table->datetime('created_at')->nullable()->useCurrent();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('product_size_id')->references('id')->on('product_sizes');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
