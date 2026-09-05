<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileAddressTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_view_address_book_and_sees_synced_profile_address(): void
    {
        $user = User::factory()->create([
            'address' => '254 Quảng Thắng',
            'area' => 'Phường Đông Quang, Tỉnh Thanh Hóa',
            'latitude' => 19.80,
            'longitude' => 105.77,
        ]);

        $response = $this->actingAs($user)->get(route('profile.addresses.index'));

        $response->assertStatus(200);
        $response->assertSee('Địa chỉ của bạn');
        $response->assertSee('254 Quảng Thắng');
        $this->assertDatabaseHas('addresses', [
            'user_id' => $user->id,
            'detail' => '254 Quảng Thắng',
            'is_default' => 1,
        ]);
    }

    public function test_customer_can_store_address_with_coordinates(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('profile.addresses.store'), [
            'receiver_name' => 'Nguyễn Văn A',
            'phone' => '0987654321',
            'label' => 'Công ty',
            'detail' => '123 Đường Lê Lợi',
            'ward' => 'Phường Điện Biên',
            'district' => 'Thành phố Thanh Hóa',
            'province' => 'Tỉnh Thanh Hóa',
            'latitude' => 19.801234,
            'longitude' => 105.771234,
            'is_default' => 1,
        ]);

        $response->assertRedirect(route('profile.addresses.index'));
        $this->assertDatabaseHas('addresses', [
            'user_id' => $user->id,
            'receiver_name' => 'Nguyễn Văn A',
            'latitude' => 19.801234,
            'longitude' => 105.771234,
            'is_default' => 1,
        ]);
    }

    public function test_customer_can_store_and_update_with_house_number_street_and_area(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('profile.addresses.store'), [
            'receiver_name' => 'Văn Minh Đỗ',
            'phone' => '0945606336',
            'label' => 'Nhà Riêng',
            'house_number' => '12/3',
            'street' => 'Đại Lộ Lê Lợi',
            'area' => 'Quảng Thắng, Phường Đông Quang, Tỉnh Thanh Hóa',
            'latitude' => 19.624989,
            'longitude' => 105.643109,
            'is_default' => 1,
        ]);

        $response->assertRedirect(route('profile.addresses.index'));
        $address = $user->addresses()->first();
        $this->assertNotNull($address);
        $this->assertEquals('12/3 Đại Lộ Lê Lợi', $address->detail);
        $this->assertEquals('Quảng Thắng, Phường Đông Quang, Tỉnh Thanh Hóa', $address->province);
        $this->assertEquals(19.624989, (float) $address->latitude);

        // Now test updating this address
        $updateResponse = $this->actingAs($user)->put(route('profile.addresses.update', $address), [
            'receiver_name' => 'Văn Minh Đỗ',
            'phone' => '0945606336',
            'label' => 'Văn Phòng',
            'house_number' => '',
            'street' => '254',
            'area' => 'Quảng Thắng, Phường Đông Quang, Tỉnh Thanh Hóa, Đông Vinh',
            'latitude' => 19.624989,
            'longitude' => 105.643109,
            'is_default' => 1,
        ]);

        $updateResponse->assertRedirect(route('profile.addresses.index'));
        $address->refresh();
        $this->assertEquals('254', $address->detail);
        $this->assertEquals('Quảng Thắng, Phường Đông Quang, Tỉnh Thanh Hóa, Đông Vinh', $address->province);
        $this->assertEquals('Văn Phòng', $address->label);
    }

    public function test_profile_clean_address_deduplicates_consecutive_words(): void
    {
        $result = \App\Http\Controllers\ProfileController::cleanAddressString('254 254 Quảng Thắng, Phường Đông Quang, Tỉnh Thanh Hóa, Đông Vinh, Đông Vinh, Phường Đông Quang, Tỉnh Thanh Hóa');
        $this->assertEquals('254 Quảng Thắng, Phường Đông Quang, Tỉnh Thanh Hóa, Đông Vinh', $result);
    }
}
