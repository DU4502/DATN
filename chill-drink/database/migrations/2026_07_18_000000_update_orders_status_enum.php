<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Thay đổi enum cũ thành VARCHAR trước, vì các trạng thái mới chưa hợp lệ
        // trong enum hiện tại của MySQL.
        Schema::table('orders', function (Blueprint $table) {
            $table->string('status', 30)->default('pending')->change();
        });

        // Đổi các trạng thái cũ sang bộ trạng thái mới.
        DB::statement("UPDATE orders SET status = 'preparing' WHERE status IN ('processing', 'in_progress')");
        DB::statement("UPDATE orders SET status = 'delivering' WHERE status IN ('shipped', 'delivering', 'shipper_accepted')");
        DB::statement("UPDATE orders SET status = 'delivered' WHERE status = 'arrived'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback về enum cũ
        DB::statement("ALTER TABLE orders MODIFY status ENUM('pending', 'processing', 'preparing', 'shipped', 'delivering', 'completed', 'cancelled') DEFAULT 'pending'");
    }
};
