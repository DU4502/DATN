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
        if (Schema::hasColumn('categories', 'status')) {
            return;
        }

        Schema::table('categories', function (Blueprint $table) {
            // Thêm cột status kiểu số nguyên nhỏ, mặc định là 1 (hoạt động)
            $table->tinyInteger('status')->default(1)->after('name');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('categories', 'status')) {
            return;
        }

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
