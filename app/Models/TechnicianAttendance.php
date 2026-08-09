<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TechnicianAttendance extends Model
{
    protected $table = 'technician_attendance';

    protected $fillable = [
        'technician_id', 'work_date', 'shift_id',
        'check_in_time', 'check_out_time', 'total_hours', 'ot_hours',
        'approval_status',
        'supervisor_approved_by', 'supervisor_approved_at',
        'engineer_approved_by', 'engineer_approved_at',
        'manager_approved_by', 'manager_approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'work_date'              => 'date',
        'check_in_time'          => 'datetime',
        'check_out_time'         => 'datetime',
        'supervisor_approved_at' => 'datetime',
        'engineer_approved_at'   => 'datetime',
        'manager_approved_at'    => 'datetime',
    ];

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function supervisorApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_approved_by');
    }

    public function engineerApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'engineer_approved_by');
    }

    public function managerApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_approved_by');
    }
}