<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Contracts\Queue\ShouldQueue;

class ReviewAvailableNotification extends Notification
{
    use Queueable;

    protected $orderId;
    protected $productId;
    protected $link;

    public function __construct(int $orderId, ?int $productId = null, ?string $link = null)
    {
        $this->orderId = $orderId;
        $this->productId = $productId;
        $this->link = $link;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'Đánh giá sản phẩm',
            'message' => 'Bạn có sản phẩm đã mua nhưng chưa đánh giá. Hãy chia sẻ trải nghiệm của bạn.',
            'order_id' => $this->orderId,
            'product_id' => $this->productId,
            'link' => $this->link ?? route('profile.orders'),
        ];
    }
}
