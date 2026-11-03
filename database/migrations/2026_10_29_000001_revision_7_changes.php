<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('jobs_records', function (Blueprint $table) {
            // Team jobs: a second/third technician on the same breakdown/repair/casual job gets
            // their own row (own times = own timesheet) linked to the main job, which is the one counted.
            $table->foreignId('parent_job_id')->nullable()->after('id')
                ->constrained('jobs_records')->cascadeOnDelete();

            // Breakdown/repair closure: was the elevator left working or shut down?
            $table->string('unit_status_after', 20)->nullable()->after('status');
            $table->string('shutdown_reason', 500)->nullable()->after('unit_status_after');

            // Set when an engineer manually corrects the check-in/out times
            $table->timestamp('times_adjusted_at')->nullable()->after('check_out_lng');
        });

        // Current state of each elevator — shutdown units are listed as a priority on the dashboard
        Schema::table('elevator_units', function (Blueprint $table) {
            $table->string('operational_status', 20)->default('working')->after('model');
            $table->timestamp('shutdown_since')->nullable()->after('operational_status');
            $table->string('shutdown_reason', 500)->nullable()->after('shutdown_since');
            $table->foreignId('shutdown_job_id')->nullable()->after('shutdown_reason')
                ->constrained('jobs_records')->nullOnDelete();
            $table->index('operational_status');
        });

        // Audit trail of manual timesheet corrections
        Schema::create('timesheet_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_record_id')->constrained('jobs_records')->cascadeOnDelete();
            $table->dateTime('old_check_in')->nullable();
            $table->dateTime('old_check_out')->nullable();
            $table->dateTime('new_check_in')->nullable();
            $table->dateTime('new_check_out')->nullable();
            $table->string('reason', 255);
            $table->foreignId('adjusted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technician_id')->constrained('users')->cascadeOnDelete();
            $table->string('leave_type', 30);
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedSmallInteger('days');
            $table->text('reason');
            $table->string('status', 20)->default('pending'); // pending / approved / rejected / cancelled
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_note', 255)->nullable();
            $table->timestamps();
            $table->index(['technician_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('timesheet_adjustments');

        Schema::table('elevator_units', function (Blueprint $table) {
            $table->dropForeign(['shutdown_job_id']);
            $table->dropIndex(['operational_status']);
            $table->dropColumn(['operational_status', 'shutdown_since', 'shutdown_reason', 'shutdown_job_id']);
        });

        Schema::table('jobs_records', function (Blueprint $table) {
            $table->dropForeign(['parent_job_id']);
            $table->dropColumn(['parent_job_id', 'unit_status_after', 'shutdown_reason', 'times_adjusted_at']);
        });
    }
};
