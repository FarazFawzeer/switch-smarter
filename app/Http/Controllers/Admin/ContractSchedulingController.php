<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\JobRecord;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ContractSchedulingController extends Controller
{
    public function index(Request $request)
    {
        $query = Contract::with([
            'engineer:id,name',
            'route:id,route_no',
            'supervisor:id,name',
            'technician:id,name',
            'renewals',
            'elevatorUnits:id,contract_id',
            'ppmJobs' => function ($q) {
                $q->whereYear('scheduled_date', now()->year)
                    ->whereMonth('scheduled_date', now()->month)
                    ->with(['technician', 'unit']);
            },
        ])
            ->where('status', 'active');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('project_name', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        if ($request->filled('engineer_id')) {
            $query->where('assigned_engineer_id', $request->input('engineer_id'));
        }

        if ($request->filled('scheduled')) {
            $query->where('is_scheduled', $request->input('scheduled') === 'yes');
        }

        if ($request->filled('ppm_status')) {
            $status = $request->input('ppm_status');
            $query->whereHas('ppmJobs', function ($q) use ($status) {
                $q->whereYear('scheduled_date', now()->year)
                    ->whereMonth('scheduled_date', now()->month);

                if ($status === 'overdue') {
                    $q->whereIn('status', ['pending', 'overdue'])->whereDate('scheduled_date', '<', now()->toDateString());
                } elseif ($status === 'pending') {
                    $q->where('status', 'pending')->whereDate('scheduled_date', '>=', now()->toDateString());
                } elseif ($status === 'completed') {
                    $q->where('status', 'completed');
                }
            });
        }

        $sortable = ['project_name', 'location'];
        $sort = $request->get('sort', 'project_name');
        $dir = $request->get('dir', 'asc') === 'desc' ? 'desc' : 'asc';
        $query->orderBy(in_array($sort, $sortable) ? $sort : 'project_name', $dir);

        $contracts = $query->paginate(20)->withQueryString();

        $engineers = User::where('type', 'Engineer')->select('id', 'name')->get();

        return view('admin.scheduling.index', compact('contracts', 'engineers', 'sort', 'dir'));
    }

    public function create(Contract $contract)
    {
        if ($contract->is_scheduled) {
            return redirect()->route('admin.scheduling.show', $contract->id);
        }

        $contract->load('route.users', 'engineer', 'elevatorUnits');

        $supervisors = $contract->route ? $contract->route->supervisors() : collect();
        $technicians = $contract->route ? $contract->route->technicians() : collect();
        $units = $contract->elevatorUnits;

        return view('admin.scheduling.create', compact('contract', 'supervisors', 'technicians', 'units'));
    }

    public function store(Request $request, Contract $contract)
    {
        if ($contract->is_scheduled) {
            return response()->json([
                'success' => false,
                'errors'  => ['ppm_start_date' => ['This contract has already been scheduled.']]
            ], 422);
        }

        $routeSupervisorIds = $contract->route ? $contract->route->supervisors()->pluck('id')->toArray() : [];
        $routeTechnicianIds = $contract->route ? $contract->route->technicians()->pluck('id')->toArray() : [];
        $contractUnitIds = $contract->elevatorUnits()->pluck('id')->toArray();

        $validator = Validator::make($request->all(), [
            'ppm_start_date' => [
                'required',
                'date',
                'after:' . $contract->contract_start_date->format('Y-m-d'),
                'before_or_equal:' . $contract->contract_end_date->format('Y-m-d'),
            ],
            'assigned_supervisor_id' => ['nullable', Rule::in($routeSupervisorIds)],
            'assigned_technician_id' => ['nullable', Rule::in($routeTechnicianIds)],
            'unit_ids'               => ['required', 'array', 'min:1'],
            'unit_ids.*'             => [Rule::in($contractUnitIds)],
        ], [
            'ppm_start_date.after'            => 'PPM start date must be after the contract start date.',
            'ppm_start_date.before_or_equal'  => 'PPM start date cannot be after the contract end date.',
            'assigned_supervisor_id.in'       => 'You can only pick a supervisor assigned to this contract\'s route.',
            'assigned_technician_id.in'       => 'You can only pick a technician assigned to this contract\'s route.',
            'unit_ids.required'               => 'Select at least one elevator unit to schedule.',
            'unit_ids.*.in'                   => 'One or more selected units do not belong to this contract.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        $ppmStartDate = Carbon::parse($request->input('ppm_start_date'));
        $supervisorId = $request->input('assigned_supervisor_id');
        $technicianId = $request->input('assigned_technician_id');
        $unitIds = $request->input('unit_ids');

        DB::transaction(function () use ($contract, $ppmStartDate, $supervisorId, $technicianId, $unitIds) {
            $contract->update([
                'ppm_start_date'         => $ppmStartDate,
                'is_scheduled'           => true,
                'assigned_supervisor_id' => $supervisorId,
                'assigned_technician_id' => $technicianId,
            ]);

            $this->generateMonthlyPpmJobs($contract, $ppmStartDate, $technicianId, $unitIds);
        });

        $technicianName = $technicianId ? User::find($technicianId)->name : null;
        $unitCount = count($unitIds);
        $message = $technicianName
            ? "PPM schedule generated for {$unitCount} unit(s) and assigned to {$technicianName}!"
            : "PPM schedule generated for {$unitCount} unit(s). No technician was assigned — you can assign one later from Jobs.";

        return response()->json([
            'success'  => true,
            'message'  => $message,
            'redirect' => route('admin.scheduling.show', $contract->id),
        ]);
    }

    public function show(Request $request, Contract $contract)
    {
        $contract->load(['engineer', 'route.users', 'supervisor', 'technician', 'renewals.renewedBy', 'elevatorUnits']);

        $totalJobsCount = $contract->ppmJobs()->count();

        $isFiltering = $request->filled('from') || $request->filled('to') || $request->filled('unit_id');

        $query = $contract->ppmJobs()->with(['technician', 'unit'])->orderBy('scheduled_date')->orderBy('elevator_unit_id');

        if ($request->filled('unit_id')) {
            $query->where('elevator_unit_id', $request->input('unit_id'));
        }

        if ($request->filled('from') || $request->filled('to')) {
            if ($request->filled('from')) {
                $query->where('scheduled_date', '>=', $request->input('from'));
            }
            if ($request->filled('to')) {
                $query->where('scheduled_date', '<=', $request->input('to'));
            }
        } elseif (! $request->filled('unit_id')) {
            // Default view: current month and everything before it
            $query->where('scheduled_date', '<=', now()->endOfMonth());
        }

        $visibleJobs = $query->get();

        // Per-unit summary: latest status for each unit, for the quick-glance cards at the top
        $unitSummaries = $contract->elevatorUnits->map(function ($unit) use ($contract) {
            $currentJob = $contract->ppmJobs()
                ->where('elevator_unit_id', $unit->id)
                ->whereYear('scheduled_date', now()->year)
                ->whereMonth('scheduled_date', now()->month)
                ->first();

            $status = 'none';
            $overdueDays = null;

            if ($currentJob) {
                if ($currentJob->status === 'completed') {
                    $status = 'completed';
                } elseif (in_array($currentJob->status, ['pending', 'overdue']) && $currentJob->scheduled_date->copy()->startOfDay()->lt(now()->copy()->startOfDay())) {
                    $status = 'overdue';
                    $overdueDays = (int) $currentJob->scheduled_date->copy()->startOfDay()->diffInDays(now()->copy()->startOfDay());
                } else {
                    $status = 'pending';
                }
            }

            return (object) [
                'unit' => $unit,
                'currentJob' => $currentJob,
                'status' => $status,
                'overdueDays' => $overdueDays,
            ];
        });

        return view('admin.scheduling.show', compact('contract', 'visibleJobs', 'totalJobsCount', 'isFiltering', 'unitSummaries'));
    }

    /**
     * Creates one PPM job per unit, per month, from the start date through the contract's end date.
     */
    private function generateMonthlyPpmJobs(Contract $contract, Carbon $startDate, ?int $technicianId, array $unitIds): void
    {
        $endDate = $contract->contract_end_date;
        $monthOffset = 0;
        $maxIterations = 600; // safety cap (50 years)

        while ($monthOffset < $maxIterations) {
            $visitDate = $startDate->copy()->addMonthsNoOverflow($monthOffset);

            if ($visitDate->gt($endDate)) {
                break;
            }

            foreach ($unitIds as $unitId) {
                JobRecord::create([
                    'contract_id'            => $contract->id,
                    'elevator_unit_id'       => $unitId,
                    'job_type'               => 'ppm',
                    'scheduled_date'         => $visitDate,
                    'status'                 => 'pending',
                    'assigned_technician_id' => $technicianId,
                    'assigned_by'            => Auth::id(),
                    'description'            => 'Auto-generated monthly PPM visit',
                ]);
            }

            $monthOffset++;
        }
    }
}
