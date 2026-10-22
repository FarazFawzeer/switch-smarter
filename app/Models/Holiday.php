<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $fillable = ['date', 'name'];

    protected $casts = [
        'date' => 'date',
    ];

    /** @var array<string,string>|null date (Y-m-d) => name, loaded once per request */
    private static ?array $listed = null;

    protected static function booted(): void
    {
        $forget = fn () => self::$listed = null;
        static::saved($forget);
        static::deleted($forget);
    }

    /**
     * Why this date is a holiday (e.g. "Weekly day off (Friday)" or "Qatar National Day"),
     * or null for a normal working day.
     */
    public static function reasonFor(CarbonInterface $date): ?string
    {
        self::$listed ??= static::query()->pluck('name', 'date')
            ->mapWithKeys(fn ($name, $day) => [substr((string) $day, 0, 10) => $name])
            ->all();

        if (isset(self::$listed[$date->toDateString()])) {
            return self::$listed[$date->toDateString()];
        }

        $dayName = $date->format('l');
        if (in_array($dayName, config('timesheet.weekly_off_days', []), true)) {
            return "Weekly day off ({$dayName})";
        }

        return null;
    }

    public static function isHoliday(CarbonInterface $date): bool
    {
        return self::reasonFor($date) !== null;
    }
}
