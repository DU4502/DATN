<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\ShipperDispatchService;
use App\Services\ShipperIncidentService;
use App\Services\OrderCancellationService;
use App\Support\OrderStatus;
use App\Support\RealtimeOrderNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StaffOrderController extends Controller
{
    /**
     * Nhân viên chỉ xem được đơn hàng của chi nhánh mình.
     */
    private function applyBranchScope($query)
    {
        $user = auth()->user();

        if ($user->branch_id) {
            return $query->where('branch_id', $user->branch_id);
        }

        // Nhân viên chưa được gán chi nhánh: không thấy đơn nào
        return $query->whereRaw('1 = 0');
    }

    public function index(Request $request)
    {
        $filters = [
            'q'              => trim((string) $request->query('q', '')),
            'status'         => trim((string) $request->query('status', '')),
            'payment_status' => trim((string) $request->query('payment_status', '')),
            'date_from'      => trim((string) $request->query('date_from', '')),
            'date_to'        => trim((string) $request->query('date_to', '')),
        ];

        $statusOptions = OrderStatus::filterOptions();

        $orders = Order::query()
            ->with(['user', 'branch', 'address', 'orderItems.product', 'orderItems.productSize.size'])
            ->where('status', '!=', OrderStatus::AWAITING_EMAIL_CONFIRMATION)
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $keyword       = $filters['q'];
                $cleanKeyword  = ltrim($keyword, '#');
                $query->where(function ($sub) use ($keyword, $cleanKeyword) {
                    $sub->where('order_code', 'like', '%' . $cleanKeyword . '%');
                    if (is_numeric($cleanKeyword)) {
                        $sub->orWhere('id', (int) $cleanKeyword);
                        return;
                    }
                    $sub->orWhereHas('user', fn ($u) => $u->where('name', 'like', '%' . $keyword . '%')
                        ->orWhere('email', 'like', '%' . $keyword . '%'));
                    if (Schema::hasColumn('orders', 'guest_name')) {
                        $sub->orWhere('guest_name', 'like', '%' . $keyword . '%');
                    }
                });
            })
            ->when(isset($statusOptions[$filters['status']]) && $filters['status'] !== '',
                fn ($q) => $q->where('status', $filters['status']))
            ->when($filters['payment_status'] !== '',
                fn ($q) => $q->where('payment_status', $filters['payment_status']));

        if (Schema::hasColumn('orders', 'created_at')) {
            if ($filters['date_from'] !== '' && $filters['date_to'] !== '') {
                $orders->whereBetween('created_at', [$filters['date_from'] . ' 00:00:00', $filters['date_to'] . ' 23:59:59']);
            } elseif ($filters['date_from'] !== '') {
                $orders->where('created_at', '>=', $filters['date_from'] . ' 00:00:00');
            } elseif ($filters['date_to'] !== '') {
                $orders->where('created_at', '<=', $filters['date_to'] . ' 23:59:59');
            }
        }

        $orders = $this->applyBranchScope($orders)->latest()->paginate(12)->withQueryString();
        $shipmentIncidents = app(ShipperIncidentService::class)->pendingForOrders($orders->getCollection());

        return view('staff.orders.index', compact('orders', 'filters', 'statusOptions', 'shipmentIncidents'));
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status'              => ['required', OrderStatus::validationRule()],
            'cancellation_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $order = Order::findOrFail($id);
        $user  = auth()->user();

        // Nhân viên chỉ cập nhật đơn hàng thuộc chi nhánh mình
        if (!$user->branch_id || (int) $order->branch_id !== (int) $user->branch_id) {
            abort(403, 'Bạn không có quyền cập nhật đơn hàng của chi nhánh khác.');
        }

        $newStatus      = OrderStatus::normalize($request->status);
        $fulfillmentType = $order->fulfillment_type ?? 'delivery';

        if ($newStatus === OrderStatus::CANCELLED && empty($request->cancellation_reason)) {
            return redirect()->back()->with('error', 'Vui lòng nhập lý do hủy đơn hàng.');
        }

        if (!OrderStatus::canStoreAdvanceTo((string) $order->status, $newStatus, $fulfillmentType)) {
            return redirect()->back()->with('error', 'Quán chỉ được xử lý đơn giao hàng tới bước Sẵn sàng giao. Các bước sau thuộc tài xế.');
        }

        if ($newStatus === OrderStatus::CONFIRMED &&
            $order->payment_method === 'vnpay' &&
            $order->payment_status !== 'paid') {
            return redirect()->back()->with('error', 'Đơn hàng VNPay phải được thanh toán trước khi xác nhận.');
        }

        $oldStatus = $order->status;

        if ($newStatus === OrderStatus::CANCELLED && OrderStatus::normalize((string) $oldStatus) !== OrderStatus::CANCELLED) {
            $cancelResult = app(OrderCancellationService::class)->cancel(
                $order,
                (string) $request->cancellation_reason,
                $user
            );
            $order = $cancelResult['order'];
        } else {
            DB::transaction(function () use ($order, $newStatus, $oldStatus, $user) {
                $order->status            = $newStatus;
                $order->status_changed_at = now();
                $order->status_changed_by = $user->id;

                if ($newStatus === OrderStatus::DELIVERED && OrderStatus::normalize((string) $oldStatus) !== OrderStatus::DELIVERED) {
                    $order->delivered_at = now();
                }

                $order->save();
            });
        }


        $dispatchResult = null;
        if ($newStatus === OrderStatus::CONFIRMED
            && $oldStatus !== OrderStatus::CONFIRMED
            && ($order->fulfillment_type ?? 'delivery') === 'delivery') {
            $dispatchResult = app(ShipperDispatchService::class)
                ->dispatchConfirmedOrder($order->fresh(['branch']));
            $order->refresh();
        }

        if ($newStatus === OrderStatus::COMPLETED && $order->payment_method === 'cod') {
            $order->payment_status = 'paid';
            $order->save();
        }

        if ($newStatus === OrderStatus::COMPLETED && $oldStatus !== OrderStatus::COMPLETED) {
            $order->awardLoyaltyPoints();
        }

        RealtimeOrderNotifier::orderStatusUpdated($order);

        $message = 'Đã cập nhật trạng thái: ' . OrderStatus::label($newStatus) . '.';
        if (($dispatchResult['status'] ?? null) === 'assigned') {
            $shipperName = $dispatchResult['shipper']?->user?->name ?: $dispatchResult['shipper']?->code ?: 'shipper';
            $mode = $dispatchResult['dispatch_mode'] ?? 'available';
            $modeText = match ($mode) {
                'returning' => ' (chuyển hướng shipper đang quay về)',
                'bundle' => ' (ghép chuyến thuận đường)',
                default => '',
            };
            $scoreText = isset($dispatchResult['dispatch_score'])
                ? ' · score '.number_format((float) $dispatchResult['dispatch_score'], 1, ',', '.')
                : '';
            $etaSeconds = (float) ($dispatchResult['dispatch_features']['pickup_eta_s'] ?? 0);
            $etaText = $etaSeconds > 0 ? ' · ETA tới quán ~'.number_format($etaSeconds / 60, 1, ',', '.').' phút' : '';
            $message .= " Đã tự động gán cho {$shipperName}{$modeText}{$scoreText}{$etaText}.";
        } elseif (($dispatchResult['status'] ?? null) === 'waiting') {
            $message .= ' Chưa có shipper rảnh phù hợp, đơn đang chờ hệ thống điều phối.';
        } elseif (($dispatchResult['status'] ?? null) === 'error') {
            $message .= ' Điều phối shipper chưa thành công, vui lòng kiểm tra lại.';
        }

        return redirect()->back()->with('success', $message);
    }
}
