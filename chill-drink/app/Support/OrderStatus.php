<?php

namespace App\Support;

use App\Models\Order;

final class OrderStatus
{
    // Trạng thái chung
    public const PENDING = 'pending';              // Chờ xác nhận
    public const CONFIRMED = 'confirmed';          // Đã xác nhận
    public const PREPARING = 'preparing';          // Đang pha chế
    
    // Trạng thái giao hàng
    public const READY_FOR_DELIVERY = 'ready_for_delivery';  // Sẵn sàng giao
    public const SHIPPER_PICKED_UP = 'shipper_picked_up';    // Shipper đã lấy hàng
    public const DELIVERING = 'delivering';                   // Đang giao
    public const DELIVERED = 'delivered';                     // Đã giao
    
    // Trạng thái lấy tại quán
    public const READY_FOR_PICKUP = 'ready_for_pickup';      // Sẵn sàng lấy
    
    // Trạng thái kết thúc
    public const COMPLETED = 'completed';          // Hoàn thành
    public const CANCELLED = 'cancelled';          // Đã hủy

    /**
     * Trạng thái chờ khách xác nhận qua email (ẩn với admin).
     */
    public const AWAITING_EMAIL_CONFIRMATION = 'awaiting_email_confirmation';

    // Aliases cho tương thích ngược
    public const IN_PROGRESS = 'preparing';        // Map cũ -> mới
    public const SHIPPER_ACCEPTED = 'delivering';  // Map cũ -> mới
    public const ARRIVED = 'delivered';            // Map cũ -> mới

    /**
     * Chuỗi trạng thái cho đơn GIAO HÀNG
     */
    public const DELIVERY_SEQUENCE = [
        self::PENDING,
        self::CONFIRMED,
        self::PREPARING,
        self::READY_FOR_DELIVERY,
        self::SHIPPER_PICKED_UP,
        self::DELIVERING,
        self::DELIVERED,
        self::COMPLETED,
    ];

    /**
     * Chuỗi trạng thái cho đơn TỰ LẤY
     */
    public const PICKUP_SEQUENCE = [
        self::PENDING,
        self::CONFIRMED,
        self::PREPARING,
        self::READY_FOR_PICKUP,
        self::COMPLETED,
    ];

    /**
     * Chuỗi mặc định (delivery)
     */
    public const SEQUENCE = self::DELIVERY_SEQUENCE;

    public static function labels(): array
    {
        return [
            self::AWAITING_EMAIL_CONFIRMATION => 'Chờ xác nhận email',
            self::PENDING => 'Chờ xác nhận',
            self::CONFIRMED => 'Đã xác nhận',
            self::PREPARING => 'Đang pha chế',
            self::READY_FOR_DELIVERY => 'Sẵn sàng giao',
            self::SHIPPER_PICKED_UP => 'Shipper đã lấy hàng',
            self::DELIVERING => 'Đang giao',
            self::DELIVERED => 'Đã giao',
            self::READY_FOR_PICKUP => 'Sẵn sàng lấy',
            self::COMPLETED => 'Hoàn thành',
            self::CANCELLED => 'Đã hủy',
        ];
    }

    /**
     * Labels cho dropdown chuyển trạng thái (dạng hành động)
     */
    public static function actionLabels(): array
    {
        return [
            self::PENDING => 'Chờ xác nhận',
            self::CONFIRMED => 'Xác nhận đơn',
            self::PREPARING => 'Bắt đầu pha chế',
            self::READY_FOR_DELIVERY => 'Sẵn sàng giao',
            self::SHIPPER_PICKED_UP => 'Shipper đã lấy hàng',
            self::DELIVERING => 'Đang giao',
            self::DELIVERED => 'Đã giao',
            self::READY_FOR_PICKUP => 'Sẵn sàng lấy',
            self::COMPLETED => 'Hoàn thành',
            self::CANCELLED => 'Hủy đơn',
        ];
    }

    /**
     * Messages thân thiện với khách hàng
     */
    public static function customerMessages(): array
    {
        return [
            self::PENDING => 'Đơn hàng của bạn đang chờ quán xác nhận',
            self::CONFIRMED => 'Quán đã nhận đơn và sẽ bắt đầu pha chế',
            self::PREPARING => 'Quán đang pha chế đồ uống của bạn',
            self::READY_FOR_DELIVERY => 'Đồ uống đã sẵn sàng, đang chờ shipper đến lấy',
            self::SHIPPER_PICKED_UP => 'Shipper đã nhận hàng từ quán',
            self::DELIVERING => 'Shipper đang trên đường giao đơn đến bạn',
            self::DELIVERED => 'Đơn hàng đã được giao đến bạn',
            self::READY_FOR_PICKUP => 'Đồ uống đã sẵn sàng! Bạn có thể đến quán lấy ngay',
            self::COMPLETED => 'Đơn hàng hoàn thành. Cảm ơn bạn đã tin tưởng!',
            self::CANCELLED => 'Đơn hàng đã được hủy',
        ];
    }

    /**
     * Emoji icons cho từng trạng thái
     */
    public static function icons(): array
    {
        return [
            self::PENDING => '⏳',
            self::CONFIRMED => '✅',
            self::PREPARING => '👨‍🍳',
            self::READY_FOR_DELIVERY => '📦',
            self::SHIPPER_PICKED_UP => '🚀',
            self::DELIVERING => '🚚',
            self::DELIVERED => '✅',
            self::READY_FOR_PICKUP => '✅',
            self::COMPLETED => '🎉',
            self::CANCELLED => '❌',
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
            // Mapping trạng thái cũ sang mới
            'processing', 'in_progress' => self::PREPARING,
            'shipping', 'shipped', 'shipper_accepted' => self::DELIVERING,
            'arrived' => self::DELIVERED,
            default => $status,
        };
    }

    /**
     * Lấy sequence phù hợp dựa trên loại đơn hàng
     */
    public static function getSequence(?string $fulfillmentType = 'delivery'): array
    {
        if ($fulfillmentType === 'pickup') {
            return self::PICKUP_SEQUENCE;
        }
        
        return self::DELIVERY_SEQUENCE;
    }

    /**
     * Kiểm tra có thể chuyển từ trạng thái này sang trạng thái khác không
     */
    public static function canTransition(string $from, string $to, ?string $fulfillmentType = 'delivery'): bool
    {
        $from = self::normalize($from);
        $to = self::normalize($to);

        // Không thể thay đổi nếu đã ở trạng thái cuối
        if (in_array($from, [self::COMPLETED, self::CANCELLED], true)) {
            return false;
        }

        // Giống nhau thì OK
        if ($from === $to) {
            return true;
        }

        // Có thể HỦY từ các trạng thái cho phép
        if ($to === self::CANCELLED) {
            return in_array($from, [
                self::PENDING,
                self::CONFIRMED,
                self::PREPARING
            ], true);
        }

        // Lấy sequence phù hợp
        $sequence = self::getSequence($fulfillmentType);
        
        $fromIndex = array_search($from, $sequence, true);
        $toIndex = array_search($to, $sequence, true);

        if ($fromIndex === false || $toIndex === false) {
            return false;
        }

        // Chỉ cho phép tiến về phía trước
        return $toIndex > $fromIndex;
    }

    /**
     * @return array<string, string>
     */
    public static function selectableOptions(string $current, ?string $fulfillmentType = 'delivery'): array
    {
        $current = self::normalize($current);
        $labels = self::labels();

        if (in_array($current, [self::COMPLETED, self::CANCELLED], true)) {
            return [$current => $labels[$current]];
        }

        $sequence = self::getSequence($fulfillmentType);
        $options = [];
        $currentIndex = array_search($current, $sequence, true);

        if ($currentIndex === false) {
            $currentIndex = 0;
        }

        for ($i = $currentIndex; $i < count($sequence); $i++) {
            $slug = $sequence[$i];
            if (isset($labels[$slug])) {
                $options[$slug] = $labels[$slug];
            }
        }

        // Thêm tùy chọn hủy nếu được phép
        if (in_array($current, [self::PENDING, self::CONFIRMED, self::PREPARING], true)) {
            $options[self::CANCELLED] = $labels[self::CANCELLED];
        }

        return $options;
    }

    public static function nextStatus(string $current, ?string $fulfillmentType = 'delivery'): ?string
    {
        $current = self::normalize($current);
        $sequence = self::getSequence($fulfillmentType);
        $index = array_search($current, $sequence, true);

        if ($index === false || $index >= count($sequence) - 1) {
            return null;
        }

        return $sequence[$index + 1];
    }

    public static function stepwiseOptions(string $current, ?string $fulfillmentType = 'delivery'): array
    {
        $current = self::normalize($current);
        $labels = self::actionLabels();
        $options = [];
        
        // Luôn thêm trạng thái hiện tại (để dropdown có value selected)
        $currentLabel = $labels[$current] ?? self::label($current);
        $options[$current] = $currentLabel;
        
        // Nếu đã completed hoặc cancelled, không cho phép thay đổi
        if (in_array($current, [self::COMPLETED, self::CANCELLED], true)) {
            return $options;
        }
        
        // Nếu đã DELIVERED, KHÔNG cho admin chuyển sang COMPLETED
        // (Khách hàng sẽ tự xác nhận)
        if ($current === self::DELIVERED) {
            return $options;
        }
        
        // Thêm trạng thái tiếp theo nếu có
        $next = self::nextStatus($current, $fulfillmentType);
        if ($next !== null && isset($labels[$next])) {
            $options[$next] = $labels[$next];
        }

        // Thêm tùy chọn hủy nếu được phép
        if (in_array($current, [self::PENDING, self::CONFIRMED, self::PREPARING], true)) {
            $options[self::CANCELLED] = $labels[self::CANCELLED];
        }

        return $options;
    }

    public static function canAdvanceTo(string $from, string $to, ?string $fulfillmentType = 'delivery'): bool
    {
        $from = self::normalize($from);
        $to = self::normalize($to);

        // Không cho phép thay đổi nếu đã hoàn thành hoặc đã hủy
        if (in_array($from, [self::COMPLETED, self::CANCELLED], true)) {
            return $to === $from;
        }

        // Giữ nguyên trạng thái
        if ($to === $from) {
            return true;
        }

        // Cho phép hủy từ các trạng thái đầu
        if ($to === self::CANCELLED) {
            return in_array($from, [self::PENDING, self::CONFIRMED, self::PREPARING], true);
        }

        // Chỉ cho phép tiến đến bước tiếp theo
        return $to === self::nextStatus($from, $fulfillmentType);
    }

    public static function userBadgeStyles(): array
    {
        return [
            self::PENDING => ['label' => 'Chờ xác nhận', 'class' => 'order-status-pending'],
            self::CONFIRMED => ['label' => 'Đã xác nhận', 'class' => 'order-status-confirmed'],
            self::PREPARING => ['label' => 'Đang pha chế', 'class' => 'order-status-preparing'],
            self::READY_FOR_DELIVERY => ['label' => 'Sẵn sàng giao', 'class' => 'order-status-ready'],
            self::SHIPPER_PICKED_UP => ['label' => 'Shipper đã lấy', 'class' => 'order-status-shipper-picked-up'],
            self::DELIVERING => ['label' => 'Đang giao', 'class' => 'order-status-delivering'],
            self::DELIVERED => ['label' => 'Đã giao', 'class' => 'order-status-delivered'],
            self::READY_FOR_PICKUP => ['label' => 'Sẵn sàng lấy', 'class' => 'order-status-ready'],
            self::COMPLETED => ['label' => 'Hoàn thành', 'class' => 'order-status-completed'],
            self::CANCELLED => ['label' => 'Đã hủy', 'class' => 'order-status-cancelled'],
        ];
    }

    public static function notificationType(string $status): string
    {
        return match (self::normalize($status)) {
            self::PENDING => 'order_pending',
            self::CONFIRMED => 'order_confirmed',
            self::PREPARING => 'order_preparing',
            self::READY_FOR_DELIVERY => 'order_ready_for_delivery',
            self::SHIPPER_PICKED_UP => 'order_shipper_picked_up',
            self::DELIVERING => 'order_delivering',
            self::DELIVERED => 'order_delivered',
            self::READY_FOR_PICKUP => 'order_ready_for_pickup',
            self::COMPLETED => 'order_completed',
            self::CANCELLED => 'order_cancelled',
            default => 'order_updated',
        };
    }

    public static function notificationIcon(string $status): string
    {
        return match (self::normalize($status)) {
            self::PENDING => 'bi-hourglass-split',
            self::CONFIRMED => 'bi-check-circle',
            self::PREPARING => 'bi-cup-hot',
            self::READY_FOR_DELIVERY => 'bi-box-seam',
            self::SHIPPER_PICKED_UP => 'bi-send',
            self::DELIVERING => 'bi-truck',
            self::DELIVERED => 'bi-house-check',
            self::READY_FOR_PICKUP => 'bi-bag-check',
            self::COMPLETED => 'bi-check2-circle',
            self::CANCELLED => 'bi-x-circle',
            default => 'bi-bell',
        };
    }

    public static function notificationIconByType(?string $type): string
    {
        $status = match ($type) {
            'order_pending' => self::PENDING,
            'order_confirmed' => self::CONFIRMED,
            'order_processing', 'order_in_progress', 'order_preparing' => self::PREPARING,
            'order_ready_for_delivery' => self::READY_FOR_DELIVERY,
            'order_shipper_picked_up' => self::SHIPPER_PICKED_UP,
            'order_shipped', 'order_shipper_accepted', 'order_delivering' => self::DELIVERING,
            'order_arrived', 'order_delivered' => self::DELIVERED,
            'order_ready_for_pickup' => self::READY_FOR_PICKUP,
            'order_completed' => self::COMPLETED,
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
        $orderCode = $order->displayCode();
        $customerMessage = self::customerMessages()[$status] ?? '';

        $content = match ($status) {
            self::PENDING => [
                'title' => "Đơn hàng {$orderCode} - Chờ xác nhận",
                'message' => $customerMessage,
            ],
            self::CONFIRMED => [
                'title' => "Đơn hàng {$orderCode} - Đã xác nhận",
                'message' => $customerMessage,
            ],
            self::PREPARING => [
                'title' => "Đơn hàng {$orderCode} - Đang pha chế",
                'message' => $customerMessage,
            ],
            self::READY_FOR_DELIVERY => [
                'title' => "Đơn hàng {$orderCode} - Sẵn sàng giao",
                'message' => $customerMessage,
            ],
            self::SHIPPER_PICKED_UP => [
                'title' => "Đơn hàng {$orderCode} - Shipper đã lấy hàng",
                'message' => $customerMessage,
            ],
            self::DELIVERING => [
                'title' => "Đơn hàng {$orderCode} - Đang giao",
                'message' => $customerMessage,
            ],
            self::DELIVERED => [
                'title' => "Đơn hàng {$orderCode} - Đã giao hàng",
                'message' => $customerMessage,
            ],
            self::READY_FOR_PICKUP => [
                'title' => "Đơn hàng {$orderCode} - Sẵn sàng lấy",
                'message' => $customerMessage,
            ],
            self::COMPLETED => [
                'title' => "Đơn hàng {$orderCode} - Hoàn thành",
                'message' => $customerMessage,
            ],
            self::CANCELLED => [
                'title' => "Đơn hàng {$orderCode} - Đã hủy",
                'message' => $order->cancellation_reason
                    ? "Đơn hàng đã được hủy. Lý do: {$order->cancellation_reason}"
                    : $customerMessage,
            ],
            default => [
                'title' => "Cập nhật đơn hàng {$orderCode}",
                'message' => "Đơn hàng {$orderCode} đã chuyển sang: {$label}.",
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

    /**
     * Trả về map [status => badge_color] dùng cho JS phía client.
     */
    public static function badgeColorMap(): array
    {
        return [
            self::PENDING              => 'warning',
            self::CONFIRMED            => 'info',
            self::PREPARING            => 'primary',
            self::READY_FOR_DELIVERY   => 'cyan',
            self::READY_FOR_PICKUP     => 'cyan',
            self::SHIPPER_PICKED_UP    => 'indigo',
            self::DELIVERING           => 'purple',
            self::DELIVERED            => 'teal',
            self::COMPLETED            => 'success',
            self::CANCELLED            => 'danger',
        ];
    }
}
