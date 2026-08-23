<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Services\OrderTimeoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OrderTimeoutServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_orders_auto_cancel_after_30_minutes_not_2_hours(): void
    {
        Notification::fake();

        $customer = User::factory()->create();
        $expiredOrder = $this->createPendingOrder($customer, 31, 'TIMEOUT-OLD');
        $freshOrder = $this->createPendingOrder($customer, 29, 'TIMEOUT-NEW');

        $result = app(OrderTimeoutService::class)->cancelExpired();

        $this->assertSame(1, $result['pending_cancelled']);
        $this->assertSame(1, $result['total']);

        $this->assertDatabaseHas('orders', [
            'id' => $expiredOrder->id,
            'status' => 'cancelled',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $freshOrder->id,
            'status' => 'pending',
        ]);
    }

    private function createPendingOrder(User $customer, int $minutesAgo, string $code): Order
    {
        $now = now()->subMinutes($minutesAgo);

        $id = DB::table('orders')->insertGetId([
            'order_code' => $code,
            'user_id' => $customer->id,
            'subtotal' => 50000,
            'shipping_fee' => 0,
            'discount' => 0,
            'total' => 50000,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'status' => 'pending',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return Order::query()->findOrFail($id);
    }
}
