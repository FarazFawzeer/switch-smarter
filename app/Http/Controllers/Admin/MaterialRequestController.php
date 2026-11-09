<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ElevatorUnit;
use App\Models\InventoryItem;
use App\Models\MaterialRequest;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Material Request (MRN) flow:
 *   Engineer / Supervisor requests a part for a unit (pending)
 *   → Manager / Admin approves (MRN no. given) or rejects
 *   → Storekeeper issues the quantity handed over: stock goes down, cost goes to the project.
 */
class MaterialRequestController extends Controller
{
    private function roleIn(array $roles): bool
    {
        return in_array(Auth::user()->type, $roles, true);
    }

    private function canSee(MaterialRequest $mr): bool
    {
        return MaterialRequest::visibleTo(Auth::user())->whereKey($mr->id)->exists();
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        abort_unless($this->roleIn(array_merge(MaterialRequest::STORE_ROLES, MaterialRequest::REQUESTER_ROLES)), 403);

        $base = MaterialRequest::visibleTo($user);
        $counts = (clone $base)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        // opens on "All"; the tabs narrow it down
        $status = $request->input('status', 'all');

        $query = (clone $base)->with(['item:id,item_code,part_name,unit,stock_qty', 'contract:id,project_name', 'unit:id,identification_no,unit_number,brand', 'requester:id,name,type']);

        if (array_key_exists($status, MaterialRequest::STATUSES)) {
            $query->where('status', $status);
        }
        if ($request->filled('ref')) {
            $ref = $request->input('ref');
            $query->where(fn ($q) => $q->where('mrn_no', 'like', "%{$ref}%")
                ->orWhere('id', (int) preg_replace('/\D/', '', $ref) ?: 0));
        }
        if ($request->filled('project')) {
            $query->whereHas('contract', fn ($q) => $q->where('project_name', 'like', '%' . $request->input('project') . '%'));
        }
        if ($request->filled('unit')) {
            $query->whereHas('unit', fn ($q) => $q->where('identification_no', 'like', '%' . $request->input('unit') . '%')
                ->orWhere('unit_number', 'like', '%' . $request->input('unit') . '%'));
        }
        if ($request->filled('part')) {
            $part = $request->input('part');
            $query->whereHas('item', fn ($q) => $q->where('part_name', 'like', "%{$part}%")->orWhere('item_code', 'like', "%{$part}%"));
        }
        if ($request->filled('requested_by')) {
            $query->whereHas('requester', fn ($q) => $q->where('name', 'like', '%' . $request->input('requested_by') . '%'));
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }

        $requests = $query->latest('id')->paginate(25)->withQueryString();

        return view('admin.stores.requests.index', [
            'requests'   => $requests,
            'status'     => $status,
            'counts'     => $counts,
            'canRequest' => $this->roleIn(MaterialRequest::REQUESTER_ROLES),
            'canApprove' => $this->roleIn(MaterialRequest::APPROVER_ROLES),
            'canIssue'   => $this->roleIn(MaterialRequest::ISSUER_ROLES),
        ]);
    }

    public function create(Request $request)
    {
        abort_unless($this->roleIn(MaterialRequest::REQUESTER_ROLES), 403, 'Only engineers and supervisors raise material requests.');

        $contracts = Contract::visibleTo(Auth::user())->whereHas('elevatorUnits')->orderBy('project_name')->get(['id', 'project_name', 'location']);
        // a part chosen from the item page, or the one picked before a validation error
        $itemId = $request->old('inventory_item_id', $request->input('item'));
        $item = $itemId ? InventoryItem::active()->find($itemId) : null;

        return view('admin.stores.requests.create', compact('contracts', 'item'));
    }

    /** AJAX: units of a project (with unit no and brand) for the request form. */
    public function units(Contract $contract)
    {
        abort_unless(Contract::visibleTo(Auth::user())->whereKey($contract->id)->exists(), 403);

        return response()->json(['units' => $contract->elevatorUnits()->orderBy('identification_no')
            ->get(['id', 'identification_no', 'unit_number', 'brand', 'model', 'unit_type'])]);
    }

    public function store(Request $request)
    {
        abort_unless($this->roleIn(MaterialRequest::REQUESTER_ROLES), 403);

        $data = $request->validate([
            'contract_id'        => ['required', 'exists:contracts,id'],
            'elevator_unit_id'   => ['required', 'exists:elevator_units,id'],
            'inventory_item_id'  => ['required', 'exists:inventory_items,id'],
            'quantity_requested' => ['required', 'numeric', 'gt:0', 'max:999999'],
            'description'        => ['required', 'string', 'max:2000'],
            'photo'              => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ], [
            'contract_id.required'        => 'Please choose the project.',
            'elevator_unit_id.required'   => 'Please choose the unit.',
            'inventory_item_id.required'  => 'Please choose the part from the store list.',
            'quantity_requested.required' => 'Please enter the quantity.',
            'quantity_requested.gt'       => 'Quantity must be more than zero.',
            'description.required'        => 'Please describe why the part is needed.',
        ]);

        abort_unless(Contract::visibleTo(Auth::user())->whereKey($data['contract_id'])->exists(), 403);
        if (! ElevatorUnit::whereKey($data['elevator_unit_id'])->where('contract_id', $data['contract_id'])->exists()) {
            return back()->withInput()->withErrors(['elevator_unit_id' => 'This unit does not belong to the chosen project.']);
        }
        if (! InventoryItem::active()->whereKey($data['inventory_item_id'])->exists()) {
            return back()->withInput()->withErrors(['inventory_item_id' => 'This part is no longer stocked.']);
        }

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $name = time() . '_' . Str::random(6) . '.' . strtolower($file->getClientOriginalExtension());
            $file->storeAs('material-requests', $name, 'public');
            $data['photo_path'] = 'material-requests/' . $name;
        }
        unset($data['photo']);

        $mr = MaterialRequest::create($data + ['status' => 'pending', 'requested_by' => Auth::id()]);

        return redirect()->route('admin.stores.requests.show', $mr)->with('success', 'Material request ' . $mr->reference() . ' sent for approval.');
    }

    public function show(MaterialRequest $mr)
    {
        abort_unless($this->canSee($mr), 403);

        $mr->load(['item', 'contract:id,project_name,location', 'unit:id,identification_no,unit_number,brand,model,unit_type',
            'requester:id,name,type', 'approver:id,name,type', 'issuer:id,name,type', 'transactions.user:id,name']);

        return view('admin.stores.requests.show', [
            'mr'         => $mr,
            'canApprove' => $mr->status === 'pending' && $this->roleIn(MaterialRequest::APPROVER_ROLES),
            'canIssue'   => $mr->status === 'approved' && $this->roleIn(MaterialRequest::ISSUER_ROLES),
            'canCancel'  => $mr->status === 'pending' && $mr->requested_by === Auth::id(),
        ]);
    }

    public function approve(Request $request, MaterialRequest $mr)
    {
        abort_unless($this->roleIn(MaterialRequest::APPROVER_ROLES), 403);
        $data = $request->validate(['remarks' => ['nullable', 'string', 'max:500']]);

        $result = DB::transaction(function () use ($mr, $data) {
            $locked = MaterialRequest::whereKey($mr->id)->lockForUpdate()->first();
            if ($locked->status !== 'pending') {
                return null;
            }
            $locked->update([
                'status'           => 'approved',
                'mrn_no'           => MaterialRequest::nextMrnNo(),
                'approved_by'      => Auth::id(),
                'approved_at'      => now(),
                'approval_remarks' => $data['remarks'] ?? null,
            ]);

            return $locked;
        });

        if (! $result) {
            return response()->json(['success' => false, 'message' => 'This request is no longer waiting for approval.'], 422);
        }

        return response()->json(['success' => true, 'message' => "Approved as {$result->mrn_no}. The storekeeper can now issue it."]);
    }

    public function reject(Request $request, MaterialRequest $mr)
    {
        abort_unless($this->roleIn(MaterialRequest::APPROVER_ROLES), 403);
        $data = $request->validate(['remarks' => ['required', 'string', 'max:500']], ['remarks.required' => 'Please give the reason for rejecting.']);

        $updated = MaterialRequest::whereKey($mr->id)->where('status', 'pending')->update([
            'status' => 'rejected', 'approved_by' => Auth::id(), 'approved_at' => now(), 'approval_remarks' => $data['remarks'],
        ]);

        return $updated
            ? response()->json(['success' => true, 'message' => 'Request rejected.'])
            : response()->json(['success' => false, 'message' => 'This request is no longer waiting for approval.'], 422);
    }

    public function issue(Request $request, MaterialRequest $mr)
    {
        abort_unless($this->roleIn(MaterialRequest::ISSUER_ROLES), 403);
        $data = $request->validate([
            'quantity' => ['required', 'numeric', 'gt:0', 'max:999999'],
            'remarks'  => ['nullable', 'string', 'max:500'],
        ], ['quantity.gt' => 'Quantity must be more than zero.']);

        try {
            $issued = StockService::issue($mr, (float) $data['quantity'], $data['remarks'] ?? null, Auth::user());
        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $item = $issued->item;

        return response()->json(['success' => true, 'message' => 'Issued ' . qty($issued->quantity_issued) . ' ' . $item->unit . ' of ' . $item->part_name
            . ' (' . money($issued->total_cost) . ' charged to the project). Stock left: ' . qty($item->stock_qty) . '.']);
    }

    public function cancel(MaterialRequest $mr)
    {
        $updated = MaterialRequest::whereKey($mr->id)->where('status', 'pending')->where('requested_by', Auth::id())->update(['status' => 'cancelled']);

        return $updated
            ? response()->json(['success' => true, 'message' => 'Request cancelled.'])
            : response()->json(['success' => false, 'message' => 'Only your own pending request can be cancelled.'], 422);
    }
}
