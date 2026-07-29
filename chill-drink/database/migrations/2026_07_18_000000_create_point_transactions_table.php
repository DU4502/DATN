<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('point_transactions', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('points'); // có thể âm (tiêu điểm)
            $table->string('type', 20); // earn, spend, expire, adjust, refund
            $table->string('reference_type', 50)->nullable(); // order, voucher
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('description')->nullable();
            $table->integer('balance_after')->default(0);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_transactions');
    }
};
