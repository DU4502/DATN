<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('products', 'gallery_images')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->json('gallery_images')->nullable()->after('image');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('products', 'gallery_images')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('gallery_images');
        });
    }
};
