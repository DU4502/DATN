<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->integer('category_id')->nullable();
            $table->string('name', 200)->index();
            $table->string('slug', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('image', 255)->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->integer('stock')->default(100);
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
            $table->softDeletes()->index();

            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
