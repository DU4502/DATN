<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\Size;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class GuestCheckoutTest extends TestCase
{
    use DatabaseTransactions;

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

        $response->assertRedirect(route('checkout.success', $order));

        $this->get(route('checkout.success', $order))
            ->assertOk()
            ->assertSee('Một email xác nhận kèm chi tiết đơn hàng')
            ->assertSee('Tạo tài khoản với thông tin này');
    }

    public function test_guest_can_convert_to_member_after_checkout(): void
    {
        Mail::fake();

        [$product, $productSize] = $this->sellableProduct();
        $branchId = Branch::query()->firstOrCreate(
            ['code' => 'HN'],
            ['name' => 'Chi nhánh Hà Nội', 'address' => 'Hà Nội', 'status' => 1]
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

        Branch::query()->firstOrCreate(
            ['code' => 'HN'],
            ['name' => 'Chi nhánh Hà Nội', 'address' => 'Hà Nội', 'status' => 1]
        );

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
                'shipping_address_ui' => '123 Nguyễn Văn Cừ',
                'shipping_area_ui' => 'Quận 5',
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
        $response->assertRedirect(route('checkout.success', $order));
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
