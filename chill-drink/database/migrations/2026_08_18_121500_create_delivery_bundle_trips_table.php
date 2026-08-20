<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('delivery_bundle_trips')) {
            Schema::create('delivery_bundle_trips', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('shipper_id')->index();
                $table->string('status', 24)->default('active')->index();
                $table->unsignedInteger('total_cups')->default(0);
                $table->unsignedInteger('estimated_distance_m')->nullable();
                $table->unsignedInteger('estimated_duration_s')->nullable();
                $table->integer('saved_distance_m')->default(0);
                $table->json('plan_json');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('delivery_bundle_trip_orders')) {
            Schema::create('delivery_bundle_trip_orders', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('trip_id')->index();
                $table->unsignedBigInteger('order_id')->unique();
                $table->string('role', 16)->default('merged');
                $table->timestamps();

                $table->unique(['trip_id', 'order_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_bundle_trip_orders');
        Schema::dropIfExists('delivery_bundle_trips');
    }
};
