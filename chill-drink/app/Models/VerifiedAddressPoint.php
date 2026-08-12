<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VerifiedAddressPoint extends Model
{
    protected $fillable = [
        'full_address',
        'house_number',
        'road_name',
        'ward',
        'district',
        'province',
        'latitude',
        'longitude',
        'normalized_key',
        'observation_count',
        'successful_delivery_count',
        'verification_level',
        'confidence',
        'last_observed_at',
        'verified_at',
        'metadata',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'observation_count' => 'integer',
        'successful_delivery_count' => 'integer',
        'confidence' => 'float',
        'last_observed_at' => 'datetime',
        'verified_at' => 'datetime',
        'metadata' => 'array',
    ];
}
