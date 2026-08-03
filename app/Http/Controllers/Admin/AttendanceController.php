<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TechnicianAttendance;
use App\Models\User;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $query = TechnicianAttendance::with(['technician:id,name,employee_id', 'shift:id,name']);

        if ($request->filled('technician_id')) {
            $query->where('technician_id', $request->input('technician_id'));
        }

        if ($request->filled('month') && $request->filled('year')) {
            $query->whereMonth('work_date', $request->input('month'))
                  ->whereYear('work_date', $request->input('year'));
        } elseif ($request->filled('from') || $request->filled('to')) {
            if ($request->filled('from')) {
                $query->whereDate('work_date', '>=', $request->input('from'));
            }
            if ($request->filled('to')) {
                $query->whereDate('work_date', '<=', $request->input('to'));
            }
        } else {
            // Default: current month
            $query->whereMonth('work_date', now()->month)
                  ->whereYear('work_date', now()->year);
        }

        $records = $query->orderByDesc('work_date')->paginate(20)->withQueryString();

        // Summary stats for the currently filtered set
        $summaryQuery = clone $query;
        $totalHours = (clone $query)->sum('total_hours');
        $totalOt = (clone $query)->sum('ot_hours');
        $totalDays = (clone $query)->count();

        $technicians = User::where('type', 'Technician')->select('id', 'name', 'employee_id')->get();

        return view('admin.attendance.index', compact('records', 'technicians', 'totalHours', 'totalOt', 'totalDays'));
    }
}