<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Leave requests from technicians. The technician's SUPERVISOR reviews and approves/rejects.
 * Engineers see their team's requests (read-only); Super Admin / Admin / Manager can act on all.
 */
class LeaveRequestController extends Controller
{
    private const ADMIN_ROLES = ['Super Admin', 'Admin', 'Manager'];

    public function index(Request $request)
    {
        $user = Auth::user();
        $status = $request->get('status', 'pending');

        $query = LeaveRequest::with(['technician:id,name,employee_id,supervisor_id', 'technician.supervisor:id,name', 'reviewer:id,name'])
            ->whereIn('technician_id', $this->visibleTechnicianIds($user));

        if (in_array($status, ['pending', 'approved', 'rejected', 'cancelled'], true)) {
            $query->where('status', $status);
        }

        $requests = $query->orderBy('start_date')->paginate(20)->withQueryString();

        $pendingCount = LeaveRequest::whereIn('technician_id', $this->visibleTechnicianIds($user))
            ->where('status', 'pending')->count();

        $canReview = $user->type === 'Supervisor' || in_array($user->type, self::ADMIN_ROLES, true);

        return view('admin.leave-requests.index', compact('requests', 'status', 'pendingCount', 'canReview'));
    }

    public function approve(Request $request, LeaveRequest $leave)
    {
        return $this->review($request, $leave, 'approved');
    }

    public function reject(Request $request, LeaveRequest $leave)
    {
        return $this->review($request, $leave, 'rejected');
    }

    private function review(Request $request, LeaveRequest $leave, string $decision)
    {
        $user = Auth::user();
        $leave->load('technician');

        if (! $this->canReview($user, $leave)) {
            return response()->json(['success' => false, 'message' => 'Only this technician\'s supervisor can review the request.'], 403);
        }

        if ($leave->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'This request has already been ' . $leave->status . '.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'note' => [$decision === 'rejected' ? 'required' : 'nullable', 'string', 'max:255'],
        ], [
            'note.required' => 'Please give a reason for rejecting the request.',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $leave->update([
            'status'      => $decision,
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
            'review_note' => $request->input('note'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Leave ' . $decision . ' for ' . optional($leave->technician)->name . '.',
        ]);
    }

    private function canReview(User $user, LeaveRequest $leave): bool
    {
        if (in_array($user->type, self::ADMIN_ROLES, true)) {
            return true;
        }

        return $user->type === 'Supervisor' && (int) optional($leave->technician)->supervisor_id === (int) $user->id;
    }

    /** @return int[] */
    private function visibleTechnicianIds(User $user): array
    {
        $technicians = User::where('type', 'Technician');

        return match (true) {
            in_array($user->type, self::ADMIN_ROLES, true) => $technicians->pluck('id')->all(),
            $user->type === 'Supervisor' => $technicians->where('supervisor_id', $user->id)->pluck('id')->all(),
            $user->type === 'Engineer'   => $technicians->whereIn('supervisor_id', $user->supervisors()->pluck('id'))->pluck('id')->all(),
            default                      => [],
        };
    }
}
