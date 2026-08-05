<?php

namespace Tests\Feature\Admin;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\Size;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderCancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_cancel_completed_order_and_revoke_awarded_points(): void
    {
        $branch = Branch::create([
            'name' => 'Cancellation Branch',
            'code' => 'CANCEL-TEST',
            'address' => 'Test address',
            'status' => true,
        ]);
        $admin = User::factory()->create(['role_id' => 2, 'branch_id' => $branch->id]);
        $customer = User::factory()->create();
        $category = Category::create([
            'name' => 'Cancellation Category',
            'slug' => 'cancellation-category',
            'status' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Cancellation Product',
            'slug' => 'cancellation-product',
            'price' => 100000,
            'stock' => 3,
            'status' => true,
        ]);
        $size = Size::create(['name' => 'M', 'multiplier' => 1]);
        $productSize = ProductSize::create([
            'product_id' => $product->id,
            'size_id' => $size->id,
            'price' => 100000,
        ]);
        $order = Order::create([
            'user_id' => $customer->id,
            'branch_id' => $branch->id,
            'subtotal' => 100000,
            'shipping_fee' => 0,
            'discount' => 0,
            'total' => 100000,
            'payment_method' => 'cod',
            'payment_status' => 'paid',
            'status' => 'completed',
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_size_id' => $productSize->id,
            'quantity' => 2,
            'unit_price' => 50000,
            'total_price' => 100000,
        ]);

        $order->awardLoyaltyPoints();

        $this->actingAs($admin)
            ->put(route('admin.orders.updateStatus', $order->id), [
                'status' => 'cancelled',
                'cancellation_reason' => 'Customer support cancellation',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'cancelled',
        ]);
        $this->assertSame(5, (int) $product->fresh()->stock);
        $this->assertSame(0, (int) $customer->fresh()->loyaltyPoint->total_points);
        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $customer->id,
            'reference_type' => 'order',
            'reference_id' => $order->id,
            'points' => -10,
        ]);
    }
}
