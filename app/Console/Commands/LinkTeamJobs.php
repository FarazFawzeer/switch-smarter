<?php

namespace App\Console\Commands;

use App\Models\JobRecord;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-off repair for data from before team jobs existed: when two or more technicians
 * each opened their OWN breakdown / repair / casual job for the same unit on the same day,
 * link them as one team job (earliest = main job, others = helpers). Nothing is deleted —
 * each technician keeps their own row and timesheet; the job is just counted once.
 * Dry run unless --force is given.
 */
class LinkTeamJobs extends Command
{
    protected $signature = 'jobs:link-team-duplicates {--force : Actually link the jobs. Without this flag nothing is changed.}';

    protected $description = 'Link same-day breakdown/repair/casual jobs on one unit by different technicians into one team job';

    public function handle(): int
    {
        $apply = (bool) $this->option('force');
        $this->info($apply ? 'Linking team jobs…' : 'DRY RUN — nothing will be changed. Re-run with --force to apply.');

        $groups = DB::table('jobs_records')
            ->whereNull('parent_job_id')
            ->whereIn('job_type', ['breakdown', 'repair', 'casual_job'])
            ->whereNotNull('elevator_unit_id')
            ->groupBy('elevator_unit_id', 'job_type', 'scheduled_date')
            ->havingRaw('COUNT(*) > 1 AND COUNT(DISTINCT assigned_technician_id) > 1')
            ->get([DB::raw('elevator_unit_id'), DB::raw('job_type'), DB::raw('scheduled_date')]);

        $rows = [];
        $links = [];

        foreach ($groups as $group) {
            $jobs = JobRecord::whereNull('parent_job_id')
                ->where('elevator_unit_id', $group->elevator_unit_id)
                ->where('job_type', $group->job_type)
                ->whereDate('scheduled_date', $group->scheduled_date)
                ->with('technician:id,name', 'unit:id,identification_no')
                ->orderBy('id')
                ->get();

            $main = $jobs->first();
            $seen = [$main->assigned_technician_id];

            foreach ($jobs->slice(1) as $job) {
                // the same technician twice on one day is a real second job — leave it alone
                if (in_array($job->assigned_technician_id, $seen, true)) {
                    continue;
                }
                $seen[] = $job->assigned_technician_id;
                $links[$job->id] = $main->id;

                $rows[] = [
                    $main->job_code,
                    $job->job_code,
                    $job->job_type,
                    optional($main->unit)->identification_no,
                    substr((string) $group->scheduled_date, 0, 10),
                    optional($job->technician)->name,
                ];
            }
        }

        if (! $rows) {
            $this->info('No duplicate team jobs found.');
            return self::SUCCESS;
        }

        $this->table(['Main job', 'Linked job', 'Type', 'Unit', 'Date', 'Technician'], $rows);
        $this->line(count($rows) . ' job(s) would be linked as team members (they stop being counted separately).');

        if ($apply) {
            DB::transaction(function () use ($links) {
                foreach ($links as $jobId => $mainId) {
                    JobRecord::whereKey($jobId)->update(['parent_job_id' => $mainId]);
                }
            });
            $this->info('Done.');
        }

        return self::SUCCESS;
    }
}
