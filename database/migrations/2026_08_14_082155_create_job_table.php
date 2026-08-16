<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE jobs_records MODIFY COLUMN status ENUM('pending','in_progress','completed','overdue','cancelled','on_hold') NOT NULL DEFAULT 'pending'");

        Schema::table('jobs_records', function (Blueprint $table) {
            $table->string('job_code')->nullable()->unique()->after('job_type');
            $table->string('breakdown_cause')->nullable()->after('description');
            $table->text('immediate_action_taken')->nullable()->after('breakdown_cause');
            $table->text('parts_used')->nullable()->after('work_notes'); // JSON array of part names
            $table->string('signature_path')->nullable()->after('completion_photo');
            $table->string('warranty_contract')->nullable()->after('immediate_action_taken'); // Repair only
            $table->string('customer_name')->nullable()->after('warranty_contract'); // Casual Job only
        });
    }

    public function down()
    {
        Schema::table('jobs_records', function (Blueprint $table) {
            $table->dropColumn([
                'job_code', 'breakdown_cause', 'immediate_action_taken',
                'parts_used', 'signature_path', 'warranty_contract', 'customer_name',
            ]);
        });

        DB::statement("ALTER TABLE jobs_records MODIFY COLUMN status ENUM('pending','in_progress','completed','overdue','cancelled') NOT NULL DEFAULT 'pending'");
    }
};