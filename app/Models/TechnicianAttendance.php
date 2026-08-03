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
    ];

    protected $casts = [
        'work_date'      => 'date',
        'check_in_time'  => 'datetime',
        'check_out_time' => 'datetime',
    ];

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }
}