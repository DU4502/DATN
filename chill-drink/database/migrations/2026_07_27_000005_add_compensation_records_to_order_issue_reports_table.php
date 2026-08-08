<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_issue_reports', function (Blueprint $table) {
            $table->integer('voucher_coupon_id')->nullable()->after('resolution_value');
            $table->dateTime('refund_requested_at')->nullable()->after('customer_confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('order_issue_reports', function (Blueprint $table) {
            $table->dropColumn(['voucher_coupon_id', 'refund_requested_at']);
        });
    }
};
