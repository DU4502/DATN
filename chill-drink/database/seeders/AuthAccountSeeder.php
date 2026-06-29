<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AuthAccountSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            [
                'name' => 'Khách hàng Chill Drink',
                'email' => 'user@chilldrink.com',
                'password' => '12345678',
                'role_id' => 1,
                'phone' => '0900000001',
            ],
            [
                'name' => 'Admin Chill Drink',
                'email' => 'admin@chilldrink.com',
                'password' => '12345678',
                'role_id' => 2,
                'phone' => '0900000002',
            ],
            [
                'name' => 'Super Admin',
                'email' => User::SUPER_ADMIN_EMAIL,
                'password' => 'SuperAdmin@2026',
                'role_id' => 3,
                'phone' => '0900000003',
            ],
        ];

        foreach ($accounts as $account) {
            User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => Hash::make($account['password']),
                    'role_id' => $account['role_id'],
                    'phone' => $account['phone'],
                    'is_active' => true,
                ]
            );
        }

        $this->command?->info('Đã tạo tài khoản người dùng, Admin và Super Admin.');
    }
}
