<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\JobRecord;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class JobController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = JobRecord::with(['site.contract', 'technician:id,name', 'assignedBy:id,name'])
            ->whereIn('job_type', ['ppm', 'repair'])
            ->whereHas('site.contract', fn ($q) => $q->visibleTo($user));

        // Technicians only ever see their own assigned jobs
        if ($user->type === 'Technician') {
            $query->where('assigned_technician_id', $user->id);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('site', fn ($q) => $q->where('site_name', 'like', "%{$search}%"));
        }

        if ($request->filled('job_type')) {
            $query->where('job_type', $request->input('job_type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $jobs = $query->latest('scheduled_date')->paginate(10)->withQueryString();

        return view('admin.jobs.index', compact('jobs'));
    }

    public function create()
    {
        $user = Auth::user();

        // Only sites under contracts this user can see (via their engineer scope)
        $sites = Site::with('contract:id,project_name')
            ->whereHas('contract', fn ($q) => $q->visibleTo($user))
            ->select('id', 'site_name', 'contract_id')
            ->orderBy('site_name')
            ->get();

        // Only technicians this user is allowed to assign work to
        $technicians = $user->assignableTechnicians()->select('id', 'name')->get();

        return view('admin.jobs.create', compact('sites', 'technicians'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $validator = $this->validateJob($request, $user);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $data['assigned_by'] = $user->id;
        $data['status'] = 'pending';

        $job = JobRecord::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Job created successfully!',
            'job'     => $job,
        ]);
    }

    public function edit(JobRecord $job)
    {
        $user = Auth::user();

        $sites = Site::with('contract:id,project_name')
            ->whereHas('contract', fn ($q) => $q->visibleTo($user))
            ->select('id', 'site_name', 'contract_id')
            ->orderBy('site_name')
            ->get();

        $technicians = $user->assignableTechnicians()->select('id', 'name')->get();

        return view('admin.jobs.edit', compact('job', 'sites', 'technicians'));
    }

    public function update(Request $request, JobRecord $job)
    {
        $user = Auth::user();
        $validator = $this->validateJob($request, $user);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $data['status'] = $request->input('status', $job->status);

        if ($data['status'] === 'completed' && $job->status !== 'completed') {
            $data['completed_at'] = now();
        }

        $job->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Job updated successfully!',
            'job'     => $job,
        ]);
    }

    public function destroy(JobRecord $job)
    {
        $job->delete();

        return response()->json([
            'success' => true,
            'message' => 'Job deleted successfully!'
        ]);
    }

    private function validateJob(Request $request, $user)
    {
        // Build the list of technician IDs this user is actually allowed to assign
        $allowedTechnicianIds = $user->assignableTechnicians()->pluck('id')->toArray();

        return Validator::make($request->all(), [
            'site_id'                => [
                'required',
                Rule::exists('sites', 'id')->where(function ($q) use ($user) {
                    $q->whereHas('contract', fn ($cq) => $cq->visibleTo($user));
                }),
            ],
            'job_type'               => ['required', Rule::in(['ppm', 'repair'])],
            'scheduled_date'         => ['required', 'date'],
            'assigned_technician_id' => ['nullable', Rule::in($allowedTechnicianIds)],
            'priority'               => ['nullable', Rule::in(['low', 'medium', 'high', 'critical'])],
            'description'            => ['nullable', 'string', 'max:1000'],
            'status'                 => ['sometimes', Rule::in(['pending', 'in_progress', 'completed', 'overdue', 'cancelled'])],
        ], [
            'assigned_technician_id.in' => 'You can only assign technicians from your own team.',
            'site_id.exists'            => 'You do not have access to this site.',
        ]);
    }
}