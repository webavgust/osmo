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
        Schema::table('proposal_variants', function (Blueprint $table) {
            $table->bigInteger('soft_cost_total_base')->unsigned()->default(0)->after('neuro_cost_total');
            $table->bigInteger('soft_discount_partner')->unsigned()->default(0)->after('soft_cost_total_base');
            $table->bigInteger('soft_cost_total')->unsigned()->default(0)->after('soft_discount_partner');

            $table->bigInteger('work_cost_total_base')->unsigned()->default(0)->after('soft_cost_total');
            $table->bigInteger('work_discount_partner')->unsigned()->default(0)->after('serv_cost_total_base');
            $table->bigInteger('work_cost_total')->unsigned()->default(0)->after('serv_soft_discount_partner');

            $table->bigInteger('cost_total')->unsigned()->default(0)->after('serv_soft_cost_total');

            $table->bigInteger('neuro_nds_cost_total')->unsigned()->default(0)->after('neuro_discount_partner');
            $table->bigInteger('soft_nds_cost_total')->unsigned()->default(0)->after('soft_discount_partner');
            $table->bigInteger('work_nds_cost_total')->unsigned()->default(0)->after('work_discount_partner');
            $table->bigInteger('nds_cost_total')->unsigned()->default(0)->after('work_cost_total');



        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::table('proposal_variants', function (Blueprint $table) {
            $table->dropColumn('soft_cost_total_base');
            $table->dropColumn('soft_discount_partner');
            $table->dropColumn('soft_cost_total');

            $table->dropColumn('serv_cost_total_base');
            $table->dropColumn('serv_soft_discount_partner');
            $table->dropColumn('serv_soft_cost_total');

            $table->dropColumn('cost_total');

            $table->dropColumn('neuro_nds_cost_total');
            $table->dropColumn('soft_nds_cost_total');
            $table->dropColumn('work_nds_cost_total');
            $table->dropColumn('nds_cost_total');
        });
    }
};
