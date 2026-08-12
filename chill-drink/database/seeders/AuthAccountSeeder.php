<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class AuthAccountSeeder extends Seeder
{
    public function run(): void
    {
        if (Schema::hasTable('roles')) {
            $roles = [
                ['id' => 1, 'name' => 'user', 'description' => 'Người dùng'],
                ['id' => 2, 'name' => 'admin', 'description' => 'Quản trị viên'],
                ['id' => 3, 'name' => 'super_admin', 'description' => 'Quản trị toàn hệ thống'],
                ['id' => 4, 'name' => 'cskh', 'description' => 'Nhân viên CSKH'],
            ];

            foreach ($roles as $role) {
                DB::table('roles')->updateOrInsert(['id' => $role['id']], $role);
            }
        }

        $accounts = [
            [
                'name' => 'Khách hàng Chill Drink',
                'email' => 'user@chilldrink.com',
                'password' => '12345678',
                'role_id' => 1,
                'phone' => '0900000001',
            ],
            [
                'name' => 'Admin Chi nhánh 1',
                'email' => 'admin@chilldrink.com',
                'password' => '12345678',
                'role_id' => 2,
                'phone' => '0900000002',
                'branch_code' => 'CN1',
            ],
            [
                'name' => 'Admin Chi nhánh 2',
                'email' => 'admin_cn2@chilldrink.com',
                'password' => '12345678',
                'role_id' => 2,
                'phone' => '0900000022',
                'branch_code' => 'CN2',
            ],
            [
                'name' => 'Admin Chi nhánh 3',
                'email' => 'admin_cn3@chilldrink.com',
                'password' => '12345678',
                'role_id' => 2,
                'phone' => '0900000023',
                'branch_code' => 'CN3',
            ],
            [
                'name' => 'Super Admin',
                'email' => User::SUPER_ADMIN_EMAIL,
                'password' => 'SuperAdmin@2026',
                'role_id' => 3,
                'phone' => '0900000003',
            ],
            [
                'name' => 'Nhân viên CSKH',
                'email' => 'cskh@chilldrink.com',
                'password' => 'Cskh@123',
                'role_id' => 4,
                'phone' => '0900000004',
            ],
        ];

        foreach ($accounts as $account) {
            $data = [
                'name' => $account['name'],
                'password' => Hash::make($account['password']),
                'role_id' => $account['role_id'],
                'phone' => $account['phone'],
                'is_active' => true,
            ];

            // Tài khoản mẫu/hệ thống phải đăng nhập được ngay sau khi seed.
            if (Schema::hasColumn('users', 'email_verified_at')) {
                $data['email_verified_at'] = now();
            }

            // Gán tài khoản Admin vào chi nhánh tương ứng
            if (isset($account['branch_code'])) {
                $branch = DB::table('branches')->where('code', $account['branch_code'])->first();
                if ($branch) {
                    $data['branch_id'] = $branch->id;
                }
            }

            User::updateOrCreate(
                ['email' => $account['email']],
                $data
            );
        }

        $this->command?->info('Đã tạo tài khoản người dùng, Admin và Super Admin.');
    }
}
