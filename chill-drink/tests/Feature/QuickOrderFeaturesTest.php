<?php

namespace Tests\Feature;

use App\Models\GroupOrder;
use App\Models\GroupOrderMember;
use App\Models\GroupOrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuickOrderFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_create_and_share_a_group_order(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('group-orders.store'), [
            'name' => 'Team Marketing',
            'note' => 'Giao tại văn phòng',
        ]);

        $group = GroupOrder::firstOrFail();
        $response->assertRedirect(route('group-orders.show', $group->code));
        $this->assertSame($user->id, $group->owner_id);
        $this->assertTrue($group->closes_at->between(now()->addMinutes(29), now()->addMinutes(31)));
        $this->get(route('group-orders.show', $group->code))->assertOk()->assertSee('Team Marketing');
        $this->get(route('group-orders.index'))->assertOk()->assertSee('data-group-countdown', false);
    }

    public function test_owner_can_choose_group_order_end_date_and_time(): void
    {
        $owner = User::factory()->create();
        $closesAt = now()->addHours(2)->startOfMinute();

        $this->actingAs($owner)->post(route('group-orders.store'), [
            'name' => 'Nhóm hẹn giờ',
            'closes_at' => $closesAt->format('Y-m-d H:i:s'),
        ])->assertRedirect();

        $this->assertTrue(GroupOrder::latest('id')->firstOrFail()->closes_at->equalTo($closesAt));
    }

    public function test_creating_a_new_group_cancels_the_owners_previous_open_group(): void
    {
        [$previous, $owner] = $this->openGroup();

        $this->actingAs($owner)->post(route('group-orders.store'), [
            'name' => 'Phòng mới',
            'closes_at' => now()->addHour()->format('Y-m-d H:i:s'),
        ])->assertRedirect();

        $this->assertSame('cancelled', $previous->fresh()->status);
        $this->assertDatabaseHas('group_orders', ['owner_id' => $owner->id, 'name' => 'Phòng mới', 'status' => 'open']);
    }

    public function test_group_order_cannot_close_less_than_five_minutes_after_creation(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)->from(route('group-orders.create'))->post(route('group-orders.store'), [
            'name' => 'Phòng quá ngắn',
            'closes_at' => now()->addSeconds(10)->format('Y-m-d H:i:s'),
        ])->assertRedirect(route('group-orders.create'))->assertSessionHasErrors('closes_at');

        $this->assertDatabaseMissing('group_orders', ['name' => 'Phòng quá ngắn']);
    }

    public function test_guest_must_login_before_opening_group_order_link(): void
    {
        $owner = User::factory()->create();
        $group = GroupOrder::create([
            'owner_id' => $owner->id,
            'name' => 'Nhóm cần đăng nhập',
            'code' => 'LOGIN123',
            'status' => 'open',
            'closes_at' => now()->addHour(),
        ]);

        $this->get(route('group-orders.show', $group->code))
            ->assertRedirect(route('login'));
    }

    public function test_expired_group_order_is_automatically_closed(): void
    {
        $user = User::factory()->create();
        $group = GroupOrder::create([
            'owner_id' => $user->id, 'name' => 'Đơn hết giờ', 'code' => 'EXPIRED1',
            'status' => 'open', 'closes_at' => now()->subSecond(),
        ]);

        $this->actingAs($user)->get(route('group-orders.show', $group->code))->assertOk()->assertSee('Đã đóng');
        $this->assertDatabaseHas('group_orders', ['id' => $group->id, 'status' => 'closed']);
    }

    public function test_members_can_detect_when_group_owner_leaves_the_room(): void
    {
        [$group, $owner] = $this->openGroup();
        $member = User::factory()->create();

        $this->actingAs($owner)
            ->post(route('group-orders.presence', $group->code))
            ->assertOk()
            ->assertJsonPath('owner_present', true);

        $this->travel(GroupOrder::OWNER_PRESENCE_SECONDS + 1)->seconds();

        $this->actingAs($member)
            ->post(route('group-orders.presence', $group->code))
            ->assertOk()
            ->assertJsonPath('owner_present', false)
            ->assertJsonPath('is_open', true);
    }

    public function test_group_order_cannot_exceed_twenty_members(): void
    {
        $owner = User::factory()->create();
        $group = GroupOrder::create([
            'owner_id' => $owner->id, 'name' => 'Phòng đầy', 'code' => 'FULL0020',
            'status' => 'open', 'closes_at' => now()->addHour(),
        ]);

        foreach (range(1, GroupOrder::MAX_MEMBERS) as $number) {
            GroupOrderMember::create([
                'group_order_id' => $group->id,
                'name' => 'Thành viên '.$number,
                'member_token' => 'member-token-'.$number,
            ]);
        }

        $this->actingAs(User::factory()->create())
            ->post(route('group-orders.join', $group->code), ['name' => 'Người thứ 51'])
            ->assertStatus(422);

        $this->assertSame(GroupOrder::MAX_MEMBERS, $group->members()->count());
    }

    public function test_same_account_cannot_join_group_twice(): void
    {
        [$group, $owner] = $this->openGroup();
        $member = User::factory()->create();
        $this->actingAs($member)->post(route('group-orders.join', $group->code), ['name' => 'Tên đầu'])->assertRedirect();
        $this->post(route('group-orders.join', $group->code), ['name' => 'Tên mới'])->assertRedirect();
        $this->assertSame(1, $group->members()->where('user_id', $member->id)->count());
        $this->assertSame('Tên mới', $group->members()->where('user_id', $member->id)->value('name'));
    }

    public function test_group_item_cannot_exceed_product_stock(): void
    {
        [$group] = $this->openGroup();
        $member = User::factory()->create();
        GroupOrderMember::create(['group_order_id' => $group->id, 'user_id' => $member->id, 'name' => 'Bạn A', 'member_token' => 'stock-member']);
        $product = Product::factory()->create(['status' => true, 'stock' => 1]);

        $this->actingAs($member)->post(route('group-orders.items.store', $group->code), [
            'product_id' => $product->id, 'size' => 'M', 'sugar_level' => 50,
            'ice_level' => 50, 'quantity' => 2,
        ])->assertStatus(422);
        $this->assertDatabaseCount('group_order_items', 0);
    }

    public function test_member_cannot_remove_another_members_item(): void
    {
        [$group] = $this->openGroup();
        $ownerOfItem = User::factory()->create();
        $attacker = User::factory()->create();
        $first = GroupOrderMember::create(['group_order_id' => $group->id, 'user_id' => $ownerOfItem->id, 'name' => 'A', 'member_token' => 'owner-item']);
        GroupOrderMember::create(['group_order_id' => $group->id, 'user_id' => $attacker->id, 'name' => 'B', 'member_token' => 'attacker']);
        $item = GroupOrderItem::create(['group_order_id' => $group->id, 'group_order_member_id' => $first->id,
            'product_id' => Product::factory()->create()->id, 'size' => 'M', 'quantity' => 1, 'unit_price' => 50000]);

        $this->actingAs($attacker)->delete(route('group-orders.items.destroy', [$group->code, $item]))->assertForbidden();
        $this->assertDatabaseHas('group_order_items', ['id' => $item->id]);
    }

    public function test_member_can_increment_their_own_group_item(): void
    {
        [$group] = $this->openGroup();
        $user = User::factory()->create();
        $member = GroupOrderMember::create(['group_order_id' => $group->id, 'user_id' => $user->id, 'name' => 'Bạn A', 'member_token' => 'increment-owner']);
        $product = Product::factory()->create(['status' => true, 'stock' => 5]);
        $item = GroupOrderItem::create(['group_order_id' => $group->id, 'group_order_member_id' => $member->id,
            'product_id' => $product->id, 'size' => 'S', 'quantity' => 1, 'unit_price' => 45000]);

        $this->actingAs($user)->patch(route('group-orders.items.increment', [$group->code, $item]))->assertRedirect();

        $this->assertSame(2, $item->fresh()->quantity);
    }

    public function test_group_can_only_be_closed_once_and_price_is_refreshed(): void
    {
        [$group, $owner] = $this->openGroup();
        $member = GroupOrderMember::create(['group_order_id' => $group->id, 'user_id' => $owner->id, 'name' => 'Chủ nhóm', 'member_token' => 'close-owner']);
        $product = Product::factory()->create(['status' => true, 'stock' => 10, 'price' => 45000]);
        $item = GroupOrderItem::create(['group_order_id' => $group->id, 'group_order_member_id' => $member->id,
            'product_id' => $product->id, 'size' => 'M', 'quantity' => 1, 'unit_price' => 1, 'toppings' => []]);

        $this->actingAs($owner)->post(route('group-orders.close', $group->code))->assertRedirect(route('cart.index'));
        $this->assertSame('closed', $group->fresh()->status);
        $this->assertSame(50000, $item->fresh()->unit_price);
        $this->post(route('group-orders.close', $group->code))->assertStatus(422);
    }

    public function test_group_cart_can_be_adjusted_and_personal_cart_is_restored_when_cancelled(): void
    {
        [$group, $owner] = $this->openGroup();
        $member = GroupOrderMember::create(['group_order_id' => $group->id, 'user_id' => $owner->id, 'name' => 'Chủ nhóm', 'member_token' => 'cart-owner']);
        $product = Product::factory()->create(['status' => true, 'stock' => 10]);
        $item = GroupOrderItem::create(['group_order_id' => $group->id, 'group_order_member_id' => $member->id,
            'product_id' => $product->id, 'size' => 'S', 'quantity' => 1, 'unit_price' => (int) $product->price]);
        $personalCart = ['personal-key' => ['product_id' => $product->id, 'quantity' => 1, 'price' => 1000]];

        $this->actingAs($owner)->withSession(['cart' => $personalCart])->post(route('group-orders.close', $group->code));
        $groupKey = 'group-'.$group->id.'-'.$item->id;
        $this->patch(route('cart.update', $groupKey), ['quantity' => 5])->assertRedirect();
        $this->assertSame(5, session('cart')[$groupKey]['quantity']);
        $this->post(route('group-orders.cancel', $group->code))->assertRedirect(route('group-orders.index'));
        $this->assertSame($personalCart, session('cart'));
        $this->assertSame('cancelled', $group->fresh()->status);
    }

    public function test_group_checkout_links_order_deducts_stock_and_restores_personal_cart(): void
    {
        [$group, $owner] = $this->openGroup();
        $member = GroupOrderMember::create(['group_order_id' => $group->id, 'user_id' => $owner->id, 'name' => 'Chủ nhóm', 'member_token' => 'checkout-owner']);
        $product = Product::factory()->create(['status' => true, 'stock' => 8, 'price' => 40000]);
        GroupOrderItem::create(['group_order_id' => $group->id, 'group_order_member_id' => $member->id,
            'product_id' => $product->id, 'size' => 'S', 'quantity' => 3, 'unit_price' => 40000, 'toppings' => []]);
        $personalCart = ['saved-personal' => ['product_id' => $product->id, 'quantity' => 1, 'price' => 1000]];
        $scheduledAt = now()->addDay()->setTime(10, 0)->startOfMinute();

        $this->actingAs($owner)->withSession(['cart' => $personalCart])->post(route('group-orders.close', $group->code));
        $this->assertDatabaseHas('group_orders', ['id' => $group->id, 'status' => 'closed']);
        $response = $this->post(route('checkout.process'), [
            'payment_method' => 'cod', 'shipping_method_ui' => 'standard',
            'shipping_address_ui' => '123 Nguyễn Huệ', 'shipping_area_ui' => 'Quận 1',
            'scheduled_at' => $scheduledAt->format('Y-m-d H:i:s'),
        ]);

        $this->assertDatabaseHas('group_orders', ['id' => $group->id]);
        $group->refresh();
        $this->assertNotNull($group->order_id);
        $this->assertSame('ordered', $group->status);
        $this->assertSame(5, (int) $product->fresh()->stock);
        $this->assertTrue($group->order->scheduled_at->equalTo($scheduledAt));
        $this->assertSame($personalCart, session('cart'));
        $response->assertRedirect(route('checkout.success', $group->order_id));
    }

    public function test_customer_can_toggle_a_favorite_product(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)->post(route('favorites.toggle', $product))->assertRedirect();
        $this->assertDatabaseHas('favorites', ['user_id' => $user->id, 'product_id' => $product->id]);

        $this->post(route('favorites.toggle', $product))->assertRedirect();
        $this->assertDatabaseMissing('favorites', ['user_id' => $user->id, 'product_id' => $product->id]);
    }

    private function openGroup(): array
    {
        $owner = User::factory()->create();
        $group = GroupOrder::create([
            'owner_id' => $owner->id, 'name' => 'Nhóm kiểm thử', 'code' => strtoupper(fake()->unique()->bothify('TEST####')),
            'status' => 'open', 'closes_at' => now()->addHour(),
        ]);
        return [$group, $owner];
    }
}
