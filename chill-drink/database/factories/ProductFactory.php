<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Support\ProductCatalog;
use App\Support\ProductImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    public function configure(): static
    {
        return $this->afterCreating(function (Product $product) {
            $product->update([
                'image' => ProductImage::forProduct($product->id, $product->slug, 700),
            ]);
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $index = 0;
        $catalog = ProductCatalog::ITEMS;
        $item = $catalog[$index % count($catalog)];
        $index++;

        $category = Category::where('name', $item['category'])->first()
            ?? Category::inRandomOrder()->first()
            ?? Category::factory();

        $categoryId = $category instanceof Category ? $category->id : $category;

        return [
            'category_id' => $categoryId,
            'name' => $item['name'],
            'slug' => $item['slug'],
            'sku' => $item['sku'],
            'image' => null,
            'price' => $item['price'],
            'description' => $item['description'],
            'stock' => fake()->numberBetween(20, 80),
            'status' => true,
        ];
    }
}
