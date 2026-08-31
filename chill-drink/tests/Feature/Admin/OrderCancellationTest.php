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
use App\Models\UserVoucher;
use App\Models\Voucher;
use App\Services\OrderCancellationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrderCancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_cancel_a_completed_order(): void
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
            ->assertSessionHas('error');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'completed',
        ]);
        $this->assertSame(10, (int) $customer->fresh()->loyaltyPoint->total_points);
        $this->assertDatabaseMissing('point_transactions', [
            'user_id' => $customer->id,
            'reference_type' => 'order',
            'reference_id' => $order->id,
            'points' => -10,
        ]);
    }

    public function test_pending_order_can_be_cancelled_without_legacy_product_stock_column(): void
    {
        $branch = Branch::create([
            'name' => 'Pending Cancellation Branch',
            'code' => 'PENDING-CANCEL',
            'address' => 'Test address',
            'status' => true,
        ]);
        $customer = User::factory()->create();
        $category = Category::create([
            'name' => 'Pending Cancellation Category',
            'slug' => 'pending-cancellation-category',
            'status' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Pending Cancellation Product',
            'slug' => 'pending-cancellation-product',
            'price' => 100000,
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
            'payment_status' => 'unpaid',
            'status' => 'pending',
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_size_id' => $productSize->id,
            'quantity' => 2,
            'unit_price' => 50000,
            'total_price' => 100000,
        ]);

        $this->assertFalse(Schema::hasColumn('products', 'stock'));

        app(OrderCancellationService::class)->cancel($order, 'Customer requested cancellation');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'cancelled',
        ]);
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_cancelling_an_unpaid_order_restores_the_customers_voucher(): void
    {
        $branch = Branch::create([
            'name' => 'Voucher Cancellation Branch',
            'code' => 'VOUCHER-CANCEL',
            'status' => true,
        ]);
        $customer = User::factory()->create();
        $voucher = Voucher::factory()->create([
            'used_count' => 1,
            'usage_limit' => 1,
            'is_redeemable' => false,
        ]);
        $ownedVoucher = UserVoucher::create([
            'user_id' => $customer->id,
            'coupon_id' => $voucher->id,
            'code' => $voucher->code,
            'is_used' => true,
            'used_at' => now(),
            'expires_at' => now()->addMonth(),
        ]);
        $order = Order::create([
            'user_id' => $customer->id,
            'branch_id' => $branch->id,
            'coupon_id' => $voucher->id,
            'subtotal' => 100000,
            'shipping_fee' => 0,
            'discount' => 20000,
            'total' => 80000,
            'payment_method' => 'vnpay',
            'payment_status' => 'failed',
            'status' => 'awaiting_payment',
        ]);
        DB::table('user_coupon_usage')->insert([
            'user_id' => $customer->id,
            'coupon_id' => $voucher->id,
            'order_id' => $order->id,
            'discount_amount' => 20000,
            'used_at' => now(),
        ]);

        app(OrderCancellationService::class)->cancel($order, 'Hết thời gian thanh toán', null, true);

        $this->assertSame(0, (int) $voucher->fresh()->used_count);
        $this->assertFalse($ownedVoucher->fresh()->is_used);
        $this->assertNull($ownedVoucher->fresh()->used_at);
        $this->assertDatabaseMissing('user_coupon_usage', ['order_id' => $order->id]);
    }
}
