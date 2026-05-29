<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = Category::pluck('id', 'slug');

        $products = [
            [
                'category_slug' => 'tra-sua',
                'name' => 'Trà Sữa Trân Châu Đường Đen',
                'price' => 39000,
                'stock' => 80,
                'image' => 'https://placehold.co/600x600/f8fafc/0f172a?text=Tra+Sua+Duong+Den',
                'description' => 'Trà sữa béo nhẹ kết hợp trân châu mềm và sốt đường đen thơm caramel.',
            ],
            [
                'category_slug' => 'tra-sua',
                'name' => 'Trà Sữa Oolong Kem Cheese',
                'price' => 45000,
                'stock' => 65,
                'image' => 'https://placehold.co/600x600/ecfeff/164e63?text=Oolong+Cheese',
                'description' => 'Nền trà oolong đậm hương, phủ kem cheese mặn ngọt cân bằng.',
            ],
            [
                'category_slug' => 'tra-sua',
                'name' => 'Trà Sữa Matcha Đậu Đỏ',
                'price' => 47000,
                'stock' => 52,
                'image' => 'https://placehold.co/600x600/ecfdf5/14532d?text=Matcha+Dau+Do',
                'description' => 'Matcha thơm dịu, sữa tươi béo và đậu đỏ bùi ngọt.',
            ],
            [
                'category_slug' => 'ca-phe',
                'name' => 'Cà Phê Sữa Đá',
                'price' => 29000,
                'stock' => 100,
                'image' => 'https://placehold.co/600x600/fef3c7/78350f?text=Ca+Phe+Sua+Da',
                'description' => 'Cà phê phin truyền thống pha sữa đặc, vị đậm và hậu ngọt.',
            ],
            [
                'category_slug' => 'ca-phe',
                'name' => 'Bạc Xỉu',
                'price' => 32000,
                'stock' => 90,
                'image' => 'https://placehold.co/600x600/fff7ed/7c2d12?text=Bac+Xiu',
                'description' => 'Sữa thơm béo cùng lượng cà phê vừa đủ, dễ uống cả ngày.',
            ],
            [
                'category_slug' => 'ca-phe',
                'name' => 'Cold Brew Cam Vàng',
                'price' => 49000,
                'stock' => 40,
                'image' => 'https://placehold.co/600x600/fffbeb/92400e?text=Cold+Brew+Cam',
                'description' => 'Cold brew ủ lạnh kết hợp cam vàng, vị sáng và ít đắng.',
            ],
            [
                'category_slug' => 'sinh-to',
                'name' => 'Sinh Tố Bơ',
                'price' => 42000,
                'stock' => 55,
                'image' => 'https://placehold.co/600x600/f0fdf4/166534?text=Sinh+To+Bo',
                'description' => 'Bơ chín xay mịn cùng sữa, vị béo tự nhiên.',
            ],
            [
                'category_slug' => 'sinh-to',
                'name' => 'Sinh Tố Dâu Chuối',
                'price' => 43000,
                'stock' => 48,
                'image' => 'https://placehold.co/600x600/fdf2f8/9d174d?text=Dau+Chuoi',
                'description' => 'Dâu tây chua nhẹ và chuối chín ngọt, xay mịn mát lạnh.',
            ],
            [
                'category_slug' => 'sinh-to',
                'name' => 'Sinh Tố Xoài',
                'price' => 39000,
                'stock' => 60,
                'image' => 'https://placehold.co/600x600/fffbeb/b45309?text=Sinh+To+Xoai',
                'description' => 'Xoài chín thơm, vị ngọt thanh và màu vàng bắt mắt.',
            ],
            [
                'category_slug' => 'nuoc-ep',
                'name' => 'Nước Ép Cam',
                'price' => 35000,
                'stock' => 75,
                'image' => 'https://placehold.co/600x600/fff7ed/c2410c?text=Nuoc+Ep+Cam',
                'description' => 'Cam tươi ép nguyên chất, vị chua ngọt tự nhiên.',
            ],
            [
                'category_slug' => 'nuoc-ep',
                'name' => 'Nước Ép Dưa Hấu',
                'price' => 32000,
                'stock' => 70,
                'image' => 'https://placehold.co/600x600/fef2f2/991b1b?text=Dua+Hau',
                'description' => 'Dưa hấu ép mát lạnh, nhẹ vị và giải khát nhanh.',
            ],
            [
                'category_slug' => 'nuoc-ep',
                'name' => 'Nước Ép Táo Cần Tây',
                'price' => 45000,
                'stock' => 45,
                'image' => 'https://placehold.co/600x600/f7fee7/365314?text=Tao+Can+Tay',
                'description' => 'Táo ngọt kết hợp cần tây xanh, phù hợp lựa chọn healthy.',
            ],
            [
                'category_slug' => 'tra-trai-cay',
                'name' => 'Trà Đào Cam Sả',
                'price' => 42000,
                'stock' => 68,
                'image' => 'https://placehold.co/600x600/fff7ed/9a3412?text=Tra+Dao+Cam+Sa',
                'description' => 'Trà đen, đào miếng, cam tươi và sả thơm dịu.',
            ],
            [
                'category_slug' => 'tra-trai-cay',
                'name' => 'Trà Vải Hoa Hồng',
                'price' => 44000,
                'stock' => 58,
                'image' => 'https://placehold.co/600x600/fdf2f8/be185d?text=Tra+Vai',
                'description' => 'Trà thanh kết hợp vải ngọt và hương hoa hồng nhẹ.',
            ],
            [
                'category_slug' => 'tra-trai-cay',
                'name' => 'Trà Dâu Tằm',
                'price' => 41000,
                'stock' => 62,
                'image' => 'https://placehold.co/600x600/fce7f3/831843?text=Tra+Dau+Tam',
                'description' => 'Dâu tằm chua ngọt, nền trà nhẹ và hậu vị trái cây rõ.',
            ],
            [
                'category_slug' => 'soda',
                'name' => 'Soda Blue Ocean',
                'price' => 36000,
                'stock' => 64,
                'image' => 'https://placehold.co/600x600/e0f2fe/075985?text=Blue+Ocean',
                'description' => 'Soda xanh mát, vị citrus nhẹ và gas sảng khoái.',
            ],
            [
                'category_slug' => 'soda',
                'name' => 'Soda Chanh Dây',
                'price' => 36000,
                'stock' => 57,
                'image' => 'https://placehold.co/600x600/fefce8/854d0e?text=Soda+Chanh+Day',
                'description' => 'Chanh dây thơm chua, kết hợp soda mát lạnh.',
            ],
            [
                'category_slug' => 'da-xay',
                'name' => 'Matcha Đá Xay',
                'price' => 52000,
                'stock' => 35,
                'image' => 'https://placehold.co/600x600/ecfdf5/166534?text=Matcha+Da+Xay',
                'description' => 'Matcha đá xay mịn, phủ kem béo và vị trà xanh rõ.',
            ],
            [
                'category_slug' => 'da-xay',
                'name' => 'Chocolate Đá Xay',
                'price' => 50000,
                'stock' => 38,
                'image' => 'https://placehold.co/600x600/fef3c7/713f12?text=Chocolate+Da+Xay',
                'description' => 'Chocolate đậm vị xay cùng đá, phù hợp khách thích vị ngọt béo.',
            ],
        ];

        foreach ($products as $product) {
            $categoryId = $categories[$product['category_slug']] ?? null;

            if (! $categoryId) {
                continue;
            }

            Product::updateOrCreate(
                ['slug' => Str::slug($product['name'])],
                [
                    'category_id' => $categoryId,
                    'name' => $product['name'],
                    'image' => $product['image'],
                    'price' => $product['price'],
                    'description' => $product['description'],
                    'stock' => $product['stock'],
                    'status' => true,
                ]
            );
        }
    }
}
