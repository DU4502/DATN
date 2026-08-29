<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('shipper_cod_receivables');
        Schema::dropIfExists('shipper_cod_settlements');

        if (Schema::hasColumn('users', 'cod_settlement_pin_set_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('cod_settlement_pin_set_at');
            });
        }

        if (Schema::hasColumn('users', 'cod_settlement_pin_hash')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('cod_settlement_pin_hash');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'cod_settlement_pin_hash')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('cod_settlement_pin_hash')->nullable()->after('password');
            });
        }

        if (! Schema::hasColumn('users', 'cod_settlement_pin_set_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('cod_settlement_pin_set_at')->nullable()->after('cod_settlement_pin_hash');
            });
        }

        if (! Schema::hasTable('shipper_cod_settlements')) {
            Schema::create('shipper_cod_settlements', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('shipper_id')->index();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->decimal('amount', 14, 2)->default(0);
                $table->unsignedInteger('order_count')->default(0);
                $table->integer('confirmed_by')->nullable()->index();
                $table->timestamp('confirmed_at')->nullable()->index();
                $table->string('note', 500)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('shipper_cod_receivables')) {
            Schema::create('shipper_cod_receivables', function (Blueprint $table) {
                $table->id();
                $table->integer('order_id')->unique();
                $table->string('order_code', 80)->nullable()->index();
                $table->unsignedBigInteger('shipper_id')->index();
                $table->unsignedBigInteger('order_branch_id')->nullable()->index();
                $table->decimal('amount', 14, 2)->default(0);
                $table->timestamp('collected_at')->nullable()->index();
                $table->unsignedBigInteger('settlement_id')->nullable()->index();
                $table->timestamp('settled_at')->nullable()->index();
                $table->timestamps();

                $table->index(['shipper_id', 'settlement_id'], 'shipper_cod_pending_idx');
            });
        }
    }
};
