<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Ensure roles exist before creating users
        $this->call(RoleSeeder::class);

        // Create Admin User without changing an existing account/password.
        // Only include columns that exist in the current database schema.
        User::firstOrCreate(
            ['email' => 'admin@chilldrink.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'role_id' => 2,
                'phone' => '0123456789',
            ]
        );

        $this->call([
            CategorySeeder::class,
            ProductSeeder::class,
        ]);

        $this->command->info('Database seeded successfully!');
    }
}
