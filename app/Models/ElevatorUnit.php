<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ElevatorUnit extends Model
{
    /** Equipment categories (Rev 8 adds Travelator). */
    public const UNIT_TYPES = ['Elevator', 'Escalator', 'Travelator', 'Dumbwaiter'];

    /** Other spellings seen in import sheets → the correct type. */
    public const UNIT_TYPE_ALIASES = [
        'lift' => 'Elevator',
        'travellator' => 'Travelator',
        'travolator' => 'Travelator',
        'moving walk' => 'Travelator',
        'moving walkway' => 'Travelator',
        'dumb waiter' => 'Dumbwaiter',
    ];

    /** Map a typed / imported value to a known unit type (null if unknown). */
    public static function normalizeUnitType(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        foreach (self::UNIT_TYPES as $type) {
            if (strcasecmp($type, $value) === 0) {
                return $type;
            }
        }

        return self::UNIT_TYPE_ALIASES[strtolower($value)] ?? null;
    }

    protected $fillable = [
        'contract_id',
        'identification_no',
        'unit_number',
        'speed',
        'capacity',
        'unit_type',
        'elevator_type',
        'brand',
        'model',
        'custom_fields',
        'operational_status',
        'shutdown_since',
        'shutdown_reason',
        'shutdown_job_id',
    ];

    protected $casts = [
        'custom_fields'  => 'array',
        'shutdown_since' => 'datetime',
    ];

    public function isShutdown(): bool
    {
        return $this->operational_status === 'shutdown';
    }

    public function shutdownJob(): BelongsTo
    {
        return $this->belongsTo(JobRecord::class, 'shutdown_job_id');
    }

    /**
     * Apply the "elevator left Working / Shutdown" choice made when a breakdown or
     * repair is closed. Shutdown keeps the original shutdown time if already down.
     */
    public function applyClosureStatus(?string $status, ?string $reason, JobRecord $job): void
    {
        if ($status === 'shutdown') {
            $this->update([
                'operational_status' => 'shutdown',
                'shutdown_since'     => $this->isShutdown() && $this->shutdown_since ? $this->shutdown_since : now(),
                'shutdown_reason'    => $reason,
                'shutdown_job_id'    => $job->parent_job_id ?? $job->id,
            ]);
        } elseif ($status === 'working' && $this->isShutdown()) {
            $this->markWorking();
        }
    }

    public function markWorking(): void
    {
        $this->update([
            'operational_status' => 'working',
            'shutdown_since'     => null,
            'shutdown_reason'    => null,
            'shutdown_job_id'    => null,
        ]);
    }
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function ppmJobs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(JobRecord::class, 'elevator_unit_id')->where('job_type', 'ppm');
    }

    
}
