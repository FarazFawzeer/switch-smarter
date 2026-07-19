<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('elevator_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->string('identification_no');
            $table->string('speed')->nullable();
            $table->string('capacity')->nullable();
            $table->enum('unit_type', ['Elevator', 'Escalator', 'Dumbwaiter'])->default('Elevator');
            $table->enum('elevator_type', ['Passenger', 'Service', 'Freight'])->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('elevator_units');
    }
};