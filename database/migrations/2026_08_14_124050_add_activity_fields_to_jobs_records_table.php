<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('jobs_records', function (Blueprint $table) {
            if (! Schema::hasColumn('jobs_records', 'cost')) {
                $table->decimal('cost', 10, 2)->nullable()->after('parts_used');
            }
        });

        // Safe to run even if already correct — MODIFY is idempotent
        DB::statement("ALTER TABLE jobs_records MODIFY COLUMN status ENUM('pending','in_progress','completed','overdue','cancelled','on_hold') NOT NULL DEFAULT 'pending'");
        DB::statement("ALTER TABLE jobs_records MODIFY COLUMN job_type ENUM('ppm', 'repair', 'breakdown', 'casual_job') NOT NULL");
    }

    public function down()
    {
        Schema::table('jobs_records', function (Blueprint $table) {
            $table->dropColumn('cost');
        });
    }
};