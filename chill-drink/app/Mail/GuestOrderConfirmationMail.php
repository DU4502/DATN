<?php

namespace App\Mail;

use App\Models\Order;
use App\Support\GuestOrderAccess;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class GuestOrderConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
        $this->order->loadMissing('orderItems.product', 'branch');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Chill Drink] Đơn hàng '.$this->order->displayCode().' đã tiếp nhận',
        );
    }

    public function content(): Content
    {
        $trackUrl = URL::temporarySignedRoute(
            'checkout.guest.track',
            now()->addDays(7),
            [
                'order' => $this->order->id,
                'token' => $this->order->guest_token,
            ]
        );

        return new Content(
            markdown: 'emails.guest-order-confirmation',
            with: [
                'order' => $this->order,
                'trackUrl' => $trackUrl,
                'convertUrl' => route('checkout.success', $this->order),
            ],
        );
    }
}
