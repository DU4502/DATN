<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ShipperIncidentManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ShipperIncidentController extends Controller
{
    public function index(Request $request, ShipperIncidentManagementService $service)
    {
        $branchId = $this->scopeBranchId($request);
        $status = trim((string) $request->query('status', 'pending'));
        if (! in_array($status, ['pending', 'resolved', 'all'], true)) {
            $status = 'pending';
        }
        $q = trim((string) $request->query('q', ''));

        $rows = $service->rows($branchId, 250);

        if ($status === 'pending') {
            $rows = $rows->where('is_pending', true);
        } elseif ($status === 'resolved') {
            $rows = $rows->where('is_pending', false);
        }

        if ($q !== '') {
            $needle = Str::lower($q);
            $rows = $rows->filter(function (array $row) use ($needle) {
                $haystack = Str::lower(implode(' ', [
                    $row['order_code'] ?? '',
                    $row['branch_name'] ?? '',
                    $row['shipper_name'] ?? '',
                    $row['shipper_code'] ?? '',
                    $row['shipper_phone'] ?? '',
                    $row['description'] ?? '',
                    $row['customer_name'] ?? '',
                ]));

                return str_contains($haystack, $needle);
            });
        }

        $rows = $rows->values();
        $pendingCount = $service->pendingCount($branchId);

        // Khi người quản lý đã mở trung tâm sự cố thì đánh dấu các notification
        // sự cố đã đọc. Badge vận hành vẫn dựa trên số sự cố pending nên không mất việc.
        $request->user()?->unreadNotifications
            ?->filter(fn ($notification) => data_get($notification->data, 'type') === 'shipper_incident_reported')
            ->each(fn ($notification) => $notification->markAsRead());

        return view('admin.shipper-incidents.index', [
            'incidents' => $rows,
            'pendingCount' => $pendingCount,
            'filters' => compact('status', 'q'),
            'isRootSuperAdmin' => $request->user()->isSuperAdmin() && ! $request->user()->isViewingAdminWorkspace(),
        ]);
    }

    public function feed(Request $request, ShipperIncidentManagementService $service): JsonResponse
    {
        $branchId = $this->scopeBranchId($request);
        $rows = $service->pendingRows($branchId, 30);

        return response()->json([
            'count' => $rows->count(),
            'latest_incident_id' => (int) ($rows->max('incident_id') ?? 0),
            'incidents' => $rows->map(fn (array $row) => [
                'incident_id' => $row['incident_id'],
                'order_id' => $row['order_id'],
                'order_code' => $row['order_code'],
                'branch_name' => $row['branch_name'],
                'incident_type' => $row['incident_type'] ?? 'driver_issue',
                'shipper_name' => $row['shipper_name'],
                'description' => $row['description'],
                'reported_at_label' => $row['reported_at_label'],
            ])->values(),
            'server_time' => now()->toIso8601String(),
        ]);
    }

    private function scopeBranchId(Request $request): ?int
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            if ($user->isViewingAdminWorkspace()) {
                return $user->adminWorkspaceBranchId() ?? -1;
            }

            return null;
        }

        // Admin không gắn chi nhánh tuyệt đối không được rơi về null (= xem tất cả).
        return is_numeric($user->branch_id) ? (int) $user->branch_id : -1;
    }
}
