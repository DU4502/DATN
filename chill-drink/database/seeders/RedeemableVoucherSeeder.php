<?php

namespace Database\Seeders;

use App\Models\Voucher;
use Illuminate\Database\Seeder;

class RedeemableVoucherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $redeemableVouchers = [
            [
                'code' => 'POINT50',
                'type' => 'fixed',
                'value' => 50000,
                'description' => 'Giảm 50,000đ cho đơn hàng từ 200,000đ',
                'min_order' => 200000,
                'point_cost' => 100,
                'is_redeemable' => 1,
                'status' => 1,
            ],
            [
                'code' => 'POINT100',
                'type' => 'fixed',
                'value' => 100000,
                'description' => 'Giảm 100,000đ cho đơn hàng từ 500,000đ',
                'min_order' => 500000,
                'point_cost' => 200,
                'is_redeemable' => 1,
                'status' => 1,
            ],
            [
                'code' => 'POINT200',
                'type' => 'fixed',
                'value' => 200000,
                'description' => 'Giảm 200,000đ cho đơn hàng từ 1,000,000đ',
                'min_order' => 1000000,
                'point_cost' => 400,
                'is_redeemable' => 1,
                'status' => 1,
            ],
            [
                'code' => 'DIAMOND20',
                'type' => 'percent',
                'value' => 20,
                'max_discount' => 500000,
                'description' => 'Giảm 20% (tối đa 500,000đ) cho đơn hàng từ 500,000đ',
                'min_order' => 500000,
                'point_cost' => 800,
                'is_redeemable' => 1,
                'status' => 1,
            ],
            [
                'code' => 'FREESHIP30',
                'type' => 'fixed',
                'value' => 30000,
                'description' => 'Miễn phí ship (giảm 30,000đ) cho đơn hàng từ 100,000đ',
                'min_order' => 100000,
                'point_cost' => 50,
                'is_redeemable' => 1,
                'status' => 1,
            ],
        ];

        foreach ($redeemableVouchers as $voucherData) {
            // Check if voucher already exists
            $existing = Voucher::where('code', $voucherData['code'])->first();
            
            if (!$existing) {
                Voucher::create(array_merge($voucherData, [
                    'usage_limit' => 0, // Unlimited
                    'used_count' => 0,
                    'starts_at' => now(),
                    'expires_at' => null,
                    'show_on_products' => 0,
                    'created_at' => now(),
                ]));
                
                $this->command->info("Created redeemable voucher: {$voucherData['code']}");
            } else {
                $this->command->warn("Voucher {$voucherData['code']} already exists, skipping.");
            }
        }
        
        $this->command->info('✅ Redeemable voucher seeding completed!');
    }
}
