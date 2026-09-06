<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderConfirmationGuest extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $points;
    public $trackingUrl;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
        $this->points = $order->pointsEarnable();
        // Giả lập Tracking URL frontend
        $this->trackingUrl = config('app.url') . '/orders/' . $order->guest_token;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Xác nhận đơn hàng ' . $this->order->displayCode() . ' từ Chill Drink')
                    ->view('emails.guest_order_confirmation');
    }
}
