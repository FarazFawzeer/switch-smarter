<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class JobRecord extends Model
{
    protected $table = 'jobs_records';

    protected $fillable = [
        'site_id',
        'contract_id',
        'elevator_unit_id',
        'job_type',
        'job_code',
        'schedule_batch',
        'scheduled_date',
        'assigned_technician_id',
        'assigned_by',
        'reported_by',
        'status',
        'priority',
        'description',
        'breakdown_cause',
        'immediate_action_taken',
        'warranty_contract',
        'customer_name',
        'completed_at',
        'check_in_time',
        'check_in_lat',
        'check_in_lng',
        'check_out_time',
        'check_out_lat',
        'check_out_lng',
        'completion_photo',
        'signature_path',
        'work_notes',
        'parts_used',
        'cost',
        'repair_job_no',
        'quotation_date',
        'po_status',
        'po_received_date',
        'po_number',
        'quotation_value',
        'scope_of_work',
        'quotation_attachment',
        'material_status',
        'site_status',
        'start_photo',
        
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'completed_at'   => 'datetime',
        'check_in_time'  => 'datetime',
        'check_out_time' => 'datetime',
        'parts_used'     => 'array',
        'quotation_date'   => 'date',
        'po_received_date' => 'date',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(ElevatorUnit::class, 'elevator_unit_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_technician_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function reschedules(): HasMany
    {
        return $this->hasMany(JobReschedule::class, 'job_record_id')->latest('id');
    }

    /**
     * Excludes PPM visits whose elevator unit no longer exists (unit_id was nulled
     * when the unit was deleted). They belong to no unit and only inflate counts.
     */
    public function scopeWithoutOrphanPpm(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('job_type', '!=', 'ppm')->orWhereNotNull('elevator_unit_id');
        });
    }

    public function hasStarted(): bool
    {
        return $this->check_in_time !== null || in_array($this->status, ['in_progress', 'on_hold', 'completed'], true);
    }

    /**
     * Splits this job's worked time into normal and overtime hours, using the
     * technician's shift end as the cut-off. Returns null while the job is still open.
     *
     * @return array{total: float, normal: float, ot: float}|null
     */
    public function workedHours(?Shift $shift): ?array
    {
        if (! $this->check_in_time || ! $this->check_out_time) {
            return null;
        }

        $start = $this->check_in_time;
        $end = $this->check_out_time;
        $totalMinutes = max(0, $start->diffInMinutes($end));

        $otMinutes = 0;
        if ($shift) {
            $shiftEnd = Carbon::parse($start->toDateString() . ' ' . $shift->end_time);
            if ($shift->end_time < $shift->start_time) {
                $shiftEnd->addDay();
            }
            if ($end->gt($shiftEnd)) {
                $otMinutes = $shiftEnd->gt($start) ? $shiftEnd->diffInMinutes($end) : $totalMinutes;
            }
        }

        return [
            'total'  => round($totalMinutes / 60, 2),
            'normal' => round(($totalMinutes - $otMinutes) / 60, 2),
            'ot'     => round($otMinutes / 60, 2),
        ];
    }

    public static function generateJobCode(string $type): string
    {
        $prefixMap = ['ppm' => 'PPM', 'repair' => 'RP', 'breakdown' => 'BD', 'casual_job' => 'CJ'];
        $prefix = $prefixMap[$type] ?? 'JB';
        $year = now()->year;

        $countThisYear = static::where('job_type', $type)->whereYear('created_at', $year)->count() + 1;

        return sprintf('%s-%d-%05d', $prefix, $year, $countThisYear);
    }
}
