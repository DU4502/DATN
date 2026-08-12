<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_issue_reports', function (Blueprint $table) {
            $table->integer('handled_by')->nullable()->after('user_id');
            $table->index('handled_by');
        });
    }

    public function down(): void
    {
        Schema::table('order_issue_reports', function (Blueprint $table) {
            $table->dropIndex(['handled_by']);
            $table->dropColumn('handled_by');
        });
    }
};
