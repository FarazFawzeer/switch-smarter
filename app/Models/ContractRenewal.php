<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractRenewal extends Model
{
    protected $fillable = [
        'contract_id', 'previous_start_date', 'previous_end_date',
        'new_start_date', 'new_end_date', 'renewed_by',
    ];

    protected $casts = [
        'previous_start_date' => 'date',
        'previous_end_date'   => 'date',
        'new_start_date'      => 'date',
        'new_end_date'        => 'date',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function renewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'renewed_by');
    }
}