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
use Tests\TestCase;

class CheckoutBranchSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_stores_the_selected_active_branch(): void
    {
        $user = User::create([
            'name' => 'Khách hàng kiểm thử',
            'email' => 'branch-checkout@example.com',
            'password' => Hash::make('password'),
            'role_id' => 1,
            'is_active' => true,
        ]);

        $branch = Branch::create([
            'name' => 'Chi nhánh thực tế',
            'code' => 'REAL-01',
            'address' => 'Quận 1, Thành phố Hồ Chí Minh',
            'latitude' => 10.7769,
            'longitude' => 106.7009,
            'status' => true,
        ]);

        $category = Category::create([
            'name' => 'Trà kiểm thử',
            'slug' => 'tra-kiem-thu',
            'status' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Trà kiểm thử',
            'slug' => 'tra-kiem-thu',
            'price' => 50000,
            'stock' => 10,
            'status' => true,
        ]);

        $size = Size::create(['name' => 'M', 'multiplier' => 1]);
        $productSize = ProductSize::create([
            'product_id' => $product->id,
            'size_id' => $size->id,
            'price' => 50000,
        ]);

        $cart = [
            'cart-1' => [
                'product_id' => $product->id,
                'product_size_id' => $productSize->id,
                'name' => $product->name,
                'price' => 50000,
                'quantity' => 1,
                'size' => 'M',
            ],
        ];

        $this
            ->actingAs($user)
            ->withSession(['cart' => $cart])
            ->get(route('checkout.index'))
            ->assertOk()
            ->assertSee('Chi nhánh phục vụ gần bạn')
            ->assertSee('Tìm chi nhánh gần nhất')
            ->assertSee('api\/branches\/nearest', false);

        $response = $this
            ->actingAs($user)
            ->withSession(['cart' => $cart])
            ->post(route('checkout.process'), [
                'payment_method' => 'cod',
                'shipping_method_ui' => 'standard',
                'shipping_address_ui' => '123 Nguyễn Huệ',
                'shipping_area_ui' => 'Quận 1, Thành phố Hồ Chí Minh',
                'branch_id' => $branch->id,
            ]);

        $order = Order::latest('id')->firstOrFail();

        $response->assertRedirect(route('checkout.success', $order));
        $this->assertSame($branch->id, (int) $order->branch_id);
    }

    public function test_checkout_rejects_an_inactive_branch(): void
    {
        $user = User::create([
            'name' => 'Khách hàng kiểm thử',
            'email' => 'inactive-branch@example.com',
            'password' => Hash::make('password'),
            'role_id' => 1,
            'is_active' => true,
        ]);

        $branch = Branch::create([
            'name' => 'Chi nhánh đã đóng',
            'code' => 'CLOSED-01',
            'status' => false,
        ]);

        $this
            ->actingAs($user)
            ->from(route('checkout.index'))
            ->post(route('checkout.process'), [
                'payment_method' => 'cod',
                'shipping_method_ui' => 'standard',
                'shipping_address_ui' => '123 Nguyễn Huệ',
                'branch_id' => $branch->id,
            ])
            ->assertRedirect(route('checkout.index'))
            ->assertSessionHasErrors('branch_id');
    }
}
