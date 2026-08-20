<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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


    public function stationBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'station_branch_id');
    }

    public function returningBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'returning_to_branch_id');
    }

    public function codReceivables()
    {
        return $this->hasMany(ShipperCodReceivable::class);
    }

    public function codSettlements()
    {
        return $this->hasMany(ShipperCodSettlement::class);
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
