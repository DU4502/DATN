<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_review_order_code_and_toggle_visibility(): void
    {
        $admin = User::factory()->create(['role_id' => 2]);
        $customer = User::factory()->create(['role_id' => 1]);
        [$product, $order] = $this->productAndOrderFor($customer);
        $review = Review::create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'order_id' => $order->id,
            'rating' => 4,
            'comment' => 'Ổn',
            'status' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.reviews.index'))
            ->assertOk()
            ->assertSee('Mã đơn')
            ->assertSee($order->displayCode())
            ->assertSee('Ẩn')
            ->assertSee('Đang hiển thị');

        $this->actingAs($admin)
            ->from(route('admin.reviews.index'))
            ->patch(route('admin.reviews.toggle-status', $review))
            ->assertRedirect();

        $this->assertFalse((bool) $review->fresh()->status);

        $this->actingAs($admin)
            ->from(route('admin.reviews.index'))
            ->patch(route('admin.reviews.toggle-status', $review))
            ->assertRedirect();

        $this->assertTrue((bool) $review->fresh()->status);
    }

    private function productAndOrderFor(User $customer): array
    {
        $category = Category::create([
            'name' => 'Danh mục đánh giá '.uniqid(),
            'slug' => 'danh-muc-danh-gia-'.uniqid(),
            'status' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Sản phẩm đánh giá '.uniqid(),
            'slug' => 'san-pham-danh-gia-'.uniqid(),
            'price' => 42000,
            'status' => true,
        ]);

        $order = Order::create([
            'order_code' => 'REV-'.strtoupper(uniqid()),
            'user_id' => $customer->id,
            'subtotal' => 42000,
            'shipping_fee' => 0,
            'discount' => 0,
            'total' => 42000,
            'payment_method' => 'cod',
            'payment_status' => 'paid',
            'status' => 'completed',
        ]);

        return [$product, $order];
    }
}
