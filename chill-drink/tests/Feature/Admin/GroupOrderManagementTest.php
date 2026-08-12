<?php

namespace Tests\Feature\Admin;

use App\Models\GroupOrder;
use App\Models\GroupOrderMember;
use App\Models\Branch;
use App\Models\GroupOrderMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupOrderManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_group_orders_and_their_details(): void
    {
        $branch = Branch::create([
            'name' => 'Chi nhánh Group Test',
            'code' => 'GROUP-TEST',
            'address' => 'Quận 1',
            'status' => true,
        ]);
        $admin = User::factory()->create(['role_id' => 2, 'branch_id' => $branch->id]);
        $owner = User::factory()->create();
        $group = GroupOrder::create([
            'owner_id' => $owner->id,
            'branch_id' => $branch->id,
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

    public function test_admin_cannot_view_group_order_from_another_branch_or_without_branch(): void
    {
        $branchA = Branch::create(['name' => 'Chi nhánh A', 'code' => 'GROUP-A', 'address' => 'A', 'status' => true]);
        $branchB = Branch::create(['name' => 'Chi nhánh B', 'code' => 'GROUP-B', 'address' => 'B', 'status' => true]);
        $customer = User::factory()->create();
        $group = GroupOrder::create([
            'owner_id' => $customer->id,
            'branch_id' => $branchB->id,
            'name' => 'Phòng chi nhánh B',
            'code' => 'GROUP-B-ROOM',
            'status' => 'open',
            'closes_at' => now()->addMinutes(30),
        ]);

        $adminA = User::factory()->create(['role_id' => 2, 'branch_id' => $branchA->id]);
        $this->actingAs($adminA)
            ->get(route('admin.group-orders.index'))
            ->assertOk()
            ->assertDontSee('GROUP-B-ROOM');
        $this->get(route('admin.group-orders.show', $group))->assertForbidden();

        $unassignedAdmin = User::factory()->create(['role_id' => 2, 'branch_id' => null]);
        $this->actingAs($unassignedAdmin)
            ->get(route('admin.group-orders.index'))
            ->assertOk()
            ->assertDontSee('GROUP-B-ROOM');
    }
}
