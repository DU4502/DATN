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
        Schema::table('messages', function (Blueprint $table) {
            // Field để lưu ID của Super Admin thực sự gửi tin nhắn (nếu có impersonation)
            if (!Schema::hasColumn('messages', 'impersonated_by_id')) {
                $table->integer('impersonated_by_id')->nullable()->after('sender_id');
            }
            // Field để lưu ID của user mà tin nhắn sẽ hiển thị dưới danh nghĩa (mặc định là sender_id)
            if (!Schema::hasColumn('messages', 'display_as_sender_id')) {
                $table->integer('display_as_sender_id')->nullable()->after('sender_id');
            }

            if (!Schema::hasColumn('messages', 'impersonated_by_id')) {
                $table->foreign('impersonated_by_id')->references('id')->on('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('messages', 'display_as_sender_id')) {
                $table->foreign('display_as_sender_id')->references('id')->on('users')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['impersonated_by_id']);
            $table->dropForeign(['display_as_sender_id']);
            $table->dropColumn(['impersonated_by_id', 'display_as_sender_id']);
        });
    }
};
