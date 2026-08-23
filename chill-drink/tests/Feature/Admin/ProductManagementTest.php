<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Branch;
use App\Models\BranchProductStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\Size;
use App\Models\User;
use App\Support\OrderStatus;
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
            'status' => true,
        ]);
        Product::create([
            'category_id' => $coffeeCategory->id,
            'name' => 'Cà phê không khớp',
            'slug' => 'ca-phe-khong-khop',
            'sku' => 'CF-OTHER-001',
            'price' => 32000,
            'status' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.super-admin.manage.products.index', ['q' => 'TS-SEARCH']))
            ->assertOk()
            ->assertSee('Trà sữa tìm kiếm')
            ->assertDontSee('Cà phê không khớp')
            ->assertSee('value="TS-SEARCH"', false);

        $this->actingAs($admin)
            ->get(route('admin.super-admin.manage.products.index', ['q' => 'Cà Phê']))
            ->assertOk()
            ->assertSee('Cà phê không khớp')
            ->assertDontSee('Trà sữa tìm kiếm');
    }

    public function test_admin_can_filter_products_by_category_status_and_availability(): void
    {
        $admin = $this->admin();
        [$teaCategory, $coffeeCategory] = $this->productCategories();

        $branch = Branch::create(['name' => 'Chi nhánh test', 'code' => 'TEST', 'status' => true]);
        $availableProduct = Product::create([
            'category_id' => $teaCategory->id,
            'name' => 'Trà đang bán sắp hết',
            'slug' => 'tra-dang-ban-sap-het',
            'price' => 45000,
            'status' => true,
        ]);
        $hiddenProduct = Product::create([
            'category_id' => $teaCategory->id,
            'name' => 'Trà đã ẩn',
            'slug' => 'tra-da-an',
            'price' => 42000,
            'status' => false,
        ]);
        $unavailableProduct = Product::create([
            'category_id' => $coffeeCategory->id,
            'name' => 'Cà phê hết hàng',
            'slug' => 'ca-phe-het-hang',
            'price' => 32000,
            'status' => true,
        ]);
        BranchProductStatus::create(['branch_id' => $branch->id, 'product_id' => $availableProduct->id, 'is_available' => true]);
        BranchProductStatus::create(['branch_id' => $branch->id, 'product_id' => $hiddenProduct->id, 'is_available' => true]);
        BranchProductStatus::create(['branch_id' => $branch->id, 'product_id' => $unavailableProduct->id, 'is_available' => false]);

        $this->actingAs($admin)
            ->get(route('admin.super-admin.manage.products.index', [
                'category' => $teaCategory->id,
                'status' => 'active',
                'branch_id' => $branch->id,
                'availability' => 'available',
            ]))
            ->assertOk()
            ->assertSee('Trà đang bán sắp hết')
            ->assertDontSee('Trà đã ẩn')
            ->assertDontSee('Cà phê hết hàng')
            ->assertSee('selected', false);

        $this->actingAs($admin)
            ->get(route('admin.super-admin.manage.products.index', ['branch_id' => $branch->id, 'availability' => 'out_of_stock']))
            ->assertOk()
            ->assertSee('Cà phê hết hàng')
            ->assertDontSee('Trà đang bán sắp hết');
    }

    public function test_product_index_collapses_branch_statuses_into_a_summary(): void
    {
        $admin = $this->admin();
        [$category] = $this->productCategories();
        $branches = collect([
            Branch::create(['name' => 'Chi nhánh 1', 'code' => 'UI-1', 'status' => true]),
            Branch::create(['name' => 'Chi nhánh 2', 'code' => 'UI-2', 'status' => true]),
            Branch::create(['name' => 'Chi nhánh 3', 'code' => 'UI-3', 'status' => true]),
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Sản phẩm kiểm tra giao diện',
            'slug' => 'san-pham-kiem-tra-giao-dien',
            'price' => 45000,
            'status' => true,
        ]);

        foreach ($branches as $index => $branch) {
            BranchProductStatus::create([
                'branch_id' => $branch->id,
                'product_id' => $product->id,
                'is_available' => $index < 2,
            ]);
        }

        $this->actingAs($admin)
            ->get(route('admin.super-admin.manage.products.index'))
            ->assertOk()
            ->assertSee('2/3 chi nhánh còn hàng')
            ->assertSee('1 chi nhánh hết hàng')
            ->assertSee('Quản lý trạng thái')
            ->assertSee('class="d-none product-availability-details"', false)
            ->assertSee('data-availability-summary="'.$product->id.'"', false);
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
            'status' => '1',
        ]);

        $product = Product::firstWhere('slug', 'tra-sua-test');

        $this->assertNotNull($product);
        $this->assertSame($category->id, $product->category_id);
        $response->assertRedirect(route('admin.products.index'));
    }

    public function test_admin_cannot_create_a_product_with_an_existing_generated_slug(): void
    {
        $admin = $this->admin();
        $category = Category::create([
            'name' => 'Nước Ép',
            'slug' => 'nuoc-ep',
            'status' => true,
        ]);

        Product::create([
            'category_id' => $category->id,
            'name' => 'Nước Ép Cam',
            'slug' => 'nuoc-ep-cam',
            'sku' => 'CD-NE-001',
            'price' => 35000,
            'status' => true,
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.products.create'))
            ->post(route('admin.products.store'), [
                'category_id' => $category->id,
                'name' => 'Nước Ép Cam',
                'price' => 39000,
                'status' => '1',
            ]);

        $response
            ->assertRedirect(route('admin.products.create'))
            ->assertSessionHasErrors('slug');
        $this->assertDatabaseCount('products', 1);
    }

    public function test_generated_sku_does_not_reuse_a_soft_deleted_catalog_sku(): void
    {
        $category = Category::create([
            'name' => 'Nước Ép',
            'slug' => 'nuoc-ep',
            'status' => true,
        ]);

        $deletedProduct = Product::create([
            'category_id' => $category->id,
            'name' => 'Nước Ép Cam',
            'slug' => 'nuoc-ep-cam-cu',
            'sku' => 'CD-NE-001',
            'price' => 35000,
            'status' => true,
        ]);
        $deletedProduct->delete();

        $replacement = Product::create([
            'category_id' => $category->id,
            'name' => 'Nước Ép Cam',
            'slug' => 'nuoc-ep-cam',
            'price' => 39000,
            'status' => true,
        ]);

        $this->assertNotSame($deletedProduct->sku, $replacement->sku);
        $this->assertSame('CD-NE-002', $replacement->sku);
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
            'status' => '1',
            'image' => $this->imageUpload('tra-dao-cam-sa.png'),
        ]);

        $product = Product::firstWhere('slug', 'tra-dao-cam-sa');

        $this->assertNotNull($product);
        $this->assertNotNull($product->image);
        $this->assertStringStartsWith('products/', $product->image);
        $this->assertStringContainsString('/storage/products/', $product->image_url);
        Storage::disk('public')->assertExists($product->image);
        $response->assertRedirect(route('admin.products.index'));

        $this->actingAs($admin)
            ->get(route('admin.super-admin.manage.products.show', $product->id))
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
            'status' => true,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.products.update', $product), [
            'category_id' => $category->id,
            'name' => 'Cà Phê Mới',
            'slug' => 'ca-phe-moi',
            'price' => 30000,
            'status' => '0',
        ]);

        $product->refresh();

        $this->assertSame('Cà Phê Mới', $product->name);
        $this->assertSame('ca-phe-moi', $product->slug);
        $this->assertFalse($product->status);
        $response->assertRedirect(route('admin.products.index'));
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
            'status' => true,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.products.update', $product->id), [
            'category_id' => $category->id,
            'name' => 'Soda Blue New',
            'slug' => 'soda-blue-new',
            'price' => 37000,
            'status' => '1',
            'image' => $this->imageUpload('new-image.png'),
        ]);

        $product->refresh();

        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($product->image);
        $this->assertNotSame($oldPath, $product->image);
        $this->assertStringContainsString('/storage/products/', $product->image_url);
        $response->assertRedirect(route('admin.products.index'));

        $this->actingAs($admin)
            ->get(route('admin.super-admin.manage.products.edit', $product->id))
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
            'status' => true,
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.products.destroy', $product));

        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $response->assertRedirect(route('admin.products.index'));
    }

    public function test_admin_cannot_delete_product_with_unfinished_orders(): void
    {
        $admin = $this->admin();
        [$product] = $this->productWithOrder(OrderStatus::PENDING);

        $response = $this->actingAs($admin)->delete(route('admin.products.destroy', $product));

        $response->assertRedirect(route('admin.products.index'));
        $response->assertSessionHas('error', 'Không thể xóa sản phẩm vì vẫn còn đơn hàng chưa hoàn thành.');
        $this->assertDatabaseHas('products', ['id' => $product->id, 'deleted_at' => null]);
    }

    public function test_admin_cannot_force_delete_product_with_unfinished_orders(): void
    {
        $admin = $this->admin();
        [$product] = $this->productWithOrder(OrderStatus::CONFIRMED);
        $product->delete();

        $response = $this->actingAs($admin)->delete(route('admin.products.force-delete', $product->id));

        $response->assertRedirect(route('admin.products.trash'));
        $response->assertSessionHas('error', 'Không thể xóa vĩnh viễn vì sản phẩm vẫn còn đơn hàng chưa hoàn thành.');
        $this->assertSoftDeleted('products', ['id' => $product->id]);
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
                'status' => '1',
                'image' => UploadedFile::fake()->create('wrong-file.pdf', 120, 'application/pdf'),
            ]);

        $response->assertRedirect(route('admin.products.create'));
        $response->assertSessionHasErrors(['image', 'price']);
        $this->assertDatabaseMissing('products', ['name' => 'Cam Ép Test']);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role_id' => 3,
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

    private function productWithOrder(string $orderStatus): array
    {
        $category = Category::create([
            'name' => 'Order Guard '.uniqid(),
            'slug' => 'order-guard-'.uniqid(),
            'status' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Guard Product '.uniqid(),
            'slug' => 'guard-product-'.uniqid(),
            'price' => 45000,
            'status' => true,
        ]);

        $size = Size::create(['name' => 'M', 'multiplier' => 1]);
        $productSize = ProductSize::create([
            'product_id' => $product->id,
            'size_id' => $size->id,
            'price' => 45000,
        ]);

        $customer = User::factory()->create(['role_id' => 1]);
        $order = Order::create([
            'user_id' => $customer->id,
            'subtotal' => 45000,
            'shipping_fee' => 0,
            'discount' => 0,
            'total' => 45000,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'status' => $orderStatus,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_size_id' => $productSize->id,
            'quantity' => 1,
            'unit_price' => 45000,
            'total_price' => 45000,
        ]);

        return [$product, $order];
    }
}
