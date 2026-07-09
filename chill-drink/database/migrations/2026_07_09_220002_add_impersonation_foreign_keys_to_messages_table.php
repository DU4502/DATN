<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('messages', 'impersonated_by_id')) {
            return;
        }

        Schema::table('messages', function (Blueprint $table) {
            try {
                $table->foreign('impersonated_by_id')->references('id')->on('users')->onDelete('set null');
            } catch (\Throwable) {
                // Foreign key may already exist
            }

            try {
                $table->foreign('display_as_sender_id')->references('id')->on('users')->onDelete('set null');
            } catch (\Throwable) {
                // Foreign key may already exist
            }
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            try {
                $table->dropForeign(['impersonated_by_id']);
            } catch (\Throwable) {
            }

            try {
                $table->dropForeign(['display_as_sender_id']);
            } catch (\Throwable) {
            }
        });
    }
};
