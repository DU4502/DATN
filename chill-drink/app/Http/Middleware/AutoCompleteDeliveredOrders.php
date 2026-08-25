<?php

namespace App\Http\Middleware;

use App\Services\DeliveredOrderCompletionService;
use App\Services\OrderTimeoutService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class AutoCompleteDeliveredOrders
{
    public function handle(Request $request, Closure $next): Response
    {
        /*
         * Scheduler vẫn là cơ chế nền chính. Lớp này là fallback cho local/XAMPP:
         * nếu người dùng quên chạy schedule:work thì request web vẫn kích hoạt các
         * timeout quan trọng. Cache tránh query DB ở mọi request.
         */
        try {
            if (Cache::add('orders:auto-complete-delivered:web-tick', true, now()->addSeconds(20))) {
                app(DeliveredOrderCompletionService::class)->completeExpired(50);
            }

            if (Cache::add('orders:auto-cancel-stale:web-tick', true, now()->addSeconds(30))) {
                app(OrderTimeoutService::class)->cancelExpired(50);
            }
        } catch (\Throwable $exception) {
            report($exception);
            // Không bao giờ làm hỏng request chính chỉ vì tác vụ nền.
        }

        return $next($request);
    }
}
