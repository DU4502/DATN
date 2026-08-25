<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderIssueNavigationVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sidebar_does_not_show_order_issue_link(): void
    {
        $admin = User::factory()->create(['role_id' => 2]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Khiếu nại đơn hàng');
    }

    public function test_super_admin_sidebar_does_not_show_order_issue_link(): void
    {
        $superAdmin = User::factory()->create(['role_id' => 3]);

        $this->actingAs($superAdmin)
            ->get(route('admin.super-admin'))
            ->assertOk()
            ->assertDontSee('Khiếu nại đơn hàng');
    }
}
