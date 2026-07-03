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
                'address' => 'Hà Nội',
                'status' => 1,
            ],
            [
                'name' => 'Chi nhánh Thanh Hóa',
                'code' => 'TH',
                'address' => 'Thanh Hóa',
                'status' => 1,
            ],
            [
                'name' => 'Chi nhánh Hồ Chí Minh',
                'code' => 'HCM',
                'address' => 'Hồ Chí Minh',
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
