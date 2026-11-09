<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Every stock movement: receive (+), issue (−) or manual adjustment (±), with the balance after it. */
class StockTransaction extends Model
{
    public const TYPES = ['receive' => 'Received', 'issue' => 'Issued', 'adjust' => 'Adjusted'];

    protected $fillable = [
        'inventory_item_id', 'type', 'quantity', 'unit_cost', 'balance_after',
        'material_request_id', 'contract_id', 'reference', 'note', 'user_id',
    ];

    protected $casts = [
        'quantity'      => 'decimal:2',
        'balance_after' => 'decimal:2',
        'unit_cost'     => 'decimal:2',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function materialRequest(): BelongsTo
    {
        return $this->belongsTo(MaterialRequest::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
