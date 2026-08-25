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
        'order_code',
        'user_id',
        'guest_name',
        'guest_phone',
        'guest_email',
        'guest_token',
        'contact_phone',
        'confirmation_token',
        'confirmation_token_expires_at',
        'delivery_type',
        'fulfillment_type',
        'scheduled_delivery_time',
        'delivery_note',
        'shipping_address_text',
        'shipping_latitude',
        'shipping_longitude',
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
        'cancellation_reason',
        'delivered_at',
        'shipper_id',
        'note',
        'scheduled_at',
        'status_changed_at',
        'status_changed_by',
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
        'delivered_at'                   => 'datetime',
        'status_changed_at'              => 'datetime',
        'shipping_latitude'              => 'float',
        'shipping_longitude'             => 'float',
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

    /**
     * Tài xế hiện đang được gán cho đơn. Giữ quan hệ ở Order để Admin/Super Admin
     * có thể hiển thị thông tin giao hàng mà không query tay trong Blade.
     */
    public function shipper()
    {
        return $this->belongsTo(Shipper::class, 'shipper_id');
    }

    public function shipments()
    {
        return $this->hasMany(Shipment::class, 'order_id');
    }

    public function bundleTripOrder()
    {
        return $this->hasOne(DeliveryBundleTripOrder::class, 'order_id');
    }

    public function dispatchDecisions()
    {
        return $this->hasMany(DeliveryDispatchDecision::class, 'order_id');
    }

    public function deliveryMessages()
    {
        return $this->hasMany(DeliveryOrderMessage::class, 'order_id');
    }

    public function issueReports()
    {
        return $this->hasMany(OrderIssueReport::class, 'order_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'order_id');
    }

    public function codReceivable()
    {
        return $this->hasOne(ShipperCodReceivable::class);
    }

    public function statusChangedBy()
    {
        return $this->belongsTo(User::class, 'status_changed_by');
    }

    public function address()
    {
        return $this->belongsTo(Address::class);
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
            OrderStatus::PENDING => 'warning',
            OrderStatus::CONFIRMED => 'info',
            OrderStatus::PREPARING => 'primary',
            OrderStatus::READY_FOR_DELIVERY, OrderStatus::READY_FOR_PICKUP => 'cyan',
            OrderStatus::SHIPPER_PICKED_UP => 'indigo',
            OrderStatus::DELIVERING => 'purple',
            OrderStatus::DELIVERED => 'teal',
            OrderStatus::COMPLETED => 'success',
            OrderStatus::CANCELLED => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Trả về mã hiển thị của đơn hàng.
     * Ưu tiên order_code nếu có, fallback về #id cho đơn cũ.
     */
    public function displayCode(): string
    {
        return $this->order_code ?? ('#' . $this->id);
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

        return $this->contact_phone ?: $this->user?->phone;
    }

    public function getShippingAddress(): string
    {
        if ($this->address) {
            return trim(implode(', ', array_filter([
                $this->address->detail,
                $this->address->ward,
                $this->address->district,
                $this->address->province
            ])));
        }

        if ($this->note && preg_match('/địa chỉ:\s*([^\r\n]*)/iu', $this->note, $matches)) {
            return trim($matches[1]);
        }

        if ($this->user && $this->user->addresses()->exists()) {
            $addr = $this->user->addresses()->where('is_default', true)->first() ?? $this->user->addresses()->first();
            return trim(implode(', ', array_filter([
                $addr->detail,
                $addr->ward,
                $addr->district,
                $addr->province
            ])));
        }

        return 'Chưa cập nhật địa chỉ';
    }

    protected static function booted(): void
    {
        static::updating(function (Order $order) {
            if ($order->isDirty('status') && OrderStatus::normalize($order->status) === OrderStatus::DELIVERED && is_null($order->delivered_at)) {
                $order->delivered_at = now();
            }
        });
    }

    public function pointsEarnable(): int
    {
        // 1 point per 10,000 VND spent
        return LoyaltyPoint::calculatePointsFromAmount((int) $this->total);
    }

    /**
     * Award loyalty points to user when order is completed
     */
    public function awardLoyaltyPoints(): void
    {
        // Only award points to registered users
        if (!$this->user_id) {
            return;
        }

        $alreadyAwarded = PointTransaction::where('reference_type', 'order')
            ->where('reference_id', $this->id)
            ->where('type', 'earn')
            ->exists();

        if ($alreadyAwarded) {
            return;
        }

        $points = $this->pointsEarnable();
        
        if ($points <= 0) {
            return;
        }

        // Get or create loyalty point record
        $loyaltyPoint = LoyaltyPoint::getOrCreateForUser($this->user_id);
        
        // Add points with transaction record
        $loyaltyPoint->addPoints(
            points: $points,
            type: 'earn',
            description: "Hoàn thành đơn hàng {$this->displayCode()}",
            referenceType: 'order',
            referenceId: $this->id
        );
    }

    /**
     * Revoke loyalty points when order is cancelled
     */
    public function revokeLoyaltyPoints(): void
    {
        if (!$this->user_id) {
            return;
        }

        $awardTransaction = PointTransaction::where('reference_type', 'order')
            ->where('reference_id', $this->id)
            ->where('type', 'earn')
            ->first();

        if (!$awardTransaction) {
            return;
        }

        $points = $awardTransaction->points;
        $loyaltyPoint = LoyaltyPoint::getOrCreateForUser($this->user_id);
        $loyaltyPoint->deductPoints(
            points: $points,
            type: 'spend',
            description: "Thu hồi điểm thưởng đơn hàng bị hủy {$this->displayCode()}",
            referenceType: 'order',
            referenceId: $this->id
        );
    }
}
