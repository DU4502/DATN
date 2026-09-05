<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Order;
use App\Models\Shipper;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestShipperSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::query()->where('status', true)->orderBy('id')->firstOrFail();

        $user = User::query()->updateOrCreate(
            ['email' => 'dangbaovylhn@gmail.com'],
            [
                'name' => 'Shipper Test Đặng Bảo Vy',
                'password' => Hash::make('12345678'),
                'role_id' => User::SHIPPER_ROLE_ID,
                'branch_id' => $branch->id,
                'phone' => '0900000099',
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        $shipper = Shipper::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'code' => 'TEST-SHIPPER-01',
                'phone' => '0900000099',
                'vehicle_type' => 'bike',
                'license_plate' => 'TEST-99',
                'status' => 'online',
                'station_branch_id' => $branch->id,
            ],
        );

        $customer = User::query()->where('email', 'user@chilldrink.com')->first();

        foreach ([
            ['code' => 'TEST-SHIP-001', 'status' => 'ready_for_delivery', 'address' => '12 Lê Lợi, Thanh Hóa'],
            ['code' => 'TEST-SHIP-002', 'status' => 'delivering', 'address' => '25 Trần Phú, Thanh Hóa'],
            ['code' => 'TEST-SHIP-003', 'status' => 'delivered', 'address' => '08 Nguyễn Trãi, Thanh Hóa'],
        ] as $item) {
            Order::query()->updateOrCreate(
                ['order_code' => $item['code']],
                [
                    'user_id' => $customer?->id,
                    'guest_name' => $customer ? null : 'Khách test shipper',
                    'guest_phone' => $customer ? null : '0900000088',
                    'branch_id' => $branch->id,
                    'shipper_id' => $shipper->id,
                    'fulfillment_type' => 'delivery',
                    'shipping_address_text' => $item['address'],
                    'shipping_latitude' => 19.806 + (crc32($item['code']) % 20) / 10000,
                    'shipping_longitude' => 105.785 + (crc32($item['code'].'lng') % 20) / 10000,
                    'subtotal' => 100000,
                    'shipping_fee' => 15000,
                    'discount' => 0,
                    'total' => 115000,
                    'payment_method' => 'cod',
                    'payment_status' => 'pending',
                    'status' => $item['status'],
                ],
            );
        }

        $this->command?->info("Đã tạo bộ test shipper cho {$user->email} tại {$branch->code}.");
    }
}
