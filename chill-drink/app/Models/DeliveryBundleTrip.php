<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryBundleTrip extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipper_id',
        'status',
        'total_cups',
        'estimated_distance_m',
        'estimated_duration_s',
        'saved_distance_m',
        'plan_json',
    ];

    protected $casts = [
        'shipper_id' => 'integer',
        'total_cups' => 'integer',
        'estimated_distance_m' => 'integer',
        'estimated_duration_s' => 'integer',
        'saved_distance_m' => 'integer',
        'plan_json' => 'array',
    ];

    public function shipper(): BelongsTo
    {
        return $this->belongsTo(Shipper::class);
    }

    public function tripOrders(): HasMany
    {
        return $this->hasMany(DeliveryBundleTripOrder::class, 'trip_id');
    }

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(
            Order::class,
            'delivery_bundle_trip_orders',
            'trip_id',
            'order_id'
        )->withPivot('role')->withTimestamps();
    }
}
