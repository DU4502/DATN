<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShipmentHistory;
use App\Models\ShipmentTracking;
use App\Models\Shipper;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipperCoreRelationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_shipper_core_relations_and_eager_loading(): void
    {
        $branch = Branch::create([
            'name' => 'Core Relations Branch',
            'code' => 'CORE-REL',
            'status' => true,
        ]);

        $user = User::factory()->create([
            'role_id' => User::SHIPPER_ROLE_ID,
            'branch_id' => $branch->id,
        ]);

        $shipper = Shipper::create([
            'user_id' => $user->id,
            'code' => 'SHP-CORE-REL',
            'phone' => '0900000000',
            'vehicle_type' => 'truck',
            'status' => 'online',
            'station_branch_id' => $branch->id,
        ]);

        $order = Order::create([
            'order_code' => 'CORE-REL-ORDER',
            'user_id' => $user->id,
            'shipper_id' => $shipper->id,
            'branch_id' => $branch->id,
            'subtotal' => 10000,
            'shipping_fee' => 0,
            'discount' => 0,
            'total' => 10000,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);

        $shipment = Shipment::create([
            'order_id' => $order->id,
            'shipper_id' => $shipper->id,
            'status' => 'assigned',
            'assigned_at' => now(),
        ]);

        $tracking = ShipmentTracking::create([
            'shipment_id' => $shipment->id,
            'latitude' => 10.7769,
            'longitude' => 106.7009,
            'recorded_at' => now(),
        ]);

        $history = ShipmentHistory::create([
            'shipment_id' => $shipment->id,
            'status' => 'assigned',
            'description' => 'Assigned for relation testing',
        ]);

        $this->assertTrue($user->fresh()->shipper->is($shipper));
        $this->assertTrue($shipper->user->is($user));
        $this->assertTrue($order->shipper->is($shipper));
        $this->assertTrue($shipper->orders->contains($order));
        $this->assertTrue($shipment->order->is($order));
        $this->assertTrue($shipment->shipper->is($shipper));
        $this->assertTrue($shipper->shipments->contains($shipment));
        $this->assertTrue($order->shipments->contains($shipment));
        $this->assertTrue($shipment->trackingRecords->contains($tracking));
        $this->assertTrue($tracking->shipment->is($shipment));
        $this->assertTrue($shipment->historyRecords->contains($history));
        $this->assertTrue($history->shipment->is($shipment));

        $loadedShipment = Shipment::with([
            'order.shipper',
            'shipper.user',
            'trackingRecords.shipment',
            'historyRecords.shipment',
        ])->findOrFail($shipment->id);

        $this->assertTrue($loadedShipment->relationLoaded('order'));
        $this->assertTrue($loadedShipment->relationLoaded('shipper'));
        $this->assertTrue($loadedShipment->relationLoaded('trackingRecords'));
        $this->assertTrue($loadedShipment->relationLoaded('historyRecords'));
    }
}
