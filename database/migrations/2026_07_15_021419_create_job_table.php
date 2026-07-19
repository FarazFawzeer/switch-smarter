<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('jobs_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->enum('job_type', ['ppm', 'repair', 'breakdown']);
            $table->date('scheduled_date')->nullable(); // null until triaged, for breakdowns
            $table->foreignId('assigned_technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete(); // technician, for breakdowns
            $table->enum('status', ['pending', 'in_progress', 'completed', 'overdue', 'cancelled'])->default('pending');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->nullable();
            $table->text('description')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('jobs_records');
    }
};