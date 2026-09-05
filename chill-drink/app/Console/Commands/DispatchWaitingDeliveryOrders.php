<?php

namespace App\Console\Commands;

use App\Services\ShipperDispatchService;
use Illuminate\Console\Command;

class DispatchWaitingDeliveryOrders extends Command
{
    protected $signature = 'delivery:dispatch-waiting {--limit=5 : Số đơn tối đa thử điều phối trong một lượt}';

    protected $description = 'P9: tự điều phối lại các đơn delivery đã sẵn sàng giao nhưng chưa có shipper';

    public function handle(ShipperDispatchService $dispatch): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $summary = $dispatch->dispatchWaitingOrders($limit);

        if (($summary['scanned'] ?? 0) === 0) {
            $this->line('Không có đơn chờ điều phối.');
            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Đã quét %d đơn: gán %d, còn chờ %d, lỗi %d.',
            (int) ($summary['scanned'] ?? 0),
            (int) ($summary['assigned'] ?? 0),
            (int) ($summary['waiting'] ?? 0),
            (int) ($summary['errors'] ?? 0),
        ));

        return self::SUCCESS;
    }
}
