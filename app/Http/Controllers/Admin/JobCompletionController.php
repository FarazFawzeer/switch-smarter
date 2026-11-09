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
 * Office "Mark as Completed": the technician did the work and saved it but did not finish
 * the job in the app. The engineer / supervisor of that technician (or an admin / manager)
 * closes it with an end time. The change is kept in timesheet_adjustments and the day's
 * hours are recalculated.
 */
class JobCompletionController extends Controller
{
    public const OPEN_STATUSES = ['in_progress', 'on_hold'];

    /** Admin/Manager: everyone. Engineer: their team. Supervisor: their own technicians. */
    public static function canComplete(User $user, ?User $technician): bool
    {
        if (! $technician) {
            return in_array($user->type, TimesheetAdjustmentController::ADMIN_ROLES, true);
        }

        if (TimesheetAdjustmentController::canAdjust($user, $technician)) {
            return true;
        }

        return $user->type === 'Supervisor' && (int) $technician->supervisor_id === (int) $user->id;
    }

    public function store(Request $request, JobRecord $job)
    {
        $job->load('technician.supervisor', 'unit');
        $user = Auth::user();

        if (! self::canComplete($user, $job->technician)) {
            return response()->json(['success' => false, 'message' => 'Only the technician\'s engineer or supervisor (or an admin) can complete this job.'], 403);
        }

        if (! in_array($job->status, self::OPEN_STATUSES, true)) {
            return response()->json(['success' => false, 'message' => 'Only a started or saved job can be marked as completed.'], 422);
        }

        if (! $job->check_in_time) {
            return response()->json(['success' => false, 'message' => 'This job was never started, so it has no start time.'], 422);
        }

        $isBreakdown = in_array($job->job_type, ['breakdown', 'repair'], true) && ! $job->parent_job_id;

        $validator = Validator::make($request->all(), [
            'check_out_time'    => ['required', 'date'],
            'reason'            => ['required', 'string', 'max:255'],
            'unit_status_after' => [$isBreakdown && ! $job->unit_status_after ? 'required' : 'nullable', 'in:working,shutdown'],
            'shutdown_reason'   => ['nullable', 'required_if:unit_status_after,shutdown', 'string', 'max:500'],
        ], [
            'check_out_time.required'     => 'Please enter the time the work finished.',
            'reason.required'             => 'Please add a short note (e.g. "work done, technician forgot to complete").',
            'unit_status_after.required'  => 'Please say whether the elevator was left Working or Shut down.',
            'shutdown_reason.required_if' => 'Please give the shutdown reason.',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $end = Carbon::parse($request->input('check_out_time'));

        if ($end->lte($job->check_in_time)) {
            return response()->json(['success' => false, 'message' => 'The end time must be after the start time (' . $job->check_in_time->format('d M, h:i A') . ').'], 422);
        }
        if ($end->isFuture()) {
            return response()->json(['success' => false, 'message' => 'The end time cannot be in the future.'], 422);
        }
        if ($job->check_in_time->diffInHours($end) > 24) {
            return response()->json(['success' => false, 'message' => 'A single job cannot run longer than 24 hours.'], 422);
        }

        self::complete($job, $end, $request->input('reason'), $user,
            $request->input('unit_status_after') ?: $job->unit_status_after,
            $request->input('shutdown_reason'));

        return response()->json([
            'success' => true,
            'message' => ($job->job_code ?? 'Job') . ' marked as completed (finished ' . $end->format('d M, h:i A') . ').',
        ]);
    }

    /** Close the job: log it, set the end time, apply the elevator status, recalculate hours. */
    public static function complete(JobRecord $job, Carbon $end, string $reason, User $by, ?string $unitStatus = null, ?string $shutdownReason = null): void
    {
        DB::transaction(function () use ($job, $end, $reason, $by, $unitStatus, $shutdownReason) {
            TimesheetAdjustment::create([
                'job_record_id' => $job->id,
                'old_check_in'  => $job->check_in_time,
                'old_check_out' => $job->check_out_time,
                'new_check_in'  => $job->check_in_time,
                'new_check_out' => $end,
                'reason'        => 'Marked completed by ' . $by->type . ': ' . $reason,
                'adjusted_by'   => $by->id,
            ]);

            $fields = [
                'check_out_time'    => $end,
                'status'            => 'completed',
                'completed_at'      => $end,
                'times_adjusted_at' => now(),
            ];
            if ($unitStatus) {
                $fields['unit_status_after'] = $unitStatus;
                if ($unitStatus === 'shutdown' && $shutdownReason) {
                    $fields['shutdown_reason'] = $shutdownReason;
                }
            }
            $job->update($fields);

            if (in_array($job->job_type, ['breakdown', 'repair'], true) && $job->unit && $unitStatus && ! $job->parent_job_id) {
                $job->unit->applyClosureStatus($unitStatus, $job->shutdown_reason, $job);
            }

            if ($job->technician) {
                foreach (collect([$job->check_in_time, $end])->map->toDateString()->unique() as $date) {
                    AttendanceCalculator::recalculate($job->technician, Carbon::parse($date));
                }
            }
        });
    }
}
