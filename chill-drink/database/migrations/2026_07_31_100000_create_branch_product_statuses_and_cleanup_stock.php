<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('branch_product_statuses')) {
            Schema::create('branch_product_statuses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->integer('product_id');
                $table->boolean('is_available')->default(true);
                $table->timestamps();

                $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
                $table->unique(['branch_id', 'product_id']);
            });
        }

        if (Schema::hasColumn('products', 'stock')) {
            $branchIds = DB::table('branches')->where('status', true)->pluck('id');

            DB::table('products')
                ->select(['id', 'stock', 'created_at'])
                ->orderBy('id')
                ->chunkById(500, function ($products) use ($branchIds) {
                    foreach ($products as $product) {
                        foreach ($branchIds as $branchId) {
                            DB::table('branch_product_statuses')->insertOrIgnore([
                                'branch_id' => $branchId,
                                'product_id' => $product->id,
                                'is_available' => (int) $product->stock > 0,
                                'created_at' => $product->created_at ?? now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                });
        }

        if (Schema::hasTable('branch_product_stocks')
            && Schema::hasColumn('branch_product_stocks', 'branch_id')
            && Schema::hasColumn('branch_product_stocks', 'product_id')) {
            $hasQuantity = Schema::hasColumn('branch_product_stocks', 'quantity');

            $migrateStocks = function ($stocks) use ($hasQuantity) {
                    foreach ($stocks as $stock) {
                        DB::table('branch_product_statuses')->updateOrInsert(
                            [
                                'branch_id' => $stock->branch_id,
                                'product_id' => $stock->product_id,
                            ],
                            [
                                'is_available' => $hasQuantity ? ((int) $stock->quantity > 0) : true,
                                'created_at' => $stock->created_at ?? now(),
                                'updated_at' => now(),
                            ]
                        );
                    }
                };

            if (Schema::hasColumn('branch_product_stocks', 'id')) {
                DB::table('branch_product_stocks')->orderBy('id')->chunkById(500, $migrateStocks);
            } else {
                DB::table('branch_product_stocks')->orderBy('branch_id')->orderBy('product_id')->chunk(500, $migrateStocks);
            }
        }

        Schema::dropIfExists('stock_histories');
        Schema::dropIfExists('branch_product_stocks');

        if (Schema::hasColumn('products', 'stock')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('stock');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('products', 'stock')) {
            Schema::table('products', function (Blueprint $table) {
                $table->integer('stock')->default(100);
            });
        }

        Schema::dropIfExists('branch_product_statuses');
    }
};
