<?php

use App\Console\Commands\AutoCompleteDeliveredOrders;
use App\Console\Commands\AutoCancelStaleOrders;
use App\Console\Commands\CancelExpiredGuestOrders;
use App\Console\Commands\CleanupOldChats;
use App\Console\Commands\CleanupDeliveryOrderChats;
use App\Console\Commands\DispatchWaitingDeliveryOrders;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Tự động huỷ đơn hàng guest chưa xác nhận email sau 15 phút
Schedule::command(CancelExpiredGuestOrders::class)
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// Tự động xoá conversation chat hết hạn (tính từ tin nhắn cuối, ngưỡng = CHAT_EXPIRY_MONTHS)
Schedule::command(CleanupOldChats::class)
    ->daily()
    ->withoutOverlapping()
    ->runInBackground();

// Tự động hủy đơn chờ quán xác nhận quá 30 phút hoặc đứng trạng thái quá 24 giờ.
Schedule::command(AutoCancelStaleOrders::class)
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

// Tự động hoàn thành đơn hàng đã giao sau 30 phút
Schedule::command(AutoCompleteDeliveredOrders::class)
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

// Chat ngắn khách <-> shipper chỉ lưu tối đa 24 giờ.
Schedule::command(CleanupDeliveryOrderChats::class)
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();

// P9: đơn từng thiếu shipper được retry tự động; không cần admin bấm lại trạng thái.
Schedule::command(DispatchWaitingDeliveryOrders::class, ['--limit' => 5])
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();
