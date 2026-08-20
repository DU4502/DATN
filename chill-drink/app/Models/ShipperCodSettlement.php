<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShipperCodSettlement extends Model
{
    protected $fillable = [
        'shipper_id',
        'branch_id',
        'amount',
        'order_count',
        'confirmed_by',
        'confirmed_at',
        'note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'order_count' => 'integer',
        'confirmed_at' => 'datetime',
    ];

    public function shipper(): BelongsTo
    {
        return $this->belongsTo(Shipper::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function receivables(): HasMany
    {
        return $this->hasMany(ShipperCodReceivable::class, 'settlement_id');
    }
}
