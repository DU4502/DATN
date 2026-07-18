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
        // Bảng này đã được tạo ở migration 2026_05_17_121831.
        // Giữ guard để migrate:fresh hoạt động với cả database cũ lẫn mới.
        if (Schema::hasTable('user_vouchers')) {
            return;
        }

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
        // Không xóa bảng thuộc migration 2026_05_17_121831 khi rollback migration tương thích này.
    }
};
