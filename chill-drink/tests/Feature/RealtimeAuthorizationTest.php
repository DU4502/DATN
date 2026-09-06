<?php

namespace Tests\Feature;

use App\Events\GroupOrderGroupMessageSent;
use App\Events\MessageSent;
use App\Events\OrderStatusUpdated;
use App\Events\OrderCreated;
use App\Models\Branch;
use App\Models\Conversation;
use App\Models\GroupOrder;
use App\Models\GroupOrderMember;
use App\Models\GroupOrderMessage;
use App\Models\Message;
use App\Models\Order;
use App\Models\Shipper;
use App\Models\User;
use App\Support\OrderStatus;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RealtimeAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'test-key',
            'broadcasting.connections.reverb.secret' => 'test-secret',
            'broadcasting.connections.reverb.app_id' => 'test-app',
        ]);
        DB::table('roles')->updateOrInsert(
            ['id' => 4],
            ['name' => 'cskh', 'description' => 'Nhân viên CSKH']
        );
    }

    public function test_group_order_channel_follows_membership_and_management_branch_scope(): void
    {
        $branch = $this->branch('RT-GROUP-A');
        $otherBranch = $this->branch('RT-GROUP-B');
        $owner = User::factory()->create(['role_id' => 1]);
        $participant = User::factory()->create(['role_id' => 1]);
        $unrelated = User::factory()->create(['role_id' => 1]);
        $group = $this->groupOrder($owner, $branch);
        GroupOrderMember::create([
            'group_order_id' => $group->id,
            'user_id' => $participant->id,
            'name' => 'Participant',
            'member_token' => 'participant-token',
        ]);
        $sameBranchAdmin = User::factory()->create(['role_id' => 2, 'branch_id' => $branch->id]);
        $otherBranchAdmin = User::factory()->create(['role_id' => 2, 'branch_id' => $otherBranch->id]);
        $sameBranchStaff = User::factory()->create(['role_id' => User::STAFF_ROLE_ID, 'branch_id' => $branch->id]);
        $otherBranchStaff = User::factory()->create(['role_id' => User::STAFF_ROLE_ID, 'branch_id' => $otherBranch->id]);
        $superAdmin = User::factory()->create(['role_id' => 3, 'branch_id' => null]);
        $channel = 'private-group-order.'.$group->id;

        foreach ([$owner, $participant, $sameBranchAdmin, $sameBranchStaff, $superAdmin] as $allowedUser) {
            $this->authorizeChannel($allowedUser, $channel)->assertOk();
        }
        foreach ([$unrelated, $otherBranchAdmin, $otherBranchStaff] as $deniedUser) {
            $this->authorizeChannel($deniedUser, $channel)->assertForbidden();
        }
    }

    public function test_conversation_channel_matches_customer_assignment_and_branch_rules(): void
    {
        $branch = $this->branch('RT-CHAT-A');
        $otherBranch = $this->branch('RT-CHAT-B');
        $customer = User::factory()->create(['role_id' => 1]);
        $otherCustomer = User::factory()->create(['role_id' => 1]);
        $assignedCskh = User::factory()->create(['role_id' => 4, 'branch_id' => $branch->id]);
        $otherCskh = User::factory()->create(['role_id' => 4, 'branch_id' => $branch->id]);
        $sameBranchAdmin = User::factory()->create(['role_id' => 2, 'branch_id' => $branch->id]);
        $otherBranchAdmin = User::factory()->create(['role_id' => 2, 'branch_id' => $otherBranch->id]);
        $sameBranchStaff = User::factory()->create(['role_id' => User::STAFF_ROLE_ID, 'branch_id' => $branch->id]);
        $shipper = User::factory()->create(['role_id' => User::SHIPPER_ROLE_ID, 'branch_id' => $branch->id]);
        $superAdmin = User::factory()->create(['role_id' => 3, 'branch_id' => null]);
        $conversation = Conversation::create([
            'user_id' => $customer->id,
            'branch_id' => $branch->id,
            'cskh_id' => $assignedCskh->id,
            'status' => 'open',
        ]);
        $channel = 'private-conversation.'.$conversation->id;

        foreach ([$customer, $assignedCskh, $sameBranchAdmin, $superAdmin] as $allowedUser) {
            $this->authorizeChannel($allowedUser, $channel)->assertOk();
        }
        foreach ([$otherCustomer, $otherCskh, $otherBranchAdmin, $sameBranchStaff, $shipper] as $deniedUser) {
            $this->authorizeChannel($deniedUser, $channel)->assertForbidden();
        }

        $conversation->update(['cskh_id' => null]);
        $this->authorizeChannel($sameBranchStaff, $channel)->assertOk();
    }

    public function test_admin_notification_channels_keep_admin_branch_scope_and_super_admin_global_scope(): void
    {
        $branch = $this->branch('RT-ADMIN-A');
        $otherBranch = $this->branch('RT-ADMIN-B');
        $admin = User::factory()->create(['role_id' => 2, 'branch_id' => $branch->id]);
        $staff = User::factory()->create(['role_id' => User::STAFF_ROLE_ID, 'branch_id' => $branch->id]);
        $superAdmin = User::factory()->create(['role_id' => 3, 'branch_id' => null]);

        $this->authorizeChannel($admin, 'private-admin-notifications.'.$branch->id)->assertOk();
        $this->authorizeChannel($admin, 'private-admin-notifications.'.$otherBranch->id)->assertForbidden();
        $this->authorizeChannel($admin, 'private-admin-notifications')->assertForbidden();

        $this->authorizeChannel($staff, 'private-admin-notifications.'.$branch->id)->assertOk();
        $this->authorizeChannel($staff, 'private-admin-notifications.'.$otherBranch->id)->assertForbidden();
        $this->authorizeChannel($staff, 'private-admin-notifications')->assertForbidden();

        $this->authorizeChannel($superAdmin, 'private-admin-notifications')->assertOk();
        $this->authorizeChannel($superAdmin, 'private-admin-notifications.'.$branch->id)->assertOk();
        $this->authorizeChannel($superAdmin, 'private-admin-notifications.'.$otherBranch->id)->assertOk();

        $this->authorizeChannel($staff, 'private-staff-orders.'.$branch->id)->assertOk();
        $this->authorizeChannel($staff, 'private-staff-orders.'.$otherBranch->id)->assertForbidden();
        $this->authorizeChannel($admin, 'private-staff-orders.'.$branch->id)->assertForbidden();
        $this->authorizeChannel($superAdmin, 'private-super-admin-orders')->assertOk();
        $this->authorizeChannel($admin, 'private-super-admin-orders')->assertForbidden();

        $shipper = User::factory()->create(['role_id' => User::SHIPPER_ROLE_ID, 'branch_id' => $branch->id]);
        $otherShipper = User::factory()->create(['role_id' => User::SHIPPER_ROLE_ID, 'branch_id' => $branch->id]);
        $this->authorizeChannel($shipper, 'private-shipper-orders.'.$shipper->id)->assertOk();
        $this->authorizeChannel($otherShipper, 'private-shipper-orders.'.$shipper->id)->assertForbidden();
        $this->authorizeChannel($admin, 'private-shipper-orders.'.$shipper->id)->assertForbidden();
    }

    public function test_private_user_channel_only_allows_the_matching_authenticated_user(): void
    {
        $customer = User::factory()->create(['role_id' => 1]);
        $otherCustomer = User::factory()->create(['role_id' => 1]);
        $staff = User::factory()->create(['role_id' => User::STAFF_ROLE_ID]);
        $shipper = User::factory()->create(['role_id' => User::SHIPPER_ROLE_ID]);
        $channel = 'private-user.'.$customer->id;

        $this->authorizeChannel($customer, $channel)->assertOk();
        foreach ([$otherCustomer, $staff, $shipper] as $deniedUser) {
            $this->authorizeChannel($deniedUser, $channel)->assertForbidden();
        }
    }

    public function test_broadcast_auth_rejects_an_unauthenticated_request(): void
    {
        $this->postJson('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-admin-notifications',
        ])->assertForbidden();
    }

    public function test_group_order_event_broadcasts_only_group_messages_on_the_matching_channel(): void
    {
        $branch = $this->branch('RT-GROUP-EVENT');
        $owner = User::factory()->create(['role_id' => 1]);
        $group = $this->groupOrder($owner, $branch);
        $sender = GroupOrderMember::create([
            'group_order_id' => $group->id,
            'user_id' => $owner->id,
            'name' => 'Owner',
            'member_token' => 'owner-token',
        ]);
        $recipient = GroupOrderMember::create([
            'group_order_id' => $group->id,
            'user_id' => User::factory()->create(['role_id' => 1])->id,
            'name' => 'Recipient',
            'member_token' => 'recipient-token',
        ]);
        $groupMessage = GroupOrderMessage::create([
            'group_order_id' => $group->id,
            'sender_member_id' => $sender->id,
            'content' => 'Tin nhắn chung',
        ]);
        $privateMessage = GroupOrderMessage::create([
            'group_order_id' => $group->id,
            'sender_member_id' => $sender->id,
            'recipient_member_id' => $recipient->id,
            'content' => 'Tin nhắn riêng',
        ]);

        $event = new GroupOrderGroupMessageSent($groupMessage);
        $this->assertSame('private-group-order.'.$group->id, $event->broadcastOn()->name);
        $this->assertSame('group-order.message.sent', $event->broadcastAs());
        $this->assertTrue($event->broadcastWhen());
        $this->assertFalse((new GroupOrderGroupMessageSent($privateMessage))->broadcastWhen());
    }

    public function test_conversation_and_order_events_match_authorized_channel_names(): void
    {
        $branch = $this->branch('RT-EVENTS');
        $customer = User::factory()->create(['role_id' => 1]);
        $conversation = Conversation::create([
            'user_id' => $customer->id,
            'branch_id' => $branch->id,
            'status' => 'open',
        ]);
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $customer->id,
            'content' => 'Issue/chat realtime',
        ]);
        $messageEvent = new MessageSent($message);
        $messageChannels = $this->privateChannelNames($messageEvent->broadcastOn());

        $this->assertSame('message-sent', $messageEvent->broadcastAs());
        $this->assertSame(['private-conversation.'.$conversation->id], $messageChannels);
        $this->authorizeChannel($customer, 'private-conversation.'.$conversation->id)->assertOk();

        $order = Order::create([
            'order_code' => 'RT-ORDER-EVENT',
            'user_id' => $customer->id,
            'branch_id' => $branch->id,
            'fulfillment_type' => 'delivery',
            'subtotal' => 100000,
            'shipping_fee' => 0,
            'discount' => 0,
            'total' => 100000,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'status' => OrderStatus::CONFIRMED,
        ]);
        $shipperUser = User::factory()->create([
            'role_id' => User::SHIPPER_ROLE_ID,
            'branch_id' => $branch->id,
        ]);
        $shipper = Shipper::create([
            'user_id' => $shipperUser->id,
            'code' => 'RT-SHIPPER-'.uniqid(),
            'phone' => '0900000001',
            'status' => 'available',
        ]);
        $order->update(['shipper_id' => $shipper->id]);
        $orderEvent = new OrderStatusUpdated($order);
        $orderChannels = $this->privateChannelNames($orderEvent->broadcastOn());

        $this->assertSame('order.status.updated', $orderEvent->broadcastAs());
        $this->assertContains('private-admin-notifications.'.$branch->id, $orderChannels);
        $this->assertContains('private-user.'.$customer->id, $orderChannels);
        $this->assertContains('private-order.'.$order->id, $orderChannels);
        $this->assertContains('private-shipper-orders.'.$shipperUser->id, $orderChannels);
        $this->assertNotContains('private-admin-notifications', $orderChannels);

        $createdChannels = $this->privateChannelNames((new OrderCreated($order))->broadcastOn());
        $this->assertContains('private-staff-orders.'.$branch->id, $createdChannels);
        $this->assertContains('private-super-admin-orders', $createdChannels);
        $this->assertContains('private-admin-notifications.'.$branch->id, $createdChannels);
    }

    private function authorizeChannel(User $user, string $channel)
    {
        return $this->actingAs($user)->postJson('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => $channel,
        ]);
    }

    private function privateChannelNames(array $channels): array
    {
        return collect($channels)
            ->filter(fn ($channel) => $channel instanceof PrivateChannel)
            ->map(fn (PrivateChannel $channel) => $channel->name)
            ->values()
            ->all();
    }

    private function groupOrder(User $owner, Branch $branch): GroupOrder
    {
        return GroupOrder::create([
            'owner_id' => $owner->id,
            'branch_id' => $branch->id,
            'name' => 'Realtime group '.$branch->code,
            'code' => 'GR-'.$branch->code,
            'status' => 'open',
            'closes_at' => now()->addMinutes(30),
        ]);
    }

    private function branch(string $code): Branch
    {
        return Branch::create([
            'name' => $code,
            'code' => $code,
            'status' => true,
        ]);
    }
}
