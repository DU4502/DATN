<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupOrder extends Model
{
    public const MAX_MEMBERS = 50;

    protected $fillable = ['owner_id', 'name', 'code', 'status', 'closes_at', 'locked_at', 'cancelled_at', 'note', 'order_id'];
    protected $casts = ['closes_at' => 'datetime', 'locked_at' => 'datetime', 'cancelled_at' => 'datetime'];

    public function owner() { return $this->belongsTo(User::class, 'owner_id'); }
    public function members() { return $this->hasMany(GroupOrderMember::class); }
    public function items() { return $this->hasMany(GroupOrderItem::class); }
    public function order() { return $this->belongsTo(Order::class); }

    public function isOpen(): bool
    {
        return $this->status === 'open' && $this->closes_at->isFuture();
    }
}
