<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'label',
        'receiver_name',
        'phone',
        'province',
        'district',
        'ward',
        'detail',
        'is_default',
        'created_at',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
