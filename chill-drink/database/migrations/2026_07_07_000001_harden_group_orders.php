<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('group_orders', function (Blueprint $table) {
            $table->timestamp('locked_at')->nullable()->after('closes_at');
            $table->timestamp('cancelled_at')->nullable()->after('locked_at');
        });
        Schema::table('group_order_members', function (Blueprint $table) {
            $table->unique(['group_order_id', 'user_id'], 'group_members_group_user_unique');
        });
    }

    public function down(): void
    {
        Schema::table('group_order_members', fn (Blueprint $table) => $table->dropUnique('group_members_group_user_unique'));
        Schema::table('group_orders', fn (Blueprint $table) => $table->dropColumn(['locked_at', 'cancelled_at']));
    }
};
