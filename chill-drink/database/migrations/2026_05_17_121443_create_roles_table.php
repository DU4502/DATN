<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->string('name', 50)->unique()->comment('user, admin');
            $table->string('description', 255)->nullable();
        });

        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'user', 'description' => 'Khach hang'],
            ['id' => 2, 'name' => 'admin', 'description' => 'Quan tri'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
