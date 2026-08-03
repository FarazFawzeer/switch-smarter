<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ContractRenewal;
use App\Models\User;
use App\Imports\ElevatorUnitsImport;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class ContractController extends Controller
{
    public function index(Request $request)
    {
        $view = $request->get('view', 'list'); // 'grid' or 'list'

        if ($view === 'grid') {
            return $this->gridView($request);
        }

        return $this->listView($request);
    }

    /**
     * List view: one row per elevator unit, with full contract context attached.
     */
    private function listView(Request $request)
    {
        $visibleContractIds = Contract::visibleTo(Auth::user())->pluck('id');

        $query = \App\Models\ElevatorUnit::query()
            ->join('contracts', 'contracts.id', '=', 'elevator_units.contract_id')
            ->whereIn('elevator_units.contract_id', $visibleContractIds)
            ->select('elevator_units.*', 'contracts.project_name', 'contracts.project_type', 'contracts.location',
                'contracts.contract_number', 'contracts.route_id', 'contracts.assigned_engineer_id',
                'contracts.contract_start_date', 'contracts.contract_end_date', 'contracts.status as contract_status')
            ->with(['contract.route:id,route_no', 'contract.engineer:id,name']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('contracts.project_name', 'like', "%{$search}%")
                    ->orWhere('contracts.location', 'like', "%{$search}%")
                    ->orWhere('contracts.contract_number', 'like', "%{$search}%")
                    ->orWhere('elevator_units.identification_no', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('contracts.status', $request->input('status'));
        }

        if ($request->filled('project_type')) {
            $query->where('contracts.project_type', $request->input('project_type'));
        }

        if ($request->filled('engineer_id')) {
            $query->where('contracts.assigned_engineer_id', $request->input('engineer_id'));
        }

        if ($request->filled('route_id')) {
            $query->where('contracts.route_id', $request->input('route_id'));
        }

        if ($request->filled('unit_type')) {
            $query->where('elevator_units.unit_type', $request->input('unit_type'));
        }

        if ($request->filled('elevator_type')) {
            $query->where('elevator_units.elevator_type', $request->input('elevator_type'));
        }

        $sortable = [
            'project_name'         => 'contracts.project_name',
            'project_type'         => 'contracts.project_type',
            'location'             => 'contracts.location',
            'contract_number'      => 'contracts.contract_number',
            'contract_start_date'  => 'contracts.contract_start_date',
            'contract_end_date'    => 'contracts.contract_end_date',
            'contract_status'      => 'contracts.status',
            'identification_no'    => 'elevator_units.identification_no',
            'unit_type'            => 'elevator_units.unit_type',
            'brand'                => 'elevator_units.brand',
        ];

        $sort = $request->get('sort');
        $dir = $request->get('dir', 'asc') === 'desc' ? 'desc' : 'asc';

        if ($sort && array_key_exists($sort, $sortable)) {
            $query->orderBy($sortable[$sort], $dir);
        } else {
            $query->orderBy('contracts.project_name')->orderBy('elevator_units.identification_no');
        }

        $units = $query->paginate(25)->withQueryString();

        $engineers = User::where('type', 'Engineer')->select('id', 'name')->get();
        $routes = \App\Models\Route::orderBy('route_no')->get();
        $projectTypes = ['Residential', 'Commercial', 'Industrial', 'Hospital', 'Hotel', 'Mixed Use'];
        $unitTypes = ['Elevator', 'Escalator', 'Dumbwaiter'];
        $elevatorTypes = ['Passenger', 'Service', 'Freight'];

        return view('admin.contracts.index', compact(
            'units', 'engineers', 'routes', 'projectTypes', 'unitTypes', 'elevatorTypes', 'sort', 'dir'
        ))->with('view', 'list');
    }

    /**
     * Grid view: unchanged, still one card per contract.
     */
    private function gridView(Request $request)
    {
        $query = Contract::with(['engineer:id,name', 'route:id,route_no', 'elevatorUnits'])
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

        return view('admin.contracts.index', compact('contracts'))->with('view', 'grid');
    }

    public function show(Contract $contract)
    {
        $contract->load(['engineer', 'route', 'elevatorUnits', 'creator', 'renewals.renewedBy']);

        return view('admin.contracts.show', compact('contract'));
    }

    public function create()
    {
        $engineers = User::where('type', 'Engineer')->select('id', 'name')->get();
        $routes = \App\Models\Route::orderBy('route_no')->get();

        return view('admin.contracts.create', compact('engineers', 'routes'));
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
        $newRouteNo = trim($request->input('new_route_no', ''));
        if ($newRouteNo !== '') {
            $route = \App\Models\Route::firstOrCreate(['route_no' => $newRouteNo]);
            $data['route_id'] = $route->id;
        }
        unset($data['new_route_no']);
        $elevators = $data['elevators'] ?? [];
        unset($data['elevators']);

        $data = $this->applyProjectTypeOverride($request, $data);
        $data['custom_fields'] = $this->buildCustomFields(
            $request->input('custom_field_labels', []),
            $request->input('custom_field_values', [])
        );

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

            foreach ($elevators as $index => $elevator) {
                $elevator['custom_fields'] = $this->buildCustomFields(
                    $elevator['custom_field_labels'] ?? [],
                    $elevator['custom_field_values'] ?? []
                );
                unset($elevator['custom_field_labels'], $elevator['custom_field_values']);

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
        $routes = \App\Models\Route::orderBy('route_no')->get();

        return view('admin.contracts.edit', compact('contract', 'engineers', 'routes'));
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
        $newRouteNo = trim($request->input('new_route_no', ''));
        if ($newRouteNo !== '') {
            $route = \App\Models\Route::firstOrCreate(['route_no' => $newRouteNo]);
            $data['route_id'] = $route->id;
        }
        unset($data['new_route_no']);
        $elevators = $data['elevators'] ?? [];
        unset($data['elevators']);

        $data = $this->applyProjectTypeOverride($request, $data);
        $data['custom_fields'] = $this->buildCustomFields(
            $request->input('custom_field_labels', []),
            $request->input('custom_field_values', [])
        );

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
            $contract->update($data);

            if (! empty($elevators)) {
                $contract->elevatorUnits()->delete();
                foreach ($elevators as $elevator) {
                    $elevator['custom_fields'] = $this->buildCustomFields(
                        $elevator['custom_field_labels'] ?? [],
                        $elevator['custom_field_values'] ?? []
                    );
                    unset($elevator['custom_field_labels'], $elevator['custom_field_values']);

                    $contract->elevatorUnits()->create($elevator);
                }
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

    public function importElevatorUnits(Request $request, Contract $contract)
    {
        $validator = Validator::make($request->all(), [
            'units_file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            Excel::import(new ElevatorUnitsImport($contract->id), $request->file('units_file'));
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors'  => ['units_file' => ['One or more rows are missing an identification number. Please check the file and try again.']]
            ], 422);
        }

        return response()->json([
            'success'  => true,
            'message'  => 'Elevator units imported successfully!',
            'redirect' => route('admin.contracts.show', $contract->id),
        ]);
    }

    public function renewForm(Contract $contract)
    {
        abort_unless($contract->status === 'expired' || $contract->contract_end_date->isPast(), 403);

        return view('admin.contracts.renew', compact('contract'));
    }

    public function renew(Request $request, Contract $contract)
    {
        $validator = Validator::make($request->all(), [
            'contract_start_date' => ['required', 'date'],
            'contract_end_date'   => ['required', 'date', 'after:contract_start_date'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        DB::transaction(function () use ($contract, $request) {
            ContractRenewal::create([
                'contract_id'          => $contract->id,
                'previous_start_date'  => $contract->contract_start_date,
                'previous_end_date'    => $contract->contract_end_date,
                'new_start_date'       => $request->input('contract_start_date'),
                'new_end_date'         => $request->input('contract_end_date'),
                'renewed_by'           => Auth::id(),
            ]);

            $contract->update([
                'contract_start_date' => $request->input('contract_start_date'),
                'contract_end_date'   => $request->input('contract_end_date'),
                'status'              => 'active',
                'is_scheduled'        => false,
                'ppm_start_date'      => null,
            ]);
        });

        return response()->json([
            'success'  => true,
            'message'  => 'Contract renewed successfully! You can now schedule PPM for the new term.',
            'redirect' => route('admin.contracts.show', $contract->id),
        ]);
    }

    private function buildCustomFields(array $labels, array $values): array
    {
        $fields = [];
        foreach ($labels as $i => $label) {
            $label = trim($label);
            $value = trim($values[$i] ?? '');
            if ($label !== '') {
                $fields[] = ['label' => $label, 'value' => $value];
            }
        }
        return $fields;
    }

    private function applyProjectTypeOverride(Request $request, array $data): array
    {
        if (($data['project_type'] ?? null) === 'Other') {
            $data['project_type'] = trim($request->input('project_type_other', '')) ?: 'Other';
        }
        return $data;
    }

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
            'project_name'         => ['required', 'string', 'max:255'],
            'route_id'             => ['nullable', 'exists:routes,id'],
            'new_route_no'         => ['nullable', 'string', 'max:50', 'unique:routes,route_no'],
            'project_type'         => ['nullable', 'string', 'max:100'],
            'location'             => ['required', 'string', 'max:255'],
            'number_of_elevators'  => ['required', 'integer', 'min:1', 'max:200'],
            'contract_start_date'  => ['required', 'date'],
            'contract_end_date'    => ['required', 'date', 'after:contract_start_date'],
            'route_no'             => ['nullable', 'string', 'max:100'],
            'assigned_engineer_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn($q) => $q->where('type', 'Engineer')),
            ],
            'contract_document'    => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'status'               => ['sometimes', 'string', Rule::in(['active', 'expired', 'cancelled'])],

            'elevators'                          => ['nullable', 'array'],
            'elevators.*.identification_no'      => ['required_with:elevators', 'string', 'max:100'],
            'elevators.*.speed'                  => ['nullable', 'string', 'max:50'],
            'elevators.*.capacity'               => ['nullable', 'string', 'max:50'],
            'elevators.*.unit_type'              => ['nullable', Rule::in(['Elevator', 'Escalator', 'Dumbwaiter'])],
            'elevators.*.elevator_type'          => ['nullable', Rule::in(['Passenger', 'Service', 'Freight'])],
            'elevators.*.brand'                  => ['nullable', 'string', 'max:100'],
            'elevators.*.model'                  => ['nullable', 'string', 'max:100'],
            'elevators.*.custom_field_labels'    => ['nullable', 'array'],
            'elevators.*.custom_field_labels.*'  => ['nullable', 'string', 'max:255'],
            'elevators.*.custom_field_values'    => ['nullable', 'array'],
            'elevators.*.custom_field_values.*'  => ['nullable', 'string', 'max:255'],
        ], [
            'contract_end_date.after'                      => 'Contract end date must be after the start date.',
            'elevators.*.identification_no.required_with'  => 'Each unit needs an identification number.',
            'number_of_elevators.max'                       => 'For contracts with more than 200 units, please contact support.',
        ]);
    }
}