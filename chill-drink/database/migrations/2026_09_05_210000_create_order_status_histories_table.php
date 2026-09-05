<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('order_status_histories')) {
            Schema::create('order_status_histories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_id');
                $table->string('from_status', 50)->nullable();
                $table->string('to_status', 50);
                $table->unsignedBigInteger('actor_id')->nullable();
                $table->text('note')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['order_id', 'created_at']);
                $table->index('actor_id');
            });
        }

        // Backfill data cho các đơn hàng hiện có
        if (Schema::hasTable('orders') && DB::table('order_status_histories')->count() === 0) {
            $orders = DB::table('orders')->select([
                'id', 'status', 'user_id', 'status_changed_by', 'status_changed_at', 'created_at', 'cancellation_reason'
            ])->get();

            $now = now();
            $records = [];
            foreach ($orders as $order) {
                // Mốc 1: Khởi tạo đơn (pending)
                $records[] = [
                    'order_id' => $order->id,
                    'from_status' => null,
                    'to_status' => 'pending',
                    'actor_id' => $order->user_id,
                    'note' => 'Đặt đơn hàng thành công',
                    'metadata' => null,
                    'created_at' => $order->created_at ?? $now,
                    'updated_at' => $order->created_at ?? $now,
                ];

                // Mốc 2: Nếu đơn đã chuyển sang trạng thái khác
                if ($order->status && $order->status !== 'pending') {
                    $records[] = [
                        'order_id' => $order->id,
                        'from_status' => 'pending',
                        'to_status' => $order->status,
                        'actor_id' => $order->status_changed_by ?? $order->user_id,
                        'note' => $order->status === 'cancelled' ? ($order->cancellation_reason ?: 'Đã hủy đơn hàng') : 'Cập nhật trạng thái đơn hàng',
                        'metadata' => null,
                        'created_at' => $order->status_changed_at ?? $now,
                        'updated_at' => $order->status_changed_at ?? $now,
                    ];
                }
            }

            if (!empty($records)) {
                DB::table('order_status_histories')->insert($records);
            }
        }
    }

    public function down(): void
    {
        // Keep table or drop
    }
};
