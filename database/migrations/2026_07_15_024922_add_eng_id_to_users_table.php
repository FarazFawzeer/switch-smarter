<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->foreignId('assigned_engineer_id')->nullable()->after('created_by')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['assigned_engineer_id']);
            $table->dropColumn('assigned_engineer_id');
        });
    }
};