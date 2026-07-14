<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class ScheduledDelivery
{
    public const PREPARATION_MINUTES = 30;
    public const OPEN_TIME = '07:00';
    public const CLOSE_TIME = '22:00';

    public static function validate(?string $value): ?string
    {
        if (! $value) return 'Vui lòng chọn ngày và giờ muốn nhận hàng.';

        try { $time = Carbon::parse($value); }
        catch (\Throwable) { return 'Ngày giờ nhận hàng không hợp lệ.'; }

        if ($time->lt(now()->addMinutes(self::PREPARATION_MINUTES))) {
            return 'Thời gian nhận hàng phải cách hiện tại ít nhất 30 phút.';
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
}
