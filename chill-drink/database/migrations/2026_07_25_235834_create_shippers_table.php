<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shippers', function (Blueprint $table) {
            $table->id();

            $table->integer('user_id');

            $table->string('code', 20)->unique();

            $table->string('phone', 20);

            $table->enum('vehicle_type', ['bike', 'car'])
                ->default('bike');

            $table->string('license_plate')->nullable();

            $table->string('avatar')->nullable();

            $table->enum('status', ['offline', 'online', 'busy'])
                ->default('offline');

            $table->decimal('current_latitude', 10, 8)->nullable();
            $table->decimal('current_longitude', 11, 8)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shippers');
    }
};
