<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipperCodReceivable extends Model
{
    protected $fillable = [
        'order_id',
        'order_code',
        'shipper_id',
        'order_branch_id',
        'amount',
        'collected_at',
        'settlement_id',
        'settled_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'collected_at' => 'datetime',
        'settled_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function shipper(): BelongsTo
    {
        return $this->belongsTo(Shipper::class);
    }

    public function orderBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'order_branch_id');
    }

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(ShipperCodSettlement::class, 'settlement_id');
    }
}
