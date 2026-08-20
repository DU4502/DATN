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

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            "ALTER TABLE orders MODIFY status ENUM(
                'awaiting_email_confirmation',
                'pending',
                'confirmed',
                'preparing',
                'ready_for_delivery',
                'shipper_picked_up',
                'delivering',
                'delivered',
                'ready_for_pickup',
                'completed',
                'cancelled',
                'processing',
                'in_progress',
                'shipping',
                'shipped',
                'shipper_accepted',
                'arrived'
            ) NOT NULL DEFAULT 'pending'"
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders') || ! Schema::hasColumn('orders', 'status')) {
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::table('orders')
            ->whereIn('status', ['awaiting_email_confirmation', 'confirmed', 'preparing', 'ready_for_delivery', 'shipper_picked_up', 'delivering', 'delivered', 'ready_for_pickup'])
            ->update([
                'status' => DB::raw("CASE
                    WHEN status = 'awaiting_email_confirmation' THEN 'pending'
                    WHEN status IN ('confirmed', 'preparing', 'ready_for_delivery', 'ready_for_pickup') THEN 'processing'
                    WHEN status IN ('shipper_picked_up', 'delivering') THEN 'shipped'
                    WHEN status = 'delivered' THEN 'arrived'
                    ELSE status
                END"),
            ]);

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
};
