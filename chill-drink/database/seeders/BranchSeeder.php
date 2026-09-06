<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('branches')) {
            $this->command?->warn('Table `branches` does not exist, skipping BranchSeeder.');
            return;
        }

        $branches = [
            [
                'name' => 'Chi nhánh 1',
                'code' => 'CN1',
                'address' => 'QQFJ+MX Quảng Phú, Thanh Hóa, Việt Nam',
                'latitude' => 19.7891230,
                'longitude' => 105.7891230,
                'status' => 1,
            ],
            [
                'name' => 'Chi nhánh 2',
                'code' => 'CN2',
                'address' => 'QQVV+9W Hạc Thành, Thanh Hóa, Việt Nam',
                'latitude' => 19.8055560,
                'longitude' => 105.7766670,
                'status' => 1,
            ],
            [
                'name' => 'Chi nhánh 3',
                'code' => 'CN3',
                'address' => 'RQ4G+W9 Hạc Thành, Thanh Hóa, Việt Nam',
                'latitude' => 19.8155560,
                'longitude' => 105.7666670,
                'status' => 1,
            ],
        ];

        foreach ($branches as $branch) {
            Branch::updateOrCreate(
                ['code' => $branch['code']],
                $branch
            );
        }

        $this->command?->info('Branches seeded.');
    }
}
