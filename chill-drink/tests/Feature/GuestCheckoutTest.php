<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchProductStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\Size;
use App\Models\User;
use App\Services\EmailRecipientVerificationService;
use App\Services\FirebasePhoneAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class GuestCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(EmailRecipientVerificationService::class, new class extends EmailRecipientVerificationService
        {
            public function assertDeliverable(string $email): void
            {
                // Default test double: skip live SMTP probing.
            }
        });
    }

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

    public function test_guest_can_choose_scheduled_pickup_and_is_forced_to_vnpay(): void
    {
        $this->travelTo('2026-08-30 09:00:00');
        [$product, $productSize] = $this->sellableProduct();
        $branch = Branch::query()->firstOrCreate(
            ['code' => 'GUEST-SCHEDULED'],
            ['name' => 'Chi nhánh giao sau', 'address' => 'Thanh Hóa', 'status' => 1]
        );
        $cart = [
            'scheduled-guest-item' => [
                'product_id' => $product->id,
                'product_size_id' => $productSize->id,
                'name' => $product->name,
                'price' => 100000,
                'quantity' => 1,
                'size' => 'M',
            ],
        ];

        $this->withSession(['cart' => $cart])
            ->post(route('checkout.guest.info.store'), [
                'guest_name' => 'Khách đặt giao sau',
                'guest_email' => 'scheduled-guest@example.com',
                'verification_method' => 'email',
                'fulfillment_type' => 'pickup',
                'delivery_type' => 'scheduled',
                'scheduled_delivery_time' => '2026-08-30 10:00:00',
                'branch_id' => $branch->id,
            ])
            ->assertRedirect(route('checkout.guest.payment'));

        $this->get(route('checkout.guest.payment'))
            ->assertOk()
            ->assertSee('Đơn đặt giao sau cần thanh toán trước')
            ->assertSee('Không áp dụng cho đặt giao sau');
        $this->travelBack();
    }

    public function test_guest_can_checkout_with_verified_phone_without_email(): void
    {
        Mail::fake();
        $this->app->instance(FirebasePhoneAuthService::class, new class extends FirebasePhoneAuthService
        {
            public function verifyPhoneTokenMatches(string $idToken, string $expectedPhone): array
            {
                return [
                    'firebase_user' => (object) ['phone_number' => '+84912345678'],
                    'international' => '+84912345678',
                    'local' => '0912345678',
                ];
            }
        });

        [$product, $productSize] = $this->sellableProduct();
        $branchId = Branch::query()->where('code', 'HN')->value('id');

        $this->withSession([
            'cart' => [
                'phone-cart' => [
                    'product_id' => $product->id,
                    'product_size_id' => $productSize->id,
                    'name' => $product->name,
                    'price' => 100000,
                    'quantity' => 1,
                    'size' => 'M',
                ],
            ],
            'checkout_cart_keys' => ['phone-cart'],
        ])->post(route('checkout.guest.info.store'), [
            'guest_name' => 'Khách Phone',
            'verification_method' => 'phone',
            'guest_phone' => '0912345678',
            'firebase_id_token' => 'verified-firebase-token',
            'delivery_type' => 'pickup',
            'branch_id' => $branchId,
        ])->assertRedirect(route('checkout.guest.payment'));

        $response = $this->post(route('checkout.guest.process'), ['payment_method' => 'cod']);
        $order = Order::query()->latest('id')->firstOrFail();

        $this->assertSame('0912345678', $order->guest_phone);
        $this->assertNull($order->guest_email);
        $this->assertSame('pending', $order->status);
        $this->assertNull($order->confirmation_token);
        $response->assertRedirect(route('checkout.success', $order));
        Mail::assertNothingSent();
    }

    public function test_guest_can_checkout_with_email_without_phone(): void
    {
        Mail::fake();
        [$product, $productSize] = $this->sellableProduct();
        $branchId = Branch::query()->where('code', 'HN')->value('id');

        $this->withSession([
            'cart' => [
                'email-cart' => [
                    'product_id' => $product->id,
                    'product_size_id' => $productSize->id,
                    'name' => $product->name,
                    'price' => 100000,
                    'quantity' => 1,
                    'size' => 'M',
                ],
            ],
            'checkout_cart_keys' => ['email-cart'],
        ])->post(route('checkout.guest.info.store'), [
            'guest_name' => 'Khách Email',
            'verification_method' => 'email',
            'guest_email' => 'email-only@example.com',
            'delivery_type' => 'pickup',
            'branch_id' => $branchId,
        ])->assertRedirect(route('checkout.guest.payment'));

        $response = $this->post(route('checkout.guest.process'), ['payment_method' => 'cod']);
        $order = Order::query()->latest('id')->firstOrFail();

        $this->assertNull($order->guest_phone);
        $this->assertSame('email-only@example.com', $order->guest_email);
        $this->assertSame('awaiting_email_confirmation', $order->status);
        $response->assertRedirect(route('checkout.guest.pending-confirmation', $order));
    }

    public function test_guest_must_choose_and_complete_one_verification_method(): void
    {
        $branchId = Branch::query()->firstOrCreate(
            ['code' => 'VERIFY-REQUIRED'],
            ['name' => 'Chi nhánh Verify', 'address' => 'Hà Nội', 'status' => 1]
        )->id;

        $this->post(route('checkout.guest.info.store'), [
            'guest_name' => 'Khách Thiếu Liên Hệ',
            'verification_method' => 'email',
            'delivery_type' => 'pickup',
            'branch_id' => $branchId,
        ])->assertSessionHasErrors('guest_email');

        $this->post(route('checkout.guest.info.store'), [
            'guest_name' => 'Khách Thiếu OTP',
            'verification_method' => 'phone',
            'guest_phone' => '0912345678',
            'delivery_type' => 'pickup',
            'branch_id' => $branchId,
        ])->assertSessionHasErrors('firebase_id_token');
    }

    public function test_guest_checkout_rolls_back_when_confirmation_email_cannot_be_sent(): void
    {
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
                'guest_name' => 'Khách Vãng Lai',
                'guest_phone' => '0912345678',
                'guest_email' => 'guest@example.com',
                'delivery_type' => 'pickup',
                'branch_id' => $branchId,
                'note' => 'Ít đá',
            ]);

        Mail::shouldReceive('to')
            ->once()
            ->with('guest@example.com')
            ->andReturnSelf();
        Mail::shouldReceive('send')
            ->once()
            ->andThrow(new \RuntimeException('SMTP failed'));

        $response = $this->post(route('checkout.guest.process'), [
            'payment_method' => 'cod',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', fn (string $message) => str_contains($message, 'Không gửi được email xác nhận'));
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_guest_checkout_rejects_nonexistent_email_before_creating_order(): void
    {
        [$product, $productSize] = $this->sellableProduct();
        $branchId = Branch::query()->firstOrCreate(
            ['code' => 'HN'],
            ['name' => 'Chi nhánh Hà Nội', 'address' => 'Hà Nội', 'status' => 1]
        )->id;

        $this->app->instance(EmailRecipientVerificationService::class, new class extends EmailRecipientVerificationService
        {
            public function assertDeliverable(string $email): void
            {
                throw new \RuntimeException('Địa chỉ email này không tồn tại hoặc không nhận thư. Vui lòng kiểm tra lại.');
            }
        });

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
                'guest_email' => 'phuongthao.lhn12bvsgsg@gmail.com',
                'delivery_type' => 'pickup',
                'branch_id' => $branchId,
                'note' => 'Ít đá',
            ])
            ->assertSessionHasErrors('guest_email');

        $this->assertDatabaseCount('orders', 0);
        $this->assertGuest();
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
        $this->fakeRoutingDistance(1000);

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
        $this->fakeRoutingDistance(20000);

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
                'shipping_address_ui' => 'Hà Nội',
                'shipping_area_ui' => 'Hà Nội',
                'latitude' => 21.0278,
                'longitude' => 105.8342,
                'note' => 'Giao tại sảnh chính',
            ])
            ->assertSessionHasErrors('branch_id');
    }

    private function fakeRoutingDistance(int $distanceMeters): void
    {
        Http::preventStrayRequests();
        Http::fake([
            '*/route/v1/*' => Http::response([
                'code' => 'Ok',
                'routes' => [[
                    'distance' => $distanceMeters,
                    'duration' => 180,
                    'geometry' => ['coordinates' => [[106.7009, 10.7769], [105.8342, 21.0278]]],
                    'legs' => [],
                ]],
            ]),
        ]);
    }

    private function sellableProduct(): array
    {
        Branch::query()->firstOrCreate(
            ['code' => 'HN'],
            ['name' => 'Chi nhánh Hà Nội', 'address' => 'Hà Nội', 'status' => 1]
        );

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
            'status' => true,
        ]);
        Branch::query()->where('status', true)->pluck('id')->each(function ($branchId) use ($product) {
            BranchProductStatus::create([
                'branch_id' => $branchId,
                'product_id' => $product->id,
                'is_available' => true,
            ]);
        });

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
