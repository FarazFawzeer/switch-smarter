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

    /** Statuses the Jobs page can be filtered by (same four as the dashboard tiles). */
    public const LIST_STATUSES = ['pending', 'in_progress', 'completed', 'on_hold'];

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
            ->withoutOrphanPpm()
            ->whereBetween('scheduled_date', [now()->startOfMonth(), now()->endOfMonth()])
            ->get();

        $pendingCount = $thisMonthJobs->where('status', 'pending')->count();
        $inProgressCount = $thisMonthJobs->where('status', 'in_progress')->count();
        $completedCount = $thisMonthJobs->where('status', 'completed')->count();
        $onHoldCount = $thisMonthJobs->where('status', 'on_hold')->count();

        $todaysSchedule = JobRecord::with(['contract:id,project_name', 'unit:id,identification_no,unit_number'])
            ->where('assigned_technician_id', $technicianId)
            ->withoutOrphanPpm()
            ->whereDate('scheduled_date', now()->toDateString())
            ->orderByRaw("FIELD(status, 'in_progress', 'on_hold', 'pending', 'completed')")
            ->orderBy('id')
            ->get()
            // one entry per unit + job type, even if old data still holds a duplicate visit
            ->unique(fn ($job) => ($job->elevator_unit_id ?? 'job' . $job->id) . '|' . $job->job_type)
            ->values();

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

        $query = JobRecord::with(['contract:id,project_name,location', 'unit:id,identification_no,unit_number,unit_type'])
            ->where('assigned_technician_id', $technicianId)
            ->withoutOrphanPpm()
            ->whereBetween('scheduled_date', [now()->startOfMonth(), now()->endOfMonth()]);

        // Status tab, opened from the Job Overview tiles on the dashboard
        $status = $request->input('status');
        if (in_array($status, self::LIST_STATUSES, true)) {
            $query->where('status', $status);
        }

        if ($request->filled('unit_search')) {
            $search = trim($request->input('unit_search'));
            $query->where(function ($q) use ($search) {
                $q->whereHas('unit', fn ($u) => $this->matchUnitSearch($u, $search))
                    ->orWhere('job_code', 'like', "%{$search}%");
            });
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
            ->whereHas('unit', fn ($q) => $this->matchUnitSearch($q, $search))
            ->orderBy('scheduled_date')
            ->limit(30)
            ->get()
            ->unique(fn ($job) => $job->elevator_unit_id . '|' . $job->job_type)
            ->take(10)
            ->values();

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

        if ($type === 'ppm') {
            $results = $this->ppmSearchResults($technicianId, $search);
        } elseif ($type === 'casual_job') {
            $jobs = JobRecord::with(['contract:id,project_name', 'unit:id,identification_no,unit_number'])
                ->where('assigned_technician_id', $technicianId)
                ->where('job_type', $type)
                ->whereIn('status', ['pending', 'in_progress', 'on_hold'])
                ->whereHas('unit', fn ($q) => $this->matchUnitSearch($q, $search))
                ->limit(10)
                ->get();

            $results = $jobs->map(fn ($job) => [
                'mode'    => 'existing',
                'job_id'  => $job->id,
                'unit_id' => $this->unitLabel($job->unit),
                'project' => optional($job->contract)->project_name ?? '—',
                'url'     => route('technician.activities.show', $job->id),
            ]);

            // Open casual jobs held by another technician on a matching unit → join as a team
            $mineUnitIds = $jobs->pluck('elevator_unit_id')->all();
            $others = JobRecord::primary()
                ->with(['contract:id,project_name', 'unit:id,identification_no,unit_number', 'technician:id,name'])
                ->where('job_type', 'casual_job')
                ->whereIn('status', ['pending', 'in_progress', 'on_hold'])
                ->where('assigned_technician_id', '!=', $technicianId)
                ->whereNotIn('elevator_unit_id', $mineUnitIds)
                ->whereDoesntHave('helpers', fn ($h) => $h->where('assigned_technician_id', $technicianId))
                ->whereHas('unit', fn ($q) => $this->matchUnitSearch($q, $search))
                ->limit(10)
                ->get();

            $results = $results->concat($others->map(fn ($job) => [
                'mode'       => 'new',
                'unit_id_pk' => $job->elevator_unit_id,
                'unit_id'    => $job->job_code . ' · ' . $this->unitLabel($job->unit),
                'project'    => optional($job->contract)->project_name ?? '—',
                'note'       => 'Assigned to ' . optional($job->technician)->name . ' — tap to JOIN (counted once)',
            ]))->values();
        } else {
            // Breakdown / Repair: first the technician's own jobs of this type that match by
            // JOB NUMBER (e.g. BD-2026-00003) or unit — open ones to continue, and an exact
            // job-number match even if completed — then any unit to report a new one.
            $existing = JobRecord::with(['contract:id,project_name', 'unit:id,identification_no,unit_number'])
                ->where('assigned_technician_id', $technicianId)
                ->where('job_type', $type)
                ->where(function ($q) use ($search) {
                    $q->where('job_code', 'like', "%{$search}%")
                        ->orWhereHas('parent', fn ($p) => $p->where('job_code', 'like', "%{$search}%"))
                        ->orWhere(fn ($open) => $open->whereIn('status', ['pending', 'in_progress', 'on_hold'])
                            ->whereHas('unit', fn ($u) => $this->matchUnitSearch($u, $search)));
                })
                ->orderByDesc('id')
                ->limit(10)
                ->get();

            $statusLabels = ['pending' => 'Not started', 'in_progress' => 'Ongoing', 'on_hold' => 'Saved draft', 'completed' => 'Completed'];

            $existing->load('parent:id,job_code');

            $results = $existing->map(fn ($job) => [
                'mode'    => 'existing',
                'job_id'  => $job->id,
                'unit_id' => ($job->displayCode() ? $job->displayCode() . ' · ' : '') . $this->unitLabel($job->unit),
                'project' => optional($job->contract)->project_name ?? '—',
                'note'    => ($statusLabels[$job->status] ?? ucfirst($job->status)) . ' — reported ' . $job->scheduled_date->format('d M Y'),
                'url'     => route('technician.activities.show', $job->id),
            ]);

            // ANY unit can break down — not only units on an active / scheduled contract.
            // (Units on an expired contract used to be hidden, so the search found nothing.)
            $units = ElevatorUnit::with('contract:id,project_name,location,status')
                ->where(fn ($q) => $this->matchUnitSearch($q, $search))
                ->orderByRaw('identification_no = ? DESC, unit_number = ? DESC', [$search, $search])
                ->orderBy('identification_no')
                ->limit(15)
                ->get();

            $label = $type === 'breakdown' ? 'breakdown' : 'repair';

            $results = $results->concat($units->map(function ($unit) use ($type, $label) {
                $open = $this->openTeamJob($unit->id, $type);
                $mine = $open && (int) $open->assigned_technician_id === (int) Auth::id();

                $note = match (true) {
                    $open && ! $mine => 'Open ' . $open->job_code . ' by ' . optional($open->technician)->name . ' — tap to JOIN (counted once)',
                    $open && $mine   => 'Your open ' . $open->job_code . ' — tap to continue',
                    default          => 'Tap to report a new ' . $label,
                };
                if ($unit->isShutdown()) {
                    $note .= ' · unit is SHUT DOWN';
                }
                if (optional($unit->contract)->status && $unit->contract->status !== 'active') {
                    $note .= ' · contract ' . $unit->contract->status;
                }

                return [
                    'mode'        => 'new',
                    'unit_id_pk'  => $unit->id,
                    'unit_id'     => $this->unitLabel($unit),
                    'project'     => optional($unit->contract)->project_name ?? '—',
                    'note'        => $note,
                    'contract_id' => $unit->contract_id,
                ];
            }))
                // a unit whose open job is already listed above (as "your job") isn't repeated
                ->unique(fn ($r) => $r['mode'] === 'new' ? 'unit-' . $r['unit_id_pk'] : 'job-' . $r['job_id'])
                ->reject(fn ($r) => $r['mode'] === 'new' && $existing->contains(fn ($j) => (int) $j->elevator_unit_id === (int) $r['unit_id_pk']
                    && in_array($j->status, ['pending', 'in_progress', 'on_hold'], true)))
                ->values();
        }

        return response()->json(['results' => $results]);
    }

    public function activityCreate(Request $request, string $type)
    {
        abort_unless(in_array($type, ['ppm', 'repair', 'breakdown', 'casual_job']), 404);

        $validator = Validator::make($request->all(), [
            'elevator_unit_id' => ['required', 'exists:elevator_units,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $unit = ElevatorUnit::findOrFail($request->input('elevator_unit_id'));

        // A PPM can only be started on a unit with no PPM visit this month yet;
        // otherwise the technician opens the existing visit instead of duplicating it.
        if ($type === 'ppm') {
            $alreadyThisMonth = JobRecord::where('elevator_unit_id', $unit->id)
                ->where('job_type', 'ppm')
                ->whereBetween('scheduled_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
                ->exists();

            if ($alreadyThisMonth) {
                return response()->json([
                    'success' => false,
                    'errors'  => ['elevator_unit_id' => ['This unit already has a PPM visit this month.']],
                ], 422);
            }
        }

        // Team jobs: if this unit already has an OPEN breakdown / repair / casual job, a second
        // technician JOINS it instead of opening a duplicate. They get their own row (own times,
        // own timesheet) linked to the main job, and the job is still counted only once.
        $parent = null;
        if ($type !== 'ppm') {
            $parent = $this->openTeamJob($unit->id, $type);

            if ($parent) {
                $mine = JobRecord::where('assigned_technician_id', Auth::id())
                    ->where(fn ($q) => $q->whereKey($parent->id)->orWhere('parent_job_id', $parent->id))
                    ->first();

                if ($mine) {
                    // already on this job — just open it
                    return response()->json(['success' => true, 'redirect' => route('technician.activities.show', $mine->id)]);
                }
            }
        }

        $job = JobRecord::create([
            'parent_job_id'          => $parent?->id,
            'contract_id'            => $unit->contract_id,
            'elevator_unit_id'       => $unit->id,
            'job_type'               => $type,
            'job_code'               => $parent ? null : JobRecord::generateJobCode($type),
            'scheduled_date'         => $parent ? $parent->scheduled_date : now()->toDateString(),
            'status'                 => 'pending',
            'assigned_technician_id' => Auth::id(),
            'assigned_by'            => Auth::id(),
            'reported_by'            => $parent ? $parent->reported_by : Auth::id(),
            'breakdown_cause'        => $parent?->breakdown_cause,
            'description'            => $parent?->description,
            'customer_name'          => $parent?->customer_name,
            'cost'                   => $parent?->cost,
        ]);

        return response()->json([
            'success'  => true,
            'message'  => $parent ? 'You joined ' . $parent->job_code . ' — your own times will be recorded.' : null,
            'redirect' => route('technician.activities.show', $job->id),
        ]);
    }

    /** The open (not completed) main job of this type on the unit, if any. */
    private function openTeamJob(int $unitId, string $type): ?JobRecord
    {
        return JobRecord::primary()
            ->where('elevator_unit_id', $unitId)
            ->where('job_type', $type)
            ->whereIn('status', ['pending', 'in_progress', 'on_hold'])
            ->with('technician:id,name')
            ->latest('id')
            ->first();
    }

    public function activityShow(JobRecord $job)
    {
        abort_unless($job->assigned_technician_id === Auth::id(), 403);

        $job->load(['contract', 'unit', 'parent.technician:id,name']);

        // everyone working on this job (main job + helpers), for the "Team" line
        $mainId = $job->parent_job_id ?? $job->id;
        $team = JobRecord::with('technician:id,name')
            ->where(fn ($q) => $q->whereKey($mainId)->orWhere('parent_job_id', $mainId))
            ->get()
            ->pluck('technician.name')
            ->filter()
            ->unique()
            ->values();

        return view('technician.activities.show', compact('job', 'team'));
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
            'start_photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
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

        if (! $job->check_in_time) {
            return response()->json(['success' => false, 'errors' => ['general' => ['You must start this activity before saving a draft.']]], 422);
        }

        if ($job->status === 'completed') {
            return response()->json(['success' => false, 'errors' => ['general' => ['This activity is already completed and can no longer be edited.']]], 422);
        }

        // A breakdown can't be saved without its main details (cause + description) —
        // a half-empty breakdown record is useless to the office.
        if ($job->job_type === 'breakdown') {
            $validator = Validator::make($request->all(), [
                'breakdown_cause' => [$job->parent_job_id ? 'nullable' : 'required', 'string', 'in:' . implode(',', self::BREAKDOWN_CAUSES)],
                'description'     => ['required', 'string', 'max:1000'],
            ], [
                'breakdown_cause.required' => 'Select the breakdown cause before saving.',
                'description.required'     => 'Describe the issue before saving.',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }
        }

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

        // (an engineer may already have filled in a forgotten check-out time — that is not "completed")
        if ($job->status === 'completed') {
            return response()->json(['success' => false, 'errors' => ['general' => ['This activity is already completed.']]], 422);
        }

        $rules = array_merge($this->activityValidationRules($job->job_type, (bool) $job->parent_job_id), [
            'completion_photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'signature'        => ['required', 'string', 'regex:#^data:image/png;base64,[A-Za-z0-9+/=]+$#', 'max:2000000'],
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

        $signatureBytes = $this->decodePngSignature($request->input('signature'));

        if ($signatureBytes === null) {
            return response()->json(['success' => false, 'errors' => ['signature' => ['The signature is not a valid image. Please sign again.']]], 422);
        }

        $now = now();

        $file = $request->file('completion_photo');
        $photoFilename = time() . '_' . Str::random(6) . '.' . $file->getClientOriginalExtension();
        $file->storeAs('job-completions', $photoFilename, 'public');

        $signaturePath = $this->saveSignature($signatureBytes);

        // keep a check-out time an engineer already corrected manually
        $checkOut = $job->times_adjusted_at && $job->check_out_time ? $job->check_out_time : $now;

        $data = $this->extractActivityFields($request, $job->job_type) + [
            'check_out_time'   => $checkOut,
            'check_out_lat'    => $request->input('latitude'),
            'check_out_lng'    => $request->input('longitude'),
            'completion_photo' => 'job-completions/' . $photoFilename,
            'signature_path'   => $signaturePath,
            'status'           => 'completed',
            'completed_at'     => $now,
        ];

        // a helper may leave the cause blank — it follows the main job
        if ($job->job_type === 'breakdown' && empty($data['breakdown_cause']) && $job->parent) {
            $data['breakdown_cause'] = $job->parent->breakdown_cause;
        }

        $job->update($data);

        // Breakdown / repair closure: elevator left Working or Shutdown
        if (in_array($job->job_type, ['breakdown', 'repair'], true) && $job->unit && $job->unit_status_after) {
            $job->unit->applyClosureStatus($job->unit_status_after, $job->shutdown_reason, $job);
        }

        $this->recordAttendanceCheckOut(Auth::user(), $checkOut);

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

    /**
     * PPM unit search for the technician app:
     *  - ONE result per unit: this month's visit (or the latest overdue one). Future
     *    monthly visits are not listed, so a unit no longer shows up several times.
     *  - plus units with no PPM visit this month at all ("Not scheduled"), so the
     *    technician can still start a PPM on them.
     */
    private function ppmSearchResults(int $technicianId, string $search)
    {
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        $existing = JobRecord::with(['contract:id,project_name', 'unit:id,identification_no,unit_number'])
            ->where('assigned_technician_id', $technicianId)
            ->where('job_type', 'ppm')
            ->whereNotNull('elevator_unit_id')
            ->whereIn('status', ['pending', 'overdue', 'in_progress', 'on_hold'])
            ->where('scheduled_date', '<=', $monthEnd)
            ->whereHas('unit', fn ($q) => $this->matchUnitSearch($q, $search))
            ->orderByDesc('scheduled_date')
            ->limit(50)
            ->get()
            ->unique('elevator_unit_id')
            ->take(10)
            ->values();

        $results = $existing->map(fn ($job) => [
            'mode'    => 'existing',
            'job_id'  => $job->id,
            'unit_id' => $this->unitLabel($job->unit),
            'project' => optional($job->contract)->project_name ?? '—',
            'note'    => $job->scheduled_date->lt(now()->startOfMonth())
                ? 'Overdue — scheduled ' . $job->scheduled_date->format('d M')
                : 'Scheduled ' . $job->scheduled_date->format('d M'),
            'url'     => route('technician.activities.show', $job->id),
        ]);

        $unscheduled = ElevatorUnit::with('contract:id,project_name')
            ->where(fn ($q) => $this->matchUnitSearch($q, $search))
            ->whereNotIn('id', $existing->pluck('elevator_unit_id'))
            ->whereHas('contract', fn ($q) => $q->where('status', 'active'))
            ->whereDoesntHave('ppmJobs', fn ($q) => $q->whereBetween('scheduled_date', [$monthStart, $monthEnd]))
            ->orderBy('identification_no')
            ->limit(10)
            ->get();

        return $results->concat($unscheduled->map(fn ($unit) => [
            'mode'        => 'new',
            'unit_id_pk'  => $unit->id,
            'unit_id'     => $this->unitLabel($unit),
            'project'     => optional($unit->contract)->project_name ?? '—',
            'note'        => 'Not scheduled this month — tap to start a PPM',
            'contract_id' => $unit->contract_id,
        ]))->values();
    }

    private function matchUnitSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('identification_no', 'like', "%{$search}%")
                ->orWhere('unit_number', 'like', "%{$search}%");
        });
    }

    private function unitLabel(?ElevatorUnit $unit): string
    {
        if (! $unit) {
            return '—';
        }

        return $unit->identification_no . ($unit->unit_number ? ' (' . $unit->unit_number . ')' : '');
    }

    private function activityValidationRules(string $type, bool $isHelper = false): array
    {
        $common = [
            'description'  => ['required', 'string', 'max:1000'],
            'work_notes'   => ['nullable', 'string', 'max:1000'],
            'parts_used'   => ['nullable', 'array'],
            'parts_used.*' => ['nullable', 'string', 'max:255'],
        ];

        if ($type === 'breakdown') {
            // a helper technician may leave the cause blank — it follows the main job
            $common['breakdown_cause'] = [$isHelper ? 'nullable' : 'required', 'string', 'in:' . implode(',', self::BREAKDOWN_CAUSES)];
            $common['immediate_action_taken'] = ['nullable', 'string', 'max:1000'];
            // Closure: elevator left Working or Shutdown — a shutdown needs a reason
            $common['unit_status_after'] = ['required', 'in:working,shutdown'];
            $common['shutdown_reason'] = ['nullable', 'required_if:unit_status_after,shutdown', 'string', 'max:500'];
        }

        if ($type === 'repair') {
            $common['warranty_contract'] = ['nullable', 'string', 'max:100'];
            $common['unit_status_after'] = ['nullable', 'in:working,shutdown'];
            $common['shutdown_reason'] = ['nullable', 'required_if:unit_status_after,shutdown', 'string', 'max:500'];
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

        if (in_array($type, ['breakdown', 'repair'], true)) {
            $status = $request->input('unit_status_after');
            $fields['unit_status_after'] = in_array($status, ['working', 'shutdown'], true) ? $status : null;
            $fields['shutdown_reason'] = $status === 'shutdown' ? trim((string) $request->input('shutdown_reason')) : null;
        }

        if ($type === 'casual_job') {
            $fields['customer_name'] = $request->input('customer_name');
            $fields['cost'] = $request->input('cost');
        }

        return $fields;
    }

    /**
     * Decode a canvas data URL and confirm the bytes really are a PNG image.
     * Returns null for anything else, so arbitrary content (e.g. HTML) is never stored.
     */
    private function decodePngSignature(string $dataUrl): ?string
    {
        $data = base64_decode(preg_replace('#^data:image/png;base64,#', '', $dataUrl), true);

        if ($data === false || $data === '') {
            return null;
        }

        $info = @getimagesizefromstring($data);

        return ($info && $info[2] === IMAGETYPE_PNG) ? $data : null;
    }

    private function saveSignature(string $pngBytes): string
    {
        $filename = 'signatures/' . time() . '_' . Str::random(6) . '.png';
        \Storage::disk('public')->put($filename, $pngBytes);

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
            // shift end for the shift the day STARTED in (correct for overnight shifts)
            $shiftEnd = $technician->shift->endFor($attendance->check_in_time ?? $time);
            if ($time->gt($shiftEnd)) {
                $otHours = round($shiftEnd->diffInMinutes($time) / 60, 2);
            }
        }

        $attendance->ot_hours = $otHours;
        $attendance->save();
    }

    public function jobsPage(Request $request)
    {
        $status = in_array($request->input('status'), self::LIST_STATUSES, true) ? $request->input('status') : '';

        // Same month + same rules as the dashboard tiles, so the numbers match
        $statusCounts = JobRecord::where('assigned_technician_id', Auth::id())
            ->withoutOrphanPpm()
            ->whereBetween('scheduled_date', [now()->startOfMonth(), now()->endOfMonth()])
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('technician.jobs.browse', compact('status', 'statusCounts'));
    }

    public function schedulePage()
    {
        $technicianId = Auth::id();

        $jobs = JobRecord::with(['contract:id,project_name', 'unit:id,identification_no,unit_number'])
            ->where('assigned_technician_id', $technicianId)
            ->withoutOrphanPpm()
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