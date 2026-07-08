<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ToppingSeeder extends Seeder
{
    public function run(): void
    {
        $toppings = [
            ['name' => 'Trân châu đen', 'price' => 5000],
            ['name' => 'Trân châu trắng', 'price' => 7000],
            ['name' => 'Kem cheese', 'price' => 7000],
            ['name' => 'Thạch matcha', 'price' => 6000],
            ['name' => 'Pudding trứng', 'price' => 7000],
            ['name' => 'Thạch phô mai', 'price' => 8000],
            ['name' => 'Thạch nha đam', 'price' => 6000],
        ];

        foreach ($toppings as $topping) {
            DB::table('toppings')->updateOrInsert(
                ['name' => $topping['name']],
                $topping + ['status' => 1]
            );
        }
    }
}
