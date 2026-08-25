<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasIndex('users', 'users_branch_id_index')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index('branch_id', 'users_branch_id_index');
            });
        }

        if ($this->hasBranchForeignKey()) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign('users_branch_id_foreign');
            });
        }

        if (Schema::hasIndex('users', 'users_branch_id_unique')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique('users_branch_id_unique');
            });
        }

        if (! $this->hasBranchForeignKey()) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreign('branch_id')
                    ->references('id')
                    ->on('branches')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            try {
                $table->dropForeign(['branch_id']);
            } catch (\Throwable) {
            }

            try {
                $table->dropIndex('users_branch_id_index');
            } catch (\Throwable) {
            }

            $table->unique('branch_id', 'users_branch_id_unique');
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
        });
    }

    private function hasBranchForeignKey(): bool
    {
        return collect(Schema::getForeignKeys('users'))
            ->contains(fn (array $foreignKey): bool => $foreignKey['name'] === 'users_branch_id_foreign');
    }
};
