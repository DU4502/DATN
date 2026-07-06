<?php

namespace Tests\Feature\Api;

use App\Models\Branch;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NearestBranchApiTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('branches')) {
            Schema::create('branches', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->unique();
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->string('address')->nullable();
                $table->decimal('latitude', 10, 6)->nullable();
                $table->decimal('longitude', 10, 6)->nullable();
                $table->boolean('status')->default(true);
                $table->timestamps();
            });
        }
    }

    public function test_it_returns_the_nearest_branch_for_a_location(): void
    {
        Branch::query()->create([
            'name' => 'Chi nhánh Hà Nội',
            'code' => 'HN',
            'address' => 'Hà Nội',
            'latitude' => 21.028511,
            'longitude' => 105.804817,
            'status' => true,
        ]);

        Branch::query()->create([
            'name' => 'Chi nhánh TP.HCM',
            'code' => 'HCM',
            'address' => 'TP.HCM',
            'latitude' => 10.823099,
            'longitude' => 106.629664,
            'status' => true,
        ]);

        $response = $this->getJson('/api/branches/nearest?latitude=21.03&longitude=105.85');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Chi nhánh Hà Nội',
                    'code' => 'HN',
                ],
            ]);
    }

    public function test_it_returns_branches_sorted_by_distance(): void
    {
        Branch::query()->create([
            'name' => 'Chi nhánh Hà Nội',
            'code' => 'HN',
            'address' => 'Hà Nội',
            'latitude' => 21.028511,
            'longitude' => 105.804817,
            'status' => true,
        ]);

        Branch::query()->create([
            'name' => 'Chi nhánh Đà Nẵng',
            'code' => 'DN',
            'address' => 'Đà Nẵng',
            'latitude' => 16.054406,
            'longitude' => 108.202167,
            'status' => true,
        ]);

        $response = $this->getJson('/api/branches?latitude=21.03&longitude=105.85');

        $response->assertOk()
            ->assertJsonPath('data.0.name', 'Chi nhánh Hà Nội')
            ->assertJsonPath('data.1.name', 'Chi nhánh Đà Nẵng');
    }
}
