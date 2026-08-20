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
        Schema::create('user_vouchers', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable();
            $table->integer('coupon_id');
            $table->string('guest_identifier', 100)->nullable()->index();
            $table->string('code', 50);
            $table->boolean('is_used')->default(false);
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('used_at')->nullable();
            $table->dateTime('redeemed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'coupon_id']);
            $table->index(['guest_identifier', 'coupon_id']);

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('coupon_id')->references('id')->on('coupons')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_vouchers');
    }
};
