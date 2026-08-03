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
        Schema::create('project_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('number');
            $table->bigInteger('project_id')->unsigned();
            $table->string('platform', 32);
            $table->tinyInteger('duration')->unsigned();
            $table->integer('streams')->unsigned();
            $table->smallInteger('sort')->unsigned();
            $table->string('comment', 200)->nullable();
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
        Schema::dropIfExists('project_configurations');
    }
};
