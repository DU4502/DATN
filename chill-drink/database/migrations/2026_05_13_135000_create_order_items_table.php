<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Legacy migration kept for history only.
        // `order_items` is created by 2026_05_17_121748_create_order_items_table.
        return;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Keep legacy migration fully no-op to avoid dropping the active `order_items` table.
        return;
    }
};
