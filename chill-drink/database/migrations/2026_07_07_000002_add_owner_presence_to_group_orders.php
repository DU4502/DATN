<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('group_orders', function (Blueprint $table) {
            $table->timestamp('owner_last_seen_at')->nullable()->after('closes_at');
        });
    }

    public function down(): void
    {
        Schema::table('group_orders', fn (Blueprint $table) => $table->dropColumn('owner_last_seen_at'));
    }
};
