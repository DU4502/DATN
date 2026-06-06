<?php

namespace Tests\Feature\Admin;

use App\Models\User;
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
        $response->assertDontSee('Nhân viên');
    }

    public function test_admin_can_change_user_role_only(): void
    {
        $admin = $this->admin();
        $user = $this->user([
            'name' => 'Original User',
            'email' => 'original@example.com',
        ]);

        $response = $this->actingAs($admin)->put(route('admin.users.update', $user), [
            'role_id' => 2,
        ]);

        $user->refresh();

        $this->assertSame('Original User', $user->name);
        $this->assertSame('original@example.com', $user->email);
        $this->assertSame(2, (int) $user->role_id);
        $response->assertRedirect(route('admin.users.index'));
    }

    public function test_staff_role_is_not_allowed(): void
    {
        $admin = $this->admin();
        $user = $this->user();

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->put(route('admin.users.update', $user), [
                'role_id' => 3,
            ])
            ->assertSessionHasErrors('role_id');

        $this->assertSame(1, (int) $user->fresh()->role_id);
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
            ->assertSessionHas('error');

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
}
