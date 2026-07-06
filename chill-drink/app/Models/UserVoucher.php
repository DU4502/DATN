<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserVoucher extends Model
{
    protected $fillable = [
        'user_id',
        'coupon_id',
        'guest_identifier',
        'code',
        'is_used',
        'expires_at',
        'used_at',
        'redeemed_at',
    ];

    protected $casts = [
        'redeemed_at' => 'datetime',
        'used_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_used' => 'boolean',
    ];

    /**
     * Get the user that received the voucher.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the voucher (coupon).
     */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'coupon_id', 'id');
    }

    /**
     * Check if voucher is already used.
     */
    public function isUsed(): bool
    {
        return $this->is_used || $this->used_at !== null;
    }
}
