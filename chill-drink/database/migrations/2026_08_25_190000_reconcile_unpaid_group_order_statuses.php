<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('group_orders') || ! Schema::hasTable('orders')) {
            return;
        }

        $unpaidVnpayOrderIds = DB::table('orders')
            ->where('payment_method', 'vnpay')
            ->where('payment_status', '!=', 'paid')
            ->pluck('id');

        if ($unpaidVnpayOrderIds->isNotEmpty()) {
            DB::table('group_orders')
                ->where('status', 'ordered')
                ->whereIn('order_id', $unpaidVnpayOrderIds)
                ->update([
                    'status' => 'closed',
                    'status_changed_by' => null,
                    'status_changed_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        // Không đưa đơn chưa thanh toán về "đã đặt" khi rollback.
    }
};
