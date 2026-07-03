<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'price')) {
                $table->decimal('price', 10, 2)->default(0);
            }

            if (! Schema::hasColumn('products', 'stock')) {
                $table->integer('stock')->default(100);
            }
        });

        if (Schema::hasTable('product_sizes') && DB::getDriverName() === 'mysql') {
            DB::statement("
                UPDATE products
                JOIN (
                    SELECT product_id, MIN(price) AS min_price
                    FROM product_sizes
                    GROUP BY product_id
                ) size_prices ON size_prices.product_id = products.id
                SET products.price = size_prices.min_price
                WHERE products.price = 0
            ");
        }

        DB::table('products')
            ->where('stock', 0)
            ->update(['stock' => 100]);

        if (Schema::hasTable('orders') && DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY payment_method ENUM('cod','bank_transfer','vnpay','momo','card','wallet') NOT NULL DEFAULT 'cod'");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('orders') && DB::getDriverName() === 'mysql') {
            DB::table('orders')
                ->where('payment_method', 'bank_transfer')
                ->update(['payment_method' => 'cod']);

            DB::statement("ALTER TABLE orders MODIFY payment_method ENUM('cod','vnpay','momo','card','wallet') NOT NULL DEFAULT 'cod'");
        }
    }
};
