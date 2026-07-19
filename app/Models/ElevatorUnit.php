<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ElevatorUnit extends Model
{
    protected $fillable = [
        'contract_id',
        'identification_no',
        'speed',
        'capacity',
        'unit_type',
        'elevator_type',
        'brand',
        'model',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }
}