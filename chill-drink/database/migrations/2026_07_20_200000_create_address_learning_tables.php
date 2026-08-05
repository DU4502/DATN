<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (! Schema::hasColumn('orders', 'shipping_address_text')) {
                    $table->string('shipping_address_text', 500)->nullable()->after('delivery_note');
                }

                if (! Schema::hasColumn('orders', 'shipping_latitude')) {
                    $table->decimal('shipping_latitude', 10, 7)->nullable()->after('shipping_address_text');
                }

                if (! Schema::hasColumn('orders', 'shipping_longitude')) {
                    $table->decimal('shipping_longitude', 10, 7)->nullable()->after('shipping_latitude');
                }
            });
        }

        if (! Schema::hasTable('address_observations')) {
            Schema::create('address_observations', function (Blueprint $table) {
                $table->id();
                $table->integer('user_id')->nullable()->index();
                $table->integer('order_id')->nullable()->index();
                $table->integer('address_id')->nullable()->index();
                $table->string('source_type', 40)->default('user_input')->index();
                $table->string('full_address', 500);
                $table->string('house_number', 50)->nullable();
                $table->string('road_name', 255)->nullable()->index();
                $table->string('ward', 100)->nullable();
                $table->string('district', 100)->nullable();
                $table->string('province', 100)->nullable();
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->string('normalized_key', 255)->index();
                $table->string('status', 40)->default('user_submitted')->index();
                $table->decimal('confidence', 4, 2)->default(0.30);
                $table->timestamp('delivered_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['latitude', 'longitude']);
                $table->index(['normalized_key', 'status']);
            });
        }

        if (! Schema::hasTable('verified_address_points')) {
            Schema::create('verified_address_points', function (Blueprint $table) {
                $table->id();
                $table->string('full_address', 500);
                $table->string('house_number', 50)->nullable();
                $table->string('road_name', 255)->nullable()->index();
                $table->string('ward', 100)->nullable();
                $table->string('district', 100)->nullable();
                $table->string('province', 100)->nullable();
                $table->decimal('latitude', 10, 7);
                $table->decimal('longitude', 10, 7);
                $table->string('normalized_key', 255)->index();
                $table->unsignedInteger('observation_count')->default(1);
                $table->unsignedInteger('successful_delivery_count')->default(0);
                $table->string('verification_level', 40)->default('user_submitted')->index();
                $table->decimal('confidence', 4, 2)->default(0.30);
                $table->timestamp('last_observed_at')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['latitude', 'longitude']);
                $table->index(['normalized_key', 'verification_level']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('verified_address_points');
        Schema::dropIfExists('address_observations');

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                foreach (['shipping_longitude', 'shipping_latitude', 'shipping_address_text'] as $column) {
                    if (Schema::hasColumn('orders', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
