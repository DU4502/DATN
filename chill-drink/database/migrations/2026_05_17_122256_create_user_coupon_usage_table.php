<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_coupon_usage', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->integer('user_id');
            $table->integer('coupon_id');
            $table->integer('order_id');
            $table->decimal('discount_amount', 10, 2)->comment('Số tiền thực tế được giảm');
            $table->datetime('used_at')->nullable()->useCurrent();

            // Tạo index tối ưu tốc độ tìm kiếm mã của user
            $table->index(['user_id', 'coupon_id'], 'idx_user_coupon');
            $table->index('order_id', 'idx_order');

            // Ràng buộc khóa ngoại
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('coupon_id')->references('id')->on('coupons')->onDelete('cascade');
            // Do bảng orders phụ thuộc vào cấu trúc riêng, ta có thể tạm thời không ép khóa ngoại cứng hoặc bổ sung sau nếu bảng orders đã chạy
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_coupon_usage');
    }
};
