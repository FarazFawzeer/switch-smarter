<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimesheetAdjustment extends Model
{
    protected $fillable = [
        'job_record_id',
        'old_check_in',
        'old_check_out',
        'new_check_in',
        'new_check_out',
        'reason',
        'adjusted_by',
    ];

    protected $casts = [
        'old_check_in'  => 'datetime',
        'old_check_out' => 'datetime',
        'new_check_in'  => 'datetime',
        'new_check_out' => 'datetime',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(JobRecord::class, 'job_record_id');
    }

    public function adjustedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }
}
