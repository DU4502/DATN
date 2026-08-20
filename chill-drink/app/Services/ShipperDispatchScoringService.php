<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Order;
use App\Models\Shipper;
use App\Support\OrderStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ShipperDispatchScoringService
{
    public function __construct(
        private readonly DeliveryRoutingService $routing,
        private readonly ShipperReturnService $returns,
    ) {
    }

    public function beginBatch(): string
    {
        return (string) Str::uuid();
    }

    /**
     * Context dùng chung trong một lần dispatch để tránh query lặp.
     */
    public function context(Order $order): array
    {
        return [
            'occupancy' => $this->returns->occupancyByBranch(),
            'demand' => $this->weightedDemandByBranch(),
            'order_urgency' => $this->orderUrgencyScore($order),
        ];
    }

    public function rankAvailable(Order $order, Collection $candidates, array $context): Collection
    {
        $order->loadMissing('branch');
        if (! $this->validBranch($order)) {
            return collect();
        }

        $maxAirKm = (float) config('shipper_dispatch.shortlist.available_air_km', 10.0);
        $maxCandidates = max(1, (int) config('shipper_dispatch.shortlist.available_candidates', 6));

        $shortlist = $candidates
            ->filter(fn (Shipper $shipper) => (int) ($shipper->user?->branch_id ?? 0) === (int) $order->branch_id)
            ->map(function (Shipper $shipper) use ($order) {
                $origin = $this->originPoint($shipper);
                if (! $origin) {
                    return null;
                }

                return [
                    'shipper' => $shipper,
                    'origin' => $origin,
                    'air_km' => $this->haversineKm(
                        $origin['latitude'],
                        $origin['longitude'],
                        (float) $order->branch->latitude,
                        (float) $order->branch->longitude
                    ),
                ];
            })
            ->filter()
            ->filter(fn (array $row) => $row['air_km'] <= $maxAirKm || $this->effectiveStationBranchId($row['shipper']) === (int) $order->branch_id)
            ->sortBy('air_km')
            ->take($maxCandidates)
            ->values();

        return $shortlist
            ->map(fn (array $row) => $this->scoreAvailableRow($order, $row, $context))
            ->filter()
            ->sortByDesc('score')
            ->values();
    }

    public function rankReturning(Order $order, Collection $candidates, array $context): Collection
    {
        $order->loadMissing('branch');
        if (! $this->validBranch($order)) {
            return collect();
        }

        $maxAirKm = (float) config('shipper_dispatch.shortlist.returning_air_km', 10.0);
        $maxCandidates = max(1, (int) config('shipper_dispatch.shortlist.returning_candidates', 4));

        $shortlist = $candidates
            ->filter(fn (Shipper $shipper) => (int) ($shipper->user?->branch_id ?? 0) === (int) $order->branch_id)
            ->map(function (Shipper $shipper) use ($order) {
                $origin = $this->originPoint($shipper, false);
                if (! $origin) {
                    return null;
                }

                return [
                    'shipper' => $shipper,
                    'origin' => $origin,
                    'air_km' => $this->haversineKm(
                        $origin['latitude'],
                        $origin['longitude'],
                        (float) $order->branch->latitude,
                        (float) $order->branch->longitude
                    ),
                ];
            })
            ->filter()
            ->filter(fn (array $row) => $row['air_km'] <= $maxAirKm)
            ->sortBy('air_km')
            ->take($maxCandidates)
            ->values();

        return $shortlist
            ->map(fn (array $row) => $this->scoreReturningRow($order, $row, $context))
            ->filter()
            ->sortByDesc('score')
            ->values();
    }

    public function scoreBundle(Order $newOrder, Order $currentOrder, array $evaluation, int $totalCups): array
    {
        $savedKm = ((float) ($evaluation['saved_distance_m'] ?? 0)) / 1000;
        $savedRatio = (float) ($evaluation['saved_ratio'] ?? 0);
        $farPair = (bool) ($evaluation['far_pair'] ?? false);
        $existingDelayMin = ((float) ($evaluation['existing_customer_delay_s'] ?? 0)) / 60;
        $maxCups = max(1, (int) config('shipper_dispatch.bundle.max_cups_per_trip', 20));
        $loadRatio = $totalCups / $maxCups;
        $urgency = $this->orderUrgencyScore($newOrder);
        $scheduledLateMin = ((float) ($evaluation['new_scheduled_lateness_s'] ?? 0)) / 60;

        $breakdown = [
            'saved_km' => $savedKm * (float) config('shipper_dispatch.bundle.saved_km_weight', 4.0),
            'saved_ratio' => $savedRatio * (float) config('shipper_dispatch.bundle.saved_ratio_weight', 30.0),
            'far_pair' => $farPair ? (float) config('shipper_dispatch.bundle.far_pair_bonus', 20.0) : 0.0,
            'urgency' => min(10.0, $urgency * 0.35),
            'existing_customer_delay' => -$existingDelayMin * (float) config('shipper_dispatch.bundle.existing_delay_penalty_per_minute', 2.0),
            'scheduled_lateness' => -$scheduledLateMin * 1.5,
            'high_load' => $loadRatio >= 0.85 ? -(float) config('shipper_dispatch.bundle.high_load_penalty', 4.0) : 0.0,
        ];

        return [
            'score' => round(array_sum($breakdown), 3),
            'breakdown' => $breakdown,
            'urgency' => $urgency,
            'total_cups' => $totalCups,
        ];
    }

    public function orderUrgencyScore(Order $order): float
    {
        $score = 0.0;
        $now = now();

        if ($order->created_at) {
            $ageMin = max(0, $order->created_at->diffInMinutes($now));
            $score += min(16.0, $ageMin / 2.5);
        }

        $status = OrderStatus::normalize((string) $order->status);
        $score += match ($status) {
            OrderStatus::READY_FOR_DELIVERY => 12.0,
            OrderStatus::PREPARING => 6.0,
            OrderStatus::CONFIRMED => 3.0,
            default => 0.0,
        };

        $scheduled = $order->scheduled_delivery_time ?? $order->scheduled_at;
        if ($scheduled) {
            $secondsUntil = $now->diffInSeconds($scheduled, false);
            if ($secondsUntil <= 0) {
                $score += 20.0 + min(20.0, abs($secondsUntil) / 180);
            } elseif ($secondsUntil <= 30 * 60) {
                $score += 14.0 * (1 - ($secondsUntil / (30 * 60)));
            }
        }

        return round($score, 3);
    }

    public function logRankedRows(string $batchUuid, Order $order, string $mode, Collection $rows): void
    {
        if (! Schema::hasTable('delivery_dispatch_decisions')) {
            return;
        }

        $now = now();
        $payload = [];
        foreach ($rows->take(10)->values() as $index => $row) {
            /** @var Shipper|null $shipper */
            $shipper = $row['shipper'] ?? null;
            $payload[] = [
                'batch_uuid' => $batchUuid,
                'order_id' => $order->id,
                'shipper_id' => $shipper?->id,
                'mode' => $mode,
                'rank' => $index + 1,
                'score' => isset($row['score']) ? round((float) $row['score'], 3) : null,
                'selected' => false,
                'features_json' => json_encode($this->cleanFeatures($row), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'reason' => $row['reason'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($payload) {
            DB::table('delivery_dispatch_decisions')->insert($payload);
        }
    }

    public function markSelected(string $batchUuid, int $orderId, int $shipperId, string $mode): void
    {
        if (! Schema::hasTable('delivery_dispatch_decisions')) {
            return;
        }

        DB::table('delivery_dispatch_decisions')
            ->where('batch_uuid', $batchUuid)
            ->where('order_id', $orderId)
            ->where('shipper_id', $shipperId)
            ->where('mode', $mode)
            ->update(['selected' => true, 'updated_at' => now()]);
    }

    private function scoreAvailableRow(Order $order, array $row, array $context): ?array
    {
        /** @var Shipper $shipper */
        $shipper = $row['shipper'];
        $origin = $row['origin'];
        $route = $this->routing->route(
            $origin['latitude'],
            $origin['longitude'],
            (float) $order->branch->latitude,
            (float) $order->branch->longitude
        );

        // Haversine chỉ shortlist. Dispatch cuối cùng phải có ETA đường thật.
        if ((bool) ($route['fallback'] ?? true)) {
            return null;
        }

        $etaMin = ((float) ($route['duration_s'] ?? 0)) / 60;
        $distanceKm = ((float) ($route['distance_m'] ?? 0)) / 1000;
        $maxEta = (float) config('shipper_dispatch.available.max_pickup_eta_minutes', 22.0);
        if ($etaMin > $maxEta && $this->effectiveStationBranchId($shipper) !== (int) $order->branch_id) {
            return null;
        }

        $stationId = $this->effectiveStationBranchId($shipper);
        $sameBranch = $stationId && $stationId === (int) $order->branch_id;
        $occupancy = (int) ($context['occupancy'][$stationId] ?? 0);
        $demand = (float) ($context['demand'][$stationId] ?? 0);
        $reservePenalty = $this->reservePenalty($stationId, $occupancy, $demand, 'available');

        $idleMinutes = 0;
        $idleFrom = $shipper->last_station_arrived_at ?: $shipper->updated_at;
        if ($idleFrom) {
            $idleMinutes = max(0, $idleFrom->diffInMinutes(now()));
        }
        $idleBonus = min(
            (float) config('shipper_dispatch.available.max_idle_bonus', 7.0),
            floor($idleMinutes / 10) * (float) config('shipper_dispatch.available.idle_bonus_per_10_minutes', 0.8)
        );

        $score = 100.0
            - $etaMin * (float) config('shipper_dispatch.available.eta_penalty_per_minute', 2.4)
            + ($sameBranch ? (float) config('shipper_dispatch.available.same_branch_bonus', 18.0) : 0.0)
            + ($stationId === null ? (float) config('shipper_dispatch.available.floating_bonus', 4.0) : 0.0)
            + $idleBonus
            - $reservePenalty;

        return [
            'shipper' => $shipper,
            'score' => round($score, 3),
            'pickup_eta_s' => (float) ($route['duration_s'] ?? 0),
            'pickup_distance_m' => (float) ($route['distance_m'] ?? 0),
            'pickup_eta_min' => round($etaMin, 2),
            'pickup_distance_km' => round($distanceKm, 3),
            'same_branch' => $sameBranch,
            'station_branch_id' => $stationId,
            'station_occupancy' => $occupancy,
            'station_weighted_demand' => round($demand, 2),
            'reserve_penalty' => round($reservePenalty, 3),
            'idle_minutes' => $idleMinutes,
            'idle_bonus' => round($idleBonus, 3),
            'order_urgency' => (float) ($context['order_urgency'] ?? 0),
            'origin_source' => $origin['source'],
            'reason' => 'AVAILABLE thuộc home branch của đơn, xếp theo ETA đường thực tế + thời gian rảnh.',
        ];
    }

    private function scoreReturningRow(Order $order, array $row, array $context): ?array
    {
        /** @var Shipper $shipper */
        $shipper = $row['shipper'];
        $origin = $row['origin'];
        $routeToPickup = $this->routing->route(
            $origin['latitude'],
            $origin['longitude'],
            (float) $order->branch->latitude,
            (float) $order->branch->longitude
        );
        if ((bool) ($routeToPickup['fallback'] ?? true)) {
            return null;
        }
        $etaMin = ((float) ($routeToPickup['duration_s'] ?? 0)) / 60;
        if ($etaMin > (float) config('shipper_dispatch.returning.max_pickup_eta_minutes', 18.0)) {
            return null;
        }

        $targetId = (int) ($shipper->returning_to_branch_id ?? 0);
        $target = $shipper->returningBranch;
        $sameTarget = $targetId > 0 && $targetId === (int) $order->branch_id;
        $detourMin = 0.0;
        if ($target && is_numeric($target->latitude) && is_numeric($target->longitude)) {
            $directReturn = $this->routing->route($origin['latitude'], $origin['longitude'], (float) $target->latitude, (float) $target->longitude);
            $pickupToTarget = $this->routing->route((float) $order->branch->latitude, (float) $order->branch->longitude, (float) $target->latitude, (float) $target->longitude);
            if ((bool) ($directReturn['fallback'] ?? true) || (bool) ($pickupToTarget['fallback'] ?? true)) {
                return null;
            }
            $viaSeconds = (float) ($routeToPickup['duration_s'] ?? 0) + (float) ($pickupToTarget['duration_s'] ?? 0);
            $detourMin = max(0, ($viaSeconds - (float) ($directReturn['duration_s'] ?? 0)) / 60);
        }

        $occupancy = (int) ($context['occupancy'][$targetId] ?? 0);
        $demand = (float) ($context['demand'][$targetId] ?? 0);
        $reservePenalty = $this->reservePenalty($targetId, $occupancy, $demand, 'returning');

        $score = 82.0
            - $etaMin * (float) config('shipper_dispatch.returning.eta_penalty_per_minute', 2.2)
            - $detourMin * (float) config('shipper_dispatch.returning.detour_penalty_per_minute', 1.2)
            + ($sameTarget ? (float) config('shipper_dispatch.returning.same_target_branch_bonus', 16.0) : 0.0)
            - $reservePenalty;

        return [
            'shipper' => $shipper,
            'score' => round($score, 3),
            'pickup_eta_s' => (float) ($routeToPickup['duration_s'] ?? 0),
            'pickup_distance_m' => (float) ($routeToPickup['distance_m'] ?? 0),
            'pickup_eta_min' => round($etaMin, 2),
            'pickup_distance_km' => round(((float) ($routeToPickup['distance_m'] ?? 0)) / 1000, 3),
            'return_target_branch_id' => $targetId ?: null,
            'same_as_return_target' => $sameTarget,
            'return_detour_min' => round($detourMin, 2),
            'return_target_occupancy' => $occupancy,
            'return_target_weighted_demand' => round($demand, 2),
            'reserve_penalty' => round($reservePenalty, 3),
            'order_urgency' => (float) ($context['order_urgency'] ?? 0),
            'origin_source' => $origin['source'],
            'reason' => 'RETURNING đang quay về chính home branch, có thể được chuyển hướng nhận đơn cùng chi nhánh.',
        ];
    }

    private function reservePenalty(?int $branchId, int $occupancy, float $demand, string $mode): float
    {
        if (! $branchId) {
            return 0.0;
        }

        $target = ShipperReturnService::TARGET_SHIPPERS_PER_BRANCH;
        $after = max(0, $occupancy - 1);
        $deficit = max(0, $target - $after);
        $reserveWeight = (float) config("shipper_dispatch.{$mode}.branch_reserve_penalty", 5.0);
        $demandWeight = (float) config("shipper_dispatch.{$mode}.branch_demand_penalty", 1.5);

        // Không phạt chỉ vì chưa đủ 5 người nếu chi nhánh hiện không có nhu cầu.
        $needFactor = min(1.0, $demand / 3.0);

        return ($deficit * $reserveWeight * $needFactor) + (max(0, $demand - $after) * $demandWeight);
    }

    /** @return array<int,float> */
    private function weightedDemandByBranch(): array
    {
        $rows = Order::query()
            ->whereNotNull('branch_id')
            ->where(function ($query) {
                $query->whereNull('fulfillment_type')->orWhere('fulfillment_type', 'delivery');
            })
            ->whereIn('status', [OrderStatus::CONFIRMED, OrderStatus::PREPARING, OrderStatus::READY_FOR_DELIVERY])
            ->get(['branch_id', 'status', 'shipper_id']);

        $result = [];
        foreach ($rows as $order) {
            $status = OrderStatus::normalize((string) $order->status);
            $weight = match ($status) {
                OrderStatus::READY_FOR_DELIVERY => 2.5,
                OrderStatus::PREPARING => 1.6,
                default => 1.0,
            };
            if (! $order->shipper_id) {
                $weight += 0.7;
            }
            $branchId = (int) $order->branch_id;
            $result[$branchId] = ($result[$branchId] ?? 0.0) + $weight;
        }

        return $result;
    }

    private function originPoint(Shipper $shipper, bool $allowStationFallback = true): ?array
    {
        if (is_numeric($shipper->current_latitude) && is_numeric($shipper->current_longitude)) {
            return [
                'latitude' => (float) $shipper->current_latitude,
                'longitude' => (float) $shipper->current_longitude,
                'source' => 'gps',
            ];
        }

        if (! $allowStationFallback) {
            return null;
        }

        $branch = $shipper->stationBranch ?: $shipper->user?->branch;
        if ($branch && is_numeric($branch->latitude) && is_numeric($branch->longitude)) {
            return [
                'latitude' => (float) $branch->latitude,
                'longitude' => (float) $branch->longitude,
                'source' => 'station',
            ];
        }

        return null;
    }

    private function effectiveStationBranchId(Shipper $shipper): ?int
    {
        $id = $shipper->user?->branch_id;

        return is_numeric($id) && (int) $id > 0 ? (int) $id : null;
    }

    private function validBranch(Order $order): bool
    {
        return $order->branch
            && is_numeric($order->branch->latitude)
            && is_numeric($order->branch->longitude);
    }

    private function cleanFeatures(array $row): array
    {
        unset($row['shipper'], $row['current_order']);

        return $row;
    }

    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusKm = 6371.0;
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);
        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;

        return $earthRadiusKm * 2 * atan2(sqrt($a), sqrt(max(0, 1 - $a)));
    }
}
