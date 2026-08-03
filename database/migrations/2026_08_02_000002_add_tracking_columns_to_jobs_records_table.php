<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('jobs_records', function (Blueprint $table) {
            $table->dateTime('check_in_time')->nullable()->after('status');
            $table->decimal('check_in_lat', 10, 7)->nullable()->after('check_in_time');
            $table->decimal('check_in_lng', 10, 7)->nullable()->after('check_in_lat');

            $table->dateTime('check_out_time')->nullable()->after('check_in_lng');
            $table->decimal('check_out_lat', 10, 7)->nullable()->after('check_out_time');
            $table->decimal('check_out_lng', 10, 7)->nullable()->after('check_out_lat');

            $table->string('completion_photo')->nullable()->after('check_out_lng');
            $table->text('work_notes')->nullable()->after('completion_photo');
        });
    }

    public function down()
    {
        Schema::table('jobs_records', function (Blueprint $table) {
            $table->dropColumn([
                'check_in_time', 'check_in_lat', 'check_in_lng',
                'check_out_time', 'check_out_lat', 'check_out_lng',
                'completion_photo', 'work_notes',
            ]);
        });
    }
};