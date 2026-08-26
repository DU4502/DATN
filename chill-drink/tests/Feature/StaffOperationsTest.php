<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Conversation;
use App\Models\GroupOrder;
use App\Models\Order;
use App\Models\User;
use App\Support\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
