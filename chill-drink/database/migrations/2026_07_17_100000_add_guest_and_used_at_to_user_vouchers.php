<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop foreign key constraint first (if exists)
        try {
            Schema::table('user_vouchers', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        } catch (\Exception $e) {
            // Foreign key might not exist, continue
        }

        // Add guest_identifier if not exists
        if (!Schema::hasColumn('user_vouchers', 'guest_identifier')) {
            Schema::table('user_vouchers', function (Blueprint $table) {
                $table->string('guest_identifier', 100)->nullable()->after('user_id')->comment('ID phiên cho khách không đăng nhập');
                $table->index('guest_identifier'); // Add index for performance
            });
        }

        // Add used_at if not exists
        if (!Schema::hasColumn('user_vouchers', 'used_at')) {
            Schema::table('user_vouchers', function (Blueprint $table) {
                $table->datetime('used_at')->nullable()->after('is_used')->comment('Thời điểm sử dụng voucher');
            });
        }

        // Make user_id nullable for guests
        Schema::table('user_vouchers', function (Blueprint $table) {
            $table->integer('user_id')->nullable()->change();
        });

        // Re-add foreign key with nullable support
        Schema::table('user_vouchers', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_vouchers', function (Blueprint $table) {
            if (Schema::hasColumn('user_vouchers', 'guest_identifier')) {
                $table->dropColumn('guest_identifier');
            }
            
            if (Schema::hasColumn('user_vouchers', 'used_at')) {
                $table->dropColumn('used_at');
            }
        });
    }
};
