<?php

namespace Tests\Feature\Auth;

use App\Models\Branch;
use App\Models\Order;
use App\Models\User;
use App\Support\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StaffShipperRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_is_not_a_shipper(): void
    {
        $staff = User::factory()->create(['role_id' => User::STAFF_ROLE_ID]);

        $this->assertTrue($staff->isStaffOnly());
        $this->assertFalse($staff->isShipper());
    }

    public function test_shipper_is_not_store_staff(): void
    {
        $shipper = User::factory()->create(['role_id' => User::SHIPPER_ROLE_ID]);

        $this->assertTrue($shipper->isShipper());
        $this->assertFalse($shipper->isStaffOnly());
    }

    public function test_shipper_query_never_includes_role_five_staff(): void
    {
        $staff = User::factory()->create(['role_id' => User::STAFF_ROLE_ID]);
        $shipper = User::factory()->create(['role_id' => User::SHIPPER_ROLE_ID]);

        $shipperIds = User::query()->shippers()->pluck('id');

        $this->assertFalse($shipperIds->contains($staff->id));
        $this->assertTrue($shipperIds->contains($shipper->id));
    }

    public function test_staff_login_redirects_to_staff_dashboard(): void
    {
        $staff = User::factory()->create(['role_id' => User::STAFF_ROLE_ID]);

        $response = $this->post('/login', [
            'email' => $staff->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($staff);
        $response->assertRedirect(route('staff.dashboard', absolute: false));
    }

    public function test_shipper_login_redirects_to_shipper_dashboard(): void
    {
        $shipper = User::factory()->create(['role_id' => User::SHIPPER_ROLE_ID]);

        $response = $this->post('/login', [
            'email' => $shipper->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($shipper);
        $response->assertRedirect(route('shipper.dashboard', absolute: false));
        $this->assertDatabaseHas('shippers', ['user_id' => $shipper->id]);
    }

    public function test_shipper_dashboard_renders_logout_button(): void
    {
        $shipper = User::factory()->create(['role_id' => User::SHIPPER_ROLE_ID]);

        $this->post('/login', [
            'email' => $shipper->email,
            'password' => 'password',
        ])->assertRedirect(route('shipper.dashboard', absolute: false));

        $this
            ->get(route('shipper.dashboard'))
            ->assertOk()
            ->assertSee('data-shipper-logout', false)
            ->assertSee('aria-label="Đăng xuất và ngưng nhận đơn"', false)
            ->assertDontSee('data-shipper-logout-blocked', false)
            ->assertSee('action="'.route('logout').'"', false);
    }

    public function test_shipper_with_incomplete_order_cannot_logout(): void
    {
        [$shipperUser, $order] = $this->shipperWithOrder(OrderStatus::DELIVERING);

        $response = $this->from(route('shipper.dashboard'))->post(route('logout'));

        $this->assertAuthenticatedAs($shipperUser);
        $this->assertSame('online', $shipperUser->fresh()->shipper->status);
        $response
            ->assertRedirect(route('shipper.dashboard'))
            ->assertSessionHas('error', 'Bạn phải hoàn thành đơn hàng đang giao trước khi đăng xuất.');
        $this->assertSame(OrderStatus::DELIVERING, $order->fresh()->status);
    }

    public function test_shipper_can_logout_after_order_is_completed(): void
    {
        [$shipperUser, $order] = $this->shipperWithOrder(OrderStatus::DELIVERING);
        $order->forceFill(['status' => OrderStatus::COMPLETED])->save();

        $this->post(route('logout'))->assertRedirect('/');

        $this->assertGuest();
        $this->assertSame('offline', $shipperUser->fresh()->shipper->status);
    }

    public function test_shipper_logout_button_shows_blocked_state_for_incomplete_order(): void
    {
        $this->shipperWithOrder(OrderStatus::DELIVERED);

        $this->get(route('shipper.dashboard'))
            ->assertOk()
            ->assertSee('data-shipper-logout-blocked', false)
            ->assertSee('Phải hoàn thành đơn hàng trước khi đăng xuất', false);
    }

    public function test_staff_cannot_access_shipper_routes(): void
    {
        $staff = User::factory()->create(['role_id' => User::STAFF_ROLE_ID]);

        $this->actingAs($staff)
            ->get(route('shipper.dashboard'))
            ->assertRedirect(route('home'));
    }

    public function test_shipper_cannot_access_staff_routes(): void
    {
        $shipper = User::factory()->create(['role_id' => User::SHIPPER_ROLE_ID]);

        $this->actingAs($shipper)
            ->get(route('staff.dashboard'))
            ->assertRedirect(route('home'));
    }

    public function test_role_migration_only_moves_legacy_users_with_shipper_profiles(): void
    {
        $staff = User::factory()->create(['role_id' => User::STAFF_ROLE_ID]);
        $legacyShipper = User::factory()->create(['role_id' => User::STAFF_ROLE_ID]);

        DB::table('shippers')->insert([
            'user_id' => $legacyShipper->id,
            'code' => 'SHP-LEGACY-'.$legacyShipper->id,
            'phone' => '',
            'vehicle_type' => 'bike',
            'status' => 'offline',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require database_path('migrations/2026_08_21_000001_separate_staff_and_shipper_roles.php');
        $migration->up();

        $this->assertSame(User::STAFF_ROLE_ID, (int) $staff->fresh()->role_id);
        $this->assertSame(User::SHIPPER_ROLE_ID, (int) $legacyShipper->fresh()->role_id);
    }

    private function shipperWithOrder(string $status): array
    {
        $branch = Branch::create([
            'name' => 'Shipper Logout Branch '.uniqid(),
            'code' => 'SL-'.uniqid(),
            'status' => true,
        ]);
        $shipperUser = User::factory()->create([
            'role_id' => User::SHIPPER_ROLE_ID,
            'branch_id' => $branch->id,
        ]);
        $customer = User::factory()->create(['role_id' => 1]);

        $this->post('/login', [
            'email' => $shipperUser->email,
            'password' => 'password',
        ])->assertRedirect(route('shipper.dashboard', absolute: false));

        $order = Order::create([
            'order_code' => 'LOGOUT-'.uniqid(),
            'user_id' => $customer->id,
            'branch_id' => $branch->id,
            'shipper_id' => $shipperUser->fresh()->shipper->id,
            'fulfillment_type' => 'delivery',
            'subtotal' => 100000,
            'shipping_fee' => 0,
            'discount' => 0,
            'total' => 100000,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'status' => $status,
        ]);

        return [$shipperUser, $order];
    }
}
