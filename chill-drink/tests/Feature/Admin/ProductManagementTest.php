<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_product(): void
    {
        $admin = $this->admin();
        $category = Category::create([
            'name' => 'Trà Sữa',
            'slug' => 'tra-sua',
            'status' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.products.store'), [
            'category_id' => $category->id,
            'name' => 'Trà Sữa Test',
            'price' => 39000,
            'stock' => 20,
            'status' => '1',
        ]);

        $product = Product::firstWhere('slug', 'tra-sua-test');

        $this->assertNotNull($product);
        $this->assertSame($category->id, $product->category_id);
        $response->assertRedirect(route('admin.products.show', $product));
    }

    public function test_admin_can_update_product(): void
    {
        $admin = $this->admin();
        $category = Category::create([
            'name' => 'Cà Phê',
            'slug' => 'ca-phe',
            'status' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Cà Phê Cũ',
            'slug' => 'ca-phe-cu',
            'price' => 25000,
            'stock' => 10,
            'status' => true,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.products.update', $product), [
            'category_id' => $category->id,
            'name' => 'Cà Phê Mới',
            'slug' => 'ca-phe-moi',
            'price' => 30000,
            'stock' => 15,
            'status' => '0',
        ]);

        $product->refresh();

        $this->assertSame('Cà Phê Mới', $product->name);
        $this->assertSame('ca-phe-moi', $product->slug);
        $this->assertFalse($product->status);
        $response->assertRedirect(route('admin.products.show', $product));
    }

    public function test_admin_can_delete_product_without_orders(): void
    {
        $admin = $this->admin();
        $category = Category::create([
            'name' => 'Soda',
            'slug' => 'soda',
            'status' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Soda Test',
            'slug' => 'soda-test',
            'price' => 36000,
            'stock' => 12,
            'status' => true,
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.products.destroy', $product));

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $response->assertRedirect(route('admin.products.index'));
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
        ]);
    }
}
