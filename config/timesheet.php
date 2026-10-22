<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Weekly days off
    |--------------------------------------------------------------------------
    |
    | Work on these weekdays is counted as HOT (holiday overtime) instead of
    | Normal Time / Over Time. Qatar's weekly rest day is Friday. Use a comma
    | separated list of English day names, e.g. "Friday,Saturday".
    |
    */

    'weekly_off_days' => array_values(array_filter(array_map(
        'trim',
        explode(',', env('TIMESHEET_WEEKLY_OFF_DAYS', 'Friday'))
    ))),

];
