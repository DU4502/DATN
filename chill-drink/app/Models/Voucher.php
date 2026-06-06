<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    use HasFactory;

    public const TYPE_PERCENT = 'percent';
    public const TYPE_FIXED = 'fixed';

    public const RANK_LABELS = [
        'bronze' => 'Đồng',
        'silver' => 'Bạc',
        'gold' => 'Vàng',
        'diamond' => 'Kim cương',
    ];

    protected $table = 'coupons';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code',
        'type',
        'value',
        'max_discount',
        'description',
        'min_order',
        'usage_limit',
        'used_count',
        'starts_at',
        'expires_at',
        'status',
        'required_rank',
        'point_cost',
        'is_redeemable',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'status' => 'boolean',
        'is_redeemable' => 'boolean',
        'max_discount' => 'integer',
    ];

    public function isValid(?int $subtotal = null): bool
    {
        return $this->isActiveNow()
            && $this->hasRemainingUses()
            && ($subtotal === null || $this->meetsMinimumOrder($subtotal));
    }

    public function isActiveNow(): bool
    {
        return (bool) $this->status
            && (! $this->starts_at || $this->starts_at->lte(now()))
            && (! $this->expires_at || $this->expires_at->gte(now()));
    }

    public function hasRemainingUses(): bool
    {
        $limit = (int) ($this->usage_limit ?? 0);

        return $limit <= 0 || (int) $this->used_count < $limit;
    }

    public function meetsMinimumOrder(int $subtotal): bool
    {
        return $subtotal >= (int) ($this->min_order ?? 0);
    }

    public function discountFor(int $subtotal): int
    {
        if ($subtotal <= 0 || ! $this->isValid($subtotal)) {
            return 0;
        }

        if ($this->type === self::TYPE_PERCENT) {
            $discount = (int) floor($subtotal * ((int) $this->value / 100));
            $maxDiscount = (int) ($this->max_discount ?? 0);

            return max(0, min($discount, $maxDiscount > 0 ? $maxDiscount : $subtotal));
        }

        return max(0, min((int) $this->value, $subtotal));
    }

    public function formattedValue(): string
    {
        if ($this->type === self::TYPE_PERCENT) {
            return (int) $this->value . '%';
        }

        return number_format((int) $this->value, 0, ',', '.') . 'đ';
    }

    public function usageText(): string
    {
        $limit = (int) ($this->usage_limit ?? 0);

        return number_format((int) $this->used_count, 0, ',', '.')
            . '/'
            . ($limit > 0 ? number_format($limit, 0, ',', '.') : '∞');
    }

    public function rankLabel(): string
    {
        return self::RANK_LABELS[$this->required_rank] ?? 'Tất cả';
    }
}
