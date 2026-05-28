<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_item_toppings', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->integer('order_item_id');
            $table->integer('topping_id');
            $table->integer('price')->default(0);

            $table->foreign('order_item_id')->references('id')->on('order_items')->onDelete('cascade');
            $table->foreign('topping_id')->references('id')->on('toppings');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_toppings');
    }
};
