<?php

namespace App\Console\Commands;

use App\Services\DeliveredOrderCompletionService;
use Illuminate\Console\Command;

class AutoCompleteDeliveredOrders extends Command
{
    protected $signature = 'orders:auto-complete-delivered {--limit=200 : Số đơn tối đa xử lý trong một lượt}';

    protected $description = 'Automatically complete delivered orders after 30 minutes if customer has not confirmed';

    public function handle(DeliveredOrderCompletionService $service): int
    {
        $count = $service->completeExpired(max(1, (int) $this->option('limit')));

        $this->info($count > 0
            ? "Auto-completed {$count} delivered order(s)."
            : 'No delivered orders are due for auto-completion.');

        return self::SUCCESS;
    }
}
