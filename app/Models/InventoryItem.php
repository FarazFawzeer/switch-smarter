<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Store item master (spare parts / materials) kept by the Storekeeper. */
class InventoryItem extends Model
{
    public const CATEGORIES = ['Electrical', 'Mechanical', 'Door Parts', 'Control / PCB', 'Safety', 'Ropes & Cables', 'Lubricants & Consumables', 'Tools', 'Other'];
    public const UNITS = ['Nos', 'Set', 'Pair', 'Box', 'Meter', 'Roll', 'Litre', 'Kg'];

    protected $fillable = [
        'item_code', 'part_name', 'category', 'brand', 'part_number', 'unit', 'unit_cost',
        'stock_qty', 'min_stock', 'store_location', 'remarks', 'is_active', 'created_by',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'stock_qty' => 'decimal:2',
        'min_stock' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(StockTransaction::class)->latest('id');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(MaterialRequest::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    /** At or below the minimum stock level (min 0 = no alert). */
    public function scopeLowStock(Builder $q): Builder
    {
        return $q->where('is_active', true)->where('min_stock', '>', 0)->whereColumn('stock_qty', '<=', 'min_stock');
    }

    public function isLow(): bool
    {
        return $this->min_stock > 0 && $this->stock_qty <= $this->min_stock;
    }

    public function stockValue(): float
    {
        return round($this->stock_qty * (float) $this->unit_cost, 2);
    }

    public static function nextCode(): string
    {
        $last = static::where('item_code', 'like', 'ITM-%')->orderByDesc('id')->value('item_code');
        $n = $last && preg_match('/(\d+)$/', $last, $m) ? (int) $m[1] + 1 : 1;
        do {
            $code = 'ITM-' . str_pad((string) $n++, 5, '0', STR_PAD_LEFT);
        } while (static::where('item_code', $code)->exists());

        return $code;
    }
}
