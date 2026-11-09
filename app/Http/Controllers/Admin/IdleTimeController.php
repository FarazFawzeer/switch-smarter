<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\IdleTimeCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/** Idle / Travel Time report: gaps between each technician's jobs on a chosen day. */
class IdleTimeController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->filled('date') && strtotime($request->input('date'))
            ? Carbon::parse($request->input('date'))->startOfDay()
            : today();
        if ($date->isFuture()) {
            $date = today();
        }

        $rows = IdleTimeCalculator::forDay($date, IdleTimeCalculator::teamTechnicianIds(Auth::user()));

        if ($request->filled('technician')) {
            $needle = mb_strtolower($request->input('technician'));
            $rows = $rows->filter(fn ($r) => str_contains(mb_strtolower(optional($r['technician'])->name . ' ' . optional($r['technician'])->employee_id), $needle))->values();
        }

        $totals = [
            'technicians' => $rows->where('on_leave', false)->count(),
            'worked'      => $rows->sum('worked_min'),
            'idle'        => $rows->sum('idle_min'),
            'on_leave'    => $rows->where('on_leave', true)->count(),
        ];

        return view('admin.idle-time.index', compact('rows', 'date', 'totals'));
    }
}
