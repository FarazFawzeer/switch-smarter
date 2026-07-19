<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Site extends Model
{
    protected $fillable = [
        'contract_id',
        'site_name',
        'address',
        'latitude',
        'longitude',
        'radius_meters',
        'elevator_count',
    ];

    protected $casts = [
        'latitude'  => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Haversine distance check — used later by the technician check-in flow.
     */
    public function isWithinRadius(float $lat, float $lng): bool
    {
        $earthRadius = 6371000; // meters

        $dLat = deg2rad($lat - (float) $this->latitude);
        $dLng = deg2rad($lng - (float) $this->longitude);

        $a = sin($dLat / 2) ** 2 +
             cos(deg2rad((float) $this->latitude)) * cos(deg2rad($lat)) * sin($dLng / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return $distance <= $this->radius_meters;
    }
}