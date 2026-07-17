<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Branch;
use App\Models\BranchSlide;

$branches = Branch::all();

$defaultSlides = [
    [
        'product_name' => 'TRÀ MATCHA',
        'title' => 'TRÀ MATCHA THANH MÁT, TINH KHIẾT',
        'price' => '85.000₫',
        'image' => '/images/matcha.png',
        'bg_color' => '#5d9c59',
        'description' => 'Hương vị trà xanh Nhật Bản thượng hạng hòa quyện cùng sữa tươi béo ngậy. Một lựa chọn hoàn hảo cho những ai yêu thích sự thanh khiết và tươi mới.',
        'sort_order' => 1
    ],
    [
        'product_name' => 'TRÀ SỮA',
        'title' => 'TRÀ SỮA CHÂN TRÂU ĐƯỜNG ĐEN',
        'price' => '75.000₫',
        'image' => '/images/trasua.png', 
        'bg_color' => '#ffffff',
        'description' => 'Trà sữa đậm đà hòa quyện với sữa tươi, nổi bật trên nền trắng tinh khôi và chữ đỏ rực.',
        'sort_order' => 2
    ],
    [
        'product_name' => 'CÀ PHÊ Ủ LẠNH',
        'title' => 'CÀ PHÊ Ủ LẠNH ĐẬM ĐÀ, SANG CHẢNH',
        'price' => '65.000₫',
        'image' => '/images/cafe.png',
        'bg_color' => '#322c2b',
        'description' => 'Cà phê ủ lạnh 12 giờ cho vị thanh khiết, ít đắng và đượm hương trái cây tự nhiên từ những hạt cà phê Arabica đặc sản.',
        'sort_order' => 3
    ],
    [
        'product_name' => 'XOÀI NHIỆT ĐỚI',
        'title' => 'HƯƠNG VỊ NHIỆT ĐỚI MÁT LẠNH',
        'price' => '90.000₫',
        'image' => '/images/sinhtoxoai.png',
        'bg_color' => '#ffb100',
        'description' => 'Hương vị xoài chín mọng hòa quyện cùng cốt dừa tươi, mang cả mùa hè nhiệt đới vào từng ngụm nước mát lạnh và thơm nồng nàn.',
        'sort_order' => 4
    ]
];

foreach ($branches as $branch) {
    if ($branch->slides()->count() === 0) {
        foreach ($defaultSlides as $slide) {
            $branch->slides()->create($slide);
        }
        echo "Seeded slides for Branch: {$branch->name}\n";
    }
}
