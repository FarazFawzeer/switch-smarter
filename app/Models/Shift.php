<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $fillable = ['name', 'start_time', 'end_time'];

    /** Shifts that run past midnight, e.g. Evening 16:00–01:00, Night 23:00–08:00. */
    public function isOvernight(): bool
    {
        return $this->end_time < $this->start_time;
    }

    /**
     * When the shift that $start falls in ends — the cut-off for overtime.
     * For an overnight shift, work started after midnight (before the end time)
     * belongs to the shift that began the previous evening, so it ends the same day.
     */
    public function endFor(CarbonInterface $start): Carbon
    {
        $end = Carbon::parse($start->toDateString() . ' ' . $this->end_time);

        if ($this->isOvernight() && $start->format('H:i:s') >= $this->end_time) {
            $end->addDay();
        }

        return $end;
    }
}
