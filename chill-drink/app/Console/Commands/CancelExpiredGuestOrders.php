<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Support\OrderStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CancelExpiredGuestOrders extends Command
{
    protected $signature = 'orders:cancel-expired-guest {--dry-run : Hiển thị các đơn sẽ bị huỷ mà không thực sự huỷ}';

    protected $description = 'Huỷ các đơn hàng guest chưa được xác nhận qua email sau khi hết hạn 15 phút';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $expiredOrders = Order::query()
            ->where('status', OrderStatus::AWAITING_EMAIL_CONFIRMATION)
            ->where('confirmation_token_expires_at', '<', now())
            ->get();

        if ($expiredOrders->isEmpty()) {
            $this->info('Không có đơn hàng hết hạn xác nhận.');
            return self::SUCCESS;
        }

        $this->info("Tìm thấy {$expiredOrders->count()} đơn hàng hết hạn xác nhận.");

        foreach ($expiredOrders as $order) {
            if ($dryRun) {
                $this->line("  [dry-run] Sẽ huỷ đơn #{$order->id} (email: {$order->guest_email}, hết hạn: {$order->confirmation_token_expires_at})");
                continue;
            }

            $order->update([
                'status'                        => OrderStatus::CANCELLED,
                'confirmation_token'            => null,
                'confirmation_token_expires_at' => null,
            ]);

            Log::info('Đơn hàng guest hết hạn xác nhận đã bị huỷ.', [
                'order_id'   => $order->id,
                'guest_email' => $order->guest_email,
            ]);

            $this->line("  Đã huỷ đơn #{$order->id} ({$order->guest_email})");
        }

        if (! $dryRun) {
            $this->info("Đã huỷ {$expiredOrders->count()} đơn hàng hết hạn.");
        }

        return self::SUCCESS;
    }
}
