<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'shipper_id',
        'status',
        'assigned_at',
        'picked_up_at',
        'delivered_at',
        'note',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function shipper(): BelongsTo
    {
        return $this->belongsTo(Shipper::class);
    }

    public function trackingRecords(): HasMany
    {
        return $this->hasMany(ShipmentTracking::class);
    }

    public function historyRecords(): HasMany
    {
        return $this->hasMany(ShipmentHistory::class);
    }
}
