<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class OrderIssueReport extends Model
{
    use HasFactory;

    /**
     * Admins may only move a support request forward. Keeping the current
     * status is allowed so a draft can be saved without changing its step.
     */
    public const ADMIN_STATUS_TRANSITIONS = [
        'open' => ['processing', 'rejected'],
        'processing' => ['awaiting_confirmation', 'rejected'],
        'awaiting_confirmation' => [],
        'resolved' => [],
        'rejected' => [],
    ];

    protected $fillable = ['order_id', 'user_id', 'handled_by', 'type', 'description', 'evidence_path', 'evidence_name', 'evidence_files', 'status', 'resolution_type', 'resolution_value', 'voucher_coupon_id', 'redelivery_order_id', 'redelivery_items', 'estimated_at', 'admin_note', 'received_at', 'processing_at', 'resolved_at', 'rejected_at', 'approved_at', 'remedy_started_at', 'customer_confirmed_at', 'refund_requested_at'];

    protected $casts = [
        'evidence_files' => 'array',
        'redelivery_items' => 'array',
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

    public function availableAdminStatuses(): array
    {
        return array_values(array_unique([
            $this->status,
            ...(self::ADMIN_STATUS_TRANSITIONS[$this->status] ?? []),
        ]));
    }

    public function canAdminTransitionTo(string $status): bool
    {
        return $status === $this->status
            || in_array($status, self::ADMIN_STATUS_TRANSITIONS[$this->status] ?? [], true);
    }

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

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'voucher_coupon_id');
    }

    public function redeliveryOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'redelivery_order_id');
    }
}
