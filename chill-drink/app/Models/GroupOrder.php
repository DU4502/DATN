<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupOrder extends Model
{
    public const MAX_MEMBERS = 20;
    public const ORDER_WINDOW_MINUTES = 30;
    public const OWNER_PRESENCE_SECONDS = 45;

    protected $fillable = ['owner_id', 'name', 'code', 'status', 'closes_at', 'owner_last_seen_at', 'locked_at', 'cancelled_at', 'note', 'order_id'];
    protected $casts = ['closes_at' => 'datetime', 'owner_last_seen_at' => 'datetime', 'locked_at' => 'datetime', 'cancelled_at' => 'datetime'];

    public function owner() { return $this->belongsTo(User::class, 'owner_id'); }
    public function members() { return $this->hasMany(GroupOrderMember::class); }
    public function items() { return $this->hasMany(GroupOrderItem::class); }
    public function messages() { return $this->hasMany(GroupOrderMessage::class); }
    public function order() { return $this->belongsTo(Order::class); }

    public function isOpen(): bool
    {
        return $this->status === 'open' && $this->closes_at->isFuture();
    }

    public function ownerIsPresent(): bool
    {
        return $this->owner_last_seen_at?->greaterThan(now()->subSeconds(self::OWNER_PRESENCE_SECONDS)) ?? false;
    }

    public function closeIfExpired(): bool
    {
        if ($this->status !== 'open' || $this->closes_at->isFuture()) {
            return false;
        }

        $this->update(['status' => 'closed', 'locked_at' => now()]);

        return true;
    }

    public static function closeExpiredOrders(): int
    {
        return static::query()
            ->where('status', 'open')
            ->where('closes_at', '<=', now())
            ->update(['status' => 'closed', 'locked_at' => now()]);
    }
}
