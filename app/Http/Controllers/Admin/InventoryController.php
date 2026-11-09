<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\InventoryItem;
use App\Models\MaterialRequest;
use App\Models\StockTransaction;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use RuntimeException;

/** Store item master, stock receive / adjust, stock history and store analytics. */
class InventoryController extends Controller
{
    /** may change items and stock */
    public const MANAGE_ROLES = ['Storekeeper', 'Super Admin', 'Admin'];

    private function canView(): bool
    {
        return in_array(Auth::user()->type, MaterialRequest::STORE_ROLES, true);
    }

    private function canManage(): bool
    {
        return in_array(Auth::user()->type, self::MANAGE_ROLES, true);
    }

    public function index(Request $request)
    {
        abort_unless($this->canView(), 403);

        $query = InventoryItem::query();

        foreach (['item_code', 'part_name', 'brand', 'part_number', 'store_location'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, 'like', '%' . $request->input($field) . '%');
            }
        }
        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }
        if ($request->input('stock') === 'low') {
            $query->lowStock();
        } elseif ($request->input('stock') === 'out') {
            $query->where('stock_qty', '<=', 0);
        } elseif ($request->input('stock') === 'ok') {
            $query->where(fn ($q) => $q->where('min_stock', '<=', 0)->orWhereColumn('stock_qty', '>', 'min_stock'))->where('stock_qty', '>', 0);
        }
        if ($request->input('active') === '0') {
            $query->where('is_active', false);
        } elseif ($request->input('active') !== 'all') {
            $query->where('is_active', true);
        }

        $sortable = ['item_code', 'part_name', 'stock_qty', 'unit_cost', 'category'];
        $sort = in_array($request->get('sort'), $sortable, true) ? $request->get('sort') : 'part_name';
        $dir = $request->get('dir') === 'desc' ? 'desc' : 'asc';
        $items = $query->orderBy($sort, $dir)->paginate(30)->withQueryString();

        $summary = [
            'items'       => InventoryItem::active()->count(),
            'value'       => (float) InventoryItem::active()->sum(DB::raw('stock_qty * unit_cost')),
            'low'         => InventoryItem::lowStock()->count(),
            'out'         => InventoryItem::active()->where('stock_qty', '<=', 0)->count(),
            'to_issue'    => MaterialRequest::where('status', 'approved')->count(),
        ];

        $canManage = $this->canManage();

        return view('admin.stores.inventory.index', compact('items', 'summary', 'canManage'));
    }

    public function create()
    {
        abort_unless($this->canManage(), 403);

        return view('admin.stores.inventory.form', ['item' => new InventoryItem(['unit' => 'Nos', 'is_active' => true]), 'nextCode' => InventoryItem::nextCode()]);
    }

    public function store(Request $request)
    {
        abort_unless($this->canManage(), 403);

        $data = $this->validated($request);
        $opening = (float) ($data['opening_stock'] ?? 0);
        unset($data['opening_stock']);

        $data['item_code'] = ($data['item_code'] ?? null) ?: InventoryItem::nextCode();
        $data['stock_qty'] = 0;
        $data['created_by'] = Auth::id();

        $item = DB::transaction(function () use ($data, $opening) {
            $item = InventoryItem::create($data);
            if ($opening > 0) {
                StockService::receive($item, $opening, null, 'Opening stock', null, Auth::user());
            }

            return $item;
        });

        return redirect()->route('admin.stores.inventory.show', $item)->with('success', "Item {$item->item_code} added.");
    }

    public function show(InventoryItem $item)
    {
        abort_unless($this->canView(), 403);

        $transactions = $item->transactions()->with(['user:id,name', 'materialRequest:id,mrn_no', 'contract:id,project_name'])->paginate(25);
        $requests = $item->requests()->with(['contract:id,project_name', 'requester:id,name'])->latest('id')->take(10)->get();
        $issuedTotal = -1 * (float) $item->transactions()->where('type', 'issue')->sum('quantity');
        $canManage = $this->canManage();

        return view('admin.stores.inventory.show', compact('item', 'transactions', 'requests', 'issuedTotal', 'canManage'));
    }

    public function edit(InventoryItem $item)
    {
        abort_unless($this->canManage(), 403);

        return view('admin.stores.inventory.form', ['item' => $item, 'nextCode' => null]);
    }

    public function update(Request $request, InventoryItem $item)
    {
        abort_unless($this->canManage(), 403);

        $data = $this->validated($request, $item);
        unset($data['opening_stock']);
        $data['item_code'] = ($data['item_code'] ?? null) ?: $item->item_code;
        $item->update($data);

        return redirect()->route('admin.stores.inventory.show', $item)->with('success', 'Item updated.');
    }

    /** Delete only an unused item; one with history is deactivated instead. */
    public function destroy(InventoryItem $item)
    {
        abort_unless($this->canManage(), 403);

        if ($item->transactions()->exists() || $item->requests()->exists()) {
            $item->update(['is_active' => false]);

            return response()->json(['success' => true, 'message' => "{$item->part_name} has stock history, so it was deactivated (hidden from new requests) instead of deleted."]);
        }

        $item->delete();

        return response()->json(['success' => true, 'message' => 'Item deleted.']);
    }

    public function receive(Request $request, InventoryItem $item)
    {
        abort_unless($this->canManage(), 403);

        $data = $request->validate([
            'quantity'  => ['required', 'numeric', 'gt:0', 'max:9999999'],
            'unit_cost' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'reference' => ['nullable', 'string', 'max:100'],
            'note'      => ['nullable', 'string', 'max:500'],
        ], ['quantity.gt' => 'Quantity must be more than zero.']);

        $tx = StockService::receive($item, (float) $data['quantity'], isset($data['unit_cost']) ? (float) $data['unit_cost'] : null,
            $data['reference'] ?? null, $data['note'] ?? null, Auth::user());

        return response()->json(['success' => true, 'message' => 'Received ' . qty($data['quantity']) . ' ' . $item->unit . '. New stock: ' . qty($tx->balance_after) . '.']);
    }

    public function adjust(Request $request, InventoryItem $item)
    {
        abort_unless($this->canManage(), 403);

        $data = $request->validate([
            'new_balance' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'note'        => ['required', 'string', 'max:500'],
        ], ['note.required' => 'Please give the reason for the stock correction (e.g. "stock count 09 Nov").']);

        try {
            $tx = StockService::adjustTo($item, (float) $data['new_balance'], $data['note'], Auth::user());
        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'message' => $tx
            ? 'Stock corrected by ' . ($tx->quantity > 0 ? '+' : '') . qty($tx->quantity) . '. New stock: ' . qty($tx->balance_after) . '.'
            : 'Stock already matches — nothing changed.']);
    }

    /** AJAX part search for the request form: shows what is available. */
    public function search(Request $request)
    {
        $term = trim((string) $request->input('q'));

        $items = InventoryItem::active()
            ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w->where('part_name', 'like', "%{$term}%")
                ->orWhere('item_code', 'like', "%{$term}%")->orWhere('part_number', 'like', "%{$term}%")->orWhere('brand', 'like', "%{$term}%")))
            ->orderBy('part_name')->limit(15)
            ->get(['id', 'item_code', 'part_name', 'brand', 'part_number', 'unit', 'stock_qty', 'min_stock', 'category']);

        return response()->json(['items' => $items->map(fn ($i) => [
            'id'    => $i->id,
            'code'  => $i->item_code,
            'name'  => $i->part_name,
            'brand' => $i->brand,
            'part_number' => $i->part_number,
            'unit'  => $i->unit,
            'stock' => (float) $i->stock_qty,
            'stock_label' => qty($i->stock_qty) . ' ' . $i->unit,
            'low'   => $i->isLow(),
        ])]);
    }

    /** Store analytics: stock value, low stock, cost per project, consumption trend. */
    public function analytics(Request $request)
    {
        abort_unless($this->canView(), 403);

        $months = (int) $request->input('months', 6);
        $months = in_array($months, [3, 6, 12], true) ? $months : 6;
        $from = now()->startOfMonth()->subMonthsNoOverflow($months - 1);

        $issued = StockTransaction::where('type', 'issue')->where('created_at', '>=', $from);

        $byMonth = (clone $issued)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, SUM(-quantity * COALESCE(unit_cost, 0)) as cost, SUM(-quantity) as qty")
            ->groupBy('ym')->pluck('cost', 'ym');
        $trend = collect(range(0, $months - 1))->map(function ($i) use ($from, $byMonth) {
            $m = $from->copy()->addMonthsNoOverflow($i);

            return ['label' => $m->format('M Y'), 'cost' => round((float) ($byMonth[$m->format('Y-m')] ?? 0), 2)];
        });

        $byProject = (clone $issued)->whereNotNull('contract_id')
            ->selectRaw('contract_id, SUM(-quantity * COALESCE(unit_cost, 0)) as cost, COUNT(DISTINCT material_request_id) as requests')
            ->groupBy('contract_id')->orderByDesc('cost')->limit(10)->get();
        $projectNames = Contract::whereIn('id', $byProject->pluck('contract_id'))->pluck('project_name', 'id');

        $topItems = (clone $issued)
            ->selectRaw('inventory_item_id, SUM(-quantity) as qty, SUM(-quantity * COALESCE(unit_cost, 0)) as cost')
            ->groupBy('inventory_item_id')->orderByDesc('cost')->limit(10)->get();
        $itemNames = InventoryItem::whereIn('id', $topItems->pluck('inventory_item_id'))->get(['id', 'part_name', 'item_code', 'unit'])->keyBy('id');

        $lowStock = InventoryItem::lowStock()->orderByRaw('stock_qty / NULLIF(min_stock, 0)')->limit(15)->get();

        $byCategory = InventoryItem::active()->selectRaw("COALESCE(category, 'Other') as cat, SUM(stock_qty * unit_cost) as value, COUNT(*) as items")
            ->groupBy('cat')->orderByDesc('value')->get();

        $summary = [
            'value'      => (float) InventoryItem::active()->sum(DB::raw('stock_qty * unit_cost')),
            'issued'     => (float) $trend->sum('cost'),
            'requests'   => MaterialRequest::where('created_at', '>=', $from)->count(),
            'pending'    => MaterialRequest::where('status', 'pending')->count(),
            'to_issue'   => MaterialRequest::where('status', 'approved')->count(),
            'low'        => InventoryItem::lowStock()->count(),
        ];

        return view('admin.stores.analytics', compact('months', 'trend', 'byProject', 'projectNames', 'topItems', 'itemNames', 'lowStock', 'byCategory', 'summary', 'from'));
    }

    private function validated(Request $request, ?InventoryItem $item = null): array
    {
        $data = $request->validate([
            'item_code'      => ['nullable', 'string', 'max:50', Rule::unique('inventory_items', 'item_code')->ignore($item?->id)],
            'part_name'      => ['required', 'string', 'max:191'],
            'category'       => ['nullable', 'string', 'max:100'],
            'brand'          => ['nullable', 'string', 'max:100'],
            'part_number'    => ['nullable', 'string', 'max:100'],
            'unit'           => ['required', 'string', 'max:30'],
            'unit_cost'      => ['required', 'numeric', 'min:0', 'max:99999999'],
            'min_stock'      => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'opening_stock'  => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'store_location' => ['nullable', 'string', 'max:150'],
            'remarks'        => ['nullable', 'string', 'max:2000'],
            'is_active'      => ['nullable', 'boolean'],
        ], [
            'part_name.required' => 'Please enter the part name.',
            'unit_cost.required' => 'Please enter the unit cost (0 if unknown).',
            'item_code.unique'   => 'This item code is already used.',
        ]);
        $data['min_stock'] = $data['min_stock'] ?? 0;
        $data['is_active'] = $request->boolean('is_active', $item ? false : true);

        return $data;
    }
}
