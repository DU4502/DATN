<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('branch_slides', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $blueprint->string('product_name');
            $blueprint->string('title')->nullable();
            $blueprint->string('price')->nullable();
            $blueprint->string('image')->nullable();
            $blueprint->string('bg_color')->default('#5d9c59');
            $blueprint->text('description')->nullable();
            $blueprint->integer('sort_order')->default(0);
            $blueprint->boolean('is_active')->default(true);
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_slides');
    }
};
