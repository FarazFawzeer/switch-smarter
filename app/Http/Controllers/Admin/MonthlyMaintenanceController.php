<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobRecord;
use App\Models\Route as RouteModel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MonthlyMaintenanceController extends Controller
{
    public function index(Request $request)
    {
        $month = (int) $request->get('month', now()->month);
        $year = (int) $request->get('year', now()->year);

        $query = JobRecord::query()
            ->where('job_type', 'ppm')
            ->whereNotNull('elevator_unit_id') // orphan visits (unit deleted) are not real PPMs
            ->whereYear('scheduled_date', $year)
            ->whereMonth('scheduled_date', $month)
            ->with(['contract:id,project_name,location,route_id,assigned_engineer_id', 'contract.route:id,route_no', 'contract.engineer:id,name', 'unit:id,identification_no,unit_number', 'technician:id,name,employee_id']);

        if ($request->filled('engineer_id')) {
            $query->whereHas('contract', fn ($q) => $q->where('assigned_engineer_id', $request->input('engineer_id')));
        }

        if ($request->filled('technician_id')) {
            $query->where('assigned_technician_id', $request->input('technician_id'));
        }

        if ($request->filled('route_id')) {
            $query->whereHas('contract', fn ($q) => $q->where('route_id', $request->input('route_id')));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('contract', fn ($cq) => $cq->where('project_name', 'like', "%{$search}%"))
                  ->orWhereHas('unit', fn ($uq) => $uq->where('identification_no', 'like', "%{$search}%"));
            });
        }

        // Summary counts computed on the FULL filtered set (before status filter/pagination),
        // via one aggregate query. Counted per UNIT, so a unit with two visits in a month
        // can never be counted twice.
        $summaryQuery = clone $query;
        $summary = $summaryQuery->selectRaw("
                COUNT(DISTINCT CASE WHEN status = 'completed' THEN elevator_unit_id END) as completed,
                COUNT(DISTINCT CASE WHEN status IN ('pending','overdue') AND scheduled_date < ? THEN elevator_unit_id END) as overdue,
                COUNT(DISTINCT CASE WHEN status = 'pending' AND scheduled_date >= ? THEN elevator_unit_id END) as pending,
                COUNT(DISTINCT CASE WHEN status IN ('in_progress','on_hold') THEN elevator_unit_id END) as in_progress,
                COUNT(DISTINCT elevator_unit_id) as scheduled
            ", [now()->toDateString(), now()->toDateString()])
            ->first();

        // Total PPMs comes from the MASTER LIST: every unit on an active contract that matches
        // the filters. With a technician selected, it's the units assigned to that technician.
        if ($request->filled('technician_id')) {
            $summary->total = (int) $summary->scheduled;
            $summary->not_scheduled = null;
        } else {
            $masterList = \App\Models\ElevatorUnit::query()
                ->join('contracts', 'contracts.id', '=', 'elevator_units.contract_id')
                ->where('contracts.status', 'active');

            if ($request->filled('engineer_id')) {
                $masterList->where('contracts.assigned_engineer_id', $request->input('engineer_id'));
            }
            if ($request->filled('route_id')) {
                $masterList->where('contracts.route_id', $request->input('route_id'));
            }
            if ($request->filled('search')) {
                $search = $request->input('search');
                $masterList->where(function ($q) use ($search) {
                    $q->where('contracts.project_name', 'like', "%{$search}%")
                        ->orWhere('elevator_units.identification_no', 'like', "%{$search}%");
                });
            }

            $summary->total = $masterList->count();
            $summary->not_scheduled = max(0, $summary->total - (int) $summary->scheduled);
        }

        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'overdue') {
                $query->whereIn('status', ['pending', 'overdue'])->whereDate('scheduled_date', '<', now()->toDateString());
            } elseif ($status === 'pending') {
                $query->where('status', 'pending')->whereDate('scheduled_date', '>=', now()->toDateString());
            } else {
                $query->where('status', $status);
            }
        }

        $jobs = $query->orderBy('scheduled_date')->paginate(30)->withQueryString();

        $engineers = User::where('type', 'Engineer')->select('id', 'name')->get();
        $technicians = User::where('type', 'Technician')->select('id', 'name', 'employee_id')->get();
        $routes = RouteModel::orderBy('route_no')->get();

        $monthOptions = collect(range(1, 12))->mapWithKeys(fn ($m) => [$m => Carbon::create()->month($m)->format('F')]);
        $yearOptions = range(now()->year - 2, now()->year + 2);

        return view('admin.monthly-maintenance.index', compact(
            'jobs', 'summary', 'engineers', 'technicians', 'routes',
            'month', 'year', 'monthOptions', 'yearOptions'
        ));
    }
}