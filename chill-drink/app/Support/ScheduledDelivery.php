<?php

namespace App\Support;

use App\Models\Order;
use Illuminate\Support\Carbon;

class ScheduledDelivery
{
    public const PICKUP_PREPARATION_MINUTES = 30;
    public const DELIVERY_OPERATION_MINUTES = 60;
    public const PAYMENT_WINDOW_MINUTES = 15;
    public const OPEN_TIME = '07:00';
    public const CLOSE_TIME = '22:00';

    public static function operationalLeadMinutes(string $fulfillmentType = 'delivery'): int
    {
        return $fulfillmentType === 'pickup'
            ? self::PICKUP_PREPARATION_MINUTES
            : self::DELIVERY_OPERATION_MINUTES;
    }

    public static function minimumBookingLeadMinutes(string $fulfillmentType = 'delivery'): int
    {
        return self::operationalLeadMinutes($fulfillmentType) + self::PAYMENT_WINDOW_MINUTES;
    }

    public static function validate(?string $value, string $fulfillmentType = 'delivery'): ?string
    {
        if (! $value) return 'Vui lòng chọn ngày và giờ muốn nhận hàng.';

        try { $time = Carbon::parse($value); }
        catch (\Throwable) { return 'Ngày giờ nhận hàng không hợp lệ.'; }

        $minimumLead = self::minimumBookingLeadMinutes($fulfillmentType);
        if ($time->lt(now()->addMinutes($minimumLead))) {
            return "Thời gian nhận hàng phải cách hiện tại ít nhất {$minimumLead} phút để thanh toán và chuẩn bị đơn.";
        }
        if (! $time->isSameDay(now())) {
            return 'Đặt giao sau chỉ áp dụng trong ngày hôm nay.';
        }

        $clock = $time->format('H:i');
        if ($clock < self::OPEN_TIME || $clock > self::CLOSE_TIME) {
            return 'Thời gian nhận phải trong giờ mở cửa 07:00–22:00.';
        }

        return null;
    }

    public static function canStartPreparation(Order $order): bool
    {
        if ($order->delivery_type !== 'scheduled') {
            return true;
        }

        $scheduledAt = $order->scheduled_delivery_time ?? $order->scheduled_at;
        $leadMinutes = self::operationalLeadMinutes((string) ($order->fulfillment_type ?? 'delivery'));

        return $scheduledAt !== null
            && now()->greaterThanOrEqualTo($scheduledAt->copy()->subMinutes($leadMinutes));
    }

    public static function preparationBlockedMessage(Order $order): string
    {
        $scheduledAt = $order->scheduled_delivery_time ?? $order->scheduled_at;
        $leadMinutes = self::operationalLeadMinutes((string) ($order->fulfillment_type ?? 'delivery'));
        $startAt = $scheduledAt?->copy()->subMinutes($leadMinutes);

        return $startAt
            ? 'Đơn giao sau chỉ được bắt đầu pha chế từ '.$startAt->format('H:i d/m/Y').' để bảo đảm đồ uống tươi mới.'
            : 'Đơn giao sau thiếu thời gian nhận hợp lệ nên chưa thể bắt đầu pha chế.';
    }

    public static function paymentDeadline(Order $order): ?Carbon
    {
        $scheduledAt = $order->scheduled_delivery_time ?? $order->scheduled_at;

        return $scheduledAt?->copy()->subMinutes(
            self::operationalLeadMinutes((string) ($order->fulfillment_type ?? 'delivery'))
        );
    }

    public static function paymentWindowExpired(Order $order): bool
    {
        $deadline = self::paymentDeadline($order);

        return $order->delivery_type === 'scheduled'
            && ($deadline === null || now()->greaterThan($deadline));
    }
}
