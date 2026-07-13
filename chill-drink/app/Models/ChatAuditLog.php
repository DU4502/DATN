<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatAuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'conversation_id',
        'actor_id',
        'action',
        'message_id',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public static function record(
        string $action,
        int $conversationId,
        ?int $messageId = null,
        ?array $metadata = null
    ): self {
        return static::create([
            'conversation_id' => $conversationId,
            'actor_id' => auth()->id(),
            'action' => $action,
            'message_id' => $messageId,
            'metadata' => array_merge($metadata ?? [], [
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]),
            'created_at' => now(),
        ]);
    }
}
