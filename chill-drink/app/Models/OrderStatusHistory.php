<?php

namespace App\Models;

use App\Support\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusHistory extends Model
{
    use HasFactory;

    protected $table = 'order_status_histories';

    protected $fillable = [
        'order_id',
        'from_status',
        'to_status',
        'actor_id',
        'note',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function toStatusLabel(): string
    {
        return OrderStatus::label($this->to_status);
    }

    public function toStatusBadgeClass(): string
    {
        return OrderStatus::badgeColorMap()[$this->to_status] ?? 'secondary';
    }

    public function actorRoleLabel(): string
    {
        if (! $this->actor) {
            return 'Hệ thống';
        }

        if ($this->actor->isAdmin() || $this->actor->isSuperAdmin()) {
            return 'Quản lý';
        }

        if ($this->actor->isStaffOnly()) {
            return 'Nhân viên';
        }

        if ($this->actor->isShipper()) {
            return 'Tài xế';
        }

        return 'Khách hàng';
    }

    public function actorBadgeClass(): string
    {
        if (! $this->actor) {
            return 'bg-secondary';
        }

        if ($this->actor->isAdmin() || $this->actor->isSuperAdmin()) {
            return 'bg-primary';
        }

        if ($this->actor->isStaffOnly()) {
            return 'bg-success';
        }

        if ($this->actor->isShipper()) {
            return 'bg-info text-dark';
        }

        return 'bg-light text-dark';
    }
}
