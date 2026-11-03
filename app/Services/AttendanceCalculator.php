<?php

namespace App\Services;

use App\Models\JobRecord;
use App\Models\TechnicianAttendance;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Rebuilds a technician's attendance for one day from their jobs that day:
 * check-in = first job start, check-out = last job end, total = the span between,
 * overtime = time after the shift end. Used after a manual timesheet adjustment.
 */
class AttendanceCalculator
{
    public static function recalculate(User $technician, Carbon $date): ?TechnicianAttendance
    {
        $day = $date->copy()->startOfDay();

        $jobs = JobRecord::where('assigned_technician_id', $technician->id)
            ->whereNotNull('check_in_time')
            ->whereBetween('check_in_time', [$day, $day->copy()->endOfDay()])
            ->get(['check_in_time', 'check_out_time']);

        $attendance = TechnicianAttendance::where('technician_id', $technician->id)
            ->whereDate('work_date', $day)
            ->first();

        if ($jobs->isEmpty()) {
            // no work left on this day (e.g. a job was moved to another date)
            if ($attendance) {
                $attendance->update(['check_in_time' => null, 'check_out_time' => null, 'total_hours' => 0, 'ot_hours' => 0]);
            }
            return $attendance;
        }

        $attendance ??= TechnicianAttendance::create([
            'technician_id' => $technician->id,
            'work_date'     => $day->toDateString(),
            'shift_id'      => $technician->shift_id,
        ]);

        $checkIn = $jobs->min('check_in_time');
        $checkOut = $jobs->pluck('check_out_time')->filter()->max();

        $totalHours = $checkOut ? round($checkIn->diffInMinutes($checkOut) / 60, 2) : null;

        $otHours = 0;
        $shift = $technician->shift;
        if ($checkOut && $shift) {
            $shiftEnd = Carbon::parse($day->toDateString() . ' ' . $shift->end_time);
            if ($shift->end_time < $shift->start_time) {
                $shiftEnd->addDay();
            }
            if ($checkOut->gt($shiftEnd)) {
                $otHours = round($shiftEnd->diffInMinutes($checkOut) / 60, 2);
            }
        }

        $attendance->update([
            'check_in_time'  => $checkIn,
            'check_out_time' => $checkOut,
            'total_hours'    => $totalHours,
            'ot_hours'       => $otHours,
        ]);

        return $attendance;
    }
}
