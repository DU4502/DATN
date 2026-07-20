<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'order_code')) {
                $table->string('order_code', 30)
                    ->nullable()
                    ->unique()
                    ->after('id')
                    ->comment('Mã đơn hàng: [MãChiNhánh]-[LoạiĐơn]-[YYYYMMDD]-[STT]');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'order_code')) {
                $table->dropUnique(['order_code']);
                $table->dropColumn('order_code');
            }
        });
    }
};
