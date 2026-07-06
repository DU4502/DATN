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
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('voucher_id')->constrained('coupons')->onDelete('cascade');
            $table->string('guest_identifier')->nullable();
            $table->timestamp('received_at')->useCurrent();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
            
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
