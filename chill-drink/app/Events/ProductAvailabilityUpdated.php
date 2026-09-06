<?php

namespace App\Events;

use App\Models\BranchProductStatus;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductAvailabilityUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public BranchProductStatus $status)
    {
        $this->status->loadMissing(['branch', 'product']);
    }

    public function broadcastOn(): array
    {
        return [new Channel('branch.'.$this->status->branch_id)];
    }

    public function broadcastAs(): string
    {
        return 'product.availability.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'product_id' => (int) $this->status->product_id,
            'branch_id' => (int) $this->status->branch_id,
            'is_available' => (bool) $this->status->is_available,
            'branch_name' => $this->status->branch?->name,
            'product_name' => $this->status->product?->name,
        ];
    }
}
