<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        // Legacy duplicate. The table is created by
        // 2026_05_25_214500_create_password_reset_tokens_table.php.
    }

    public function down(): void
    {
        // Keep this migration no-op so rollback does not drop the active table.
    }
};
