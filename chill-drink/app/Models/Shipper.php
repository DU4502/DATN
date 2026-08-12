<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shipper extends Model
{
    use HasFactory;

    protected $table = 'shippers';

    protected $fillable = [
        'user_id',
        'code',
        'phone',
        'vehicle_type',
        'license_plate',
        'avatar',
        'status',
        'current_latitude',
        'current_longitude',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'current_latitude' => 'float',
        'current_longitude' => 'float',
    ];

    /**
     * Shipper thuộc về User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Kiểm tra Online
     */
    public function isOnline(): bool
    {
        return $this->status === 'online';
    }

    /**
     * Kiểm tra Busy
     */
    public function isBusy(): bool
    {
        return $this->status === 'busy';
    }

    /**
     * Kiểm tra Offline
     */
    public function isOffline(): bool
    {
        return $this->status === 'offline';
    }
}
