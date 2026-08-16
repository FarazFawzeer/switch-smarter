<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('jobs_records', function (Blueprint $table) {
            if (! Schema::hasColumn('jobs_records', 'repair_job_no')) {
                $table->string('repair_job_no')->nullable()->after('job_code');
            }
            if (! Schema::hasColumn('jobs_records', 'quotation_date')) {
                $table->date('quotation_date')->nullable()->after('repair_job_no');
            }
            if (! Schema::hasColumn('jobs_records', 'po_status')) {
                $table->string('po_status')->nullable()->after('quotation_date');
            }
            if (! Schema::hasColumn('jobs_records', 'po_received_date')) {
                $table->date('po_received_date')->nullable()->after('po_status');
            }
            if (! Schema::hasColumn('jobs_records', 'po_number')) {
                $table->string('po_number')->nullable()->after('po_received_date');
            }
            if (! Schema::hasColumn('jobs_records', 'quotation_value')) {
                $table->decimal('quotation_value', 10, 2)->nullable()->after('po_number');
            }
            if (! Schema::hasColumn('jobs_records', 'scope_of_work')) {
                $table->text('scope_of_work')->nullable()->after('quotation_value');
            }
            if (! Schema::hasColumn('jobs_records', 'quotation_attachment')) {
                $table->string('quotation_attachment')->nullable()->after('scope_of_work');
            }
            if (! Schema::hasColumn('jobs_records', 'material_status')) {
                $table->string('material_status')->nullable()->after('quotation_attachment');
            }
            if (! Schema::hasColumn('jobs_records', 'site_status')) {
                $table->string('site_status')->nullable()->after('material_status');
            }
        });
    }

    public function down()
    {
        Schema::table('jobs_records', function (Blueprint $table) {
            $table->dropColumn([
                'repair_job_no', 'quotation_date', 'po_status', 'po_received_date', 'po_number',
                'quotation_value', 'scope_of_work', 'quotation_attachment', 'material_status', 'site_status',
            ]);
        });
    }
};