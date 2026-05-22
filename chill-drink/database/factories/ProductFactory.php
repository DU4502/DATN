<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $drinks = [
            'Trà Sữa Trân Châu Đường Đen',
            'Cà Phê Sữa Đá',
            'Sinh Tố Bơ',
            'Nước Ép Cam',
            'Trà Đào Cam Sả',
            'Soda Blue Curacao',
            'Đá Xay Matcha',
            'Trà Oolong Sữa',
            'Cà Phê Đen Đá',
            'Sinh Tố Dâu',
            'Nước Ép Dưa Hấu',
            'Trà Vải',
            'Soda Chanh Dây',
            'Đá Xay Chocolate',
            'Trà Sữa Thái',
        ];

        $name = fake()->randomElement($drinks) . ' ' . fake()->unique()->numberBetween(1, 9999);

        return [
            'category_id' => Category::inRandomOrder()->first()->id ?? Category::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'image' => 'https://via.placeholder.com/400x400.png?text=' . urlencode($name),
            'price' => fake()->randomFloat(2, 20000, 80000),
            'description' => fake()->paragraph(),
            'stock' => fake()->numberBetween(0, 100),
            'status' => true,
        ];
    }
}
