<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shipping_zones', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->string('name', 150);
            $table->string('province', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->integer('base_fee')->default(15000);
            $table->integer('per_km_fee')->default(0);
            $table->datetime('created_at')->nullable()->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_zones');
    }
};
