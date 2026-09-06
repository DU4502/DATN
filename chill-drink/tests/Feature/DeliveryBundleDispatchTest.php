<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\DeliveryBundleTrip;
use App\Models\DeliveryDispatchDecision;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShipmentHistory;
use App\Models\Shipper;
use App\Models\User;
use App\Services\DeliveryRoutingService;
use App\Services\ShipperBundleService;
use App\Services\ShipperDispatchService;
use App\Services\ShipperDispatchScoringService;
use App\Support\OrderStatus;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DeliveryBundleDispatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatch_assigns_only_an_active_shipper_from_the_order_branch_and_logs_the_decision(): void
    {
        Notification::fake();

        $branch = $this->createBranch('DSP-A', 10.7769, 106.7009);
        $staff = User::factory()->create([
            'role_id' => User::STAFF_ROLE_ID,
            'branch_id' => $branch->id,
        ]);
        Shipper::create([
            'user_id' => $staff->id,
            'code' => 'INVALID-STAFF',
            'phone' => '0900000001',
            'status' => 'online',
        ]);

        $shipper = $this->createShipper($branch, 'VALID-SHIPPER', 'online');
        $order = $this->createOrder($branch, 'DSP-ORDER-1');

        $this->mockRouting();

        $result = app(ShipperDispatchService::class)->dispatchConfirmedOrder($order);

        $this->assertSame('assigned', $result['status']);
        $this->assertSame('available', $result['dispatch_mode']);
        $this->assertTrue($order->fresh()->shipper->is($shipper));
        $this->assertSame('busy', $shipper->fresh()->status);
        $this->assertDatabaseHas('shipments', [
            'order_id' => $order->id,
            'shipper_id' => $shipper->id,
            'status' => 'accepted',
        ]);
        $this->assertDatabaseHas('delivery_dispatch_decisions', [
            'order_id' => $order->id,
            'shipper_id' => $shipper->id,
            'mode' => 'available',
            'selected' => true,
        ]);
        $this->assertDatabaseMissing('delivery_dispatch_decisions', [
            'order_id' => $order->id,
            'shipper_id' => $staff->shipper?->id,
        ]);

        $decision = DeliveryDispatchDecision::with(['order', 'shipper'])->firstOrFail();
        $this->assertTrue($decision->order->is($order));
        $this->assertTrue($decision->shipper->is($shipper));
        $this->assertNotNull($decision->score);
        $this->assertIsArray($decision->features_json);
    }

    public function test_dispatch_skips_orders_before_the_store_marks_them_ready_for_delivery(): void
    {
        Notification::fake();

        $branch = $this->createBranch('DSP-NOT-READY', 10.7769, 106.7009);
        $shipper = $this->createShipper($branch, 'NOT-READY-SHIPPER', 'online');

        foreach ([OrderStatus::CONFIRMED, OrderStatus::PREPARING] as $status) {
            $order = $this->createOrder($branch, 'NOT-READY-'.$status);
            $order->forceFill(['status' => $status])->save();

            $result = app(ShipperDispatchService::class)->dispatchConfirmedOrder($order->fresh());

            $this->assertSame('skipped', $result['status']);
            $this->assertNull($order->fresh()->shipper_id);
        }

        $this->assertSame('online', $shipper->fresh()->status);
        $this->assertDatabaseCount('shipments', 0);
        $this->assertDatabaseCount('delivery_dispatch_decisions', 0);
    }

    public function test_scheduled_order_is_not_dispatched_too_early(): void
    {
        Notification::fake();
        $this->travelTo('2026-08-29 09:00:00');

        $branch = $this->createBranch('DSP-SCHEDULED', 10.7769, 106.7009);
        $shipper = $this->createShipper($branch, 'SCHEDULED-SHIPPER', 'online');
        $order = $this->createOrder($branch, 'DSP-SCHEDULED-1');
        $order->update([
            'delivery_type' => 'scheduled',
            'scheduled_delivery_time' => now()->addHours(2),
            'scheduled_at' => now()->addHours(2),
        ]);

        $result = app(ShipperDispatchService::class)->dispatchConfirmedOrder($order->fresh());

        $this->assertSame('deferred', $result['status']);
        $this->assertNull($order->fresh()->shipper_id);
        $this->assertSame('online', $shipper->fresh()->status);
        $this->assertDatabaseMissing('shipments', ['order_id' => $order->id]);

        $this->travelBack();
    }

    public function test_scheduled_order_becomes_dispatchable_thirty_minutes_before_delivery(): void
    {
        Notification::fake();
        $this->travelTo('2026-08-29 09:00:00');

        $branch = $this->createBranch('DSP-SCHEDULED-DUE', 10.7769, 106.7009);
        $shipper = $this->createShipper($branch, 'SCHEDULED-DUE-SHIPPER', 'online');
        $order = $this->createOrder($branch, 'DSP-SCHEDULED-DUE-1');
        $order->update([
            'delivery_type' => 'scheduled',
            'fulfillment_type' => 'delivery',
            'scheduled_delivery_time' => now()->addMinutes(30),
            'scheduled_at' => now()->addMinutes(30),
        ]);
        $this->mockRouting();

        $result = app(ShipperDispatchService::class)->dispatchConfirmedOrder($order->fresh());

        $this->assertSame('assigned', $result['status']);
        $this->assertSame($shipper->id, $order->fresh()->shipper_id);
        $this->travelBack();
    }

    public function test_staff_and_cross_branch_shipper_are_not_dispatch_candidates(): void
    {
        $orderBranch = $this->createBranch('DSP-B', 10.7769, 106.7009);
        $otherBranch = $this->createBranch('DSP-C', 10.7869, 106.7109);

        $staff = User::factory()->create([
            'role_id' => User::STAFF_ROLE_ID,
            'branch_id' => $orderBranch->id,
        ]);
        Shipper::create([
            'user_id' => $staff->id,
            'code' => 'STAFF-AS-SHIPPER',
            'phone' => '0900000002',
            'status' => 'online',
        ]);
        $this->createShipper($otherBranch, 'CROSS-BRANCH', 'online');
        $order = $this->createOrder($orderBranch, 'DSP-ORDER-2');

        $result = app(ShipperDispatchService::class)->dispatchConfirmedOrder($order);

        $this->assertSame('waiting', $result['status']);
        $this->assertNull($order->fresh()->shipper_id);
        $this->assertDatabaseCount('shipments', 0);
        $this->assertDatabaseCount('delivery_dispatch_decisions', 0);
    }

    public function test_dispatch_rechecks_shipper_role_after_locking_a_stale_candidate(): void
    {
        Notification::fake();

        $branch = $this->createBranch('DSP-RACE', 10.7769, 106.7009);
        $shipper = $this->createShipper($branch, 'SHIP-RACE', 'online');
        $order = $this->createOrder($branch, 'ORDER-RACE');
        $this->mockRouting();

        $this->mock(ShipperDispatchScoringService::class, function ($mock) use ($shipper) {
            $mock->shouldReceive('beginBatch')->andReturn('race-batch');
            $mock->shouldReceive('context')->andReturn([]);
            $mock->shouldReceive('rankAvailable')->andReturnUsing(function ($order, $candidates) use ($shipper) {
                $shipper->user->forceFill(['role_id' => User::STAFF_ROLE_ID])->save();

                return collect([[
                    'shipper' => $candidates->first(),
                    'score' => 100.0,
                    'pickup_eta_s' => 60.0,
                ]]);
            });
            $mock->shouldReceive('rankReturning')->andReturn(collect());
            $mock->shouldReceive('logRankedRows');
        });

        $result = app(ShipperDispatchService::class)->dispatchConfirmedOrder($order);

        $this->assertSame('waiting', $result['status']);
        $this->assertNull($order->fresh()->shipper_id);
        $this->assertDatabaseMissing('shipments', ['order_id' => $order->id]);
    }

    public function test_offline_shipper_can_go_online_and_receive_a_waiting_order_from_their_branch(): void
    {
        Notification::fake();

        $branch = $this->createBranch('DSP-ONLINE', 10.7769, 106.7009);
        $shipper = $this->createShipper($branch, 'OFFLINE-SHIPPER', 'offline');
        $order = $this->createOrder($branch, 'WAITING-FOR-ONLINE');

        $this->mockRouting();

        $this->actingAs($shipper->user)
            ->get(route('shipper.dashboard'))
            ->assertOk()
            ->assertSee('Bắt đầu nhận đơn');

        $this->actingAs($shipper->user)
            ->post(route('shipper.status.update'), ['status' => 'online'])
            ->assertRedirect();

        $this->assertSame($shipper->id, $order->fresh()->shipper_id);
        $this->assertSame('busy', $shipper->fresh()->status);
        $this->assertDatabaseHas('shipments', [
            'order_id' => $order->id,
            'shipper_id' => $shipper->id,
            'status' => 'accepted',
        ]);
    }

    public function test_stale_online_shipper_does_not_receive_new_dispatch(): void
    {
        Notification::fake();

        $branch = $this->createBranch('DSP-STALE', 10.7769, 106.7009);
        $shipper = $this->createShipper($branch, 'STALE-SHIPPER', 'online');
        $shipper->forceFill(['last_active_at' => now()->subMinutes(10)])->save();
        $order = $this->createOrder($branch, 'STALE-ORDER');

        $this->mockRouting();

        $result = app(ShipperDispatchService::class)->dispatchConfirmedOrder($order);

        $this->assertSame('waiting', $result['status']);
        $this->assertNull($order->fresh()->shipper_id);
        $this->assertSame('online', $shipper->fresh()->status);
        $this->assertDatabaseMissing('shipments', [
            'order_id' => $order->id,
            'shipper_id' => $shipper->id,
        ]);
    }

    public function test_assignment_pulse_exposes_checkout_distance_for_shipper_prompt(): void
    {
        $branch = $this->createBranch('DSP-PULSE-DISTANCE', 10.7769, 106.7009);
        $shipper = $this->createShipper($branch, 'PULSE-DISTANCE-SHIPPER', 'busy');
        $order = $this->createOrder($branch, 'PULSE-DISTANCE-ORDER', $shipper);
        $order->forceFill([
            'note' => 'Giao hàng: khoảng cách 6.4 km, 5 cốc, phí 7.000đ',
        ])->save();
        Shipment::create([
            'order_id' => $order->id,
            'shipper_id' => $shipper->id,
            'status' => 'accepted',
            'assigned_at' => now(),
        ]);

        $this->actingAs($shipper->user)
            ->getJson(route('shipper.assignments.pulse'))
            ->assertOk()
            ->assertJsonPath('order.distance_km', 6.4)
            ->assertJsonPath('order.details.distance_km', 6.4);
    }

    public function test_delivery_arrival_swipe_unlocks_only_within_two_hundred_and_fifty_meters(): void
    {
        $branch = $this->createBranch('ARRIVAL-20M', 10.7769, 106.7009);
        $shipper = $this->createShipper($branch, 'ARRIVAL-SHIPPER', 'busy');
        $order = $this->createOrder($branch, 'ARRIVAL-ORDER', $shipper);
        $order->forceFill(['status' => 'delivering'])->save();
        $shipment = Shipment::create([
            'order_id' => $order->id,
            'shipper_id' => $shipper->id,
            'status' => 'delivering',
            'assigned_at' => now(),
        ]);
        $targetLatitude = (float) $order->shipping_latitude;
        $targetLongitude = (float) $order->shipping_longitude;

        $this->actingAs($shipper->user)
            ->postJson(route('shipper.location.update'), [
                'order_id' => $order->id,
                'latitude' => $targetLatitude + (255 / 111320),
                'longitude' => $targetLongitude,
                'accuracy' => 5,
                'test_mode' => true,
            ])
            ->assertOk()
            ->assertJsonPath('arrival.radius_m', 250)
            ->assertJsonPath('arrival.eligible', false)
            ->assertJsonPath('arrival.verified', false);

        $this->assertDatabaseMissing('shipment_history', [
            'shipment_id' => $shipment->id,
            'status' => 'arrived_customer',
        ]);

        $this->actingAs($shipper->user)
            ->postJson(route('shipper.location.update'), [
                'order_id' => $order->id,
                'latitude' => $targetLatitude + (45 / 111320),
                'longitude' => $targetLongitude,
                'accuracy' => 5,
                'test_mode' => true,
            ])
            ->assertOk()
            ->assertJsonPath('arrival.radius_m', 250)
            ->assertJsonPath('arrival.eligible', true)
            ->assertJsonPath('arrival.verified', true);

        $this->assertDatabaseHas('shipment_history', [
            'shipment_id' => $shipment->id,
            'status' => 'arrived_customer',
        ]);
    }

    public function test_bundle_trip_relations_metadata_and_duplicate_protection(): void
    {
        $branch = $this->createBranch('BND-A', 10.7769, 106.7009);
        $shipper = $this->createShipper($branch, 'BUNDLE-SHIPPER', 'busy');
        $primary = $this->createOrder($branch, 'BUNDLE-PRIMARY', $shipper);
        $merged = $this->createOrder($branch, 'BUNDLE-MERGED');
        $third = $this->createOrder($branch, 'BUNDLE-THIRD');
        $service = app(ShipperBundleService::class);

        $tripId = $service->createTrip($shipper, $primary, $merged, $this->bundleEvaluation([$primary, $merged]));
        $sameTripId = $service->createTrip($shipper, $primary, $merged, $this->bundleEvaluation([$primary, $merged]));
        $thirdTripId = $service->attachOrderToTrip(
            $shipper,
            collect([$primary, $merged]),
            $third,
            $this->bundleEvaluation([$primary, $merged, $third])
        );

        $this->assertSame($tripId, $sameTripId);
        $this->assertSame($tripId, $thirdTripId);
        $this->assertDatabaseCount('delivery_bundle_trips', 1);
        $this->assertDatabaseCount('delivery_bundle_trip_orders', 3);

        $trip = DeliveryBundleTrip::with(['shipper', 'tripOrders.order', 'orders'])->findOrFail($tripId);
        $this->assertTrue($trip->shipper->is($shipper));
        $this->assertCount(3, $trip->orders);
        $this->assertEqualsCanonicalizing(
            [$primary->id, $merged->id, $third->id],
            $trip->orders->pluck('id')->all()
        );
        $this->assertSame('primary', $trip->tripOrders->firstWhere('order_id', $primary->id)->role);
        $this->assertTrue($primary->fresh()->bundleTripOrder->trip->is($trip));

        $duplicateTrip = DeliveryBundleTrip::create([
            'shipper_id' => $shipper->id,
            'status' => 'active',
            'total_cups' => 1,
            'plan_json' => ['order_ids' => [$primary->id]],
        ]);

        try {
            $duplicateTrip->tripOrders()->create([
                'order_id' => $primary->id,
                'role' => 'primary',
            ]);
            $this->fail('The globally unique order_id constraint must reject duplicate bundle membership.');
        } catch (QueryException) {
            $this->assertDatabaseCount('delivery_bundle_trip_orders', 3);
        }
    }

    public function test_bundle_handover_route_prioritizes_the_handover_point_over_the_next_bundle_stop(): void
    {
        $branch = $this->createBranch('BND-HANDOVER-ROUTE', 10.7769, 106.7009);
        $shipper = $this->createShipper($branch, 'BND-HANDOVER-ROUTE-SHIPPER', 'busy');
        $primary = $this->createOrder($branch, 'BND-HANDOVER-ROUTE-PRIMARY', $shipper);
        $primary->forceFill(['status' => 'delivering'])->save();
        $merged = $this->createOrder($branch, 'BND-HANDOVER-ROUTE-MERGED', $shipper);
        $merged->forceFill(['status' => 'delivering'])->save();

        app(ShipperBundleService::class)->createTrip(
            $shipper,
            $primary,
            $merged,
            $this->bundleEvaluation([$primary, $merged])
        );

        $handoverLatitude = 10.8000;
        $handoverLongitude = 106.7300;
        $shipment = Shipment::create([
            'order_id' => $primary->id,
            'shipper_id' => $shipper->id,
            'status' => 'handover_required',
            'assigned_at' => now(),
            'note' => json_encode([
                'type' => 'incident_handover',
                'handover' => [
                    'latitude' => $handoverLatitude,
                    'longitude' => $handoverLongitude,
                    'label' => 'Điểm bàn giao kiểm thử',
                    'address' => 'Vị trí bàn giao kiểm thử',
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        $this->mock(DeliveryRoutingService::class, function ($mock) use ($handoverLatitude, $handoverLongitude) {
            $mock->shouldReceive('route')->once()->andReturn([
                'source' => 'test',
                'fallback' => false,
                'distance_m' => 777.0,
                'duration_s' => 222.0,
                'legs' => [],
                'geometry' => [
                    [10.7769, 106.7009],
                    [$handoverLatitude, $handoverLongitude],
                ],
                'steps' => [],
                'alternatives_count' => 1,
            ]);
        });

        $this->actingAs($shipper->user)
            ->getJson(route('shipper.map.route', [
                'id' => $primary->id,
                'latitude' => 10.7769,
                'longitude' => 106.7009,
                'accuracy' => 5,
            ]))
            ->assertOk()
            ->assertJsonPath('target.type', 'handover')
            ->assertJsonPath('target.latitude', $handoverLatitude)
            ->assertJsonPath('target.longitude', $handoverLongitude)
            ->assertJsonPath('route.distance_m', 777)
            ->assertJsonPath('bundle_route', null);

        $this->assertDatabaseHas('shipments', ['id' => $shipment->id, 'status' => 'handover_required']);
    }

    public function test_dispatch_bundles_a_compatible_order_into_the_busy_shippers_trip(): void
    {
        Notification::fake();

        $branch = $this->createBranch('BND-DISPATCH', 10.7769, 106.7009);
        $shipper = $this->createShipper($branch, 'BUSY-BUNDLE', 'busy');
        $primary = $this->createOrder($branch, 'BUSY-PRIMARY', $shipper);
        $newOrder = $this->createOrder($branch, 'BUSY-MERGED');
        $shipment = Shipment::create([
            'order_id' => $primary->id,
            'shipper_id' => $shipper->id,
            'status' => 'accepted',
            'assigned_at' => now(),
        ]);
        ShipmentHistory::create([
            'shipment_id' => $shipment->id,
            'status' => 'accepted',
        ]);

        $this->mockBundleRouting();

        $result = app(ShipperDispatchService::class)->dispatchConfirmedOrder($newOrder);

        $this->assertSame('assigned', $result['status']);
        $this->assertSame('bundle', $result['dispatch_mode']);
        $this->assertSame($shipper->id, $newOrder->fresh()->shipper_id);
        $this->assertDatabaseCount('delivery_bundle_trips', 1);
        $this->assertDatabaseHas('delivery_bundle_trip_orders', ['order_id' => $primary->id]);
        $this->assertDatabaseHas('delivery_bundle_trip_orders', ['order_id' => $newOrder->id]);
        $this->assertDatabaseHas('delivery_dispatch_decisions', [
            'order_id' => $newOrder->id,
            'shipper_id' => $shipper->id,
            'mode' => 'bundle',
            'selected' => true,
        ]);
    }

    public function test_stale_busy_shipper_can_still_receive_bundle_before_pickup(): void
    {
        Notification::fake();

        $branch = $this->createBranch('BND-STALE-PRE-PICKUP', 10.7769, 106.7009);
        $shipper = $this->createShipper($branch, 'BUSY-STALE-PRE-PICKUP', 'busy');
        $shipper->forceFill(['last_active_at' => now()->subMinutes(10)])->save();
        $primary = $this->createOrder($branch, 'STALE-PRE-PICKUP-PRIMARY', $shipper);
        $newOrder = $this->createOrder($branch, 'STALE-PRE-PICKUP-MERGED');
        $shipment = Shipment::create([
            'order_id' => $primary->id,
            'shipper_id' => $shipper->id,
            'status' => 'accepted',
            'assigned_at' => now(),
        ]);
        ShipmentHistory::create([
            'shipment_id' => $shipment->id,
            'status' => 'accepted',
        ]);

        $this->mockBundleRouting();

        $result = app(ShipperDispatchService::class)->dispatchConfirmedOrder($newOrder);

        $this->assertSame('assigned', $result['status']);
        $this->assertSame('bundle', $result['dispatch_mode']);
        $this->assertSame($shipper->id, $newOrder->fresh()->shipper_id);
        $this->assertDatabaseHas('delivery_bundle_trip_orders', ['order_id' => $primary->id]);
        $this->assertDatabaseHas('delivery_bundle_trip_orders', ['order_id' => $newOrder->id]);
    }

    public function test_bundle_dispatch_stops_after_shipper_leaves_store_200m_after_pickup(): void
    {
        Notification::fake();

        $branch = $this->createBranch('BND-LOCK', 10.7769, 106.7009);
        $shipper = $this->createShipper($branch, 'BUNDLE-LOCK', 'busy');
        $primary = $this->createOrder($branch, 'BUNDLE-LOCK-PRIMARY', $shipper);
        $primary->forceFill(['status' => 'delivering'])->save();
        $newOrder = $this->createOrder($branch, 'BUNDLE-LOCK-NEW');

        $shipper->forceFill([
            'current_latitude' => (float) $branch->latitude + (250 / 111320),
            'current_longitude' => (float) $branch->longitude,
        ])->save();

        $shipment = Shipment::create([
            'order_id' => $primary->id,
            'shipper_id' => $shipper->id,
            'status' => 'delivering',
            'assigned_at' => now(),
        ]);
        ShipmentHistory::create([
            'shipment_id' => $shipment->id,
            'status' => 'accepted',
        ]);

        $this->mockBundleRouting();

        $result = app(ShipperDispatchService::class)->dispatchConfirmedOrder($newOrder);

        $this->assertSame('waiting', $result['status']);
        $this->assertNull($newOrder->fresh()->shipper_id);
        $this->assertDatabaseMissing('delivery_bundle_trip_orders', [
            'order_id' => $newOrder->id,
        ]);
    }

    public function test_orders_from_a_dissolved_trip_can_be_bundled_again_without_duplicate_membership(): void
    {
        $branch = $this->createBranch('BND-RETRY', 10.7769, 106.7009);
        $shipper = $this->createShipper($branch, 'BUNDLE-RETRY', 'busy');
        $primary = $this->createOrder($branch, 'BUNDLE-RETRY-1', $shipper);
        $merged = $this->createOrder($branch, 'BUNDLE-RETRY-2', $shipper);
        $service = app(ShipperBundleService::class);

        $oldTripId = $service->createTrip($shipper, $primary, $merged, $this->bundleEvaluation([$primary, $merged]));
        $this->assertTrue($service->dissolveTripForOrder($primary));

        $newTripId = $service->createTrip($shipper, $primary, $merged, $this->bundleEvaluation([$primary, $merged]));

        $this->assertNotSame($oldTripId, $newTripId);
        $this->assertDatabaseHas('delivery_bundle_trips', ['id' => $oldTripId, 'status' => 'dissolved']);
        $this->assertDatabaseHas('delivery_bundle_trips', ['id' => $newTripId, 'status' => 'active']);
        $this->assertDatabaseCount('delivery_bundle_trip_orders', 2);
        $this->assertSame(
            [$newTripId],
            DB::table('delivery_bundle_trip_orders')->distinct()->pluck('trip_id')->map(fn ($id) => (int) $id)->all()
        );
    }

    public function test_bundle_and_dispatch_records_roll_back_with_the_transaction(): void
    {
        $branch = $this->createBranch('ROLLBACK', 10.7769, 106.7009);
        $shipper = $this->createShipper($branch, 'ROLLBACK-SHIPPER', 'busy');
        $order = $this->createOrder($branch, 'ROLLBACK-ORDER', $shipper);

        $beforeTrips = DeliveryBundleTrip::count();
        $beforeDecisions = DeliveryDispatchDecision::count();

        try {
            DB::transaction(function () use ($shipper, $order) {
                DeliveryBundleTrip::create([
                    'shipper_id' => $shipper->id,
                    'status' => 'active',
                    'total_cups' => 1,
                    'plan_json' => ['order_ids' => [$order->id]],
                ]);
                DeliveryDispatchDecision::create([
                    'batch_uuid' => fake()->uuid(),
                    'order_id' => $order->id,
                    'shipper_id' => $shipper->id,
                    'mode' => 'bundle',
                    'rank' => 1,
                    'score' => 10,
                    'selected' => true,
                    'features_json' => ['source' => 'rollback-test'],
                ]);

                throw new \RuntimeException('Rollback smoke test');
            });
        } catch (\RuntimeException) {
        }

        $this->assertSame($beforeTrips, DeliveryBundleTrip::count());
        $this->assertSame($beforeDecisions, DeliveryDispatchDecision::count());
    }

    private function createBranch(string $code, float $latitude, float $longitude): Branch
    {
        return Branch::create([
            'name' => $code,
            'code' => $code,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'status' => true,
        ]);
    }

    private function createShipper(Branch $branch, string $code, string $status): Shipper
    {
        $user = User::factory()->create([
            'role_id' => User::SHIPPER_ROLE_ID,
            'branch_id' => $branch->id,
        ]);

        return Shipper::create([
            'user_id' => $user->id,
            'code' => $code,
            'phone' => '0900000000',
            'status' => $status,
            'last_active_at' => in_array($status, ['online', 'busy'], true) ? now() : null,
            'station_branch_id' => $branch->id,
            'current_latitude' => $branch->latitude,
            'current_longitude' => $branch->longitude,
        ]);
    }

    private function createOrder(Branch $branch, string $code, ?Shipper $shipper = null): Order
    {
        $customer = User::factory()->create([
            'role_id' => 1,
            'branch_id' => null,
        ]);

        return Order::create([
            'order_code' => $code,
            'user_id' => $customer->id,
            'shipper_id' => $shipper?->id,
            'branch_id' => $branch->id,
            'fulfillment_type' => 'delivery',
            'subtotal' => 10000,
            'shipping_fee' => 0,
            'discount' => 0,
            'total' => 10000,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'status' => OrderStatus::READY_FOR_DELIVERY,
            'shipping_latitude' => (float) $branch->latitude + 0.01,
            'shipping_longitude' => (float) $branch->longitude + 0.01,
        ]);
    }

    private function mockRouting(): void
    {
        $this->mock(DeliveryRoutingService::class, function ($mock) {
            $mock->shouldReceive('route')->andReturn([
                'source' => 'test',
                'fallback' => false,
                'distance_m' => 1000.0,
                'duration_s' => 300.0,
                'legs' => [],
                'geometry' => [],
                'steps' => [],
                'alternatives_count' => 0,
            ]);
        });
    }

    private function mockBundleRouting(): void
    {
        $this->mock(DeliveryRoutingService::class, function ($mock) {
            $mock->shouldReceive('route')->andReturn([
                'source' => 'test',
                'fallback' => false,
                'distance_m' => 500.0,
                'duration_s' => 100.0,
                'legs' => [],
                'geometry' => [],
                'steps' => [],
                'alternatives_count' => 0,
            ]);
            $mock->shouldReceive('routeThrough')->andReturnUsing(function (array $points) {
                $legCount = max(1, count($points) - 1);
                $distance = count($points) >= 5 ? 1800.0 : 1200.0;

                return [
                    'source' => 'test',
                    'fallback' => false,
                    'distance_m' => $distance,
                    'duration_s' => $legCount * 100.0,
                    'legs' => array_fill(0, $legCount, [
                        'distance_m' => $distance / $legCount,
                        'duration_s' => 100.0,
                    ]),
                    'geometry' => [],
                    'steps' => [],
                    'alternatives_count' => 0,
                ];
            });
        });
    }

    private function bundleEvaluation(array $orders): array
    {
        return [
            'total_cups' => count($orders),
            'merged_distance_m' => 2500,
            'merged_duration_s' => 900,
            'saved_distance_m' => 700,
            'separate_distance_m' => 3200,
            'separate_duration_s' => 1100,
            'far_pair' => false,
            'stops' => collect($orders)->map(fn (Order $order) => [
                'type' => 'delivery',
                'order_id' => $order->id,
                'latitude' => 10.77,
                'longitude' => 106.70,
            ])->all(),
        ];
    }
}
