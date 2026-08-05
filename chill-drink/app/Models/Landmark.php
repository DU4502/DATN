<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Landmark extends Model
{
    protected $fillable = [
        'name',
        'aliases',
        'latitude',
        'longitude',
        'address_text',
        'type',
        'source_type',
        'verification_level',
        'successful_delivery_count',
        'status',
        'verified_at',
    ];

    protected $casts = [
        'aliases' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
        'successful_delivery_count' => 'integer',
        'verified_at' => 'datetime',
    ];
}
