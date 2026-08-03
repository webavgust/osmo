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
        Schema::create('contract_specification_scenarios', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('contract_specification_id')->unsigned();
            $table->bigInteger('scenario_id')->unsigned();
            $table->string('name')->nullable();
            $table->integer('sort');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contract_specification_scenarios');
    }
};
