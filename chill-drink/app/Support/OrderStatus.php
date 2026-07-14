<?php

namespace App\Support;

use App\Models\Order;

final class OrderStatus
{
    public const PENDING = 'pending';

    public const IN_PROGRESS = 'in_progress';

    public const SHIPPER_ACCEPTED = 'shipper_accepted';

    public const ARRIVED = 'arrived';

    public const COMPLETED = 'completed';

    public const CANCELLED = 'cancelled';

    /**
     * Trạng thái chờ khách xác nhận qua email (ẩn với admin).
     */
    public const AWAITING_EMAIL_CONFIRMATION = 'awaiting_email_confirmation';

    public const SEQUENCE = [
        self::PENDING,
        self::IN_PROGRESS,
        self::SHIPPER_ACCEPTED,
        self::ARRIVED,
        self::COMPLETED,
    ];

    public static function labels(): array
    {
        return [
            self::AWAITING_EMAIL_CONFIRMATION => 'Chờ xác nhận email',
            self::PENDING => 'Chờ xử lý',
            self::IN_PROGRESS => 'Đang thực hiện',
            self::SHIPPER_ACCEPTED => 'Shiper đã nhận đơn',
            self::ARRIVED => 'Đơn hàng đã đến',
            self::COMPLETED => 'Hoàn thành',
            self::CANCELLED => 'Đã hủy',
        ];
    }

    /**
     * Labels hiển thị cho Admin (ẩn trạng thái chờ xác nhận email).
     */
    public static function adminLabels(): array
    {
        $all = self::labels();
        unset($all[self::AWAITING_EMAIL_CONFIRMATION]);

        return $all;
    }

    public static function filterOptions(): array
    {
        return ['' => 'Tất cả trạng thái'] + self::adminLabels();
    }

    public static function label(string $status): string
    {
        $status = self::normalize($status);

        return self::labels()[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }

    public static function normalize(string $status): string
    {
        return match ($status) {
            'processing', 'preparing' => self::IN_PROGRESS,
            'shipping', 'shipped', 'delivering' => self::SHIPPER_ACCEPTED,
            default => $status,
        };
    }

    public static function canTransition(string $from, string $to): bool
    {
        $from = self::normalize($from);
        $to = self::normalize($to);

        if ($from === $to) {
            return true;
        }

        if ($from === self::CANCELLED || $from === self::COMPLETED) {
            return false;
        }

        if ($to === self::CANCELLED) {
            return true;
        }

        $fromIndex = array_search($from, self::SEQUENCE, true);
        $toIndex = array_search($to, self::SEQUENCE, true);

        if ($fromIndex === false || $toIndex === false) {
            return false;
        }

        return $toIndex > $fromIndex;
    }

    /**
     * @return array<string, string>
     */
    public static function selectableOptions(string $current): array
    {
        $current = self::normalize($current);
        $labels = self::labels();

        if (in_array($current, [self::COMPLETED, self::CANCELLED], true)) {
            return [$current => $labels[$current]];
        }

        $options = [];
        $currentIndex = array_search($current, self::SEQUENCE, true);

        if ($currentIndex === false) {
            $currentIndex = 0;
        }

        for ($i = $currentIndex; $i < count(self::SEQUENCE); $i++) {
            $slug = self::SEQUENCE[$i];
            $options[$slug] = $labels[$slug];
        }

        $options[self::CANCELLED] = $labels[self::CANCELLED];

        return $options;
    }

    public static function nextStatus(string $current): ?string
    {
        $current = self::normalize($current);
        $index = array_search($current, self::SEQUENCE, true);

        if ($index === false || $index >= count(self::SEQUENCE) - 1) {
            return null;
        }

        return self::SEQUENCE[$index + 1];
    }

    public static function stepwiseOptions(string $current): array
    {
        $current = self::normalize($current);
        $labels = self::labels();
        $options = [$current => $labels[$current] ?? ucfirst(str_replace('_', ' ', $current))];
        $next = self::nextStatus($current);

        if ($next !== null) {
            $options[$next] = $labels[$next] ?? ucfirst(str_replace('_', ' ', $next));
        }

        return $options;
    }

    public static function canAdvanceTo(string $from, string $to): bool
    {
        $from = self::normalize($from);
        $to = self::normalize($to);

        if (in_array($from, [self::COMPLETED, self::CANCELLED], true)) {
            return $to === $from;
        }

        if ($to === $from) {
            return true;
        }

        return $to === self::nextStatus($from) || $to === self::CANCELLED;
    }

    public static function userBadgeStyles(): array
    {
        return [
            self::PENDING => ['label' => 'Chờ xử lý', 'class' => 'order-status-pending'],
            self::IN_PROGRESS => ['label' => 'Đang thực hiện', 'class' => 'order-status-in-progress'],
            self::SHIPPER_ACCEPTED => ['label' => 'Shiper đã nhận đơn', 'class' => 'order-status-shipper-accepted'],
            self::ARRIVED => ['label' => 'Đơn hàng đã đến', 'class' => 'order-status-arrived'],
            self::COMPLETED => ['label' => 'Hoàn thành', 'class' => 'order-status-completed'],
            self::CANCELLED => ['label' => 'Đã hủy', 'class' => 'order-status-cancelled'],
        ];
    }

    public static function notificationType(string $status): string
    {
        return match (self::normalize($status)) {
            self::PENDING => 'order_pending',
            self::IN_PROGRESS => 'order_in_progress',
            self::SHIPPER_ACCEPTED => 'order_shipper_accepted',
            self::ARRIVED => 'order_arrived',
            self::COMPLETED => 'order_completed',
            self::CANCELLED => 'order_cancelled',
            default => 'order_updated',
        };
    }

    public static function notificationIcon(string $status): string
    {
        return match (self::normalize($status)) {
            self::PENDING => 'bi-hourglass-split',
            self::IN_PROGRESS => 'bi-cup-straw',
            self::SHIPPER_ACCEPTED => 'bi-truck',
            self::ARRIVED => 'bi-geo-alt',
            self::COMPLETED => 'bi-check2-circle',
            self::CANCELLED => 'bi-x-circle',
            default => 'bi-bell',
        };
    }

    public static function notificationIconByType(?string $type): string
    {
        $status = match ($type) {
            'order_pending' => self::PENDING,
            'order_processing', 'order_in_progress' => self::IN_PROGRESS,
            'order_shipped', 'order_shipper_accepted' => self::SHIPPER_ACCEPTED,
            'order_arrived' => self::ARRIVED,
            'order_delivered', 'order_completed' => self::COMPLETED,
            'order_cancelled' => self::CANCELLED,
            default => null,
        };

        return $status ? self::notificationIcon($status) : 'bi-bell';
    }

    /**
     * @return array{type: string, title: string, message: string, status: string, status_label: string}
     */
    public static function notificationPayload(Order $order): array
    {
        $status = self::normalize((string) $order->status);
        $label = self::label($status);
        $orderId = (int) $order->id;

        $content = match ($status) {
            self::PENDING => [
                'title' => "Đơn hàng #{$orderId} - Chờ xử lý",
                'message' => $order->payment_method === 'vnpay' && $order->payment_status !== 'paid'
                    ? "Đơn hàng #{$orderId} đã được tạo với trạng thái Chờ xử lý. Vui lòng thanh toán VNPay để tiếp tục."
                    : "Đơn hàng #{$orderId} đã đặt thành công với trạng thái Chờ xử lý.",
            ],
            self::IN_PROGRESS => [
                'title' => "Đơn hàng #{$orderId} - Đang thực hiện",
                'message' => "Đơn hàng #{$orderId} đã chuyển sang trạng thái Đang thực hiện.",
            ],
            self::SHIPPER_ACCEPTED => [
                'title' => "Đơn hàng #{$orderId} - Shiper đã nhận đơn",
                'message' => "Shiper đã nhận đơn hàng #{$orderId} và đang trên đường giao đến bạn.",
            ],
            self::ARRIVED => [
                'title' => "Đơn hàng #{$orderId} - Đơn hàng đã đến",
                'message' => "Đơn hàng #{$orderId} đã đến. Vui lòng kiểm tra và nhận hàng.",
            ],
            self::COMPLETED => [
                'title' => "Đơn hàng #{$orderId} - Hoàn thành",
                'message' => "Đơn hàng #{$orderId} đã hoàn thành. Cảm ơn bạn đã mua sắm tại Chill Drink!",
            ],
            self::CANCELLED => [
                'title' => "Đơn hàng #{$orderId} - Đã hủy",
                'message' => "Đơn hàng #{$orderId} đã được chuyển sang trạng thái Đã hủy.",
            ],
            default => [
                'title' => "Cập nhật đơn hàng #{$orderId}",
                'message' => "Đơn hàng #{$orderId} đã chuyển sang: {$label}.",
            ],
        };

        return [
            'type' => self::notificationType($status),
            'title' => $content['title'],
            'message' => $content['message'],
            'status' => $status,
            'status_label' => $label,
        ];
    }

    public static function validationRule(): string
    {
        return 'in:'.implode(',', array_keys(self::labels()));
    }
}
