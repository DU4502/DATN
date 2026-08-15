<?php

use App\Console\Commands\AutoCompleteDeliveredOrders;
use App\Console\Commands\CancelExpiredGuestOrders;
use App\Console\Commands\CleanupOldChats;
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

// Tự động hoàn thành đơn hàng đã giao sau 30 phút
Schedule::command(AutoCompleteDeliveredOrders::class)
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

// Tự động đóng phiên chat không hoạt động sau 24h
Schedule::command('chat:close-inactive')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();
