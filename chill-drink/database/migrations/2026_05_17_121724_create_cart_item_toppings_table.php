<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cart_item_toppings', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->integer('cart_id')->index();
            $table->integer('topping_id')->index();

            $table->foreign('cart_id')->references('id')->on('cart')->onDelete('cascade');
            $table->foreign('topping_id')->references('id')->on('toppings')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_item_toppings');
    }
};
