<?php

namespace App\Services;

use App\Models\JobRecord;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Idle / travel time (Rev 8): the time BETWEEN a technician's jobs on one day.
 * Example: job 8:00–10:00, next job 12:00–14:00 → 2h idle/travel (10:00–12:00).
 * Time before the first job and after the last job is not counted, and a technician
 * on approved leave is never counted as working or idle.
 */
class IdleTimeCalculator
{
    public const ADMIN_ROLES = ['Super Admin', 'Admin', 'Manager'];

    /** Technicians this office user looks after (null = everyone). */
    public static function teamTechnicianIds(User $user): ?array
    {
        return match (true) {
            in_array($user->type, self::ADMIN_ROLES, true) => null,
            $user->type === 'Supervisor' => User::where('type', 'Technician')->where('supervisor_id', $user->id)->pluck('id')->all(),
            $user->type === 'Engineer'   => User::where('type', 'Technician')->whereIn('supervisor_id', $user->supervisors()->pluck('id'))->pluck('id')->all(),
            default                      => [],
        };
    }

    /** IDs of technicians on approved leave on this day. */
    public static function onLeaveIds(Carbon $date, ?array $technicianIds = null): Collection
    {
        return LeaveRequest::where('status', 'approved')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->when($technicianIds !== null, fn ($q) => $q->whereIn('technician_id', $technicianIds))
            ->pluck('technician_id')->unique()->values();
    }

    /**
     * One row per technician who worked that day (plus those on leave):
     * jobs, first start, last finish, working minutes, idle/travel minutes, the gaps and
     * what they are doing now (today only).
     *
     * @param  array|null  $technicianIds  limit to these technicians (null = all)
     */
    public static function forDay(Carbon $date, ?array $technicianIds = null): Collection
    {
        $dayStart = $date->copy()->startOfDay();
        $dayEnd = $date->copy()->endOfDay();
        $isToday = $date->isToday();
        $now = now();

        $jobs = JobRecord::whereNotNull('check_in_time')
            ->whereBetween('check_in_time', [$dayStart, $dayEnd])
            ->whereNotNull('assigned_technician_id')
            ->when($technicianIds !== null, fn ($q) => $q->whereIn('assigned_technician_id', $technicianIds))
            ->with(['unit:id,identification_no', 'contract:id,project_name'])
            ->orderBy('check_in_time')
            ->get(['id', 'job_code', 'job_type', 'status', 'assigned_technician_id', 'elevator_unit_id', 'contract_id', 'check_in_time', 'check_out_time']);

        $leaveIds = self::onLeaveIds($date, $technicianIds);
        $people = User::whereIn('id', $jobs->pluck('assigned_technician_id')->merge($leaveIds)->unique())
            ->get(['id', 'name', 'employee_id', 'image_path', 'supervisor_id'])->keyBy('id');

        $rows = collect();

        foreach ($jobs->groupBy('assigned_technician_id') as $techId => $techJobs) {
            $worked = 0;
            $idle = 0;
            $gaps = [];
            $coveredUntil = null;   // latest finish so far (jobs can overlap)
            $running = null;

            foreach ($techJobs as $job) {
                $start = $job->check_in_time;
                // an open job counts until now (today) or until the end of that day (past days)
                $end = $job->check_out_time ?? ($isToday ? $now : $dayEnd);
                if ($end->lt($start)) {
                    $end = $start->copy();
                }
                if (! $job->check_out_time && $job->status === 'in_progress') {
                    $running = $job;
                }

                if ($coveredUntil && $start->gt($coveredUntil)) {
                    $gap = (int) $coveredUntil->diffInMinutes($start);
                    if ($gap > 0) {
                        $idle += $gap;
                        $gaps[] = ['from' => $coveredUntil->copy(), 'to' => $start->copy(), 'minutes' => $gap];
                    }
                }

                // working minutes = time covered by jobs (overlaps counted once)
                $countFrom = $coveredUntil && $coveredUntil->gt($start) ? $coveredUntil : $start;
                if ($end->gt($countFrom)) {
                    $worked += (int) $countFrom->diffInMinutes($end);
                }
                if (! $coveredUntil || $end->gt($coveredUntil)) {
                    $coveredUntil = $end->copy();
                }
            }

            $last = $techJobs->last();
            $now_state = null;
            if ($isToday) {
                $now_state = $running
                    ? ['type' => 'working', 'label' => 'On job' . ($running->job_code ? ' ' . $running->job_code : ''), 'since' => $running->check_in_time]
                    : ['type' => 'idle', 'label' => 'Idle / travelling', 'since' => $coveredUntil];
            }

            $rows->push([
                'technician'   => $people[$techId] ?? null,
                'technician_id' => (int) $techId,
                'on_leave'     => $leaveIds->contains((int) $techId),
                'jobs'         => $techJobs->count(),
                'first_start'  => $techJobs->first()->check_in_time,
                'last_end'     => $running ? null : $coveredUntil,
                'worked_min'   => $worked,
                'idle_min'     => $idle,
                'gaps'         => $gaps,
                'now'          => $now_state,
                'job_list'     => $techJobs,
            ]);
        }

        // on leave and no work recorded: shown, but never as working or idle
        foreach ($leaveIds->diff($jobs->pluck('assigned_technician_id')) as $techId) {
            $rows->push([
                'technician'   => $people[$techId] ?? null,
                'technician_id' => (int) $techId,
                'on_leave'     => true,
                'jobs'         => 0,
                'first_start'  => null,
                'last_end'     => null,
                'worked_min'   => 0,
                'idle_min'     => 0,
                'gaps'         => [],
                'now'          => $isToday ? ['type' => 'leave', 'label' => 'On leave', 'since' => null] : null,
                'job_list'     => collect(),
            ]);
        }

        return $rows->sortBy([['on_leave', 'asc'], ['idle_min', 'desc']])->values();
    }

    public static function format(int $minutes): string
    {
        if ($minutes <= 0) {
            return '0m';
        }

        return (intdiv($minutes, 60) ? intdiv($minutes, 60) . 'h ' : '') . ($minutes % 60) . 'm';
    }
}
