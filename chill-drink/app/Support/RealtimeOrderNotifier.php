<?php

namespace App\Support;

use App\Events\OrderCreated;
use App\Events\OrderStatusUpdated;
use App\Models\Order;
use App\Notifications\OrderStatusUpdatedNotification;
use App\Notifications\DeliveryDelayReportedNotification;
use Illuminate\Support\Facades\Log;

class RealtimeOrderNotifier
{
    public static function orderCreated(Order $order): void
    {
        if (! self::isConfigured()) {
            return;
        }

        try {
            event(new OrderCreated($order->fresh(['user'])));
        } catch (\Throwable $exception) {
            Log::warning('Không thể broadcast đơn hàng mới.', [
                'order_id' => $order->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public static function orderStatusUpdated(Order $order): void
    {
        $order = $order->fresh(['user']);

        if ($order->user) {
            try {
                $order->user->notify(new OrderStatusUpdatedNotification($order));
            } catch (\Throwable $exception) {
                Log::warning('Không thể lưu thông báo cập nhật trạng thái đơn hàng.', [
                    'order_id' => $order->id,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        if (! self::isConfigured()) {
            return;
        }

        try {
            event(new OrderStatusUpdated($order));
        } catch (\Throwable $exception) {
            Log::warning('Không thể broadcast cập nhật trạng thái đơn hàng.', [
                'order_id' => $order->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Gửi lời xin lỗi riêng cho khách khi sự cố thuộc về tài xế/chuyến giao.
     * Luồng sự cố phía khách không gọi method này để không làm lộ yêu cầu nội bộ.
     */
    public static function deliveryDelayReported(Order $order, string $incidentDescription = '', ?int $incidentId = null): void
    {
        $order = $order->fresh(['user']);

        if (! $order->user) {
            return;
        }

        try {
            $alreadyNotified = $incidentId !== null
                ? $order->user->notifications()
                    ->where('data->type', 'delivery_delay_reported')
                    ->where('data->incident_id', $incidentId)
                    ->exists()
                : false;

            if (! $alreadyNotified) {
                $order->user->notify(new DeliveryDelayReportedNotification($order, $incidentDescription, $incidentId));
            }
        } catch (\Throwable $exception) {
            Log::warning('Không thể lưu thông báo giao hàng chậm trễ.', [
                'order_id' => $order->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public static function isConfigured(): bool
    {
        $driver = (string) config('broadcasting.default');

        if ($driver === '' || in_array($driver, ['null', 'log'], true)) {
            return false;
        }

        if ($driver === 'pusher') {
            $connection = config('broadcasting.connections.pusher', []);

            return filled($connection['key'] ?? null)
                && filled($connection['secret'] ?? null)
                && filled($connection['app_id'] ?? null);
        }

        if ($driver === 'reverb') {
            $connection = config('broadcasting.connections.reverb', []);

            return filled($connection['key'] ?? null)
                && filled($connection['secret'] ?? null)
                && filled($connection['app_id'] ?? null);
        }

        return true;
    }
}
