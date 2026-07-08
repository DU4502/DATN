<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loyalty_points', function (Blueprint $table) {
            if (! Schema::hasColumn('loyalty_points', 'created_at')) {
                $table->timestamp('created_at')->nullable()->useCurrent();
            }

            if (! Schema::hasColumn('loyalty_points', 'updated_at')) {
                $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
            }
        });
    }

    public function down(): void
    {
        Schema::table('loyalty_points', function (Blueprint $table) {
            if (Schema::hasColumn('loyalty_points', 'created_at')) {
                $table->dropColumn('created_at');
            }

            if (Schema::hasColumn('loyalty_points', 'updated_at')) {
                $table->dropColumn('updated_at');
            }
        });
    }
};
