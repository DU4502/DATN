<?php

namespace App\Events;

use App\Models\Branch;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BranchStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Branch $branch
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('branches'),
        ];
    }

    public function broadcastWith(): array
    {
        $isActive = (bool) $this->branch->status;

        return [
            'id' => (int) $this->branch->id,
            'name' => $this->branch->name,
            'status' => $isActive,
            'status_text' => $isActive ? 'Hoạt động' : 'Tạm ngưng',
        ];
    }
}
