<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sizes', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->string('name', 50);
            $table->decimal('multiplier', 5, 2)->default(1.00);
            $table->datetime('created_at')->nullable()->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sizes');
    }
};
