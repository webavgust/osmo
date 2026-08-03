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
        Schema::create('scenarios', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('scenario_group_id')->unsigned();
            $table->string('name', 128);
            $table->smallInteger('sort')->default(0);
            $table->timestamps();
        });



    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('scenarios2024_09_15_115319_create_scenarios_table.php');
    }
};
