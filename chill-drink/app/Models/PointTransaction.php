<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'points',
        'type',
        'reference_type',
        'reference_id',
        'description',
        'balance_after',
    ];

    protected $casts = [
        'points' => 'integer',
        'reference_id' => 'integer',
        'balance_after' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship: Transaction belongs to User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the reference model (polymorphic-like but manual)
     */
    public function getReference()
    {
        if (!$this->reference_type || !$this->reference_id) {
            return null;
        }

        return match ($this->reference_type) {
            'order' => Order::find($this->reference_id),
            'voucher' => Voucher::find($this->reference_id),
            default => null,
        };
    }

    /**
     * Get transaction type display name
     */
    public function typeDisplayName(): string
    {
        return match ($this->type) {
            'earn' => 'Tích điểm',
            'spend' => 'Tiêu điểm',
            'expire' => 'Hết hạn',
            'adjust' => 'Điều chỉnh',
            'refund' => 'Hoàn điểm',
            default => 'Khác',
        };
    }

    /**
     * Get transaction type icon
     */
    public function typeIcon(): string
    {
        return match ($this->type) {
            'earn' => 'bi-plus-circle-fill text-success',
            'spend' => 'bi-dash-circle-fill text-danger',
            'expire' => 'bi-clock-history text-warning',
            'adjust' => 'bi-pencil-square text-info',
            'refund' => 'bi-arrow-counterclockwise text-primary',
            default => 'bi-circle text-secondary',
        };
    }

    /**
     * Get formatted points with sign
     */
    public function formattedPoints(): string
    {
        $sign = $this->points > 0 ? '+' : '';
        return $sign . number_format($this->points, 0, ',', '.');
    }

    /**
     * Create a transaction record
     */
    public static function record(
        int $userId,
        int $points,
        string $type,
        int $balanceAfter,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $description = null
    ): self {
        return self::create([
            'user_id' => $userId,
            'points' => $points,
            'type' => $type,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'description' => $description,
            'balance_after' => $balanceAfter,
        ]);
    }
}
