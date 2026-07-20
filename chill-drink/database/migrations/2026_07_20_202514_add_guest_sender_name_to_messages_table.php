<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // Đánh dấu tin nhắn là từ guest (không có user account)
            $table->string('guest_sender_name')->nullable()->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            if (Schema::hasColumn('messages', 'guest_sender_name')) {
                $table->dropColumn('guest_sender_name');
            }
        });
    }
};
