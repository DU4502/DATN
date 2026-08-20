<?php

namespace App\Services;

use App\Models\Order;
use App\Support\OrderStatus;
use App\Support\RealtimeOrderNotifier;
use Illuminate\Support\Facades\Log;

class OrderTimeoutService
{
    /**
     * Các trạng thái đã vào luồng xử lý nhưng chưa kết thúc.
     * PENDING dùng luật riêng 2 giờ; DELIVERED dùng cơ chế auto-complete 30 phút.
     */
    private const INACTIVE_STATUSES = [
        OrderStatus::CONFIRMED,
        OrderStatus::PREPARING,
        OrderStatus::READY_FOR_DELIVERY,
        OrderStatus::SHIPPER_PICKED_UP,
        OrderStatus::DELIVERING,
        OrderStatus::READY_FOR_PICKUP,

        // Alias dữ liệu cũ để những đơn legacy không bị bỏ sót.
        'processing',
        'in_progress',
        'shipping',
        'shipped',
        'shipper_accepted',
    ];

    public function __construct(
        private readonly OrderCancellationService $cancellations,
    ) {
    }

    /**
     * @return array{pending_cancelled:int,inactive_cancelled:int,total:int}
     */
    public function cancelExpired(?int $limit = null): array
    {
        $pendingHours = max(1, (int) config('order_timeouts.pending_hours', 2));
        $inactiveHours = max(1, (int) config('order_timeouts.inactive_hours', 24));
        $limit = max(1, $limit ?? (int) config('order_timeouts.batch_limit', 200));

        $pendingThreshold = now()->subHours($pendingHours);
        $inactiveThreshold = now()->subHours($inactiveHours);

        $pendingCancelled = 0;
        $inactiveCancelled = 0;

        // Luật 1: khách đã đặt nhưng quán không xác nhận trong 2 giờ.
        // Đơn giao sau vẫn phải được quán xác nhận trong 2 giờ kể từ lúc đặt;
        // xác nhận chỉ là nhận đơn, không có nghĩa bắt đầu pha chế ngay.
        $pendingIds = Order::query()
            ->where('status', OrderStatus::PENDING)
            ->where('created_at', '<=', $pendingThreshold)
            ->orderBy('created_at')
            ->limit($limit)
            ->pluck('id');

        foreach ($pendingIds as $orderId) {
            $order = Order::query()->find($orderId);
            if (! $order) {
                continue;
            }

            $reason = sprintf(
                'Tự động hủy: quán không xác nhận đơn trong %d giờ kể từ khi khách đặt hàng.',
                $pendingHours
            );

            try {
                $result = $this->cancellations->cancelIfStale(
                    $order,
                    $reason,
                    OrderStatus::PENDING,
                    $pendingThreshold,
                    'created_at',
                );

                if ($result === null) {
                    continue;
                }

                $cancelled = $result['order']->fresh();
                RealtimeOrderNotifier::orderStatusUpdated($cancelled);
                $pendingCancelled++;

                Log::info('Auto-cancelled order because store did not confirm in time.', [
                    'order_id' => $cancelled->id,
                    'timeout_hours' => $pendingHours,
                    'created_at' => $cancelled->created_at?->toDateTimeString(),
                ]);
            } catch (\Throwable $exception) {
                report($exception);
                Log::warning('Failed to auto-cancel pending order.', [
                    'order_id' => $orderId,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        $remaining = max(0, $limit - $pendingCancelled);
        if ($remaining === 0) {
            return [
                'pending_cancelled' => $pendingCancelled,
                'inactive_cancelled' => 0,
                'total' => $pendingCancelled,
            ];
        }

        /*
         * Luật 2: đã vào luồng xử lý nhưng đứng nguyên trạng thái 24 giờ.
         * Với đơn hẹn giờ, mốc đếm 24 giờ không bắt đầu trước scheduled_at /
         * scheduled_delivery_time để tránh hủy nhầm đơn đặt trước cho ngày sau.
         * Sau mốc giao hẹn, mỗi lần đổi trạng thái sẽ reset đồng hồ 24 giờ.
         */
        $inactiveOrders = Order::query()
            ->whereIn('status', self::INACTIVE_STATUSES)
            ->where(function ($query) use ($inactiveThreshold) {
                $query->where('status_changed_at', '<=', $inactiveThreshold)
                    ->orWhere(function ($fallback) use ($inactiveThreshold) {
                        $fallback->whereNull('status_changed_at')
                            ->where('created_at', '<=', $inactiveThreshold);
                    });
            })
            ->where(function ($query) use ($inactiveThreshold) {
                $query->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '<=', $inactiveThreshold);
            })
            ->where(function ($query) use ($inactiveThreshold) {
                $query->whereNull('scheduled_delivery_time')
                    ->orWhere('scheduled_delivery_time', '<=', $inactiveThreshold);
            })
            ->orderByRaw('COALESCE(status_changed_at, created_at) asc')
            ->limit(max($remaining * 3, $remaining))
            ->get();

        foreach ($inactiveOrders as $order) {
            if ($inactiveCancelled >= $remaining) {
                break;
            }

            $normalizedStatus = OrderStatus::normalize((string) $order->status);
            if (! in_array($normalizedStatus, [
                OrderStatus::CONFIRMED,
                OrderStatus::PREPARING,
                OrderStatus::READY_FOR_DELIVERY,
                OrderStatus::SHIPPER_PICKED_UP,
                OrderStatus::DELIVERING,
                OrderStatus::READY_FOR_PICKUP,
            ], true)) {
                continue;
            }

            $reason = sprintf(
                'Tự động hủy: đơn không chuyển trạng thái trong %d giờ, đang dừng ở "%s".',
                $inactiveHours,
                OrderStatus::label($normalizedStatus)
            );

            try {
                $result = $this->cancellations->cancelIfStale(
                    $order,
                    $reason,
                    $normalizedStatus,
                    $inactiveThreshold,
                    'status_or_schedule',
                );

                if ($result === null) {
                    continue;
                }

                $cancelled = $result['order']->fresh();
                RealtimeOrderNotifier::orderStatusUpdated($cancelled);
                $inactiveCancelled++;

                Log::info('Auto-cancelled order because status did not advance in time.', [
                    'order_id' => $cancelled->id,
                    'old_status' => $normalizedStatus,
                    'timeout_hours' => $inactiveHours,
                    'status_changed_at' => $order->status_changed_at?->toDateTimeString(),
                ]);
            } catch (\Throwable $exception) {
                report($exception);
                Log::warning('Failed to auto-cancel inactive order.', [
                    'order_id' => $order->id,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return [
            'pending_cancelled' => $pendingCancelled,
            'inactive_cancelled' => $inactiveCancelled,
            'total' => $pendingCancelled + $inactiveCancelled,
        ];
    }
}
