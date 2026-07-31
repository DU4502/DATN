<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Thêm role nhân viên (role_id = 5)
        DB::table('roles')->insertOrIgnore([
            ['id' => 5, 'name' => 'staff', 'description' => 'Nhân viên'],
        ]);

        // 2. Thêm cột ghi nhận ai thay đổi trạng thái đơn hàng và lúc nào
        if (!Schema::hasColumn('orders', 'status_changed_at')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->timestamp('status_changed_at')->nullable()->after('status');
                $table->integer('status_changed_by')->nullable()->after('status_changed_at')
                    ->comment('user_id của người thay đổi trạng thái');
            });
        }

        // 3. Thêm cột ghi nhận ai thay đổi trạng thái đơn nhóm và lúc nào
        if (!Schema::hasColumn('group_orders', 'status_changed_at')) {
            Schema::table('group_orders', function (Blueprint $table) {
                $table->timestamp('status_changed_at')->nullable()->after('status');
                $table->integer('status_changed_by')->nullable()->after('status_changed_at')
                    ->comment('user_id của người thay đổi trạng thái đơn nhóm');
            });
        }
    }

    public function down(): void
    {
        // Xóa role staff
        DB::table('roles')->where('id', 5)->delete();

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumnIfExists('status_changed_at');
            $table->dropColumnIfExists('status_changed_by');
        });

        Schema::table('group_orders', function (Blueprint $table) {
            $table->dropColumnIfExists('status_changed_at');
            $table->dropColumnIfExists('status_changed_by');
        });
    }
};
