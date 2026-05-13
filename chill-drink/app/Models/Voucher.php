<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code',
        'discount_percent',
        'quantity',
        'expired_date',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'expired_date' => 'date',
        'status' => 'boolean',
    ];

    /**
     * Check if voucher is valid
     */
    public function isValid()
    {
        return $this->status 
            && $this->quantity > 0 
            && $this->expired_date->isFuture();
    }
}
