<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Short site reference for each unit (L1, L2, SL3 …), shown next to the Unit ID
        Schema::table('elevator_units', function (Blueprint $table) {
            $table->string('unit_number', 50)->nullable()->after('identification_no');
        });

        Schema::table('jobs_records', function (Blueprint $table) {
            // Groups the PPM visits created by one "Schedule PPM" submit, so it can be undone as a whole
            $table->string('schedule_batch', 36)->nullable()->after('job_code')->index();
            $table->index(['elevator_unit_id', 'job_type', 'scheduled_date'], 'jobs_records_unit_type_date_index');
        });

        // History of scheduled-date changes, so a reschedule can be undone
        Schema::create('job_reschedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_record_id')->constrained('jobs_records')->cascadeOnDelete();
            $table->date('old_date');
            $table->date('new_date');
            $table->string('reason', 255)->nullable();
            $table->foreignId('rescheduled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('undone_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('job_reschedules');

        Schema::table('jobs_records', function (Blueprint $table) {
            $table->dropIndex('jobs_records_unit_type_date_index');
            $table->dropIndex(['schedule_batch']);
            $table->dropColumn('schedule_batch');
        });

        Schema::table('elevator_units', function (Blueprint $table) {
            $table->dropColumn('unit_number');
        });
    }
};
