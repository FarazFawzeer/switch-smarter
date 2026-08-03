<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('jobs_records', function (Blueprint $table) {
            $table->foreignId('elevator_unit_id')->nullable()->after('contract_id')
                ->constrained('elevator_units')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('jobs_records', function (Blueprint $table) {
            $table->dropForeign(['elevator_unit_id']);
            $table->dropColumn('elevator_unit_id');
        });
    }
};