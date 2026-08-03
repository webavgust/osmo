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
            $table->json('cost_force')->nullable()->after('event_reminder');
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
            $table->dropColumn('cost_force');
        });
    }
};
