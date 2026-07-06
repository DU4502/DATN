<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'phone',
        'email',
        'address',
        'latitude',
        'longitude',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function scopeAvailableForLocation($query)
    {
        return $query
            ->where('status', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');
    }

    public function distanceTo(float $latitude, float $longitude): float
    {
        if ($this->latitude === null || $this->longitude === null) {
            return INF;
        }

        $earthRadiusKm = 6371.0088;
        $originLatitude = deg2rad($latitude);
        $originLongitude = deg2rad($longitude);
        $branchLatitude = deg2rad((float) $this->latitude);
        $branchLongitude = deg2rad((float) $this->longitude);

        $latitudeDelta = $branchLatitude - $originLatitude;
        $longitudeDelta = $branchLongitude - $originLongitude;

        $haversine = sin($latitudeDelta / 2) ** 2
            + cos($originLatitude) * cos($branchLatitude) * sin($longitudeDelta / 2) ** 2;

        $haversine = min(1, max(0, $haversine));

        return $earthRadiusKm * 2 * atan2(sqrt($haversine), sqrt(1 - $haversine));
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
