<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('shippers')) {
            return;
        }

        if (DB::table('shippers')->whereNull('user_id')->exists()) {
            throw new \RuntimeException('Cannot enforce shippers.user_id integrity while null values exist.');
        }

        if (DB::table('shippers')->select('user_id')->groupBy('user_id')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new \RuntimeException('Cannot enforce one shipper per user while duplicate user_id values exist.');
        }

        if (DB::table('shippers')
            ->leftJoin('users', 'users.id', '=', 'shippers.user_id')
            ->whereNull('users.id')
            ->exists()) {
            throw new \RuntimeException('Cannot add shippers.user_id foreign key while orphan rows exist.');
        }

        Schema::table('shippers', function (Blueprint $table) {
            $table->string('vehicle_type', 50)->default('bike')->change();
        });

        if (! Schema::hasIndex('shippers', 'shippers_user_id_unique')) {
            Schema::table('shippers', function (Blueprint $table) {
                $table->unique('user_id', 'shippers_user_id_unique');
            });
        }

        if (! $this->hasUserForeignKey()) {
            Schema::table('shippers', function (Blueprint $table) {
                $table->foreign('user_id', 'shippers_user_id_foreign')
                    ->references('id')
                    ->on('users')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('shippers')) {
            return;
        }

        if ($this->hasUserForeignKey()) {
            Schema::table('shippers', function (Blueprint $table) {
                $table->dropForeign('shippers_user_id_foreign');
            });
        }

        if (Schema::hasIndex('shippers', 'shippers_user_id_unique')) {
            Schema::table('shippers', function (Blueprint $table) {
                $table->dropUnique('shippers_user_id_unique');
            });
        }
    }

    private function hasUserForeignKey(): bool
    {
        return collect(Schema::getForeignKeys('shippers'))
            ->contains(fn (array $foreignKey): bool => $foreignKey['name'] === 'shippers_user_id_foreign');
    }
};
