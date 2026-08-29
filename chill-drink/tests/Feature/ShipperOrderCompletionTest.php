<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShipmentHistory;
use App\Models\Shipper;
use App\Models\User;
use App\Services\SuperAdminAnalyticsService;
use App\Support\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ShipperOrderCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_shipper_delivery_completes_cod_order_and_recognizes_revenue_immediately(): void
    {
        $branch = Branch::query()->create([
            'name' => 'Chi nhánh hoàn tất COD',
            'code' => 'COD-COMPLETE',
            'address' => 'Quận 1, TP.HCM',
            'status' => true,
        ]);
        $shipperUser = User::factory()->create([
            'role_id' => User::SHIPPER_ROLE_ID,
            'branch_id' => $branch->id,
        ]);
        $customer = User::factory()->create(['role_id' => 1]);
        $shipper = Shipper::query()->create([
            'user_id' => $shipperUser->id,
            'code' => 'SHP-COD-COMPLETE',
            'phone' => '0900000000',
            'vehicle_type' => 'motorbike',
            'status' => 'busy',
            'station_branch_id' => $branch->id,
        ]);
        $order = Order::query()->create([
            'order_code' => 'ORDER-COD-COMPLETE',
            'user_id' => $customer->id,
            'shipper_id' => $shipper->id,
            'branch_id' => $branch->id,
            'fulfillment_type' => 'delivery',
            'shipping_address_text' => '1 Nguyễn Huệ, Quận 1',
            'subtotal' => 120000,
            'shipping_fee' => 15000,
            'discount' => 0,
            'total' => 135000,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'status' => OrderStatus::DELIVERING,
        ]);
        $shipment = Shipment::query()->create([
            'order_id' => $order->id,
            'shipper_id' => $shipper->id,
            'status' => 'delivering',
            'assigned_at' => now()->subMinutes(20),
            'picked_up_at' => now()->subMinutes(10),
        ]);
        ShipmentHistory::query()->create([
            'shipment_id' => $shipment->id,
            'status' => 'arrived_customer',
            'description' => 'GPS đã xác nhận shipper tới điểm giao.',
        ]);
        ShipmentHistory::query()->create([
            'shipment_id' => $shipment->id,
            'status' => 'arrived_customer_confirmed',
            'description' => 'Shipper đã xác nhận đến nơi.',
        ]);

        $this->actingAs($shipperUser)
            ->post(route('shipper.orders.complete', $order))
            ->assertRedirect();

        $completedOrder = $order->fresh();
        $this->assertSame(OrderStatus::COMPLETED, $completedOrder->status);
        $this->assertSame('paid', $completedOrder->payment_status);
        $this->assertNotNull($completedOrder->delivered_at);
        $this->assertSame($shipperUser->id, (int) $completedOrder->status_changed_by);
        $this->assertSame('delivered', $shipment->fresh()->status);
        $this->assertSame(
            135000,
            app(SuperAdminAnalyticsService::class)
                ->revenueSummary(app(SuperAdminAnalyticsService::class)->validSalesOrdersQuery())
        );
        $this->assertDatabaseHas('migrations', [
            'migration' => '2026_08_29_000000_remove_shipper_cod_settlement',
        ]);
        $this->assertFalse(Schema::hasTable('shipper_cod_receivables'));
        $this->assertFalse(Schema::hasTable('shipper_cod_settlements'));
        $this->assertFalse(Route::has('admin.cod-settlements.index'));
        $this->assertFalse(Route::has('admin.super-admin.manage.cod-settlements.index'));
    }
}
