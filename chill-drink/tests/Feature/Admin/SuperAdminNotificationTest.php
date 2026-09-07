<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class SuperAdminNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_mark_every_notification_as_read_from_header(): void
    {
        $superAdmin = User::factory()->create([
            'role_id' => 3,
            'is_active' => true,
        ]);

        DB::table('notifications')->insert([
            'id' => (string) Str::uuid(),
            'type' => 'test-notification',
            'notifiable_type' => User::class,
            'notifiable_id' => $superAdmin->id,
            'data' => json_encode([
                'title' => 'Thông báo kiểm thử',
                'message' => 'Nội dung kiểm thử',
            ], JSON_UNESCAPED_UNICODE),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($superAdmin)
            ->get(route('admin.super-admin.manage.toppings.index'))
            ->assertOk()
            ->assertSee('Đã đọc tất cả')
            ->assertSee('data-notify-unread-count', false);

        $this->actingAs($superAdmin)
            ->postJson(route('notifications.mark-all-read'))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('notifications', [
            'notifiable_id' => $superAdmin->id,
            'read_at' => null,
        ]);

        $this->actingAs($superAdmin)
            ->get(route('admin.super-admin.manage.toppings.index'))
            ->assertOk()
            ->assertDontSee('<span class="root-notification-count"', false)
            ->assertDontSee('Đã đọc tất cả');
    }
}
