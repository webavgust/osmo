<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->uuid('group');
            $table->bigInteger('manager_id')->unsigned();
            $table->bigInteger('company_id')->unsigned();
            $table->bigInteger('partner_id')->unsigned();
            $table->smallInteger('iteration')->unsigned();
            $table->string('name', 200);
            $table->date('sended_at');
            $table->float('rate_unlimited')->default(0);
            $table->string('number', 64);
            $table->mediumInteger('number_int')->default(0);
            $table->timestamps();
        });

        Schema::create('proposal_variants', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('proposal_id')->unsigned();
            $table->boolean('is_main')->default(false);
            $table->string('period_type');
            $table->tinyInteger('period_value')->unsigned()->nullable();
            $table->bigInteger('neuro_cost_total_base')->unsigned()->default(0);
            $table->bigInteger('neuro_discount_customer')->unsigned()->default(0);
            $table->float('discount_partner_p')->unsigned()->default(0);
            $table->bigInteger('neuro_discount_partner')->unsigned()->default(0);
            $table->bigInteger('neuro_cost_total')->unsigned()->default(0);

            $table->timestamps();
        });

        Schema::create('proposal_variant_scenarios', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('proposal_variant_id')->unsigned();
            $table->bigInteger('scenario_id')->unsigned();
            $table->boolean('cb_process')->default(true);
            $table->boolean('cb_nds')->default(true);
            $table->smallInteger('count')->unsigned()->default(0);
            $table->float('discount')->unsigned()->default(0);
            $table->integer('cost')->unsigned()->default(0);
            $table->integer('cost_discount')->unsigned()->default(0);
            $table->bigInteger('nds')->unsigned()->default(0);
            $table->float('cost_year')->unsigned()->default(0);
            $table->float('cost_unlimited')->unsigned()->default(0);
//            $table->integer('cost_total')->unsigned()->default(0);
        });

        Schema::create('neuroservice_proposal_variant_scenario', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('proposal_variant_scenario_id')->unsigned();
            $table->bigInteger('neuroservice_id')->unsigned();
            $table->json('cost')->nullable();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('proposals');
        Schema::dropIfExists('proposal_variants');
        Schema::dropIfExists('proposal_variant_scenarios');
        Schema::dropIfExists('neuroservice_proposal_variant_scenario');
    }
};
