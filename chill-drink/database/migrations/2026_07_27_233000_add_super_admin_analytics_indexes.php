<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            if (! Schema::hasIndex('orders', 'idx_orders_created_at')) {
                $table->index('created_at', 'idx_orders_created_at');
            }

            if (! Schema::hasIndex('orders', 'idx_orders_branch_created_at')) {
                $table->index(['branch_id', 'created_at'], 'idx_orders_branch_created_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            if (! Schema::hasIndex('orders', 'orders_branch_id_foreign')) {
                $table->index('branch_id', 'orders_branch_id_foreign');
            }

            if (Schema::hasIndex('orders', 'idx_orders_branch_created_at')) {
                $table->dropIndex('idx_orders_branch_created_at');
            }

            if (Schema::hasIndex('orders', 'idx_orders_created_at')) {
                $table->dropIndex('idx_orders_created_at');
            }
        });
    }
};
