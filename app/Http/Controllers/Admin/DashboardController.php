<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ElevatorUnit;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $isAdmin = in_array($user->type, ['Manager', 'Super Admin', 'Admin']);

        $contractsQuery = Contract::query();
        if (! $isAdmin) {
            $contractsQuery->visibleTo($user);
        }

        $totalContracts = (clone $contractsQuery)->count();
        $activeContracts = (clone $contractsQuery)->where('status', 'active')->count();
        $expiredContracts = (clone $contractsQuery)->where('status', 'expired')->count();
        $cancelledContracts = (clone $contractsQuery)->where('status', 'cancelled')->count();

        $totalElevatorUnits = (clone $contractsQuery)->withCount('elevatorUnits')->get()->sum('elevator_units_count');

        // Contracts expiring within the next 90 days (active only)
        $expiringSoon = (clone $contractsQuery)
            ->where('status', 'active')
            ->whereBetween('contract_end_date', [Carbon::today(), Carbon::today()->addDays(2000)])
            ->orderBy('contract_end_date')
            ->with('engineer:id,name')
            ->limit(5)
            ->get();

        // Most recently added contracts
        $recentContracts = (clone $contractsQuery)
            ->with('engineer:id,name')
            ->withCount('elevatorUnits')
            ->latest()
            ->limit(5)
            ->get();

        $visibleContractIds = (clone $contractsQuery)->pluck('id');

        $unitTypeBreakdown = ElevatorUnit::query()
            ->whereIn('contract_id', $visibleContractIds)
            ->selectRaw('unit_type, count(*) as total')
            ->groupBy('unit_type')
            ->pluck('total', 'unit_type');

        // Defensive debug — remove after confirming this works
        \Log::info('Dashboard unit type debug', [
            'visible_contract_ids' => $visibleContractIds->toArray(),
            'unit_type_breakdown'  => $unitTypeBreakdown->toArray(),
        ]);

        // Contracts grouped by engineer (admin view only — not meaningful for a single engineer's own view)
        $contractsByEngineer = collect();
        if ($isAdmin) {
            $contractsByEngineer = Contract::selectRaw('assigned_engineer_id, count(*) as total')
                ->whereNotNull('assigned_engineer_id')
                ->groupBy('assigned_engineer_id')
                ->with('engineer:id,name')
                ->get()
                ->map(fn($row) => [
                    'name'  => optional($row->engineer)->name ?? 'Unassigned',
                    'total' => $row->total,
                ]);
        }

        return view('index', compact(
            'totalContracts',
            'activeContracts',
            'expiredContracts',
            'cancelledContracts',
            'totalElevatorUnits',
            'expiringSoon',
            'recentContracts',
            'unitTypeBreakdown',
            'contractsByEngineer',
            'isAdmin'
        ));
    }
}
