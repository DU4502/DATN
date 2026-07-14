<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupOrderItem extends Model
{
    protected $fillable = ['group_order_id', 'group_order_member_id', 'product_id', 'size', 'sugar_level', 'ice_level', 'toppings', 'note', 'quantity', 'unit_price'];
    protected $casts = ['toppings' => 'array'];
    public function groupOrder() { return $this->belongsTo(GroupOrder::class); }
    public function member() { return $this->belongsTo(GroupOrderMember::class, 'group_order_member_id'); }
    public function product() { return $this->belongsTo(Product::class); }
    public function subtotal(): int { return $this->unit_price * $this->quantity; }
}
