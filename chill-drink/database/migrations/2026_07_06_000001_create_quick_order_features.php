<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('product_id');
            $table->timestamps();
            $table->unique(['user_id', 'product_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });

        Schema::create('taste_profiles', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('product_id');
            $table->string('size', 10)->default('M');
            $table->unsignedTinyInteger('sugar_level')->default(100);
            $table->unsignedTinyInteger('ice_level')->default(100);
            $table->json('toppings')->nullable();
            $table->string('note', 500)->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'product_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });

        Schema::create('group_orders', function (Blueprint $table) {
            $table->id();
            $table->integer('owner_id');
            $table->string('name', 120);
            $table->string('code', 16)->unique();
            $table->string('status', 20)->default('open')->index();
            $table->dateTime('closes_at');
            $table->string('note', 500)->nullable();
            $table->integer('order_id')->nullable();
            $table->timestamps();
            $table->foreign('owner_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
        });

        Schema::create('group_order_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_order_id')->constrained()->cascadeOnDelete();
            $table->integer('user_id')->nullable();
            $table->string('name', 100);
            $table->string('member_token', 64);
            $table->timestamps();
            $table->unique(['group_order_id', 'member_token']);
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('group_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('group_order_member_id')->constrained()->cascadeOnDelete();
            $table->integer('product_id');
            $table->string('size', 10)->default('M');
            $table->unsignedTinyInteger('sugar_level')->default(100);
            $table->unsignedTinyInteger('ice_level')->default(100);
            $table->json('toppings')->nullable();
            $table->string('note', 500)->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('unit_price');
            $table->timestamps();
            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
        });

        Schema::create('reorder_history', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('source_order_id');
            $table->integer('source_order_item_id')->nullable();
            $table->string('type', 20);
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('source_order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('source_order_item_id')->references('id')->on('order_items')->nullOnDelete();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('scheduled_at')->nullable()->after('note')->index();
        });
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('item_note', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', fn (Blueprint $table) => $table->dropColumn('item_note'));
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn('scheduled_at'));
        Schema::dropIfExists('reorder_history');
        Schema::dropIfExists('group_order_items');
        Schema::dropIfExists('group_order_members');
        Schema::dropIfExists('group_orders');
        Schema::dropIfExists('taste_profiles');
        Schema::dropIfExists('favorites');
    }
};
