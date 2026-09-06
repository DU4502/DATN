<?php

namespace Tests\Feature\Admin;

use App\Models\Branch;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class BranchManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_delete_an_empty_branch(): void
    {
        $superAdmin = User::factory()->create(['role_id' => 3]);
        $branch = Branch::create([
            'name' => 'Empty Branch',
            'code' => 'EMPTY-BRANCH',
            'address' => 'Test address',
            'status' => true,
        ]);

        $this->actingAs($superAdmin)
            ->delete(route('admin.branches.destroy', $branch))
            ->assertRedirect(route('admin.super-admin').'#branch-ranking')
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('branches', ['id' => $branch->id]);
    }

    public function test_branch_with_conversation_cannot_be_deleted(): void
    {
        $superAdmin = User::factory()->create(['role_id' => 3]);
        $customer = User::factory()->create();
        $branch = Branch::create([
            'name' => 'Chat Branch',
            'code' => 'CHAT-BRANCH',
            'address' => 'Test address',
            'status' => true,
        ]);
        Conversation::create([
            'user_id' => $customer->id,
            'branch_id' => $branch->id,
            'subject' => 'Delivery question',
            'status' => 'open',
        ]);

        $this->assertTrue($branch->conversations()->exists());

        $this->actingAs($superAdmin)
            ->delete(route('admin.branches.destroy', $branch))
            ->assertRedirect(route('admin.super-admin').'#branch-ranking')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('branches', ['id' => $branch->id]);
    }

    public function test_super_admin_can_set_branch_status_explicitly(): void
    {
        Event::fake();

        $superAdmin = User::factory()->create(['role_id' => 3]);
        $branch = Branch::create([
            'name' => 'Realtime Branch',
            'code' => 'REALTIME-BRANCH',
            'address' => 'Test address',
            'latitude' => 19.807157,
            'longitude' => 105.776156,
            'status' => false,
        ]);

        $this->actingAs($superAdmin)
            ->patchJson(route('admin.branches.toggle-status', $branch), ['status' => true])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', true);

        $this->assertTrue($branch->fresh()->status);

        $this->actingAs($superAdmin)
            ->patchJson(route('admin.branches.toggle-status', $branch), ['status' => true])
            ->assertOk()
            ->assertJsonPath('status', true);

        $this->assertTrue($branch->fresh()->status);
    }
}
