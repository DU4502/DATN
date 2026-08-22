<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_vouchers')) {
            return;
        }

        Schema::table('user_vouchers', function (Blueprint $table) {
            if (! Schema::hasIndex('user_vouchers', ['user_id', 'coupon_id'])) {
                $table->index(['user_id', 'coupon_id'], 'user_vouchers_user_coupon_index');
            }

            if (! Schema::hasIndex('user_vouchers', ['guest_identifier', 'coupon_id'])) {
                $table->index(['guest_identifier', 'coupon_id'], 'user_vouchers_guest_coupon_index');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('user_vouchers')) {
            return;
        }

        Schema::table('user_vouchers', function (Blueprint $table) {
            if (Schema::hasIndex('user_vouchers', 'user_vouchers_guest_coupon_index')) {
                $table->dropIndex('user_vouchers_guest_coupon_index');
            }

            if (Schema::hasIndex('user_vouchers', 'user_vouchers_user_coupon_index')) {
                $table->dropIndex('user_vouchers_user_coupon_index');
            }
        });
    }
};
