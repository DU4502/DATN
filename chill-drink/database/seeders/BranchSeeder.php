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
                'name' => 'Chi nhánh Hà Nội',
                'code' => 'HN',
                'address' => 'Số 12 Lý Thường Kiệt, Quận Hoàn Kiếm, Hà Nội',
                'latitude' => 21.028511,
                'longitude' => 105.804817,
                'status' => 1,
            ],
            [
                'name' => 'Chi nhánh Thanh Hóa',
                'code' => 'TH',
                'address' => 'Số 45 Trần Phú, Phường Điện Biên, TP. Thanh Hóa',
                'latitude' => 19.807674,
                'longitude' => 105.776652,
                'status' => 1,
            ],
            [
                'name' => 'Chi nhánh Hồ Chí Minh',
                'code' => 'HCM',
                'address' => 'Số 88 Nguyễn Huệ, Phường Bến Nghé, Quận 1, TP. Hồ Chí Minh',
                'latitude' => 10.823099,
                'longitude' => 106.629664,
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
