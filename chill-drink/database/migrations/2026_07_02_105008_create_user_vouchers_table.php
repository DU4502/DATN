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
            $table->integer('voucher_id');
            $table->string('guest_identifier')->nullable();
            $table->timestamp('received_at')->useCurrent();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
            
            // Cài đặt khóa ngoại thủ công để khớp kiểu INT
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('voucher_id')->references('id')->on('coupons')->onDelete('cascade');
            
            // Add unique constraint for user + voucher
            $table->unique(['user_id', 'voucher_id']);
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
