<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_order_messages', function (Blueprint $table) {
            $table->id();
            $table->integer('order_id');
            $table->integer('sender_user_id');
            $table->string('sender_type', 20); // customer | shipper
            $table->string('content', 500);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'created_at']);
            $table->index(['order_id', 'sender_type', 'read_at']);
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('sender_user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_order_messages');
    }
};
