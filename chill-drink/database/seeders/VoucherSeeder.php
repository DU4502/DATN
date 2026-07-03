<?php

namespace Database\Seeders;

use App\Models\Voucher;
use Illuminate\Database\Seeder;

class VoucherSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $vouchers = [
            [
                'code' => 'CHILL10',
                'type' => Voucher::TYPE_PERCENT,
                'value' => 10,
                'max_discount' => 20000,
                'description' => 'Giảm 10% cho đơn đồ uống từ 80.000đ.',
                'min_order' => 80000,
                'usage_limit' => 100,
                'required_rank' => null,
                'point_cost' => 0,
                'is_redeemable' => false,
            ],
            [
                'code' => 'OPENING15',
                'type' => Voucher::TYPE_PERCENT,
                'value' => 15,
                'max_discount' => 25000,
                'description' => 'Ưu đãi khai trương, giảm 15% cho đơn từ 50.000đ.',
                'min_order' => 50000,
                'usage_limit' => 100,
                'required_rank' => null,
                'point_cost' => 50,
                'is_redeemable' => true,
            ],
            [
                'code' => 'SHIP20K',
                'type' => Voucher::TYPE_FIXED,
                'value' => 20000,
                'max_discount' => null,
                'description' => 'Giảm trực tiếp 20.000đ cho đơn từ 40.000đ.',
                'min_order' => 40000,
                'usage_limit' => 50,
                'required_rank' => null,
                'point_cost' => 0,
                'is_redeemable' => false,
            ],
            [
                'code' => 'DIAMOND25',
                'type' => Voucher::TYPE_PERCENT,
                'value' => 25,
                'max_discount' => 50000,
                'description' => 'Mã ưu đãi cho khách rank kim cương.',
                'min_order' => 120000,
                'usage_limit' => 0,
                'required_rank' => 'diamond',
                'point_cost' => 100,
                'is_redeemable' => true,
            ],
        ];

        foreach ($vouchers as $voucher) {
            Voucher::updateOrCreate(
                ['code' => $voucher['code']],
                array_merge($voucher, [
                    'status' => true,
                    'starts_at' => $now,
                    'expires_at' => $now->copy()->addMonths(3),
                    'created_at' => $now,
                ])
            );
        }
    }
}
