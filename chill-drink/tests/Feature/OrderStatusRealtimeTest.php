<?php

namespace Tests\Feature;

use App\Events\OrderStatusUpdated;
use App\Models\Branch;
use App\Models\Order;
use App\Models\Shipper;
use App\Models\User;
use App\Support\OrderStatus;
use App\Support\OrderRealtimeChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\Channel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class OrderStatusRealtimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_status_event_contains_customer_payload_and_private_user_channel(): void
    {
        $customer = User::factory()->create(['role_id' => 1, 'is_active' => true]);
        $otherCustomer = User::factory()->create(['role_id' => 1, 'is_active' => true]);
        $order = $this->orderFor($customer, $this->branch());
        $order->update(['status' => OrderStatus::CONFIRMED]);

        $event = new OrderStatusUpdated($order->fresh());
        $payload = $event->broadcastWith();
        $channelNames = collect($event->broadcastOn())
            ->filter(fn ($channel) => $channel instanceof PrivateChannel)
            ->map(fn (PrivateChannel $channel) => $channel->name)
            ->all();

        $this->assertSame($order->id, $payload['order_id']);
        $this->assertSame($order->order_code, $payload['order_code']);
        $this->assertSame(OrderStatus::CONFIRMED, $payload['status']);
        $this->assertSame(OrderStatus::label(OrderStatus::CONFIRMED), $payload['status_label']);
        $this->assertNotEmpty($payload['updated_at']);
        $this->assertContains('private-user.'.$customer->id, $channelNames);
        $this->assertContains('private-order.'.$order->id, $channelNames);
        $this->assertNotContains('private-user.'.$otherCustomer->id, $channelNames);
    }

    public function test_admin_successful_status_update_dispatches_realtime_event_after_saving(): void
    {
        $this->enableRealtime();
        Event::fake([OrderStatusUpdated::class]);
        $branch = $this->branch();
        $admin = User::factory()->create([
            'role_id' => 2,
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $customer = User::factory()->create(['role_id' => 1, 'is_active' => true]);
        $order = $this->orderFor($customer, $branch);

        $this->actingAs($admin)
            ->put(route('admin.orders.updateStatus', $order->id), ['status' => OrderStatus::CONFIRMED])
            ->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::CONFIRMED,
        ]);
        Event::assertDispatched(OrderStatusUpdated::class, fn (OrderStatusUpdated $event) =>
            $event->order->id === $order->id
            && $event->order->status === OrderStatus::CONFIRMED
        );
    }

    public function test_private_user_channel_rejects_another_customer(): void
    {
        $this->enableRealtime();
        $customer = User::factory()->create(['role_id' => 1, 'is_active' => true]);
        $otherCustomer = User::factory()->create(['role_id' => 1, 'is_active' => true]);
        $payload = [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-user.'.$customer->id,
        ];

        $this->actingAs($customer)->postJson('/broadcasting/auth', $payload)->assertOk();
        $this->actingAs($otherCustomer)->postJson('/broadcasting/auth', $payload)->assertForbidden();
    }

    public function test_order_tracking_private_channel_only_authorizes_its_customer(): void
    {
        $this->enableRealtime();
        $customer = User::factory()->create(['role_id' => 1, 'is_active' => true]);
        $otherCustomer = User::factory()->create(['role_id' => 1, 'is_active' => true]);
        $order = $this->orderFor($customer, $this->branch());
        $payload = [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-order.'.$order->id,
        ];

        $this->actingAs($customer)->postJson('/broadcasting/auth', $payload)->assertOk();
        $this->actingAs($otherCustomer)->postJson('/broadcasting/auth', $payload)->assertForbidden();
    }

    public function test_guest_orders_broadcast_on_distinct_opaque_channels(): void
    {
        $branch = $this->branch();
        $first = $this->guestOrder($branch, 'guest-token-a-'.str_repeat('1', 32));
        $second = $this->guestOrder($branch, 'guest-token-b-'.str_repeat('2', 32));

        $firstChannels = collect((new OrderStatusUpdated($first))->broadcastOn())
            ->filter(fn ($channel) => $channel instanceof Channel && ! $channel instanceof PrivateChannel)
            ->pluck('name')
            ->values();
        $secondChannels = collect((new OrderStatusUpdated($second))->broadcastOn())
            ->filter(fn ($channel) => $channel instanceof Channel && ! $channel instanceof PrivateChannel)
            ->pluck('name')
            ->values();

        $this->assertCount(1, $firstChannels);
        $this->assertCount(1, $secondChannels);
        $this->assertNotSame($firstChannels->first(), $secondChannels->first());
        $this->assertStringStartsWith('guest-order.', $firstChannels->first());
        $this->assertStringNotContainsString($first->guest_token, $firstChannels->first());
    }

    public function test_staff_status_changes_dispatch_customer_realtime_events_for_each_step(): void
    {
        $this->enableRealtime();
        Event::fake([OrderStatusUpdated::class]);
        $branch = $this->branch();
        $staff = User::factory()->create([
            'role_id' => User::STAFF_ROLE_ID,
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $customer = User::factory()->create(['role_id' => 1, 'is_active' => true]);
        $order = $this->orderFor($customer, $branch);

        $this->actingAs($staff);
        foreach ([OrderStatus::CONFIRMED, OrderStatus::PREPARING, OrderStatus::READY_FOR_DELIVERY] as $status) {
            $this->put(route('staff.orders.updateStatus', $order->id), ['status' => $status])->assertRedirect();
        }

        Event::assertDispatchedTimes(OrderStatusUpdated::class, 3);
        $this->assertSame(OrderStatus::READY_FOR_DELIVERY, $order->fresh()->status);
    }

    public function test_tracking_page_subscribes_to_exact_order_channel_and_refreshes_on_event(): void
    {
        $customer = User::factory()->create(['role_id' => 1, 'is_active' => true]);
        $order = $this->orderFor($customer, $this->branch());

        $this->actingAs($customer)
            ->get(route('orders.track', $order))
            ->assertOk()
            ->assertSee('data-realtime-channel="order.'.$order->id.'"', false)
            ->assertSee("channel.listen('.order.status.updated'", false)
            ->assertSee('Number(payload?.order_id) !== orderId', false)
            ->assertSee('poll(true)', false);
    }

    public function test_shipper_orders_page_subscribes_and_updates_assigned_order_status_realtime(): void
    {
        $this->enableRealtime();
        $branch = $this->branch();
        $shipperUser = User::factory()->create([
            'role_id' => User::SHIPPER_ROLE_ID,
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $shipper = Shipper::create([
            'user_id' => $shipperUser->id,
            'code' => 'SHIPPER-RT-'.uniqid(),
            'phone' => '0900000002',
            'status' => 'available',
        ]);
        $customer = User::factory()->create(['role_id' => 1, 'is_active' => true]);
        $order = $this->orderFor($customer, $branch);
        $order->update([
            'shipper_id' => $shipper->id,
            'status' => OrderStatus::PREPARING,
        ]);

        $this->actingAs($shipperUser)
            ->get(route('shipper.orders'))
            ->assertOk()
            ->assertSee('data-shipper-order="'.$order->id.'"', false)
            ->assertSee('data-shipper-order-status', false)
            ->assertSee("window.Echo.private('shipper-orders.'", false)
            ->assertSee(".listen('.order.status.updated'", false)
            ->assertSee("badge.textContent = payload.status_label || status", false);
    }

    public function test_admin_ready_for_delivery_update_is_broadcast_to_the_assigned_shipper(): void
    {
        $this->enableRealtime();
        Event::fake([OrderStatusUpdated::class]);
        $branch = $this->branch();
        $admin = User::factory()->create([
            'role_id' => 2,
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $shipperUser = User::factory()->create([
            'role_id' => User::SHIPPER_ROLE_ID,
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $shipper = Shipper::create([
            'user_id' => $shipperUser->id,
            'code' => 'SHIPPER-ADMIN-RT-'.uniqid(),
            'phone' => '0900000003',
            'status' => 'busy',
        ]);
        $customer = User::factory()->create(['role_id' => 1, 'is_active' => true]);
        $order = $this->orderFor($customer, $branch);
        $order->update([
            'shipper_id' => $shipper->id,
            'status' => OrderStatus::PREPARING,
        ]);

        $this->actingAs($admin)
            ->putJson(route('admin.orders.updateStatus', $order), [
                'status' => OrderStatus::READY_FOR_DELIVERY,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', OrderStatus::READY_FOR_DELIVERY);

        $this->assertSame(OrderStatus::READY_FOR_DELIVERY, $order->fresh()->status);
        Event::assertDispatched(OrderStatusUpdated::class, function (OrderStatusUpdated $event) use ($order, $shipperUser) {
            $channels = collect($event->broadcastOn())
                ->filter(fn ($channel) => $channel instanceof PrivateChannel)
                ->map(fn (PrivateChannel $channel) => $channel->name);

            return (int) $event->order->id === (int) $order->id
                && $channels->contains('private-shipper-orders.'.$shipperUser->id);
        });
    }

    public function test_guest_checkout_success_subscribes_only_to_its_opaque_order_channel(): void
    {
        $order = $this->guestOrder($this->branch(), 'checkout-guest-'.str_repeat('a', 40));
        $channel = OrderRealtimeChannel::guest($order);

        $this->withSession(["guest_order_tokens.{$order->id}" => $order->guest_token])
            ->get(route('checkout.success', $order))
            ->assertOk()
            ->assertSee('data-realtime-channel="'.$channel.'"', false)
            ->assertSee('data-realtime-private="0"', false)
            ->assertDontSee('data-realtime-channel="order.'.$order->id.'"', false);
    }

    public function test_failed_status_transition_does_not_dispatch_realtime_event(): void
    {
        $this->enableRealtime();
        Event::fake([OrderStatusUpdated::class]);
        $branch = $this->branch();
        $admin = User::factory()->create([
            'role_id' => 2,
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $customer = User::factory()->create(['role_id' => 1, 'is_active' => true]);
        $order = $this->orderFor($customer, $branch);

        $this->actingAs($admin)
            ->put(route('admin.orders.updateStatus', $order->id), ['status' => OrderStatus::COMPLETED])
            ->assertRedirect();

        $this->assertSame(OrderStatus::PENDING, $order->fresh()->status);
        Event::assertNotDispatched(OrderStatusUpdated::class);
    }

    public function test_broadcast_failure_does_not_roll_back_a_saved_staff_status_change(): void
    {
        $this->enableRealtime();
        $branch = $this->branch();
        $staff = User::factory()->create([
            'role_id' => User::STAFF_ROLE_ID,
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $customer = User::factory()->create(['role_id' => 1, 'is_active' => true]);
        $order = $this->orderFor($customer, $branch);

        Event::forget(OrderStatusUpdated::class);
        Event::listen(OrderStatusUpdated::class, static function (): void {
            throw new \RuntimeException('Broadcast transport unavailable');
        });

        $this->actingAs($staff)
            ->putJson(route('staff.orders.updateStatus', $order), [
                'status' => OrderStatus::CONFIRMED,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::CONFIRMED,
            'status_changed_by' => $staff->id,
        ]);
    }

    public function test_orders_page_renders_realtime_status_targets_without_removing_existing_actions(): void
    {
        $customer = User::factory()->create(['role_id' => 1, 'is_active' => true]);
        $order = $this->orderFor($customer, $this->branch());
        $completedOrder = $this->orderFor($customer, $this->branch());
        $completedOrder->update(['status' => OrderStatus::COMPLETED]);

        $this->actingAs($customer)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee('data-order-id="'.$order->id.'"', false)
            ->assertSee('data-order-status-badge', false)
            ->assertSee('data-order-status-icon', false)
            ->assertSee('data-order-cancel-action', false)
            ->assertSee('data-order-confirm-action', false)
            ->assertSee('Đặt lại đơn');
    }

    public function test_every_order_workflow_status_has_consistent_realtime_badge_data(): void
    {
        $customer = User::factory()->create(['role_id' => 1, 'is_active' => true]);
        $order = $this->orderFor($customer, $this->branch());

        foreach (array_keys(OrderStatus::userBadgeStyles()) as $status) {
            $order->forceFill(['status' => $status]);
            $payload = (new OrderStatusUpdated($order))->broadcastWith();
            $style = OrderStatus::userBadgeStyles()[$status];

            $this->assertSame($status, $payload['status']);
            $this->assertSame($style['label'], $payload['status_label']);
            $this->assertSame(OrderStatus::notificationIcon($status), $payload['status_icon']);
            $this->assertNotEmpty($style['class']);
        }
    }

    public function test_admin_ajax_status_update_returns_json_and_dispatches_realtime_event(): void
    {
        $this->enableRealtime();
        Event::fake([OrderStatusUpdated::class]);
        $branch = $this->branch();
        $admin = User::factory()->create([
            'role_id' => 2,
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $customer = User::factory()->create(['role_id' => 1, 'is_active' => true]);
        $order = $this->orderFor($customer, $branch);

        $this->actingAs($admin)
            ->putJson(route('admin.orders.updateStatus', $order->id), [
                'status' => OrderStatus::CONFIRMED,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.order_code', $order->order_code)
            ->assertJsonPath('data.status', OrderStatus::CONFIRMED)
            ->assertJsonPath('data.status_label', OrderStatus::label(OrderStatus::CONFIRMED))
            ->assertJsonPath('data.status_options.'.OrderStatus::PREPARING, OrderStatus::actionLabels()[OrderStatus::PREPARING]);

        $this->assertSame(OrderStatus::CONFIRMED, $order->fresh()->status);
        Event::assertDispatched(OrderStatusUpdated::class, fn (OrderStatusUpdated $event) =>
            $event->order->id === $order->id
            && $event->order->status === OrderStatus::CONFIRMED
        );
    }

    public function test_admin_ajax_status_update_rolls_back_ui_payload_when_backend_rejects_transition(): void
    {
        $this->enableRealtime();
        Event::fake([OrderStatusUpdated::class]);
        $branch = $this->branch();
        $admin = User::factory()->create([
            'role_id' => 2,
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $customer = User::factory()->create(['role_id' => 1, 'is_active' => true]);
        $order = $this->orderFor($customer, $branch);

        $this->actingAs($admin)
            ->putJson(route('admin.orders.updateStatus', $order->id), [
                'status' => OrderStatus::COMPLETED,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['message']);

        $this->assertSame(OrderStatus::PENDING, $order->fresh()->status);
        Event::assertNotDispatched(OrderStatusUpdated::class);
    }

    public function test_admin_orders_page_uses_background_status_request_without_inline_submit(): void
    {
        $branch = $this->branch();
        $admin = User::factory()->create([
            'role_id' => 2,
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $customer = User::factory()->create(['role_id' => 1, 'is_active' => true]);
        $this->orderFor($customer, $branch);

        $this->actingAs($admin)
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee('data-order-status-form', false)
            ->assertSee('data-order-status-select', false)
            ->assertSee("fetch(form.action", false)
            ->assertSee('event.preventDefault()', false)
            ->assertDontSee('onchange="this.form.submit()"', false)
            ->assertDontSee('window.location.reload()', false);
    }

    public function test_super_admin_realtime_rows_use_status_route_with_order_id(): void
    {
        $branch = $this->branch();
        $superAdmin = User::factory()->create([
            'role_id' => 3,
            'is_active' => true,
        ]);
        $customer = User::factory()->create(['role_id' => 1, 'is_active' => true]);
        $order = $this->orderFor($customer, $branch);

        $this->actingAs($superAdmin)
            ->get(route('admin.super-admin.manage.orders.index'))
            ->assertOk()
            ->assertSee(route('admin.super-admin.manage.orders.updateStatus', $order->id), false)
            ->assertSee("const statusUpdateUrlTemplate =", false)
            ->assertSee("payload.status_update_url || statusUpdateUrl(orderId)", false)
            ->assertDontSee("payload.status_update_url || '#'", false);
    }

    public function test_super_admin_ajax_status_route_updates_the_target_order(): void
    {
        $this->enableRealtime();
        Event::fake([OrderStatusUpdated::class]);
        $branch = $this->branch();
        $superAdmin = User::factory()->create([
            'role_id' => 3,
            'is_active' => true,
        ]);
        $customer = User::factory()->create(['role_id' => 1, 'is_active' => true]);
        $order = $this->orderFor($customer, $branch);

        $this->actingAs($superAdmin)
            ->putJson(route('admin.super-admin.manage.orders.updateStatus', $order->id), [
                'status' => OrderStatus::CONFIRMED,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.status', OrderStatus::CONFIRMED);

        $this->assertSame(OrderStatus::CONFIRMED, $order->fresh()->status);
        Event::assertDispatched(OrderStatusUpdated::class, fn (OrderStatusUpdated $event) =>
            $event->order->id === $order->id
            && $event->order->status === OrderStatus::CONFIRMED
        );
    }

    private function enableRealtime(): void
    {
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'test-key',
            'broadcasting.connections.reverb.secret' => 'test-secret',
            'broadcasting.connections.reverb.app_id' => 'test-app',
        ]);
    }

    private function branch(): Branch
    {
        return Branch::create([
            'name' => 'Chi nhánh realtime '.uniqid(),
            'code' => 'RT-'.uniqid(),
            'status' => true,
        ]);
    }

    private function orderFor(User $customer, Branch $branch): Order
    {
        return Order::create([
            'order_code' => 'CD-RT-'.uniqid(),
            'user_id' => $customer->id,
            'branch_id' => $branch->id,
            'fulfillment_type' => 'delivery',
            'subtotal' => 100000,
            'shipping_fee' => 0,
            'discount' => 0,
            'total' => 100000,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'status' => OrderStatus::PENDING,
        ]);
    }

    private function guestOrder(Branch $branch, string $token): Order
    {
        return Order::create([
            'order_code' => 'CD-GUEST-'.uniqid(),
            'user_id' => null,
            'guest_name' => 'Guest realtime',
            'guest_email' => uniqid().'@example.test',
            'guest_phone' => '0900000000',
            'guest_token' => $token,
            'branch_id' => $branch->id,
            'fulfillment_type' => 'delivery',
            'subtotal' => 100000,
            'shipping_fee' => 0,
            'discount' => 0,
            'total' => 100000,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'status' => OrderStatus::PENDING,
        ]);
    }
}
