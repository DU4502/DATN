<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Ensure roles exist before creating users
        $this->call(RoleSeeder::class);

        // Create Admin User once and avoid overwriting existing credentials
        $adminData = [
            'name' => 'Admin',
            'password' => Hash::make('12345678'),
            'role_id' => 2,
            'phone' => '0123456789',
        ];

        if (Schema::hasColumn('users', 'role')) {
            $adminData['role'] = 'admin';
        }

        if (Schema::hasColumn('users', 'address')) {
            $adminData['address'] = 'Hà Nội, Việt Nam';
        }

        if (Schema::hasColumn('users', 'points')) {
            $adminData['points'] = 0;
        }

        User::firstOrCreate(
            ['email' => 'admin@chilldrink.com'],
            $adminData
        );

        $this->call(VoucherSeeder::class);

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
            $categorySlug = Str::slug($category['name']);
            $categoryLookup = Schema::hasColumn('categories', 'slug')
                ? ['slug' => $categorySlug]
                : ['name' => $category['name']];
            $categoryData = [
                'name' => $category['name'],
            ];

            if (Schema::hasColumn('categories', 'status')) {
                $categoryData['status'] = true;
            }

            if (Schema::hasColumn('categories', 'slug')) {
                $categoryData['slug'] = $categorySlug;
            }

            if (Schema::hasColumn('categories', 'description')) {
                $categoryData['description'] = $category['description'];
            }

            Category::updateOrCreate($categoryLookup, $categoryData);
        }

        // Create Products only if schema matches factory expectations
        if (Product::count() === 0) {
            if (Schema::hasColumn('products', 'price')) {
                Product::factory(30)->create();
            } else {
                $this->command->info('Skipping Product factory: `products.price` column not found.');
            }
        }

        $this->command->info('Database seeded successfully!');
    }
}
