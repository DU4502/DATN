<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CodSettlementPinSetupCodeNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $code,
        private readonly int $ttlMinutes
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = filled(data_get($notifiable, 'name'))
            ? data_get($notifiable, 'name')
            : 'bạn';

        return (new MailMessage)
            ->subject('Mã xác minh tạo hoặc đổi PIN đối soát COD')
            ->greeting('Xin chào '.$name.',')
            ->line('Bạn vừa yêu cầu tạo hoặc đổi mã PIN đối soát COD trong hệ thống Chill Drink.')
            ->line('Mã xác minh Gmail của bạn: '.$this->code)
            ->line('Mã có hiệu lực trong '.$this->ttlMinutes.' phút.')
            ->line('Sau khi xác minh thành công, bạn có thể lưu PIN đối soát mới.')
            ->line('Nếu bạn không thực hiện thao tác này, hãy bỏ qua email.');
    }
}
