<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobRecord;
use App\Models\TechnicianAttendance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TimesheetApprovalController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $isAdminOverride = $this->isAdminOverride($user);

        $engineers = collect();
        $supervisors = collect();
        $technicians = collect();
        $records = collect();

        $stage = $isAdminOverride ? $request->get('view_as', 'supervisor') : $this->stageForRole($user);

        if ($isAdminOverride) {
            $engineers = User::where('type', 'Engineer')->select('id', 'name')->get();
            $supervisors = User::where('type', 'Supervisor')->select('id', 'name')->get();
            $technicians = User::where('type', 'Technician')->select('id', 'name', 'employee_id')->get();

            $statusMap = ['supervisor' => 'pending', 'engineer' => 'supervisor_approved', 'manager' => 'engineer_approved'];
            $technicianIds = $request->filled('technician_id')
                ? [$request->input('technician_id')]
                : $technicians->pluck('id')->toArray();

            // Admin/Super Admin can override the stage-based status filter entirely, and pick month range
            $statusFilter = $request->filled('status_filter') ? [$request->input('status_filter')] : [$statusMap[$stage]];
            $monthFilter = $request->get('month_filter', 'current');

            $records = $this->fetchRecords($technicianIds, $statusFilter, $monthFilter, true);

            return view('admin.timesheet-approval.index', compact('engineers', 'supervisors', 'technicians', 'records', 'user', 'stage', 'isAdminOverride'));
        }

        if ($stage === 'supervisor') {
            $engineers = collect([$user->engineer])->filter();
            $technicians = $user->technicians()->select('id', 'name', 'employee_id')->get();

            $technicianIds = $this->scopedTechnicianIds($request, $technicians);

            $records = $this->fetchRecords($technicianIds, ['pending'], 'both', false);

        } elseif ($stage === 'engineer') {
            $supervisors = $user->supervisors()->select('id', 'name')->get();

            if ($request->filled('supervisor_id')) {
                // Only supervisors who report to this engineer can be selected
                $selectedSupervisor = $supervisors->firstWhere('id', (int) $request->input('supervisor_id'));
                $technicians = $selectedSupervisor ? $selectedSupervisor->technicians()->select('id', 'name', 'employee_id')->get() : collect();
            } else {
                $technicians = User::where('type', 'Technician')
                    ->whereIn('supervisor_id', $user->supervisors()->pluck('id'))
                    ->select('id', 'name', 'employee_id')
                    ->get();
            }

            $technicianIds = $this->scopedTechnicianIds($request, $technicians);

            $records = $this->fetchRecords($technicianIds, ['supervisor_approved'], 'both', false);

        } else {
            // stageForRole never returns 'manager' for a non-override user anymore,
            // since the only manager-equivalent role (Super Admin) is always an admin override.
            $technicians = collect();
            $records = collect();
        }

        return view('admin.timesheet-approval.index', compact('engineers', 'supervisors', 'technicians', 'records', 'user', 'stage', 'isAdminOverride'));
    }

    public function approve(Request $request, TechnicianAttendance $attendance)
    {
        if (! $this->approveRecord(Auth::user(), $attendance)) {
            return response()->json(['success' => false, 'message' => 'You are not authorized to approve this record at its current stage.'], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Timesheet day approved and moved to the next stage.',
        ]);
    }

    /**
     * Approve several days at once ("Select all" / ticked rows). Each record is checked
     * individually, so anything the user can't approve at its current stage is skipped.
     */
    public function bulkApprove(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids'   => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => ['integer'],
        ], [
            'ids.required' => 'Select at least one timesheet day to approve.',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $user = Auth::user();
        $approved = 0;
        $skipped = 0;

        TechnicianAttendance::with('technician.supervisor')
            ->whereIn('id', $request->input('ids'))
            ->get()
            ->each(function ($attendance) use ($user, &$approved, &$skipped) {
                $this->approveRecord($user, $attendance) ? $approved++ : $skipped++;
            });

        $message = "{$approved} timesheet day(s) approved and moved to the next stage.";
        if ($skipped > 0) {
            $message .= " {$skipped} skipped (not at your approval stage).";
        }

        return response()->json(['success' => $approved > 0, 'message' => $message]);
    }

    /** Moves one record to the next approval stage. Returns false if the user can't act on it. */
    private function approveRecord(User $user, TechnicianAttendance $attendance): bool
    {
        $stage = $this->authorizeAndGetStage($user, $attendance);

        if (! $stage) {
            return false;
        }

        $updates = ['rejection_reason' => null];

        if ($stage === 'supervisor') {
            $updates['approval_status'] = 'supervisor_approved';
            $updates['supervisor_approved_by'] = $user->id;
            $updates['supervisor_approved_at'] = now();
        } elseif ($stage === 'engineer') {
            $updates['approval_status'] = 'engineer_approved';
            $updates['engineer_approved_by'] = $user->id;
            $updates['engineer_approved_at'] = now();
        } elseif ($stage === 'manager') {
            $updates['approval_status'] = 'manager_approved';
            $updates['manager_approved_by'] = $user->id;
            $updates['manager_approved_at'] = now();
        }

        $attendance->update($updates);

        return true;
    }

    public function reject(Request $request, TechnicianAttendance $attendance)
    {
        $user = Auth::user();
        $stage = $this->authorizeAndGetStage($user, $attendance);

        if (! $stage) {
            return response()->json(['success' => false, 'message' => 'You are not authorized to reject this record at its current stage.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'reason' => ['required', 'string', 'max:500'],
        ], [
            'reason.required' => 'Please provide a reason for rejecting this timesheet day.',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $attendance->update([
            'approval_status'  => 'rejected',
            'rejection_reason' => $request->input('reason'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Timesheet day rejected. It has been sent back for correction.',
        ]);
    }

    /**
     * Admin oversight roles: Super Admin acts as the final "Manager" approval
     * authority, since there is no separate Manager role in this system.
     * Admin is included as a developer/testing bypass.
     */
    private function isAdminOverride(User $user): bool
    {
        return in_array($user->type, ['Super Admin', 'Admin']);
    }

    private function stageForRole(User $user): string
    {
        return match ($user->type) {
            'Supervisor' => 'supervisor',
            'Engineer'   => 'engineer',
            default      => 'none', // no other role reaches this branch — Super Admin/Admin are always override
        };
    }

    private function authorizeAndGetStage(User $user, TechnicianAttendance $attendance): ?string
    {
        if ($this->isAdminOverride($user)) {
            return match ($attendance->approval_status) {
                'pending'             => 'supervisor',
                'supervisor_approved' => 'engineer',
                'engineer_approved'   => 'manager',
                default               => null,
            };
        }

        $technician = $attendance->technician;

        if (! $technician) {
            return null;
        }

        if ($user->type === 'Supervisor' && $attendance->approval_status === 'pending') {
            return $technician->supervisor_id === $user->id ? 'supervisor' : null;
        }

        if ($user->type === 'Engineer' && $attendance->approval_status === 'supervisor_approved') {
            $technicianSupervisor = $technician->supervisor;
            return ($technicianSupervisor && $technicianSupervisor->engineer_id === $user->id) ? 'engineer' : null;
        }

        return null;
    }

    /**
     * A technician_id filter is honoured only if that technician is in the
     * caller's own team; otherwise the result is empty rather than leaking records.
     */
    private function scopedTechnicianIds(Request $request, $technicians): array
    {
        $allowedIds = $technicians->pluck('id')->toArray();

        if (! $request->filled('technician_id')) {
            return $allowedIds;
        }

        $requested = (int) $request->input('technician_id');

        return in_array($requested, $allowedIds, true) ? [$requested] : [];
    }

    /**
     * Shared query builder.
     * $monthFilter: 'current', 'previous', or 'both'.
     * $allowStatusOverride: when true (admin view), $statuses is used as an exact filter
     * rather than "this stage or later" — lets the admin browse any single status freely.
     */
    private function fetchRecords(array $technicianIds, array $statuses, string $monthFilter, bool $allowStatusOverride)
    {
        if (empty($technicianIds)) {
            return collect();
        }

        $now = now();

        if ($monthFilter === 'current') {
            $rangeStart = $now->copy()->startOfMonth();
            $rangeEnd = $now->copy()->endOfMonth();
        } elseif ($monthFilter === 'previous') {
            $rangeStart = $now->copy()->subMonthNoOverflow()->startOfMonth();
            $rangeEnd = $now->copy()->subMonthNoOverflow()->endOfMonth();
        } else { // both
            $rangeStart = $now->copy()->subMonthNoOverflow()->startOfMonth();
            $rangeEnd = $now->copy()->endOfMonth();
        }

        $query = TechnicianAttendance::with(['technician:id,name,employee_id,image_path', 'shift:id,name'])
            ->whereIn('technician_id', $technicianIds)
            ->whereBetween('work_date', [$rangeStart, $rangeEnd]);

        if ($allowStatusOverride) {
            $query->whereIn('approval_status', $statuses);
        } else {
            $query->where(function ($q) use ($statuses) {
                $q->whereIn('approval_status', $statuses)
                  ->orWhereIn('approval_status', ['supervisor_approved', 'engineer_approved', 'manager_approved', 'rejected']);
            });
        }

        $records = $query->orderByDesc('work_date')->orderBy('technician_id')->get();

        $this->attachDayJobs($records);

        return $records;
    }

    /**
     * Attaches every job the technician worked on that day (unit, project, start/end,
     * location, hours) to each record, for the expandable "units time" detail row.
     */
    private function attachDayJobs($records): void
    {
        if ($records->isEmpty()) {
            return;
        }

        $jobs = JobRecord::with(['contract:id,project_name', 'unit:id,identification_no,unit_number', 'technician:id,shift_id', 'technician.shift'])
            ->whereIn('assigned_technician_id', $records->pluck('technician_id')->unique())
            ->whereNotNull('check_in_time')
            ->whereBetween('check_in_time', [
                $records->min('work_date')->copy()->startOfDay(),
                $records->max('work_date')->copy()->endOfDay(),
            ])
            ->orderBy('check_in_time')
            ->get()
            ->groupBy(fn ($job) => $job->assigned_technician_id . '|' . $job->check_in_time->toDateString());

        $records->each(function ($record) use ($jobs) {
            $dayJobs = $jobs->get($record->technician_id . '|' . $record->work_date->toDateString(), collect());

            $record->day_jobs = $dayJobs->map(function ($job) {
                $job->hours = $job->workedHours(optional($job->technician)->shift);
                return $job;
            });
        });
    }
}