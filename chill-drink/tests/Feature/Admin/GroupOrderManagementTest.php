<?php

namespace Tests\Feature\Admin;

use App\Models\GroupOrder;
use App\Models\GroupOrderMember;
use App\Models\GroupOrderMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupOrderManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_group_orders_and_their_details(): void
    {
        $admin = User::factory()->create(['role_id' => 3]);
        $owner = User::factory()->create();
        $group = GroupOrder::create([
            'owner_id' => $owner->id,
            'name' => 'Team văn phòng',
            'code' => 'ADMIN123',
            'status' => 'open',
            'closes_at' => now()->addMinutes(30),
        ]);
        $ownerMember = GroupOrderMember::create([
            'group_order_id' => $group->id,
            'user_id' => $owner->id,
            'name' => 'Chủ nhóm',
            'member_token' => 'admin-group-owner',
        ]);
        GroupOrderMessage::create([
            'group_order_id' => $group->id,
            'sender_member_id' => $ownerMember->id,
            'content' => 'Tin nhắn để Super Admin giám sát',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.group-orders.index'))
            ->assertOk()
            ->assertSee('ADMIN123')
            ->assertSee('Team văn phòng');

        $this->get(route('admin.group-orders.show', $group))
            ->assertOk()
            ->assertSee('Chủ nhóm')
            ->assertSee('1 / 20 thành viên')
            ->assertSee('Lịch sử trò chuyện')
            ->assertSee('Tin nhắn để Super Admin giám sát');
    }

    public function test_regular_admin_cannot_view_group_orders(): void
    {
        $admin = User::factory()->create(['role_id' => 2]);

        $this->actingAs($admin)
            ->get(route('admin.group-orders.index'))
            ->assertRedirect(route('admin.dashboard'));
    }
}
