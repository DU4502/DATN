<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\Size;
use App\Models\Topping;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ToppingManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_delete_topping_used_in_order_history(): void
    {
        $admin = $this->admin();
        $topping = Topping::create([
            'name' => 'Trân châu lịch sử',
            'price' => 5000,
            'status' => true,
        ]);

        $category = Category::create([
            'name' => 'Đồ uống',
            'slug' => 'do-uong',
            'status' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Trà sữa lịch sử',
            'slug' => 'tra-sua-lich-su',
            'price' => 30000,
            'stock' => 10,
            'status' => true,
        ]);
        $size = Size::create(['name' => 'M', 'multiplier' => 1]);
        $productSize = ProductSize::create([
            'product_id' => $product->id,
            'size_id' => $size->id,
            'price' => 30000,
        ]);
        $order = Order::create([
            'order_code' => 'TEST-TOPPING-001',
            'user_id' => User::factory()->create()->id,
            'subtotal' => 30000,
            'shipping_fee' => 0,
            'discount' => 0,
            'total' => 30000,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);
        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_size_id' => $productSize->id,
            'quantity' => 1,
            'unit_price' => 30000,
            'total_price' => 30000,
        ]);

        DB::table('order_item_toppings')->insert([
            'order_item_id' => $orderItem->id,
            'topping_id' => $topping->id,
            'price' => 5000,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.toppings.destroy', $topping))
            ->assertRedirect(route('admin.toppings.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('toppings', ['id' => $topping->id]);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role_id' => 2,
            'is_active' => true,
        ]);
    }
}
