<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('delivery_fee_settings')) {
            return;
        }

        Schema::create('delivery_fee_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('free_distance_km', 6, 2)->default(5.00);
            $table->unsignedInteger('fast_surcharge')->default(8000);
            $table->json('cup_tiers');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        DB::table('delivery_fee_settings')->insert([
            'id' => 1,
            'free_distance_km' => 5.00,
            'fast_surcharge' => 8000,
            'cup_tiers' => json_encode([
                ['min_cups' => 1, 'max_cups' => 5, 'price_per_km' => 5000],
                ['min_cups' => 6, 'max_cups' => 10, 'price_per_km' => 6000],
                ['min_cups' => 11, 'max_cups' => 15, 'price_per_km' => 7000],
                ['min_cups' => 16, 'max_cups' => null, 'price_per_km' => 8000],
            ], JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_fee_settings');
    }
};
