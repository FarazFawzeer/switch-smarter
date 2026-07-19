<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('supervisor_id')->nullable()->after('type')->constrained('users')->nullOnDelete();
            $table->foreignId('engineer_id')->nullable()->after('type')->constrained('users')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['supervisor_id']);
            $table->dropForeign(['engineer_id']);
            $table->dropColumn(['supervisor_id', 'engineer_id']);
        });
    }
};