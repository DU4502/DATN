<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('loyalty_points', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->integer('user_id')->unique();
            $table->integer('total_points')->default(0);
            $table->integer('monthly_points')->default(0);
            $table->integer('lifetime_points')->default(0);
            $table->enum('level', ['bronze', 'silver', 'gold', 'diamond'])->default('bronze');
            $table->string('current_month', 7)->nullable();
            $table->datetime('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_points');
    }
};
