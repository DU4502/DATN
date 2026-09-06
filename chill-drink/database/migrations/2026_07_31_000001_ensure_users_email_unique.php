<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if ($this->hasUniqueEmailIndex()) {
            return;
        }

        $duplicates = DB::table('users')
            ->selectRaw('email, COUNT(*) as records_count')
            ->whereNotNull('email')
            ->groupBy('email')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isNotEmpty()) {
            $groupsCount = $duplicates->count();
            $recordsCount = (int) $duplicates->sum('records_count');

            throw new \RuntimeException(
                "Cannot add unique users.email index: {$groupsCount} duplicate email group(s) "
                ."containing {$recordsCount} record(s) exist. Resolve duplicates manually before running this migration."
            );
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unique('email', 'users_email_unique');
        });
    }

    public function down(): void
    {
        // No-op: this migration cannot know whether the unique index existed
        // before it ran, so rollback must not weaken an existing schema.
    }

    private function hasUniqueEmailIndex(): bool
    {
        return collect(Schema::getIndexes('users'))->contains(
            static function (array $index): bool {
                $columns = array_values($index['columns'] ?? []);

                return (bool) ($index['unique'] ?? false)
                    && count($columns) === 1
                    && $columns[0] === 'email';
            }
        );
    }
};
