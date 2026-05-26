<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();

            // Tạo cột role_id kiểu INT thông thường để khớp với bảng roles cũ
            $table->integer('role_id')->default(1);

            $table->string('name', 150);
            $table->string('email', 150)->unique()->nullable();
            $table->string('avatar', 255)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('password', 255);
            $table->tinyInteger('is_active')->default(1);
            $table->rememberToken();
            $table->timestamps();

            // Viết lệnh liên kết khóa ngoại thủ công ở dòng này:
            $table->foreign('role_id')->references('id')->on('roles')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
