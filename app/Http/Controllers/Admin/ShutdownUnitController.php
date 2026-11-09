<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ElevatorUnit;
use App\Models\Route as RouteModel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Elevators currently shut down (set when a technician closes a breakdown/repair as
 * "Shutdown"). Treated as a priority list — longest-down first.
 */
class ShutdownUnitController extends Controller
{
    public function index(Request $request)
    {
        $visibleContractIds = Contract::visibleTo(Auth::user())->pluck('id');

        $query = ElevatorUnit::query()
            ->where('operational_status', 'shutdown')
            ->whereIn('contract_id', $visibleContractIds)
            ->with([
                'contract:id,project_name,location,route_id,assigned_engineer_id',
                'contract.route:id,route_no',
                'contract.engineer:id,name',
                'shutdownJob:id,job_code,job_type,assigned_technician_id,breakdown_cause',
                'shutdownJob.technician:id,name',
            ]);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('identification_no', 'like', "%{$search}%")
                    ->orWhere('unit_number', 'like', "%{$search}%")
                    ->orWhereHas('contract', fn ($c) => $c->where('project_name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('unit')) {
            $query->where('identification_no', 'like', '%' . $request->input('unit') . '%');
        }
        if ($request->filled('unit_no')) {
            $query->where('unit_number', 'like', '%' . $request->input('unit_no') . '%');
        }
        if ($request->filled('project')) {
            $project = $request->input('project');
            $query->whereHas('contract', fn ($c) => $c->where(fn ($w) => $w->where('project_name', 'like', "%{$project}%")->orWhere('location', 'like', "%{$project}%")));
        }
        if ($request->filled('route_id')) {
            $query->whereHas('contract', fn ($c) => $c->where('route_id', $request->input('route_id')));
        }
        if ($request->filled('engineer_id')) {
            $query->whereHas('contract', fn ($c) => $c->where('assigned_engineer_id', $request->input('engineer_id')));
        }
        if ($request->filled('reason')) {
            $query->whereHas('shutdownJob', fn ($j) => $j->where('breakdown_cause', 'like', '%' . $request->input('reason') . '%'));
        }

        $units = $query->orderBy('shutdown_since')->get();

        $routeOptions = RouteModel::whereIn('id', Contract::whereIn('id', $visibleContractIds)->whereNotNull('route_id')->pluck('route_id'))
            ->orderBy('route_no')->pluck('route_no', 'id')->all();
        $engineerOptions = User::whereIn('id', Contract::whereIn('id', $visibleContractIds)->whereNotNull('assigned_engineer_id')->pluck('assigned_engineer_id'))
            ->orderBy('name')->pluck('name', 'id')->all();

        return view('admin.shutdown-units.index', compact('units', 'routeOptions', 'engineerOptions'));
    }

    /** Office override: elevator is running again (e.g. fixed outside the app). */
    public function markWorking(ElevatorUnit $unit)
    {
        abort_unless(Contract::visibleTo(Auth::user())->whereKey($unit->contract_id)->exists(), 403);

        if (! $unit->isShutdown()) {
            return response()->json(['success' => false, 'message' => 'This elevator is not shut down.'], 422);
        }

        $unit->markWorking();

        return response()->json([
            'success' => true,
            'message' => $unit->identification_no . ' is marked as working again.',
        ]);
    }
}
