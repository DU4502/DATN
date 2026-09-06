<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'cod_settlement_pin_hash')) {
                $table->string('cod_settlement_pin_hash')->nullable()->after('password');
            }

            if (! Schema::hasColumn('users', 'cod_settlement_pin_set_at')) {
                $table->timestamp('cod_settlement_pin_set_at')->nullable()->after('cod_settlement_pin_hash');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'cod_settlement_pin_set_at')) {
                $table->dropColumn('cod_settlement_pin_set_at');
            }

            if (Schema::hasColumn('users', 'cod_settlement_pin_hash')) {
                $table->dropColumn('cod_settlement_pin_hash');
            }
        });
    }
};
