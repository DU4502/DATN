<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('delivery_order_messages') && Schema::hasColumn('delivery_order_messages', 'sender_user_id')) {
            Schema::table('delivery_order_messages', function (Blueprint $table) {
                // Guest checkout không có users.id; sender_type=customer vẫn xác định đúng phía gửi.
                $table->integer('sender_user_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('delivery_order_messages') && Schema::hasColumn('delivery_order_messages', 'sender_user_id')) {
            // Chỉ phục vụ rollback an toàn: xóa message guest trước khi ép NOT NULL trở lại.
            \DB::table('delivery_order_messages')->whereNull('sender_user_id')->delete();
            Schema::table('delivery_order_messages', function (Blueprint $table) {
                $table->integer('sender_user_id')->nullable(false)->change();
            });
        }
    }
};
