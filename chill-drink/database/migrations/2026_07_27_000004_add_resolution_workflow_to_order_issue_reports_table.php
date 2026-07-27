<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_issue_reports', function (Blueprint $table) {
            $table->string('resolution_type', 30)->nullable()->after('status');
            $table->string('resolution_value', 255)->nullable()->after('resolution_type');
            $table->dateTime('estimated_at')->nullable()->after('resolution_value');
            $table->dateTime('approved_at')->nullable()->after('rejected_at');
            $table->dateTime('remedy_started_at')->nullable()->after('approved_at');
            $table->dateTime('customer_confirmed_at')->nullable()->after('remedy_started_at');
        });
    }

    public function down(): void
    {
        Schema::table('order_issue_reports', function (Blueprint $table) {
            $table->dropColumn(['resolution_type', 'resolution_value', 'estimated_at', 'approved_at', 'remedy_started_at', 'customer_confirmed_at']);
        });
    }
};
