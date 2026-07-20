<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        try {
            DB::statement('ALTER TABLE messages MODIFY COLUMN sender_id INT(11) NULL DEFAULT NULL');
        } catch (\Throwable $e) {}
    }

    public function down(): void
    {
    }
};
