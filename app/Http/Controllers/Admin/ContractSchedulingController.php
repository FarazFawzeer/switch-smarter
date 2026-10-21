<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ElevatorUnit;
use App\Models\JobRecord;
use App\Models\JobReschedule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ContractSchedulingController extends Controller
{
    /** SQL: distinct units of a contract that have at least one PPM visit (orphan visits excluded). */
    private const SCHEDULED_UNITS_SQL = "(SELECT COUNT(DISTINCT j.elevator_unit_id) FROM jobs_records j
        WHERE j.contract_id = contracts.id AND j.job_type = 'ppm' AND j.elevator_unit_id IS NOT NULL)";

    private const TOTAL_UNITS_SQL = '(SELECT COUNT(*) FROM elevator_units u WHERE u.contract_id = contracts.id)';

    public function index(Request $request)
    {
        $view = $request->get('view', 'list'); // 'list' or 'calendar'

        $engineers = User::where('type', 'Engineer')->select('id', 'name')->get();
        $supervisors = User::where('type', 'Supervisor')->select('id', 'name')->orderBy('name')->get();
        $routes = \App\Models\Route::orderBy('route_no')->get();
        $technicians = User::where('type', 'Technician')->select('id', 'name', 'supervisor_id')->orderBy('name')->get();

        if ($view === 'calendar') {
            return $this->calendarView($request, $engineers, $routes, $technicians, $supervisors);
        }

        // Counts come from SQL sub-queries (no per-contract queries), so the
        // "scheduled" filter runs in the database BEFORE pagination.
        $query = Contract::query()
            ->select('contracts.*')
            ->selectRaw(self::SCHEDULED_UNITS_SQL . ' AS scheduled_units_count')
            ->selectRaw(self::TOTAL_UNITS_SQL . ' AS total_units_count')
            ->selectRaw("(SELECT GROUP_CONCAT(DISTINCT tu.name ORDER BY tu.name SEPARATOR ', ')
                FROM jobs_records j JOIN users tu ON tu.id = j.assigned_technician_id
                WHERE j.contract_id = contracts.id AND j.job_type = 'ppm' AND j.elevator_unit_id IS NOT NULL) AS technician_names")
            ->with([
                'engineer:id,name',
                'route:id,route_no',
                'supervisor:id,name',
                'technician:id,name',
                'renewals',
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

        $this->applyPeopleFilters($query, $request);

        if ($request->filled('scheduled')) {
            $scheduled = self::SCHEDULED_UNITS_SQL;
            $total = self::TOTAL_UNITS_SQL;

            match ($request->input('scheduled')) {
                'yes'     => $query->whereRaw("{$total} > 0 AND {$scheduled} >= {$total}"),
                'partial' => $query->whereRaw("{$scheduled} > 0 AND {$scheduled} < {$total}"),
                'no'      => $query->whereRaw("{$total} > 0 AND {$scheduled} = 0"),
                default   => null,
            };
        }

        $sortable = ['project_name', 'location'];
        $sort = $request->get('sort', 'project_name');
        $dir = $request->get('dir', 'asc') === 'desc' ? 'desc' : 'asc';
        $query->orderBy(in_array($sort, $sortable) ? $sort : 'project_name', $dir);

        $contracts = $query->paginate(20)->withQueryString();

        $contracts->getCollection()->transform(function ($contract) {
            $total = (int) $contract->total_units_count;
            $scheduled = (int) $contract->scheduled_units_count;

            $contract->scheduling_status = $total === 0
                ? 'no_units'
                : ($scheduled === 0
                    ? 'not_scheduled'
                    : ($scheduled < $total ? 'partial' : 'complete'));

            return $contract;
        });

        return view('admin.scheduling.index', compact('contracts', 'engineers', 'supervisors', 'technicians', 'sort', 'dir', 'view'));
    }

    /**
     * Supervisor / technician filters. A contract matches when the person is assigned at
     * contract level OR holds (or supervises whoever holds) one of its PPM visits.
     */
    private function applyPeopleFilters($query, Request $request): void
    {
        if ($request->filled('supervisor_id')) {
            $supervisorId = (int) $request->input('supervisor_id');
            $query->where(function ($q) use ($supervisorId) {
                $q->where('assigned_supervisor_id', $supervisorId)
                    ->orWhereExists(function ($sub) use ($supervisorId) {
                        $sub->selectRaw(1)->from('jobs_records as j')
                            ->join('users as t', 't.id', '=', 'j.assigned_technician_id')
                            ->whereColumn('j.contract_id', 'contracts.id')
                            ->where('j.job_type', 'ppm')
                            ->where('t.supervisor_id', $supervisorId);
                    });
            });
        }

        if ($request->filled('technician_id')) {
            $technicianId = (int) $request->input('technician_id');
            $query->where(function ($q) use ($technicianId) {
                $q->where('assigned_technician_id', $technicianId)
                    ->orWhereExists(function ($sub) use ($technicianId) {
                        $sub->selectRaw(1)->from('jobs_records as j')
                            ->whereColumn('j.contract_id', 'contracts.id')
                            ->where('j.job_type', 'ppm')
                            ->where('j.assigned_technician_id', $technicianId);
                    });
            });
        }
    }

    /**
     * Company-wide calendar: every PPM visit across all contracts/units for the selected month.
     */
    private function calendarView(Request $request, $engineers, $routes, $technicians, $supervisors)
    {
        $month = (int) $request->get('month', now()->month);
        $year = (int) $request->get('year', now()->year);

        $monthStart = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $query = JobRecord::where('job_type', 'ppm')
            ->whereNotNull('elevator_unit_id')
            ->whereBetween('scheduled_date', [$monthStart, $monthEnd])
            ->with(['unit:id,identification_no,unit_number', 'contract:id,project_name,route_id', 'contract.route:id,route_no', 'technician:id,name']);

        if ($request->filled('route_id')) {
            $query->whereHas('contract', fn($q) => $q->where('route_id', $request->input('route_id')));
        }

        if ($request->filled('supervisor_id')) {
            $query->whereHas('technician', fn($q) => $q->where('supervisor_id', $request->input('supervisor_id')));
        }

        if ($request->filled('technician_id')) {
            $query->where('assigned_technician_id', $request->input('technician_id'));
        }

        $jobs = $query->get()->groupBy(fn($job) => $job->scheduled_date->day);

        // Build the full 6-row calendar grid, including faded leading/trailing days from adjacent months
        $calendarWeeks = [];
        $gridStart = $monthStart->copy()->startOfWeek(Carbon::SUNDAY);
        $cursor = $gridStart->copy();

        for ($week = 0; $week < 6; $week++) {
            $days = [];
            for ($d = 0; $d < 7; $d++) {
                $days[] = [
                    'date'         => $cursor->copy(),
                    'inMonth'      => $cursor->month === $month,
                    'isToday'      => $cursor->isToday(),
                    'jobs'         => $cursor->month === $month ? ($jobs->get($cursor->day) ?? collect()) : collect(),
                ];
                $cursor->addDay();
            }
            $calendarWeeks[] = $days;
            if ($cursor->month !== $month && $week >= 4) {
                break; // stop once we've fully wrapped past the target month
            }
        }

        $monthOptions = collect(range(1, 12))->mapWithKeys(fn($m) => [$m => Carbon::create()->month($m)->format('F')]);
        $yearOptions = range(now()->year - 2, now()->year + 2);

        return view('admin.scheduling.index', compact(
            'calendarWeeks',
            'month',
            'year',
            'monthOptions',
            'yearOptions',
            'engineers',
            'routes',
            'technicians',
            'supervisors'
        ))->with('view', 'calendar');
    }

    public function create(Contract $contract)
    {
        $contract->load('route.users', 'engineer', 'elevatorUnits');

        $supervisors = $contract->route ? $contract->route->supervisors() : collect();
        $technicians = $contract->route ? $contract->route->technicians() : collect();

        $scheduledUnitIds = $this->scheduledUnitIds($contract);

        $units = $contract->elevatorUnits->whereNotIn('id', $scheduledUnitIds)->values();
        $scheduledUnits = $contract->elevatorUnits->whereIn('id', $scheduledUnitIds)->values();

        return view('admin.scheduling.create', compact('contract', 'supervisors', 'technicians', 'units', 'scheduledUnits'));
    }

    public function store(Request $request, Contract $contract)
    {
        $routeSupervisorIds = $contract->route ? $contract->route->supervisors()->pluck('id')->toArray() : [];
        $routeTechnicianIds = $contract->route ? $contract->route->technicians()->pluck('id')->toArray() : [];
        $contractUnitIds = $contract->elevatorUnits()->pluck('id')->toArray();
        $alreadyScheduledUnitIds = $this->scheduledUnitIds($contract);

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
        $technicianId = (int) $request->input('assigned_technician_id');
        $unitIds = array_values(array_unique(array_map('intval', $request->input('unit_ids'))));
        $batch = (string) Str::uuid();

        $created = DB::transaction(function () use ($contract, $ppmStartDate, $supervisorId, $technicianId, $unitIds, $batch) {
            // Lock the contract row so a double-click / two tabs can't schedule the same units twice
            Contract::whereKey($contract->id)->lockForUpdate()->first();

            // Re-check inside the lock: drop any unit that got scheduled in the meantime
            $unitIds = array_values(array_diff($unitIds, $this->scheduledUnitIds($contract)));
            if (empty($unitIds)) {
                return 0;
            }

            $created = $this->generateMonthlyPpmJobs($contract, $ppmStartDate, $technicianId, $unitIds, $batch);

            $updateData = [
                'assigned_supervisor_id' => $supervisorId,
                'assigned_technician_id' => $technicianId,
            ];

            // Only set the contract's headline ppm_start_date the first time it's ever scheduled
            if (! $contract->ppm_start_date) {
                $updateData['ppm_start_date'] = $ppmStartDate;
            }

            $contract->update($updateData);
            $this->refreshScheduledFlag($contract);

            return $created;
        });

        if ($created === 0) {
            return response()->json([
                'success' => false,
                'errors'  => ['unit_ids' => ['These units were already scheduled (possibly from another tab). Nothing was added.']],
            ], 422);
        }

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

        // Orphan visits (unit deleted) are excluded everywhere on this page
        $ppmJobs = fn () => $contract->ppmJobs()->whereNotNull('elevator_unit_id');

        $totalJobsCount = $ppmJobs()->count();

        $isFiltering = $request->filled('from') || $request->filled('to') || $request->filled('unit_id');
        $query = $ppmJobs()
            ->with(['technician', 'unit', 'reschedules' => fn ($q) => $q->whereNull('undone_at')])
            ->orderBy('scheduled_date')
            ->orderBy('elevator_unit_id');

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

        $monthStats = JobRecord::where('contract_id', $contract->id)
            ->where('job_type', 'ppm')
            ->whereNotNull('elevator_unit_id')
            ->whereYear('scheduled_date', now()->year)
            ->whereMonth('scheduled_date', now()->month)
            ->selectRaw("
            COUNT(DISTINCT CASE WHEN status = 'completed' THEN elevator_unit_id END) as completed,
            COUNT(DISTINCT CASE WHEN status IN ('pending','overdue') AND scheduled_date < ? THEN elevator_unit_id END) as overdue,
            COUNT(DISTINCT CASE WHEN status = 'pending' AND scheduled_date >= ? THEN elevator_unit_id END) as pending,
            COUNT(DISTINCT elevator_unit_id) as scheduled_this_month
        ", [now()->toDateString(), now()->toDateString()])
            ->first();

        $everScheduledCount = count($this->scheduledUnitIds($contract));

        $unitStatusCounts = [
            'completed'   => (int) ($monthStats->completed ?? 0),
            'pending'     => (int) ($monthStats->pending ?? 0),
            'overdue'     => (int) ($monthStats->overdue ?? 0),
            'unscheduled' => max(0, $totalUnits - $everScheduledCount),
            'none'        => max(0, $everScheduledCount - ($monthStats->scheduled_this_month ?? 0)),
        ];

        // ===== Paginated, searchable/filterable unit table =====
        $unitsQuery = $contract->elevatorUnits()->orderBy('identification_no');

        if ($request->filled('unit_search')) {
            $unitSearch = $request->input('unit_search');
            $unitsQuery->where(function ($q) use ($unitSearch) {
                $q->where('identification_no', 'like', '%' . $unitSearch . '%')
                    ->orWhere('unit_number', 'like', '%' . $unitSearch . '%');
            });
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
        $currentMonthJobs = JobRecord::whereIn('elevator_unit_id', $pagedUnitIds)
            ->where('job_type', 'ppm')
            ->whereYear('scheduled_date', now()->year)
            ->whereMonth('scheduled_date', now()->month)
            ->with('technician')
            ->orderBy('scheduled_date')
            ->get()
            ->groupBy('elevator_unit_id')
            ->map->first();

        $everScheduledUnitIds = JobRecord::whereIn('elevator_unit_id', $pagedUnitIds)
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
                'hasEverBeenScheduled' => $hasEverBeenScheduled,
            ];
        });

        $unscheduledCount = $unitStatusCounts['unscheduled'];

        // ===== Scheduling history: one row per "Schedule PPM" submit, each can be undone =====
        $batches = JobRecord::where('contract_id', $contract->id)
            ->where('job_type', 'ppm')
            ->whereNotNull('schedule_batch')
            ->groupBy('schedule_batch')
            ->selectRaw('schedule_batch, MIN(created_at) as created_at, MIN(scheduled_date) as first_visit,
                COUNT(*) as visits, COUNT(DISTINCT elevator_unit_id) as units,
                MAX(assigned_technician_id) as technician_id, MAX(assigned_by) as assigned_by,
                SUM(CASE WHEN check_in_time IS NOT NULL OR status IN (\'in_progress\',\'on_hold\',\'completed\') THEN 1 ELSE 0 END) as started')
            ->orderByDesc('created_at')
            ->get();

        $batchPeople = User::whereIn('id', $batches->pluck('technician_id')->merge($batches->pluck('assigned_by'))->filter()->unique())
            ->pluck('name', 'id');

        return view('admin.scheduling.show', compact(
            'contract',
            'visibleJobs',
            'totalJobsCount',
            'isFiltering',
            'unitSummaries',
            'paginatedUnits',
            'unitStatusCounts',
            'unscheduledCount',
            'batches',
            'batchPeople'
        ));
    }

    /**
     * Move one visit to a new date (e.g. the customer asked for a later day).
     * The previous date is kept in job_reschedules so the change can be undone.
     */
    public function reschedule(Request $request, JobRecord $job)
    {
        $job->load('contract');

        if ($job->hasStarted()) {
            return response()->json(['success' => false, 'message' => 'This visit has already been started, so its date can no longer be changed.'], 422);
        }

        $contract = $job->contract;

        $validator = Validator::make($request->all(), [
            'scheduled_date' => [
                'required',
                'date',
                'after_or_equal:' . $contract->contract_start_date->format('Y-m-d'),
                'before_or_equal:' . $contract->contract_end_date->format('Y-m-d'),
            ],
            'reason' => ['nullable', 'string', 'max:255'],
        ], [
            'scheduled_date.after_or_equal'  => 'The new date must be within the contract period.',
            'scheduled_date.before_or_equal' => 'The new date must be within the contract period.',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $newDate = Carbon::parse($request->input('scheduled_date'))->startOfDay();

        if ($newDate->isSameDay($job->scheduled_date)) {
            return response()->json(['success' => false, 'message' => 'The visit is already on that date.'], 422);
        }

        // Never put two visits for the same elevator on the same day
        $clash = JobRecord::where('elevator_unit_id', $job->elevator_unit_id)
            ->where('job_type', $job->job_type)
            ->whereDate('scheduled_date', $newDate)
            ->whereKeyNot($job->id)
            ->exists();

        if ($job->elevator_unit_id && $clash) {
            return response()->json(['success' => false, 'message' => 'This unit already has a visit on ' . $newDate->format('d M Y') . '.'], 422);
        }

        DB::transaction(function () use ($job, $newDate, $request) {
            JobReschedule::create([
                'job_record_id'  => $job->id,
                'old_date'       => $job->scheduled_date,
                'new_date'       => $newDate,
                'reason'         => $request->input('reason'),
                'rescheduled_by' => Auth::id(),
            ]);

            $job->update([
                'scheduled_date' => $newDate,
                'status'         => $job->status === 'overdue' ? 'pending' : $job->status,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Visit moved to ' . $newDate->format('d M Y') . '.',
        ]);
    }

    /** Put a visit back on the date it had before its most recent reschedule. */
    public function undoReschedule(JobRecord $job)
    {
        if ($job->hasStarted()) {
            return response()->json(['success' => false, 'message' => 'This visit has already been started, so its date can no longer be changed.'], 422);
        }

        $last = $job->reschedules()->whereNull('undone_at')->first();

        if (! $last) {
            return response()->json(['success' => false, 'message' => 'There is no date change to undo for this visit.'], 422);
        }

        DB::transaction(function () use ($job, $last) {
            $job->update(['scheduled_date' => $last->old_date]);
            $last->update(['undone_at' => now()]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Visit moved back to ' . $last->old_date->format('d M Y') . '.',
        ]);
    }

    /**
     * Undo one "Schedule PPM" submit: removes every visit it created that hasn't been started.
     * Visits already started/completed are kept, so no work history is lost.
     */
    public function undoBatch(Contract $contract, string $batch)
    {
        $jobs = JobRecord::where('contract_id', $contract->id)->where('schedule_batch', $batch);

        if (! (clone $jobs)->exists()) {
            return response()->json(['success' => false, 'message' => 'That schedule no longer exists.'], 404);
        }

        [$removed, $kept] = DB::transaction(function () use ($contract, $jobs) {
            $removed = $this->notStarted(clone $jobs)->delete();
            $kept = (clone $jobs)->count();
            $this->refreshScheduledFlag($contract);

            return [$removed, $kept];
        });

        $message = "Schedule undone — {$removed} upcoming visit(s) removed.";
        if ($kept > 0) {
            $message .= " {$kept} visit(s) were already started or completed and have been kept.";
        }

        return response()->json(['success' => true, 'message' => $message]);
    }

    /** Remove all not-yet-started PPM visits of one unit, so it can be scheduled again. */
    public function unscheduleUnit(Contract $contract, ElevatorUnit $unit)
    {
        abort_unless((int) $unit->contract_id === (int) $contract->id, 404);

        $jobs = JobRecord::where('contract_id', $contract->id)
            ->where('elevator_unit_id', $unit->id)
            ->where('job_type', 'ppm');

        [$removed, $kept] = DB::transaction(function () use ($contract, $jobs) {
            $removed = $this->notStarted(clone $jobs)->delete();
            $kept = (clone $jobs)->count();
            $this->refreshScheduledFlag($contract);

            return [$removed, $kept];
        });

        $message = "{$removed} upcoming visit(s) removed for {$unit->identification_no}.";
        if ($kept > 0) {
            $message .= " {$kept} started/completed visit(s) were kept, so the unit still counts as scheduled.";
        } else {
            $message .= ' You can now schedule it again.';
        }

        return response()->json(['success' => true, 'message' => $message]);
    }

    // ================= Helpers =================

    /** @return int[] units of this contract that already have at least one PPM visit */
    private function scheduledUnitIds(Contract $contract): array
    {
        return JobRecord::where('contract_id', $contract->id)
            ->where('job_type', 'ppm')
            ->whereNotNull('elevator_unit_id')
            ->distinct()
            ->pluck('elevator_unit_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function notStarted($query)
    {
        return $query->whereNull('check_in_time')
            ->whereNotIn('status', ['in_progress', 'on_hold', 'completed']);
    }

    private function refreshScheduledFlag(Contract $contract): void
    {
        $totalUnits = $contract->elevatorUnits()->count();
        $scheduledUnits = count($this->scheduledUnitIds($contract));

        $data = ['is_scheduled' => $totalUnits > 0 && $scheduledUnits >= $totalUnits];

        if ($scheduledUnits === 0) {
            $data['ppm_start_date'] = null;
        }

        $contract->update($data);
    }

    /**
     * One visit per unit per month, from the start date until the contract ends.
     * A month in which the unit already has a PPM visit is skipped, so running the
     * generator twice can never create duplicate visits for the same elevator.
     */
    private function generateMonthlyPpmJobs(Contract $contract, Carbon $startDate, ?int $technicianId, array $unitIds, string $batch): int
    {
        $endDate = $contract->contract_end_date;

        $existingMonths = JobRecord::whereIn('elevator_unit_id', $unitIds)
            ->where('job_type', 'ppm')
            ->get(['elevator_unit_id', 'scheduled_date'])
            ->map(fn ($j) => $j->elevator_unit_id . '|' . $j->scheduled_date->format('Y-m'))
            ->flip();

        $now = now();
        $rows = [];

        for ($monthOffset = 0; $monthOffset < 600; $monthOffset++) {
            $visitDate = $startDate->copy()->addMonthsNoOverflow($monthOffset);

            if ($visitDate->gt($endDate)) {
                break;
            }

            foreach ($unitIds as $unitId) {
                if ($existingMonths->has($unitId . '|' . $visitDate->format('Y-m'))) {
                    continue;
                }

                $rows[] = [
                    'contract_id'            => $contract->id,
                    'elevator_unit_id'       => $unitId,
                    'job_type'               => 'ppm',
                    'schedule_batch'         => $batch,
                    'scheduled_date'         => $visitDate->toDateString(),
                    'status'                 => 'pending',
                    'assigned_technician_id' => $technicianId,
                    'assigned_by'            => Auth::id(),
                    'description'            => 'Auto-generated monthly PPM visit',
                    'created_at'             => $now,
                    'updated_at'             => $now,
                ];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            JobRecord::insert($chunk);
        }

        return count($rows);
    }
}
