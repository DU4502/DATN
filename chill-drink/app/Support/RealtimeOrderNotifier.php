<?php

namespace App\Support;

use App\Events\OrderCreated;
use App\Events\OrderStatusUpdated;
use App\Models\Order;
use App\Notifications\OrderStatusUpdatedNotification;
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
