<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('shipment_history', 'incident_type')) {
            Schema::table('shipment_history', function (Blueprint $table) {
                $table->string('incident_type', 30)
                    ->default('driver_issue')
                    ->after('status');
            });
        }

        if (! Schema::hasIndex('shipment_history', 'shipment_history_incident_type_index')) {
            Schema::table('shipment_history', function (Blueprint $table) {
                $table->index('incident_type', 'shipment_history_incident_type_index');
            });
        }

        DB::table('shipment_history')
            ->whereNull('incident_type')
            ->update(['incident_type' => 'driver_issue']);

        // Các báo sự cố cũ đều là luồng sự cố tài xế.
        Schema::table('shipment_history', function (Blueprint $table) {
            $table->string('incident_type', 30)
                ->default('driver_issue')
                ->nullable(false)
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('shipment_history', function (Blueprint $table) {
            $table->dropIndex(['incident_type']);
            $table->dropColumn('incident_type');
        });
    }
};
