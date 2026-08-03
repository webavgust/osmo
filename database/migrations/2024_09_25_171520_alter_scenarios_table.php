<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('scenarios', function (Blueprint $table) {
            $table->mediumText('work_scenario')->nullable()->after('name');
            $table->mediumText('work_result')->nullable()->after('work_scenario');
            $table->mediumText('event_reminder')->nullable()->after('work_result');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('scenarios', function (Blueprint $table) {
            $table->dropColumn('work_scenario');
            $table->dropColumn('work_result');
            $table->dropColumn('event_reminder');
        });
    }
};
