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
        Schema::table('proposal_variant_scenarios', function (Blueprint $table) {
            $table->string('real_name')->after('scenario_id')->default('');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('proposal_variant_scenarios', function (Blueprint $table) {
            $table->dropColumn('real_name');
        });
    }
};
