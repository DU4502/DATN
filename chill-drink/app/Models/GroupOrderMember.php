<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupOrderMember extends Model
{
    protected $fillable = ['group_order_id', 'user_id', 'name', 'member_token'];
    protected $hidden = ['member_token'];
    public function groupOrder() { return $this->belongsTo(GroupOrder::class); }
    public function items() { return $this->hasMany(GroupOrderItem::class); }
}
