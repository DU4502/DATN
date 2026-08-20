<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migration tương thích cho các DB cũ.
     * shipper_id thuộc bảng shippers, không phải users.
     */
    public function up(): void
    {
        if (Schema::hasColumn('orders', 'shipper_id')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('shipper_id')
                ->nullable()
                ->after('user_id')
                ->constrained('shippers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('orders', 'shipper_id')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shipper_id');
        });
    }
};
