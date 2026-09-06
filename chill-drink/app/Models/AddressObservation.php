<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AddressObservation extends Model
{
    protected $fillable = [
        'user_id',
        'order_id',
        'address_id',
        'source_type',
        'full_address',
        'house_number',
        'road_name',
        'ward',
        'district',
        'province',
        'latitude',
        'longitude',
        'normalized_key',
        'status',
        'confidence',
        'delivered_at',
        'metadata',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'confidence' => 'float',
        'delivered_at' => 'datetime',
        'metadata' => 'array',
    ];
}
