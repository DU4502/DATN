<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $relations = [
            ['delivery_bundle_trips', 'shipper_id', 'shippers', false],
            ['delivery_bundle_trip_orders', 'trip_id', 'delivery_bundle_trips', false],
            ['delivery_bundle_trip_orders', 'order_id', 'orders', false],
            ['delivery_dispatch_decisions', 'order_id', 'orders', false],
            ['delivery_dispatch_decisions', 'shipper_id', 'shippers', true],
            ['delivery_fee_settings', 'updated_by', 'users', true],
            ['order_issue_reports', 'handled_by', 'users', true],
            ['order_issue_reports', 'voucher_coupon_id', 'coupons', true],
            ['delivery_order_messages', 'sender_user_id', 'users', true],
            ['shippers', 'station_branch_id', 'branches', true],
            ['shippers', 'returning_to_branch_id', 'branches', true],
        ];

        foreach ($relations as [$table, $column, $parent, $nullable]) {
            $this->assertRelationReady($table, $column, $parent, $nullable);
        }

        $this->changeColumnToSignedInteger('delivery_bundle_trip_orders', 'order_id');
        $this->changeColumnToSignedInteger('delivery_dispatch_decisions', 'order_id');
        $this->changeColumnToSignedInteger('delivery_fee_settings', 'updated_by', nullable: true);

        $this->addForeignKey('delivery_bundle_trips', 'shipper_id', 'shippers', 'delivery_bundle_trips_shipper_id_foreign', 'cascade');
        $this->addForeignKey('delivery_bundle_trip_orders', 'trip_id', 'delivery_bundle_trips', 'delivery_bundle_trip_orders_trip_id_foreign', 'cascade');
        $this->addForeignKey('delivery_bundle_trip_orders', 'order_id', 'orders', 'delivery_bundle_trip_orders_order_id_foreign', 'cascade');
        $this->addForeignKey('delivery_dispatch_decisions', 'order_id', 'orders', 'delivery_dispatch_decisions_order_id_foreign', 'cascade');
        $this->addForeignKey('delivery_dispatch_decisions', 'shipper_id', 'shippers', 'delivery_dispatch_decisions_shipper_id_foreign', 'null');
        $this->addForeignKey('delivery_fee_settings', 'updated_by', 'users', 'delivery_fee_settings_updated_by_foreign', 'null');
        $this->addForeignKey('order_issue_reports', 'handled_by', 'users', 'order_issue_reports_handled_by_foreign', 'null');

        if (Schema::hasTable('order_issue_reports')
            && Schema::hasColumn('order_issue_reports', 'voucher_coupon_id')
            && ! Schema::hasIndex('order_issue_reports', 'order_issue_reports_voucher_coupon_id_index')) {
            Schema::table('order_issue_reports', function (Blueprint $table) {
                $table->index('voucher_coupon_id', 'order_issue_reports_voucher_coupon_id_index');
            });
        }

        $this->addForeignKey('order_issue_reports', 'voucher_coupon_id', 'coupons', 'order_issue_reports_voucher_coupon_id_foreign', 'null');
        $this->replaceDeliveryMessageSenderForeignKey();
        $this->addForeignKey('shippers', 'station_branch_id', 'branches', 'shippers_station_branch_id_foreign', 'null');
        $this->addForeignKey('shippers', 'returning_to_branch_id', 'branches', 'shippers_returning_to_branch_id_foreign', 'null');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $this->dropForeignKey('shippers', 'shippers_returning_to_branch_id_foreign');
        $this->dropForeignKey('shippers', 'shippers_station_branch_id_foreign');

        if ($this->foreignKeyName('delivery_order_messages', 'sender_user_id') !== null) {
            $this->dropForeignKey('delivery_order_messages', $this->foreignKeyName('delivery_order_messages', 'sender_user_id'));
            Schema::table('delivery_order_messages', function (Blueprint $table) {
                $table->foreign('sender_user_id', 'delivery_order_messages_sender_user_id_foreign')
                    ->references('id')
                    ->on('users')
                    ->cascadeOnDelete();
            });
        }

        $this->dropForeignKey('order_issue_reports', 'order_issue_reports_voucher_coupon_id_foreign');
        $this->dropForeignKey('order_issue_reports', 'order_issue_reports_handled_by_foreign');
        $this->dropForeignKey('delivery_fee_settings', 'delivery_fee_settings_updated_by_foreign');
        $this->dropForeignKey('delivery_dispatch_decisions', 'delivery_dispatch_decisions_shipper_id_foreign');
        $this->dropForeignKey('delivery_dispatch_decisions', 'delivery_dispatch_decisions_order_id_foreign');
        $this->dropForeignKey('delivery_bundle_trip_orders', 'delivery_bundle_trip_orders_order_id_foreign');
        $this->dropForeignKey('delivery_bundle_trip_orders', 'delivery_bundle_trip_orders_trip_id_foreign');
        $this->dropForeignKey('delivery_bundle_trips', 'delivery_bundle_trips_shipper_id_foreign');

        $this->changeColumnToUnsignedBigInteger('delivery_dispatch_decisions', 'order_id');
        $this->changeColumnToUnsignedBigInteger('delivery_bundle_trip_orders', 'order_id');
    }

    private function assertRelationReady(string $table, string $column, string $parent, bool $nullable): void
    {
        if (! Schema::hasTable($table)
            || ! Schema::hasColumn($table, $column)
            || ! Schema::hasTable($parent)
            || ! Schema::hasColumn($parent, 'id')) {
            return;
        }

        $query = DB::table("{$table} as child")
            ->leftJoin("{$parent} as parent", "parent.id", '=', "child.{$column}")
            ->whereNull('parent.id');

        if ($nullable) {
            $query->whereNotNull("child.{$column}");
        }

        if ($query->exists()) {
            $sample = $query->limit(10)->pluck("child.{$column}")->implode(', ');

            throw new RuntimeException("Cannot add {$table}.{$column} foreign key; orphan values exist: {$sample}");
        }
    }

    private function changeColumnToSignedInteger(string $table, string $column, bool $nullable = false): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        if ($this->columnType($table, $column) !== 'int(11)') {
            DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` INT ".($nullable ? 'NULL' : 'NOT NULL'));
        }
    }

    private function changeColumnToUnsignedBigInteger(string $table, string $column): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` BIGINT UNSIGNED NOT NULL");
    }

    private function columnType(string $table, string $column): ?string
    {
        return DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->value('COLUMN_TYPE');
    }

    private function addForeignKey(
        string $table,
        string $column,
        string $parent,
        string $name,
        string $onDelete
    ): void {
        if (! Schema::hasTable($table)
            || ! Schema::hasColumn($table, $column)
            || ! Schema::hasTable($parent)
            || $this->foreignKeyName($table, $column) !== null) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column, $parent, $name, $onDelete) {
            $foreign = $blueprint->foreign($column, $name)
                ->references('id')
                ->on($parent);

            if ($onDelete === 'cascade') {
                $foreign->cascadeOnDelete();
            } elseif ($onDelete === 'null') {
                $foreign->nullOnDelete();
            }
        });
    }

    private function replaceDeliveryMessageSenderForeignKey(): void
    {
        if (! Schema::hasTable('delivery_order_messages')
            || ! Schema::hasColumn('delivery_order_messages', 'sender_user_id')) {
            return;
        }

        $foreignKey = $this->foreignKeyName('delivery_order_messages', 'sender_user_id');
        $deleteRule = $foreignKey ? $this->deleteRule('delivery_order_messages', $foreignKey) : null;

        if ($deleteRule === 'SET NULL') {
            return;
        }

        if ($foreignKey !== null) {
            $this->dropForeignKey('delivery_order_messages', $foreignKey);
        }

        Schema::table('delivery_order_messages', function (Blueprint $table) {
            $table->foreign('sender_user_id', 'delivery_order_messages_sender_user_id_foreign')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    private function foreignKeyName(string $table, string $column): ?string
    {
        return DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->value('CONSTRAINT_NAME');
    }

    private function deleteRule(string $table, string $foreignKey): ?string
    {
        return DB::table('information_schema.REFERENTIAL_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $foreignKey)
            ->value('DELETE_RULE');
    }

    private function dropForeignKey(string $table, ?string $name): void
    {
        if ($name === null || ! Schema::hasTable($table)) {
            return;
        }

        $exists = DB::table('information_schema.REFERENTIAL_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $name)
            ->exists();

        if ($exists) {
            Schema::table($table, function (Blueprint $blueprint) use ($name) {
                $blueprint->dropForeign($name);
            });
        }
    }
};
