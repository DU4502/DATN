<?php

namespace Tests\Feature\Api;

use App\Jobs\ProcessGuestOrderEmail;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\Size;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GuestCheckoutApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_api_checkout_uses_a_valid_pending_payment_status(): void
    {
        Queue::fake();

        $branch = Branch::create([
            'name' => 'API Test Branch',
            'code' => 'API-TEST',
            'address' => 'Test address',
            'latitude' => 21.0285,
            'longitude' => 105.8542,
            'status' => true,
        ]);
        $category = Category::create([
            'name' => 'API Test Category',
            'slug' => 'api-test-category',
            'status' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'API Test Product',
            'slug' => 'api-test-product',
            'price' => 65000,
            'stock' => 10,
            'status' => true,
        ]);
        $size = Size::create(['name' => 'M', 'multiplier' => 1]);
        ProductSize::create([
            'product_id' => $product->id,
            'size_id' => $size->id,
            'price' => 65000,
        ]);

        $response = $this->postJson(route('api.guest.checkout'), [
            'guest_name' => 'API Guest',
            'guest_email' => 'api-guest@example.com',
            'guest_phone' => '0900000000',
            'branch_id' => $branch->id,
            'latitude' => 21.0285,
            'longitude' => 105.8542,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ]);

        $response->assertCreated();
        $orderId = $response->json('data.order_id');

        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'payment_status' => 'pending',
            'total' => 130000,
        ]);
        Queue::assertPushed(ProcessGuestOrderEmail::class);
    }
}
