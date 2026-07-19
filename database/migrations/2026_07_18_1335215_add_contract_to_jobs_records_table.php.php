<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('jobs_records', function (Blueprint $table) {
            $table->dropForeign(['site_id']);
            $table->dropColumn('site_id');
        });

        Schema::table('jobs_records', function (Blueprint $table) {
            $table->foreignId('site_id')->nullable()->after('id')->constrained('sites')->nullOnDelete();
            $table->foreignId('contract_id')->nullable()->after('site_id')->constrained('contracts')->cascadeOnDelete();
        });
    }

    public function down()
    {
        Schema::table('jobs_records', function (Blueprint $table) {
            $table->dropForeign(['contract_id']);
            $table->dropColumn('contract_id');
            $table->dropForeign(['site_id']);
            $table->dropColumn('site_id');
        });

        Schema::table('jobs_records', function (Blueprint $table) {
            $table->foreignId('site_id')->after('id')->constrained('sites')->cascadeOnDelete();
        });
    }
};