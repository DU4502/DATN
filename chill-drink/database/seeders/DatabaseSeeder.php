<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Admin User
        User::create([
            'name' => 'Admin',
            'email' => 'admin@chilldrink.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'role_id' => 2,
            'phone' => '0123456789',
            'address' => 'Hà Nội, Việt Nam',
            'points' => 0,
        ]);

        // Create Categories
        $categories = [
            ['name' => 'Trà Sữa', 'description' => 'Các loại trà sữa thơm ngon'],
            ['name' => 'Cà Phê', 'description' => 'Cà phê nguyên chất'],
            ['name' => 'Sinh Tố', 'description' => 'Sinh tố trái cây tươi'],
            ['name' => 'Nước Ép', 'description' => 'Nước ép trái cây tự nhiên'],
            ['name' => 'Trà Trái Cây', 'description' => 'Trà trái cây mát lạnh'],
            ['name' => 'Soda', 'description' => 'Soda các loại'],
        ];

        foreach ($categories as $category) {
            Category::create([
                'name' => $category['name'],
                'slug' => \Illuminate\Support\Str::slug($category['name']),
                'description' => $category['description'],
                'status' => true,
            ]);
        }

        // Create Products
        Product::factory(30)->create();

        $this->command->info('Database seeded successfully!');
    }
}
