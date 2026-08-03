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
        Schema::create('access_user_group', function (Blueprint $table) {
            $table->bigInteger('access_id')->unsigned();
            $table->bigInteger('user_group_id')->unsigned();
            $table->tinyInteger('mode')->unsigned();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('access_user_group');
    }
};
