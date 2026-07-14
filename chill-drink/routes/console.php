<?php

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

// Tự động xoá chat cũ
Schedule::command(CleanupOldChats::class)
    ->daily()
    ->withoutOverlapping()
    ->runInBackground();
