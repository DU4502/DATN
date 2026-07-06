<?php

namespace App\Jobs;

use App\Models\Order;
use App\Mail\OrderConfirmationGuest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ProcessGuestOrderEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            Mail::to($this->order->guest_email)
                ->send(new OrderConfirmationGuest($this->order));
        } catch (\Exception $e) {
            Log::error('ProcessGuestOrderEmail failed: ' . $e->getMessage());
            // Retry mechanisms can be handled depending on queue driver configuration
            $this->fail($e);
        }
    }
}
