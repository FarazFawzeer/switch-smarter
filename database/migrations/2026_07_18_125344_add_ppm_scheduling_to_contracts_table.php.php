<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->date('ppm_start_date')->nullable()->after('contract_end_date');
            $table->boolean('is_scheduled')->default(false)->after('ppm_start_date');
        });
    }

    public function down()
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['ppm_start_date', 'is_scheduled']);
        });
    }
};