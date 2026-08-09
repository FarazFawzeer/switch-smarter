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
            // 'yes' = fully scheduled, 'partial' = some units left, 'no' = nothing scheduled yet
            $status = $request->input('scheduled');
            $query->get(); // handled after fetch below since it needs per-unit computation
        }

        $sortable = ['project_name', 'location'];
        $sort = $request->get('sort', 'project_name');
        $dir = $request->get('dir', 'asc') === 'desc' ? 'desc' : 'asc';
        $query->orderBy(in_array($sort, $sortable) ? $sort : 'project_name', $dir);

        $contracts = $query->paginate(20)->withQueryString();

        // Attach a computed scheduling status to each contract for the view
        $contracts->getCollection()->transform(function ($contract) {
            $total = $contract->elevatorUnits->count();
            $scheduled = $contract->scheduledUnitsCount();

            $contract->scheduling_status = $total === 0
                ? 'no_units'
                : ($scheduled === 0
                    ? 'not_scheduled'
                    : ($scheduled < $total ? 'partial' : 'complete'));
            $contract->scheduled_units_count = $scheduled;
            $contract->total_units_count = $total;

            return $contract;
        });

        // Apply the "scheduled" filter now that status is computed (small in-memory filter on the current page)
        if ($request->filled('scheduled')) {
            $status = $request->input('scheduled');
            $map = ['yes' => 'complete', 'partial' => 'partial', 'no' => 'not_scheduled'];
            if (isset($map[$status])) {
                $contracts->setCollection(
                    $contracts->getCollection()->filter(fn($c) => $c->scheduling_status === $map[$status])->values()
                );
            }
        }

        $engineers = User::where('type', 'Engineer')->select('id', 'name')->get();

        return view('admin.scheduling.index', compact('contracts', 'engineers', 'sort', 'dir'));
    }

    public function create(Contract $contract)
    {
        $contract->load('route.users', 'engineer', 'elevatorUnits');

        $supervisors = $contract->route ? $contract->route->supervisors() : collect();
        $technicians = $contract->route ? $contract->route->technicians() : collect();

        $scheduledUnitIds = JobRecord::where('contract_id', $contract->id)
            ->where('job_type', 'ppm')
            ->whereNotNull('elevator_unit_id')
            ->distinct()
            ->pluck('elevator_unit_id')
            ->toArray();

        $units = $contract->elevatorUnits->whereNotIn('id', $scheduledUnitIds)->values();
        $scheduledUnits = $contract->elevatorUnits->whereIn('id', $scheduledUnitIds)->values();

        return view('admin.scheduling.create', compact('contract', 'supervisors', 'technicians', 'units', 'scheduledUnits'));
    }

    public function store(Request $request, Contract $contract)
    {
        $routeSupervisorIds = $contract->route ? $contract->route->supervisors()->pluck('id')->toArray() : [];
        $routeTechnicianIds = $contract->route ? $contract->route->technicians()->pluck('id')->toArray() : [];
        $contractUnitIds = $contract->elevatorUnits()->pluck('id')->toArray();

        $alreadyScheduledUnitIds = JobRecord::where('contract_id', $contract->id)
            ->where('job_type', 'ppm')
            ->whereNotNull('elevator_unit_id')
            ->distinct()
            ->pluck('elevator_unit_id')
            ->toArray();

        $validator = Validator::make($request->all(), [
            'ppm_start_date' => [
                'required',
                'date',
                'after:' . $contract->contract_start_date->format('Y-m-d'),
                'before_or_equal:' . $contract->contract_end_date->format('Y-m-d'),
            ],
            'assigned_supervisor_id' => ['nullable', Rule::in($routeSupervisorIds)],
            'assigned_technician_id' => [
                'required',
                Rule::in($routeTechnicianIds),
            ],
            'unit_ids'   => ['required', 'array', 'min:1'],
            'unit_ids.*' => [
                Rule::in($contractUnitIds),
                Rule::notIn($alreadyScheduledUnitIds),
            ],
        ], [
            'ppm_start_date.after'            => 'PPM start date must be after the contract start date.',
            'ppm_start_date.before_or_equal'  => 'PPM start date cannot be after the contract end date.',
            'ppm_start_date.required'         => 'Please assign a PPM date before completing the schedule.',
            'assigned_technician_id.required' => 'Please assign a technician before completing the PPM schedule.',
            'assigned_supervisor_id.in'       => 'You can only pick a supervisor assigned to this contract\'s route.',
            'assigned_technician_id.in'       => 'You can only pick a technician assigned to this contract\'s route.',
            'unit_ids.required'               => 'Select at least one elevator unit to schedule.',
            'unit_ids.*.in'                   => 'One or more selected units do not belong to this contract.',
            'unit_ids.*.not_in'               => 'One or more selected units have already been scheduled.',
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
            $updateData = [
                'assigned_supervisor_id' => $supervisorId,
                'assigned_technician_id' => $technicianId,
            ];

            // Only set the contract's headline ppm_start_date the first time it's ever scheduled
            if (! $contract->ppm_start_date) {
                $updateData['ppm_start_date'] = $ppmStartDate;
            }

            $this->generateMonthlyPpmJobs($contract, $ppmStartDate, $technicianId, $unitIds);

            // Mark fully scheduled only once every unit on the contract has at least one PPM job
            $totalUnits = $contract->elevatorUnits()->count();
            $scheduledUnits = $contract->scheduledUnitsCount();
            $updateData['is_scheduled'] = $scheduledUnits >= $totalUnits;

            $contract->update($updateData);
        });

        $technicianName = User::find($technicianId)->name;
        $unitCount = count($unitIds);

        return response()->json([
            'success'  => true,
            'message'  => "PPM schedule generated for {$unitCount} unit(s) and assigned to {$technicianName}!",
            'redirect' => route('admin.scheduling.show', $contract->id),
        ]);
    }

    public function show(Request $request, Contract $contract)
    {
        $contract->load(['engineer', 'route.users', 'supervisor', 'technician', 'renewals.renewedBy']);

        $totalJobsCount = $contract->ppmJobs()->count();

        $isFiltering = $request->filled('from') || $request->filled('to') || $request->filled('unit_id');
$query = $contract->ppmJobs()->with(['technician', 'unit'])->orderBy('scheduled_date')->orderBy('elevator_unit_id');

if ($request->filled('unit_id')) {
    $query->where('elevator_unit_id', $request->input('unit_id'));
}

if ($request->filled('from') || $request->filled('to')) {
    // Explicit date range always takes priority, whether or not a unit is selected
    if ($request->filled('from')) {
        $query->where('scheduled_date', '>=', $request->input('from'));
    }
    if ($request->filled('to')) {
        $query->where('scheduled_date', '<=', $request->input('to'));
    }
} elseif ($request->filled('unit_id')) {
    // A specific unit was selected with no date range — show only its current month's visit
    $query->whereYear('scheduled_date', now()->year)
          ->whereMonth('scheduled_date', now()->month);
} else {
    // Default view (no unit, no dates): current month and everything before it
    $query->where('scheduled_date', '<=', now()->endOfMonth());
}

$visibleJobs = $query->get();
        // ===== Unit overview: aggregate counts via SQL, no per-unit loop =====
        $totalUnits = $contract->elevatorUnits()->count();

        $monthStats = \App\Models\JobRecord::where('contract_id', $contract->id)
            ->where('job_type', 'ppm')
            ->whereYear('scheduled_date', now()->year)
            ->whereMonth('scheduled_date', now()->month)
            ->selectRaw("
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status IN ('pending','overdue') AND scheduled_date < ? THEN 1 ELSE 0 END) as overdue,
            SUM(CASE WHEN status = 'pending' AND scheduled_date >= ? THEN 1 ELSE 0 END) as pending,
            COUNT(DISTINCT elevator_unit_id) as scheduled_this_month
        ", [now()->toDateString(), now()->toDateString()])
            ->first();

        $everScheduledCount = \App\Models\JobRecord::where('contract_id', $contract->id)
            ->where('job_type', 'ppm')
            ->distinct('elevator_unit_id')
            ->count('elevator_unit_id');

        $unitStatusCounts = [
            'completed'   => (int) ($monthStats->completed ?? 0),
            'pending'     => (int) ($monthStats->pending ?? 0),
            'overdue'     => (int) ($monthStats->overdue ?? 0),
            'unscheduled' => $totalUnits - $everScheduledCount,
            'none'        => max(0, $everScheduledCount - ($monthStats->scheduled_this_month ?? 0)),
        ];

        // ===== Paginated, searchable/filterable unit table =====
        $unitsQuery = $contract->elevatorUnits()->orderBy('identification_no');

        if ($request->filled('unit_search')) {
            $unitsQuery->where('identification_no', 'like', '%' . $request->input('unit_search') . '%');
        }

        if ($request->filled('unit_status')) {
            $status = $request->input('unit_status');
            $unitsQuery->where(function ($q) use ($status) {
                if ($status === 'unscheduled') {
                    $q->whereDoesntHave('ppmJobs');
                } elseif ($status === 'none') {
                    $q->whereHas('ppmJobs')
                        ->whereDoesntHave('ppmJobs', function ($jq) {
                            $jq->whereYear('scheduled_date', now()->year)->whereMonth('scheduled_date', now()->month);
                        });
                } elseif ($status === 'completed') {
                    $q->whereHas('ppmJobs', function ($jq) {
                        $jq->whereYear('scheduled_date', now()->year)->whereMonth('scheduled_date', now()->month)
                            ->where('status', 'completed');
                    });
                } elseif ($status === 'overdue') {
                    $q->whereHas('ppmJobs', function ($jq) {
                        $jq->whereYear('scheduled_date', now()->year)->whereMonth('scheduled_date', now()->month)
                            ->whereIn('status', ['pending', 'overdue'])
                            ->whereDate('scheduled_date', '<', now()->toDateString());
                    });
                } elseif ($status === 'pending') {
                    $q->whereHas('ppmJobs', function ($jq) {
                        $jq->whereYear('scheduled_date', now()->year)->whereMonth('scheduled_date', now()->month)
                            ->where('status', 'pending')
                            ->whereDate('scheduled_date', '>=', now()->toDateString());
                    });
                }
            });
        }

        $paginatedUnits = $unitsQuery->paginate(5, ['*'], 'unit_page')->withQueryString();

        // Fetch this month's jobs only for the units on the current page — one query, not N queries
        $pagedUnitIds = $paginatedUnits->pluck('id');
        $currentMonthJobs = \App\Models\JobRecord::whereIn('elevator_unit_id', $pagedUnitIds)
            ->where('job_type', 'ppm')
            ->whereYear('scheduled_date', now()->year)
            ->whereMonth('scheduled_date', now()->month)
            ->with('technician')
            ->get()
            ->keyBy('elevator_unit_id');

        $everScheduledUnitIds = \App\Models\JobRecord::whereIn('elevator_unit_id', $pagedUnitIds)
            ->where('job_type', 'ppm')
            ->distinct()
            ->pluck('elevator_unit_id')
            ->toArray();

        $unitSummaries = $paginatedUnits->getCollection()->map(function ($unit) use ($currentMonthJobs, $everScheduledUnitIds) {
            $currentJob = $currentMonthJobs->get($unit->id);
            $hasEverBeenScheduled = in_array($unit->id, $everScheduledUnitIds);

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
            } elseif (! $hasEverBeenScheduled) {
                $status = 'unscheduled';
            }

            return (object) [
                'unit' => $unit,
                'currentJob' => $currentJob,
                'status' => $status,
                'overdueDays' => $overdueDays,
            ];
        });

        $unscheduledCount = $unitStatusCounts['unscheduled'];

        return view('admin.scheduling.show', compact(
            'contract',
            'visibleJobs',
            'totalJobsCount',
            'isFiltering',
            'unitSummaries',
            'paginatedUnits',
            'unitStatusCounts',
            'unscheduledCount'
        ));
    }

    private function generateMonthlyPpmJobs(Contract $contract, Carbon $startDate, ?int $technicianId, array $unitIds): void
    {
        $endDate = $contract->contract_end_date;
        $monthOffset = 0;
        $maxIterations = 600;

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
