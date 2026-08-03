<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobRecord;
use App\Models\User;
use Illuminate\Http\Request;

class JobTrackingController extends Controller
{
    public function index(Request $request)
    {
        $query = JobRecord::with(['technician:id,name', 'contract:id,project_name,location', 'unit:id,identification_no'])
            ->where('job_type', 'ppm')
            ->whereNotNull('check_in_time'); // only show visits that have actually started

        if ($request->filled('technician_id')) {
            $query->where('assigned_technician_id', $request->input('technician_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('from')) {
            $query->whereDate('scheduled_date', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('scheduled_date', '<=', $request->input('to'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('contract', fn ($cq) => $cq->where('project_name', 'like', "%{$search}%"))
                  ->orWhereHas('unit', fn ($uq) => $uq->where('identification_no', 'like', "%{$search}%"));
            });
        }

        $jobs = $query->orderByDesc('scheduled_date')->paginate(20)->withQueryString();

        $technicians = User::where('type', 'Technician')->select('id', 'name')->get();

        return view('admin.tracking.index', compact('jobs', 'technicians'));
    }

    public function show(JobRecord $job)
    {
        $job->load(['technician', 'contract', 'unit', 'assignedBy']);

        return view('admin.tracking.show', compact('job'));
    }
}