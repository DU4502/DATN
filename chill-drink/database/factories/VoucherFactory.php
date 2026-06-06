<?php

namespace Database\Factories;

use App\Models\Voucher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Voucher>
 */
class VoucherFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->bothify('SALE####')),
            'type' => Voucher::TYPE_FIXED,
            'value' => $this->faker->numberBetween(10000, 50000),
            'max_discount' => null,
            'description' => $this->faker->sentence(),
            'min_order' => $this->faker->numberBetween(0, 100000),
            'usage_limit' => 100,
            'used_count' => 0,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonth(),
            'status' => true,
            'required_rank' => null,
            'point_cost' => 0,
            'is_redeemable' => false,
            'created_at' => now(),
        ];
    }
}
