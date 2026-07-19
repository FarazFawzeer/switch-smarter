<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobRecord extends Model
{
    protected $table = 'jobs_records';

    protected $fillable = [
        'site_id',
        'contract_id',
        'job_type',
        'scheduled_date',
        'assigned_technician_id',
        'assigned_by',
        'reported_by',
        'status',
        'priority',
        'description',
        'completed_at',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'completed_at'   => 'datetime',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
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

    public function isOverdue(): bool
    {
        return $this->status === 'pending'
            && $this->scheduled_date
            && $this->scheduled_date->isPast();
    }
}