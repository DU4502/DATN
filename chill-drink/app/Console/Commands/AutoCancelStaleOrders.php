<?php

namespace App\Console\Commands;

use App\Services\OrderTimeoutService;
use Illuminate\Console\Command;

class AutoCancelStaleOrders extends Command
{
    protected $signature = 'orders:auto-cancel-stale {--limit= : Số đơn tối đa xử lý trong một lượt}';

    protected $description = 'Tự hủy đơn chờ quán xác nhận quá 2 giờ hoặc đứng nguyên trạng thái quá 24 giờ';

    public function handle(OrderTimeoutService $service): int
    {
        $limit = $this->option('limit');
        $result = $service->cancelExpired($limit !== null ? max(1, (int) $limit) : null);

        if ($result['total'] > 0) {
            $this->info(sprintf(
                'Đã tự hủy %d đơn: %d đơn chờ xác nhận quá hạn, %d đơn đứng trạng thái quá hạn.',
                $result['total'],
                $result['pending_cancelled'],
                $result['inactive_cancelled'],
            ));
        } else {
            $this->info('Không có đơn nào đến hạn tự hủy.');
        }

        return self::SUCCESS;
    }
}
