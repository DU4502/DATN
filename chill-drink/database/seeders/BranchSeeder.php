<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('Không tạo chi nhánh mẫu. Chi nhánh phải được khai báo từ dữ liệu thực tế.');
    }
}
