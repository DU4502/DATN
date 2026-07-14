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

        // SQLite dùng TEXT và không cần thay đổi ENUM.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY status VARCHAR(50) NOT NULL DEFAULT 'pending'");

            DB::statement(
                "ALTER TABLE orders MODIFY status ENUM(
                'awaiting_email_confirmation',
                'pending',
                'in_progress',
                'shipper_accepted',
                'arrived',
                'completed',
                'cancelled'
                ) NOT NULL DEFAULT 'pending'"
            );
        }

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'confirmation_token')) {
                $table->string('confirmation_token', 64)->nullable()->unique()->after('guest_token');
            }

            if (! Schema::hasColumn('orders', 'confirmation_token_expires_at')) {
                $table->timestamp('confirmation_token_expires_at')->nullable()->after('confirmation_token');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'confirmation_token_expires_at')) {
                $table->dropColumn('confirmation_token_expires_at');
            }

            if (Schema::hasColumn('orders', 'confirmation_token')) {
                $table->dropColumn('confirmation_token');
            }
        });

        // Chuyển các đơn awaiting về pending trước khi xoá enum value
        DB::table('orders')
            ->where('status', 'awaiting_email_confirmation')
            ->update(['status' => 'pending']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY status VARCHAR(50) NOT NULL DEFAULT 'pending'");

            DB::statement(
                "ALTER TABLE orders MODIFY status ENUM(
                'pending',
                'in_progress',
                'shipper_accepted',
                'arrived',
                'completed',
                'cancelled'
                ) NOT NULL DEFAULT 'pending'"
            );
        }
    }
};
