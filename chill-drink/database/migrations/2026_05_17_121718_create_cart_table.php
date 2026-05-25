<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cart', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->integer('user_id')->index();
            $table->integer('product_id')->index();
            $table->integer('quantity')->default(1);
            $table->string('size', 10)->nullable();
            $table->string('sugar_level', 10)->nullable();
            $table->string('ice_level', 10)->nullable();
            $table->string('note', 255)->nullable();
            $table->datetime('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart');
    }
};
