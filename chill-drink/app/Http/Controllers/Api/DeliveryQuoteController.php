<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\DeliveryRoutingService;
use App\Support\OrderDistancePolicy;
use App\Support\ShippingFee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryQuoteController extends Controller
{
    public function __invoke(Request $request, DeliveryRoutingService $routing): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => ['required', 'integer'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'method' => ['nullable', 'string'],
            'cup_count' => ['nullable', 'integer', 'min:1', 'max:999'],
        ]);

        $branch = Branch::availableForLocation()->find($validated['branch_id']);

        if (! $branch) {
            return response()->json([
                'success' => false,
                'message' => 'Chi nhánh không khả dụng hoặc chưa có tọa độ.',
            ], 422);
        }

        $route = $routing->route(
            (float) $branch->latitude,
            (float) $branch->longitude,
            (float) $validated['latitude'],
            (float) $validated['longitude']
        );

        if ((bool) ($route['fallback'] ?? true)) {
            return response()->json([
                'success' => false,
                'inside_service_radius' => false,
                'message' => OrderDistancePolicy::routingUnavailableMessage(),
                'route_source' => (string) ($route['source'] ?? 'unknown'),
                'route_fallback' => true,
            ], 503);
        }

        $distanceKm = round(((float) ($route['distance_m'] ?? 0)) / 1000, 3);
        $inside = OrderDistancePolicy::isInsideServiceRadius($distanceKm);
        $method = array_key_exists((string) ($validated['method'] ?? ''), ShippingFee::methods())
            ? (string) $validated['method']
            : 'standard';
        $quote = ShippingFee::calculate($distanceKm, $method, (int) ($validated['cup_count'] ?? 1));

        return response()->json([
            'success' => true,
            'inside_service_radius' => $inside,
            'message' => $inside ? 'Tuyến giao hàng hợp lệ.' : OrderDistancePolicy::message(),
            'branch' => [
                'id' => $branch->id,
                'name' => $branch->name,
            ],
            'distance_km' => $distanceKm,
            'duration_s' => (float) ($route['duration_s'] ?? 0),
            'route_source' => (string) ($route['source'] ?? 'unknown'),
            'route_fallback' => (bool) ($route['fallback'] ?? false),
            'shipping' => $quote,
            'max_distance_km' => OrderDistancePolicy::MAX_DISTANCE_KM,
        ]);
    }
}
