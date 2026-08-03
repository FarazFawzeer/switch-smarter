<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('technician_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technician_id')->constrained('users')->cascadeOnDelete();
            $table->date('work_date');
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->dateTime('check_in_time')->nullable();
            $table->dateTime('check_out_time')->nullable();
            $table->decimal('total_hours', 5, 2)->nullable();
            $table->decimal('ot_hours', 5, 2)->default(0);
            $table->timestamps();

            $table->unique(['technician_id', 'work_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('technician_attendance');
    }
};