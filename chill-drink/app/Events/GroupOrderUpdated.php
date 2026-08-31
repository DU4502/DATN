<?php

namespace App\Events;

use App\Models\GroupOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GroupOrderUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $groupOrderId,
        public string $action,
    ) {
    }

    public static function fromGroup(GroupOrder $group, string $action): self
    {
        return new self((int) $group->id, $action);
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('group-order.'.$this->groupOrderId);
    }

    public function broadcastAs(): string
    {
        return 'group-order.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'group_id' => $this->groupOrderId,
            'action' => $this->action,
            'sent_at' => now()->toIso8601String(),
        ];
    }
}
