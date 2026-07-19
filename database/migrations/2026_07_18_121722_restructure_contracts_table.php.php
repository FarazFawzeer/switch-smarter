<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->string('location')->nullable()->after('project_name');
            $table->string('contract_number')->nullable()->after('location');
            $table->unsignedTinyInteger('number_of_elevators')->default(1)->after('contract_number');
            $table->string('route_no')->nullable()->after('assigned_engineer_id');
            $table->foreignId('assigned_supervisor_id')->nullable()->after('assigned_engineer_id')
                ->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_technician_id')->nullable()->after('assigned_supervisor_id')
                ->constrained('users')->nullOnDelete();

            // These move to the future Contract Scheduling section, not part of master data entry
            $table->dropColumn(['duration_years', 'ppm_start_date', 'ppm_visits_per_year']);
        });
    }

    public function down()
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['assigned_supervisor_id']);
            $table->dropForeign(['assigned_technician_id']);
            $table->dropColumn(['location', 'contract_number', 'number_of_elevators', 'route_no', 'assigned_supervisor_id', 'assigned_technician_id']);
            $table->unsignedTinyInteger('duration_years')->default(1);
            $table->date('ppm_start_date')->nullable();
            $table->unsignedTinyInteger('ppm_visits_per_year')->nullable();
        });
    }
};