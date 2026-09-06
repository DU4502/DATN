<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders')
            || ! Schema::hasColumn('orders', 'status')
            || DB::getDriverName() !== 'mysql') {
            return;
        }

        $statuses = [
            'awaiting_payment',
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
        ];

        $unsupportedStatuses = DB::table('orders')
            ->whereNull('status')
            ->orWhere('status', '')
            ->orWhereNotIn('status', $statuses)
            ->distinct()
            ->pluck('status');

        if ($unsupportedStatuses->isNotEmpty()) {
            throw new RuntimeException(
                'Cannot expand orders.status because unsupported values exist: '
                .$unsupportedStatuses->map(fn ($status) => var_export($status, true))->implode(', ')
            );
        }

        $enum = implode("','", $statuses);

        DB::statement(
            "ALTER TABLE orders MODIFY status ENUM('{$enum}') NOT NULL DEFAULT 'pending'"
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders')
            || ! Schema::hasColumn('orders', 'status')
            || DB::getDriverName() !== 'mysql') {
            return;
        }

        // Không để rollback làm mất các đơn VNPay đang chờ thanh toán.
        DB::table('orders')
            ->where('status', 'awaiting_payment')
            ->update(['status' => 'pending']);

        $statuses = [
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
        ];
        $enum = implode("','", $statuses);

        DB::statement(
            "ALTER TABLE orders MODIFY status ENUM('{$enum}') NOT NULL DEFAULT 'pending'"
        );
    }
};
