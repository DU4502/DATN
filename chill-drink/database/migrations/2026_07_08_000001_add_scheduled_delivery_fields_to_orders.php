<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('fulfillment_type', 20)->default('delivery')->after('delivery_type');
            $table->dateTime('scheduled_delivery_time')->nullable()->after('scheduled_at')->index();
            $table->text('delivery_note')->nullable()->after('scheduled_delivery_time');
        });

        DB::table('orders')->whereIn('delivery_type', ['delivery', 'pickup'])
            ->update(['fulfillment_type' => DB::raw('delivery_type')]);
        DB::table('orders')->whereNotNull('scheduled_at')->update([
            'delivery_type' => 'scheduled',
            'scheduled_delivery_time' => DB::raw('scheduled_at'),
        ]);
        DB::table('orders')->whereNull('scheduled_at')->update(['delivery_type' => 'now']);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['scheduled_delivery_time']);
            $table->dropColumn(['fulfillment_type', 'scheduled_delivery_time', 'delivery_note']);
        });
    }
};
