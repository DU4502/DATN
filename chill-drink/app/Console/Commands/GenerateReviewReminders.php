<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\Review;
use App\Notifications\ReviewAvailableNotification;

class GenerateReviewReminders extends Command
{
    protected $signature = 'notifications:generate-review-reminders';

    protected $description = 'Generate review reminder notifications for completed orders with unreviewed products';

    public function handle(): int
    {
        $this->info('Scanning completed orders for reviewable products...');

        Order::query()
            ->with(['user', 'orderItems.product'])
            ->where('status', 'completed')
            ->chunk(100, function ($orders) {
                foreach ($orders as $order) {
                    $user = $order->user;

                    if (! $user) {
                        continue;
                    }

                    $reviewableProducts = [];

                    foreach ($order->orderItems as $item) {
                        $product = $item->product;
                        if (! $product) {
                            continue;
                        }

                        $eligibleOrderId = Review::nextEligibleCompletedOrderId($user->id, $product->id);
                        if ($eligibleOrderId && (int) $eligibleOrderId === (int) $order->id) {
                            $reviewableProducts[] = $product->id;
                        }
                    }

                    if (empty($reviewableProducts)) {
                        // If there was a previous notification for this order, mark it read
                        $user->notifications()
                            ->where('type', ReviewAvailableNotification::class)
                            ->whereJsonContains('data->order_id', $order->id)
                            ->get()
                            ->each(function ($n) {
                                if (is_null($n->read_at)) {
                                    $n->markAsRead();
                                }
                            });

                        continue;
                    }

                    // Avoid duplicates: check if an unread notification for this order exists
                    $exists = $user->notifications()
                        ->where('type', ReviewAvailableNotification::class)
                        ->whereJsonContains('data->order_id', $order->id)
                        ->whereNull('read_at')
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    // Create notification pointing to the first reviewable product
                    $firstProductId = $reviewableProducts[0] ?? null;
                    $link = $firstProductId ? route('products.show', $firstProductId) . '#reviews' : route('profile.orders');

                    $user->notify(new ReviewAvailableNotification($order->id, $firstProductId, $link));
                }
            });

        $this->info('Done.');

        return self::SUCCESS;
    }
}
