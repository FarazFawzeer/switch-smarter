<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Material Request (MRN): Engineer / Supervisor requests a part for a unit →
 * Manager / Admin approves (MRN number given) or rejects → Storekeeper issues it from stock.
 */
class MaterialRequest extends Model
{
    public const STATUSES = [
        'pending'   => 'Awaiting approval',
        'approved'  => 'Approved — to issue',
        'issued'    => 'Issued',
        'rejected'  => 'Rejected',
        'cancelled' => 'Cancelled',
    ];

    public const STATUS_COLORS = [
        'pending'   => '#E08A00',
        'approved'  => '#2E6BD8',
        'issued'    => '#2E9E5B',
        'rejected'  => '#D64545',
        'cancelled' => '#8792a2',
    ];

    /** who requests / approves / issues */
    public const REQUESTER_ROLES = ['Engineer', 'Supervisor'];
    public const APPROVER_ROLES = ['Super Admin', 'Admin', 'Manager'];
    public const ISSUER_ROLES = ['Storekeeper', 'Super Admin', 'Admin'];
    public const STORE_ROLES = ['Storekeeper', 'Super Admin', 'Admin', 'Manager'];

    protected $fillable = [
        'mrn_no', 'inventory_item_id', 'contract_id', 'elevator_unit_id',
        'quantity_requested', 'quantity_issued', 'unit_cost', 'total_cost',
        'photo_path', 'description', 'status',
        'requested_by', 'approved_by', 'approved_at', 'approval_remarks',
        'issued_by', 'issued_at', 'issue_remarks',
    ];

    protected $casts = [
        'quantity_requested' => 'decimal:2',
        'quantity_issued'    => 'decimal:2',
        'unit_cost'          => 'decimal:2',
        'total_cost'         => 'decimal:2',
        'approved_at'        => 'datetime',
        'issued_at'          => 'datetime',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(ElevatorUnit::class, 'elevator_unit_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(StockTransaction::class);
    }

    /** What this user may see: store/approver roles see all, requesters their own (engineers also their supervisors'). */
    public function scopeVisibleTo(Builder $q, User $user): Builder
    {
        if (in_array($user->type, self::STORE_ROLES, true)) {
            return $q;
        }
        if ($user->type === 'Engineer') {
            return $q->whereIn('requested_by', $user->supervisors()->pluck('id')->push($user->id));
        }

        return $q->where('requested_by', $user->id);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function statusColor(): string
    {
        return self::STATUS_COLORS[$this->status] ?? '#8792a2';
    }

    /** Request reference shown before approval (the MRN number is given on approval). */
    public function reference(): string
    {
        return $this->mrn_no ?: 'REQ-' . str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    public static function nextMrnNo(): string
    {
        $prefix = 'MRN-' . now()->year . '-';
        $last = static::where('mrn_no', 'like', $prefix . '%')->lockForUpdate()->orderByDesc('mrn_no')->value('mrn_no');
        $n = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;

        return $prefix . str_pad((string) $n, 5, '0', STR_PAD_LEFT);
    }
}
