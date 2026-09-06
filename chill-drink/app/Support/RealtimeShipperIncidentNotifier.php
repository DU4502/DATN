<?php

namespace App\Support;

use App\Events\ShipperIncidentReported;
use App\Models\Order;
use App\Models\User;
use App\Notifications\ShipperIncidentReportedNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;

class RealtimeShipperIncidentNotifier
{
    public static function reported(Order $order, array $incident): void
    {
        $order->loadMissing('branch');

        try {
            if (Schema::hasTable('notifications')) {
                $recipients = User::query()
                    ->where(function ($query) use ($order) {
                        $query->where(function ($adminQuery) use ($order) {
                            $adminQuery->where('role_id', 2)
                                ->where('branch_id', $order->branch_id);
                        })
                        ->orWhere('role_id', 3)
                        ->orWhereRaw('LOWER(email) = ?', [strtolower(User::SUPER_ADMIN_EMAIL)]);
                    })
                    ->when(Schema::hasColumn('users', 'is_active'), fn ($q) => $q->where('is_active', true))
                    ->get()
                    ->unique('id')
                    ->values();

                if ($recipients->isNotEmpty()) {
                    Notification::send($recipients, new ShipperIncidentReportedNotification($order, $incident));
                }
            }
        } catch (\Throwable $exception) {
            Log::warning('Không thể lưu thông báo sự cố shipper.', [
                'order_id' => $order->id,
                'message' => $exception->getMessage(),
            ]);
        }

        if (! RealtimeOrderNotifier::isConfigured()) {
            return;
        }

        try {
            event(new ShipperIncidentReported($order->fresh(['branch']), $incident));
        } catch (\Throwable $exception) {
            Log::warning('Không thể broadcast sự cố shipper.', [
                'order_id' => $order->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
