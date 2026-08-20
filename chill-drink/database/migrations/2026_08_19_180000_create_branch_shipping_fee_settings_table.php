<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('branch_shipping_fee_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->unique()->constrained('branches')->cascadeOnDelete();
            $table->decimal('free_km', 6, 2)->default(5.00);
            $table->unsignedInteger('fast_surcharge')->default(8000);
            $table->json('tiers')->nullable();

            // users.id của project là INT signed, không phải BIGINT UNSIGNED.
            // Không dùng foreignId() ở đây vì sẽ tạo BIGINT UNSIGNED và làm FK lỗi errno 150.
            $table->integer('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('updated_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_shipping_fee_settings');
    }
};
