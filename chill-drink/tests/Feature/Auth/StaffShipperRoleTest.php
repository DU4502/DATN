<?php

namespace Tests\Feature\Auth;

use App\Models\User;
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
}
