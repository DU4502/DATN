<?php

namespace Tests\Feature\Admin;

use App\Models\Branch;
use App\Models\DeliveryBundleTrip;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\Shipper;
use App\Models\ShipperCodReceivable;
use App\Models\ShipperCodSettlement;
use App\Models\User;
use App\Support\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_users_index(): void
    {
        $admin = $this->admin();
        $user = $this->user(['name' => 'Khách hàng Một']);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertSee($user->name);
        $response->assertDontSee('Thêm người dùng');
        $response->assertSee('<option value="5"', false);
        $response->assertDontSee('<option value="2"', false);
        $response->assertDontSee('<option value="3"', false);
        $response->assertDontSee('<option value="4"', false);
    }

    public function test_admin_cannot_promote_customer_to_admin(): void
    {
        $admin = $this->admin();
        $user = $this->user([
            'name' => 'Original User',
            'email' => 'original@example.com',
        ]);

        $response = $this->actingAs($admin)->put(route('admin.users.update', $user), [
            'role_id' => 2,
        ]);

        $response->assertForbidden();
        $this->assertSame('Original User', $user->fresh()->name);
        $this->assertSame('original@example.com', $user->fresh()->email);
        $this->assertSame(1, (int) $user->fresh()->role_id);
    }

    public function test_unknown_role_is_not_allowed(): void
    {
        $admin = $this->admin();
        $user = $this->user();

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->put(route('admin.users.update', $user), [
                'role_id' => 99,
            ])
            ->assertSessionHasErrors('role_id');

        $this->assertSame(1, (int) $user->fresh()->role_id);
    }

    public function test_super_admin_can_assign_staff_and_shipper_roles(): void
    {
        $superAdmin = $this->admin(['role_id' => 3]);
        $staff = $this->user();
        $shipper = $this->user();
        $branch = Branch::create(['name' => 'Chi nhánh Shipper', 'code' => 'SHIPPER-BRANCH', 'status' => true]);

        $this->actingAs($superAdmin)
            ->get(route('admin.super-admin.manage.users.edit', $staff))
            ->assertOk()
            ->assertSee('Nhân viên')
            ->assertSee('Shipper')
            ->assertSee('Chi nhánh làm việc');

        $this->actingAs($superAdmin)
            ->put(route('admin.users.update', $staff), [
                'role_id' => User::STAFF_ROLE_ID,
                'branch_id' => $branch->id,
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->actingAs($superAdmin)
            ->put(route('admin.users.update', $shipper), [
                'role_id' => User::SHIPPER_ROLE_ID,
                'branch_id' => $branch->id,
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertSame(User::STAFF_ROLE_ID, (int) $staff->fresh()->role_id);
        $this->assertSame((int) $branch->id, (int) $staff->fresh()->branch_id);
        $this->assertSame(User::SHIPPER_ROLE_ID, (int) $shipper->fresh()->role_id);
        $this->assertSame((int) $branch->id, (int) $shipper->fresh()->branch_id);
        $this->assertDatabaseHas('shippers', [
            'user_id' => $shipper->id,
            'status' => 'offline',
            'station_branch_id' => $branch->id,
        ]);
        $this->assertInstanceOf(Shipper::class, $shipper->fresh()->shipper);
    }

    public function test_super_admin_must_choose_a_branch_when_assigning_staff_or_shipper_role(): void
    {
        $superAdmin = $this->admin(['role_id' => 3]);

        foreach ([User::STAFF_ROLE_ID, User::SHIPPER_ROLE_ID] as $roleId) {
            $user = $this->user();

            $this->actingAs($superAdmin)
                ->put(route('admin.users.update', $user), [
                    'role_id' => $roleId,
                ])
                ->assertSessionHasErrors('branch_id');

            $this->assertSame(1, (int) $user->fresh()->role_id);
            $this->assertNull($user->fresh()->shipper);
        }
    }

    public function test_super_admin_can_transfer_an_existing_shipper_to_another_branch(): void
    {
        $superAdmin = $this->admin(['role_id' => 3]);
        $oldBranch = Branch::create(['name' => 'Chi nhánh Cũ', 'code' => 'OLD-BRANCH', 'status' => true]);
        $newBranch = Branch::create(['name' => 'Chi nhánh Mới', 'code' => 'NEW-BRANCH', 'status' => true]);
        $user = $this->user([
            'role_id' => User::SHIPPER_ROLE_ID,
            'branch_id' => $oldBranch->id,
        ]);
        $shipper = Shipper::create([
            'user_id' => $user->id,
            'code' => 'SHP-TRANSFER',
            'phone' => '',
            'vehicle_type' => 'bike',
            'status' => 'offline',
            'station_branch_id' => $oldBranch->id,
        ]);

        $this->actingAs($superAdmin)
            ->put(route('admin.users.update', $user), [
                'role_id' => User::SHIPPER_ROLE_ID,
                'branch_id' => $newBranch->id,
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertSame((int) $newBranch->id, (int) $user->fresh()->branch_id);
        $this->assertSame((int) $newBranch->id, (int) $shipper->fresh()->station_branch_id);
    }

    public function test_regular_admin_can_assign_lower_roles_to_their_branch(): void
    {
        $branch = Branch::create(['name' => 'Chi nhánh Admin', 'code' => 'ADMIN-BRANCH', 'status' => true]);
        $admin = $this->admin(['branch_id' => $branch->id]);

        foreach ([User::STAFF_ROLE_ID, User::SHIPPER_ROLE_ID] as $roleId) {
            $user = $this->user();

            $this->actingAs($admin)
                ->put(route('admin.users.update', $user), [
                    'role_id' => $roleId,
                    'branch_id' => $branch->id,
                ])
                ->assertRedirect(route('admin.users.index'));

            $this->assertSame($roleId, (int) $user->fresh()->role_id);
            $this->assertSame((int) $branch->id, (int) $user->fresh()->branch_id);
        }
    }

    public function test_idle_shipper_can_leave_shipper_role(): void
    {
        $admin = $this->admin(['role_id' => 3]);
        [$shipperUser] = $this->shipperAccount();

        $this->actingAs($admin)
            ->put(route('admin.users.update', $shipperUser), ['role_id' => 1])
            ->assertRedirect(route('admin.users.index'));

        $this->assertSame(1, (int) $shipperUser->fresh()->role_id);
        $this->assertNotNull($shipperUser->fresh()->shipper);
    }

    public function test_direct_role_change_is_blocked_for_shipper_with_active_order(): void
    {
        $admin = $this->admin(['role_id' => 3]);
        [$shipperUser, $shipper, $branch] = $this->shipperAccount();
        $order = $this->deliveryOrder($branch, $shipper, ['status' => OrderStatus::CONFIRMED]);

        $this->actingAs($admin)
            ->from(route('admin.users.edit', $shipperUser))
            ->put(route('admin.users.update', $shipperUser), ['role_id' => 1])
            ->assertSessionHasErrors('role_id')
            ->assertSessionHas('error');

        $this->assertSame(User::SHIPPER_ROLE_ID, (int) $shipperUser->fresh()->role_id);
        $this->assertSame((int) $shipper->id, (int) $order->fresh()->shipper_id);
    }

    public function test_completed_order_does_not_block_shipper_role_change(): void
    {
        $admin = $this->admin(['role_id' => 3]);
        [$shipperUser, $shipper, $branch] = $this->shipperAccount();
        $this->deliveryOrder($branch, $shipper, [
            'status' => OrderStatus::COMPLETED,
            'payment_method' => 'vnpay',
            'payment_status' => 'paid',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $shipperUser), ['role_id' => 1])
            ->assertRedirect(route('admin.users.index'));

        $this->assertSame(1, (int) $shipperUser->fresh()->role_id);
    }

    public function test_active_shipment_blocks_shipper_role_change(): void
    {
        $admin = $this->admin(['role_id' => 3]);
        [$shipperUser, $shipper, $branch] = $this->shipperAccount();
        $order = $this->deliveryOrder($branch, $shipper, [
            'status' => OrderStatus::COMPLETED,
            'payment_method' => 'vnpay',
            'payment_status' => 'paid',
        ]);
        Shipment::create([
            'order_id' => $order->id,
            'shipper_id' => $shipper->id,
            'status' => 'accepted',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $shipperUser), ['role_id' => 1])
            ->assertSessionHasErrors('role_id');

        $this->assertSame(User::SHIPPER_ROLE_ID, (int) $shipperUser->fresh()->role_id);
    }

    public function test_active_bundle_trip_blocks_shipper_role_change(): void
    {
        $admin = $this->admin(['role_id' => 3]);
        [$shipperUser, $shipper] = $this->shipperAccount();
        DeliveryBundleTrip::create([
            'shipper_id' => $shipper->id,
            'status' => 'active',
            'plan_json' => [],
        ]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $shipperUser), ['role_id' => 1])
            ->assertSessionHasErrors('role_id');

        $this->assertSame(User::SHIPPER_ROLE_ID, (int) $shipperUser->fresh()->role_id);
    }

    public function test_unsettled_cod_blocks_shipper_role_change_after_order_completion(): void
    {
        $admin = $this->admin(['role_id' => 3]);
        [$shipperUser, $shipper, $branch] = $this->shipperAccount();
        $order = $this->deliveryOrder($branch, $shipper, [
            'status' => OrderStatus::COMPLETED,
            'payment_method' => 'cod',
            'payment_status' => 'paid',
        ]);
        ShipperCodReceivable::create([
            'order_id' => $order->id,
            'order_code' => $order->order_code,
            'shipper_id' => $shipper->id,
            'order_branch_id' => $branch->id,
            'amount' => $order->total,
            'collected_at' => now(),
        ]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $shipperUser), ['role_id' => 1])
            ->assertSessionHasErrors('role_id');

        $this->assertSame(User::SHIPPER_ROLE_ID, (int) $shipperUser->fresh()->role_id);
    }

    public function test_settled_cod_does_not_block_shipper_role_change(): void
    {
        $admin = $this->admin(['role_id' => 3]);
        [$shipperUser, $shipper, $branch] = $this->shipperAccount();
        $order = $this->deliveryOrder($branch, $shipper, [
            'status' => OrderStatus::COMPLETED,
            'payment_method' => 'cod',
            'payment_status' => 'paid',
        ]);
        $settlement = ShipperCodSettlement::create([
            'shipper_id' => $shipper->id,
            'branch_id' => $branch->id,
            'amount' => $order->total,
            'order_count' => 1,
            'confirmed_by' => $admin->id,
            'confirmed_at' => now(),
        ]);
        ShipperCodReceivable::create([
            'order_id' => $order->id,
            'order_code' => $order->order_code,
            'shipper_id' => $shipper->id,
            'order_branch_id' => $branch->id,
            'amount' => $order->total,
            'collected_at' => now(),
            'settlement_id' => $settlement->id,
            'settled_at' => now(),
        ]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $shipperUser), ['role_id' => 1])
            ->assertRedirect(route('admin.users.index'));

        $this->assertSame(1, (int) $shipperUser->fresh()->role_id);
    }

    public function test_terminal_shipment_and_trip_do_not_block_shipper_role_change(): void
    {
        $admin = $this->admin(['role_id' => 3]);
        [$shipperUser, $shipper, $branch] = $this->shipperAccount();
        $order = $this->deliveryOrder($branch, $shipper, [
            'status' => OrderStatus::COMPLETED,
            'payment_method' => 'vnpay',
            'payment_status' => 'paid',
        ]);
        Shipment::create([
            'order_id' => $order->id,
            'shipper_id' => $shipper->id,
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
        DeliveryBundleTrip::create([
            'shipper_id' => $shipper->id,
            'status' => 'dissolved',
            'plan_json' => [],
        ]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $shipperUser), ['role_id' => 1])
            ->assertRedirect(route('admin.users.index'));

        $this->assertSame(1, (int) $shipperUser->fresh()->role_id);
    }

    public function test_admin_can_lock_and_unlock_user(): void
    {
        $admin = $this->admin();
        $user = $this->user(['is_active' => 1]);

        $this->actingAs($admin)
            ->patch(route('admin.users.toggle-status', $user))
            ->assertRedirect();

        $this->assertFalse($user->fresh()->is_active);

        $this->actingAs($admin)
            ->patch(route('admin.users.toggle-status', $user))
            ->assertRedirect();

        $this->assertTrue($user->fresh()->is_active);
    }

    public function test_admin_cannot_lock_their_own_account(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->patch(route('admin.users.toggle-status', $admin))
            ->assertForbidden();

        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_admin_cannot_create_or_delete_users_from_management(): void
    {
        $admin = $this->admin();
        $user = $this->user();

        $this->actingAs($admin)
            ->get('/admin/users/create')
            ->assertNotFound();

        $this->actingAs($admin)
            ->delete('/admin/users/'.$user->id)
            ->assertStatus(405);

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    private function admin(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Admin Test',
            'email' => 'admin-test-'.uniqid().'@example.com',
            'password' => Hash::make('password'),
            'role_id' => 2,
            'is_active' => 1,
        ], $overrides));
    }

    private function user(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'User Test',
            'email' => 'user-test-'.uniqid().'@example.com',
            'password' => Hash::make('password'),
            'role_id' => 1,
            'is_active' => 1,
        ], $overrides));
    }

    private function shipperAccount(): array
    {
        $branch = Branch::create([
            'name' => 'Chi nhánh '.uniqid(),
            'code' => 'BR-'.strtoupper(uniqid()),
            'status' => true,
        ]);
        $user = $this->user([
            'role_id' => User::SHIPPER_ROLE_ID,
            'branch_id' => $branch->id,
        ]);
        $shipper = Shipper::create([
            'user_id' => $user->id,
            'code' => 'SHIP-'.strtoupper(uniqid()),
            'phone' => '0900000000',
            'vehicle_type' => 'bike',
            'status' => 'offline',
            'station_branch_id' => $branch->id,
        ]);

        return [$user, $shipper, $branch];
    }

    private function deliveryOrder(Branch $branch, Shipper $shipper, array $overrides = []): Order
    {
        return Order::create(array_merge([
            'order_code' => 'ROLE-'.strtoupper(uniqid()),
            'user_id' => $this->user()->id,
            'shipper_id' => $shipper->id,
            'branch_id' => $branch->id,
            'fulfillment_type' => 'delivery',
            'subtotal' => 100000,
            'shipping_fee' => 10000,
            'discount' => 0,
            'total' => 110000,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'status' => OrderStatus::CONFIRMED,
        ], $overrides));
    }
}
