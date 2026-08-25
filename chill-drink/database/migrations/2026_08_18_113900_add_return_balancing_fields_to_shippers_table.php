<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('shippers')) {
            return;
        }

        Schema::table('shippers', function (Blueprint $table) {
            if (! Schema::hasColumn('shippers', 'station_branch_id')) {
                $table->unsignedBigInteger('station_branch_id')->nullable()->after('status')->index();
            }
            if (! Schema::hasColumn('shippers', 'returning_to_branch_id')) {
                $table->unsignedBigInteger('returning_to_branch_id')->nullable()->after('station_branch_id')->index();
            }
            if (! Schema::hasColumn('shippers', 'returning_started_at')) {
                $table->timestamp('returning_started_at')->nullable()->after('returning_to_branch_id');
            }
            if (! Schema::hasColumn('shippers', 'last_station_arrived_at')) {
                $table->timestamp('last_station_arrived_at')->nullable()->after('returning_started_at');
            }
        });

        // Không bắt buộc FK để migration vẫn chạy được trên các DB test nhẹ.
        // Logic ứng dụng luôn kiểm tra branch tồn tại trước khi sử dụng.
    }

    public function down(): void
    {
        if (! Schema::hasTable('shippers')) {
            return;
        }

        Schema::table('shippers', function (Blueprint $table) {
            foreach (['last_station_arrived_at', 'returning_started_at', 'returning_to_branch_id', 'station_branch_id'] as $column) {
                if (Schema::hasColumn('shippers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
