<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('elevator_units', function (Blueprint $table) {
            if (! Schema::hasColumn('elevator_units', 'custom_fields')) {
                $table->json('custom_fields')->nullable()->after('model');
            }
        });
    }

    public function down()
    {
        Schema::table('elevator_units', function (Blueprint $table) {
            $table->dropColumn('custom_fields');
        });
    }
};