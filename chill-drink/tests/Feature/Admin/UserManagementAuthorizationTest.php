<?php

namespace Tests\Feature\Admin;

use App\Models\Branch;
use App\Models\Shipper;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('roles')->updateOrInsert(
            ['id' => 4],
            ['name' => 'cskh', 'description' => 'Customer support'],
        );
    }

    public function test_regular_admin_cannot_promote_any_supported_target_to_admin_or_super_admin(): void
    {
        $branch = $this->branch('AUTH-A');
        $admin = $this->user(2, $branch);

        foreach ([1, 4, User::STAFF_ROLE_ID, User::SHIPPER_ROLE_ID] as $currentRole) {
            foreach ([2, 3] as $forbiddenRole) {
                $target = $this->user($currentRole, $currentRole === 1 ? null : $branch);

                $this->actingAs($admin)
                    ->put(route('admin.users.update', $target), ['role_id' => $forbiddenRole])
                    ->assertForbidden();

                $this->assertSame($currentRole, (int) $target->fresh()->role_id);
            }
        }
    }

    public function test_cskh_role_is_hidden_from_regular_and_super_admin_assignment_ui(): void
    {
        $branch = $this->branch('AUTH-CSKH-UI');
        $admin = $this->user(2, $branch);
        $superAdmin = $this->user(3);
        $customer = $this->user();

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee($customer->name)
            ->assertDontSee('<option value="4"', false);

        $this->actingAs($superAdmin)
            ->get(route('admin.super-admin.manage.users.index'))
            ->assertOk()
            ->assertDontSee('<option value="4"', false);
    }

    public function test_direct_cskh_assignment_is_rejected_for_regular_and_super_admin(): void
    {
        $branch = $this->branch('AUTH-CSKH-DIRECT');
        $admin = $this->user(2, $branch);
        $superAdmin = $this->user(3);

        foreach ([$admin, $superAdmin] as $actor) {
            $customer = $this->user();

            $this->actingAs($actor)
                ->from(route('admin.users.index'))
                ->put(route('admin.users.update', $customer), [
                    'role_id' => 4,
                    'branch_id' => $branch->id,
                ])
                ->assertSessionHasErrors('role_id');

            $this->assertSame(1, (int) $customer->fresh()->role_id);
            $this->assertNull($customer->fresh()->branch_id);
        }
    }

    public function test_legacy_super_admin_role_endpoint_rejects_cskh_assignment(): void
    {
        $superAdmin = $this->user(3);
        $targetAdmin = $this->user(2);

        $this->actingAs($superAdmin)
            ->patch(route('admin.super-admin.update-role', $targetAdmin), ['role_id' => 4])
            ->assertSessionHasErrors('role_id');

        $this->assertSame(2, (int) $targetAdmin->fresh()->role_id);
    }

    public function test_existing_cskh_account_remains_unchanged_while_assignment_is_hidden(): void
    {
        $branch = $this->branch('AUTH-CSKH-LEGACY');
        $admin = $this->user(2, $branch);
        $legacyCskh = $this->user(4, $branch, 'Legacy Support');
        $original = $legacyCskh->only(['name', 'email', 'role_id', 'branch_id', 'is_active']);
        $countBefore = User::query()->where('role_id', 4)->count();

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee($legacyCskh->name)
            ->assertDontSee('<option value="4"', false);

        $this->assertSame($countBefore, User::query()->where('role_id', 4)->count());
        $this->assertSame($original, $legacyCskh->fresh()->only(array_keys($original)));
    }

    public function test_regular_admin_cannot_manage_peer_admin_or_super_admin(): void
    {
        $branch = $this->branch('AUTH-B');
        $admin = $this->user(2, $branch);

        foreach ([2, 3] as $targetRole) {
            $target = $this->user($targetRole, $branch);

            $this->actingAs($admin)->get(route('admin.users.show', $target))->assertForbidden();
            $this->actingAs($admin)->get(route('admin.users.edit', $target))->assertForbidden();
            $this->actingAs($admin)
                ->put(route('admin.users.update', $target), ['role_id' => 1])
                ->assertForbidden();
            $this->actingAs($admin)
                ->patch(route('admin.users.toggle-status', $target))
                ->assertForbidden();

            $this->assertSame($targetRole, (int) $target->fresh()->role_id);
            $this->assertTrue($target->fresh()->is_active);
        }
    }

    public function test_regular_admin_cannot_manage_branch_scoped_users_from_another_branch(): void
    {
        $branchA = $this->branch('AUTH-C-A');
        $branchB = $this->branch('AUTH-C-B');
        $admin = $this->user(2, $branchA);

        foreach ([4, User::STAFF_ROLE_ID, User::SHIPPER_ROLE_ID] as $roleId) {
            $target = $this->user($roleId, $branchB);

            $this->actingAs($admin)->get(route('admin.users.show', $target))->assertForbidden();
            $this->actingAs($admin)->get(route('admin.users.edit', $target))->assertForbidden();
            $this->actingAs($admin)
                ->put(route('admin.users.update', $target), [
                    'role_id' => $roleId,
                    'branch_id' => $branchA->id,
                ])
                ->assertForbidden();
            $this->actingAs($admin)
                ->patch(route('admin.users.toggle-status', $target))
                ->assertForbidden();

            $this->assertSame((int) $branchB->id, (int) $target->fresh()->branch_id);
            $this->assertTrue($target->fresh()->is_active);
        }
    }

    public function test_regular_admin_index_is_branch_isolated_for_branch_roles(): void
    {
        $branchA = $this->branch('AUTH-D-A');
        $branchB = $this->branch('AUTH-D-B');
        $admin = $this->user(2, $branchA);
        $sameBranchStaff = $this->user(User::STAFF_ROLE_ID, $branchA, 'Visible Staff');
        $otherBranchStaff = $this->user(User::STAFF_ROLE_ID, $branchB, 'Hidden Staff');
        $peerAdmin = $this->user(2, $branchA, 'Hidden Admin');
        $customer = $this->user(1, null, 'Visible Customer');

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertOk()
            ->assertSee($sameBranchStaff->name)
            ->assertSee($customer->name)
            ->assertSee($branchA->name)
            ->assertDontSee($otherBranchStaff->name)
            ->assertDontSee($branchB->name)
            ->assertDontSee($peerAdmin->name);
    }

    public function test_regular_admin_can_manage_lower_roles_in_own_branch(): void
    {
        $branch = $this->branch('AUTH-E');
        $admin = $this->user(2, $branch);
        $staff = $this->user(User::STAFF_ROLE_ID, $branch);

        $this->actingAs($admin)->get(route('admin.users.show', $staff))->assertOk();
        $this->actingAs($admin)->get(route('admin.users.edit', $staff))->assertOk();
        $this->actingAs($admin)
            ->put(route('admin.users.update', $staff), [
                'role_id' => User::STAFF_ROLE_ID,
                'branch_id' => $branch->id,
            ])
            ->assertRedirect(route('admin.users.index'));
        $this->actingAs($admin)
            ->patch(route('admin.users.toggle-status', $staff))
            ->assertRedirect();

        $this->assertSame(User::STAFF_ROLE_ID, (int) $staff->fresh()->role_id);
        $this->assertFalse($staff->fresh()->is_active);
    }

    public function test_regular_admin_cannot_assign_a_lower_role_to_another_branch(): void
    {
        $branchA = $this->branch('AUTH-F-A');
        $branchB = $this->branch('AUTH-F-B');
        $admin = $this->user(2, $branchA);
        $customer = $this->user();

        $this->actingAs($admin)
            ->put(route('admin.users.update', $customer), [
                'role_id' => User::STAFF_ROLE_ID,
                'branch_id' => $branchB->id,
            ])
            ->assertForbidden();

        $this->assertSame(1, (int) $customer->fresh()->role_id);
        $this->assertNull($customer->fresh()->branch_id);
    }

    public function test_regular_admin_cannot_escalate_their_own_role(): void
    {
        $branch = $this->branch('AUTH-G');
        $admin = $this->user(2, $branch);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $admin), ['role_id' => 3])
            ->assertForbidden();

        $this->assertSame(2, (int) $admin->fresh()->role_id);
    }

    public function test_customer_staff_and_shipper_profile_requests_cannot_mass_assign_role(): void
    {
        $branch = $this->branch('AUTH-PROFILE');

        foreach ([1, User::STAFF_ROLE_ID] as $roleId) {
            $user = $this->user($roleId, $roleId === 1 ? null : $branch);

            $this->actingAs($user)
                ->patch(route('profile.update'), [
                    'name' => $user->name,
                    'email' => $user->email,
                    'role_id' => 3,
                ])
                ->assertRedirect(route('profile.edit'));

            $this->assertSame($roleId, (int) $user->fresh()->role_id);
        }

        $shipperUser = $this->user(User::SHIPPER_ROLE_ID, $branch);
        Shipper::create([
            'user_id' => $shipperUser->id,
            'code' => 'AUTH-PROFILE-SHIPPER',
            'phone' => '0900000000',
            'vehicle_type' => 'bike',
            'status' => 'offline',
            'station_branch_id' => $branch->id,
        ]);

        $this->actingAs($shipperUser)
            ->put(route('shipper.profile.update'), [
                'name' => $shipperUser->name,
                'phone' => '0900000001',
                'vehicle_type' => 'bike',
                'role_id' => 3,
            ])
            ->assertRedirect(route('shipper.profile'));

        $this->assertSame(User::SHIPPER_ROLE_ID, (int) $shipperUser->fresh()->role_id);
    }

    public function test_bulk_status_change_is_atomic_when_any_target_is_out_of_scope(): void
    {
        $branchA = $this->branch('AUTH-H-A');
        $branchB = $this->branch('AUTH-H-B');
        $admin = $this->user(2, $branchA);
        $sameBranchStaff = $this->user(User::STAFF_ROLE_ID, $branchA);
        $otherBranchStaff = $this->user(User::STAFF_ROLE_ID, $branchB);

        $this->actingAs($admin)
            ->patch(route('admin.users.bulk-toggle-status'), [
                'user_ids' => [$sameBranchStaff->id, $otherBranchStaff->id],
                'status' => false,
            ])
            ->assertForbidden();

        $this->assertTrue($sameBranchStaff->fresh()->is_active);
        $this->assertTrue($otherBranchStaff->fresh()->is_active);
    }

    public function test_super_admin_can_manage_users_globally_and_assign_admin_role(): void
    {
        $branchA = $this->branch('AUTH-I-A');
        $branchB = $this->branch('AUTH-I-B');
        $superAdmin = $this->user(3, $branchA);
        $staff = $this->user(User::STAFF_ROLE_ID, $branchB);

        $this->actingAs($superAdmin)
            ->get(route('admin.super-admin.manage.users.edit', $staff))
            ->assertOk();
        $this->actingAs($superAdmin)
            ->put(route('admin.users.update', $staff), ['role_id' => 2])
            ->assertRedirect(route('admin.users.index'));

        $this->assertSame(2, (int) $staff->fresh()->role_id);
    }

    public function test_inactive_branch_cannot_be_assigned_to_staff_or_shipper(): void
    {
        $activeBranch = $this->branch('AUTH-J-A');
        $inactiveBranch = $this->branch('AUTH-J-B', false);
        $superAdmin = $this->user(3, $activeBranch);

        foreach ([User::STAFF_ROLE_ID, User::SHIPPER_ROLE_ID] as $roleId) {
            $customer = $this->user();

            $this->actingAs($superAdmin)
                ->put(route('admin.users.update', $customer), [
                    'role_id' => $roleId,
                    'branch_id' => $inactiveBranch->id,
                ])
                ->assertSessionHasErrors('branch_id');

            $this->assertSame(1, (int) $customer->fresh()->role_id);
            $this->assertNull($customer->fresh()->branch_id);
        }

        $legacyStaff = $this->user(User::STAFF_ROLE_ID, $inactiveBranch);
        $this->assertSame((int) $inactiveBranch->id, (int) $legacyStaff->fresh()->branch_id);
        $this->assertSame(User::STAFF_ROLE_ID, (int) $legacyStaff->fresh()->role_id);
    }

    private function branch(string $code, bool $active = true): Branch
    {
        return Branch::create([
            'name' => 'Branch '.$code,
            'code' => $code,
            'status' => $active,
        ]);
    }

    private function user(int $roleId = 1, ?Branch $branch = null, ?string $name = null): User
    {
        return User::create([
            'name' => $name ?? 'User '.uniqid(),
            'email' => uniqid('auth-', true).'@example.com',
            'password' => Hash::make('password'),
            'role_id' => $roleId,
            'branch_id' => $branch?->id,
            'is_active' => true,
        ]);
    }
}
