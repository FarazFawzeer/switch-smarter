<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Models\JobRecord;
use App\Models\TechnicianAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TechnicianJobController extends Controller
{
    // Constructor removed — role restriction now handled via route middleware in web.php

public function index()
{
    $technicianId = Auth::id();

    // Quick stats — always current month, loaded once with the page
    $thisMonthJobs = JobRecord::where('assigned_technician_id', $technicianId)
        ->whereBetween('scheduled_date', [now()->startOfMonth(), now()->endOfMonth()])
        ->get();

    $completedCount = $thisMonthJobs->where('status', 'completed')->count();
    $overdueCount = $thisMonthJobs->filter(fn ($job) =>
        in_array($job->status, ['pending', 'overdue']) && $job->scheduled_date->isPast()
    )->count();
    $pendingCount = $thisMonthJobs->count() - $completedCount - $overdueCount;

    $todayAttendance = TechnicianAttendance::where('technician_id', $technicianId)
        ->where('work_date', now()->toDateString())
        ->first();

    return view('technician.jobs.index', compact('completedCount', 'overdueCount', 'pendingCount', 'todayAttendance'));
}

/**
 * AJAX endpoint: returns just the job list HTML fragment for a given period/search,
 * so switching tabs doesn't reload the whole dashboard.
 */
public function list(Request $request)
{
    $technicianId = Auth::id();

    $rangeStart = now()->subMonthNoOverflow()->startOfMonth();
    $rangeEnd = now()->addMonthNoOverflow()->endOfMonth();

    $query = JobRecord::with(['contract:id,project_name,location', 'unit:id,identification_no,unit_type'])
        ->where('assigned_technician_id', $technicianId)
        ->whereBetween('scheduled_date', [$rangeStart, $rangeEnd]);

    if ($request->filled('unit_search')) {
        $search = $request->input('unit_search');
        $query->whereHas('unit', function ($q) use ($search) {
            $q->where('identification_no', 'like', "%{$search}%");
        });
    }

    $period = $request->get('period', 'current');
    if ($period === 'previous') {
        $query->whereBetween('scheduled_date', [$rangeStart, now()->subMonthNoOverflow()->endOfMonth()]);
    } elseif ($period === 'upcoming') {
        $query->whereBetween('scheduled_date', [now()->addMonthNoOverflow()->startOfMonth(), $rangeEnd]);
    } else {
        $period = 'current';
        $query->whereBetween('scheduled_date', [now()->startOfMonth(), now()->endOfMonth()]);
    }

    $paginatedJobs = $query->orderBy('scheduled_date')->paginate(8)->withQueryString();

    return view('technician.jobs._job-list', compact('paginatedJobs', 'period'));
}

    public function show(JobRecord $job)
    {
        abort_unless($job->assigned_technician_id === Auth::id(), 403);

        $job->load(['contract', 'unit']);

        return view('technician.jobs.show', compact('job'));
    }

    public function checkIn(Request $request, JobRecord $job)
    {
        abort_unless($job->assigned_technician_id === Auth::id(), 403);

        if ($job->check_in_time) {
            return response()->json(['success' => false, 'errors' => ['general' => ['This job has already been started.']]], 422);
        }

        $validator = Validator::make($request->all(), [
            'latitude'  => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ], [
            'latitude.required'  => 'Location access is required to start work. Please allow location access and try again.',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $now = now();

        $job->update([
            'check_in_time' => $now,
            'check_in_lat'  => $request->input('latitude'),
            'check_in_lng'  => $request->input('longitude'),
            'status'        => 'in_progress',
        ]);

        $this->recordAttendanceCheckIn(Auth::user(), $now);

        return response()->json([
            'success'  => true,
            'message'  => 'Work started. Timer is running.',
            'redirect' => route('technician.jobs.show', $job->id),
        ]);
    }

    public function complete(Request $request, JobRecord $job)
    {
        abort_unless($job->assigned_technician_id === Auth::id(), 403);

        if (! $job->check_in_time) {
            return response()->json(['success' => false, 'errors' => ['general' => ['You must start work before completing it.']]], 422);
        }

        if ($job->check_out_time) {
            return response()->json(['success' => false, 'errors' => ['general' => ['This job is already completed.']]], 422);
        }

        $validator = Validator::make($request->all(), [
            'latitude'           => ['required', 'numeric', 'between:-90,90'],
            'longitude'          => ['required', 'numeric', 'between:-180,180'],
            'completion_photo'   => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'work_notes'         => ['nullable', 'string', 'max:1000'],
        ], [
            'completion_photo.required' => 'Please upload a photo of the completed work before finishing.',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $now = now();

        $file = $request->file('completion_photo');
        $filename = time() . '_' . Str::random(6) . '.' . $file->getClientOriginalExtension();
        $file->storeAs('job-completions', $filename, 'public');

        $job->update([
            'check_out_time'    => $now,
            'check_out_lat'     => $request->input('latitude'),
            'check_out_lng'     => $request->input('longitude'),
            'completion_photo'  => 'job-completions/' . $filename,
            'work_notes'        => $request->input('work_notes'),
            'status'            => 'completed',
            'completed_at'      => $now,
        ]);

        $this->recordAttendanceCheckOut(Auth::user(), $now);

        return response()->json([
            'success'  => true,
            'message'  => 'Job completed successfully!',
            'redirect' => route('technician.jobs.index'),
        ]);
    }

    public function timesheet()
    {
        $records = TechnicianAttendance::where('technician_id', Auth::id())
            ->whereMonth('work_date', now()->month)
            ->whereYear('work_date', now()->year)
            ->orderByDesc('work_date')
            ->get();

        $totalHours = $records->sum('total_hours');
        $totalOt = $records->sum('ot_hours');

        return view('technician.timesheet', compact('records', 'totalHours', 'totalOt'));
    }

    private function recordAttendanceCheckIn($technician, Carbon $time): void
    {
        $attendance = TechnicianAttendance::firstOrCreate(
            ['technician_id' => $technician->id, 'work_date' => $time->toDateString()],
            ['shift_id' => $technician->shift_id]
        );

        if (! $attendance->check_in_time) {
            $attendance->update(['check_in_time' => $time]);
        }
    }

    private function recordAttendanceCheckOut($technician, Carbon $time): void
    {
        $attendance = TechnicianAttendance::firstOrCreate(
            ['technician_id' => $technician->id, 'work_date' => $time->toDateString()],
            ['shift_id' => $technician->shift_id]
        );

        $attendance->check_out_time = $time;

        if ($attendance->check_in_time) {
            $totalHours = $attendance->check_in_time->diffInMinutes($time) / 60;
            $attendance->total_hours = round($totalHours, 2);
        }

        $otHours = 0;
        if ($technician->shift) {
            $shiftEnd = Carbon::parse($time->toDateString() . ' ' . $technician->shift->end_time);

            if ($technician->shift->end_time < $technician->shift->start_time) {
                $shiftEnd->addDay();
            }

            if ($time->gt($shiftEnd)) {
                $otHours = round($shiftEnd->diffInMinutes($time) / 60, 2);
            }
        }

        $attendance->ot_hours = $otHours;
        $attendance->save();
    }
}
