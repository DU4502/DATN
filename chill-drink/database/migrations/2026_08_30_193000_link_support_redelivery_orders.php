<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->integer('support_issue_id')->nullable()->after('order_code')->index();
        });

        Schema::table('order_issue_reports', function (Blueprint $table) {
            $table->integer('redelivery_order_id')->nullable()->after('voucher_coupon_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('order_issue_reports', function (Blueprint $table) {
            $table->dropIndex(['redelivery_order_id']);
            $table->dropColumn('redelivery_order_id');
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['support_issue_id']);
            $table->dropColumn('support_issue_id');
        });
    }
};
