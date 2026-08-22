<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Services\DeliveryRoutingService;
use App\Support\OrderDistancePolicy;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DeliveryRoutingServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Http::preventStrayRequests();
    }

    public function test_osrm_success_returns_a_verified_route(): void
    {
        Http::fake([
            '*/route/v1/*' => Http::response([
                'code' => 'Ok',
                'routes' => [[
                    'distance' => 1250.5,
                    'duration' => 240,
                    'geometry' => ['coordinates' => [[106.7009, 10.7769], [106.71, 10.78]]],
                    'legs' => [],
                ]],
            ]),
        ]);

        $route = app(DeliveryRoutingService::class)->route(10.7769, 106.7009, 10.78, 106.71);

        $this->assertSame('routing_server', $route['source']);
        $this->assertFalse($route['fallback']);
        $this->assertSame(1250.5, $route['distance_m']);
        $this->assertSame([[10.7769, 106.7009], [10.78, 106.71]], $route['geometry']);
    }

    public function test_osrm_connection_failure_falls_back_for_display_but_fails_closed_for_checkout(): void
    {
        Http::fake([
            '*/route/v1/*' => Http::failedConnection('OSRM unavailable'),
        ]);

        $service = app(DeliveryRoutingService::class);
        $route = $service->route(10.7769, 106.7009, 10.78, 106.71);
        $branch = new Branch(['latitude' => 10.7769, 'longitude' => 106.7009]);

        $this->assertSame('coordinate_fallback', $route['source']);
        $this->assertTrue($route['fallback']);
        $this->assertNull(OrderDistancePolicy::distanceFromBranch($branch, 10.78, 106.71));
    }

    public function test_invalid_osrm_response_does_not_crash_or_become_a_verified_route(): void
    {
        Http::fake([
            '*/route/v1/*' => Http::response(['code' => 'Ok', 'routes' => [['duration' => 120]]]),
        ]);

        $route = app(DeliveryRoutingService::class)->route(10.7769, 106.7009, 10.79, 106.72);

        $this->assertSame('coordinate_fallback', $route['source']);
        $this->assertTrue($route['fallback']);
        $this->assertIsFloat($route['distance_m']);
    }
}
