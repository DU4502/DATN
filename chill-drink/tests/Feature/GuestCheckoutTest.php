<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\Size;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class GuestCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_complete_cod_checkout(): void
    {
        Mail::fake();

        [$product, $productSize] = $this->sellableProduct();
        Branch::query()->firstOrCreate(
            ['code' => 'HN'],
            ['name' => 'Chi nhánh Hà Nội', 'address' => 'Hà Nội', 'status' => 1]
        );

        $this->withSession([
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
            'checkout_cart_keys' => ['cart-1'],
        ])
            ->post(route('checkout.guest.info.store'), [
                'guest_name' => 'Khách Vãng Lai',
                'guest_phone' => '0912345678',
                'guest_email' => 'guest@example.com',
                'delivery_type' => 'pickup',
                'branch_id' => Branch::query()->where('code', 'HN')->value('id'),
                'note' => 'Ít đá',
            ])
            ->assertRedirect(route('checkout.guest.payment'));

        $response = $this
            ->post(route('checkout.guest.process'), [
                'payment_method' => 'cod',
            ]);

        $order = Order::query()->latest('id')->first();

        $this->assertNotNull($order);
        $this->assertNull($order->user_id);
        $this->assertSame('guest@example.com', $order->guest_email);

        $response->assertRedirect(route('checkout.guest.pending-confirmation', $order));

        $this->withSession(["guest_order_tokens.{$order->id}" => $order->guest_token])
            ->get(route('checkout.guest.pending-confirmation', $order))
            ->assertOk()
            ->assertSee('Chúng tôi đã gửi email xác nhận đến:')
            ->assertSee('guest@example.com');
    }

    public function test_guest_checkout_rejects_invalid_vietnamese_phone_number(): void
    {
        [$product, $productSize] = $this->sellableProduct();
        Branch::query()->firstOrCreate(
            ['code' => 'HN'],
            ['name' => 'Chi nhánh Hà Nội', 'address' => 'Hà Nội', 'status' => 1]
        );

        $this->withSession([
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
            'checkout_cart_keys' => ['cart-1'],
        ])
            ->post(route('checkout.guest.info.store'), [
                'guest_name' => 'Khách Vãng Lai',
                'guest_phone' => '034753534555555555',
                'guest_email' => 'guest@example.com',
                'delivery_type' => 'pickup',
                'branch_id' => Branch::query()->where('code', 'HN')->value('id'),
                'note' => 'Ít đá',
            ])
            ->assertSessionHasErrors('guest_phone');
    }

    public function test_guest_can_convert_to_member_after_checkout(): void
    {
        Mail::fake();

        [$product, $productSize] = $this->sellableProduct();
        $branchId = Branch::query()->updateOrCreate(
            ['code' => 'HN'],
            [
                'name' => 'Chi nhánh Hà Nội',
                'address' => 'Hà Nội',
                'latitude' => 21.0278,
                'longitude' => 105.8342,
                'status' => 1,
            ]
        )->id;

        $this->withSession([
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
            'checkout_cart_keys' => ['cart-1'],
        ])
            ->post(route('checkout.guest.info.store'), [
                'guest_name' => 'Nguyễn Văn A',
                'guest_phone' => '0987654321',
                'guest_email' => 'newmember@example.com',
                'delivery_type' => 'pickup',
                'branch_id' => $branchId,
            ]);

        $this->post(route('checkout.guest.process'), ['payment_method' => 'cod']);

        $order = Order::query()->latest('id')->first();
        $token = $order->guest_token;

        $this->withSession(["guest_order_tokens.{$order->id}" => $token])
            ->post(route('register.guest-convert'), [
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('orders.index'));

        $order->refresh();
        $this->assertNotNull($order->user_id);
        $this->assertAuthenticatedAs(User::query()->where('email', 'newmember@example.com')->first());
    }

    public function test_cart_page_shows_guest_checkout_gate_for_guests(): void
    {
        [$product, $productSize] = $this->sellableProduct();

        $this->withSession([
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
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Mua hàng nhanh — không cần tài khoản')
            ->assertSee('data-guest-checkout-url');
    }

    public function test_guest_checkout_can_convert_demo_cart_items_into_payable_products(): void
    {
        Mail::fake();

        $branchId = Branch::query()->updateOrCreate(
            ['code' => 'HN'],
            [
                'name' => 'Chi nhánh Hà Nội',
                'address' => 'Hà Nội',
                'latitude' => 21.0278,
                'longitude' => 105.8342,
                'status' => 1,
            ]
        )->id;

        $this->withSession([
            'cart' => [
                'cart-1' => [
                    'product_id' => 'demo-tra-sua-tran-chau-demo',
                    'name' => 'Trà Sữa Trân Châu',
                    'price' => 62000,
                    'quantity' => 1,
                    'size' => 'M',
                ],
            ],
            'checkout_cart_keys' => ['cart-1'],
        ])
            ->post(route('checkout.guest.info.store'), [
                'guest_name' => 'Khách Demo',
                'guest_phone' => '0912345678',
                'guest_email' => 'demo-checkout@example.com',
                'delivery_type' => 'delivery',
                'branch_id' => $branchId,
                'shipping_address_ui' => '123 Nguyễn Văn Cừ',
                'shipping_area_ui' => 'Quận 5',
                'latitude' => 21.0278,
                'longitude' => 105.8342,
            ])
            ->assertRedirect(route('checkout.guest.payment'));

        $response = $this->post(route('checkout.guest.process'), [
            'payment_method' => 'cod',
        ]);

        $order = Order::query()->latest('id')->first();
        $createdProduct = Product::query()->where('name', 'Trà Sữa Trân Châu')->first();

        $this->assertNotNull($order);
        $this->assertNotNull($createdProduct);
        $this->assertSame($createdProduct->id, $order->orderItems()->first()->product_id);
        $response->assertRedirect(route('checkout.guest.pending-confirmation', $order));
    }

    public function test_guest_checkout_rejects_delivery_branch_outside_15_km(): void
    {
        [$product, $productSize] = $this->sellableProduct();
        $branchId = Branch::query()->create([
            'code' => 'HCM-FAR',
            'name' => 'Chi nhánh xa',
            'address' => 'Thành phố Hồ Chí Minh',
            'latitude' => 10.7769,
            'longitude' => 106.7009,
            'status' => 1,
        ])->id;

        $this->withSession([
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
            'checkout_cart_keys' => ['cart-1'],
        ])
            ->post(route('checkout.guest.info.store'), [
                'guest_name' => 'Khách Vãng Lai',
                'guest_phone' => '0912345678',
                'guest_email' => 'guest@example.com',
                'delivery_type' => 'delivery',
                'branch_id' => $branchId,
                'shipping_address_ui' => '123 Phố Huế, Hà Nội',
                'shipping_area_ui' => 'Hà Nội',
                'note' => 'Giao tại sảnh tòa nhà, vui lòng gọi trước.',
                'latitude' => 21.0278,
                'longitude' => 105.8342,
            ])
            ->assertSessionHasErrors('branch_id');
    }

    private function sellableProduct(): array
    {
        $categoryName = 'Trà sữa '.uniqid();
        $categorySlug = 'tra-sua-'.uniqid();
        $productSlug = 'tra-sua-guest-'.uniqid();

        $category = Category::create([
            'name' => $categoryName,
            'slug' => $categorySlug,
            'status' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Trà sữa test guest',
            'slug' => $productSlug,
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
