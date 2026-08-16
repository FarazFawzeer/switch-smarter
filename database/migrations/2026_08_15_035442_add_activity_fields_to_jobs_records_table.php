<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('jobs_records', function (Blueprint $table) {
            if (! Schema::hasColumn('jobs_records', 'start_photo')) {
                $table->string('start_photo')->nullable()->after('check_in_lng');
            }
        });
    }

    public function down()
    {
        Schema::table('jobs_records', function (Blueprint $table) {
            $table->dropColumn('start_photo');
        });
    }
};