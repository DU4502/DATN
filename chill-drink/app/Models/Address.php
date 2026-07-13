<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $table = 'addresses';

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
        'latitude',
        'longitude',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',

    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
