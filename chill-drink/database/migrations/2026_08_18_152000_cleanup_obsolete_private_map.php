<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('shipment_tracking')) {
            if (Schema::hasColumn('shipment_tracking', 'map_node_id')) {
                // Bản migration cũ tạo FK này. Thử gỡ FK trước; nếu DB từng bị chỉnh tay
                // và FK không còn thì vẫn tiếp tục xóa cột obsolete.
                try {
                    Schema::table('shipment_tracking', function (Blueprint $table) {
                        $table->dropForeign(['map_node_id']);
                    });
                } catch (\Throwable) {
                    // no-op: cleanup vẫn phải tiếp tục
                }

                if (Schema::hasColumn('shipment_tracking', 'map_node_id')) {
                    Schema::table('shipment_tracking', function (Blueprint $table) {
                        $table->dropColumn('map_node_id');
                    });
                }
            }

            $obsoleteColumns = collect(['accuracy_m', 'speed_mps', 'heading_deg', 'route_stage'])
                ->filter(fn (string $column) => Schema::hasColumn('shipment_tracking', $column))
                ->values()
                ->all();

            if ($obsoleteColumns !== []) {
                Schema::table('shipment_tracking', function (Blueprint $table) use ($obsoleteColumns) {
                    $table->dropColumn($obsoleteColumns);
                });
            }
        }

        // Cạnh phải xóa trước node vì FK from/to node.
        Schema::dropIfExists('map_edges');
        Schema::dropIfExists('map_nodes');
    }

    public function down(): void
    {
        // Không khôi phục kiến trúc private-map đã loại bỏ.
    }
};
