<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {

            $table->foreignId('shipper_id')
                ->nullable()
                ->after('user_id')
                ->constrained('shippers')
                ->nullOnDelete();

            $table->enum('delivery_status', [
                'waiting',
                'accepted',
                'picked_up',
                'delivering',
                'completed',
                'failed',
                'cancelled'
            ])->default('waiting');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {

            $table->dropForeign(['shipper_id']);

            $table->dropColumn([
                'shipper_id',
                'delivery_status'
            ]);
        });
    }
};