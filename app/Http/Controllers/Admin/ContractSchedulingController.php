<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\JobRecord;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ContractSchedulingController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Contract::with(['engineer:id,name', 'supervisor:id,name'])
            ->where('status', 'active');

        // Admin oversight roles see everything; Engineer/Supervisor stay scoped
        if (! in_array($user->type, ['Manager', 'Super Admin', 'Admin'])) {
            $query->visibleTo($user);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('project_name', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        $contracts = $query->latest()->paginate(9)->withQueryString();

        return view('admin.scheduling.index', compact('contracts'));
    }

    public function assignSupervisorForm(Contract $contract)
    {
        $this->authorizeEngineerOwnsContract($contract);

        $supervisors = $this->isAdminOverride()
            ? User::where('type', 'Supervisor')->where('engineer_id', $contract->assigned_engineer_id)->select('id', 'name')->get()
            : Auth::user()->supervisors()->select('id', 'name')->get();

        return view('admin.scheduling.assign-supervisor', compact('contract', 'supervisors'));
    }

    public function assignSupervisor(Request $request, Contract $contract)
    {
        $this->authorizeEngineerOwnsContract($contract);

        $validator = Validator::make($request->all(), [
            'assigned_supervisor_id' => [
                'required',
                Rule::exists('users', 'id')->where(function ($q) use ($contract) {
                    $q->where('type', 'Supervisor')->where('engineer_id', $contract->assigned_engineer_id);
                }),
            ],
        ], [
            'assigned_supervisor_id.exists' => 'You can only assign a supervisor from this contract\'s engineer team.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        $contract->update([
            'assigned_supervisor_id' => $request->input('assigned_supervisor_id'),
        ]);

        return response()->json([
            'success'  => true,
            'message'  => 'Contract handed off to supervisor successfully!',
            'redirect' => route('admin.scheduling.index'),
        ]);
    }

    public function create(Contract $contract)
    {
        $this->authorizeSupervisorOwnsContract($contract);

        if ($contract->is_scheduled) {
            return redirect()->route('admin.scheduling.show', $contract->id);
        }

        $technicians = $this->isAdminOverride()
            ? User::where('type', 'Technician')->where('supervisor_id', $contract->assigned_supervisor_id)->select('id', 'name')->get()
            : Auth::user()->technicians()->select('id', 'name')->get();

        return view('admin.scheduling.create', compact('contract', 'technicians'));
    }

    public function store(Request $request, Contract $contract)
    {
        $this->authorizeSupervisorOwnsContract($contract);

        if ($contract->is_scheduled) {
            return response()->json([
                'success' => false,
                'errors'  => ['ppm_start_date' => ['This contract has already been scheduled.']]
            ], 422);
        }

        $allowedTechnicianIds = $this->isAdminOverride()
            ? User::where('type', 'Technician')->where('supervisor_id', $contract->assigned_supervisor_id)->pluck('id')->toArray()
            : Auth::user()->technicians()->pluck('id')->toArray();

        $validator = Validator::make($request->all(), [
            'ppm_start_date' => [
                'required',
                'date',
                'after_or_equal:' . $contract->contract_start_date->format('Y-m-d'),
                'before_or_equal:' . $contract->contract_end_date->format('Y-m-d'),
            ],
            'assigned_technician_id' => ['nullable', Rule::in($allowedTechnicianIds)],
        ], [
            'ppm_start_date.after_or_equal'  => 'PPM start date cannot be before the contract start date.',
            'ppm_start_date.before_or_equal' => 'PPM start date cannot be after the contract end date.',
            'assigned_technician_id.in'      => 'You can only assign a technician from this contract\'s supervisor team.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        $ppmStartDate = Carbon::parse($request->input('ppm_start_date'));
        $technicianId = $request->input('assigned_technician_id');

        DB::transaction(function () use ($contract, $ppmStartDate, $technicianId) {
            $contract->update([
                'ppm_start_date'         => $ppmStartDate,
                'is_scheduled'           => true,
                'assigned_technician_id' => $technicianId,
            ]);

            $this->generateMonthlyPpmJobs($contract, $ppmStartDate, $technicianId);
        });

        return response()->json([
            'success'  => true,
            'message'  => 'PPM schedule generated successfully!',
            'redirect' => route('admin.scheduling.show', $contract->id),
        ]);
    }

    public function show(Contract $contract)
    {
        $contract->load(['engineer', 'supervisor', 'technician', 'ppmJobs.technician']);

        return view('admin.scheduling.show', compact('contract'));
    }

    private function generateMonthlyPpmJobs(Contract $contract, Carbon $startDate, ?int $technicianId): void
    {
        $endDate = $contract->contract_end_date;
        $monthOffset = 0;
        $maxIterations = 600;

        while ($monthOffset < $maxIterations) {
            $visitDate = $startDate->copy()->addMonthsNoOverflow($monthOffset);

            if ($visitDate->gt($endDate)) {
                break;
            }

            JobRecord::create([
                'contract_id'            => $contract->id,
                'job_type'               => 'ppm',
                'scheduled_date'         => $visitDate,
                'status'                 => 'pending',
                'assigned_technician_id' => $technicianId,
                'assigned_by'            => Auth::id(),
                'description'            => 'Auto-generated monthly PPM visit',
            ]);

            $monthOffset++;
        }
    }

    /**
     * TEMPORARY: allows Manager/Super Admin/Admin to bypass ownership checks
     * for testing and oversight. Revisit before going live — decide with the
     * client whether this stays, gets restricted, or gets logged/audited.
     */
    private function isAdminOverride(): bool
    {
        return in_array(Auth::user()->type, ['Manager', 'Super Admin', 'Admin']);
    }

    private function authorizeEngineerOwnsContract(Contract $contract): void
    {
        if ($this->isAdminOverride()) {
            return;
        }

        abort_unless(
            Auth::user()->type === 'Engineer' && $contract->assigned_engineer_id === Auth::id(),
            403,
            'You do not have permission to assign this contract.'
        );
    }

    private function authorizeSupervisorOwnsContract(Contract $contract): void
    {
        if ($this->isAdminOverride()) {
            return;
        }

        abort_unless(
            Auth::user()->type === 'Supervisor' && $contract->assigned_supervisor_id === Auth::id(),
            403,
            'You do not have permission to schedule this contract.'
        );
    }
}