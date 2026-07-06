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
        ]);

        $response->assertCreated()->assertJsonPath('address.street', '123 Nguyễn Huệ');
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
        ])->assertCreated()->assertJsonPath('address.isDefault', true);

        $this->assertDatabaseHas('addresses', ['user_id' => $user->id, 'is_default' => 1]);
    }
}
