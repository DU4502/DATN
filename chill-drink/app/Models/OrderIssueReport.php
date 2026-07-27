<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderIssueReport extends Model
{
    use HasFactory;

    protected $fillable = ['order_id', 'user_id', 'handled_by', 'type', 'description', 'evidence_path', 'evidence_name', 'status', 'resolution_type', 'resolution_value', 'voucher_coupon_id', 'estimated_at', 'admin_note', 'received_at', 'processing_at', 'resolved_at', 'rejected_at', 'approved_at', 'remedy_started_at', 'customer_confirmed_at', 'refund_requested_at'];

    protected $casts = [
        'received_at' => 'datetime',
        'processing_at' => 'datetime',
        'resolved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'estimated_at' => 'datetime',
        'approved_at' => 'datetime',
        'remedy_started_at' => 'datetime',
        'customer_confirmed_at' => 'datetime',
        'refund_requested_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
