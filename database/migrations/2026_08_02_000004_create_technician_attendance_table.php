<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('technician_attendance', function (Blueprint $table) {
            $table->enum('approval_status', ['pending', 'supervisor_approved', 'engineer_approved', 'manager_approved', 'rejected'])
                ->default('pending')->after('ot_hours');

            $table->foreignId('supervisor_approved_by')->nullable()->after('approval_status')->constrained('users')->nullOnDelete();
            $table->timestamp('supervisor_approved_at')->nullable()->after('supervisor_approved_by');

            $table->foreignId('engineer_approved_by')->nullable()->after('supervisor_approved_at')->constrained('users')->nullOnDelete();
            $table->timestamp('engineer_approved_at')->nullable()->after('engineer_approved_by');

            $table->foreignId('manager_approved_by')->nullable()->after('engineer_approved_at')->constrained('users')->nullOnDelete();
            $table->timestamp('manager_approved_at')->nullable()->after('manager_approved_by');

            $table->text('rejection_reason')->nullable()->after('manager_approved_at');
        });
    }

    public function down()
    {
        Schema::table('technician_attendance', function (Blueprint $table) {
            $table->dropForeign(['supervisor_approved_by']);
            $table->dropForeign(['engineer_approved_by']);
            $table->dropForeign(['manager_approved_by']);
            $table->dropColumn([
                'approval_status', 'supervisor_approved_by', 'supervisor_approved_at',
                'engineer_approved_by', 'engineer_approved_at', 'manager_approved_by',
                'manager_approved_at', 'rejection_reason',
            ]);
        });
    }
};