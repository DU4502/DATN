<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Conversation;
use App\Models\Order;
use App\Models\User;
use App\Support\ChatHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatHelperTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_chat_does_not_use_customer_as_system_message_sender_when_no_staff_exists(): void
    {
        $customer = User::factory()->create([
            'role_id' => 1,
            'is_active' => true,
        ]);
        $branch = Branch::create([
            'name' => 'Chi nhánh Chat Test',
            'code' => 'CHAT-TEST',
            'address' => 'Quận 1',
            'status' => true,
        ]);
        $order = Order::create([
            'order_code' => 'CHAT-ORDER-001',
            'user_id' => $customer->id,
            'branch_id' => $branch->id,
            'subtotal' => 30000,
            'shipping_fee' => 0,
            'discount' => 0,
            'total' => 30000,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);

        $this->actingAs($customer);

        ChatHelper::ensureChatWithOrderBranch($order);

        $conversation = Conversation::query()->firstOrFail();

        $this->assertSame($branch->id, $conversation->branch_id);
        $this->assertDatabaseCount('messages', 0);
        $this->assertDatabaseMissing('messages', ['sender_id' => $customer->id]);
    }

    public function test_select_branch_does_not_create_customer_authored_system_message_without_staff(): void
    {
        $customer = User::factory()->create([
            'role_id' => 1,
            'is_active' => true,
        ]);
        $branch = Branch::create([
            'name' => 'Chi nhánh Select Test',
            'code' => 'SELECT-TEST',
            'address' => 'Quận 1',
            'status' => true,
        ]);
        $conversation = Conversation::create([
            'user_id' => $customer->id,
            'status' => 'open',
        ]);

        $response = $this->actingAs($customer)->postJson(route('chat.select-branch'), [
            'conversation_id' => $conversation->id,
            'branch_id' => $branch->id,
        ]);

        $response->assertOk()->assertJsonPath('message', null);
        $this->assertDatabaseCount('messages', 0);
        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'branch_id' => $branch->id,
        ]);
    }
}
