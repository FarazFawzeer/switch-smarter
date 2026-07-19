<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ContractController extends Controller
{
    public function index(Request $request)
    {
        $query = Contract::with(['engineer:id,name', 'elevatorUnits'])
            ->visibleTo(Auth::user());

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('project_name', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('contract_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $contracts = $query->latest()->paginate(9)->withQueryString();

        return view('admin.contracts.index', compact('contracts'));
    }

    public function show(Contract $contract)
    {
        $contract->load(['engineer', 'elevatorUnits', 'creator']);

        return view('admin.contracts.show', compact('contract'));
    }

    public function create()
    {
        $engineers = User::where('type', 'Engineer')->select('id', 'name')->get();

        return view('admin.contracts.create', compact('engineers'));
    }

    public function store(Request $request)
    {
        $validator = $this->validateContract($request);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $elevators = $data['elevators'] ?? [];
        unset($data['elevators']);

        $data['status'] = 'active';
        $data['created_by'] = Auth::id();

        if ($request->hasFile('contract_document')) {
            $file = $request->file('contract_document');
            $filename = time() . '_' . Str::slug($data['project_name']) . '.' . $file->getClientOriginalExtension();
            $file->storeAs('contracts', $filename, 'public');
            $data['contract_document'] = 'contracts/' . $filename;
        }

        $contract = DB::transaction(function () use ($data, $elevators) {
            $data['contract_number'] = $this->generateContractNumber();

            $contract = Contract::create($data);

            foreach ($elevators as $elevator) {
                $contract->elevatorUnits()->create($elevator);
            }

            return $contract;
        });

        return response()->json([
            'success'  => true,
            'message'  => 'Project saved successfully!',
            'contract' => $contract,
            'redirect' => route('admin.contracts.show', $contract->id),
        ]);
    }

    public function edit(Contract $contract)
    {
        $contract->load('elevatorUnits');
        $engineers = User::where('type', 'Engineer')->select('id', 'name')->get();

        return view('admin.contracts.edit', compact('contract', 'engineers'));
    }

    public function update(Request $request, Contract $contract)
    {
        $validator = $this->validateContract($request);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $elevators = $data['elevators'] ?? [];
        unset($data['elevators']);

        $data['status'] = $request->input('status', $contract->status);

        if ($request->hasFile('contract_document')) {
            if ($contract->contract_document) {
                Storage::disk('public')->delete($contract->contract_document);
            }
            $file = $request->file('contract_document');
            $filename = time() . '_' . Str::slug($data['project_name']) . '.' . $file->getClientOriginalExtension();
            $file->storeAs('contracts', $filename, 'public');
            $data['contract_document'] = 'contracts/' . $filename;
        }

        DB::transaction(function () use ($contract, $data, $elevators) {
            // contract_number is never changed on update — it was assigned once at creation
            $contract->update($data);

            $contract->elevatorUnits()->delete();
            foreach ($elevators as $elevator) {
                $contract->elevatorUnits()->create($elevator);
            }
        });

        return response()->json([
            'success'  => true,
            'message'  => 'Project updated successfully!',
            'redirect' => route('admin.contracts.show', $contract->id),
        ]);
    }

    public function destroy(Contract $contract)
    {
        if ($contract->contract_document) {
            Storage::disk('public')->delete($contract->contract_document);
        }

        $contract->delete();

        return response()->json([
            'success' => true,
            'message' => 'Project deleted successfully!'
        ]);
    }

    /**
     * Auto-generates the next sequential contract number: 0001, 0002, 0003...
     * Must be called inside a DB transaction with the row locked to avoid
     * two simultaneous submissions generating the same number.
     */
    private function generateContractNumber(): string
    {
        $last = Contract::lockForUpdate()->orderByDesc('id')->first();

        $nextNumber = 1;

        if ($last && $last->contract_number && preg_match('/(\d+)$/', $last->contract_number, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        } elseif ($last) {
            $nextNumber = $last->id + 1;
        }

        return str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }

   private function validateContract(Request $request)
{
    return Validator::make($request->all(), [
        'project_name'            => ['required', 'string', 'max:255'],
        'location'                => ['required', 'string', 'max:255'],
        'number_of_elevators'     => ['required', 'integer', 'min:1', 'max:50'],
        'contract_start_date'     => ['required', 'date'],
        'contract_end_date'       => ['required', 'date', 'after:contract_start_date'],
        'route_no'                => ['nullable', 'string', 'max:100'],
        'assigned_engineer_id'    => [
            'required',
            Rule::exists('users', 'id')->where(fn ($q) => $q->where('type', 'Engineer')),
        ],
        'contract_document'       => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        'status'                  => ['sometimes', 'string', Rule::in(['active', 'expired', 'cancelled'])],

        'elevators'                     => ['required', 'array', 'min:1'],
        'elevators.*.identification_no' => ['required', 'string', 'max:100'],
        'elevators.*.speed'             => ['nullable', 'string', 'max:50'],
        'elevators.*.capacity'          => ['nullable', 'string', 'max:50'],
        'elevators.*.unit_type'         => ['required', Rule::in(['Elevator', 'Escalator', 'Dumbwaiter'])],
        'elevators.*.elevator_type'     => ['nullable', Rule::in(['Passenger', 'Service', 'Freight'])],
        'elevators.*.brand'             => ['nullable', 'string', 'max:100'],
        'elevators.*.model'             => ['nullable', 'string', 'max:100'],
    ], [
        'contract_end_date.after' => 'Contract end date must be after the start date.',
        'elevators.required'      => 'Please add at least one elevator/escalator unit.',
    ]);
}
}