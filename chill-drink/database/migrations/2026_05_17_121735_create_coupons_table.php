<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->string('code', 50)->unique();
            $table->enum('type', ['percent', 'fixed'])->default('fixed');
            $table->integer('value');
            $table->decimal('max_discount', 10, 2)->nullable();
            $table->text('description')->nullable();
            $table->integer('min_order')->default(0);
            $table->integer('usage_limit')->default(1);
            $table->integer('used_count')->default(0);
            $table->datetime('starts_at')->nullable();
            $table->datetime('expires_at')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->enum('required_rank', ['bronze', 'silver', 'gold', 'diamond'])->nullable();
            $table->integer('point_cost')->default(0);
            $table->tinyInteger('is_redeemable')->default(0);
            $table->datetime('created_at')->nullable()->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
