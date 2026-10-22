<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Public / company holidays. Work on these dates (and on the weekly day off
 * from config/timesheet.php) is shown as HOT in timesheets.
 */
class HolidayController extends Controller
{
    public function index(Request $request)
    {
        $year = (int) $request->get('year', now()->year);

        $holidays = Holiday::whereYear('date', $year)->orderBy('date')->get();

        $years = Holiday::selectRaw('DISTINCT YEAR(date) as y')->pluck('y')
            ->push(now()->year)->push(now()->year + 1)
            ->unique()->sort()->values();

        $weeklyOff = config('timesheet.weekly_off_days', []);

        return view('admin.holidays.index', compact('holidays', 'year', 'years', 'weeklyOff'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => ['required', 'date', 'unique:holidays,date'],
            'name' => ['required', 'string', 'max:150'],
        ], [
            'date.unique' => 'That date is already in the holiday list.',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $holiday = Holiday::create($validator->validated());

        return response()->json([
            'success' => true,
            'message' => $holiday->name . ' added on ' . $holiday->date->format('d M Y') . '.',
        ]);
    }

    public function destroy(Holiday $holiday)
    {
        $holiday->delete();

        return response()->json([
            'success' => true,
            'message' => $holiday->name . ' removed.',
        ]);
    }
}
