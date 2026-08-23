<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ShipperIncidentManagementService
{
    private const RESOLUTION_STATUSES = [
        'incident_resolved_keep',
        'incident_resolved_reassign',
        'incident_resolved_cancel',
        'reassigned_out',
    ];

    /**
     * Lịch sử sự cố lấy trực tiếp từ shipment_history — nguồn dữ liệu gốc của P6.
     * Admin chi nhánh truyền branchId; Super Admin truyền null để xem toàn hệ thống.
     */
    public function rows(?int $branchId = null, int $limit = 150): Collection
    {
        $query = $this->baseIssueQuery($branchId);
        if (! $query) {
            return collect();
        }

        $rows = $query
            ->orderByDesc('issue.id')
            ->limit(max(1, $limit))
            ->get();

        return $this->hydrateRows($rows);
    }

    /**
     * Chỉ lấy sự cố thực sự chưa được xử lý ngay từ SQL để feed realtime luôn nhẹ.
     * Không tải hàng chục sự cố đã xử lý rồi mới filter ở PHP.
     */
    public function pendingRows(?int $branchId = null, int $limit = 50): Collection
    {
        $query = $this->baseIssueQuery($branchId);
        if (! $query) {
            return collect();
        }

        $this->applyPendingConstraint($query);

        $rows = $query
            ->orderByDesc('issue.id')
            ->limit(max(1, $limit))
            ->get();

        return $this->hydrateRows($rows, true);
    }

    public function pendingCount(?int $branchId = null): int
    {
        $query = $this->baseIssueQuery($branchId, false);
        if (! $query) {
            return 0;
        }

        $this->applyPendingConstraint($query);

        return (int) $query->count('issue.id');
    }

    private function baseIssueQuery(?int $branchId, bool $withDetails = true): ?Builder
    {
        if (! Schema::hasTable('shipment_history') || ! Schema::hasTable('shipments')) {
            return null;
        }

        $query = DB::table('shipment_history as issue')
            ->join('shipments as shipment', 'shipment.id', '=', 'issue.shipment_id')
            ->join('orders as orders', 'orders.id', '=', 'shipment.order_id')
            ->where('issue.status', 'issue_reported')
            ->when($branchId !== null, fn ($q) => $q->where('orders.branch_id', $branchId));

        if (! $withDetails) {
            return $query;
        }

        return $query
            ->leftJoin('branches as branches', 'branches.id', '=', 'orders.branch_id')
            ->leftJoin('shippers as shippers', 'shippers.id', '=', 'shipment.shipper_id')
            ->leftJoin('users as shipper_user', 'shipper_user.id', '=', 'shippers.user_id')
            ->select([
                'issue.id as incident_id',
                'issue.shipment_id',
                'issue.description',
                'issue.incident_type',
                'issue.created_at as reported_at',
                'orders.id as order_id',
                'orders.order_code',
                'orders.branch_id',
                'orders.status as order_status',
                'orders.guest_name',
                'orders.user_id as customer_user_id',
                'branches.name as branch_name',
                'shipment.shipper_id',
                'shippers.code as shipper_code',
                'shippers.phone as shipper_phone',
                'shippers.vehicle_type',
                'shippers.license_plate',
                'shipper_user.name as shipper_name',
                'shipper_user.phone as shipper_user_phone',
            ]);
    }

    private function applyPendingConstraint(Builder $query): void
    {
        $query->whereNotExists(function ($resolution) {
            $resolution->selectRaw('1')
                ->from('shipment_history as resolution')
                ->whereColumn('resolution.shipment_id', 'issue.shipment_id')
                ->whereColumn('resolution.id', '>', 'issue.id')
                ->whereIn('resolution.status', self::RESOLUTION_STATUSES);
        });
    }

    /**
     * Hydrate theo batch: tối đa 1 query resolution + 1 query tên khách,
     * tránh N+1 (feed polling 4 giây không được tạo ~90 query mỗi lượt).
     */
    private function hydrateRows(Collection $rows, bool $knownPending = false): Collection
    {
        if ($rows->isEmpty()) {
            return collect();
        }

        $resolutionsByShipment = collect();
        if (! $knownPending) {
            $shipmentIds = $rows->pluck('shipment_id')->filter()->unique()->values();
            if ($shipmentIds->isNotEmpty()) {
                $resolutionsByShipment = DB::table('shipment_history')
                    ->whereIn('shipment_id', $shipmentIds->all())
                    ->whereIn('status', self::RESOLUTION_STATUSES)
                    ->orderBy('id')
                    ->get(['id', 'shipment_id', 'status', 'description', 'created_at'])
                    ->groupBy(fn ($row) => (int) $row->shipment_id);
            }
        }

        $customerNames = collect();
        $customerIds = $rows->pluck('customer_user_id')->filter()->unique()->values();
        if ($customerIds->isNotEmpty()) {
            $customerNames = User::query()->whereIn('id', $customerIds)->pluck('name', 'id');
        }

        return $rows->map(function ($row) use ($customerNames, $resolutionsByShipment, $knownPending) {
            $resolution = null;
            if (! $knownPending) {
                $resolution = $resolutionsByShipment
                    ->get((int) $row->shipment_id, collect())
                    ->first(fn ($candidate) => (int) $candidate->id > (int) $row->incident_id);
            }

            $reportedAt = $row->reported_at ? Carbon::parse($row->reported_at) : null;
            $resolvedAt = $resolution?->created_at ? Carbon::parse($resolution->created_at) : null;
            $pending = $knownPending || $resolution === null;

            return [
                'incident_id' => (int) $row->incident_id,
                'shipment_id' => (int) $row->shipment_id,
                'order_id' => (int) $row->order_id,
                'order_code' => $row->order_code ?: ('#'.$row->order_id),
                'branch_id' => is_numeric($row->branch_id) ? (int) $row->branch_id : null,
                'branch_name' => $row->branch_name ?: 'Chưa gán chi nhánh',
                'order_status' => (string) $row->order_status,
                'shipper_id' => (int) $row->shipper_id,
                'shipper_name' => $row->shipper_name ?: $row->shipper_code ?: 'Shipper',
                'shipper_code' => $row->shipper_code,
                'shipper_phone' => $row->shipper_phone ?: $row->shipper_user_phone,
                'vehicle_type' => $row->vehicle_type,
                'license_plate' => $row->license_plate,
                'customer_name' => $row->guest_name ?: ($customerNames[$row->customer_user_id] ?? 'Khách hàng'),
                'description' => (string) ($row->description ?: 'Shipper báo sự cố.'),
                'incident_type' => (string) ($row->incident_type ?: 'driver_issue'),
                'reported_at' => $reportedAt,
                'reported_at_label' => $reportedAt?->format('H:i · d/m/Y'),
                'is_pending' => $pending,
                'resolution_status' => $resolution?->status,
                'resolution_description' => $resolution?->description,
                'resolved_at' => $resolvedAt,
                'resolved_at_label' => $resolvedAt?->format('H:i · d/m/Y'),
            ];
        });
    }
}
