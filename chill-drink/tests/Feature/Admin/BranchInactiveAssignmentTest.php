<?php

namespace Tests\Feature\Admin;

use App\Models\Branch;
use App\Models\Shipper;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BranchInactiveAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_staff_at_an_active_branch(): void
    {
        $superAdmin = $this->user(3);
        $branch = $this->branch('ACTIVE-STAFF');

        $this->actingAs($superAdmin)
            ->post(route('admin.super-admin.staff.store'), $this->accountPayload('active-staff@example.com', $branch))
            ->assertRedirect(route('admin.super-admin'));

        $this->assertDatabaseHas('users', [
            'email' => 'active-staff@example.com',
            'role_id' => User::STAFF_ROLE_ID,
            'branch_id' => $branch->id,
        ]);
    }

    public function test_super_admin_cannot_create_staff_at_an_inactive_branch(): void
    {
        $superAdmin = $this->user(3);
        $branch = $this->branch('INACTIVE-STAFF', false);

        $this->actingAs($superAdmin)
            ->post(route('admin.super-admin.staff.store'), $this->accountPayload('inactive-staff@example.com', $branch))
            ->assertSessionHasErrors(['branch_id'], null, 'createStaff');

        $this->assertDatabaseMissing('users', ['email' => 'inactive-staff@example.com']);
    }

    public function test_super_admin_can_create_shipper_at_an_active_branch(): void
    {
        $superAdmin = $this->user(3);
        $branch = $this->branch('ACTIVE-SHIPPER');

        $this->actingAs($superAdmin)
            ->post(route('admin.staff.store'), $this->accountPayload('active-shipper@example.com', $branch))
            ->assertRedirect(route('admin.staff.index'));

        $shipperUser = User::query()->where('email', 'active-shipper@example.com')->firstOrFail();
        $this->assertSame(User::SHIPPER_ROLE_ID, (int) $shipperUser->role_id);
        $this->assertSame((int) $branch->id, (int) $shipperUser->branch_id);
        $this->assertDatabaseHas('shippers', [
            'user_id' => $shipperUser->id,
            'station_branch_id' => $branch->id,
        ]);
    }

    public function test_shipper_creation_rejects_an_inactive_branch_for_super_and_regular_admin(): void
    {
        $inactiveBranch = $this->branch('INACTIVE-SHIPPER', false);
        $superAdmin = $this->user(3);
        $admin = $this->user(2, $inactiveBranch);

        $this->actingAs($superAdmin)
            ->post(route('admin.staff.store'), $this->accountPayload('inactive-super-shipper@example.com', $inactiveBranch))
            ->assertSessionHasErrors(['branch_id'], null, 'createStaff');

        $this->actingAs($admin)
            ->post(route('admin.staff.store'), $this->accountPayload('inactive-admin-shipper@example.com'))
            ->assertSessionHasErrors(['branch_id'], null, 'createStaff');

        $this->assertDatabaseMissing('users', ['email' => 'inactive-super-shipper@example.com']);
        $this->assertDatabaseMissing('users', ['email' => 'inactive-admin-shipper@example.com']);
    }

    public function test_direct_role_assignment_to_staff_or_shipper_rejects_an_inactive_branch(): void
    {
        $superAdmin = $this->user(3);
        $inactiveBranch = $this->branch('INACTIVE-ROLE', false);

        foreach ([User::STAFF_ROLE_ID, User::SHIPPER_ROLE_ID] as $roleId) {
            $customer = $this->user(1);

            $this->actingAs($superAdmin)
                ->put(route('admin.users.update', $customer), [
                    'role_id' => $roleId,
                    'branch_id' => $inactiveBranch->id,
                ])
                ->assertSessionHasErrors('branch_id');

            $this->assertSame(1, (int) $customer->fresh()->role_id);
            $this->assertNull($customer->fresh()->branch_id);
        }
    }

    public function test_shipper_transfer_to_an_inactive_branch_is_rejected(): void
    {
        $superAdmin = $this->user(3);
        $activeBranch = $this->branch('TRANSFER-FROM');
        $inactiveBranch = $this->branch('TRANSFER-INACTIVE', false);
        [$shipperUser, $shipper] = $this->shipper($activeBranch);

        $this->actingAs($superAdmin)
            ->patch(route('admin.staff.update-branch', $shipperUser), [
                'branch_id' => $inactiveBranch->id,
            ])
            ->assertSessionHasErrors('branch_id');

        $this->assertSame((int) $activeBranch->id, (int) $shipperUser->fresh()->branch_id);
        $this->assertSame((int) $activeBranch->id, (int) $shipper->fresh()->station_branch_id);
    }

    public function test_staff_branch_update_to_an_inactive_branch_is_rejected(): void
    {
        $superAdmin = $this->user(3);
        $activeBranch = $this->branch('STAFF-TRANSFER-FROM');
        $inactiveBranch = $this->branch('STAFF-TRANSFER-INACTIVE', false);
        $staff = $this->user(User::STAFF_ROLE_ID, $activeBranch);

        $this->actingAs($superAdmin)
            ->patch(route('admin.super-admin.staff.update-branch', $staff), [
                'branch_id' => $inactiveBranch->id,
            ])
            ->assertSessionHasErrors('branch_id');

        $this->assertSame((int) $activeBranch->id, (int) $staff->fresh()->branch_id);
    }

    public function test_legacy_shipper_at_inactive_branch_can_edit_other_fields_without_losing_assignment(): void
    {
        $superAdmin = $this->user(3);
        $inactiveBranch = $this->branch('LEGACY-SHIPPER', false);
        [$shipperUser, $shipper] = $this->shipper($inactiveBranch);

        $this->actingAs($superAdmin)
            ->get(route('admin.super-admin.manage.staff.index'))
            ->assertOk()
            ->assertSee($inactiveBranch->name.' (Ngừng hoạt động - hiện tại)');

        $this->actingAs($superAdmin)
            ->put(route('admin.staff.update', $shipperUser), [
                'name' => 'Legacy Shipper Updated',
                'email' => $shipperUser->email,
                'branch_id' => $inactiveBranch->id,
            ])
            ->assertRedirect(route('admin.staff.index'));

        $this->assertSame('Legacy Shipper Updated', $shipperUser->fresh()->name);
        $this->assertSame((int) $inactiveBranch->id, (int) $shipperUser->fresh()->branch_id);
        $this->assertSame((int) $inactiveBranch->id, (int) $shipper->fresh()->station_branch_id);
    }

    public function test_legacy_staff_at_inactive_branch_can_keep_current_assignment(): void
    {
        $superAdmin = $this->user(3);
        $inactiveBranch = $this->branch('LEGACY-STAFF', false);
        $staff = $this->user(User::STAFF_ROLE_ID, $inactiveBranch);

        $this->actingAs($superAdmin)
            ->get(route('admin.super-admin.manage.users.edit', $staff))
            ->assertOk()
            ->assertSee($inactiveBranch->name.' (Ngừng hoạt động - hiện tại)');

        $this->actingAs($superAdmin)
            ->put(route('admin.users.update', $staff), [
                'role_id' => User::STAFF_ROLE_ID,
                'branch_id' => $inactiveBranch->id,
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertSame(User::STAFF_ROLE_ID, (int) $staff->fresh()->role_id);
        $this->assertSame((int) $inactiveBranch->id, (int) $staff->fresh()->branch_id);
    }

    public function test_legacy_shipper_can_move_from_inactive_to_active_branch(): void
    {
        $superAdmin = $this->user(3);
        $inactiveBranch = $this->branch('LEGACY-FROM', false);
        $activeBranch = $this->branch('LEGACY-TO');
        [$shipperUser, $shipper] = $this->shipper($inactiveBranch);

        $this->actingAs($superAdmin)
            ->patch(route('admin.staff.update-branch', $shipperUser), [
                'branch_id' => $activeBranch->id,
            ])
            ->assertRedirect();

        $this->assertSame((int) $activeBranch->id, (int) $shipperUser->fresh()->branch_id);
        $this->assertSame((int) $activeBranch->id, (int) $shipper->fresh()->station_branch_id);
    }

    private function accountPayload(string $email, ?Branch $branch = null): array
    {
        return [
            'name' => 'Branch Assignment Test',
            'email' => $email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'branch_id' => $branch?->id,
        ];
    }

    private function branch(string $code, bool $active = true): Branch
    {
        return Branch::create([
            'name' => 'Branch '.$code,
            'code' => $code,
            'status' => $active,
        ]);
    }

    private function user(int $roleId, ?Branch $branch = null): User
    {
        return User::create([
            'name' => 'User '.uniqid(),
            'email' => uniqid('branch-', true).'@example.com',
            'password' => Hash::make('password'),
            'role_id' => $roleId,
            'branch_id' => $branch?->id,
            'is_active' => true,
        ]);
    }

    private function shipper(Branch $branch): array
    {
        $user = $this->user(User::SHIPPER_ROLE_ID, $branch);
        $shipper = Shipper::create([
            'user_id' => $user->id,
            'code' => 'SHIP-'.strtoupper(uniqid()),
            'phone' => '0900000000',
            'vehicle_type' => 'bike',
            'status' => 'offline',
            'station_branch_id' => $branch->id,
        ]);

        return [$user, $shipper];
    }
}
