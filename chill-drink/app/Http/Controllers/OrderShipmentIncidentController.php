<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\ShipperIncidentService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderShipmentIncidentController extends Controller
{
    public function resolve(Request $request, Order $order, ShipperIncidentService $incidents)
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['keep', 'reassign', 'cancel'])],
        ]);

        $user = $request->user();
        abort_unless($user && ($user->isAdmin() || $user->isSuperAdmin()), 403, 'Chỉ Admin chi nhánh hoặc Super Admin được xử lý sự cố giao hàng.');

        if (! $user->isSuperAdmin()) {
            abort_unless(
                is_numeric($user->branch_id)
                && (int) $user->branch_id === (int) $order->branch_id,
                403,
                'Bạn không có quyền xử lý sự cố của chi nhánh khác.'
            );
        }

        $incident = $incidents->pendingIncident($order);
        if (! $incident) {
            return back()->with('error', 'Sự cố này đã được xử lý hoặc không còn tồn tại.');
        }

        $incidentType = $incident['incident_type'] ?? 'driver_issue';
        $result = match ([$incidentType, $validated['action']]) {
            ['customer_cancel', 'keep'] => $incidents->keepCustomerCancelRequest($order, $user),
            ['customer_cancel', 'cancel'] => $incidents->cancelCustomerRequest($order, $user),
            ['driver_issue', 'keep'] => $incidents->keepCurrentShipper($order, $user),
            ['driver_issue', 'reassign'] => $incidents->reassign($order, $user),
            default => [
                'status' => 'invalid_action',
                'message' => 'Thao tác không phù hợp với luồng sự cố này.',
            ],
        };

        $successStatuses = ['kept', 'assigned', 'cancelled'];
        if (in_array($result['status'] ?? '', $successStatuses, true)) {
            $user->unreadNotifications
                ->filter(fn ($notification) => data_get($notification->data, 'type') === 'shipper_incident_reported'
                    && (int) data_get($notification->data, 'order_id') === (int) $order->id)
                ->each(fn ($notification) => $notification->markAsRead());

            $message = (string) ($result['message'] ?? 'Đã xử lý sự cố.');

            if (($result['status'] ?? null) === 'assigned' && isset($result['distance_m'], $result['duration_s'])) {
                $distanceKm = ((float) $result['distance_m']) / 1000;
                $minutes = max(1, (int) round(((float) $result['duration_s']) / 60));
                $message .= ' Tài xế thay thế cách điểm tiếp quản khoảng '.number_format($distanceKm, 1, ',', '.').' km · '.$minutes.' phút.';
            }

            // Nhánh sự cố phía khách không phát thông báo trạng thái cho khách;
            // đây là quyết định vận hành nội bộ theo yêu cầu nghiệp vụ.
            if (($result['status'] ?? null) === 'cancelled') {
                return back()->with('success', $message);
            }

            return back()->with('success', $message);
        }

        return back()->with('error', (string) ($result['message'] ?? 'Chưa thể xử lý sự cố lúc này.'));
    }
}
