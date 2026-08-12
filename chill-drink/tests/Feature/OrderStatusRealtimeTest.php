<?php

namespace Tests\Feature;

use App\Events\OrderStatusUpdated;
use App\Models\Branch;
use App\Models\Order;
use App\Models\User;
use App\Support\OrderStatus;
use Illuminate\Broadcasting\PrivateChannel;
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

    public function test_orders_page_renders_realtime_status_targets_without_removing_existing_actions(): void
    {
        $customer = User::factory()->create(['role_id' => 1, 'is_active' => true]);
        $order = $this->orderFor($customer, $this->branch());

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
}
