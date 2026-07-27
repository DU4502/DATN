<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_issue_reports', function (Blueprint $table) {
            $table->id();
            // Các bảng cũ dùng INT, không phải BIGINT mặc định của foreignId().
            $table->integer('order_id');
            $table->integer('user_id');
            $table->string('type', 40);
            $table->text('description');
            $table->string('status', 30)->default('open');
            $table->text('admin_note')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'status']);
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_issue_reports');
    }
};
