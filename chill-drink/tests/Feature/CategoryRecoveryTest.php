<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CategoryRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_corrupted_categories_are_backed_up_and_recovered_without_changing_products(): void
    {
        DB::table('categories')->insert([
            'id' => 1,
            'name' => 'Corrupted',
            'slug' => 'corrupted',
            'status' => -80,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $product = Product::create([
            'category_id' => 1,
            'name' => 'Sản phẩm giữ nguyên',
            'slug' => 'san-pham-giu-nguyen',
            'price' => 45000,
            'status' => true,
        ]);

        $migration = require database_path('migrations/2026_07_31_120000_restore_categories_table_safely.php');
        $migration->up();

        $this->assertTrue(Schema::hasTable('categories_corrupt_backup_20260731'));
        $this->assertDatabaseHas('categories', [
            'id' => 1,
            'name' => 'Trà Sữa',
            'slug' => 'tra-sua',
            'status' => 1,
        ]);
        $this->assertSame(1, (int) $product->fresh()->category_id);
        $this->assertSame('Trà Sữa', $product->fresh()->category->name);
    }
}
