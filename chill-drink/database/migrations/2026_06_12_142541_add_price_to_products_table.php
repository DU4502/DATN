<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        if (Schema::hasColumn('products', 'price')) {
            return;
        }

        Schema::table('products', function ($table) {
            $table->decimal('price', 10, 2)->default(0)->after('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // The price column already exists in deployed schemas, so rollback must not drop it.
    }
};
