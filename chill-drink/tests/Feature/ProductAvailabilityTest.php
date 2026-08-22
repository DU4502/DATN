<?php

namespace Tests\Feature;

use App\Events\ProductAvailabilityUpdated;
use App\Models\Branch;
use App\Models\BranchProductStatus;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_schema_contains_only_branch_product_availability_data(): void
    {
        $this->assertTrue(Schema::hasTable('branch_product_statuses'));
        $this->assertTrue(Schema::hasColumns('branch_product_statuses', [
            'id', 'branch_id', 'product_id', 'is_available', 'created_at', 'updated_at',
        ]));
        $this->assertFalse(Schema::hasColumn('products', 'stock'));
        $this->assertFalse(Schema::hasTable('branch_product_stocks'));
        $this->assertFalse(Schema::hasTable('stock_histories'));
    }

    public function test_super_admin_can_update_any_branch_and_broadcast_event(): void
    {
        Event::fake([ProductAvailabilityUpdated::class]);
        $superAdmin = User::factory()->create(['role_id' => 3, 'is_active' => true]);
        $branch = $this->branch('SUPER');
        $product = Product::factory()->create();

        $this->actingAs($superAdmin)
            ->patchJson(route('admin.products.branches.availability.update', [$product->id, $branch]), [
                'is_available' => false,
            ])
            ->assertOk()
            ->assertJsonPath('is_available', false);

        $this->assertDatabaseHas('branch_product_statuses', [
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'is_available' => false,
        ]);
        Event::assertDispatched(ProductAvailabilityUpdated::class);
    }

    public function test_branch_admin_cannot_update_another_branch(): void
    {
        $ownBranch = $this->branch('OWN');
        $otherBranch = $this->branch('OTHER');
        $admin = User::factory()->create([
            'role_id' => 2,
            'branch_id' => $ownBranch->id,
            'is_active' => true,
        ]);
        $product = Product::factory()->create();

        $this->actingAs($admin)
            ->patchJson(route('admin.products.branches.availability.update', [$product->id, $otherBranch]), [
                'is_available' => false,
            ])
            ->assertForbidden();

        $this->assertTrue((bool) BranchProductStatus::query()
            ->where('branch_id', $otherBranch->id)
            ->where('product_id', $product->id)
            ->value('is_available'));
    }

    public function test_cart_rejects_unavailable_and_unassigned_products(): void
    {
        $branch = $this->branch('CART');
        $product = Product::factory()->create();
        $status = BranchProductStatus::query()
            ->where('branch_id', $branch->id)
            ->where('product_id', $product->id)
            ->firstOrFail();

        $status->update(['is_available' => false]);
        $this->withSession(['nearest_branch_id' => $branch->id])
            ->postJson(route('cart.add', $product->id), $this->cartOptions())
            ->assertUnprocessable()
            ->assertJsonPath('message', fn (string $message) => str_contains($message, $product->name));

        $status->delete();
        $this->withSession(['nearest_branch_id' => $branch->id])
            ->postJson(route('cart.add', $product->id), $this->cartOptions())
            ->assertUnprocessable()
            ->assertJsonPath('message', fn (string $message) => str_contains($message, $product->name));
    }

    public function test_checkout_rechecks_availability_before_creating_order(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            '*/route/v1/*' => Http::response([
                'code' => 'Ok',
                'routes' => [[
                    'distance' => 1000,
                    'duration' => 180,
                    'geometry' => ['coordinates' => [[106.7009, 10.7769], [106.701, 10.777]]],
                    'legs' => [],
                ]],
            ]),
        ]);

        $branch = $this->branch('CHECKOUT');
        $product = Product::factory()->create(['price' => 45000]);
        BranchProductStatus::query()
            ->where('branch_id', $branch->id)
            ->where('product_id', $product->id)
            ->update(['is_available' => false]);
        $customer = User::factory()->create([
            'role_id' => 1,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $cart = [
            'availability-item' => [
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => 45000,
                'quantity' => 1,
                'size' => 'S',
            ],
        ];

        $this->actingAs($customer)
            ->withSession(['cart' => $cart, 'nearest_branch_id' => $branch->id])
            ->from(route('checkout.index'))
            ->post(route('checkout.process'), [
                'payment_method' => 'cod',
                'fulfillment_type' => 'delivery',
                'branch_id' => $branch->id,
                'shipping_method_ui' => 'standard',
                'shipping_address_ui' => '123 Nguyễn Huệ',
                'shipping_area_ui' => 'Quận 1',
                'shipping_phone_ui' => '0901234567',
                'latitude' => 10.777,
                'longitude' => 106.701,
                'address_location_confirmed' => '1',
            ])
            ->assertRedirect(route('checkout.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 0);
    }

    private function branch(string $code): Branch
    {
        return Branch::create([
            'name' => 'Chi nhánh '.$code,
            'code' => $code,
            'latitude' => 10.7769,
            'longitude' => 106.7009,
            'status' => true,
        ]);
    }

    private function cartOptions(): array
    {
        return [
            'size' => 'S',
            'sugar_level' => 100,
            'ice_level' => 100,
            'quantity' => 1,
        ];
    }
}
