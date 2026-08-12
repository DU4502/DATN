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
        // Đổi enum cũ thành các giá trị mới (mapping)
        DB::statement("UPDATE orders SET status = 'preparing' WHERE status IN ('processing', 'in_progress')");
        DB::statement("UPDATE orders SET status = 'delivering' WHERE status IN ('shipped', 'delivering', 'shipper_accepted')");
        DB::statement("UPDATE orders SET status = 'delivered' WHERE status = 'arrived'");
        
        // Thay đổi cột status thành VARCHAR để linh hoạt hơn
        Schema::table('orders', function (Blueprint $table) {
            $table->string('status', 30)->default('pending')->change();
        });
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
