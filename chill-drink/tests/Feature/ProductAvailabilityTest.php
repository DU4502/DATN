<?php

namespace Tests\Feature;

use App\Events\ProductAvailabilityUpdated;
use App\Models\Branch;
use App\Models\BranchProductStatus;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\Size;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
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

    public function test_admin_and_super_admin_updates_succeed_when_realtime_broadcast_fails(): void
    {
        config()->set('broadcasting.default', 'null');

        $superAdmin = User::factory()->create(['role_id' => 3, 'is_active' => true]);
        $superAdminBranch = $this->branch('SUPER-REALTIME-OFFLINE');
        $branchAdminBranch = $this->branch('ADMIN-REALTIME-OFFLINE');
        $branchAdmin = User::factory()->create([
            'role_id' => 2,
            'branch_id' => $branchAdminBranch->id,
            'is_active' => true,
        ]);
        $superAdminProduct = Product::factory()->create();
        $branchAdminProduct = Product::factory()->create();

        Event::listen(ProductAvailabilityUpdated::class, function (): never {
            throw new RuntimeException('Realtime server is unavailable.');
        });

        foreach ([
            [$superAdmin, $superAdminBranch, $superAdminProduct],
            [$branchAdmin, $branchAdminBranch, $branchAdminProduct],
        ] as [$user, $branch, $product]) {
            $this->actingAs($user)
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
        }
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

    public function test_cart_keeps_unavailable_item_visible_but_excludes_it_from_checkout(): void
    {
        $branch = $this->branch('CART-LIVE-STATUS');
        $product = Product::factory()->create([
            'name' => 'Soda vừa tạm hết',
            'price' => 38000,
        ]);
        BranchProductStatus::query()
            ->where('branch_id', $branch->id)
            ->where('product_id', $product->id)
            ->update(['is_available' => false]);

        $cart = [
            'live-status-item' => [
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => 38000,
                'quantity' => 1,
                'size' => 'S',
                'size_label' => 'Size S',
            ],
        ];

        $this->withSession([
            'nearest_branch_id' => $branch->id,
            'cart' => $cart,
        ])->get(route('cart.index'))
            ->assertOk()
            ->assertSee($product->name)
            ->assertSee('Tạm hết hàng tại '.$branch->name)
            ->assertSee('data-cart-available="0"', false)
            ->assertSee('product:availability-applied', false);
    }

    public function test_cart_normalizes_size_prices_without_double_counting_base_price(): void
    {
        $branch = $this->branch('PRICING');

        $finalPriceProduct = Product::factory()->create(['price' => 34000]);
        BranchProductStatus::query()
            ->where('branch_id', $branch->id)
            ->where('product_id', $finalPriceProduct->id)
            ->update(['is_available' => true]);

        $finalSize = Size::create(['name' => 'S', 'multiplier' => 1]);
        ProductSize::create([
            'product_id' => $finalPriceProduct->id,
            'size_id' => $finalSize->id,
            'price' => 34000,
        ]);

        $finalResponse = $this->withSession(['nearest_branch_id' => $branch->id])
            ->postJson(route('cart.add', $finalPriceProduct->id), [
                'size' => 'S',
                'sugar_level' => 100,
                'ice_level' => 100,
                'quantity' => 1,
            ])
            ->assertOk();

        $finalCart = session('cart');
        $finalItem = array_values($finalCart)[0];

        $finalResponse->assertJsonPath('total', 34000);
        $this->assertSame(34000, (int) $finalItem['price']);
        $this->assertSame(0, (int) $finalItem['size_extra']);

        $extraPriceProduct = Product::factory()->create(['price' => 34000]);
        BranchProductStatus::query()
            ->where('branch_id', $branch->id)
            ->where('product_id', $extraPriceProduct->id)
            ->update(['is_available' => true]);

        $extraSize = Size::create(['name' => 'M', 'multiplier' => 1.1]);
        ProductSize::create([
            'product_id' => $extraPriceProduct->id,
            'size_id' => $extraSize->id,
            'price' => 5000,
        ]);

        $extraResponse = $this->withSession(['nearest_branch_id' => $branch->id, 'cart' => []])
            ->postJson(route('cart.add', $extraPriceProduct->id), [
                'size' => 'M',
                'sugar_level' => 100,
                'ice_level' => 100,
                'quantity' => 1,
            ])
            ->assertOk();

        $extraCart = session('cart');
        $extraItem = array_values($extraCart)[0];

        $extraResponse->assertJsonPath('total', 39000);
        $this->assertSame(39000, (int) $extraItem['price']);
        $this->assertSame(5000, (int) $extraItem['size_extra']);
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
            ->getJson(route('checkout.availability', ['branch_id' => $branch->id]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('unavailable.0.product_id', $product->id)
            ->assertJsonPath('unavailable.0.name', $product->name);

        $this->withSession(['cart' => $cart, 'nearest_branch_id' => $branch->id])
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
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('error', fn (string $message) => str_contains($message, $product->name));

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
