<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders') || ! Schema::hasColumn('orders', 'status')) {
            return;
        }

        $usesMySql = DB::getDriverName() === 'mysql';

        if ($usesMySql) {
            DB::statement("ALTER TABLE orders MODIFY status VARCHAR(50) NOT NULL DEFAULT 'pending'");
        }

        DB::table('orders')
            ->whereIn('status', ['processing', 'preparing'])
            ->update(['status' => 'in_progress']);

        DB::table('orders')
            ->whereIn('status', ['shipped', 'delivering', 'shipping'])
            ->update(['status' => 'shipper_accepted']);

        if ($usesMySql) {
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

    public function down(): void
    {
        if (! Schema::hasTable('orders') || ! Schema::hasColumn('orders', 'status')) {
            return;
        }

        $usesMySql = DB::getDriverName() === 'mysql';

        if ($usesMySql) {
            DB::statement("ALTER TABLE orders MODIFY status VARCHAR(50) NOT NULL DEFAULT 'pending'");
        }

        DB::table('orders')
            ->where('status', 'in_progress')
            ->update(['status' => 'processing']);

        DB::table('orders')
            ->where('status', 'shipper_accepted')
            ->update(['status' => 'shipped']);

        DB::table('orders')
            ->where('status', 'arrived')
            ->update(['status' => 'delivering']);

        if ($usesMySql) {
            DB::statement(
                "ALTER TABLE orders MODIFY status ENUM(
                    'pending',
                    'processing',
                    'preparing',
                    'shipped',
                    'delivering',
                    'completed',
                    'cancelled'
                ) NOT NULL DEFAULT 'pending'"
            );
        }
    }
};
