<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\MaterialRequest;
use App\Models\StockTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * All stock movements go through here, inside a transaction with the item row locked,
 * so the stock balance and the movement history always agree.
 */
class StockService
{
    /** Goods received into the store (+). Optionally updates the item's unit cost. */
    public static function receive(InventoryItem $item, float $qty, ?float $unitCost, ?string $reference, ?string $note, User $by): StockTransaction
    {
        if ($qty <= 0) {
            throw new RuntimeException('Quantity must be more than zero.');
        }

        return DB::transaction(function () use ($item, $qty, $unitCost, $reference, $note, $by) {
            $locked = InventoryItem::whereKey($item->id)->lockForUpdate()->firstOrFail();
            $balance = round((float) $locked->stock_qty + $qty, 2);

            $locked->stock_qty = $balance;
            if ($unitCost !== null && $unitCost >= 0) {
                $locked->unit_cost = $unitCost;
            }
            $locked->save();

            return StockTransaction::create([
                'inventory_item_id' => $locked->id,
                'type'              => 'receive',
                'quantity'          => $qty,
                'unit_cost'         => $locked->unit_cost,
                'balance_after'     => $balance,
                'reference'         => $reference,
                'note'              => $note,
                'user_id'           => $by->id,
            ]);
        });
    }

    /** Stock count correction: sets the balance to what is physically in the store. */
    public static function adjustTo(InventoryItem $item, float $newBalance, string $note, User $by): ?StockTransaction
    {
        if ($newBalance < 0) {
            throw new RuntimeException('Stock cannot be negative.');
        }

        return DB::transaction(function () use ($item, $newBalance, $note, $by) {
            $locked = InventoryItem::whereKey($item->id)->lockForUpdate()->firstOrFail();
            $diff = round($newBalance - (float) $locked->stock_qty, 2);
            if ($diff == 0) {
                return null;
            }

            $locked->update(['stock_qty' => $newBalance]);

            return StockTransaction::create([
                'inventory_item_id' => $locked->id,
                'type'              => 'adjust',
                'quantity'          => $diff,
                'unit_cost'         => $locked->unit_cost,
                'balance_after'     => $newBalance,
                'note'              => $note,
                'user_id'           => $by->id,
            ]);
        });
    }

    /**
     * Storekeeper hands over an approved request: stock goes down, the cost is charged to
     * the request's project, and the request becomes Issued.
     */
    public static function issue(MaterialRequest $request, float $qty, ?string $remarks, User $by): MaterialRequest
    {
        return DB::transaction(function () use ($request, $qty, $remarks, $by) {
            $req = MaterialRequest::whereKey($request->id)->lockForUpdate()->firstOrFail();
            if ($req->status !== 'approved') {
                throw new RuntimeException('Only an approved request can be issued.');
            }
            if ($qty <= 0) {
                throw new RuntimeException('Quantity must be more than zero.');
            }
            if ($qty > (float) $req->quantity_requested) {
                throw new RuntimeException('You cannot issue more than was requested (' . qty($req->quantity_requested) . ').');
            }

            $item = InventoryItem::whereKey($req->inventory_item_id)->lockForUpdate()->firstOrFail();
            if ($qty > (float) $item->stock_qty) {
                throw new RuntimeException('Only ' . qty($item->stock_qty) . ' ' . $item->unit . ' in stock.');
            }

            $balance = round((float) $item->stock_qty - $qty, 2);
            $item->update(['stock_qty' => $balance]);

            $unitCost = (float) $item->unit_cost;

            StockTransaction::create([
                'inventory_item_id'   => $item->id,
                'type'                => 'issue',
                'quantity'            => -$qty,
                'unit_cost'           => $unitCost,
                'balance_after'       => $balance,
                'material_request_id' => $req->id,
                'contract_id'         => $req->contract_id,
                'reference'           => $req->mrn_no,
                'note'                => $remarks,
                'user_id'             => $by->id,
            ]);

            $req->update([
                'status'          => 'issued',
                'quantity_issued' => $qty,
                'unit_cost'       => $unitCost,
                'total_cost'      => round($qty * $unitCost, 2),
                'issued_by'       => $by->id,
                'issued_at'       => now(),
                'issue_remarks'   => $remarks,
            ]);

            return $req->fresh();
        });
    }
}
