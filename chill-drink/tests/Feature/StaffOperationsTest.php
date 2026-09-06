<?php

namespace Tests\Feature;

use App\Events\OrderStatusUpdated;
use App\Models\Branch;
use App\Models\Conversation;
use App\Models\GroupOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemTopping;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\Size;
use App\Models\Topping;
use App\Models\User;
use App\Services\ShipperDispatchService;
use App\Support\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class StaffOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_contains_only_branch_work_and_no_admin_or_shipper_tools(): void
    {
        $branch = $this->branch('STAFF-DASH-A');
        $otherBranch = $this->branch('STAFF-DASH-B');
        $staff = $this->staff($branch);
        $customer = User::factory()->create(['role_id' => 1]);

        $ownOrder = $this->order($customer, $branch, 'STAFF-DASH-OWN');
        $otherOrder = $this->order($customer, $otherBranch, 'STAFF-DASH-OTHER');
        $this->groupOrder($customer, $branch, 'STAFF-GROUP-OWN');
        $this->groupOrder($customer, $otherBranch, 'STAFF-GROUP-OTHER');

        $this->actingAs($staff)
            ->get(route('staff.dashboard'))
            ->assertOk()
            ->assertSee($ownOrder->order_code)
            ->assertDontSee($otherOrder->order_code)
            ->assertViewHas('newOrders', 1)
            ->assertViewHas('groupOrdersToHandle', 1)
            ->assertDontSee('DOANH THU')
            ->assertDontSee('/admin/', false)
            ->assertDontSee('/shipper/', false);
    }

    public function test_dashboard_metric_cards_open_the_exact_branch_scoped_records(): void
    {
        $branch = $this->branch('STAFF-METRICS');
        $otherBranch = $this->branch('STAFF-METRICS-OTHER');
        $staff = $this->staff($branch);
        $customer = User::factory()->create(['role_id' => 1]);

        $pending = $this->order($customer, $branch, 'METRIC-PENDING');
        $confirmed = $this->order($customer, $branch, 'METRIC-CONFIRMED');
        $confirmed->update(['status' => OrderStatus::CONFIRMED]);
        $preparing = $this->order($customer, $branch, 'METRIC-PREPARING');
        $preparing->update(['status' => OrderStatus::PREPARING]);
        $readyDelivery = $this->order($customer, $branch, 'METRIC-READY-DELIVERY');
        $readyDelivery->update(['status' => OrderStatus::READY_FOR_DELIVERY]);
        $readyPickup = $this->order($customer, $branch, 'METRIC-READY-PICKUP', 'pickup');
        $readyPickup->update(['status' => OrderStatus::READY_FOR_PICKUP]);
        $completedToday = $this->order($customer, $branch, 'METRIC-TODAY');
        $completedToday->update(['status' => OrderStatus::COMPLETED]);
        $completedYesterday = $this->order($customer, $branch, 'METRIC-YESTERDAY');
        $completedYesterday->forceFill([
            'status' => OrderStatus::COMPLETED,
            'created_at' => now()->subDay(),
        ])->save();
        $otherOrder = $this->order($customer, $otherBranch, 'METRIC-OTHER-BRANCH');

        $openGroup = $this->groupOrder($customer, $branch, 'METRIC-GROUP-OPEN');
        $closedGroup = $this->groupOrder($customer, $branch, 'METRIC-GROUP-CLOSED');
        $closedGroup->update(['status' => 'closed']);
        $orderedGroup = $this->groupOrder($customer, $branch, 'METRIC-GROUP-ORDERED');
        $orderedGroup->update(['status' => 'ordered']);

        $this->actingAs($staff)
            ->get(route('staff.dashboard'))
            ->assertOk()
            ->assertViewHas('totalWork', 5)
            ->assertSee(route('staff.orders.index', ['scope' => 'work']), false)
            ->assertSee(route('staff.orders.index', ['scope' => 'new']), false)
            ->assertSee(route('staff.orders.index', ['scope' => 'preparing']), false)
            ->assertSee(route('staff.orders.index', ['scope' => 'ready_delivery']), false)
            ->assertSee(route('staff.orders.index', ['scope' => 'ready_pickup']), false)
            ->assertSee(route('staff.orders.index', ['scope' => 'today']), false)
            ->assertSee(route('staff.group-orders.index', ['scope' => 'active']), false);

        $this->get(route('staff.orders.index', ['scope' => 'new']))
            ->assertOk()->assertSee($pending->order_code)->assertDontSee($confirmed->order_code);
        $this->get(route('staff.orders.index', ['scope' => 'preparing']))
            ->assertOk()->assertSee($confirmed->order_code)->assertSee($preparing->order_code)->assertDontSee($pending->order_code);
        $this->get(route('staff.orders.index', ['scope' => 'ready_delivery']))
            ->assertOk()->assertSee($readyDelivery->order_code)->assertDontSee($readyPickup->order_code);
        $this->get(route('staff.orders.index', ['scope' => 'ready_pickup']))
            ->assertOk()->assertSee($readyPickup->order_code)->assertDontSee($readyDelivery->order_code);
        $this->get(route('staff.orders.index', ['scope' => 'work']))
            ->assertOk()->assertSee($pending->order_code)->assertSee($readyPickup->order_code)
            ->assertDontSee($completedToday->order_code)->assertDontSee($otherOrder->order_code);
        $this->get(route('staff.orders.index', ['scope' => 'today']))
            ->assertOk()->assertSee($completedToday->order_code)->assertDontSee($completedYesterday->order_code)
            ->assertDontSee($otherOrder->order_code);
        $this->get(route('staff.group-orders.index', ['scope' => 'active']))
            ->assertOk()->assertSee($openGroup->code)->assertSee($closedGroup->code)->assertDontSee($orderedGroup->code);
    }

    public function test_staff_can_filter_regular_scheduled_and_group_orders_from_one_order_page(): void
    {
        $branch = $this->branch('STAFF-ORDER-TYPES');
        $otherBranch = $this->branch('STAFF-ORDER-TYPES-OTHER');
        $staff = $this->staff($branch);
        $customer = User::factory()->create(['role_id' => 1]);

        $regular = $this->order($customer, $branch, 'TYPE-REGULAR');
        $regular->update(['delivery_type' => 'now']);
        $scheduled = $this->order($customer, $branch, 'TYPE-SCHEDULED');
        $scheduled->update([
            'delivery_type' => 'scheduled',
            'scheduled_delivery_time' => now()->addHour(),
            'scheduled_at' => now()->addHour(),
        ]);
        $groupOrderRecord = $this->order($customer, $branch, 'TYPE-GROUP');
        $group = $this->groupOrder($customer, $branch, 'TYPE-GROUP-ROOM');
        $group->update(['status' => 'ordered', 'order_id' => $groupOrderRecord->id]);
        $otherOrder = $this->order($customer, $otherBranch, 'TYPE-OTHER-BRANCH');

        $this->actingAs($staff)
            ->get(route('staff.orders.index'))
            ->assertOk()
            ->assertSee('name="order_type"', false)
            ->assertSee('Đơn thường')
            ->assertSee('Đơn giao sau')
            ->assertSee('Đơn nhóm')
            ->assertDontSee('href="'.route('staff.group-orders.index').'"', false);

        $this->get(route('staff.orders.index', ['order_type' => 'regular']))
            ->assertOk()->assertSee($regular->order_code)
            ->assertDontSee($scheduled->order_code)->assertDontSee($groupOrderRecord->order_code)
            ->assertDontSee($otherOrder->order_code);
        $this->get(route('staff.orders.index', ['order_type' => 'scheduled']))
            ->assertOk()->assertSee($scheduled->order_code)
            ->assertDontSee($regular->order_code)->assertDontSee($groupOrderRecord->order_code)
            ->assertDontSee($otherOrder->order_code);
        $this->get(route('staff.orders.index', ['order_type' => 'group']))
            ->assertOk()->assertSee($groupOrderRecord->order_code)->assertSee($group->code)
            ->assertDontSee($regular->order_code)->assertDontSee($scheduled->order_code)
            ->assertDontSee($otherOrder->order_code);
    }

    public function test_dashboard_processing_order_has_expandable_real_order_details(): void
    {
        $branch = $this->branch('STAFF-DASH-DETAIL');
        $staff = $this->staff($branch);
        $customer = User::factory()->create([
            'role_id' => 1,
            'phone' => '0901234567',
        ]);
        $order = $this->order($customer, $branch, 'DASHBOARD-DETAIL');
        $order->update([
            'status' => OrderStatus::PREPARING,
            'note' => 'Gọi khách trước khi giao.',
        ]);
        $product = Product::factory()->create(['name' => 'Trà sữa kiểm thử']);
        $size = Size::create(['name' => 'L', 'multiplier' => 1]);
        $productSize = ProductSize::create([
            'product_id' => $product->id,
            'size_id' => $size->id,
            'price' => 55000,
        ]);
        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_size_id' => $productSize->id,
            'ice_level' => 50,
            'sugar_level' => 30,
            'quantity' => 2,
            'unit_price' => 62000,
            'total_price' => 124000,
            'item_note' => 'Không dùng ống hút.',
        ]);
        $topping = Topping::create(['name' => 'Trân châu đen', 'price' => 7000, 'status' => true]);
        OrderItemTopping::create([
            'order_item_id' => $item->id,
            'topping_id' => $topping->id,
            'price' => 7000,
        ]);

        $this->actingAs($staff)
            ->get(route('staff.dashboard'))
            ->assertOk()
            ->assertSee('data-dashboard-order-detail="'.$order->id.'"', false)
            ->assertSee('Chi tiết món')
            ->assertSee('2× Trà sữa kiểm thử')
            ->assertSee('Size L')
            ->assertSee('Đường 30%')
            ->assertSee('Đá 50%')
            ->assertSee('Trân châu đen')
            ->assertSee('Không dùng ống hút.')
            ->assertSee('0901234567')
            ->assertSee('Gọi khách trước khi giao.');
    }

    public function test_staff_order_transitions_follow_store_and_pickup_workflows(): void
    {
        $branch = $this->branch('STAFF-FLOW');
        $staff = $this->staff($branch);
        $customer = User::factory()->create(['role_id' => 1]);
        $deliveryOrder = $this->order($customer, $branch, 'STAFF-DELIVERY');

        $this->actingAs($staff)
            ->put(route('staff.orders.updateStatus', $deliveryOrder), ['status' => OrderStatus::CONFIRMED])
            ->assertRedirect();
        $this->put(route('staff.orders.updateStatus', $deliveryOrder), ['status' => OrderStatus::PREPARING])
            ->assertRedirect();
        $this->put(route('staff.orders.updateStatus', $deliveryOrder), ['status' => OrderStatus::READY_FOR_DELIVERY])
            ->assertRedirect();

        $this->assertSame(OrderStatus::READY_FOR_DELIVERY, $deliveryOrder->fresh()->status);

        $this->put(route('staff.orders.updateStatus', $deliveryOrder), ['status' => OrderStatus::SHIPPER_PICKED_UP])
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertSame(OrderStatus::READY_FOR_DELIVERY, $deliveryOrder->fresh()->status);

        $pickupOrder = $this->order($customer, $branch, 'STAFF-PICKUP', 'pickup');
        foreach ([
            OrderStatus::CONFIRMED,
            OrderStatus::PREPARING,
            OrderStatus::READY_FOR_PICKUP,
            OrderStatus::COMPLETED,
        ] as $status) {
            $this->put(route('staff.orders.updateStatus', $pickupOrder), ['status' => $status])
                ->assertRedirect();
        }

        $this->assertSame(OrderStatus::COMPLETED, $pickupOrder->fresh()->status);
    }

    public function test_staff_dispatches_shipper_only_after_order_is_ready_for_delivery(): void
    {
        $branch = $this->branch('STAFF-DISPATCH-READY');
        $staff = $this->staff($branch);
        $customer = User::factory()->create(['role_id' => 1]);
        $order = $this->order($customer, $branch, 'STAFF-DISPATCH-READY-ORDER');

        $this->mock(ShipperDispatchService::class, function ($mock) {
            $mock->shouldReceive('dispatchConfirmedOrder')
                ->once()
                ->andReturn(['status' => 'waiting', 'shipper' => null]);
        });

        $this->actingAs($staff)
            ->put(route('staff.orders.updateStatus', $order), ['status' => OrderStatus::CONFIRMED])
            ->assertRedirect();
        $this->put(route('staff.orders.updateStatus', $order), ['status' => OrderStatus::PREPARING])
            ->assertRedirect();
        $this->put(route('staff.orders.updateStatus', $order), ['status' => OrderStatus::READY_FOR_DELIVERY])
            ->assertRedirect();

        $this->assertSame(OrderStatus::READY_FOR_DELIVERY, $order->fresh()->status);
        $this->assertNull($order->fresh()->shipper_id);
    }

    public function test_staff_direct_requests_cannot_cross_branch_or_enter_admin_and_shipper_areas(): void
    {
        $branch = $this->branch('STAFF-SCOPE-A');
        $otherBranch = $this->branch('STAFF-SCOPE-B');
        $staff = $this->staff($branch);
        $customer = User::factory()->create(['role_id' => 1]);
        $otherOrder = $this->order($customer, $otherBranch, 'STAFF-SCOPE-ORDER');

        $this->actingAs($staff)
            ->put(route('staff.orders.updateStatus', $otherOrder), ['status' => OrderStatus::CONFIRMED])
            ->assertForbidden();
        $this->get(route('admin.users.index'))->assertRedirect(route('home'));
        $this->get(route('admin.dashboard'))->assertRedirect(route('home'));
        $this->get(route('shipper.dashboard'))->assertRedirect(route('home'));

        $this->assertSame(OrderStatus::PENDING, $otherOrder->fresh()->status);
    }

    public function test_group_orders_and_conversations_are_isolated_by_staff_branch(): void
    {
        $branch = $this->branch('STAFF-COMMS-A');
        $otherBranch = $this->branch('STAFF-COMMS-B');
        $staff = $this->staff($branch);
        $customer = User::factory()->create(['role_id' => 1]);
        $ownGroup = $this->groupOrder($customer, $branch, 'STAFF-GROUP-A');
        $otherGroup = $this->groupOrder($customer, $otherBranch, 'STAFF-GROUP-B');
        $ownConversation = Conversation::create([
            'user_id' => $customer->id,
            'branch_id' => $branch->id,
            'status' => 'open',
        ]);
        $otherConversation = Conversation::create([
            'user_id' => $customer->id,
            'branch_id' => $otherBranch->id,
            'status' => 'open',
        ]);

        $this->actingAs($staff)
            ->get(route('staff.group-orders.index'))
            ->assertOk()
            ->assertSee($ownGroup->code)
            ->assertDontSee($otherGroup->code);
        $this->get(route('staff.group-orders.show', $otherGroup))->assertForbidden();
        $this->put(route('staff.group-orders.updateStatus', $otherGroup), ['status' => 'closed'])
            ->assertForbidden();

        $this->get(route('staff.chat.show', $ownConversation))->assertOk();
        $this->postJson(route('staff.chat.reply', $ownConversation), ['content' => 'Nhân viên chi nhánh hỗ trợ.'])
            ->assertOk();
        $this->get(route('staff.chat.show', $otherConversation))->assertForbidden();
        $this->postJson(route('staff.chat.reply', $otherConversation), ['content' => 'Không được phép.'])
            ->assertForbidden();
    }

    public function test_staff_can_only_authorize_their_branch_order_channel(): void
    {
        $branch = $this->branch('STAFF-RT-A');
        $otherBranch = $this->branch('STAFF-RT-B');
        $staff = $this->staff($branch);

        $this->actingAs($staff)->postJson('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-admin-notifications.'.$branch->id,
        ])->assertOk();

        $this->postJson('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-admin-notifications.'.$otherBranch->id,
        ])->assertForbidden();

        $this->postJson('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-admin-notifications',
        ])->assertForbidden();
    }

    public function test_new_order_modal_is_rendered_for_staff_but_not_branch_admin(): void
    {
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'test-key',
            'broadcasting.connections.reverb.secret' => 'test-secret',
            'broadcasting.connections.reverb.app_id' => 'test-app',
        ]);
        $branch = $this->branch('STAFF-MODAL');
        $otherBranch = $this->branch('STAFF-MODAL-OTHER');
        $staff = $this->staff($branch);
        $customer = User::factory()->create(['role_id' => 1, 'phone' => '0901234567']);
        $ownOrder = $this->order($customer, $branch, 'STAFF-MODAL-OWN');
        $otherOrder = $this->order($customer, $otherBranch, 'STAFF-MODAL-OTHER');
        $admin = User::factory()->create([
            'role_id' => 2,
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        $this->actingAs($staff)
            ->get(route('staff.dashboard'))
            ->assertOk()
            ->assertSee('id="staffNewOrderAlert"', false)
            ->assertSee('Thông báo đơn mới')
            ->assertSee('Địa chỉ giao hàng')
            ->assertSee('Chi tiết món')
            ->assertSee('Size ${item.size_name}', false)
            ->assertSee('${item.sugar_level}% đường', false)
            ->assertSee('${item.ice_level}% đá', false)
            ->assertSee('Xem sau 5 phút')
            ->assertSee('Tắt thông báo')
            ->assertSee("Number(event.detail?.branch_id) === branchId", false)
            ->assertSee("document.addEventListener('order:status-updated'", false)
            ->assertSee("Number(event.detail?.order_id) !== Number(activeOrder.order_id)", false)
            ->assertSee('refreshQueued = refreshQueued || forceShow', false);

        $this->getJson(route('staff.orders.pending-alerts'))
            ->assertOk()
            ->assertJsonPath('pending_count', 1)
            ->assertJsonPath('orders.0.order_id', $ownOrder->id)
            ->assertJsonPath('orders.0.customer_phone', '0901234567')
            ->assertJsonStructure(['orders' => [[
                'order_code', 'created_at', 'branch_name', 'customer_name',
                'customer_phone', 'payment_method_label', 'total_formatted',
                'shipping_address', 'note', 'items', 'url', 'status_update_url',
            ]]])
            ->assertJsonMissing(['order_id' => $otherOrder->id]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('adminPendingAlertLayer', false)
            ->assertDontSee('staffNewOrderAlert', false)
            ->assertSee('window.showRealtimeToast', false)
            ->assertSee('branchAdminOrderChannel', false)
            ->assertSee(".listen('.order.created'", false)
            ->assertSee(".listen('.order.status.updated'", false);
    }

    public function test_only_one_staff_member_can_accept_the_same_pending_order(): void
    {
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'test-key',
            'broadcasting.connections.reverb.secret' => 'test-secret',
            'broadcasting.connections.reverb.app_id' => 'test-app',
        ]);
        Event::fake([OrderStatusUpdated::class]);

        $branch = $this->branch('STAFF-DOUBLE-ACCEPT');
        $firstStaff = $this->staff($branch);
        $secondStaff = $this->staff($branch);
        $customer = User::factory()->create(['role_id' => 1]);
        $order = $this->order($customer, $branch, 'STAFF-DOUBLE-ACCEPT-ORDER');

        $this->actingAs($firstStaff)
            ->putJson(route('staff.orders.updateStatus', $order), ['status' => OrderStatus::CONFIRMED])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->actingAs($secondStaff)
            ->putJson(route('staff.orders.updateStatus', $order), ['status' => OrderStatus::CONFIRMED])
            ->assertConflict()
            ->assertJsonPath('success', false);

        $freshOrder = $order->fresh();
        $this->assertSame(OrderStatus::CONFIRMED, $freshOrder->status);
        $this->assertSame($firstStaff->id, (int) $freshOrder->status_changed_by);
        $this->assertSame($branch->id, (int) $freshOrder->branch_id);
        $this->assertSame($customer->id, (int) $freshOrder->user_id);
        Event::assertDispatchedTimes(OrderStatusUpdated::class, 1);
    }

    private function branch(string $code): Branch
    {
        return Branch::create([
            'name' => $code,
            'code' => $code,
            'status' => true,
        ]);
    }

    private function staff(Branch $branch): User
    {
        return User::factory()->create([
            'role_id' => User::STAFF_ROLE_ID,
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
    }

    private function order(
        User $customer,
        Branch $branch,
        string $code,
        string $fulfillmentType = 'delivery'
    ): Order {
        return Order::create([
            'order_code' => $code,
            'user_id' => $customer->id,
            'branch_id' => $branch->id,
            'fulfillment_type' => $fulfillmentType,
            'subtotal' => 100000,
            'shipping_fee' => 0,
            'discount' => 0,
            'total' => 100000,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'status' => OrderStatus::PENDING,
        ]);
    }

    private function groupOrder(User $owner, Branch $branch, string $code): GroupOrder
    {
        return GroupOrder::create([
            'owner_id' => $owner->id,
            'branch_id' => $branch->id,
            'name' => $code,
            'code' => $code,
            'status' => 'open',
            'closes_at' => now()->addMinutes(30),
        ]);
    }
}
