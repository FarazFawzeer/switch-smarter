<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Models\ElevatorUnit;
use App\Models\JobRecord;
use App\Models\TechnicianAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TechnicianJobController extends Controller
{
    private const COMPLETION_RADIUS_METERS = 200;

    public const BREAKDOWN_CAUSES = [
        'Maintenance adjustment',
        'Client issue',
        'Power supply issue',
        'Controller issue',
        'Brake issue',
        'Main contactor issue',
        'Car door issue',
        'Landing door issue',
        'Safety line issue',
        'Main Drive issue',
        'Others',
    ];

    public function index()
    {
        $technicianId = Auth::id();

        $thisMonthJobs = JobRecord::where('assigned_technician_id', $technicianId)
            ->whereBetween('scheduled_date', [now()->startOfMonth(), now()->endOfMonth()])
            ->get();

        $pendingCount = $thisMonthJobs->where('status', 'pending')->count();
        $inProgressCount = $thisMonthJobs->where('status', 'in_progress')->count();
        $completedCount = $thisMonthJobs->where('status', 'completed')->count();
        $onHoldCount = $thisMonthJobs->where('status', 'on_hold')->count();

        $todaysSchedule = JobRecord::with(['contract:id,project_name', 'unit:id,identification_no'])
            ->where('assigned_technician_id', $technicianId)
            ->whereDate('scheduled_date', now()->toDateString())
            ->orderBy('scheduled_date')
            ->get();

        $todayAttendance = TechnicianAttendance::where('technician_id', $technicianId)
            ->where('work_date', now()->toDateString())
            ->first();

        return view('technician.jobs.index', compact(
            'pendingCount', 'inProgressCount', 'completedCount', 'onHoldCount', 'todaysSchedule', 'todayAttendance'
        ));
    }

    public function list(Request $request)
    {
        $technicianId = Auth::id();

        $query = JobRecord::with(['contract:id,project_name,location', 'unit:id,identification_no,unit_type'])
            ->where('assigned_technician_id', $technicianId)
            ->whereBetween('scheduled_date', [now()->startOfMonth(), now()->endOfMonth()]);

        if ($request->filled('unit_search')) {
            $search = $request->input('unit_search');
            $query->whereHas('unit', fn ($q) => $q->where('identification_no', 'like', "%{$search}%"));
        }

        $paginatedJobs = $query->orderBy('scheduled_date')->paginate(8)->withQueryString();

        return view('technician.jobs._job-list', compact('paginatedJobs'));
    }

    public function findUnit()
    {
        return view('technician.jobs.find');
    }

    public function searchUnit(Request $request)
    {
        $technicianId = Auth::id();
        $search = trim($request->input('unit_search', ''));

        if ($search === '') {
            return response()->json(['results' => []]);
        }

        $jobs = JobRecord::with(['contract:id,project_name', 'unit:id,identification_no,unit_type'])
            ->where('assigned_technician_id', $technicianId)
            ->whereBetween('scheduled_date', [now()->startOfMonth(), now()->endOfMonth()])
            ->whereHas('unit', fn ($q) => $q->where('identification_no', 'like', "%{$search}%"))
            ->orderBy('scheduled_date')
            ->limit(10)
            ->get();

        $results = $jobs->map(fn ($job) => [
            'id'      => $job->id,
            'unit_id' => optional($job->unit)->identification_no ?? '—',
            'project' => optional($job->contract)->project_name ?? 'Unknown project',
            'status'  => $job->status,
            'url'     => route('technician.jobs.show', $job->id),
        ]);

        return response()->json(['results' => $results]);
    }

    public function selectActivity()
    {
        return view('technician.activities.select');
    }

    public function activitySearchForm(string $type)
    {
        abort_unless(in_array($type, ['ppm', 'repair', 'breakdown', 'casual_job']), 404);

        return view('technician.activities.search', compact('type'));
    }

    public function activitySearchUnit(Request $request, string $type)
    {
        $technicianId = Auth::id();
        $search = trim($request->input('unit_search', ''));

        if ($search === '') {
            return response()->json(['results' => []]);
        }

        if ($type === 'ppm' || $type === 'casual_job') {
            $jobs = JobRecord::with(['contract:id,project_name', 'unit:id,identification_no'])
                ->where('assigned_technician_id', $technicianId)
                ->where('job_type', $type)
                ->whereIn('status', ['pending', 'in_progress', 'on_hold'])
                ->whereHas('unit', fn ($q) => $q->where('identification_no', 'like', "%{$search}%"))
                ->limit(10)
                ->get();

            $results = $jobs->map(fn ($job) => [
                'mode'    => 'existing',
                'job_id'  => $job->id,
                'unit_id' => optional($job->unit)->identification_no ?? '—',
                'project' => optional($job->contract)->project_name ?? '—',
                'url'     => route('technician.activities.show', $job->id),
            ]);
        } else {
            $units = ElevatorUnit::with('contract:id,project_name,location')
                ->where('identification_no', 'like', "%{$search}%")
                ->whereHas('contract', fn ($q) => $q->where('status', 'active'))
                ->limit(10)
                ->get();

            $results = $units->map(fn ($unit) => [
                'mode'        => 'new',
                'unit_id_pk'  => $unit->id,
                'unit_id'     => $unit->identification_no,
                'project'     => optional($unit->contract)->project_name ?? '—',
                'contract_id' => $unit->contract_id,
            ]);
        }

        return response()->json(['results' => $results]);
    }

    public function activityCreate(Request $request, string $type)
    {
        abort_unless(in_array($type, ['repair', 'breakdown', 'casual_job']), 404);

        $validator = Validator::make($request->all(), [
            'elevator_unit_id' => ['required', 'exists:elevator_units,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $unit = ElevatorUnit::findOrFail($request->input('elevator_unit_id'));

        $job = JobRecord::create([
            'contract_id'            => $unit->contract_id,
            'elevator_unit_id'       => $unit->id,
            'job_type'               => $type,
            'job_code'               => JobRecord::generateJobCode($type),
            'scheduled_date'         => now()->toDateString(),
            'status'                 => 'pending',
            'assigned_technician_id' => Auth::id(),
            'assigned_by'            => Auth::id(),
            'reported_by'            => Auth::id(),
        ]);

        return response()->json([
            'success'  => true,
            'redirect' => route('technician.activities.show', $job->id),
        ]);
    }

    public function activityShow(JobRecord $job)
    {
        abort_unless($job->assigned_technician_id === Auth::id(), 403);

        $job->load(['contract', 'unit']);

        return view('technician.activities.show', compact('job'));
    }

    /**
     * Start — required for ALL job types (PPM, Breakdown, Repair, Casual Job):
     * a start photo, GPS location, and the auto-captured start time.
     * Only after this succeeds does the rest of the form become available.
     */
    public function activityStart(Request $request, JobRecord $job)
    {
        abort_unless($job->assigned_technician_id === Auth::id(), 403);

        if ($job->check_in_time) {
            return response()->json(['success' => false, 'errors' => ['general' => ['This activity has already been started.']]], 422);
        }

        $validator = Validator::make($request->all(), [
            'start_photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'latitude'    => ['required', 'numeric', 'between:-90,90'],
            'longitude'   => ['required', 'numeric', 'between:-180,180'],
        ], [
            'start_photo.required' => 'Please take a photo before starting this activity.',
            'latitude.required'    => 'Location access is required to start this activity.',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $file = $request->file('start_photo');
        $filename = time() . '_' . Str::random(6) . '.' . $file->getClientOriginalExtension();
        $file->storeAs('job-starts', $filename, 'public');

        $now = now();

        $job->update([
            'check_in_time' => $now,
            'check_in_lat'  => $request->input('latitude'),
            'check_in_lng'  => $request->input('longitude'),
            'start_photo'   => 'job-starts/' . $filename,
            'status'        => 'in_progress',
        ]);

        $this->recordAttendanceCheckIn(Auth::user(), $now);

        return response()->json(['success' => true, 'message' => 'Activity started.']);
    }

    public function activitySaveDraft(Request $request, JobRecord $job)
    {
        abort_unless($job->assigned_technician_id === Auth::id(), 403);

        $job->update($this->extractActivityFields($request, $job->job_type) + ['status' => 'on_hold']);

        return response()->json(['success' => true, 'message' => 'Saved as draft. You can complete this later.']);
    }

    /**
     * Complete — required for ALL job types: a completion photo, signature, GPS location,
     * and the auto-captured end time. The 200m geofence (against the start location) applies
     * to every job type now, since every job type now captures a start location.
     */
    public function activityComplete(Request $request, JobRecord $job)
    {
        abort_unless($job->assigned_technician_id === Auth::id(), 403);

        if (! $job->check_in_time) {
            return response()->json(['success' => false, 'errors' => ['general' => ['You must start this activity before completing it.']]], 422);
        }

        if ($job->check_out_time) {
            return response()->json(['success' => false, 'errors' => ['general' => ['This activity is already completed.']]], 422);
        }

        $rules = array_merge($this->activityValidationRules($job->job_type), [
            'completion_photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'signature'        => ['required', 'string'],
            'latitude'         => ['required', 'numeric', 'between:-90,90'],
            'longitude'        => ['required', 'numeric', 'between:-180,180'],
        ]);

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Geofence check — now applies to every job type, since every job type captures a start location
        if ($job->check_in_lat && $job->check_in_lng) {
            $distance = $this->distanceInMeters(
                (float) $job->check_in_lat,
                (float) $job->check_in_lng,
                (float) $request->input('latitude'),
                (float) $request->input('longitude')
            );

            if ($distance > self::COMPLETION_RADIUS_METERS) {
                return response()->json([
                    'success' => false,
                    'errors'  => ['general' => [
                        'You are ' . round($distance) . 'm away from where you started this activity. '
                        . 'You must be within ' . self::COMPLETION_RADIUS_METERS . 'm to complete it.'
                    ]]
                ], 422);
            }
        }

        $now = now();

        $file = $request->file('completion_photo');
        $photoFilename = time() . '_' . Str::random(6) . '.' . $file->getClientOriginalExtension();
        $file->storeAs('job-completions', $photoFilename, 'public');

        $signaturePath = $this->saveSignature($request->input('signature'));

        $data = $this->extractActivityFields($request, $job->job_type) + [
            'check_out_time'   => $now,
            'check_out_lat'    => $request->input('latitude'),
            'check_out_lng'    => $request->input('longitude'),
            'completion_photo' => 'job-completions/' . $photoFilename,
            'signature_path'   => $signaturePath,
            'status'           => 'completed',
            'completed_at'     => $now,
        ];

        $job->update($data);

        $this->recordAttendanceCheckOut(Auth::user(), $now);

        return response()->json([
            'success'  => true,
            'message'  => 'Activity completed successfully!',
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

    // ================= Helpers =================

    private function activityValidationRules(string $type): array
    {
        $common = [
            'description'  => ['required', 'string', 'max:1000'],
            'work_notes'   => ['nullable', 'string', 'max:1000'],
            'parts_used'   => ['nullable', 'array'],
            'parts_used.*' => ['nullable', 'string', 'max:255'],
        ];

        if ($type === 'breakdown') {
            $common['breakdown_cause'] = ['required', 'string', 'in:' . implode(',', self::BREAKDOWN_CAUSES)];
            $common['immediate_action_taken'] = ['nullable', 'string', 'max:1000'];
        }

        if ($type === 'repair') {
            $common['warranty_contract'] = ['nullable', 'string', 'max:100'];
        }

        if ($type === 'casual_job') {
            $common['customer_name'] = ['nullable', 'string', 'max:255'];
            $common['cost'] = ['nullable', 'numeric', 'min:0'];
        }

        return $common;
    }

    private function extractActivityFields(Request $request, string $type): array
    {
        $fields = [
            'description' => $request->input('description'),
            'work_notes'  => $request->input('work_notes'),
            'parts_used'  => array_values(array_filter($request->input('parts_used', []))),
        ];

        if ($type === 'breakdown') {
            $fields['breakdown_cause'] = $request->input('breakdown_cause');
            $fields['immediate_action_taken'] = $request->input('immediate_action_taken');
        }

        if ($type === 'repair') {
            $fields['warranty_contract'] = $request->input('warranty_contract');
        }

        if ($type === 'casual_job') {
            $fields['customer_name'] = $request->input('customer_name');
            $fields['cost'] = $request->input('cost');
        }

        return $fields;
    }

    private function saveSignature(?string $base64): ?string
    {
        if (! $base64) {
            return null;
        }

        $data = preg_replace('#^data:image/\w+;base64,#i', '', $base64);
        $data = base64_decode($data);

        $filename = 'signatures/' . time() . '_' . Str::random(6) . '.png';
        \Storage::disk('public')->put($filename, $data);

        return $filename;
    }

    private function distanceInMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
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
            $attendance->total_hours = round($attendance->check_in_time->diffInMinutes($time) / 60, 2);
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

    public function jobsPage()
    {
        return view('technician.jobs.browse');
    }

    public function schedulePage()
    {
        $technicianId = Auth::id();

        $jobs = JobRecord::with(['contract:id,project_name', 'unit:id,identification_no'])
            ->where('assigned_technician_id', $technicianId)
            ->whereBetween('scheduled_date', [now()->startOfMonth(), now()->endOfMonth()])
            ->orderBy('scheduled_date')
            ->get()
            ->groupBy(fn ($job) => $job->scheduled_date->format('d M Y'));

        return view('technician.schedule', compact('jobs'));
    }

    public function profilePage()
    {
        $user = Auth::user()->load('shift', 'supervisor');

        return view('technician.profile', compact('user'));
    }
}