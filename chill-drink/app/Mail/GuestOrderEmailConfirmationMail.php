<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
class GuestOrderEmailConfirmationMail extends Mailable
{
    // KHÔNG dùng Queueable — gửi synchronous để tránh phụ thuộc queue worker
    use SerializesModels;

    public function __construct(public Order $order)
    {
        $this->order->loadMissing('orderItems.product', 'branch');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Chill Drink] Xác nhận đơn hàng #' . $this->order->id . ' của bạn',
        );
    }

    public function content(): Content
    {
        $confirmUrl = route('checkout.guest.confirm-email', [
            'order' => $this->order->id,
            'token' => $this->order->confirmation_token,
        ]);

        return new Content(
            view: 'emails.guest-order-email-confirmation',
            with: [
                'order'      => $this->order,
                'confirmUrl' => $confirmUrl,
                'expiresAt'  => $this->order->confirmation_token_expires_at,
            ],
        );
    }
}
