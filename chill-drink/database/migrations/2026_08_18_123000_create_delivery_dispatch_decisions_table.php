<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('delivery_dispatch_decisions')) {
            return;
        }

        Schema::create('delivery_dispatch_decisions', function (Blueprint $table) {
            $table->id();
            $table->string('batch_uuid', 36)->index();
            $table->unsignedBigInteger('order_id')->index();
            $table->unsignedBigInteger('shipper_id')->nullable()->index();
            $table->string('mode', 24)->index();
            $table->unsignedInteger('rank')->nullable();
            $table->decimal('score', 10, 3)->nullable();
            $table->boolean('selected')->default(false)->index();
            $table->json('features_json')->nullable();
            $table->string('reason', 500)->nullable();
            $table->timestamps();

            $table->index(['order_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_dispatch_decisions');
    }
};
