<?php

namespace App\Events;

use App\Models\GroupOrderMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GroupOrderGroupMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public GroupOrderMessage $message)
    {
        $this->message->loadMissing(['sender:id,name']);
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('group-order.' . $this->message->group_order_id);
    }

    public function broadcastAs(): string
    {
        return 'group-order.message.sent';
    }

    public function broadcastWhen(): bool
    {
        return $this->message->recipient_member_id === null;
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'sender_id' => $this->message->sender_member_id,
            'sender_name' => $this->message->sender->name,
            'recipient_id' => $this->message->recipient_member_id,
            'content' => $this->message->content,
            'attachment_name' => $this->message->attachment_name,
            'attachment_mime' => $this->message->attachment_mime,
            'attachment_size' => $this->message->attachment_size,
            'attachment_url' => $this->message->attachment_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->message->attachment_path) : null,
            'read_at' => $this->message->read_at?->toIso8601String(),
            'created_at' => $this->message->created_at->toIso8601String(),
        ];
    }
}
