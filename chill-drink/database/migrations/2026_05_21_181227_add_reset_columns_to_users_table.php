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
        if (! Schema::hasColumn('users', 'reset_token')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('reset_token')->nullable();
            });
        }

        if (! Schema::hasColumn('users', 'reset_expire')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dateTime('reset_expire')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $drops = [];

            if (Schema::hasColumn('users', 'reset_token')) {
                $drops[] = 'reset_token';
            }

            if (Schema::hasColumn('users', 'reset_expire')) {
                $drops[] = 'reset_expire';
            }

            if (! empty($drops)) {
                $table->dropColumn($drops);
            }
        });
    }
};
