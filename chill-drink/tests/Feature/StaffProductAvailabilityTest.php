<?php

namespace Tests\Feature;

use App\Events\ProductAvailabilityUpdated;
use App\Models\Branch;
use App\Models\BranchProductStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\Size;
use App\Models\User;
use App\Services\ProductAvailabilityService;
use App\Support\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Tests\TestCase;

class StaffProductAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_only_sees_products_assigned_to_their_branch(): void
    {
        $branch = $this->branch('STAFF-PRODUCT-A');
        $otherBranch = $this->branch('STAFF-PRODUCT-B');
        $staff = $this->staff($branch);
        $visibleProduct = Product::factory()->create(['name' => 'Visible Matcha']);
        $hiddenProduct = Product::factory()->create(['name' => 'Hidden Coffee']);
        BranchProductStatus::query()
            ->where('branch_id', $branch->id)
            ->where('product_id', $hiddenProduct->id)
            ->delete();

        $this->actingAs($staff)
            ->get(route('staff.products.availability.index'))
            ->assertOk()
            ->assertSee($visibleProduct->name)
            ->assertDontSee($hiddenProduct->name)
            ->assertSee($branch->name)
            ->assertDontSee($otherBranch->name)
            ->assertDontSee('Sửa sản phẩm')
            ->assertDontSee('Xóa sản phẩm')
            ->assertDontSee('Thêm sản phẩm');
    }

    public function test_staff_can_mark_sold_out_and_reopen_only_their_branch(): void
    {
        Event::fake([ProductAvailabilityUpdated::class]);
        $branch = $this->branch('STAFF-TOGGLE-A');
        $otherBranch = $this->branch('STAFF-TOGGLE-B');
        $staff = $this->staff($branch);
        $product = Product::factory()->create();
        $originalProduct = $product->only(['name', 'slug', 'sku', 'price', 'status']);

        $this->actingAs($staff)
            ->patchJson(route('staff.products.availability.update', $product), ['is_available' => false])
            ->assertOk()
            ->assertJsonPath('branch_id', $branch->id)
            ->assertJsonPath('is_available', false);

        $this->assertFalse($this->availability($branch, $product));
        $this->assertTrue($this->availability($otherBranch, $product));
        $this->assertSame($originalProduct, $product->fresh()->only(array_keys($originalProduct)));

        $this->patchJson(route('staff.products.availability.update', $product), ['is_available' => true])
            ->assertOk()
            ->assertJsonPath('is_available', true);

        $this->assertTrue($this->availability($branch, $product));
        Event::assertDispatchedTimes(ProductAvailabilityUpdated::class, 2);
    }

    public function test_staff_branch_tampering_is_rejected_without_changing_either_branch(): void
    {
        $branch = $this->branch('STAFF-TAMPER-A');
        $otherBranch = $this->branch('STAFF-TAMPER-B');
        $staff = $this->staff($branch);
        $product = Product::factory()->create();

        $this->actingAs($staff)
            ->patchJson(route('staff.products.availability.update', $product), [
                'is_available' => false,
                'branch_id' => $otherBranch->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('branch_id');

        $this->assertTrue($this->availability($branch, $product));
        $this->assertTrue($this->availability($otherBranch, $product));

        $this->patchJson(route('admin.products.branches.availability.update', [$product->id, $otherBranch]), [
            'is_available' => false,
        ])->assertRedirect(route('home'));

        $this->assertTrue($this->availability($otherBranch, $product));
    }

    public function test_staff_cannot_assign_an_unserved_product_to_their_branch(): void
    {
        $branch = $this->branch('STAFF-UNSERVED');
        $staff = $this->staff($branch);
        $product = Product::factory()->create();
        BranchProductStatus::query()
            ->where('branch_id', $branch->id)
            ->where('product_id', $product->id)
            ->delete();

        $this->actingAs($staff)
            ->patchJson(route('staff.products.availability.update', $product), ['is_available' => true])
            ->assertNotFound();

        $this->assertDatabaseMissing('branch_product_statuses', [
            'branch_id' => $branch->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_shipper_and_customer_do_not_receive_staff_availability_permission(): void
    {
        $branch = $this->branch('STAFF-ROLE-AVAILABILITY');
        $shipper = User::factory()->create([
            'role_id' => User::SHIPPER_ROLE_ID,
            'branch_id' => $branch->id,
        ]);
        $customer = User::factory()->create(['role_id' => 1]);

        foreach ([$shipper, $customer] as $user) {
            $this->actingAs($user)
                ->get(route('staff.products.availability.index'))
                ->assertRedirect(route('home'));
        }
    }

    public function test_existing_order_and_items_are_unchanged_when_staff_marks_product_sold_out(): void
    {
        Event::fake([ProductAvailabilityUpdated::class]);
        $branch = $this->branch('STAFF-HISTORY');
        $staff = $this->staff($branch);
        $customer = User::factory()->create(['role_id' => 1]);
        $product = Product::factory()->create(['price' => 42000]);
        $size = Size::create(['name' => 'S', 'multiplier' => 1]);
        $productSize = ProductSize::create([
            'product_id' => $product->id,
            'size_id' => $size->id,
            'price' => 42000,
        ]);
        $order = Order::create([
            'order_code' => 'STAFF-HISTORY-ORDER',
            'user_id' => $customer->id,
            'branch_id' => $branch->id,
            'fulfillment_type' => 'delivery',
            'subtotal' => 42000,
            'shipping_fee' => 0,
            'discount' => 0,
            'total' => 42000,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'status' => OrderStatus::CONFIRMED,
        ]);
        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_size_id' => $productSize->id,
            'quantity' => 1,
            'unit_price' => 42000,
            'total_price' => 42000,
            'ice_level' => 100,
            'sugar_level' => 100,
        ]);

        $this->actingAs($staff)
            ->patchJson(route('staff.products.availability.update', $product), ['is_available' => false])
            ->assertOk();

        $this->assertSame(OrderStatus::CONFIRMED, $order->fresh()->status);
        $this->assertSame($product->id, (int) $orderItem->fresh()->product_id);
        $this->assertSame(42000, (int) $orderItem->fresh()->total_price);
        $this->assertDatabaseCount('order_items', 1);
    }

    public function test_checkout_availability_service_rejects_sold_out_branch_but_keeps_other_branch_available(): void
    {
        Event::fake([ProductAvailabilityUpdated::class]);
        $branch = $this->branch('STAFF-CHECKOUT-A');
        $otherBranch = $this->branch('STAFF-CHECKOUT-B');
        $staff = $this->staff($branch);
        $product = Product::factory()->create();
        $cart = [['product_id' => $product->id, 'quantity' => 1]];

        $this->actingAs($staff)
            ->patchJson(route('staff.products.availability.update', $product), ['is_available' => false])
            ->assertOk();

        $service = app(ProductAvailabilityService::class);
        try {
            $service->assertCartAvailable($cart, $branch);
            $this->fail('Branch sold out must reject checkout availability validation.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString($product->name, $exception->getMessage());
        }

        $service->assertCartAvailable($cart, $otherBranch);
        $this->addToAssertionCount(1);
    }

    private function branch(string $code): Branch
    {
        return Branch::create([
            'name' => $code,
            'code' => $code,
            'status' => true,
        ]);
    }

    private function staff(Branch $branch): User
    {
        return User::factory()->create([
            'role_id' => User::STAFF_ROLE_ID,
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
    }

    private function availability(Branch $branch, Product $product): bool
    {
        return (bool) BranchProductStatus::query()
            ->where('branch_id', $branch->id)
            ->where('product_id', $product->id)
            ->value('is_available');
    }
}
