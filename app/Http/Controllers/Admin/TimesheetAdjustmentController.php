<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobRecord;
use App\Models\TimesheetAdjustment;
use App\Models\User;
use App\Services\AttendanceCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Manual Timesheet Adjustment: an engineer corrects a technician's check-in / check-out
 * time (wrong time entered, or the technician forgot to log out). Every change is kept
 * in timesheet_adjustments, and the day's attendance hours are recalculated.
 */
class TimesheetAdjustmentController extends Controller
{
    public const ADMIN_ROLES = ['Super Admin', 'Admin', 'Manager'];

    /** Can this user correct this technician's times? Engineers: only their own team. */
    public static function canAdjust(User $user, ?User $technician): bool
    {
        if (! $technician) {
            return false;
        }

        if (in_array($user->type, self::ADMIN_ROLES, true)) {
            return true;
        }

        return $user->type === 'Engineer'
            && optional($technician->supervisor)->engineer_id === $user->id;
    }

    /** IDs of technicians the user may adjust (null = everyone). */
    public static function adjustableTechnicianIds(User $user): ?array
    {
        if (in_array($user->type, self::ADMIN_ROLES, true)) {
            return null;
        }

        if ($user->type !== 'Engineer') {
            return [];
        }

        return User::where('type', 'Technician')
            ->whereIn('supervisor_id', $user->supervisors()->pluck('id'))
            ->pluck('id')
            ->all();
    }

    public function store(Request $request, JobRecord $job)
    {
        $job->load('technician.supervisor', 'technician.shift');

        if (! self::canAdjust(Auth::user(), $job->technician)) {
            return response()->json(['success' => false, 'message' => 'Only the technician\'s engineer (or an admin) can adjust this timesheet.'], 403);
        }

        if (! $job->check_in_time) {
            return response()->json(['success' => false, 'message' => 'This job was never started, so there is no time to correct.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'check_in_time'  => ['required', 'date'],
            'check_out_time' => ['nullable', 'date', 'after:check_in_time'],
            'reason'         => ['required', 'string', 'max:255'],
        ], [
            'check_out_time.after' => 'Check-out must be after check-in.',
            'reason.required'      => 'Please give a reason for the correction (e.g. "forgot to log out").',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $newIn = Carbon::parse($request->input('check_in_time'));
        $newOut = $request->filled('check_out_time') ? Carbon::parse($request->input('check_out_time')) : null;

        if ($newOut && $newIn->diffInHours($newOut) > 24) {
            return response()->json(['success' => false, 'message' => 'A single job cannot run longer than 24 hours.'], 422);
        }

        if ($newIn->isFuture() || ($newOut && $newOut->isFuture())) {
            return response()->json(['success' => false, 'message' => 'Times cannot be in the future.'], 422);
        }

        $oldIn = $job->check_in_time;
        $oldOut = $job->check_out_time;

        if ($oldIn->equalTo($newIn) && (($oldOut && $newOut && $oldOut->equalTo($newOut)) || (! $oldOut && ! $newOut))) {
            return response()->json(['success' => false, 'message' => 'Nothing changed.'], 422);
        }

        // Rev 8: entering an end time on a started / saved job completes it
        $completes = $newOut && in_array($job->status, JobCompletionController::OPEN_STATUSES, true);

        DB::transaction(function () use ($job, $oldIn, $oldOut, $newIn, $newOut, $request, $completes) {
            TimesheetAdjustment::create([
                'job_record_id' => $job->id,
                'old_check_in'  => $oldIn,
                'old_check_out' => $oldOut,
                'new_check_in'  => $newIn,
                'new_check_out' => $newOut,
                'reason'        => $request->input('reason'),
                'adjusted_by'   => Auth::id(),
            ]);

            $job->update([
                'check_in_time'     => $newIn,
                'check_out_time'    => $newOut,
                'times_adjusted_at' => now(),
            ] + ($completes ? ['status' => 'completed', 'completed_at' => $newOut] : []));

            if ($completes && $job->unit && $job->unit_status_after && ! $job->parent_job_id
                && in_array($job->job_type, ['breakdown', 'repair'], true)) {
                $job->unit->applyClosureStatus($job->unit_status_after, $job->shutdown_reason, $job);
            }

            // the job may have moved to another day — recalculate both days
            foreach (collect([$oldIn, $newIn])->map->toDateString()->unique() as $date) {
                AttendanceCalculator::recalculate($job->technician, Carbon::parse($date));
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Timesheet corrected: ' . $newIn->format('d M, h:i A') . ' → ' . ($newOut?->format('h:i A') ?? 'no check-out') . '. Hours recalculated.'
                . ($completes ? ' The job is now Completed.' : ''),
        ]);
    }
}
