<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_vouchers', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->integer('user_id');
            $table->integer('coupon_id');
            $table->string('code', 100)->nullable()->comment('Mã voucher cụ thể nếu có');
            $table->tinyInteger('is_used')->default(0)->comment('0: Chưa dùng, 1: Đã dùng');
            $table->datetime('expires_at')->nullable()->comment('Ngày hết hạn sử dụng');
            $table->datetime('redeemed_at')->nullable()->useCurrent()->comment('Ngày nhận/đổi voucher');
            $table->timestamps();

            // Khóa ngoại liên kết tới bảng users và coupons đã tạo trước đó
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('coupon_id')->references('id')->on('coupons')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_vouchers');
    }
};
