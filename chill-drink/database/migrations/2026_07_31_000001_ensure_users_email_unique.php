<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Đảm bảo cột email trong bảng users có UNIQUE index.
 *
 * Migration này idempotent: nếu unique index đã tồn tại thì bỏ qua,
 * không gây lỗi khi chạy lại.
 *
 * Mục đích: Không cho phép một địa chỉ email được dùng cho nhiều tài khoản
 * dù khác vai trò (User, Nhân viên, Admin, Super Admin).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Xóa duplicate emails trước (nếu có) để không vi phạm unique khi thêm index.
        // Giữ lại bản ghi có id nhỏ nhất cho mỗi email, xóa các bản trùng còn lại.
        $duplicates = DB::table('users')
            ->select('email', DB::raw('MIN(id) as keep_id'))
            ->whereNotNull('email')
            ->groupBy('email')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $dup) {
            DB::table('users')
                ->where('email', $dup->email)
                ->where('id', '!=', $dup->keep_id)
                ->delete();
        }

        Schema::table('users', function (Blueprint $table) {
            // Kiểm tra xem unique index đã tồn tại chưa trước khi tạo
            $indexes = collect(
                collect(Schema::getIndexes('users'))
                    ->filter(static fn (array $index): bool => (bool) ($index['unique'] ?? false)
                        && in_array('email', $index['columns'] ?? [], true))
                    ->all()
            );

            if ($indexes->isEmpty()) {
                $table->unique('email', 'users_email_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            try {
                $table->dropUnique('users_email_unique');
            } catch (\Throwable) {
                // Ignore nếu index không tồn tại
            }
        });
    }
};
