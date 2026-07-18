<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Support\OrderStatus;
use App\Support\RealtimeOrderNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoCompleteDeliveredOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:auto-complete-delivered';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically complete delivered orders after 30 minutes if customer has not confirmed';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Find orders that are delivered for more than 30 minutes
        $thirtyMinutesAgo = now()->subMinutes(30);
        
        $orders = Order::query()
            ->where('status', OrderStatus::DELIVERED)
            ->whereNotNull('delivered_at')
            ->where('delivered_at', '<=', $thirtyMinutesAgo)
            ->get();

        if ($orders->isEmpty()) {
            $this->info('No orders to auto-complete.');
            return self::SUCCESS;
        }

        $count = 0;
        foreach ($orders as $order) {
            try {
                // Update to completed
                $order->status = OrderStatus::COMPLETED;
                
                // Mark COD as paid
                if ($order->payment_method === 'cod' && $order->payment_status !== 'paid') {
                    $order->payment_status = 'paid';
                }
                
                $order->save();

                // Send notification
                RealtimeOrderNotifier::orderStatusUpdated($order);

                $count++;
                
                Log::info("Auto-completed order #{$order->id} after 30 minutes", [
                    'order_id' => $order->id,
                    'delivered_at' => $order->delivered_at,
                ]);
                
            } catch (\Throwable $e) {
                Log::error("Failed to auto-complete order #{$order->id}", [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Auto-completed {$count} orders.");
        
        return self::SUCCESS;
    }
}
