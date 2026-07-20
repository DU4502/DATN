<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        try {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE conversations MODIFY COLUMN user_id INT(11) NULL DEFAULT NULL');
        } catch (\Throwable $e) {}

        Schema::table('conversations', function (Blueprint $table) {
            if (!Schema::hasColumn('conversations', 'guest_name')) {
                $table->string('guest_name')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('conversations', 'guest_email')) {
                $table->string('guest_email')->nullable()->after('guest_name');
            }
            if (!Schema::hasColumn('conversations', 'guest_token')) {
                $table->string('guest_token', 100)->nullable()->unique()->after('guest_email');
            }
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('conversations', 'guest_token')) {
                $table->dropUnique(['guest_token']);
                $columns[] = 'guest_token';
            }
            if (Schema::hasColumn('conversations', 'guest_email')) {
                $columns[] = 'guest_email';
            }
            if (Schema::hasColumn('conversations', 'guest_name')) {
                $columns[] = 'guest_name';
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
