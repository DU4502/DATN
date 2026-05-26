<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_admin_can_upload_image_when_creating_product(): void
    {
        Storage::fake('public');

        $admin = $this->admin();
        $category = Category::create([
            'name' => 'Trà Trái Cây',
            'slug' => 'tra-trai-cay',
            'status' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.products.store'), [
            'category_id' => $category->id,
            'name' => 'Trà Đào Cam Sả',
            'price' => 49000,
            'stock' => 10,
            'status' => '1',
            'image' => UploadedFile::fake()->image('tra-dao-cam-sa.png'),
        ]);

        $product = Product::firstWhere('slug', 'tra-dao-cam-sa');

        $this->assertNotNull($product);
        $this->assertNotNull($product->image);
        $this->assertStringStartsWith('products/', $product->image);
        Storage::disk('public')->assertExists($product->image);
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

    public function test_admin_can_replace_old_image_when_updating_product(): void
    {
        Storage::fake('public');

        $admin = $this->admin();
        $category = Category::create([
            'name' => 'Soda',
            'slug' => 'soda',
            'status' => true,
        ]);

        $oldPath = UploadedFile::fake()->image('old-image.jpg')->store('products', 'public');

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Soda Blue',
            'slug' => 'soda-blue',
            'image' => $oldPath,
            'price' => 35000,
            'stock' => 8,
            'status' => true,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.products.update', $product->id), [
            'category_id' => $category->id,
            'name' => 'Soda Blue New',
            'slug' => 'soda-blue-new',
            'price' => 37000,
            'stock' => 12,
            'status' => '1',
            'image' => UploadedFile::fake()->image('new-image.jpg'),
        ]);

        $product->refresh();

        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($product->image);
        $this->assertNotSame($oldPath, $product->image);
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

    public function test_product_validation_rejects_invalid_image_and_negative_values(): void
    {
        Storage::fake('public');

        $admin = $this->admin();
        $category = Category::create([
            'name' => 'Nước Ép',
            'slug' => 'nuoc-ep',
            'status' => true,
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.products.create'))
            ->post(route('admin.products.store'), [
                'category_id' => $category->id,
                'name' => 'Cam Ép Test',
                'price' => -1,
                'stock' => -5,
                'status' => '1',
                'image' => UploadedFile::fake()->create('wrong-file.pdf', 120, 'application/pdf'),
            ]);

        $response->assertRedirect(route('admin.products.create'));
        $response->assertSessionHasErrors(['image', 'price', 'stock']);
        $this->assertDatabaseMissing('products', ['name' => 'Cam Ép Test']);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
        ]);
    }
}
