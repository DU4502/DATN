<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailVerificationCodeNotification extends Notification
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
            ->subject('Mã xác minh tài khoản Chill Drink')
            ->greeting('Xin chào '.$name.',')
            ->line('Dùng mã bên dưới để xác minh email tài khoản Chill Drink.')
            ->line('Mã xác minh: '.$this->code)
            ->line('Mã có hiệu lực trong '.$this->ttlMinutes.' phút.')
            ->line('Nếu bạn không tạo tài khoản, hãy bỏ qua email này.');
    }
}
