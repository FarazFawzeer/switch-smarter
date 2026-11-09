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

        // a helper technician's row → show the main job (the team is listed there)
        if ($job->parent_job_id) {
            return redirect()->route('admin.job-types.show', $job->parent_job_id);
        }

        $job->load(['contract:id,project_name,location,route_id,assigned_engineer_id', 'contract.route:id,route_no', 'contract.engineer:id,name', 'unit', 'technician:id,name,employee_id,supervisor_id', 'technician.supervisor:id,engineer_id', 'assignedBy:id,name', 'reportedBy:id,name', 'helpers.technician:id,name,employee_id']);

        // technicians who could still be added to the team
        $teamIds = $job->helpers->pluck('assigned_technician_id')->push($job->assigned_technician_id)->filter();
        $availableTechnicians = User::where('type', 'Technician')->whereNotIn('id', $teamIds)->orderBy('name')->get(['id', 'name']);

        $labelMap = ['breakdown' => 'Breakdown', 'repair' => 'Repair', 'casual_job' => 'Casual Job'];
        $routeNameMap = ['breakdown' => 'admin.job-types.breakdown', 'repair' => 'admin.job-types.repair', 'casual_job' => 'admin.job-types.casual-job'];

        $label = $labelMap[$job->job_type];
        $backRoute = $routeNameMap[$job->job_type];

        return view('admin.job-types.show', compact('job', 'label', 'backRoute', 'availableTechnicians'));
    }

    /**
     * Add another technician to a breakdown / repair / casual job. They get their own
     * linked row (own start/finish = own timesheet); the job itself is still counted once.
     */
    public function addTechnician(Request $request, JobRecord $job)
    {
        abort_unless(in_array($job->job_type, ['breakdown', 'repair', 'casual_job']), 404);
        $main = $job->parent_job_id ? $job->parent : $job;

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'technician_id' => ['required', \Illuminate\Validation\Rule::exists('users', 'id')->where('type', 'Technician')],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $technicianId = (int) $request->input('technician_id');
        $alreadyOnTeam = (int) $main->assigned_technician_id === $technicianId
            || $main->helpers()->where('assigned_technician_id', $technicianId)->exists();

        if ($alreadyOnTeam) {
            return response()->json(['success' => false, 'message' => 'That technician is already on this job.'], 422);
        }

        $this->createHelper($main, $technicianId);

        return response()->json([
            'success' => true,
            'message' => User::find($technicianId)->name . ' added to ' . $main->job_code . '. It now appears in their job list.',
        ]);
    }

    private function createHelper(JobRecord $main, int $technicianId): JobRecord
    {
        return JobRecord::create([
            'parent_job_id'          => $main->id,
            'contract_id'            => $main->contract_id,
            'elevator_unit_id'       => $main->elevator_unit_id,
            'job_type'               => $main->job_type,
            'scheduled_date'         => $main->scheduled_date,
            'status'                 => 'pending',
            'assigned_technician_id' => $technicianId,
            'assigned_by'            => \Illuminate\Support\Facades\Auth::id(),
            'reported_by'            => $main->reported_by,
            'description'            => $main->description,
            'breakdown_cause'        => $main->breakdown_cause,
            'customer_name'          => $main->customer_name,
            'cost'                   => $main->cost,
            'repair_job_no'          => $main->repair_job_no,
            'scope_of_work'          => $main->scope_of_work,
        ]);
    }

    private function renderIndex(Request $request, string $jobType, string $label, string $viewName, string $routeName)
    {
        $month = (int) $request->get('month', now()->month);
        $year = (int) $request->get('year', now()->year);

        $search = trim((string) $request->input('search', ''));
        $jobNo = trim((string) $request->input('job_no', ''));
        $project = trim((string) $request->input('project', ''));
        $unit = trim((string) $request->input('unit', ''));

        // A text search (job number, unit, project) or a date range looks across ALL months —
        // a job number typed in from an older report must still be found.
        $searchAllMonths = $search !== '' || $jobNo !== '' || $project !== '' || $unit !== ''
            || $request->filled('date_from') || $request->filled('date_to');

        // Main jobs only: when 2+ technicians attend the same job, the extra technicians'
        // rows are linked helpers — listed under the job, but never counted twice.
        $query = JobRecord::query()
            ->primary()
            ->where('job_type', $jobType)
            ->when(! $searchAllMonths, fn ($q) => $q->whereYear('scheduled_date', $year)->whereMonth('scheduled_date', $month))
            ->with([
                'contract:id,project_name,location,route_id,assigned_engineer_id', 'contract.route:id,route_no', 'contract.engineer:id,name',
                'unit:id,identification_no,unit_number,operational_status', 'technician:id,name,employee_id,supervisor_id', 'technician.supervisor:id,engineer_id',
                'helpers:id,parent_job_id,assigned_technician_id,status,breakdown_cause', 'helpers.technician:id,name',
            ]);

        if ($request->filled('engineer_id')) {
            $query->whereHas('contract', fn($q) => $q->where('assigned_engineer_id', $request->input('engineer_id')));
        }

        if ($request->filled('technician_id')) {
            // the lead technician OR any technician who joined the job
            $technicianId = (int) $request->input('technician_id');
            $query->where(fn ($q) => $q->where('assigned_technician_id', $technicianId)
                ->orWhereHas('helpers', fn ($h) => $h->where('assigned_technician_id', $technicianId)));
        }

        // ---- column filters ----
        if ($jobNo !== '') {
            $query->where(fn ($q) => $q->where('job_code', 'like', "%{$jobNo}%")->orWhere('repair_job_no', 'like', "%{$jobNo}%"));
        }
        if ($project !== '') {
            $query->whereHas('contract', fn ($c) => $c->where(fn ($w) => $w->where('project_name', 'like', "%{$project}%")->orWhere('location', 'like', "%{$project}%")));
        }
        if ($unit !== '') {
            $query->whereHas('unit', fn ($u) => $u->where(fn ($w) => $w->where('identification_no', 'like', "%{$unit}%")->orWhere('unit_number', 'like', "%{$unit}%")));
        }
        if ($request->filled('cause')) {
            $cause = $request->input('cause');
            $query->where(fn ($q) => $q->where('breakdown_cause', $cause)
                ->orWhere(fn ($n) => $n->whereNull('breakdown_cause')->whereHas('helpers', fn ($h) => $h->where('breakdown_cause', $cause))));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('scheduled_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('scheduled_date', '<=', $request->input('date_to'));
        }
        if ($request->filled('details')) {
            $details = $request->input('details');
            $query->where(fn ($q) => $q->where('description', 'like', "%{$details}%")->orWhere('work_notes', 'like', "%{$details}%")->orWhere('scope_of_work', 'like', "%{$details}%"));
        }

        if ($request->filled('route_id')) {
            $query->whereHas('contract', fn($q) => $q->where('route_id', $request->input('route_id')));
        }

        if ($searchAllMonths) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('contract', fn($cq) => $cq->where('project_name', 'like', "%{$search}%"))
                    ->orWhereHas('unit', fn($uq) => $uq->where(fn ($w) => $w->where('identification_no', 'like', "%{$search}%")
                        ->orWhere('unit_number', 'like', "%{$search}%")))
                    ->orWhere('job_code', 'like', "%{$search}%")
                    ->orWhere('repair_job_no', 'like', "%{$search}%")
                    ->orWhere('po_number', 'like', "%{$search}%");
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

        $dir = $request->get('dir') === 'asc' ? 'asc' : 'desc';
        $jobs = $query->orderBy('scheduled_date', $request->get('sort') === 'date' ? $dir : 'desc')->orderByDesc('id')->paginate(30)->withQueryString();

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
            'routeName',
            'searchAllMonths'
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
        // the route's own technicians, plus the Breakdown & Repair team (they cover every route)
        $routeTechnicians = $contract->route ? $contract->route->technicians() : collect();
        $breakdownTeam = User::where('type', 'Technician')->where('work_category', 'breakdown')
            ->whereNotIn('id', $routeTechnicians->pluck('id'))->orderBy('name')->get(['id', 'name']);

        $technicians = $routeTechnicians->map(fn($t) => ['id' => $t->id, 'name' => $t->name])
            ->concat($breakdownTeam->map(fn($t) => ['id' => $t->id, 'name' => $t->name . ' (Breakdown team)']))
            ->values();

        return response()->json(['technicians' => $technicians]);
    }

    public function casualJobStore(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'contract_id'           => ['required', 'exists:contracts,id'],
            'elevator_unit_id'      => ['required', 'exists:elevator_units,id'],
            'assigned_technician_id' => ['required', 'exists:users,id'],
            'additional_technician_ids'   => ['nullable', 'array'],
            'additional_technician_ids.*' => ['integer', \Illuminate\Validation\Rule::exists('users', 'id')->where('type', 'Technician')],
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

        $extraTechnicianIds = collect($data['additional_technician_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->reject(fn ($id) => $id === (int) $data['assigned_technician_id'])
            ->unique();
        unset($data['additional_technician_ids']);

        $job = JobRecord::create($data + [
            'job_type'       => 'casual_job',
            'job_code'       => JobRecord::generateJobCode('casual_job'),
            'scheduled_date' => now()->toDateString(),
            'status'         => 'pending',
            'assigned_by'    => \Illuminate\Support\Facades\Auth::id(),
        ]);

        // 2+ technicians: one casual job (counted once), one linked row per extra technician
        foreach ($extraTechnicianIds as $technicianId) {
            $this->createHelper($job, $technicianId);
        }

        return response()->json([
            'success'  => true,
            'message'  => 'Casual Job created successfully! It will now appear in the assigned technician\'s job list.',
            'redirect' => route('admin.job-types.show', $job->id),
        ]);
    }
}
