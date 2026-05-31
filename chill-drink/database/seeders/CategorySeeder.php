<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Support\ProductCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! Schema::hasTable('categories')) {
            $this->command?->warn('Table `categories` does not exist, skipping CategorySeeder.');
            return;
        }

        $categories = collect(ProductCatalog::CATEGORY_DESCRIPTIONS)
            ->map(fn (string $description, string $name) => compact('name', 'description'))
            ->values();

        foreach ($categories as $category) {
            $slug = Str::slug($category['name']);
            $lookup = Schema::hasColumn('categories', 'slug')
                ? ['slug' => $slug]
                : ['name' => $category['name']];

            $data = [
                'name' => $category['name'],
            ];

            if (Schema::hasColumn('categories', 'slug')) {
                $data['slug'] = $slug;
            }

            if (Schema::hasColumn('categories', 'description')) {
                $data['description'] = $category['description'];
            }

            if (Schema::hasColumn('categories', 'status')) {
                $data['status'] = true;
            }

            Category::updateOrCreate(
                $lookup,
                $data
            );
        }
    }
}
