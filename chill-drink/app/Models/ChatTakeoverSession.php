<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatTakeoverSession extends Model
{
    protected $fillable = [
        'conversation_id',
        'super_admin_id',
        'impersonate_as_id',
        'started_at',
        'ended_at',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function superAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'super_admin_id');
    }

    public function impersonateAs(): BelongsTo
    {
        return $this->belongsTo(User::class, 'impersonate_as_id');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('ended_at');
    }

    public static function activeFor(int $conversationId, int $superAdminId): ?self
    {
        return static::query()
            ->where('conversation_id', $conversationId)
            ->where('super_admin_id', $superAdminId)
            ->active()
            ->latest('started_at')
            ->first();
    }

    public static function activeForConversation(int $conversationId): ?self
    {
        return static::query()
            ->where('conversation_id', $conversationId)
            ->active()
            ->latest('started_at')
            ->first();
    }
}
