<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_issue_reports', function (Blueprint $table) {
            $table->json('redelivery_items')->nullable()->after('redelivery_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_issue_reports', function (Blueprint $table) {
            $table->dropColumn('redelivery_items');
        });
    }
};
