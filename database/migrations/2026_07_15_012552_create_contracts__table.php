<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('project_name');
            $table->string('client_name');
            $table->date('contract_start_date');
            $table->unsignedTinyInteger('duration_years');
            $table->date('contract_end_date'); // computed server-side from start + duration
            $table->date('ppm_start_date');
            $table->unsignedTinyInteger('ppm_visits_per_year');
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active');
            $table->string('contract_document')->nullable(); // path in storage/app/public/contracts
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('contracts');
    }
};