<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobRecord;
use App\Models\TechnicianAttendance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class JobTrackingController extends Controller
{
    public function index(Request $request)
    {
        $jobs = $this->buildFilteredQuery($request)
            ->orderByDesc('check_in_time')
            ->paginate(20)
            ->withQueryString();

        $jobs->getCollection()->transform(function ($job) {
            $job->attendance = $this->matchingAttendance($job);
            return $job;
        });

        $summary = $this->buildSummary($request);

        $technicians = User::where('type', 'Technician')->select('id', 'name', 'employee_id')->get();

        $chartData = null;

        if ($request->filled('technician_id')) {
            $chartData = $this->buildWorkingHoursChart(
                $request->input('technician_id'),
                $request->input('from'),
                $request->input('to')
            );
        }
        return view('admin.tracking.index', compact('jobs', 'technicians', 'summary', 'chartData'));
    }

    /**
     * Daily Normal Time / Overtime / HOT (day-off) breakdown for one technician,
     * across the selected date range (defaults to the current month).
     */
    private function buildWorkingHoursChart(int $technicianId, ?string $from, ?string $to): array
    {
        $technician = User::with('shift')->find($technicianId);
        $shiftHours = $technician?->shift
            ? \Illuminate\Support\Carbon::parse($technician->shift->start_time)
            ->diffInHours(\Illuminate\Support\Carbon::parse($technician->shift->end_time))
            : 8;

        $rangeStart = $from ? \Illuminate\Support\Carbon::parse($from) : now()->startOfMonth();
        $rangeEnd = $to ? \Illuminate\Support\Carbon::parse($to) : now()->endOfMonth();

        $attendanceByDate = TechnicianAttendance::where('technician_id', $technicianId)
            ->whereBetween('work_date', [$rangeStart, $rangeEnd])
            ->get()
            ->keyBy(fn($a) => $a->work_date->toDateString());

        $labels = [];
        $normalTime = [];
        $overTime = [];
        $hot = [];

        $cursor = $rangeStart->copy();
        while ($cursor->lte($rangeEnd)) {
            $dateKey = $cursor->toDateString();
            $labels[] = $cursor->format('M j');

            $record = $attendanceByDate->get($dateKey);

            if ($record) {
                $ot = (float) ($record->ot_hours ?? 0);
                $normal = max(0, (float) ($record->total_hours ?? 0) - $ot);
                $normalTime[] = round($normal, 1);
                $overTime[] = round($ot, 1);
                $hot[] = 0;
            } else {
                // No attendance record at all this day = off/holiday — shown as a full-height reference bar
                $normalTime[] = 0;
                $overTime[] = 0;
                $hot[] = $shiftHours;
            }

            $cursor->addDay();
        }

        return [
            'technician_name' => $technician?->name ?? 'Technician',
            'labels'          => $labels,
            'normal_time'     => $normalTime,
            'over_time'       => $overTime,
            'hot'             => $hot,
        ];
    }

    public function export(Request $request)
    {
        $jobs = $this->buildFilteredQuery($request)->orderByDesc('check_in_time')->get();

        $filename = 'timesheet_' . now()->format('Y-m-d_His') . '.csv';

        $callback = function () use ($jobs) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['No', 'Technician', 'Project / Unit', 'Date Commenced', 'Check-in', 'Check-out', 'Work Shift', 'Work Type', 'Normal Time', 'Overtime', 'Supervisor Approval', 'Engineer Approval', 'Manager Approval', 'Status']);

            foreach ($jobs as $index => $job) {
                $attendance = $this->matchingAttendance($job);

                fputcsv($handle, [
                    $index + 1,
                    optional($job->technician)->name ?? '—',
                    (optional($job->contract)->project_name ?? '—') . ' / ' . (optional($job->unit)->identification_no ?? '—'),
                    $job->check_in_time?->format('d M Y'),
                    $job->check_in_time?->format('h:i A'),
                    $job->check_out_time?->format('h:i A') ?? 'Not yet',
                    optional(optional($attendance)->shift)->name ?? '—',
                    $this->workTypeLabel($job->job_type),
                    $attendance ? number_format(max(0, ($attendance->total_hours ?? 0) - ($attendance->ot_hours ?? 0)), 2) . 'h' : '—',
                    $attendance && $attendance->ot_hours > 0 ? number_format($attendance->ot_hours, 2) . 'h' : '0h',
                    $attendance?->supervisor_approved_at ? 'Approved' : 'Pending',
                    $attendance?->engineer_approved_at ? 'Approved' : 'Pending',
                    $attendance?->manager_approved_at ? 'Approved' : 'Pending',
                    ucfirst(str_replace('_', ' ', $job->status)),
                ]);
            }

            fclose($handle);
        };

        return Response::stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function show(JobRecord $job)
    {
        $job->load(['technician', 'contract', 'unit', 'assignedBy']);
        $job->attendance = $this->matchingAttendance($job);

        return view('admin.tracking.show', compact('job'));
    }

    /**
     * Shared filtered query — used by both index() and export() so results always match.
     */
    private function buildFilteredQuery(Request $request)
    {
        $query = JobRecord::with(['technician:id,name,employee_id', 'contract:id,project_name,location', 'unit:id,identification_no'])
            ->whereNotNull('check_in_time');

        if ($request->filled('technician_id')) {
            $query->where('assigned_technician_id', $request->input('technician_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('job_type')) {
            $query->where('job_type', $request->input('job_type'));
        }

        if ($request->filled('from')) {
            $query->whereDate('check_in_time', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('check_in_time', '<=', $request->input('to'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('contract', fn($cq) => $cq->where('project_name', 'like', "%{$search}%"))
                    ->orWhereHas('unit', fn($uq) => $uq->where('identification_no', 'like', "%{$search}%"))
                    ->orWhereHas('technician', fn($tq) => $tq->where('name', 'like', "%{$search}%"));
            });
        }

        return $query;
    }

    /**
     * Finds the technician's attendance record for the day this job was started —
     * source of shift, normal/OT hours, and approval chain status.
     */
    private function matchingAttendance(JobRecord $job): ?TechnicianAttendance
    {
        if (! $job->check_in_time || ! $job->assigned_technician_id) {
            return null;
        }

        return TechnicianAttendance::with('shift:id,name')
            ->where('technician_id', $job->assigned_technician_id)
            ->where('work_date', $job->check_in_time->toDateString())
            ->first();
    }

    /**
     * Summary cards — computed from the same filtered set, deduplicated by
     * technician+date so hours aren't double-counted across multiple jobs in one day.
     */
    private function buildSummary(Request $request): array
    {
        $jobs = $this->buildFilteredQuery($request)->get();

        $uniquePairs = $jobs
            ->filter(fn($job) => $job->check_in_time && $job->assigned_technician_id)
            ->unique(fn($job) => $job->assigned_technician_id . '_' . $job->check_in_time->toDateString());

        $attendanceRecords = $uniquePairs->map(fn($job) => $this->matchingAttendance($job))->filter();

        $totalHours = $attendanceRecords->sum('total_hours');
        $overtimeHours = $attendanceRecords->sum('ot_hours');
        $regularHours = max(0, $totalHours - $overtimeHours);

        $totalTechnicians = $jobs->pluck('assigned_technician_id')->unique()->filter()->count();

        $fullyApprovedCount = $attendanceRecords->filter(fn($a) => $a->manager_approved_at)->count();
        $compliance = $attendanceRecords->count() > 0
            ? round(($fullyApprovedCount / $attendanceRecords->count()) * 100, 1)
            : 0;

        return [
            'total_technicians' => $totalTechnicians,
            'total_hours'       => $totalHours,
            'regular_hours'     => $regularHours,
            'overtime_hours'    => $overtimeHours,
            'compliance'        => $compliance,
        ];
    }

    public function workTypeLabel(string $jobType): string
    {
        return match ($jobType) {
            'ppm'        => 'Maintenance',
            'breakdown'  => 'Breakdown',
            'repair'     => 'Repair',
            'casual_job' => 'Casual Job',
            default      => ucfirst($jobType),
        };
    }
}
