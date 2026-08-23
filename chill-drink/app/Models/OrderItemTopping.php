<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItemTopping extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'order_item_toppings';

    protected $fillable = [
        'order_item_id',
        'topping_id',
        'price',
    ];

    protected $casts = [
        'price' => 'integer',
    ];

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    public function topping()
    {
        return $this->belongsTo(Topping::class, 'topping_id');
    }
}
