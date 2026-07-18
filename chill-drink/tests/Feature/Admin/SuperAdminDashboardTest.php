<?php

namespace Tests\Feature\Admin;

use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_real_dashboard_data_and_filter_admins(): void
    {
        $superAdmin = User::factory()->create([
            'email' => User::SUPER_ADMIN_EMAIL,
            'role_id' => 3,
        ]);
        User::factory()->create([
            'name' => 'Admin Can Tim',
            'email' => 'search-admin@chilldrink.com',
            'role_id' => 3,
        ]);
        User::factory()->create(['role_id' => 1]);

        $response = $this->actingAs($superAdmin)->get('/admin/super-admin?q=search-admin');

        $response->assertOk()
            ->assertSee('Tổng quan quản trị cấp cao')
            ->assertSee('search-admin@chilldrink.com')
            ->assertDontSee('Không có quản trị viên phù hợp');
    }

    public function test_super_admin_can_create_an_admin_account(): void
    {
        $superAdmin = User::factory()->create([
            'email' => User::SUPER_ADMIN_EMAIL,
            'role_id' => 2,
        ]);

        $response = $this->actingAs($superAdmin)->post('/admin/super-admin/admins', [
            'name' => 'Admin Mới',
            'email' => 'admin-moi@chilldrink.com',
            'password' => '12345678',
            'password_confirmation' => '12345678',
            'is_active' => '1',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => 'admin-moi@chilldrink.com',
            'role_id' => 2,
            'is_active' => 1,
        ]);
        $this->assertTrue(SystemLog::where('action', 'like', '%admin-moi@chilldrink.com%')->exists());
    }

    public function test_regular_admin_layout_does_not_show_super_admin_entry(): void
    {
        $admin = User::factory()->create(['role_id' => 2]);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk()
            ->assertDontSee('/admin/super-admin', false);
    }

    public function test_successful_login_updates_last_login_and_creates_audit_log(): void
    {
        $admin = User::factory()->create(['role_id' => 2]);

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $this->assertNotNull($admin->fresh()->last_login_at);
        $this->assertDatabaseHas('system_logs', [
            'user_id' => $admin->id,
            'action' => 'Đăng nhập hệ thống',
            'status' => 'success',
        ]);
    }
}
