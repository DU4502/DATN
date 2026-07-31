<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Xóa unique constraint trên users.branch_id.
 *
 * MySQL không cho drop unique index khi có FK dùng nó.
 * Phải: drop FK → drop unique → tái tạo FK (không unique).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 1. Drop FK trỏ tới branches
            try {
                $table->dropForeign(['branch_id']);
            } catch (\Throwable) {}

            // 2. Drop unique index
            try {
                $table->dropUnique('users_branch_id_unique');
            } catch (\Throwable) {}

            // 3. Tái tạo FK (không unique)
            try {
                $table->foreign('branch_id')
                    ->references('id')
                    ->on('branches')
                    ->nullOnDelete();
            } catch (\Throwable) {}
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            try { $table->dropForeign(['branch_id']); } catch (\Throwable) {}
            try { $table->dropIndex('users_branch_id_index'); } catch (\Throwable) {}

            // Khôi phục unique + FK
            $table->unique('branch_id', 'users_branch_id_unique');
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
        });
    }
};
