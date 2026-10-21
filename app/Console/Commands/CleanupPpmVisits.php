<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\JobRecord;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-off repair for PPM data damaged before Revision 6:
 *  1. "Orphan" visits — the unit was deleted when its contract was edited, so
 *     elevator_unit_id became NULL (shown as an empty Unit ID, inflates counts).
 *  2. Duplicate visits — the same elevator with more than one PPM visit on the same date.
 *
 * Only visits that were never started are removed. Started/completed visits are reported
 * and left alone. Runs as a dry run unless --force is given.
 */
class CleanupPpmVisits extends Command
{
    protected $signature = 'ppm:cleanup {--force : Actually delete. Without this flag nothing is changed.}';

    protected $description = 'Remove orphan (no unit) and duplicate PPM visits that were never started';

    public function handle(): int
    {
        $apply = (bool) $this->option('force');

        $this->info($apply ? 'Cleaning up PPM visits…' : 'DRY RUN — nothing will be changed. Re-run with --force to apply.');
        $this->newLine();

        // ---------- 1. Orphan visits ----------
        $orphans = JobRecord::where('job_type', 'ppm')->whereNull('elevator_unit_id');
        $orphanStarted = (clone $orphans)->where(fn ($q) => $this->startedScope($q))->count();
        $orphanDeletable = (clone $orphans)->where(fn ($q) => $this->notStartedScope($q));
        $orphanCount = (clone $orphanDeletable)->count();

        $this->line("Orphan PPM visits (no unit): <comment>{$orphanCount}</comment> to remove, {$orphanStarted} started/completed kept.");

        // ---------- 2. Duplicate visits (same unit, same date) ----------
        $groups = DB::table('jobs_records')
            ->where('job_type', 'ppm')
            ->whereNotNull('elevator_unit_id')
            ->groupBy('elevator_unit_id', 'scheduled_date')
            ->havingRaw('COUNT(*) > 1')
            ->get(['elevator_unit_id', 'scheduled_date']);

        $duplicateIds = [];
        foreach ($groups as $group) {
            $jobs = JobRecord::where('job_type', 'ppm')
                ->where('elevator_unit_id', $group->elevator_unit_id)
                ->whereDate('scheduled_date', $group->scheduled_date)
                ->orderBy('id')
                ->get();

            // keep a started/completed one if there is one, otherwise the oldest
            $keep = $jobs->first(fn ($j) => $j->hasStarted()) ?? $jobs->first();

            foreach ($jobs as $job) {
                if ($job->id !== $keep->id && ! $job->hasStarted()) {
                    $duplicateIds[] = $job->id;
                }
            }
        }

        $this->line('Duplicate PPM visits (same unit + same date): <comment>' . count($duplicateIds) . '</comment> to remove across ' . $groups->count() . ' unit/date pair(s).');

        // ---------- Per-technician impact (helps explain wrong unit counts) ----------
        $affectedIds = array_merge((clone $orphanDeletable)->pluck('id')->all(), $duplicateIds);

        if ($affectedIds) {
            $byTechnician = JobRecord::whereIn('jobs_records.id', $affectedIds)
                ->leftJoin('users', 'users.id', '=', 'jobs_records.assigned_technician_id')
                ->groupBy('users.name')
                ->selectRaw("COALESCE(users.name, 'Unassigned') as technician, COUNT(*) as visits")
                ->orderByDesc('visits')
                ->get();

            $this->newLine();
            $this->table(['Technician', 'Visits to remove'], $byTechnician->map(fn ($r) => [$r->technician, $r->visits])->all());
        }

        if (! $apply) {
            return self::SUCCESS;
        }

        $contractIds = JobRecord::whereIn('id', $affectedIds)->distinct()->pluck('contract_id')->filter();

        DB::transaction(function () use ($orphanDeletable, $duplicateIds, $contractIds) {
            $orphanDeletable->delete();

            foreach (array_chunk($duplicateIds, 500) as $chunk) {
                JobRecord::whereIn('id', $chunk)->delete();
            }

            // re-derive each affected contract's "fully scheduled" flag
            foreach (Contract::whereIn('id', $contractIds)->get() as $contract) {
                $total = $contract->elevatorUnits()->count();
                $scheduled = $contract->scheduledUnitsCount();
                $contract->update([
                    'is_scheduled'   => $total > 0 && $scheduled >= $total,
                    'ppm_start_date' => $scheduled > 0 ? $contract->ppm_start_date : null,
                ]);
            }
        });

        $this->newLine();
        $this->info('Done. Removed ' . count($affectedIds) . ' visit(s).');

        return self::SUCCESS;
    }

    private function startedScope($q)
    {
        return $q->whereNotNull('check_in_time')->orWhereIn('status', ['in_progress', 'on_hold', 'completed']);
    }

    private function notStartedScope($q)
    {
        return $q->whereNull('check_in_time')->whereNotIn('status', ['in_progress', 'on_hold', 'completed']);
    }
}
