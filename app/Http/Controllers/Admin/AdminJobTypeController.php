<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobRecord;
use App\Models\Route as RouteModel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AdminJobTypeController extends Controller
{
    public function breakdown(Request $request)
    {
        return $this->renderIndex($request, 'breakdown', 'Breakdown', 'admin.job-types.breakdown', 'admin.job-types.breakdown');
    }

    public function repair(Request $request)
    {
        return $this->renderIndex($request, 'repair', 'Repair', 'admin.job-types.repair', 'admin.job-types.repair');
    }

    public function casualJob(Request $request)
    {
        return $this->renderIndex($request, 'casual_job', 'Casual Jobs', 'admin.job-types.casual-job', 'admin.job-types.casual-job');
    }

    public function show(JobRecord $job)
    {
        abort_unless(in_array($job->job_type, ['breakdown', 'repair', 'casual_job']), 404);

        $job->load(['contract:id,project_name,location,route_id,assigned_engineer_id', 'contract.route:id,route_no', 'contract.engineer:id,name', 'unit', 'technician:id,name,employee_id', 'assignedBy:id,name', 'reportedBy:id,name']);

        $labelMap = ['breakdown' => 'Breakdown', 'repair' => 'Repair', 'casual_job' => 'Casual Job'];
        $routeNameMap = ['breakdown' => 'admin.job-types.breakdown', 'repair' => 'admin.job-types.repair', 'casual_job' => 'admin.job-types.casual-job'];

        $label = $labelMap[$job->job_type];
        $backRoute = $routeNameMap[$job->job_type];

        return view('admin.job-types.show', compact('job', 'label', 'backRoute'));
    }

    private function renderIndex(Request $request, string $jobType, string $label, string $viewName, string $routeName)
    {
        $month = (int) $request->get('month', now()->month);
        $year = (int) $request->get('year', now()->year);

        $query = JobRecord::query()
            ->where('job_type', $jobType)
            ->whereYear('scheduled_date', $year)
            ->whereMonth('scheduled_date', $month)
            ->with(['contract:id,project_name,location,route_id,assigned_engineer_id', 'contract.route:id,route_no', 'contract.engineer:id,name', 'unit:id,identification_no', 'technician:id,name,employee_id']);

        if ($request->filled('engineer_id')) {
            $query->whereHas('contract', fn($q) => $q->where('assigned_engineer_id', $request->input('engineer_id')));
        }

        if ($request->filled('technician_id')) {
            $query->where('assigned_technician_id', $request->input('technician_id'));
        }

        if ($request->filled('route_id')) {
            $query->whereHas('contract', fn($q) => $q->where('route_id', $request->input('route_id')));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('contract', fn($cq) => $cq->where('project_name', 'like', "%{$search}%"))
                    ->orWhereHas('unit', fn($uq) => $uq->where('identification_no', 'like', "%{$search}%"))
                    ->orWhere('job_code', 'like', "%{$search}%");
            });
        }

        $summaryQuery = clone $query;
        $summary = $summaryQuery->selectRaw("
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status IN ('pending','overdue') AND scheduled_date < ? THEN 1 ELSE 0 END) as overdue,
                SUM(CASE WHEN status = 'pending' AND scheduled_date >= ? THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'on_hold' THEN 1 ELSE 0 END) as on_hold,
                SUM(cost) as total_cost,
                COUNT(*) as total
            ", [now()->toDateString(), now()->toDateString()])
            ->first();

        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'overdue') {
                $query->whereIn('status', ['pending', 'overdue'])->whereDate('scheduled_date', '<', now()->toDateString());
            } elseif ($status === 'pending') {
                $query->where('status', 'pending')->whereDate('scheduled_date', '>=', now()->toDateString());
            } else {
                $query->where('status', $status);
            }
        }

        $jobs = $query->orderByDesc('scheduled_date')->paginate(30)->withQueryString();

        $engineers = User::where('type', 'Engineer')->select('id', 'name')->get();
        $technicians = User::where('type', 'Technician')->select('id', 'name', 'employee_id')->get();
        $routes = RouteModel::orderBy('route_no')->get();

        $monthOptions = collect(range(1, 12))->mapWithKeys(fn($m) => [$m => Carbon::create()->month($m)->format('F')]);
        $yearOptions = range(now()->year - 2, now()->year + 2);

        return view($viewName, compact(
            'jobs',
            'summary',
            'engineers',
            'technicians',
            'routes',
            'month',
            'year',
            'monthOptions',
            'yearOptions',
            'label',
            'jobType',
            'routeName'
        ));
    }


    public function casualJobCreate()
    {
        $contracts = \App\Models\Contract::where('status', 'active')->orderBy('project_name')->get(['id', 'project_name', 'route_id']);

        return view('admin.job-types.casual-job-create', compact('contracts'));
    }

    /**
     * AJAX: units belonging to the selected contract.
     */
    public function unitsForContract(\App\Models\Contract $contract)
    {
        $units = $contract->elevatorUnits()->select('id', 'identification_no', 'unit_type')->get();

        return response()->json(['units' => $units]);
    }

    /**
     * AJAX: technicians covering the selected contract's route.
     */
    public function techniciansForContract(\App\Models\Contract $contract)
    {
        $technicians = $contract->route
            ? $contract->route->technicians()->map(fn($t) => ['id' => $t->id, 'name' => $t->name])
            : collect();

        return response()->json(['technicians' => $technicians]);
    }

    public function casualJobStore(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'contract_id'           => ['required', 'exists:contracts,id'],
            'elevator_unit_id'      => ['required', 'exists:elevator_units,id'],
            'assigned_technician_id' => ['required', 'exists:users,id'],
            'customer_name'         => ['nullable', 'string', 'max:255'],
            'repair_job_no'         => ['required', 'string', 'max:100'],
            'quotation_date'        => ['required', 'date'],
            'po_status'             => ['required', 'in:Pending,Received,Not Required'],
            'po_received_date'      => ['nullable', 'required_if:po_status,Received', 'date'],
            'po_number'             => ['nullable', 'required_if:po_status,Received', 'string', 'max:100'],
            'quotation_value'       => ['required', 'numeric', 'min:0'],
            'scope_of_work'         => ['required', 'string', 'max:2000'],
            'quotation_attachment'  => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'material_status'       => ['required', 'in:Pending,Ordered,Received,Not Required'],
            'site_status'           => ['required', 'in:Not Started,In Progress,Completed'],
        ], [
            'po_received_date.required_if' => 'Please enter the PO received date.',
            'po_number.required_if'        => 'Please enter the PO number.',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['cost'] = $data['quotation_value']; // keep the existing cost field in sync for reporting

        if ($request->hasFile('quotation_attachment')) {
            $file = $request->file('quotation_attachment');
            $filename = time() . '_' . \Illuminate\Support\Str::random(6) . '.' . $file->getClientOriginalExtension();
            $file->storeAs('casual-job-quotations', $filename, 'public');
            $data['quotation_attachment'] = 'casual-job-quotations/' . $filename;
        }

        $job = JobRecord::create($data + [
            'job_type'       => 'casual_job',
            'job_code'       => JobRecord::generateJobCode('casual_job'),
            'scheduled_date' => now()->toDateString(),
            'status'         => 'pending',
            'assigned_by'    => \Illuminate\Support\Facades\Auth::id(),
        ]);

        return response()->json([
            'success'  => true,
            'message'  => 'Casual Job created successfully! It will now appear in the assigned technician\'s job list.',
            'redirect' => route('admin.job-types.show', $job->id),
        ]);
    }
}
