<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('landmarks')) {
            return;
        }

        Schema::create('landmarks', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->json('aliases')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('address_text', 500)->nullable();
            $table->string('type', 100)->nullable();
            $table->string('source_type', 30)->nullable();
            $table->string('verification_level', 30)->default('imported');
            $table->unsignedInteger('successful_delivery_count')->default(0);
            $table->string('status', 30)->default('active');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->index(['latitude', 'longitude']);
            $table->index(['status', 'verification_level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landmarks');
    }
};
