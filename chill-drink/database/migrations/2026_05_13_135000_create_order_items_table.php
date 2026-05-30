<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Legacy duplicate. The canonical order_items schema is created by
        // 2026_05_17_121748_create_order_items_table.php after products/orders exist.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
