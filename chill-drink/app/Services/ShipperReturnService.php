<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Order;
use App\Models\Shipper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ShipperReturnService
{
    // Giữ constant để code scoring cũ không vỡ; P15 không còn dùng nó để tự chuyển người giữa chi nhánh.
    public const TARGET_SHIPPERS_PER_BRANCH = 5;
    public const ARRIVAL_RADIUS_M = 90.0;
    public const ARRIVAL_ACCURACY_MAX_M = 120.0;

    public function __construct(private readonly DeliveryRoutingService $routing)
    {
    }

    /**
     * P15: shipper luôn quay về HOME BRANCH = users.branch_id.
     * Không còn tự chọn/cân bằng sang chi nhánh khác sau khi giao xong.
     */
    public function startAfterDelivery(Shipper $shipper, Order $order): array
    {
        $shipper->loadMissing('user.branch');
        $homeBranchId = $shipper->homeBranchId();
        if (! $homeBranchId) {
            $shipper->forceFill(['status' => 'online'])->save();

            return [
                'status' => 'available',
                'message' => 'Shipper chưa được gán home branch. Super Admin cần gán chi nhánh trước khi nhận nhiệm vụ mới.',
            ];
        }

        /** @var Branch|null $branch */
        $branch = Branch::query()
            ->whereKey($homeBranchId)
            ->where('status', 1)
            ->first();

        if (! $branch || ! is_numeric($branch->latitude) || ! is_numeric($branch->longitude)) {
            $shipper->forceFill([
                'status' => 'online',
                'station_branch_id' => $homeBranchId,
                'returning_to_branch_id' => null,
                'returning_started_at' => null,
            ])->save();

            return [
                'status' => 'available',
                'branch' => $branch,
                'message' => 'Home branch chưa có tọa độ hợp lệ; shipper được giữ ở trạng thái sẵn sàng.',
            ];
        }

        $origin = $this->originPoint($shipper, $order);
        if (! $origin) {
            $shipper->forceFill([
                'status' => 'online',
                'station_branch_id' => $homeBranchId,
                'returning_to_branch_id' => null,
                'returning_started_at' => null,
            ])->save();

            return [
                'status' => 'available',
                'branch' => $branch,
                'message' => 'Không có GPS cuối chuyến để tạo chặng quay về; home branch vẫn giữ là '.$branch->name.'.',
            ];
        }

        return DB::transaction(function () use ($shipper, $branch, $origin, $homeBranchId) {
            /** @var Shipper $locked */
            $locked = Shipper::query()->with('user')->whereKey($shipper->id)->lockForUpdate()->firstOrFail();
            if ((int) ($locked->user?->branch_id ?? 0) !== (int) $homeBranchId) {
                return [
                    'status' => 'changed',
                    'message' => 'Home branch vừa thay đổi. Hãy tải lại để nhận tuyến quay về mới.',
                ];
            }

            $distanceM = $this->distanceMeters(
                $origin['latitude'],
                $origin['longitude'],
                (float) $branch->latitude,
                (float) $branch->longitude
            );

            if ($distanceM <= self::ARRIVAL_RADIUS_M) {
                $locked->forceFill([
                    'status' => 'online',
                    'station_branch_id' => $homeBranchId,
                    'returning_to_branch_id' => null,
                    'returning_started_at' => null,
                    'last_station_arrived_at' => now(),
                ])->save();

                return [
                    'status' => 'arrived',
                    'branch' => $branch,
                    'distance_km' => 0.0,
                    'message' => 'Bạn đã ở gần home branch '.$branch->name.' và đang sẵn sàng nhận nhiệm vụ mới.',
                ];
            }

            $route = $this->routing->route(
                $origin['latitude'],
                $origin['longitude'],
                (float) $branch->latitude,
                (float) $branch->longitude
            );

            $locked->forceFill([
                'status' => 'online',
                'station_branch_id' => null,
                'returning_to_branch_id' => $homeBranchId,
                'returning_started_at' => now(),
            ])->save();

            return [
                'status' => 'returning',
                'branch' => $branch,
                'distance_km' => ((float) ($route['distance_m'] ?? $distanceM)) / 1000,
                'duration_s' => (float) ($route['duration_s'] ?? 0),
                'message' => 'Giao xong, bạn quay về home branch '.$branch->name.'.',
            ];
        }, 3);
    }

    public function currentReturn(Shipper $shipper): ?array
    {
        if (! $this->schemaReady()) {
            return null;
        }

        $shipper->loadMissing('user');
        $homeBranchId = $shipper->homeBranchId();
        if (! $homeBranchId) {
            return null;
        }

        // Tự sửa dữ liệu P7 cũ: nếu trước đây hệ thống điều shipper về một chi nhánh khác,
        // từ P15 target quay về luôn là home branch.
        if ($shipper->returning_to_branch_id && (int) $shipper->returning_to_branch_id !== $homeBranchId) {
            $shipper->forceFill([
                'returning_to_branch_id' => $homeBranchId,
                'returning_started_at' => $shipper->returning_started_at ?: now(),
            ])->save();
        }

        if (! $shipper->returning_to_branch_id) {
            return null;
        }

        $branch = Branch::query()
            ->whereKey($homeBranchId)
            ->where('status', 1)
            ->first();

        if (! $branch || ! is_numeric($branch->latitude) || ! is_numeric($branch->longitude)) {
            return null;
        }

        return [
            'branch' => $branch,
            'started_at' => $shipper->returning_started_at,
        ];
    }

    public function recordLocation(Shipper $shipper, float $latitude, float $longitude, ?float $accuracy): ?array
    {
        $return = $this->currentReturn($shipper);
        if (! $return) {
            return null;
        }

        /** @var Branch $branch */
        $branch = $return['branch'];
        $distanceM = $this->distanceMeters($latitude, $longitude, (float) $branch->latitude, (float) $branch->longitude);
        $accuracyValue = is_numeric($accuracy) ? (float) $accuracy : 9999.0;
        $verified = $accuracyValue <= self::ARRIVAL_ACCURACY_MAX_M && $distanceM <= self::ARRIVAL_RADIUS_M;

        if ($verified) {
            DB::transaction(function () use ($shipper, $branch) {
                $locked = Shipper::query()->with('user')->whereKey($shipper->id)->lockForUpdate()->first();
                if (! $locked || (int) ($locked->user?->branch_id ?? 0) !== (int) $branch->id) {
                    return;
                }

                $locked->forceFill([
                    'status' => 'online',
                    'station_branch_id' => $branch->id,
                    'returning_to_branch_id' => null,
                    'returning_started_at' => null,
                    'last_station_arrived_at' => now(),
                ])->save();
            }, 3);
        }

        return [
            'returning' => ! $verified,
            'arrived' => $verified,
            'branch_id' => (int) $branch->id,
            'branch_name' => $branch->name,
            'distance_m' => round($distanceM, 1),
            'radius_m' => self::ARRIVAL_RADIUS_M,
            'accuracy_m' => is_numeric($accuracy) ? round((float) $accuracy, 1) : null,
            'message' => $verified
                ? 'Đã về home branch '.$branch->name.'. Bạn đang sẵn sàng nhận nhiệm vụ mới.'
                : 'Đang quay về home branch '.$branch->name.' · còn khoảng '.(int) round($distanceM).' m.',
        ];
    }

    /**
     * P15: số người của một chi nhánh được tính theo HOME BRANCH, không theo nơi hệ thống
     * từng điều họ tới. Hàm giữ lại để dashboard/scoring cũ không vỡ.
     *
     * @return array<int,int>
     */
    public function occupancyByBranch(?int $excludeShipperId = null): array
    {
        $counts = [];
        $shippers = Shipper::query()->with('user')->get();

        foreach ($shippers as $item) {
            if ($excludeShipperId && (int) $item->id === $excludeShipperId) {
                continue;
            }
            if ($item->status === 'offline') {
                continue;
            }

            $branchId = $item->homeBranchId();
            if (! $branchId) {
                continue;
            }
            $counts[$branchId] = ($counts[$branchId] ?? 0) + 1;
        }

        return $counts;
    }

    public function logicalStatus(Shipper $shipper): string
    {
        if ($this->schemaReady() && $shipper->returning_to_branch_id) {
            return 'returning';
        }

        return (string) $shipper->status;
    }

    private function originPoint(Shipper $shipper, Order $order): ?array
    {
        if (is_numeric($shipper->current_latitude) && is_numeric($shipper->current_longitude)) {
            return [
                'latitude' => (float) $shipper->current_latitude,
                'longitude' => (float) $shipper->current_longitude,
            ];
        }

        if (is_numeric($order->delivery_latitude ?? null) && is_numeric($order->delivery_longitude ?? null)) {
            return [
                'latitude' => (float) $order->delivery_latitude,
                'longitude' => (float) $order->delivery_longitude,
            ];
        }

        $order->loadMissing('address');
        if ($order->address && is_numeric($order->address->latitude) && is_numeric($order->address->longitude)) {
            return [
                'latitude' => (float) $order->address->latitude,
                'longitude' => (float) $order->address->longitude,
            ];
        }

        return null;
    }

    private function schemaReady(): bool
    {
        return Schema::hasTable('shippers')
            && Schema::hasColumn('shippers', 'station_branch_id')
            && Schema::hasColumn('shippers', 'returning_to_branch_id');
    }

    private function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusM = 6371000.0;
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);
        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;

        return $earthRadiusM * 2 * atan2(sqrt($a), sqrt(max(0, 1 - $a)));
    }
}
