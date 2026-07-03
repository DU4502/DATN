<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\Review;
use App\Models\Size;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProductReviewTest extends TestCase
{
    use DatabaseTransactions;

    public function test_customer_with_completed_order_can_create_review(): void
    {
        $user = $this->customer();
        [$product, $productSize] = $this->sellableProduct();
        $order = $this->completedOrderFor($user, $product, $productSize);

        $response = $this->actingAs($user)->post(route('products.reviews.store', $product), [
            'rating' => 5,
            'comment' => 'Đồ uống ngon và giao đúng lúc.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'order_id' => $order->id,
            'rating' => 5,
            'comment' => 'Đồ uống ngon và giao đúng lúc.',
            'status' => 1,
        ]);
    }

    public function test_customer_without_completed_order_cannot_create_review(): void
    {
        $user = $this->customer();
        [$product, $productSize] = $this->sellableProduct();
        $this->orderFor($user, $product, $productSize, 'pending');

        $response = $this
            ->actingAs($user)
            ->from(route('products.show', $product))
            ->post(route('products.reviews.store', $product), [
                'rating' => 4,
                'comment' => 'Muốn đánh giá nhưng đơn chưa hoàn tất.',
            ]);

        $response->assertRedirect(route('products.show', $product));
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_customer_can_review_again_after_a_new_completed_purchase(): void
    {
        $user = $this->customer();
        [$product, $productSize] = $this->sellableProduct();
        $firstOrder = $this->completedOrderFor($user, $product, $productSize);
        $secondOrder = $this->completedOrderFor($user, $product, $productSize);

        Review::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'order_id' => $firstOrder->id,
            'rating' => 3,
            'comment' => 'Lần mua trước',
            'status' => true,
        ]);

        $response = $this->actingAs($user)->post(route('products.reviews.store', $product), [
            'rating' => 5,
            'comment' => 'Lần mua sau tốt hơn.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('reviews', 2);
        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'order_id' => $secondOrder->id,
            'rating' => 5,
            'comment' => 'Lần mua sau tốt hơn.',
        ]);
    }

    public function test_customer_cannot_review_the_same_purchase_twice(): void
    {
        $user = $this->customer();
        [$product, $productSize] = $this->sellableProduct();
        $order = $this->completedOrderFor($user, $product, $productSize);

        Review::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'order_id' => $order->id,
            'rating' => 4,
            'comment' => 'Đã review rồi',
            'status' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->from(route('products.show', $product))
            ->post(route('products.reviews.store', $product), [
                'rating' => 5,
                'comment' => 'Muốn đánh giá lại đơn cũ',
            ]);

        $response->assertRedirect(route('products.show', $product));
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('reviews', 1);
        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'order_id' => $order->id,
            'rating' => 4,
            'comment' => 'Đã review rồi',
        ]);
    }

    private function customer(): User
    {
        return User::create([
            'name' => 'Customer Review',
            'email' => 'review-'.uniqid().'@example.com',
            'password' => Hash::make('password'),
            'role_id' => 1,
            'is_active' => 1,
        ]);
    }

    private function sellableProduct(): array
    {
        $categoryName = 'Trà sữa '.uniqid();
        $categorySlug = 'tra-sua-'.uniqid();
        $productSlug = 'tra-sua-review-test-'.uniqid();

        $category = Category::create([
            'name' => $categoryName,
            'slug' => $categorySlug,
            'status' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Trà sữa review test',
            'slug' => $productSlug,
            'price' => 45000,
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
            'price' => 45000,
        ]);

        return [$product, $productSize];
    }

    private function completedOrderFor(User $user, Product $product, ProductSize $productSize): Order
    {
        return $this->orderFor($user, $product, $productSize, 'completed');
    }

    private function orderFor(User $user, Product $product, ProductSize $productSize, string $status): Order
    {
        $order = Order::create([
            'user_id' => $user->id,
            'subtotal' => 45000,
            'shipping_fee' => 15000,
            'discount' => 0,
            'total' => 60000,
            'payment_method' => 'cod',
            'payment_status' => 'paid',
            'status' => $status,
            'note' => 'Order test',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_size_id' => $productSize->id,
            'ice_level' => 100,
            'sugar_level' => 100,
            'quantity' => 1,
            'unit_price' => 45000,
            'total_price' => 45000,
        ]);

        return $order;
    }
}
