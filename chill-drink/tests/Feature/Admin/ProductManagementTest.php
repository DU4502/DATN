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

    public function test_admin_can_search_products_by_name_sku_and_category(): void
    {
        $admin = $this->admin();
        [$teaCategory, $coffeeCategory] = $this->productCategories();

        Product::create([
            'category_id' => $teaCategory->id,
            'name' => 'Trà sữa tìm kiếm',
            'slug' => 'tra-sua-tim-kiem',
            'sku' => 'TS-SEARCH-001',
            'price' => 45000,
            'stock' => 12,
            'status' => true,
        ]);
        Product::create([
            'category_id' => $coffeeCategory->id,
            'name' => 'Cà phê không khớp',
            'slug' => 'ca-phe-khong-khop',
            'sku' => 'CF-OTHER-001',
            'price' => 32000,
            'stock' => 8,
            'status' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.products.index', ['q' => 'TS-SEARCH']))
            ->assertOk()
            ->assertSee('Trà sữa tìm kiếm')
            ->assertDontSee('Cà phê không khớp')
            ->assertSee('value="TS-SEARCH"', false);

        $this->actingAs($admin)
            ->get(route('admin.products.index', ['q' => 'Cà Phê']))
            ->assertOk()
            ->assertSee('Cà phê không khớp')
            ->assertDontSee('Trà sữa tìm kiếm');
    }

    public function test_admin_can_filter_products_by_category_status_and_stock(): void
    {
        $admin = $this->admin();
        [$teaCategory, $coffeeCategory] = $this->productCategories();

        Product::create([
            'category_id' => $teaCategory->id,
            'name' => 'Trà đang bán sắp hết',
            'slug' => 'tra-dang-ban-sap-het',
            'price' => 45000,
            'stock' => 3,
            'status' => true,
        ]);
        Product::create([
            'category_id' => $teaCategory->id,
            'name' => 'Trà đã ẩn',
            'slug' => 'tra-da-an',
            'price' => 42000,
            'stock' => 20,
            'status' => false,
        ]);
        Product::create([
            'category_id' => $coffeeCategory->id,
            'name' => 'Cà phê hết hàng',
            'slug' => 'ca-phe-het-hang',
            'price' => 32000,
            'stock' => 0,
            'status' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.products.index', [
                'category' => $teaCategory->id,
                'status' => 'active',
                'stock' => 'low',
            ]))
            ->assertOk()
            ->assertSee('Trà đang bán sắp hết')
            ->assertDontSee('Trà đã ẩn')
            ->assertDontSee('Cà phê hết hàng')
            ->assertSee('selected', false);

        $this->actingAs($admin)
            ->get(route('admin.products.index', ['stock' => 'out']))
            ->assertOk()
            ->assertSee('Cà phê hết hàng')
            ->assertDontSee('Trà đang bán sắp hết');
    }

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
        $response->assertRedirect(route('admin.products.show', $product->id));
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
            'image' => $this->imageUpload('tra-dao-cam-sa.png'),
        ]);

        $product = Product::firstWhere('slug', 'tra-dao-cam-sa');

        $this->assertNotNull($product);
        $this->assertNotNull($product->image);
        $this->assertStringStartsWith('products/', $product->image);
        $this->assertStringContainsString('/storage/products/', $product->image_url);
        Storage::disk('public')->assertExists($product->image);
        $response->assertRedirect(route('admin.products.show', $product->id));

        $this->actingAs($admin)
            ->get(route('admin.products.show', $product->id))
            ->assertOk()
            ->assertSee('/storage/products/', false);
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
        $response->assertRedirect(route('admin.products.show', $product->id));
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

        $oldPath = $this->imageUpload('old-image.png')->store('products', 'public');

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
            'image' => $this->imageUpload('new-image.png'),
        ]);

        $product->refresh();

        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($product->image);
        $this->assertNotSame($oldPath, $product->image);
        $this->assertStringContainsString('/storage/products/', $product->image_url);
        $response->assertRedirect(route('admin.products.show', $product->id));

        $this->actingAs($admin)
            ->get(route('admin.products.edit', $product->id))
            ->assertOk()
            ->assertSee('/storage/products/', false);
    }

    public function test_uploaded_product_gallery_does_not_append_generated_placeholder_images(): void
    {
        Storage::fake('public');

        $category = Category::create([
            'name' => 'Cà Phê',
            'slug' => 'ca-phe',
            'status' => true,
        ]);

        $mainPath = $this->imageUpload('main.png')->store('products', 'public');
        $galleryPath = $this->imageUpload('gallery.png')->store('products/gallery', 'public');

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Cà phê upload',
            'slug' => 'ca-phe-upload',
            'image' => $mainPath,
            'gallery_images' => [$galleryPath],
            'price' => 30000,
            'stock' => 10,
            'status' => true,
        ]);

        $galleryImages = $product->fresh()->gallery_images;

        $this->assertCount(2, $galleryImages);
        $this->assertStringContainsString('/storage/products/', $galleryImages[0]);
        $this->assertStringContainsString('/storage/products/gallery/', $galleryImages[1]);
        $this->assertFalse(collect($galleryImages)->contains(fn (string $image) => str_contains($image, 'images.unsplash.com')));
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

        $this->assertSoftDeleted('products', ['id' => $product->id]);
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
            'role_id' => 2,
            'is_active' => 1,
        ]);
    }

    private function productCategories(): array
    {
        $teaCategory = Category::create([
            'name' => 'Trà Sữa',
            'slug' => 'tra-sua',
            'status' => true,
        ]);
        $coffeeCategory = Category::create([
            'name' => 'Cà Phê',
            'slug' => 'ca-phe',
            'status' => true,
        ]);

        return [$teaCategory, $coffeeCategory];
    }

    private function imageUpload(string $name): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'product-test-image-');
        file_put_contents($path, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAFgwJ/lYx3VwAAAABJRU5ErkJggg=='
        ));

        return new UploadedFile($path, $name, 'image/png', null, true);
    }
}
