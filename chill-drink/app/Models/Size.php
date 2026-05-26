<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Size extends Model
{
    use HasFactory;

    public $timestamps = false;

    const UPDATED_AT = null;

    protected $fillable = [
        'name',
        'multiplier',
        'created_at',
    ];

    protected $casts = [
        'multiplier' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_sizes')
            ->withPivot('price');
    }
}

