<?php

namespace Database\Seeders;

use App\Models\BranchSlide;
use Illuminate\Database\Seeder;

class BranchSlideSeeder extends Seeder
{
    public function run(): void
    {
        $slides = [
            // Chi nhánh 1
            [
                'branch_id'    => 1,
                'product_name' => 'Trà Sữa Trân Châu',
                'title'        => 'Thức uống yêu thích mùa hè',
                'price'        => '45.000đ',
                'image'        => '/images/trasua.png',
                'bg_color'     => '#F9E4B7',
                'description'  => 'Trà sữa trân châu thơm ngon, béo ngậy với những viên trân châu dai mềm hấp dẫn.',
                'sort_order'   => 1,
                'is_active'    => true,
            ],
            [
                'branch_id'    => 1,
                'product_name' => 'Cà Phê Sữa Đá',
                'title'        => 'Năng lượng cho ngày mới',
                'price'        => '35.000đ',
                'image'        => '/images/cafe.png',
                'bg_color'     => '#D4E8D0',
                'description'  => 'Cà phê sữa đá đậm đà, thơm ngát giúp bạn tỉnh táo suốt cả ngày dài.',
                'sort_order'   => 2,
                'is_active'    => true,
            ],
            [
                'branch_id'    => 1,
                'product_name' => 'Matcha Latte',
                'title'        => 'Thanh mát & tốt cho sức khỏe',
                'price'        => '55.000đ',
                'image'        => '/images/matcha.png',
                'bg_color'     => '#C8E6C9',
                'description'  => 'Matcha latte thuần chay từ trà xanh Nhật Bản cao cấp, thơm ngon và tốt cho sức khỏe.',
                'sort_order'   => 3,
                'is_active'    => true,
            ],

            // Chi nhánh 2
            [
                'branch_id'    => 2,
                'product_name' => 'Sinh Tố Xoài',
                'title'        => 'Vị nhiệt đới tươi mát',
                'price'        => '50.000đ',
                'image'        => '/images/sinhtoxoai.png',
                'bg_color'     => '#FFE0B2',
                'description'  => 'Sinh tố xoài tươi nguyên chất, ngọt thanh và giàu vitamin C.',
                'sort_order'   => 1,
                'is_active'    => true,
            ],
            [
                'branch_id'    => 2,
                'product_name' => 'Trà Đào Cam Sả',
                'title'        => 'Combo giải khát số 1',
                'price'        => '40.000đ',
                'image'        => '/images/trasua.png',
                'bg_color'     => '#FFE4E1',
                'description'  => 'Trà đào thơm mát kết hợp cam tươi và sả chanh, hương vị khó quên.',
                'sort_order'   => 2,
                'is_active'    => true,
            ],

            // Chi nhánh 3
            [
                'branch_id'    => 3,
                'product_name' => 'Hồng Trà Sữa',
                'title'        => 'Đậm đà theo từng ngụm',
                'price'        => '45.000đ',
                'image'        => '/images/trasua.png',
                'bg_color'     => '#FCE4EC',
                'description'  => 'Hồng trà sữa thơm nồng, vị trà đậm hòa quyện cùng sữa tươi béo ngậy.',
                'sort_order'   => 1,
                'is_active'    => true,
            ],
            [
                'branch_id'    => 3,
                'product_name' => 'Bạc Xỉu Đá',
                'title'        => 'Đặc sản Sài Gòn chính hiệu',
                'price'        => '30.000đ',
                'image'        => '/images/cafe.png',
                'bg_color'     => '#E3F2FD',
                'description'  => 'Bạc xỉu đá theo công thức truyền thống, sữa nhiều hơn cà phê, ngọt nhẹ dễ uống.',
                'sort_order'   => 2,
                'is_active'    => true,
            ],
        ];

        foreach ($slides as $slide) {
            BranchSlide::firstOrCreate(
                ['branch_id' => $slide['branch_id'], 'sort_order' => $slide['sort_order']],
                $slide
            );
        }
    }
}
