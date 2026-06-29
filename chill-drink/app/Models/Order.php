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
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'total_price' => 'decimal:2',
        'subtotal' => 'integer',
        'shipping_fee' => 'integer',
        'discount' => 'integer',
        'total' => 'integer',
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
}
