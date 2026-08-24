<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddressManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_persist_default_checkout_address(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('checkout.addresses.store'), [
            'name' => 'Nguyễn Văn A', 'phone' => '0901234567',
            'area' => 'Quận 1, TP Hồ Chí Minh', 'street' => '123 Nguyễn Huệ',
            'type' => 'Nhà Riêng', 'is_default' => true,
            'latitude' => 10.7769, 'longitude' => 106.7009,
        ]);

        $response->assertCreated()
            ->assertJsonPath('address.house_number', '123')
            ->assertJsonPath('address.street', 'Nguyễn Huệ');
        $this->assertDatabaseHas('addresses', ['user_id' => $user->id, 'detail' => '123 Nguyễn Huệ', 'is_default' => 1]);
        $this->assertSame('123 Nguyễn Huệ', $user->fresh()->address);
    }

    public function test_customer_cannot_update_another_users_address(): void
    {
        $owner = User::factory()->create();
        $address = Address::create([
            'user_id' => $owner->id, 'receiver_name' => 'Chủ địa chỉ', 'phone' => '0900000000',
            'province' => 'Hà Nội', 'detail' => 'Số 1', 'label' => 'Nhà Riêng', 'created_at' => now(),
        ]);

        $this->actingAs(User::factory()->create())->putJson(route('checkout.addresses.update', $address), [
            'name' => 'Người lạ', 'phone' => '0911111111', 'area' => 'Đà Nẵng',
            'street' => 'Số 2', 'type' => 'Văn Phòng', 'is_default' => false,
        ])->assertForbidden();
    }

    public function test_address_api_accepts_camel_case_default_flag_from_checkout_ui(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('checkout.addresses.store'), [
            'name' => 'Nguyễn Văn B', 'phone' => '0909999999',
            'area' => 'Thanh Hóa', 'street' => 'Đường Ngọc Mai',
            'type' => 'Nhà Riêng', 'isDefault' => true,
            'latitude' => 19.8067, 'longitude' => 105.7852,
        ])->assertCreated()->assertJsonPath('address.isDefault', true);

        $this->assertDatabaseHas('addresses', ['user_id' => $user->id, 'is_default' => 1]);
    }

    public function test_customer_can_update_their_checkout_address_with_coordinates(): void
    {
        $user = User::factory()->create();
        $address = Address::create([
            'user_id' => $user->id,
            'receiver_name' => 'Nguyễn Văn C',
            'phone' => '0901234567',
            'province' => 'Hà Nội',
            'detail' => '12 Phố Huế',
            'label' => 'Nhà',
            'latitude' => 21.0278,
            'longitude' => 105.8342,
            'is_default' => false,
            'created_at' => now(),
        ]);

        $this->actingAs($user)->putJson(route('checkout.addresses.update', $address), [
            'name' => 'Nguyễn Văn C',
            'phone' => '0901234567',
            'area' => 'Hà Nội',
            'house_number' => '25',
            'street' => 'Phố Huế',
            'type' => 'Văn phòng',
            'latitude' => 21.028,
            'longitude' => 105.835,
            'is_default' => true,
        ])->assertOk()
            ->assertJsonPath('address.latitude', 21.028)
            ->assertJsonPath('address.longitude', 105.835);

        $this->assertDatabaseHas('addresses', [
            'id' => $address->id,
            'detail' => '25 Phố Huế',
            'is_default' => 1,
        ]);
    }

    public function test_customer_can_update_saved_address_without_resubmitting_coordinates(): void
    {
        $user = User::factory()->create();
        $address = Address::create([
            'user_id' => $user->id,
            'receiver_name' => 'Nguyễn Văn C',
            'phone' => '0901234567',
            'province' => 'Thanh Hóa',
            'detail' => 'ngõ 910 Quang Trung',
            'label' => 'Nhà',
            'latitude' => 19.8067,
            'longitude' => 105.7852,
            'is_default' => true,
            'created_at' => now(),
        ]);

        $this->actingAs($user)->putJson(route('checkout.addresses.update', $address), [
            'name' => 'Nguyễn Văn C',
            'phone' => '0901234567',
            'area' => 'Thanh Hóa',
            'street' => 'ngõ 910 Quang Trung',
            'type' => 'Nhà',
            'is_default' => true,
        ])->assertOk();

        $this->assertDatabaseHas('addresses', [
            'id' => $address->id,
            'latitude' => 19.8067,
            'longitude' => 105.7852,
        ]);
    }

    public function test_checkout_address_rejects_missing_coordinates(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('checkout.addresses.store'), [
            'name' => 'Nguyễn Văn D',
            'phone' => '0901234567',
            'area' => 'Đà Nẵng',
            'street' => '10 Bạch Đằng',
            'type' => 'Nhà',
            'is_default' => true,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['latitude', 'longitude']);

        $this->assertDatabaseCount('addresses', 0);
    }

    public function test_customer_can_delete_their_profile_address(): void
    {
        $user = User::factory()->create();
        $address = Address::create([
            'user_id' => $user->id,
            'receiver_name' => 'Nguyễn Văn E',
            'phone' => '0901234567',
            'detail' => '30 Lê Lợi',
            'label' => 'Nhà',
            'is_default' => true,
            'created_at' => now(),
        ]);

        $this->actingAs($user)
            ->delete(route('profile.addresses.destroy', $address))
            ->assertRedirect(route('profile.addresses.index'));

        $this->assertDatabaseMissing('addresses', ['id' => $address->id]);
    }
}
