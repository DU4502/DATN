<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Legacy no-op: categories.status is created in the base categories table.
    }

    public function down(): void
    {
        //
    }
};
