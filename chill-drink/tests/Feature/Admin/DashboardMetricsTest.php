<?php

namespace Tests\Feature\Admin;

use App\Models\Branch;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_order_count_matches_completed_revenue_orders(): void
    {
        $branch = Branch::create([
            'name' => 'Chi nhánh Dashboard Test',
            'code' => 'DASH-TEST',
            'address' => 'Quận 1',
            'status' => true,
        ]);
        $admin = User::factory()->create([
            'role_id' => 2,
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $customer = User::factory()->create(['role_id' => 1]);

        $this->makeOrder($customer, $branch, 'DASH-COMPLETED', 'completed');
        $this->makeOrder($customer, $branch, 'DASH-CANCELLED', 'cancelled');

        $this->actingAs($admin)
            ->getJson('/admin/dashboard/data?period=week')
            ->assertOk()
            ->assertJsonPath('totalOrders', 1);
    }

    private function makeOrder(User $customer, Branch $branch, string $code, string $status): Order
    {
        return Order::create([
            'order_code' => $code,
            'user_id' => $customer->id,
            'branch_id' => $branch->id,
            'subtotal' => 30000,
            'shipping_fee' => 0,
            'discount' => 0,
            'total' => 30000,
            'payment_method' => 'cod',
            'payment_status' => $status === 'completed' ? 'paid' : 'pending',
            'status' => $status,
        ]);
    }
}
