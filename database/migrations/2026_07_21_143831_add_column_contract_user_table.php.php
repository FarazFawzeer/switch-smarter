<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn('route_no');
            $table->foreignId('route_id')->nullable()->after('assigned_engineer_id')->constrained('routes')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['route_id']);
            $table->dropColumn('route_id');
            $table->string('route_no')->nullable();
        });
    }
};