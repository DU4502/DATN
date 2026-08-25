<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\DeliveryBundleTrip;
use App\Models\Order;
use App\Models\Shipper;
use App\Models\ShipperCodReceivable;
use App\Models\ShipperCodSettlement;
use App\Models\User;
use App\Services\ShipperCodService;
use App\Support\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class ShipperCodSettlementTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivered_cod_order_records_the_canonical_order_total_once(): void
    {
        $branch = $this->createBranch('COD-A');
        $shipper = $this->createShipper($branch, 'COD-SHIPPER-A');
        $order = $this->createOrder($branch, $shipper, 'COD-ORDER-A', [
            'subtotal' => 120000,
            'shipping_fee' => 25000,
            'discount' => 15000,
            'total' => 130000,
            'payment_status' => 'pending',
        ]);
        $service = app(ShipperCodService::class);

        $first = $service->recordCollection($order, $shipper);
        $second = $service->recordCollection($order->fresh(), $shipper);

        $this->assertNotNull($first);
        $this->assertTrue($first->is($second));
        $this->assertSame('130000.00', $first->amount);
        $this->assertSame($branch->id, $first->order_branch_id);
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertDatabaseCount('shipper_cod_receivables', 1);
        $this->assertTrue($order->fresh()->codReceivable->is($first));
        $this->assertTrue($first->shipper->is($shipper));
        $this->assertTrue($first->order->is($order));
    }

    public function test_non_cod_wrong_shipper_staff_and_invalid_status_cannot_collect_cod(): void
    {
        $branch = $this->createBranch('COD-B');
        $assigned = $this->createShipper($branch, 'COD-ASSIGNED');
        $other = $this->createShipper($branch, 'COD-OTHER');
        $staffShipper = $this->createShipper($branch, 'COD-STAFF', User::STAFF_ROLE_ID);
        $service = app(ShipperCodService::class);

        $nonCod = $this->createOrder($branch, $assigned, 'NON-COD', ['payment_method' => 'vnpay']);
        $wrongShipper = $this->createOrder($branch, $assigned, 'WRONG-SHIPPER');
        $staffOrder = $this->createOrder($branch, $staffShipper, 'STAFF-COD');
        $cancelled = $this->createOrder($branch, $assigned, 'CANCELLED-COD', ['status' => OrderStatus::CANCELLED]);
        $notDelivered = $this->createOrder($branch, $assigned, 'DELIVERING-COD', ['status' => OrderStatus::DELIVERING]);

        $this->assertNull($service->recordCollection($nonCod, $assigned));
        $this->assertNull($service->recordCollection($wrongShipper, $other));
        $this->assertNull($service->recordCollection($staffOrder, $staffShipper));
        $this->assertNull($service->recordCollection($cancelled, $assigned));
        $this->assertNull($service->recordCollection($notDelivered, $assigned));
        $this->assertDatabaseCount('shipper_cod_receivables', 0);
    }

    public function test_admin_settles_all_pending_records_once_at_the_shippers_home_branch(): void
    {
        $homeBranch = $this->createBranch('COD-HOME');
        $orderBranch = $this->createBranch('COD-ORDER-BRANCH');
        $shipper = $this->createShipper($homeBranch, 'COD-SETTLE');
        $admin = User::factory()->create([
            'role_id' => 2,
            'branch_id' => $homeBranch->id,
        ]);
        $firstOrder = $this->createOrder($homeBranch, $shipper, 'COD-SETTLE-1', ['total' => 90000]);
        $secondOrder = $this->createOrder($orderBranch, $shipper, 'COD-SETTLE-2', ['total' => 65000]);
        $service = app(ShipperCodService::class);

        $firstReceivable = $service->recordCollection($firstOrder, $shipper);
        $secondReceivable = $service->recordCollection($secondOrder, $shipper);
        $settlement = $service->settleAll($shipper, $homeBranch->id, $admin, 'Đã nhận đủ tiền');

        $this->assertSame('155000.00', $settlement->amount);
        $this->assertSame(2, $settlement->order_count);
        $this->assertTrue($settlement->shipper->is($shipper));
        $this->assertTrue($settlement->branch->is($homeBranch));
        $this->assertTrue($settlement->confirmer->is($admin));
        $this->assertCount(2, $settlement->receivables);
        $this->assertSame($orderBranch->id, $secondReceivable->fresh()->order_branch_id);
        $this->assertSame($settlement->id, $firstReceivable->fresh()->settlement_id);
        $this->assertSame($settlement->id, $secondReceivable->fresh()->settlement_id);

        $this->expectException(RuntimeException::class);
        $service->settleAll($shipper, $homeBranch->id, $admin);
    }

    public function test_staff_and_an_admin_from_another_branch_cannot_confirm_settlement(): void
    {
        $homeBranch = $this->createBranch('COD-AUTH-HOME');
        $otherBranch = $this->createBranch('COD-AUTH-OTHER');
        $shipper = $this->createShipper($homeBranch, 'COD-AUTH-SHIPPER');
        $order = $this->createOrder($homeBranch, $shipper, 'COD-AUTH-ORDER');
        $staff = User::factory()->create([
            'role_id' => User::STAFF_ROLE_ID,
            'branch_id' => $homeBranch->id,
        ]);
        $otherAdmin = User::factory()->create([
            'role_id' => 2,
            'branch_id' => $otherBranch->id,
        ]);
        $service = app(ShipperCodService::class);
        $service->recordCollection($order, $shipper);

        foreach ([$staff, $otherAdmin] as $actor) {
            try {
                $service->settleAll($shipper, $homeBranch->id, $actor);
                $this->fail('Unauthorized actor must not confirm COD settlement.');
            } catch (RuntimeException) {
                $this->assertDatabaseCount('shipper_cod_settlements', 0);
            }
        }

        $this->assertNull(ShipperCodReceivable::firstOrFail()->settlement_id);
    }

    public function test_bundle_orders_are_collected_per_order_without_duplicates(): void
    {
        $branch = $this->createBranch('COD-BUNDLE');
        $shipper = $this->createShipper($branch, 'COD-BUNDLE-SHIPPER');
        $first = $this->createOrder($branch, $shipper, 'COD-BUNDLE-1', ['total' => 40000]);
        $second = $this->createOrder($branch, $shipper, 'COD-BUNDLE-2', ['total' => 50000]);
        $trip = DeliveryBundleTrip::create([
            'shipper_id' => $shipper->id,
            'status' => 'active',
            'total_cups' => 2,
            'plan_json' => ['order_ids' => [$first->id, $second->id]],
        ]);
        $trip->orders()->attach($first->id, ['role' => 'primary']);
        $trip->orders()->attach($second->id, ['role' => 'merged']);
        $service = app(ShipperCodService::class);

        $service->recordCollection($first, $shipper);
        $service->recordCollection($second, $shipper);
        $service->recordCollection($first->fresh(), $shipper);
        $service->recordCollection($second->fresh(), $shipper);

        $this->assertDatabaseCount('shipper_cod_receivables', 2);
        $this->assertSame(90000, $service->pendingAmount($shipper));
        $this->assertEqualsCanonicalizing(
            [$first->id, $second->id],
            ShipperCodReceivable::pluck('order_id')->all()
        );
    }

    public function test_cod_records_and_settlements_roll_back_with_the_transaction(): void
    {
        $branch = $this->createBranch('COD-ROLLBACK');
        $shipper = $this->createShipper($branch, 'COD-ROLLBACK-SHIPPER');
        $order = $this->createOrder($branch, $shipper, 'COD-ROLLBACK-ORDER');

        try {
            DB::transaction(function () use ($order, $shipper, $branch) {
                $receivable = ShipperCodReceivable::create([
                    'order_id' => $order->id,
                    'order_code' => $order->order_code,
                    'shipper_id' => $shipper->id,
                    'order_branch_id' => $branch->id,
                    'amount' => $order->total,
                    'collected_at' => now(),
                ]);
                $settlement = ShipperCodSettlement::create([
                    'shipper_id' => $shipper->id,
                    'branch_id' => $branch->id,
                    'amount' => $order->total,
                    'order_count' => 1,
                    'confirmed_at' => now(),
                ]);
                $receivable->update(['settlement_id' => $settlement->id, 'settled_at' => now()]);

                throw new RuntimeException('Rollback COD smoke data');
            });
        } catch (RuntimeException) {
        }

        $this->assertDatabaseCount('shipper_cod_receivables', 0);
        $this->assertDatabaseCount('shipper_cod_settlements', 0);
    }

    private function createBranch(string $code): Branch
    {
        return Branch::create([
            'name' => $code,
            'code' => $code,
            'status' => true,
        ]);
    }

    private function createShipper(Branch $branch, string $code, int $roleId = User::SHIPPER_ROLE_ID): Shipper
    {
        $user = User::factory()->create([
            'role_id' => $roleId,
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        return Shipper::create([
            'user_id' => $user->id,
            'code' => $code,
            'phone' => '0900000000',
            'status' => 'busy',
        ]);
    }

    private function createOrder(Branch $branch, Shipper $shipper, string $code, array $overrides = []): Order
    {
        $customer = User::factory()->create(['role_id' => 1, 'branch_id' => null]);

        return Order::create(array_merge([
            'order_code' => $code,
            'user_id' => $customer->id,
            'shipper_id' => $shipper->id,
            'branch_id' => $branch->id,
            'fulfillment_type' => 'delivery',
            'subtotal' => 100000,
            'shipping_fee' => 20000,
            'discount' => 10000,
            'total' => 110000,
            'payment_method' => 'cod',
            'payment_status' => 'paid',
            'status' => OrderStatus::DELIVERED,
            'delivered_at' => now(),
        ], $overrides));
    }
}
