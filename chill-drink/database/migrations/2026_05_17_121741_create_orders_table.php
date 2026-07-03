<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->integer('user_id')->index();
            $table->integer('address_id')->nullable();
            $table->integer('coupon_id')->nullable();
            $table->integer('shipping_zone_id')->nullable();
            $table->integer('subtotal');
            $table->integer('shipping_fee')->default(0);
            $table->integer('discount')->default(0);
            $table->integer('total');
            $table->enum('payment_method', ['cod', 'bank_transfer', 'vnpay', 'momo', 'card', 'wallet'])->default('cod');
            $table->enum('status', ['pending', 'processing', 'preparing', 'shipped', 'delivering', 'completed', 'cancelled'])->default('pending')->index();
            $table->enum('payment_status', ['pending', 'paid', 'failed'])->default('pending');
            $table->string('vnpay_transaction_id', 50)->nullable();
            $table->string('note', 500)->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('address_id')->references('id')->on('addresses')->onDelete('set null');
            $table->foreign('coupon_id')->references('id')->on('coupons')->onDelete('set null');
            $table->foreign('shipping_zone_id')->references('id')->on('shipping_zones')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
