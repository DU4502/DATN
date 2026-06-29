<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('roles')) {
            DB::table('roles')->updateOrInsert(
                ['id' => 3],
                ['name' => 'super_admin', 'description' => 'Quản trị toàn hệ thống'],
            );
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('is_active');
            }

            if (! Schema::hasColumn('users', 'last_login_ip')) {
                $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            }
        });

        if (! Schema::hasTable('system_logs')) {
            Schema::create('system_logs', function (Blueprint $table) {
                $table->id();
                $table->integer('user_id')->nullable()->index();
                $table->string('actor_name', 150)->nullable();
                $table->string('actor_email', 150)->nullable()->index();
                $table->string('action', 255);
                $table->string('category', 50)->default('system')->index();
                $table->string('status', 30)->default('success')->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });

            DB::table('system_logs')->insert([
                'actor_name' => 'Hệ thống',
                'action' => 'Khởi tạo nhật ký hệ thống Super Admin',
                'category' => 'system',
                'status' => 'success',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('system_logs');

        Schema::table('users', function (Blueprint $table) {
            $columns = collect(['last_login_at', 'last_login_ip'])
                ->filter(fn (string $column) => Schema::hasColumn('users', $column))
                ->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
