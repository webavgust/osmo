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
        Schema::create('proposal_works', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('proposal_id')->unsigned();
            $table->boolean('cb_process')->default(true);

            $table->mediumText('description')->nullable();
            $table->mediumText('notice')->nullable();
            $table->integer('sort')->unsigned()->default(0);
        });

        Schema::create('proposal_variant_works', function (Blueprint $table) {
            $table->id();
            $table->mediumText('proposal_variant_id')->nullable();
            $table->bigInteger('proposal_work_id')->unsigned();
            $table->float('cost')->unsigned()->default(0);
            $table->float('count')->unsigned()->default(0);
            $table->boolean('cb_partner_discount')->default(false);
            $table->float('discount')->unsigned()->default(0);
            $table->float('total')->unsigned()->default(0);
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('proposal_works');
        Schema::dropIfExists('proposal_variant_works');
    }
};
