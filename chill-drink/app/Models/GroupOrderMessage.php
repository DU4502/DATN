<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupOrderMessage extends Model
{
    protected $fillable = ['group_order_id', 'sender_member_id', 'recipient_member_id', 'content', 'attachment_path', 'attachment_name', 'attachment_mime', 'attachment_size'];
    protected $casts = ['read_at' => 'datetime'];

    public function groupOrder() { return $this->belongsTo(GroupOrder::class); }
    public function sender() { return $this->belongsTo(GroupOrderMember::class, 'sender_member_id'); }
    public function recipient() { return $this->belongsTo(GroupOrderMember::class, 'recipient_member_id'); }
}
