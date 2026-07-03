<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Trà Sữa', 'description' => 'Các loại trà sữa thơm béo, nhiều topping.'],
            ['name' => 'Cà Phê', 'description' => 'Cà phê rang xay đậm vị, pha chế mỗi ngày.'],
            ['name' => 'Sinh Tố', 'description' => 'Sinh tố trái cây tươi, mịn và mát lạnh.'],
            ['name' => 'Nước Ép', 'description' => 'Nước ép trái cây nguyên chất, giàu vitamin.'],
            ['name' => 'Trà Trái Cây', 'description' => 'Trà trái cây thanh mát, hợp thời tiết nóng.'],
            ['name' => 'Soda', 'description' => 'Soda giải khát có gas, màu sắc bắt mắt.'],
            ['name' => 'Đá Xay', 'description' => 'Đồ uống đá xay mát lạnh, vị ngọt cân bằng.'],
            ['name' => 'Matcha', 'description' => 'Matcha thơm vị trà xanh, béo nhẹ và thanh mát.'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => Str::slug($category['name'])],
                [
                    'name' => $category['name'],
                    'description' => $category['description'],
                    'status' => true,
                ]
            );
        }
    }
}
