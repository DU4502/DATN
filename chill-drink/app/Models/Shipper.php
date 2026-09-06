<?php

namespace App\Models;

use App\Support\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shipper extends Model
{
    use HasFactory;

    protected $table = 'shippers';

    protected $fillable = [
        'user_id',
        'code',
        'phone',
        'vehicle_type',
        'license_plate',
        'avatar',
        'status',
        'last_active_at',
        'station_branch_id',
        'returning_to_branch_id',
        'returning_started_at',
        'last_station_arrived_at',
        'current_latitude',
        'current_longitude',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'station_branch_id' => 'integer',
        'returning_to_branch_id' => 'integer',
        'last_active_at' => 'datetime',
        'returning_started_at' => 'datetime',
        'last_station_arrived_at' => 'datetime',
        'current_latitude' => 'float',
        'current_longitude' => 'float',
    ];

    /**
     * Shipper thuộc về User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'shipper_id');
    }

    public function hasActiveDeliveryOrders(): bool
    {
        return $this->orders()
            ->whereNotIn('status', [OrderStatus::DELIVERED, OrderStatus::COMPLETED, OrderStatus::CANCELLED])
            ->exists();
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'shipper_id');
    }

    public function bundleTrips(): HasMany
    {
        return $this->hasMany(DeliveryBundleTrip::class, 'shipper_id');
    }

    public function dispatchDecisions(): HasMany
    {
        return $this->hasMany(DeliveryDispatchDecision::class, 'shipper_id');
    }


    public function stationBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'station_branch_id');
    }

    public function returningBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'returning_to_branch_id');
    }

    public function isReturning(): bool
    {
        return ! empty($this->returning_to_branch_id);
    }

    /**
     * Home branch cố định của shipper. Nguồn chuẩn duy nhất là users.branch_id.
     * station_branch_id/returning_to_branch_id chỉ mô tả vị trí vận hành, không quyết định quyền nhận đơn.
     */
    public function homeBranchId(): ?int
    {
        $branchId = $this->user?->branch_id;

        return is_numeric($branchId) && (int) $branchId > 0 ? (int) $branchId : null;
    }

    public function effectiveStationBranchId(): ?int
    {
        return $this->homeBranchId();
    }

    /**
     * Kiểm tra Online
     */
    public function isOnline(): bool
    {
        return $this->status === 'online';
    }

    public function isRecentlyActive(): bool
    {
        if (! $this->last_active_at) {
            return false;
        }

        $ttlMinutes = max(1, (int) config('shipper_dispatch.presence.active_ttl_minutes', 3));

        return $this->last_active_at->gte(now()->subMinutes($ttlMinutes));
    }

    /**
     * Kiểm tra Busy
     */
    public function isBusy(): bool
    {
        return $this->status === 'busy';
    }

    /**
     * Kiểm tra Offline
     */
    public function isOffline(): bool
    {
        return $this->status === 'offline';
    }
}
