<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Order;
use App\Models\Shipper;
use App\Models\User;
use App\Support\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionEndToEndTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_ui_and_backend_are_limited_to_customer_data(): void
    {
        $customer = User::factory()->create(['role_id' => 1]);
        $otherCustomer = User::factory()->create(['role_id' => 1]);
        $branch = $this->branch('ROLE-CUSTOMER');
        $ownOrder = $this->order($customer, $branch, 'ROLE-OWN-ORDER');
        $otherOrder = $this->order($otherCustomer, $branch, 'ROLE-OTHER-ORDER');

        $this->actingAs($customer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('/staff/', false)
            ->assertDontSee('/shipper/', false);

        $this->actingAs($customer)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee($ownOrder->order_code)
            ->assertDontSee($otherOrder->order_code);

        $this->actingAs($customer)->get(route('staff.dashboard'))->assertRedirect(route('home'));
        $this->actingAs($customer)->get(route('shipper.dashboard'))->assertRedirect(route('home'));
        $this->actingAs($customer)->get(route('admin.dashboard'))->assertRedirect(route('home'));
        $this->actingAs($customer)->get(route('admin.super-admin'))->assertRedirect(route('admin.dashboard'));
    }

    public function test_customer_cannot_mutate_their_role_through_profile_request(): void
    {
        $customer = User::factory()->create(['role_id' => 1]);

        $this->actingAs($customer)->patch(route('profile.update'), [
            'name' => 'Customer Updated',
            'email' => $customer->email,
            'role_id' => 3,
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, (int) $customer->fresh()->role_id);
    }

    public function test_staff_ui_role_and_direct_route_permissions_are_isolated_from_shipper(): void
    {
        $branch = $this->branch('ROLE-STAFF');
        $staff = User::factory()->create([
            'role_id' => User::STAFF_ROLE_ID,
            'branch_id' => $branch->id,
        ]);

        $this->assertTrue($staff->isStaffOnly());
        $this->assertFalse($staff->isShipper());

        $outputBufferLevel = ob_get_level();
        $this->actingAs($staff)
            ->get(route('staff.dashboard'))
            ->assertOk()
            ->assertSee(route('staff.orders.index', absolute: false), false)
            ->assertDontSee('/shipper/', false);
        $this->assertSame($outputBufferLevel, ob_get_level());

        $this->actingAs($staff)->get(route('shipper.dashboard'))->assertRedirect(route('home'));
        $this->actingAs($staff)->get(route('admin.dashboard'))->assertRedirect(route('home'));
        $this->actingAs($staff)->get(route('admin.super-admin'))->assertRedirect(route('admin.dashboard'));
    }

    public function test_staff_order_list_and_updates_are_scoped_to_their_branch(): void
    {
        $branch = $this->branch('ROLE-STAFF-A');
        $otherBranch = $this->branch('ROLE-STAFF-B');
        $staff = User::factory()->create([
            'role_id' => User::STAFF_ROLE_ID,
            'branch_id' => $branch->id,
        ]);
        $customer = User::factory()->create(['role_id' => 1]);
        $ownOrder = $this->order($customer, $branch, 'ROLE-STAFF-OWN');
        $otherOrder = $this->order($customer, $otherBranch, 'ROLE-STAFF-OTHER');

        $this->actingAs($staff)
            ->get(route('staff.orders.index'))
            ->assertOk()
            ->assertSee($ownOrder->order_code)
            ->assertDontSee($otherOrder->order_code);

        $this->actingAs($staff)
            ->put(route('staff.orders.updateStatus', $otherOrder), ['status' => OrderStatus::CONFIRMED])
            ->assertForbidden();

        $this->assertSame(OrderStatus::PENDING, $otherOrder->fresh()->status);
    }

    public function test_shipper_ui_generic_dashboard_and_assignment_scope_are_isolated(): void
    {
        $branch = $this->branch('ROLE-SHIPPER');
        $shipper = $this->shipper($branch, 'ROLE-SHIPPER-A');
        $otherShipper = $this->shipper($branch, 'ROLE-SHIPPER-B');
        $customer = User::factory()->create(['role_id' => 1]);
        $ownOrder = $this->order($customer, $branch, 'ROLE-SHIPPER-OWN', $shipper);
        $otherOrder = $this->order($customer, $branch, 'ROLE-SHIPPER-OTHER', $otherShipper);
        $ownOrder->update(['status' => OrderStatus::CONFIRMED]);
        $otherOrder->update(['status' => OrderStatus::CONFIRMED]);

        $this->assertTrue($shipper->user->isShipper());
        $this->assertFalse($shipper->user->isStaffOnly());

        $this->actingAs($shipper->user)
            ->get(route('dashboard'))
            ->assertRedirect(route('shipper.dashboard'));

        $this->actingAs($shipper->user)
            ->get(route('shipper.dashboard'))
            ->assertOk()
            ->assertSee('/shipper/', false)
            ->assertDontSee('/staff/', false);

        $this->actingAs($shipper->user)->get(route('shipper.orders.show', $ownOrder))->assertOk();
        $this->actingAs($shipper->user)->get(route('shipper.orders.show', $otherOrder))->assertNotFound();
        $this->actingAs($shipper->user)->get(route('staff.dashboard'))->assertRedirect(route('home'));
        $this->actingAs($shipper->user)->get(route('admin.dashboard'))->assertRedirect(route('home'));
        $this->actingAs($shipper->user)->get(route('admin.super-admin'))->assertRedirect(route('admin.dashboard'));
    }

    public function test_admin_order_and_shipper_management_are_scoped_to_their_branch(): void
    {
        $branch = $this->branch('ROLE-ADMIN-A');
        $otherBranch = $this->branch('ROLE-ADMIN-B');
        $admin = User::factory()->create(['role_id' => 2, 'branch_id' => $branch->id]);
        $customer = User::factory()->create(['role_id' => 1]);
        $ownOrder = $this->order($customer, $branch, 'ROLE-ADMIN-OWN');
        $otherOrder = $this->order($customer, $otherBranch, 'ROLE-ADMIN-OTHER');
        $ownShipper = $this->shipper($branch, 'ROLE-ADMIN-SHIPPER-A');
        $otherShipper = $this->shipper($otherBranch, 'ROLE-ADMIN-SHIPPER-B');

        $this->actingAs($admin)
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee($ownOrder->order_code)
            ->assertDontSee($otherOrder->order_code);

        $this->actingAs($admin)
            ->put(route('admin.orders.updateStatus', $otherOrder), ['status' => OrderStatus::CONFIRMED])
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('admin.staff.index'))
            ->assertOk()
            ->assertSee($ownShipper->user->email)
            ->assertDontSee($otherShipper->user->email);

        $this->actingAs($admin)
            ->patch(route('admin.staff.toggle-status', $otherShipper->user))
            ->assertForbidden();

        $this->actingAs($admin)->get(route('admin.super-admin'))->assertRedirect(route('admin.dashboard'));
        $this->assertSame(OrderStatus::PENDING, $otherOrder->fresh()->status);
        $this->assertTrue($otherShipper->user->fresh()->is_active);
    }

    public function test_super_admin_keeps_global_order_scope_after_legacy_branch_query(): void
    {
        $branch = $this->branch('ROLE-SUPER-A');
        $otherBranch = $this->branch('ROLE-SUPER-B');
        $superAdmin = User::factory()->create(['role_id' => 3, 'branch_id' => null]);
        $customer = User::factory()->create(['role_id' => 1]);
        $firstOrder = $this->order($customer, $branch, 'ROLE-SUPER-ONE');
        $secondOrder = $this->order($customer, $otherBranch, 'ROLE-SUPER-TWO');

        $this->actingAs($superAdmin)
            ->get(route('admin.super-admin.manage.orders.index', ['analytics_branch_id' => $branch->id]))
            ->assertOk()
            ->assertSee($firstOrder->order_code)
            ->assertSee($secondOrder->order_code);

        $this->actingAs($superAdmin)->get(route('admin.super-admin'))->assertOk();
        $this->actingAs($superAdmin)->get(route('staff.dashboard'))->assertRedirect(route('home'));
        $this->actingAs($superAdmin)->get(route('shipper.dashboard'))->assertRedirect(route('home'));
    }

    private function branch(string $code): Branch
    {
        return Branch::create([
            'name' => $code,
            'code' => $code,
            'status' => true,
        ]);
    }

    private function order(User $customer, Branch $branch, string $code, ?Shipper $shipper = null): Order
    {
        return Order::create([
            'order_code' => $code,
            'user_id' => $customer->id,
            'branch_id' => $branch->id,
            'shipper_id' => $shipper?->id,
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

    private function shipper(Branch $branch, string $code): Shipper
    {
        $user = User::factory()->create([
            'role_id' => User::SHIPPER_ROLE_ID,
            'branch_id' => $branch->id,
        ]);

        return Shipper::create([
            'user_id' => $user->id,
            'code' => $code,
            'phone' => '0900000000',
            'vehicle_type' => 'bike',
            'status' => 'online',
        ])->load('user');
    }
}
