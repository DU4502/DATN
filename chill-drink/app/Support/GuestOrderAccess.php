<?php

namespace App\Support;

use App\Models\Order;
use Illuminate\Http\Request;

final class GuestOrderAccess
{
    public static function canView(Order $order, ?Request $request = null): bool
    {
        $request ??= request();

        if (auth()->check() && (int) $order->user_id === (int) auth()->id()) {
            return true;
        }

        if (! $order->isGuest()) {
            return false;
        }

        $token = self::tokenFromRequest($request, $order);

        return filled($token) && hash_equals((string) $order->guest_token, (string) $token);
    }

    public static function tokenFromRequest(?Request $request, Order $order): ?string
    {
        $request ??= request();

        if ($request->filled('token')) {
            return (string) $request->query('token');
        }

        return session("guest_order_tokens.{$order->id}");
    }

    public static function remember(Order $order): void
    {
        if (! $order->isGuest() || blank($order->guest_token)) {
            return;
        }

        session(["guest_order_tokens.{$order->id}" => $order->guest_token]);
    }

    public static function storeConvertPayload(Order $order): void
    {
        if (! $order->isGuest()) {
            return;
        }

        session([
            'guest_convert' => [
                'order_id' => (int) $order->id,
                'name' => (string) $order->guest_name,
                'phone' => (string) $order->guest_phone,
                'email' => (string) $order->guest_email,
            ],
        ]);
    }
}
