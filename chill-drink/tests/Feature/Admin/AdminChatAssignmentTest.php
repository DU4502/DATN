<?php

namespace Tests\Feature\Admin;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminChatAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_cskh_reply_assigns_conversation_and_later_cskh_cannot_take_it_over(): void
    {
        DB::table('roles')->updateOrInsert(
            ['id' => 4],
            ['name' => 'cskh', 'description' => 'Nhân viên CSKH']
        );

        $customer = User::factory()->create(['role_id' => 1]);
        $firstCskh = User::factory()->create(['role_id' => 4, 'is_active' => true]);
        $secondCskh = User::factory()->create(['role_id' => 4, 'is_active' => true]);
        $conversation = Conversation::create([
            'user_id' => $customer->id,
            'status' => 'open',
        ]);

        $this->actingAs($firstCskh)
            ->postJson(route('admin.chat.reply', $conversation), ['content' => 'Tôi sẽ hỗ trợ bạn.'])
            ->assertOk();

        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'cskh_id' => $firstCskh->id,
        ]);

        $this->actingAs($secondCskh)
            ->postJson(route('admin.chat.reply', $conversation), ['content' => 'Tôi tiếp nhận nhé.'])
            ->assertForbidden();

        $this->assertDatabaseCount('messages', 1);
    }
}
