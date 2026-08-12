<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_issue_reports', function (Blueprint $table) {
            $table->timestamp('received_at')->nullable()->after('admin_note');
            $table->timestamp('processing_at')->nullable()->after('received_at');
            $table->timestamp('resolved_at')->nullable()->after('processing_at');
            $table->timestamp('rejected_at')->nullable()->after('resolved_at');
        });

        DB::table('order_issue_reports')->whereNull('received_at')->update(['received_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('order_issue_reports', function (Blueprint $table) {
            $table->dropColumn(['received_at', 'processing_at', 'resolved_at', 'rejected_at']);
        });
    }
};
