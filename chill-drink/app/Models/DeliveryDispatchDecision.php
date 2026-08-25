<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryDispatchDecision extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_uuid',
        'order_id',
        'shipper_id',
        'mode',
        'rank',
        'score',
        'selected',
        'features_json',
        'reason',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'shipper_id' => 'integer',
        'rank' => 'integer',
        'score' => 'decimal:3',
        'selected' => 'boolean',
        'features_json' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function shipper(): BelongsTo
    {
        return $this->belongsTo(Shipper::class);
    }
}
