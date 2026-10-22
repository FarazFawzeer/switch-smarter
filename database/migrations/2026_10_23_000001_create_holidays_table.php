<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Public / company holidays. Work done on these dates is shown as HOT in timesheets.
        // (The weekly day off — Friday in Qatar — comes from config/timesheet.php, not this table.)
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->string('name', 150);
            $table->timestamps();
        });

        // Qatar official holidays. Eid dates follow the moon sighting, so they are
        // estimates — adjust them on the Holidays page once officially announced.
        $now = now();
        $holidays = [
            ['2026-02-10', 'National Sport Day'],
            ['2026-03-20', 'Eid al-Fitr (Day 1)'],
            ['2026-03-21', 'Eid al-Fitr (Day 2)'],
            ['2026-03-22', 'Eid al-Fitr (Day 3)'],
            ['2026-05-27', 'Eid al-Adha (Day 1)'],
            ['2026-05-28', 'Eid al-Adha (Day 2)'],
            ['2026-05-29', 'Eid al-Adha (Day 3)'],
            ['2026-12-18', 'Qatar National Day'],
            ['2027-02-09', 'National Sport Day'],
            ['2027-03-10', 'Eid al-Fitr (Day 1) — estimated'],
            ['2027-03-11', 'Eid al-Fitr (Day 2) — estimated'],
            ['2027-03-12', 'Eid al-Fitr (Day 3) — estimated'],
            ['2027-05-16', 'Eid al-Adha (Day 1) — estimated'],
            ['2027-05-17', 'Eid al-Adha (Day 2) — estimated'],
            ['2027-05-18', 'Eid al-Adha (Day 3) — estimated'],
            ['2027-12-18', 'Qatar National Day'],
        ];

        DB::table('holidays')->insertOrIgnore(array_map(fn ($h) => [
            'date'       => $h[0],
            'name'       => $h[1],
            'created_at' => $now,
            'updated_at' => $now,
        ], $holidays));
    }

    public function down()
    {
        Schema::dropIfExists('holidays');
    }
};
