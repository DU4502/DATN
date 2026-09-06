<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            Schema::table('orders', function (Blueprint $table) {
                if (! Schema::hasColumn('orders', 'user_id')) {
                    return;
                }

                $foreignKeys = collect(DB::select(
                    "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
                     WHERE TABLE_SCHEMA = DATABASE()
                     AND TABLE_NAME = 'orders'
                     AND COLUMN_NAME = 'user_id'
                     AND REFERENCED_TABLE_NAME IS NOT NULL"
                ))->pluck('CONSTRAINT_NAME');

                foreach ($foreignKeys as $foreignKey) {
                    DB::statement("ALTER TABLE orders DROP FOREIGN KEY `{$foreignKey}`");
                }
            });
        }

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'user_id')) {
                $table->integer('user_id')->nullable()->change();
            }

            if (! Schema::hasColumn('orders', 'guest_name')) {
                $table->string('guest_name', 255)->nullable()->after('user_id');
            }

            if (! Schema::hasColumn('orders', 'guest_phone')) {
                $table->string('guest_phone', 30)->nullable()->after('guest_name');
            }

            if (! Schema::hasColumn('orders', 'guest_email')) {
                $table->string('guest_email', 255)->nullable()->after('guest_phone');
            }

            if (! Schema::hasColumn('orders', 'guest_token')) {
                $table->string('guest_token', 64)->nullable()->unique()->after('guest_email');
            }

            if (! Schema::hasColumn('orders', 'delivery_type')) {
                $table->string('delivery_type', 20)->default('delivery')->after('guest_token');
            }
        });

        if (DB::getDriverName() === 'mysql') {
            Schema::table('orders', function (Blueprint $table) {
                if (Schema::hasColumn('orders', 'user_id')) {
                    $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            Schema::table('orders', function (Blueprint $table) {
                $foreignKeys = collect(DB::select(
                "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE()
                 AND TABLE_NAME = 'orders'
                 AND COLUMN_NAME = 'user_id'
                 AND REFERENCED_TABLE_NAME IS NOT NULL"
            ))->pluck('CONSTRAINT_NAME');

                foreach ($foreignKeys as $foreignKey) {
                    DB::statement("ALTER TABLE orders DROP FOREIGN KEY `{$foreignKey}`");
                }
            });
        }

        Schema::table('orders', function (Blueprint $table) {
            foreach (['delivery_type', 'guest_token', 'guest_email', 'guest_phone', 'guest_name'] as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }

            if (Schema::hasColumn('orders', 'user_id')) {
                $table->integer('user_id')->nullable(false)->change();
                if (DB::getDriverName() === 'mysql') {
                    $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                }
            }
        });
    }
};
