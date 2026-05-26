<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductSize extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'product_sizes';

    protected $fillable = [
        'product_id',
        'size_id',
        'price',
    ];

    protected $casts = [
        'price' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function size()
    {
        return $this->belongsTo(Size::class);
    }
}

