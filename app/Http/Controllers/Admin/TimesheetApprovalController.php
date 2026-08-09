<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

            $technicianIds = $request->filled('technician_id')
                ? [$request->input('technician_id')]
                : $technicians->pluck('id')->toArray();

            $records = $this->fetchRecords($technicianIds, ['pending'], 'both', false);

        } elseif ($stage === 'engineer') {
            $supervisors = $user->supervisors()->select('id', 'name')->get();

            if ($request->filled('supervisor_id')) {
                $selectedSupervisor = User::find($request->input('supervisor_id'));
                $technicians = $selectedSupervisor ? $selectedSupervisor->technicians()->select('id', 'name', 'employee_id')->get() : collect();
            } else {
                $technicians = User::where('type', 'Technician')
                    ->whereIn('supervisor_id', $user->supervisors()->pluck('id'))
                    ->select('id', 'name', 'employee_id')
                    ->get();
            }

            $technicianIds = $request->filled('technician_id')
                ? [$request->input('technician_id')]
                : $technicians->pluck('id')->toArray();

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
        $user = Auth::user();
        $stage = $this->authorizeAndGetStage($user, $attendance);

        if (! $stage) {
            return response()->json(['success' => false, 'message' => 'You are not authorized to approve this record at its current stage.'], 403);
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

        return response()->json([
            'success' => true,
            'message' => 'Timesheet day approved and moved to the next stage.',
        ]);
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

        return $query->orderByDesc('work_date')->orderBy('technician_id')->get();
    }
}