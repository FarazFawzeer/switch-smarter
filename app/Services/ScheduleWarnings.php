<?php

namespace App\Services;

use App\Models\Holiday;
use App\Models\LeaveRequest;
use Illuminate\Support\Collection;

/**
 * Warnings for scheduled visits: the date is a holiday (weekly day off or public holiday),
 * or the assigned technician has approved leave that day. Dates are NOT moved automatically —
 * the scheduler sees the warning and reschedules by hand if needed.
 */
class ScheduleWarnings
{
    /**
     * @param  Collection  $jobs  JobRecords with scheduled_date + assigned_technician_id
     * @return array<int, string[]>  job id => list of warnings
     */
    public static function forJobs(Collection $jobs): array
    {
        if ($jobs->isEmpty()) {
            return [];
        }

        $leaves = self::approvedLeaves(
            $jobs->pluck('assigned_technician_id')->filter()->unique()->all(),
            $jobs->min('scheduled_date'),
            $jobs->max('scheduled_date')
        );

        $warnings = [];
        foreach ($jobs as $job) {
            $list = [];
            if ($job->scheduled_date && ($holiday = Holiday::reasonFor($job->scheduled_date))) {
                $list[] = 'Holiday: ' . $holiday;
            }
            if ($job->assigned_technician_id && $job->scheduled_date && self::onLeave($leaves, $job->assigned_technician_id, $job->scheduled_date)) {
                $list[] = 'Technician on approved leave';
            }
            if ($list) {
                $warnings[$job->id] = $list;
            }
        }

        return $warnings;
    }

    /** Approved leave for these technicians overlapping the date range, grouped by technician. */
    public static function approvedLeaves(array $technicianIds, $from, $to): Collection
    {
        if (! $technicianIds || ! $from || ! $to) {
            return collect();
        }

        return LeaveRequest::where('status', 'approved')
            ->whereIn('technician_id', $technicianIds)
            ->whereDate('start_date', '<=', $to)
            ->whereDate('end_date', '>=', $from)
            ->get(['technician_id', 'start_date', 'end_date'])
            ->groupBy('technician_id');
    }

    public static function onLeave(Collection $leaves, int $technicianId, $date): bool
    {
        return ($leaves->get($technicianId) ?? collect())
            ->contains(fn ($l) => $date->between($l->start_date->copy()->startOfDay(), $l->end_date->copy()->endOfDay()));
    }
}
