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
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['id' => $role['id']],
                $role
            );
        }

        if (Schema::hasTable('users')) {
            DB::table('users')
                ->where('role_id', 3)
                ->update(['role_id' => 1]);
        }

        DB::table('roles')->where('id', 3)->delete();

        $this->command->info('Default roles seeded.');
    }
}
