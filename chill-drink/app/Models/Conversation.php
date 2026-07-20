<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = [
        'user_id',
        'branch_id',
        'cskh_id',
        'order_id',
        'subject',
        'status',
        'last_message_at',
        'guest_name',
        'guest_email',
        'guest_token',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cskh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cskh_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
    }

    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function activeTakeoverSession()
    {
        return $this->hasOne(ChatTakeoverSession::class)->whereNull('ended_at')->latestOfMany('started_at');
    }

    public function takeoverSessions(): HasMany
    {
        return $this->hasMany(ChatTakeoverSession::class);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('cskh_id');
    }

    public function scopeForGuest($query, string $token)
    {
        return $query->where('guest_token', $token);
    }

    public function isGuest(): bool
    {
        return is_null($this->user_id) && !is_null($this->guest_token);
    }

    public function isOwnedBy(?int $userId, ?string $guestToken): bool
    {
        if ($userId) {
            return $this->user_id === $userId;
        }

        if ($guestToken) {
            return $this->guest_token === $guestToken;
        }

        return false;
    }
}
