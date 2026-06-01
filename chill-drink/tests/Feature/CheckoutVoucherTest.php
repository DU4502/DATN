<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\Size;
use App\Models\User;
use App\Models\Voucher;
use App\Support\ShippingFee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CheckoutVoucherTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_apply_valid_voucher_during_checkout(): void
    {
        $user = $this->customer();
        [$product, $productSize] = $this->sellableProduct();
        $voucher = Voucher::factory()->create([
            'code' => 'CHILL10',
            'type' => Voucher::TYPE_PERCENT,
            'value' => 10,
            'max_discount' => 20000,
            'min_order' => 50000,
            'usage_limit' => 100,
            'status' => true,
            'required_rank' => null,
            'point_cost' => 0,
            'is_redeemable' => false,
        ]);

        $shippingAddress = '123 Test Street';
        $shippingArea = 'Test Area';
        $shippingFee = ShippingFee::quoteForAddress($shippingAddress, $shippingArea, 'standard')['total_fee'];

        $response = $this
            ->actingAs($user)
            ->withSession([
                'cart' => [
                    'cart-1' => [
                        'product_id' => $product->id,
                        'product_size_id' => $productSize->id,
                        'name' => $product->name,
                        'price' => 100000,
                        'quantity' => 1,
                        'size' => 'M',
                        'ice_level' => 100,
                        'sugar_level' => 100,
                    ],
                ],
            ])
            ->post(route('checkout.process'), [
                'payment_method' => 'cod',
                'shipping_method_ui' => 'standard',
                'shipping_address_ui' => $shippingAddress,
                'shipping_area_ui' => $shippingArea,
                'voucher_code' => 'CHILL10',
                'note' => '',
            ]);

        $order = Order::latest()->first();

        $response->assertRedirect(route('home'));
        $this->assertNotNull($order);
        $this->assertSame($voucher->id, (int) $order->coupon_id);
        $this->assertSame(100000, (int) $order->subtotal);
        $this->assertSame(10000, (int) $order->discount);
        $this->assertSame(100000 + $shippingFee - 10000, (int) $order->total);
        $this->assertSame(1, (int) $voucher->fresh()->used_count);
        $this->assertSame(99, (int) $product->fresh()->stock);
        $this->assertDatabaseHas('user_coupon_usage', [
            'user_id' => $user->id,
            'coupon_id' => $voucher->id,
            'order_id' => $order->id,
            'discount_amount' => 10000,
        ]);
    }

    public function test_checkout_rejects_voucher_below_minimum_order(): void
    {
        $user = $this->customer();
        [$product, $productSize] = $this->sellableProduct();
        Voucher::factory()->create([
            'code' => 'MIN200',
            'type' => Voucher::TYPE_FIXED,
            'value' => 20000,
            'min_order' => 200000,
            'usage_limit' => 10,
            'status' => true,
        ]);

        $this
            ->actingAs($user)
            ->withSession([
                'cart' => [
                    'cart-1' => [
                        'product_id' => $product->id,
                        'product_size_id' => $productSize->id,
                        'name' => $product->name,
                        'price' => 100000,
                        'quantity' => 1,
                        'size' => 'M',
                    ],
                ],
            ])
            ->from(route('checkout.index'))
            ->post(route('checkout.process'), [
                'payment_method' => 'cod',
                'shipping_method_ui' => 'standard',
                'shipping_address_ui' => '123 Test Street',
                'shipping_area_ui' => 'Test Area',
                'voucher_code' => 'MIN200',
            ])
            ->assertRedirect(route('checkout.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 0);
    }

    private function customer(): User
    {
        return User::create([
            'name' => 'Customer Test',
            'email' => 'customer-'.uniqid().'@example.com',
            'password' => Hash::make('password'),
            'role_id' => 1,
            'is_active' => 1,
        ]);
    }

    private function sellableProduct(): array
    {
        $category = Category::create([
            'name' => 'Trà sữa',
            'slug' => 'tra-sua',
            'status' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Trà sữa test',
            'slug' => 'tra-sua-test',
            'price' => 100000,
            'stock' => 100,
            'status' => true,
        ]);

        $size = Size::create([
            'name' => 'M',
            'multiplier' => 1,
        ]);

        $productSize = ProductSize::create([
            'product_id' => $product->id,
            'size_id' => $size->id,
            'price' => 100000,
        ]);

        return [$product, $productSize];
    }
}
