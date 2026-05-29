<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! Schema::hasTable('roles')) {
            $this->command->warn('Table `roles` does not exist, skipping RoleSeeder.');
            return;
        }

        $roles = [
            ['id' => 1, 'name' => 'user',  'description' => 'Khách hàng'],
            ['id' => 2, 'name' => 'admin', 'description' => 'Quản trị'],
            ['id' => 3, 'name' => 'staff', 'description' => 'Nhân viên'],
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
