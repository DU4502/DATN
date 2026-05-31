<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    use HasFactory;

    protected $table = 'coupons';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code',
        'type',
        'value',
        'max_discount',
        'description',
        'min_order',
        'usage_limit',
        'used_count',
        'starts_at',
        'expires_at',
        'status',
        'required_rank',
        'point_cost',
        'is_redeemable',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'status' => 'boolean',
        'is_redeemable' => 'boolean',
    ];

    /**
     * Check if voucher is valid
     */
    public function isValid()
    {
        return $this->status 
            && $this->used_count < $this->usage_limit
            && (! $this->starts_at || $this->starts_at->isPast())
            && (! $this->expires_at || $this->expires_at->isFuture());
    }
}
