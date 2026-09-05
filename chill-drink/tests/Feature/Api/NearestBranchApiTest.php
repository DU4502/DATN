<?php

namespace Tests\Feature\Api;

use App\Models\Branch;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Http;
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

        Http::preventStrayRequests();
        Http::fake([
            '*/route/v1/*' => Http::response([
                'code' => 'Ok',
                'routes' => [[
                    'distance' => 5000,
                    'duration' => 600,
                    'geometry' => ['coordinates' => [[105.804817, 21.028511], [105.85, 21.03]]],
                    'legs' => [],
                ]],
            ]),
        ]);
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
            ->assertJsonCount(1, 'data');
    }

    public function test_it_does_not_return_a_nearest_branch_outside_15_km(): void
    {
        Branch::query()->create([
            'name' => 'Chi nhánh TP.HCM',
            'code' => 'HCM',
            'address' => 'TP.HCM',
            'latitude' => 10.823099,
            'longitude' => 106.629664,
            'status' => true,
        ]);

        $this->getJson('/api/branches/nearest?latitude=21.03&longitude=105.85')
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_availability_only_returns_active_branches_with_coordinates(): void
    {
        $available = Branch::query()->create([
            'name' => 'Chi nhánh đang mở',
            'code' => 'AVAILABLE-RT',
            'address' => 'Có tọa độ',
            'latitude' => 19.807157,
            'longitude' => 105.776156,
            'status' => true,
        ]);

        Branch::query()->create([
            'name' => 'Chi nhánh đang đóng',
            'code' => 'CLOSED-RT',
            'address' => 'Có tọa độ',
            'latitude' => 19.807157,
            'longitude' => 105.776156,
            'status' => false,
        ]);

        Branch::query()->create([
            'name' => 'Chi nhánh thiếu tọa độ',
            'code' => 'NO-COORDS-RT',
            'address' => 'Chưa gắn map',
            'status' => true,
        ]);

        $this->getJson('/api/branches/availability')
            ->assertOk()
            ->assertJsonFragment(['id' => $available->id, 'name' => 'Chi nhánh đang mở'])
            ->assertJsonMissing(['name' => 'Chi nhánh đang đóng'])
            ->assertJsonMissing(['name' => 'Chi nhánh thiếu tọa độ']);
    }
}
