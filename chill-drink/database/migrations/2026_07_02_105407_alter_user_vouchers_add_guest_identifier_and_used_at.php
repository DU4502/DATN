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
        Schema::table('user_vouchers', function (Blueprint $table) {
            // Add guest_identifier column if it doesn't exist
            if (!Schema::hasColumn('user_vouchers', 'guest_identifier')) {
                $table->string('guest_identifier')->nullable()->after('user_id');
            }
            
            // Add used_at column if it doesn't exist
            if (!Schema::hasColumn('user_vouchers', 'used_at')) {
                $table->datetime('used_at')->nullable()->after('expires_at');
            }
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
