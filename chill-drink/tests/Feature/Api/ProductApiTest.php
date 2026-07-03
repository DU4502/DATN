<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_categories_endpoint_returns_active_categories_only(): void
    {
        $visibleName = 'Trà sữa '.uniqid();
        $hiddenName = 'Ẩn '.uniqid();
        $visibleSlug = 'tra-sua-'.uniqid();
        $hiddenSlug = 'an-'.uniqid();

        Category::create([
            'name' => $visibleName,
            'slug' => $visibleSlug,
            'status' => true,
        ]);

        Category::create([
            'name' => $hiddenName,
            'slug' => $hiddenSlug,
            'status' => false,
        ]);

        $response = $this->getJson('/api/categories');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', $visibleName);
    }

    public function test_products_endpoint_supports_filtering_and_returns_review_stats(): void
    {
        $categoryName = 'Trà sữa '.uniqid();
        $categorySlug = 'tra-sua-'.uniqid();
        $productName = 'Trà sữa ô long '.uniqid();
        $productSlug = 'tra-sua-o-long-'.uniqid();

        $category = Category::create([
            'name' => $categoryName,
            'slug' => $categorySlug,
            'status' => true,
        ]);

        $visibleProduct = Product::create([
            'category_id' => $category->id,
            'name' => $productName,
            'slug' => $productSlug,
            'price' => 49000,
            'stock' => 30,
            'status' => true,
        ]);

        Product::create([
            'category_id' => $category->id,
            'name' => 'Sản phẩm ẩn',
            'slug' => 'san-pham-an',
            'price' => 10000,
            'stock' => 10,
            'status' => false,
        ]);

        $customer = $this->customer();
        $order = Order::create([
            'user_id' => $customer->id,
            'subtotal' => 49000,
            'shipping_fee' => 0,
            'discount' => 0,
            'total' => 49000,
            'payment_method' => 'cod',
            'payment_status' => 'paid',
            'status' => 'completed',
            'note' => 'api test',
        ]);

        Review::create([
            'user_id' => $customer->id,
            'product_id' => $visibleProduct->id,
            'order_id' => $order->id,
            'rating' => 4,
            'comment' => 'Ổn',
            'status' => true,
        ]);

        $response = $this->getJson("/api/products?category_id={$category->id}&search=ô long");

        $response->assertOk()
            ->assertJsonPath('data.0.slug', $productSlug)
            ->assertJsonPath('data.0.review_count', 1)
            ->assertJsonPath('data.0.average_rating', 4);
    }

    public function test_product_detail_endpoint_returns_reviews(): void
    {
        $category = Category::create([
            'name' => 'Cà phê',
            'slug' => 'ca-phe',
            'status' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Cold Brew',
            'slug' => 'cold-brew',
            'price' => 55000,
            'stock' => 20,
            'status' => true,
        ]);

        $customer = $this->customer();
        $order = Order::create([
            'user_id' => $customer->id,
            'subtotal' => 55000,
            'shipping_fee' => 0,
            'discount' => 0,
            'total' => 55000,
            'payment_method' => 'cod',
            'payment_status' => 'paid',
            'status' => 'completed',
            'note' => 'api detail test',
        ]);

        Review::create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'order_id' => $order->id,
            'rating' => 5,
            'comment' => 'Rất ngon',
            'status' => true,
        ]);

        $response = $this->getJson('/api/products/cold-brew');

        $response->assertOk()
            ->assertJsonPath('data.slug', 'cold-brew')
            ->assertJsonPath('data.review_count', 1)
            ->assertJsonPath('data.reviews.0.comment', 'Rất ngon')
            ->assertJsonPath('data.reviews.0.user_name', 'API Customer');
    }

    private function customer(): User
    {
        return User::create([
            'name' => 'API Customer',
            'email' => 'api-customer-'.uniqid().'@example.com',
            'password' => Hash::make('password'),
            'role_id' => 1,
            'is_active' => 1,
        ]);
    }
}
