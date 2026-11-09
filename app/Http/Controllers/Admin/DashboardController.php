<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Technician\TechnicianJobController;
use App\Models\Contract;
use App\Models\ElevatorUnit;
use App\Models\JobRecord;
use App\Models\TechnicianAttendance;
use App\Models\User;
use App\Services\IdleTimeCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Operations dashboard (Revision 7). Counts use MAIN jobs only, so a breakdown attended
 * by several technicians is counted once. Engineers/Supervisors see their own contracts.
 */
class DashboardController extends Controller
{
    private const ADMIN_ROLES = ['Manager', 'Super Admin', 'Admin'];

    public function index(Request $request)
    {
        $user = Auth::user();
        $contractIds = $this->visibleContractIds($user);

        [$monthStart, $monthEnd] = $this->selectedMonth($request);
        $prevStart = $monthStart->copy()->subMonthNoOverflow()->startOfMonth();
        $prevEnd = $prevStart->copy()->endOfMonth();

        $jobs = fn () => JobRecord::primary()->whereIn('contract_id', $contractIds);
        $units = fn () => ElevatorUnit::whereIn('contract_id', $contractIds);

        // ---------- KPI cards ----------
        // Only real projects: active AND holding at least one unit. Empty projects (left over from
        // a deleted bulk upload) used to be counted, so "Total Projects" could exceed "Total Units".
        $projects = Contract::whereIn('id', $contractIds)->where('status', 'active')->whereHas('elevatorUnits');
        $kpi = [
            'projects'          => (clone $projects)->count(),
            'projects_new'      => (clone $projects)->whereBetween('created_at', [$monthStart, $monthEnd])->count(),
            'units'             => $units()->count(),
            'units_new'         => $units()->whereBetween('created_at', [$monthStart, $monthEnd])->count(),
            // Breakdown: recorded during the selected month
            'breakdowns'        => $jobs()->where('job_type', 'breakdown')->whereBetween('scheduled_date', [$monthStart, $monthEnd])->count(),
            'breakdowns_prev'   => $jobs()->where('job_type', 'breakdown')->whereBetween('scheduled_date', [$prevStart, $prevEnd])->count(),
            // Shutdown: elevators shut down right now and still open
            'shutdown'          => $units()->where('operational_status', 'shutdown')->count(),
            // Casual jobs: pending / in progress / awaiting completion (open now)
            'casual_open'       => $jobs()->where('job_type', 'casual_job')->whereIn('status', ['pending', 'in_progress', 'on_hold'])->count(),
            // Repairs: completed during the selected month
            'repairs_done'      => $jobs()->where('job_type', 'repair')->where('status', 'completed')->whereBetween('completed_at', [$monthStart, $monthEnd])->count(),
            'repairs_done_prev' => $jobs()->where('job_type', 'repair')->where('status', 'completed')->whereBetween('completed_at', [$prevStart, $prevEnd])->count(),
        ];

        // ---------- Total units by equipment type ----------
        $unitTypes = $units()->selectRaw("COALESCE(NULLIF(unit_type, ''), 'Other') as type, COUNT(*) as total")
            ->groupBy('type')->orderByDesc('total')->pluck('total', 'type');

        // ---------- Units by engineer ----------
        $unitsByEngineer = ElevatorUnit::query()
            ->join('contracts', 'contracts.id', '=', 'elevator_units.contract_id')
            ->leftJoin('users', 'users.id', '=', 'contracts.assigned_engineer_id')
            ->whereIn('contracts.id', $contractIds)
            ->where('contracts.status', 'active')
            ->groupBy('users.id', 'users.name', 'users.image_path')
            ->selectRaw("users.id, COALESCE(users.name, 'Unassigned') as name, users.image_path, COUNT(*) as total")
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        // ---------- Breakdown summary (per day × cause) ----------
        $breakdownSummary = $this->breakdownSummary($jobs, $monthStart, $monthEnd);

        // ---------- Calendar: days with breakdowns ----------
        $calendar = $this->calendarWeeks($monthStart, $breakdownSummary['per_day']);

        $live = $this->liveActivity($contractIds, $user);

        $greeting = match (true) {
            now()->hour < 12 => 'Good Morning',
            now()->hour < 17 => 'Good Afternoon',
            default          => 'Good Evening',
        };

        $monthOptions = collect(range(0, 11))->map(fn ($i) => now()->startOfMonth()->subMonthsNoOverflow($i))
            ->mapWithKeys(fn ($d) => [$d->format('Y-m') => $d->format('F Y')]);

        return view('index', compact(
            'kpi', 'unitTypes', 'unitsByEngineer', 'breakdownSummary', 'calendar', 'live',
            'greeting', 'monthStart', 'monthOptions', 'user'
        ));
    }

    /** Live Maintenance Activity block (refreshed every minute by the page). */
    public function live()
    {
        $live = $this->liveActivity($this->visibleContractIds(Auth::user()), Auth::user());

        return view('dashboard._live', compact('live'));
    }

    /** Daily Breakdown Details: every breakdown on one day, with its cause. */
    public function breakdownsOnDay(Request $request)
    {
        $request->validate(['date' => ['required', 'date']]);
        $date = Carbon::parse($request->input('date'));

        $rows = JobRecord::primary()
            ->whereIn('contract_id', $this->visibleContractIds(Auth::user()))
            ->where('job_type', 'breakdown')
            ->whereDate('scheduled_date', $date)
            ->with(['unit:id,identification_no,unit_number,operational_status', 'contract:id,project_name,location', 'technician:id,name', 'helpers.technician:id,name'])
            ->orderBy('id')
            ->get()
            ->map(fn ($job) => [
                'code'        => $job->job_code,
                'unit'        => optional($job->unit)->identification_no . (optional($job->unit)->unit_number ? ' (' . $job->unit->unit_number . ')' : ''),
                'project'     => optional($job->contract)->project_name,
                'location'    => optional($job->contract)->location,
                'cause'       => $job->breakdown_cause ?: $job->helpers->pluck('breakdown_cause')->filter()->first() ?: 'Not recorded yet',
                'technicians' => collect([optional($job->technician)->name])->concat($job->helpers->pluck('technician.name'))->filter()->implode(', '),
                'status'      => ucfirst(str_replace('_', ' ', $job->status)),
                'unit_status' => $job->unit_status_after ? ucfirst($job->unit_status_after) : (optional($job->unit)->operational_status === 'shutdown' ? 'Shutdown' : null),
                'url'         => route('admin.job-types.show', $job->id),
            ]);

        return response()->json([
            'date'  => $date->format('l, d M Y'),
            'count' => $rows->count(),
            'rows'  => $rows,
        ]);
    }

    // ================= Helpers =================

    private function visibleContractIds(User $user): Collection
    {
        $query = Contract::query();
        if (! in_array($user->type, self::ADMIN_ROLES, true)) {
            $query->visibleTo($user);
        }

        return $query->pluck('id');
    }

    private function selectedMonth(Request $request): array
    {
        $month = $request->get('month');
        $start = ($month && preg_match('/^\d{4}-\d{2}$/', $month))
            ? Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfMonth()
            : now()->startOfMonth();

        return [$start, $start->copy()->endOfMonth()];
    }

    private function breakdownSummary(callable $jobs, Carbon $monthStart, Carbon $monthEnd): array
    {
        $rows = $jobs()->where('job_type', 'breakdown')
            ->whereBetween('scheduled_date', [$monthStart, $monthEnd])
            ->get(['id', 'scheduled_date', 'breakdown_cause']);

        $causes = collect(TechnicianJobController::BREAKDOWN_CAUSES)->push('Not recorded');
        $days = range(1, $monthEnd->day);

        $series = $causes->map(function ($cause) use ($rows, $days) {
            $data = array_map(fn ($d) => $rows->filter(fn ($r) => $r->scheduled_date->day === $d
                && (($r->breakdown_cause ?: 'Not recorded') === $cause))->count(), $days);

            return ['name' => $cause, 'data' => $data];
        })->filter(fn ($s) => array_sum($s['data']) > 0)->values();

        return [
            'labels'  => array_map(fn ($d) => $monthStart->copy()->day($d)->format('M j'), $days),
            'dates'   => array_map(fn ($d) => $monthStart->copy()->day($d)->toDateString(), $days),
            'series'  => $series,
            'total'   => $rows->count(),
            'per_day' => $rows->groupBy(fn ($r) => $r->scheduled_date->day)->map->count(),
        ];
    }

    private function calendarWeeks(Carbon $monthStart, Collection $perDay): array
    {
        $weeks = [];
        $cursor = $monthStart->copy()->startOfWeek(Carbon::MONDAY);
        $end = $monthStart->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        while ($cursor->lte($end)) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $inMonth = $cursor->month === $monthStart->month;
                $week[] = [
                    'date'       => $cursor->copy(),
                    'inMonth'    => $inMonth,
                    'isToday'    => $cursor->isToday(),
                    'breakdowns' => $inMonth ? (int) ($perDay[$cursor->day] ?? 0) : 0,
                ];
                $cursor->addDay();
            }
            $weeks[] = $week;
        }

        return $weeks;
    }

    /**
     * Today's activity: active technicians (a job running now), idle/travelling technicians
     * (worked today, between jobs now), technicians on leave, idle/travel time per technician
     * (gaps between their jobs), counts by job type and the latest jobs.
     */
    private function liveActivity(Collection $contractIds, User $user): array
    {
        $today = now()->startOfDay();

        // only work started TODAY — an old job someone forgot to close (days ago) is not "live"
        $todayJobs = JobRecord::whereIn('contract_id', $contractIds)
            ->where('check_in_time', '>=', $today)
            ->with(['unit:id,identification_no,unit_number', 'contract:id,project_name,location', 'technician:id,name,image_path', 'parent:id,job_code'])
            ->orderByRaw("FIELD(status, 'in_progress', 'on_hold', 'pending', 'completed')")
            ->orderByDesc('check_in_time')
            ->get();

        // Rev 8: per-technician idle / travel time = gaps BETWEEN jobs today; leave is never idle
        $teamIds = IdleTimeCalculator::teamTechnicianIds($user);
        $scopeIds = $teamIds === null ? null
            : collect($teamIds)->merge($todayJobs->pluck('assigned_technician_id'))->filter()->unique()->values()->all();
        $idleRows = IdleTimeCalculator::forDay(now(), $scopeIds);

        $working = $idleRows->filter(fn ($r) => ! $r['on_leave'] && ($r['now']['type'] ?? null) === 'working');
        $idleNow = $idleRows->filter(fn ($r) => ! $r['on_leave'] && ($r['now']['type'] ?? null) === 'idle');
        $onLeave = $idleRows->where('on_leave', true);

        // counted once per main job (a team job started by 2 technicians = 1)
        $byType = $todayJobs->unique(fn ($j) => $j->parent_job_id ?? $j->id)->countBy('job_type');

        return [
            'active'     => $working->count(),
            'idle'       => $idleNow->count(),
            'on_leave'   => $onLeave->count(),
            'idle_rows'  => $idleRows,
            'idle_total' => $idleRows->where('on_leave', false)->sum('idle_min'),
            'by_type'    => [
                'ppm'        => $byType['ppm'] ?? 0,
                'breakdown'  => $byType['breakdown'] ?? 0,
                'repair'     => $byType['repair'] ?? 0,
                'casual_job' => $byType['casual_job'] ?? 0,
            ],
            'jobs'       => $todayJobs->take(8),
            'updated_at' => now(),
        ];
    }
}
