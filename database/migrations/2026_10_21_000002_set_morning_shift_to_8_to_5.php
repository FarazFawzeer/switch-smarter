<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::table('shifts')->where('name', 'Morning')->update([
            'start_time' => '08:00:00',
            'end_time'   => '17:00:00',
            'updated_at' => now(),
        ]);
    }

    public function down()
    {
        DB::table('shifts')->where('name', 'Morning')->update([
            'start_time' => '09:00:00',
            'end_time'   => '17:00:00',
            'updated_at' => now(),
        ]);
    }
};
