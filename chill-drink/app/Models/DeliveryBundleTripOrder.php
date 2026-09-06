<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryBundleTripOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'order_id',
        'role',
    ];

    protected $casts = [
        'trip_id' => 'integer',
        'order_id' => 'integer',
    ];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(DeliveryBundleTrip::class, 'trip_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
