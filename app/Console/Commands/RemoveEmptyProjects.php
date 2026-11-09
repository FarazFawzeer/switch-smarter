<?php

namespace App\Console\Commands;

use App\Models\Contract;
use Illuminate\Console\Command;

/**
 * Projects with no units and no jobs — usually left over after a wrong bulk upload was
 * cleaned up. They are invisible in the master list (one row per unit) but used to inflate
 * the dashboard's project count and keep routes "in use". Dry run unless --force is given.
 */
class RemoveEmptyProjects extends Command
{
    protected $signature = 'projects:remove-empty {--force : Actually delete. Without this flag nothing is changed.}';

    protected $description = 'List (and with --force delete) projects that have no units and no jobs';

    public function handle(): int
    {
        $empty = Contract::doesntHave('elevatorUnits')->doesntHave('jobs')->with('route:id,route_no')->orderBy('project_name')->get();

        if ($empty->isEmpty()) {
            $this->info('No empty projects found.');
            return self::SUCCESS;
        }

        $this->table(['ID', 'Project', 'Location', 'Route', 'Status', 'Created'], $empty->map(fn ($c) => [
            $c->id, $c->project_name, $c->location, optional($c->route)->route_no ?? '—', $c->status, optional($c->created_at)->format('d M Y'),
        ])->all());

        if (! $this->option('force')) {
            $this->line($empty->count() . ' empty project(s). DRY RUN — re-run with --force to delete them.');
            return self::SUCCESS;
        }

        $empty->each->delete();
        $this->info('Deleted ' . $empty->count() . ' empty project(s).');

        return self::SUCCESS;
    }
}
