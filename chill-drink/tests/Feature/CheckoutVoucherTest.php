<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Category;
use App\Models\BranchProductStatus;
use App\Models\LoyaltyPoint;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\Size;
use App\Models\User;
use App\Models\UserVoucher;
use App\Models\Voucher;
use App\Support\ShippingFee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckoutVoucherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

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
    }

    public function test_checkout_page_only_offers_cod_and_vnpay(): void
    {
        $user = $this->customer();
        [$product, $productSize] = $this->sellableProduct();

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
            ->get(route('checkout.index'))
            ->assertOk()
            ->assertSee('Thanh toán khi nhận hàng')
            ->assertSee('VNPay')
            ->assertDontSee('Chuyển khoản ngân hàng')
            ->assertDontSee('Ví Momo');
    }

    public function test_checkout_summary_shows_three_items_before_expanding(): void
    {
        $user = $this->customer();
        [$product, $productSize] = $this->sellableProduct();
        $cart = [];

        foreach (range(1, 4) as $index) {
            $cart['cart-'.$index] = [
                'product_id' => $product->id,
                'product_size_id' => $productSize->id,
                'name' => $product->name.' '.$index,
                'price' => 100000,
                'quantity' => 1,
                'size' => 'M',
            ];
        }

        $response = $this
            ->actingAs($user)
            ->withSession(['cart' => $cart])
            ->get(route('checkout.index'));

        $response->assertOk();
        $response->assertSee('Xem thêm 1 món');
        $response->assertSee('data-checkout-summary-toggle', false);
        $this->assertSame(4, substr_count($response->getContent(), 'data-checkout-item="'));
        $this->assertSame(1, substr_count($response->getContent(), 'data-checkout-extra-item'));
    }

    public function test_checkout_only_renders_the_explicitly_selected_cart_items(): void
    {
        $user = $this->customer();
        [$product, $productSize] = $this->sellableProduct();
        $cartItem = [
            'product_id' => $product->id,
            'product_size_id' => $productSize->id,
            'name' => $product->name,
            'price' => 100000,
            'quantity' => 1,
            'size' => 'M',
        ];

        $response = $this
            ->actingAs($user)
            ->withSession(['cart' => [
                'selected-item' => $cartItem,
                'unselected-item' => $cartItem,
            ]])
            ->get(route('checkout.index', ['items' => ['selected-item']]));

        $response->assertOk();
        $this->assertSame(1, substr_count($response->getContent(), 'data-checkout-item="'));
        $response->assertSessionHas('checkout_cart_keys', ['selected-item']);
    }

    public function test_checkout_redirects_when_every_requested_cart_item_is_stale(): void
    {
        $user = $this->customer();
        [$product, $productSize] = $this->sellableProduct();

        $response = $this
            ->actingAs($user)
            ->withSession(['cart' => [
                'current-item' => [
                    'product_id' => $product->id,
                    'product_size_id' => $productSize->id,
                    'name' => $product->name,
                    'price' => 100000,
                    'quantity' => 1,
                    'size' => 'M',
                ],
            ]])
            ->get(route('checkout.index', ['items' => ['stale-item']]));

        $response->assertRedirect(route('cart.index'));
        $response->assertSessionHas('error', 'Sản phẩm bạn chọn thanh toán không còn trong giỏ hàng hoặc đã thay đổi, vui lòng chọn lại.');
        $response->assertSessionMissing('checkout_cart_keys');
    }

    public function test_checkout_redirects_when_only_part_of_the_requested_selection_still_exists(): void
    {
        $user = $this->customer();
        [$product, $productSize] = $this->sellableProduct();

        $response = $this
            ->actingAs($user)
            ->withSession(['cart' => [
                'current-item' => [
                    'product_id' => $product->id,
                    'product_size_id' => $productSize->id,
                    'name' => $product->name,
                    'price' => 100000,
                    'quantity' => 1,
                    'size' => 'M',
                ],
            ]])
            ->get(route('checkout.index', ['items' => ['current-item', 'stale-item']]));

        $response->assertRedirect(route('cart.index'));
        $response->assertSessionHas('error', 'Sản phẩm bạn chọn thanh toán không còn trong giỏ hàng hoặc đã thay đổi, vui lòng chọn lại.');
        $response->assertSessionMissing('checkout_cart_keys');
    }

    public function test_checkout_process_rejects_a_stale_session_selection_without_creating_an_order(): void
    {
        $user = $this->customer();
        [$product, $productSize] = $this->sellableProduct();
        $branch = $this->activeBranch();

        $response = $this
            ->actingAs($user)
            ->withSession([
                'cart' => [
                    'current-item' => [
                        'product_id' => $product->id,
                        'product_size_id' => $productSize->id,
                        'name' => $product->name,
                        'price' => 100000,
                        'quantity' => 1,
                        'size' => 'M',
                    ],
                ],
                'checkout_cart_keys' => ['stale-item'],
            ])
            ->post(route('checkout.process'), [
                'payment_method' => 'cod',
                'shipping_method_ui' => 'standard',
                'shipping_phone_ui' => '0901234567',
                'fulfillment_type' => 'pickup',
                'branch_id' => $branch->id,
            ]);

        $response->assertRedirect(route('cart.index'));
        $response->assertSessionHas('error', 'Sản phẩm bạn chọn thanh toán không còn trong giỏ hàng hoặc đã thay đổi, vui lòng chọn lại.');
        $response->assertSessionMissing('checkout_cart_keys');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_rejects_removed_payment_methods(): void
    {
        $this
            ->actingAs($this->customer())
            ->from(route('checkout.index'))
            ->post(route('checkout.process'), [
                'payment_method' => 'momo',
                'shipping_method_ui' => 'standard',
                'shipping_phone_ui' => '0901234567',
                'shipping_address_ui' => '123 Test Street',
            ])
            ->assertRedirect(route('checkout.index'))
            ->assertSessionHasErrors('payment_method');
    }

    public function test_authenticated_pickup_checkout_does_not_require_coordinates(): void
    {
        $user = $this->customer();
        [$product, $productSize] = $this->sellableProduct();
        $branch = $this->activeBranch();

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
                    ],
                ],
            ])
            ->post(route('checkout.process'), [
                'payment_method' => 'cod',
                'shipping_method_ui' => 'standard',
                'shipping_phone_ui' => '0901234567',
                'fulfillment_type' => 'pickup',
                'branch_id' => $branch->id,
            ]);

        $order = Order::latest()->firstOrFail();

        $response->assertRedirect(route('checkout.success', $order));
        $this->assertSame('pickup', $order->fulfillment_type);
        $this->assertSame(0, (int) $order->shipping_fee);
        $this->assertNull($order->shipping_latitude);
        $this->assertNull($order->shipping_longitude);
        Http::assertNothingSent();
    }

    public function test_checkout_requires_a_real_phone_instead_of_using_a_placeholder(): void
    {
        $user = $this->customer();
        $user->update(['phone' => null]);
        [$product, $productSize] = $this->sellableProduct();
        $branch = $this->activeBranch();

        $this->actingAs($user)
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
                'fulfillment_type' => 'pickup',
                'branch_id' => $branch->id,
            ])
            ->assertRedirect(route('checkout.index'))
            ->assertSessionHasErrors('shipping_phone_ui');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_redirects_vnpay_order_to_payment_gateway_route(): void
    {
        $user = $this->customer();
        [$product, $productSize] = $this->sellableProduct();

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
                    ],
                ],
            ])
            ->post(route('checkout.process'), [
                'payment_method' => 'vnpay',
                'shipping_method_ui' => 'standard',
                'shipping_phone_ui' => '0901234567',
                'shipping_address_ui' => '123 Test Street',
                'shipping_area_ui' => 'Test Area',
                'shipping_phone_ui' => '0912345678',
                'fulfillment_type' => 'delivery',
                'branch_id' => $this->activeBranch()->id,
                'latitude' => 10.7769,
                'longitude' => 106.7009,
                'address_location_confirmed' => '1',
            ]);

        $order = Order::latest()->first();

        $this->assertNotNull($order);
        $this->assertSame('vnpay', $order->payment_method);
        $response->assertRedirect(route('vnpay.payment', $order));
    }

    public function test_checkout_page_renders_database_vouchers_not_demo_vouchers(): void
    {
        $user = $this->customer();
        [$product, $productSize] = $this->sellableProduct();
        Voucher::factory()->create([
            'code' => 'PAGE10',
            'type' => Voucher::TYPE_PERCENT,
            'value' => 10,
            'max_discount' => 20000,
            'min_order' => 50000,
            'usage_limit' => 100,
            'status' => true,
        ]);
        Voucher::factory()->create([
            'code' => 'MIN200',
            'type' => Voucher::TYPE_FIXED,
            'value' => 20000,
            'min_order' => 200000,
            'usage_limit' => 10,
            'status' => true,
        ]);

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
                    ],
                ],
            ])
            ->get(route('checkout.index'));

        $response->assertOk();
        $response->assertSee('name="voucher_code"', false);
        $response->assertDontSee('voucher_code_ui');
        $response->assertSee('PAGE10');
        $response->assertSee('MIN200');
        $response->assertDontSee('Voucher này đang là giao diện demo');
        $response->assertDontSee('SHIP15');
    }

    public function test_issue_support_voucher_is_only_visible_to_its_owner(): void
    {
        $owner = $this->customer();
        $otherCustomer = $this->customer();
        [$product, $productSize] = $this->sellableProduct();
        $voucher = Voucher::factory()->create([
            'code' => 'HT000123-OWNER',
            'type' => Voucher::TYPE_FIXED,
            'value' => 95000,
            'min_order' => 0,
            'usage_limit' => 1,
            'used_count' => 0,
            'status' => true,
            'starts_at' => now()->subMinute(),
            'expires_at' => now()->addDays(30),
        ]);
        UserVoucher::create([
            'user_id' => $owner->id,
            'coupon_id' => $voucher->id,
            'code' => $voucher->code,
            'is_used' => false,
            'expires_at' => $voucher->expires_at,
            'redeemed_at' => now(),
        ]);
        $cart = [
            'cart-1' => [
                'product_id' => $product->id,
                'product_size_id' => $productSize->id,
                'name' => $product->name,
                'price' => 100000,
                'quantity' => 1,
                'size' => 'M',
            ],
        ];

        $this->actingAs($owner)
            ->withSession(['cart' => $cart])
            ->get(route('checkout.index'))
            ->assertOk()
            ->assertSee($voucher->code)
            ->assertSee('HỖ TRỢ ĐƠN HÀNG');

        $this->actingAs($otherCustomer)
            ->withSession(['cart' => $cart])
            ->get(route('checkout.index'))
            ->assertOk()
            ->assertDontSee($voucher->code);

        $this->actingAs($otherCustomer)
            ->withSession(['cart' => $cart])
            ->from(route('checkout.index'))
            ->post(route('checkout.process'), [
                'payment_method' => 'cod',
                'shipping_method_ui' => 'standard',
                'shipping_phone_ui' => '0901234567',
                'fulfillment_type' => 'pickup',
                'branch_id' => $this->activeBranch()->id,
                'voucher_code' => $voucher->code,
            ])
            ->assertRedirect(route('checkout.index'))
            ->assertSessionHas('error', 'Voucher hỗ trợ này không thuộc tài khoản của bạn.');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_customer_can_apply_valid_voucher_during_checkout(): void
    {
        $user = $this->customer();
        [$product, $productSize] = $this->sellableProduct();
        $voucher = Voucher::factory()->create([
            'code' => 'TESTCHILL10',
            'type' => Voucher::TYPE_PERCENT,
            'value' => 10,
            'max_discount' => 20000,
            'min_order' => 50000,
            'usage_limit' => 100,
            'status' => true,
            'point_cost' => 0,
            'is_redeemable' => false,
        ]);

        $shippingAddress = 'ngõ 910 Quang Trung';
        $shippingArea = 'Test Area';
        $shippingFee = ShippingFee::calculate(0, 'standard')['total_fee'];

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
                'shipping_phone_ui' => '0901234567',
                'shipping_address_ui' => $shippingAddress,
                'shipping_area_ui' => $shippingArea,
                'shipping_phone_ui' => '0912345678',
                'fulfillment_type' => 'delivery',
                'branch_id' => $this->activeBranch()->id,
                'latitude' => 10.7769,
                'longitude' => 106.7009,
                'voucher_code' => 'TESTCHILL10',
                'address_location_confirmed' => '1',
                'note' => '',
            ]);

        $order = Order::latest()->first();

        $this->assertNotNull($order);
        $response->assertRedirect(route('checkout.success', $order));
        $this->assertSame($voucher->id, (int) $order->coupon_id);
        $this->assertSame(100000, (int) $order->subtotal);
        $this->assertSame(10000, (int) $order->discount);
        $this->assertSame(100000 + $shippingFee - 10000, (int) $order->total);
        $this->assertSame(1, (int) $voucher->fresh()->used_count);
        $this->assertDatabaseHas('user_coupon_usage', [
            'user_id' => $user->id,
            'coupon_id' => $voucher->id,
            'order_id' => $order->id,
            'discount_amount' => 10000,
        ]);
    }

    public function test_customer_can_validate_a_manual_voucher_before_selecting_it(): void
    {
        $user = $this->customer();
        [$product, $productSize] = $this->sellableProduct();
        Voucher::factory()->create([
            'code' => 'MANUAL10',
            'type' => Voucher::TYPE_PERCENT,
            'value' => 10,
            'max_discount' => 20000,
            'min_order' => 50000,
            'usage_limit' => 100,
            'status' => true,
            'point_cost' => 0,
            'is_redeemable' => false,
        ]);
        $cart = [
            'manual-voucher-item' => [
                'product_id' => $product->id,
                'product_size_id' => $productSize->id,
                'name' => $product->name,
                'price' => 100000,
                'quantity' => 1,
                'size' => 'M',
            ],
        ];

        $this->actingAs($user)
            ->withSession(['cart' => $cart])
            ->postJson(route('checkout.voucher.validate'), [
                'code' => 'manual10',
                'fulfillment_type' => 'delivery',
                'shipping_fee' => 20000,
            ])
            ->assertOk()
            ->assertJsonPath('valid', true)
            ->assertJsonPath('voucher.code', 'MANUAL10')
            ->assertJsonPath('voucher.type', 'discount')
            ->assertJsonPath('voucher.discount', 10000);

        $this->postJson(route('checkout.voucher.validate'), [
            'code' => 'NHAP-BUA-123',
            'fulfillment_type' => 'delivery',
            'shipping_fee' => 20000,
        ])->assertUnprocessable()->assertJsonPath('valid', false);

        $this->postJson(route('checkout.voucher.validate'), [
            'code' => 'MA KHONG HOP LE!',
        ])->assertUnprocessable()->assertJsonValidationErrors('code');
    }

    public function test_manual_voucher_validation_is_rate_limited(): void
    {
        $user = $this->customer();
        [$product, $productSize] = $this->sellableProduct();
        $cart = [
            'rate-limit-voucher-item' => [
                'product_id' => $product->id,
                'product_size_id' => $productSize->id,
                'name' => $product->name,
                'price' => 100000,
                'quantity' => 1,
                'size' => 'M',
            ],
        ];

        $this->actingAs($user)->withSession(['cart' => $cart]);
        foreach (range(1, 6) as $attempt) {
            $this->postJson(route('checkout.voucher.validate'), [
                'code' => 'RANDOM'.$attempt,
            ])->assertUnprocessable();
        }

        $this->postJson(route('checkout.voucher.validate'), [
            'code' => 'RANDOM7',
        ])->assertTooManyRequests();
    }

    public function test_redeemed_voucher_does_not_deduct_points_again_at_checkout(): void
    {
        $user = $this->customer();
        [$product, $productSize] = $this->sellableProduct();
        $voucher = Voucher::factory()->create([
            'code' => 'REWARD15',
            'type' => Voucher::TYPE_PERCENT,
            'value' => 15,
            'max_discount' => 25000,
            'min_order' => 50000,
            'point_cost' => 50,
            'is_redeemable' => true,
        ]);
        LoyaltyPoint::create([
            'user_id' => $user->id,
            'total_points' => 100,
            'monthly_points' => 100,
            'lifetime_points' => 100,
            'current_month' => now()->format('Y-m'),
        ]);

        $this->actingAs($user)
            ->from(route('loyalty.index'))
            ->post(route('loyalty.redeem-voucher', $voucher))
            ->assertRedirect(route('loyalty.index'));

        $this->assertSame(50, (int) $user->loyaltyPoint()->firstOrFail()->total_points);
        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $user->id,
            'points' => -50,
            'reference_type' => 'voucher',
            'reference_id' => $voucher->id,
        ]);

        $cart = [
            'cart-reward' => [
                'product_id' => $product->id,
                'product_size_id' => $productSize->id,
                'name' => $product->name,
                'price' => 100000,
                'quantity' => 1,
                'size' => 'M',
            ],
        ];

        $this
            ->actingAs($user)
            ->withSession(['cart' => $cart])
            ->get(route('checkout.index'))
            ->assertOk()
            ->assertSee('id="receivedVouchersSection"', false)
            ->assertSee('data-voucher-code="REWARD15"', false)
            ->assertDontSee('loadReceivedVouchers', false);

        $response = $this
            ->actingAs($user)
            ->withSession(['cart' => $cart])
            ->post(route('checkout.process'), [
                'payment_method' => 'cod',
                'shipping_method_ui' => 'standard',
                'shipping_phone_ui' => '0901234567',
                'fulfillment_type' => 'pickup',
                'branch_id' => $this->activeBranch()->id,
                'voucher_code' => $voucher->code,
            ]);

        $order = Order::latest()->firstOrFail();

        $response->assertRedirect(route('checkout.success', $order));
        $this->assertSame(50, (int) $user->loyaltyPoint()->firstOrFail()->total_points);
        $this->assertDatabaseCount('point_transactions', 1);
        $this->assertDatabaseHas('user_vouchers', [
            'user_id' => $user->id,
            'coupon_id' => $voucher->id,
            'is_used' => true,
        ]);
    }

    public function test_redeemable_voucher_must_be_exchanged_before_checkout(): void
    {
        $user = $this->customer();
        [$product, $productSize] = $this->sellableProduct();
        $voucher = Voucher::factory()->create([
            'code' => 'LOCKEDREWARD',
            'min_order' => 0,
            'point_cost' => 50,
            'is_redeemable' => true,
        ]);
        LoyaltyPoint::create([
            'user_id' => $user->id,
            'total_points' => 100,
            'monthly_points' => 100,
            'lifetime_points' => 100,
            'current_month' => now()->format('Y-m'),
        ]);

        $this
            ->actingAs($user)
            ->withSession([
                'cart' => [
                    'cart-reward' => [
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
                'shipping_phone_ui' => '0901234567',
                'fulfillment_type' => 'pickup',
                'branch_id' => $this->activeBranch()->id,
                'voucher_code' => $voucher->code,
            ])
            ->assertRedirect(route('checkout.index'))
            ->assertSessionHas('error', 'Bạn cần đổi điểm lấy voucher này trước khi sử dụng.');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(100, (int) $user->loyaltyPoint()->firstOrFail()->total_points);
    }

    public function test_checkout_rejects_voucher_below_minimum_order(): void
    {
        $user = $this->customer();
        [$product, $productSize] = $this->sellableProduct();
        $ordersBefore = Order::count();
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
                'shipping_phone_ui' => '0901234567',
                'shipping_address_ui' => '123 Test Street',
                'shipping_area_ui' => 'Test Area',
                'shipping_phone_ui' => '0912345678',
                'fulfillment_type' => 'delivery',
                'branch_id' => $this->activeBranch()->id,
                'latitude' => 10.7769,
                'longitude' => 106.7009,
                'voucher_code' => 'MIN200',
                'address_location_confirmed' => '1',
            ])
            ->assertRedirect(route('checkout.index'))
            ->assertSessionHas('error');

        $this->assertSame($ordersBefore, Order::count());
    }

    public function test_checkout_requires_specific_shipping_address_for_delivery_radius(): void
    {
        $user = $this->customer();
        [$product, $productSize] = $this->sellableProduct();
        $ordersBefore = Order::count();

        $this
            ->actingAs($user)
            ->withSession([
                'cart' => [
                    "{$product->id}:M:100:100" => [
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
                'shipping_phone_ui' => '0901234567',
                'shipping_address_ui' => '',
                'shipping_area_ui' => 'Hà Nội',
                'shipping_phone_ui' => '0912345678',
                'fulfillment_type' => 'delivery',
                'branch_id' => $this->activeBranch()->id,
                'latitude' => 10.7769,
                'longitude' => 106.7009,
                'voucher_code' => '',
            ])
            ->assertSessionHasErrors('shipping_address_ui');

        $this->assertSame($ordersBefore, Order::count());
    }

    public function test_checkout_uses_current_database_price_instead_of_session_price(): void
    {
        $user = $this->customer();
        [$product, $productSize] = $this->sellableProduct();
        $voucher = Voucher::factory()->create([
            'code' => 'DBPRICE',
            'type' => Voucher::TYPE_PERCENT,
            'value' => 10,
            'max_discount' => 50000,
            'min_order' => 50000,
            'usage_limit' => 100,
            'status' => true,
        ]);

        $shippingAddress = '123 Test Street';
        $shippingArea = 'Test Area';
        $shippingFee = ShippingFee::calculate(0, 'standard')['total_fee'];

        $response = $this
            ->actingAs($user)
            ->withSession([
                'cart' => [
                    'cart-1' => [
                        'product_id' => $product->id,
                        'product_size_id' => $productSize->id,
                        'name' => $product->name,
                        'price' => 1000,
                        'quantity' => 1,
                        'size' => 'M',
                    ],
                ],
            ])
            ->post(route('checkout.process'), [
                'payment_method' => 'cod',
                'shipping_method_ui' => 'standard',
                'shipping_phone_ui' => '0901234567',
                'shipping_address_ui' => $shippingAddress,
                'shipping_area_ui' => $shippingArea,
                'shipping_phone_ui' => '0912345678',
                'fulfillment_type' => 'delivery',
                'branch_id' => $this->activeBranch()->id,
                'latitude' => 10.7769,
                'longitude' => 106.7009,
                'voucher_code' => 'DBPRICE',
                'address_location_confirmed' => '1',
            ]);

        $order = Order::latest()->first();

        $this->assertNotNull($order);
        $response->assertRedirect(route('checkout.success', $order));
        $this->assertSame($voucher->id, (int) $order->coupon_id);
        $this->assertSame(100000, (int) $order->subtotal);
        $this->assertSame(10000, (int) $order->discount);
        $this->assertSame(100000 + $shippingFee - 10000, (int) $order->total);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'unit_price' => 100000,
            'total_price' => 100000,
        ]);
    }

    private function customer(): User
    {
        return User::create([
            'name' => 'Customer Test',
            'email' => 'customer-'.uniqid().'@example.com',
            'password' => Hash::make('password'),
            'role_id' => 1,
            'is_active' => 1,
            'email_verified_at' => now(),
        ]);
    }

    private function activeBranch(): Branch
    {
        return Branch::query()->firstOrCreate(
            ['code' => 'TEST-BRANCH'],
            [
                'name' => 'Chi nhánh kiểm thử',
                'address' => 'Quận 1',
                'latitude' => 10.7769,
                'longitude' => 106.7009,
                'status' => true,
            ]
        );
    }

    private function sellableProduct(): array
    {
        $categoryName = 'Trà sữa '.uniqid();
        $categorySlug = 'tra-sua-'.uniqid();
        $productSlug = 'tra-sua-test-'.uniqid();

        $category = Category::create([
            'name' => $categoryName,
            'slug' => $categorySlug,
            'status' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Trà sữa test',
            'slug' => $productSlug,
            'price' => 100000,
            'status' => true,
        ]);
        $branch = $this->activeBranch();
        BranchProductStatus::query()->updateOrCreate(
            ['branch_id' => $branch->id, 'product_id' => $product->id],
            ['is_available' => true]
        );

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
