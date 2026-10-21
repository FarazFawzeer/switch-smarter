<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobReschedule extends Model
{
    protected $fillable = [
        'job_record_id',
        'old_date',
        'new_date',
        'reason',
        'rescheduled_by',
        'undone_at',
    ];

    protected $casts = [
        'old_date'  => 'date',
        'new_date'  => 'date',
        'undone_at' => 'datetime',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(JobRecord::class, 'job_record_id');
    }

    public function rescheduledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rescheduled_by');
    }
}
