<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/** Technician Leave Request — goes to the technician's supervisor for approval. */
class TechnicianLeaveController extends Controller
{
    public function index()
    {
        $requests = LeaveRequest::where('technician_id', Auth::id())
            ->with('reviewer:id,name')
            ->orderByDesc('start_date')
            ->limit(30)
            ->get();

        $supervisor = Auth::user()->supervisor;

        return view('technician.leave', compact('requests', 'supervisor'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'leave_type' => ['required', Rule::in(LeaveRequest::TYPES)],
            'start_date' => ['required', 'date', 'after_or_equal:' . now()->subDays(30)->toDateString()],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date', 'before_or_equal:' . now()->addYear()->toDateString()],
            'reason'     => ['required', 'string', 'max:1000'],
        ], [
            'start_date.after_or_equal' => 'Leave can be requested for dates up to 30 days back.',
            'end_date.after_or_equal'   => 'The end date must be on or after the start date.',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $start = Carbon::parse($request->input('start_date'));
        $end = Carbon::parse($request->input('end_date'));

        $overlap = LeaveRequest::where('technician_id', Auth::id())
            ->whereIn('status', ['pending', 'approved'])
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->exists();

        if ($overlap) {
            return response()->json([
                'success' => false,
                'errors'  => ['start_date' => ['You already have a pending or approved leave request for some of these dates.']],
            ], 422);
        }

        LeaveRequest::create([
            'technician_id' => Auth::id(),
            'leave_type'    => $request->input('leave_type'),
            'start_date'    => $start,
            'end_date'      => $end,
            'days'          => $start->diffInDays($end) + 1,
            'reason'        => $request->input('reason'),
            'status'        => 'pending',
        ]);

        $supervisorName = optional(Auth::user()->supervisor)->name;

        return response()->json([
            'success' => true,
            'message' => 'Leave request sent' . ($supervisorName ? " to {$supervisorName}" : '') . ' for approval.',
        ]);
    }

    /** A technician can withdraw a request that hasn't been reviewed yet. */
    public function cancel(LeaveRequest $leave)
    {
        abort_unless((int) $leave->technician_id === (int) Auth::id(), 403);

        if ($leave->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Only a pending request can be cancelled.'], 422);
        }

        $leave->update(['status' => 'cancelled']);

        return response()->json(['success' => true, 'message' => 'Leave request cancelled.']);
    }
}
