<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyPoint extends Model
{
    protected $table = 'loyalty_points';

    protected $fillable = [
        'user_id',
        'total_points',
        'monthly_points',
        'lifetime_points',
        'current_month',
    ];

    protected $casts = [
        'total_points' => 'integer',
        'monthly_points' => 'integer',
        'lifetime_points' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Points earning rate: 1 point per 10,000 VND spent
    const POINTS_PER_VND = 10000;

    /**
     * Relationship: LoyaltyPoint belongs to User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Ensure monthly points reflect the current month
     */
    public function checkAndResetMonthly(): self
    {
        $nowMonth = now()->format('Y-m');
        if ($this->current_month !== $nowMonth) {
            $this->monthly_points = 0;
            $this->current_month = $nowMonth;
            $this->save();
        }

        return $this;
    }

    /**
     * Add points to user account
     */
    public function addPoints(int $points, string $type = 'earn', ?string $description = null, ?string $referenceType = null, ?int $referenceId = null): void
    {
        $this->checkAndResetMonthly();

        $this->total_points += $points;
        $this->monthly_points += $points;
        $this->lifetime_points += $points;
        
        $this->save();
        
        // Record transaction
        PointTransaction::record(
            userId: $this->user_id,
            points: $points,
            type: $type,
            balanceAfter: $this->total_points,
            referenceType: $referenceType,
            referenceId: $referenceId,
            description: $description
        );
    }

    /**
     * Deduct points from user account
     */
    public function deductPoints(int $points, string $type = 'spend', ?string $description = null, ?string $referenceType = null, ?int $referenceId = null): bool
    {
        if ($this->total_points < $points) {
            return false;
        }

        $this->total_points -= $points;
        $this->save();
        
        // Record transaction (negative points)
        PointTransaction::record(
            userId: $this->user_id,
            points: -$points,
            type: $type,
            balanceAfter: $this->total_points,
            referenceType: $referenceType,
            referenceId: $referenceId,
            description: $description
        );

        return true;
    }

    /**
     * Reset monthly points (to be called at start of each month)
     */
    public function resetMonthlyPoints(): void
    {
        $this->monthly_points = 0;
        $this->current_month = now()->format('Y-m');
        $this->save();
    }

    /**
     * Calculate points earned from order amount
     */
    public static function calculatePointsFromAmount(int $amount): int
    {
        return (int) floor($amount / self::POINTS_PER_VND);
    }

    /**
     * Get or create loyalty points record for user
     */
    public static function getOrCreateForUser(int $userId): self
    {
        $record = self::firstOrCreate(
            ['user_id' => $userId],
            [
                'total_points' => 0,
                'monthly_points' => 0,
                'lifetime_points' => 0,
                'current_month' => now()->format('Y-m'),
            ]
        );

        return $record->checkAndResetMonthly();
    }
}
