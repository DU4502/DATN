<?php

namespace App\Console\Commands;

use App\Models\DeliveryOrderMessage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class CleanupDeliveryOrderChats extends Command
{
    protected $signature = 'delivery-chat:cleanup';
    protected $description = 'Xóa tin nhắn khách - shipper theo đơn đã quá 24 giờ';

    public function handle(): int
    {
        if (! Schema::hasTable('delivery_order_messages')) {
            $this->warn('Bỏ qua cleanup vì bảng delivery_order_messages chưa tồn tại.');

            return self::SUCCESS;
        }

        $deleted = DeliveryOrderMessage::query()
            ->where('created_at', '<', now()->subHours(24))
            ->delete();

        $this->info("Đã xóa {$deleted} tin nhắn giao hàng quá 24 giờ.");

        return self::SUCCESS;
    }
}
