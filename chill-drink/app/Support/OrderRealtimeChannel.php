<?php

namespace App\Support;

use App\Models\Order;

final class OrderRealtimeChannel
{
    public static function authenticated(Order $order): string
    {
        return 'order.'.(int) $order->id;
    }

    public static function guest(Order $order): ?string
    {
        if (! $order->isGuest() || blank($order->guest_token)) {
            return null;
        }

        return 'guest-order.'.hash('sha256', (string) $order->guest_token);
    }
}
