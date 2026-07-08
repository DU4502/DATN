<?php

namespace Tests\Feature\Api;

use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NearestBranchApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_active_branches_ordered_by_distance(): void
    {
        $nearest = Branch::create([
            'name' => 'Chi nhánh Trung tâm',
            'code' => 'TEST-CENTER',
            'address' => 'Quận 1, Thành phố Hồ Chí Minh',
            'latitude' => 10.7769,
            'longitude' => 106.7009,
            'status' => true,
        ]);

        $farther = Branch::create([
            'name' => 'Chi nhánh Ngoại thành',
            'code' => 'TEST-OUTER',
            'address' => 'Thành phố Thủ Đức, Thành phố Hồ Chí Minh',
            'latitude' => 10.8494,
            'longitude' => 106.7537,
            'status' => true,
        ]);

        Branch::create([
            'name' => 'Chi nhánh Tạm đóng',
            'code' => 'TEST-INACTIVE',
            'latitude' => 10.7768,
            'longitude' => 106.7008,
            'status' => false,
        ]);

        $response = $this->getJson(route('api.branches.nearest', [
            'latitude' => 10.775,
            'longitude' => 106.699,
            'limit' => 2,
        ]));

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $nearest->id)
            ->assertJsonPath('data.1.id', $farther->id)
            ->assertJsonMissing(['code' => 'TEST-INACTIVE']);

        $this->assertLessThan(
            $response->json('data.1.distance_km'),
            $response->json('data.0.distance_km')
        );
    }

    public function test_it_validates_coordinates(): void
    {
        $this->getJson(route('api.branches.nearest', [
            'latitude' => 91,
            'longitude' => 181,
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['latitude', 'longitude']);
    }

    public function test_it_returns_an_empty_result_when_no_real_branch_is_configured(): void
    {
        Branch::query()->delete();

        $this->getJson(route('api.branches.nearest', [
            'latitude' => 10.775,
            'longitude' => 106.699,
        ]))
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
