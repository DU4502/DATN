<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        DB::table('roles')->updateOrInsert(
            ['id' => 4],
            ['name' => 'cskh', 'description' => 'Nhân viên chăm sóc khách hàng']
        );
    }

    public function down(): void
    {
        // Không xóa role khi rollback để tránh làm hỏng tài khoản CSKH hiện có.
    }
};
