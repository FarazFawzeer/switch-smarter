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
        $view = $request->get('view', 'list'); // list view shown first by default

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

        if ($request->filled('project_type')) {
            $query->where('project_type', $request->input('project_type'));
        }

        if ($request->filled('engineer_id')) {
            $query->where('assigned_engineer_id', $request->input('engineer_id'));
        }

        if ($request->filled('route_id')) {
            $query->where('route_id', $request->input('route_id'));
        }

        $sortable = ['project_name', 'project_type', 'location', 'contract_number', 'contract_start_date', 'contract_end_date', 'status'];
        $sort = $request->get('sort');
        $dir = $request->get('dir', 'asc') === 'desc' ? 'desc' : 'asc';

        if ($sort && in_array($sort, $sortable)) {
            $query->orderBy($sort, $dir);
        } else {
            $query->latest();
        }

        $contracts = $query->paginate($view === 'list' ? 25 : 9)->withQueryString();

        $engineers = User::where('type', 'Engineer')->select('id', 'name')->get();
        $routes = \App\Models\Route::orderBy('route_no')->get();
        $projectTypes = ['Residential', 'Commercial', 'Industrial', 'Hospital', 'Hotel', 'Mixed Use'];

        return view('admin.contracts.index', compact('contracts', 'view', 'engineers', 'routes', 'projectTypes', 'sort', 'dir'));
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

            // Manual rows submitted here fully replace existing units.
            // Units added separately via bulk import (on this same edit page) are untouched
            // unless the admin also resubmits this main form's elevator rows.
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

    /**
     * Bulk import elevator units from an Excel/CSV file.
     * Adds to existing units — does not remove what's already there.
     */
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

    /**
     * Show the renewal form — only reachable for expired contracts.
     */
    public function renewForm(Contract $contract)
    {
        abort_unless($contract->status === 'expired' || $contract->contract_end_date->isPast(), 403);

        return view('admin.contracts.renew', compact('contract'));
    }

    /**
     * Process a renewal: keep the same contract, log the old term, apply the new one.
     */
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
                'is_scheduled'        => false,   // allows PPM scheduling to run fresh for the new term
                'ppm_start_date'      => null,
            ]);
        });

        return response()->json([
            'success'  => true,
            'message'  => 'Contract renewed successfully! You can now schedule PPM for the new term.',
            'redirect' => route('admin.contracts.show', $contract->id),
        ]);
    }

    /**
     * Builds a clean custom_fields array from parallel label/value input arrays,
     * skipping any row where the label was left blank.
     */
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

    /**
     * If "Other" was selected for project type, use the free-text value instead.
     */
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
