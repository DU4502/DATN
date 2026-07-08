<?php

namespace App\Models;

use App\Support\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'guest_name',
        'guest_phone',
        'guest_email',
        'guest_token',
        'confirmation_token',
        'confirmation_token_expires_at',
        'delivery_type',
        'fulfillment_type',
        'scheduled_delivery_time',
        'delivery_note',
        'branch_id',
        'coupon_id',
        'subtotal',
        'shipping_fee',
        'discount',
        'total',
        'total_price',
        'payment_method',
        'payment_status',
        'vnpay_transaction_id',
        'status',
        'note',
        'scheduled_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'total_price'                    => 'decimal:2',
        'subtotal'                       => 'integer',
        'shipping_fee'                   => 'integer',
        'discount'                       => 'integer',
        'total'                          => 'integer',
        'confirmation_token_expires_at'  => 'datetime',
        'scheduled_at'                   => 'datetime',
        'scheduled_delivery_time'        => 'datetime',
    ];

    /**
     * Get the user that owns the order
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class, 'coupon_id');
    }

    /**
     * Get all order items for the order
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeColor()
    {
        return match (OrderStatus::normalize((string) $this->status)) {
            OrderStatus::PENDING => 'yellow',
            OrderStatus::IN_PROGRESS => 'blue',
            OrderStatus::SHIPPER_ACCEPTED => 'purple',
            OrderStatus::ARRIVED => 'indigo',
            OrderStatus::COMPLETED => 'green',
            OrderStatus::CANCELLED => 'red',
            default => 'gray',
        };
    }

    public function isGuest(): bool
    {
        return blank($this->user_id);
    }

    /**
     * Kiểm tra đơn hàng đang chờ xác nhận email.
     */
    public function isAwaitingEmailConfirmation(): bool
    {
        return $this->status === 'awaiting_email_confirmation';
    }

    /**
     * Kiểm tra token xác nhận email có hợp lệ không.
     */
    public function isConfirmationTokenValid(string $token): bool
    {
        if (blank($this->confirmation_token)) {
            return false;
        }

        if (! hash_equals($this->confirmation_token, $token)) {
            return false;
        }

        if ($this->confirmation_token_expires_at === null) {
            return false;
        }

        return $this->confirmation_token_expires_at->isFuture();
    }

    public function customerName(): string
    {
        if ($this->isGuest()) {
            return (string) ($this->guest_name ?? '');
        }

        return (string) ($this->user?->name ?? '');
    }

    public function customerEmail(): ?string
    {
        if ($this->isGuest()) {
            return $this->guest_email;
        }

        return $this->user?->email;
    }

    public function customerPhone(): ?string
    {
        if ($this->isGuest()) {
            return $this->guest_phone;
        }

        return $this->user?->phone;
    }

    public function pointsEarnable(): int
    {
        return max(0, (int) floor(((int) $this->total) / 1000));
    }
}
