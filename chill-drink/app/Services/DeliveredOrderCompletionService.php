<?php

namespace App\Services;

use App\Models\Order;
use App\Support\OrderStatus;
use App\Support\RealtimeOrderNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeliveredOrderCompletionService
{
    public const AUTO_COMPLETE_AFTER_MINUTES = 30;

    /**
     * Hoàn tất các đơn đã ở DELIVERED quá thời gian chờ khách xác nhận.
     * Có lock + re-check để scheduler, middleware và thao tác khách không đua nhau.
     */
    public function completeExpired(int $limit = 100): int
    {
        $threshold = now()->subMinutes(self::AUTO_COMPLETE_AFTER_MINUTES);

        $ids = Order::query()
            ->where('status', OrderStatus::DELIVERED)
            ->whereNotNull('delivered_at')
            ->where('delivered_at', '<=', $threshold)
            ->orderBy('delivered_at')
            ->limit(max(1, $limit))
            ->pluck('id');

        $completed = 0;

        foreach ($ids as $orderId) {
            try {
                $order = DB::transaction(function () use ($orderId, $threshold) {
                    $locked = Order::query()->whereKey($orderId)->lockForUpdate()->first();

                    if (! $locked
                        || OrderStatus::normalize((string) $locked->status) !== OrderStatus::DELIVERED
                        || ! $locked->delivered_at
                        || $locked->delivered_at->gt($threshold)) {
                        return null;
                    }

                    $values = [
                        'status' => OrderStatus::COMPLETED,
                        'status_changed_at' => now(),
                        // null = hệ thống tự động, không giả danh admin/shipper/khách.
                        'status_changed_by' => null,
                    ];

                    if (strtolower((string) $locked->payment_method) === 'cod'
                        && strtolower((string) $locked->payment_status) !== 'paid') {
                        $values['payment_status'] = 'paid';
                    }

                    $locked->forceFill($values)->save();

                    return $locked->fresh();
                }, 3);

                if (! $order instanceof Order) {
                    continue;
                }

                if (strtolower((string) $order->payment_method) === 'cod' && $order->shipper_id) {
                    $shipper = \App\Models\Shipper::query()->find($order->shipper_id);
                    if ($shipper) {
                        app(ShipperCodService::class)->recordCollection($order->fresh(), $shipper);
                    }
                }

                // Hàm này đã tự chống cộng điểm hai lần bằng reference order.
                $order->awardLoyaltyPoints();
                RealtimeOrderNotifier::orderStatusUpdated($order);

                $completed++;

                Log::info('Auto-completed delivered order after customer confirmation timeout.', [
                    'order_id' => $order->id,
                    'delivered_at' => $order->delivered_at?->toDateTimeString(),
                    'timeout_minutes' => self::AUTO_COMPLETE_AFTER_MINUTES,
                ]);
            } catch (\Throwable $exception) {
                report($exception);
                Log::warning('Could not auto-complete delivered order.', [
                    'order_id' => $orderId,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return $completed;
    }
}
