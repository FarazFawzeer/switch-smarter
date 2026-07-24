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
    /**
     * Admin dashboard: every engineer's contracts, with current-month PPM status.
     */
public function index(Request $request)
{
    $query = Contract::with([
            'engineer:id,name',
            'route:id,route_no',
            'supervisor:id,name',
            'technician:id,name',
            'renewals',
            'ppmJobs' => function ($q) {
                $q->whereYear('scheduled_date', now()->year)
                  ->whereMonth('scheduled_date', now()->month)
                  ->with('technician');
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
                $q->where('status', 'pending')->whereDate('scheduled_date', '<', now()->toDateString());
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

        $contract->load('route.users', 'engineer');

        $supervisors = $contract->route ? $contract->route->supervisors() : collect();
        $technicians = $contract->route ? $contract->route->technicians() : collect();

        return view('admin.scheduling.create', compact('contract', 'supervisors', 'technicians'));
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

        $validator = Validator::make($request->all(), [
            'ppm_start_date' => [
                'required',
                'date',
                'after:' . $contract->contract_start_date->format('Y-m-d'),
                'before_or_equal:' . $contract->contract_end_date->format('Y-m-d'),
            ],
            'assigned_supervisor_id' => ['nullable', Rule::in($routeSupervisorIds)],
            'assigned_technician_id' => ['nullable', Rule::in($routeTechnicianIds)],
        ], [
            'ppm_start_date.after'            => 'PPM start date must be after the contract start date.',
            'ppm_start_date.before_or_equal'  => 'PPM start date cannot be after the contract end date.',
            'assigned_supervisor_id.in'       => 'You can only pick a supervisor assigned to this contract\'s route.',
            'assigned_technician_id.in'       => 'You can only pick a technician assigned to this contract\'s route.',
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

        DB::transaction(function () use ($contract, $ppmStartDate, $supervisorId, $technicianId) {
            $contract->update([
                'ppm_start_date'         => $ppmStartDate,
                'is_scheduled'           => true,
                'assigned_supervisor_id' => $supervisorId,
                'assigned_technician_id' => $technicianId,
            ]);

            $this->generateMonthlyPpmJobs($contract, $ppmStartDate, $technicianId);
        });

        $technicianName = $technicianId ? User::find($technicianId)->name : null;
        $message = $technicianName
            ? "PPM schedule generated and assigned to {$technicianName}!"
            : "PPM schedule generated. No technician was assigned — you can assign one later from Jobs.";

        return response()->json([
            'success'  => true,
            'message'  => $message,
            'redirect' => route('admin.scheduling.show', $contract->id),
        ]);
    }

    public function show(Request $request, Contract $contract)
    {
        $contract->load(['engineer', 'route.users', 'supervisor', 'technician', 'renewals.renewedBy']);

        $totalJobsCount = $contract->ppmJobs()->count();

        $isFiltering = $request->filled('from') || $request->filled('to') || $request->filled('month') || $request->filled('year');

        $query = $contract->ppmJobs()->with('technician')->orderBy('scheduled_date');

        if ($request->filled('month') && $request->filled('year')) {
            $query->whereYear('scheduled_date', $request->input('year'))
                ->whereMonth('scheduled_date', $request->input('month'));
        } elseif ($request->filled('year')) {
            $query->whereYear('scheduled_date', $request->input('year'));
        } elseif ($request->filled('from') || $request->filled('to')) {
            if ($request->filled('from')) {
                $query->where('scheduled_date', '>=', $request->input('from'));
            }
            if ($request->filled('to')) {
                $query->where('scheduled_date', '<=', $request->input('to'));
            }
        } else {
            // Default view per client spec: current month and everything before it
            $query->where('scheduled_date', '<=', now()->endOfMonth());
        }

        $visibleJobs = $query->get();

        // Year range for the dropdown, based on this contract's own schedule
        $yearOptions = collect();
        if ($contract->ppmJobs()->exists()) {
            $minYear = $contract->ppmJobs()->min('scheduled_date');
            $maxYear = $contract->ppmJobs()->max('scheduled_date');
            $yearOptions = collect(range(Carbon::parse($minYear)->year, Carbon::parse($maxYear)->year));
        }

        return view('admin.scheduling.show', compact('contract', 'visibleJobs', 'totalJobsCount', 'isFiltering', 'yearOptions'));
    }

    private function generateMonthlyPpmJobs(Contract $contract, Carbon $startDate, ?int $technicianId): void
    {
        $endDate = $contract->contract_end_date;
        $monthOffset = 0;
        $maxIterations = 600;

        while ($monthOffset < $maxIterations) {
            $visitDate = $startDate->copy()->addMonthsNoOverflow($monthOffset);

            if ($visitDate->gt($endDate)) {
                break;
            }

            JobRecord::create([
                'contract_id'            => $contract->id,
                'job_type'               => 'ppm',
                'scheduled_date'         => $visitDate,
                'status'                 => 'pending',
                'assigned_technician_id' => $technicianId,
                'assigned_by'            => Auth::id(),
                'description'            => 'Auto-generated monthly PPM visit',
            ]);

            $monthOffset++;
        }
    }
}
