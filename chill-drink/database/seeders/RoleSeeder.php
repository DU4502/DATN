<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('roles')) {
            $this->command->warn('Table `roles` does not exist, skipping RoleSeeder.');
            return;
        }

        $roles = [
            ['id' => 1, 'name' => 'user', 'description' => 'Người dùng'],
            ['id' => 2, 'name' => 'admin', 'description' => 'Quản trị viên'],
            ['id' => 3, 'name' => 'super_admin', 'description' => 'Quản trị toàn hệ thống'],
            ['id' => 4, 'name' => 'cskh', 'description' => 'Nhân viên CSKH'],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['id' => $role['id']],
                $role
            );
        }

        $this->command->info('Default roles seeded.');
    }
}
