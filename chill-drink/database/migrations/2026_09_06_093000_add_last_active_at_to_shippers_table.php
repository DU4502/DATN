<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('shippers') || Schema::hasColumn('shippers', 'last_active_at')) {
            return;
        }

        Schema::table('shippers', function (Blueprint $table) {
            $table->timestamp('last_active_at')->nullable()->after('status')->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('shippers') || ! Schema::hasColumn('shippers', 'last_active_at')) {
            return;
        }

        Schema::table('shippers', function (Blueprint $table) {
            $table->dropColumn('last_active_at');
        });
    }
};
