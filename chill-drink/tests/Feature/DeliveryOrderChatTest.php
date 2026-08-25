<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\DeliveryOrderMessage;
use App\Models\Order;
use App\Models\Shipper;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryOrderChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_and_assigned_shipper_can_send_and_load_messages(): void
    {
        [$customer, $shipperUser, $shipper, $order] = $this->chatContext();

        $this->actingAs($customer)
            ->postJson(route('orders.delivery-chat.send', $order), ['content' => 'Tôi đang chờ ở cổng.'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message.sender_type', 'customer');

        $this->actingAs($shipperUser)
            ->postJson(route('shipper.orders.delivery-chat.send', $order), ['content' => 'Tôi sắp tới.'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message.sender_type', 'shipper');

        $messages = $order->fresh()->deliveryMessages()->with(['order', 'sender'])->oldest('id')->get();

        $this->assertCount(2, $messages);
        $this->assertTrue($messages[0]->order->is($order));
        $this->assertTrue($messages[0]->sender->is($customer));
        $this->assertTrue($messages[1]->sender->is($shipperUser));
        $this->assertSame($shipper->id, $order->fresh()->shipper->id);
    }

    public function test_guest_sender_is_nullable_through_the_existing_chat_flow(): void
    {
        [, , , $order] = $this->chatContext(guest: true);

        $this->postJson(route('checkout.guest.delivery-chat.send', [
            'order' => $order,
            'token' => $order->guest_token,
        ]), ['content' => 'Tôi đứng trước cửa.'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message.sender_name', 'Khách hàng');

        $message = DeliveryOrderMessage::query()->sole();

        $this->assertNull($message->sender_user_id);
        $this->assertNull($message->sender);
        $this->assertTrue($message->order->is($order));
    }

    public function test_unrelated_users_and_shippers_cannot_access_an_order_chat(): void
    {
        [, , , $order, $branch] = $this->chatContext();

        $unrelatedCustomer = User::factory()->create(['role_id' => 1]);

        $this->actingAs($unrelatedCustomer)
            ->getJson(route('orders.delivery-chat.messages', $order))
            ->assertForbidden();

        $unrelatedShipperUser = User::factory()->create([
            'role_id' => User::SHIPPER_ROLE_ID,
            'branch_id' => $branch->id,
        ]);

        Shipper::create([
            'user_id' => $unrelatedShipperUser->id,
            'code' => 'SHP-CHAT-OTHER',
            'phone' => '0900000001',
            'vehicle_type' => 'bike',
            'status' => 'online',
            'station_branch_id' => $branch->id,
        ]);

        $this->actingAs($unrelatedShipperUser)
            ->getJson(route('shipper.orders.delivery-chat.messages', $order))
            ->assertForbidden();
    }

    private function chatContext(bool $guest = false): array
    {
        $branch = Branch::create([
            'name' => 'Delivery Chat Branch',
            'code' => 'CHAT-BR',
            'status' => true,
        ]);

        $customer = User::factory()->create(['role_id' => 1]);
        $shipperUser = User::factory()->create([
            'role_id' => User::SHIPPER_ROLE_ID,
            'branch_id' => $branch->id,
        ]);

        $shipper = Shipper::create([
            'user_id' => $shipperUser->id,
            'code' => 'SHP-CHAT-MAIN',
            'phone' => '0900000000',
            'vehicle_type' => 'bike',
            'status' => 'online',
            'station_branch_id' => $branch->id,
        ]);

        $order = Order::create([
            'order_code' => $guest ? 'CHAT-GUEST-ORDER' : 'CHAT-USER-ORDER',
            'user_id' => $guest ? null : $customer->id,
            'guest_name' => $guest ? 'Guest Chat' : null,
            'guest_phone' => $guest ? '0900000002' : null,
            'guest_email' => $guest ? 'guest-chat@example.test' : null,
            'guest_token' => $guest ? 'guest-chat-token' : null,
            'shipper_id' => $shipper->id,
            'fulfillment_type' => 'delivery',
            'branch_id' => $branch->id,
            'subtotal' => 10000,
            'shipping_fee' => 0,
            'discount' => 0,
            'total' => 10000,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'status' => 'delivering',
        ]);

        return [$customer, $shipperUser, $shipper, $order, $branch];
    }
}
