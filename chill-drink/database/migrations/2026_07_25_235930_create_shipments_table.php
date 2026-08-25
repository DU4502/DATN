<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();

            $table->integer('order_id');

            $table->foreignId('shipper_id')
                ->constrained('shippers')
                ->cascadeOnDelete();

            $table->string('status', 30)
                ->default('assigned');

            $table->timestamp('assigned_at')
                ->nullable();

            $table->timestamp('picked_up_at')
                ->nullable();

            $table->timestamp('delivered_at')
                ->nullable();

            $table->text('note')
                ->nullable();

            $table->timestamps();

            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
