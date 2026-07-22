<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Contract extends Model
{
   protected $fillable = [
    'project_name', 'project_type', 'location', 'contract_number', 'number_of_elevators',
    'contract_start_date', 'contract_end_date', 'ppm_start_date', 'is_scheduled',
    'route_id', 'assigned_engineer_id', 'assigned_supervisor_id', 'assigned_technician_id',
    'status', 'contract_document', 'custom_fields', 'created_by',
];


protected $casts = [
    'contract_start_date' => 'date',
    'contract_end_date'   => 'date',
    'ppm_start_date'      => 'date',
    'is_scheduled'        => 'boolean',
    'custom_fields'       => 'array',
];

public function renewals(): \Illuminate\Database\Eloquent\Relations\HasMany
{
    return $this->hasMany(ContractRenewal::class)->latest();
}

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function engineer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_engineer_id');
    }

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }

    public function elevatorUnits(): HasMany
    {
        return $this->hasMany(ElevatorUnit::class);
    }

    public function ppmJobs(): HasMany
    {
        return $this->hasMany(JobRecord::class)->where('job_type', 'ppm')->orderBy('scheduled_date');
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->contract_end_date->isFuture();
    }

    public function progressPercent(): int
    {
        $start = $this->contract_start_date;
        $end = $this->contract_end_date;
        $today = Carbon::today();

        if ($today->lte($start)) return 0;
        if ($today->gte($end)) return 100;

        $totalDays = $start->diffInDays($end);
        $elapsedDays = $start->diffInDays($today);

        return $totalDays > 0 ? (int) round(($elapsedDays / $totalDays) * 100) : 100;
    }

    public function daysRemaining(): int
    {
        return max(0, Carbon::today()->diffInDays($this->contract_end_date, false));
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match ($user->type) {
            'Engineer'   => $query->where('assigned_engineer_id', $user->id),
            'Supervisor' => $query->where('assigned_supervisor_id', $user->id),
            default      => $query,
        };
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_supervisor_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_technician_id');
    }

    public function route(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Route::class);
    }
}
