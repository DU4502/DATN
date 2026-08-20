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
            'action' => ['required', Rule::in(['keep', 'reassign'])],
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

        $result = $validated['action'] === 'keep'
            ? $incidents->keepCurrentShipper($order, $user)
            : $incidents->reassign($order, $user);

        $successStatuses = ['kept', 'assigned'];
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

            return back()->with('success', $message);
        }

        return back()->with('error', (string) ($result['message'] ?? 'Chưa thể xử lý sự cố lúc này.'));
    }
}
