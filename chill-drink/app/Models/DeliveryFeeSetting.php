<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryFeeSetting extends Model
{
    protected $fillable = [
        'free_distance_km',
        'fast_surcharge',
        'cup_tiers',
        'updated_by',
    ];

    protected $casts = [
        'free_distance_km' => 'float',
        'fast_surcharge' => 'integer',
        'cup_tiers' => 'array',
        'updated_by' => 'integer',
    ];
}
